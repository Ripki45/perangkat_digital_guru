<?php

namespace App\Services;

use App\Models\EmisSemanticDocument;
use App\Models\Guru;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;

class EmisSemanticSearchService
{
    public function hasConfiguredEmbeddingProvider(): bool
    {
        if (Embeddings::isFaked()) {
            return true;
        }

        $provider = (string) config('ai.default_for_embeddings', 'openai');
        $key = config("ai.providers.{$provider}.key");

        return is_string($key) && trim($key) !== '';
    }

    public function supportsNativeVectorDatabase(): bool
    {
        return in_array(DB::getDriverName(), ['pgsql', 'mariadb'], true);
    }

    /**
     * Build semantic chunks for all students and gurus belonging to a user.
     *
     * @return array{indexed: int, mode: string}
     */
    public function indexUserRecords(User $user): array
    {
        $items = [];

        $user->students()->with('rdmScores')->chunk(100, function (Collection $students) use (&$items): void {
            /** @var Student $student */
            foreach ($students as $student) {
                $payload = $student->toEmisPayload();
                $avgRdm = $student->rdmScores->isNotEmpty()
                    ? number_format((float) $student->rdmScores->avg('nilai'), 1)
                    : 'belum ada';

                $items[] = [
                    'source_type' => 'student',
                    'source_id' => $student->id,
                    'title' => "{$payload['nama']} (Kelas {$payload['rombel']} | NISN {$payload['nisn']})",
                    'content' => implode('. ', array_filter([
                        "Siswa {$payload['nama']}, jenis kelamin {$payload['jk']}, kelas/rombel {$payload['rombel']}, status {$payload['statusSiswa']}",
                        "NISN {$payload['nisn']}, TTL {$payload['tempatLahir']} {$payload['tglLahir']}",
                        "Orang tua/Wali: Ayah {$payload['ayahNama']}, Ibu {$payload['ibuNama']}",
                        "Alamat: {$payload['alamatJalan']} {$payload['desa']} {$payload['kecamatan']} {$payload['kabupaten']}",
                        "Bantuan sosial PIP: {$payload['statusPip']}, PKH: {$payload['statusPkh']}, tempat tinggal: {$payload['tempatTinggal']}",
                        $payload['hobi'] !== '' ? "Hobi {$payload['hobi']}, cita-cita {$payload['citaCita']}" : null,
                        "Rata-rata nilai RDM: {$avgRdm}",
                    ])),
                    'metadata' => [
                        'rombel' => $payload['rombel'],
                        'nisn' => $payload['nisn'],
                        'status' => $payload['statusSiswa'],
                    ],
                ];
            }
        });

        $user->gurus()->chunk(100, function (Collection $gurus) use (&$items): void {
            /** @var Guru $guru */
            foreach ($gurus as $guru) {
                $payload = $guru->toEmisPayload();
                $items[] = [
                    'source_type' => 'guru',
                    'source_id' => $guru->id,
                    'title' => trim("{$payload['gelarDepan']} {$payload['nama']} {$payload['gelarBelakang']}")." ({$payload['jenisPtk']})",
                    'content' => implode('. ', [
                        "GTK {$payload['nama']}, jabatan {$payload['jenisPtk']}, status kepegawaian {$payload['statusPegawai']} ({$payload['statusAktif']})",
                        "NPK {$payload['npk']}, NUPTK {$payload['nuptk']}, pendidikan {$payload['pendidikan']} {$payload['prodi']}",
                        "Sertifikasi {$payload['statusSertifikasi']}, mapel utama {$payload['mapelUtama']}, beban JTM {$payload['jtm']} jam",
                    ]),
                    'metadata' => [
                        'status_pegawai' => $payload['statusPegawai'],
                        'jenis_ptk' => $payload['jenisPtk'],
                    ],
                ];
            }
        });

        if ($items === []) {
            return ['indexed' => 0, 'mode' => 'empty'];
        }

        $useAiEmbeddings = $this->hasConfiguredEmbeddingProvider();
        $vectors = [];
        $modelName = $useAiEmbeddings ? (string) config('ai.default_for_embeddings', 'openai') : 'local-trigram-v1';

        if ($useAiEmbeddings) {
            foreach (array_chunk(array_column($items, 'content'), 50) as $batch) {
                $response = Embeddings::for($batch)->generate();
                foreach ($response->embeddings as $vec) {
                    $vectors[] = $vec;
                }
            }
        }

        DB::transaction(function () use ($user, $items, $vectors, $useAiEmbeddings, $modelName): void {
            foreach ($items as $idx => $item) {
                $vector = $useAiEmbeddings
                    ? ($vectors[$idx] ?? $this->buildDeterministicVector($item['content']))
                    : ($this->supportsNativeVectorDatabase() ? null : $this->buildDeterministicVector($item['content']));

                EmisSemanticDocument::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'source_type' => $item['source_type'],
                        'source_id' => $item['source_id'],
                    ],
                    [
                        'title' => $item['title'],
                        'content' => $item['content'],
                        'metadata' => $item['metadata'],
                        'embedding' => $vector,
                        'embedding_model' => $modelName,
                    ],
                );
            }
        });

        return [
            'indexed' => count($items),
            'mode' => $useAiEmbeddings ? 'ai-embeddings' : 'local-trigram-v1',
        ];
    }

    /**
     * Search semantic documents using native Postgres/MariaDB vector queries or SQLite cosine similarity.
     *
     * @return array{engine: string, results: list<array{id: int, source_type: string, source_id: int|null, title: string, content: string, metadata: array<string, mixed>|null, similarity: float}>}
     */
    public function search(User $user, string $query, int $limit = 8, float $minSimilarity = 0.2): array
    {
        $cleanQuery = trim($query);
        if ($cleanQuery === '') {
            return ['engine' => 'empty', 'results' => []];
        }

        $useAiEmbeddings = $this->hasConfiguredEmbeddingProvider();
        $queryVector = $useAiEmbeddings
            ? Embeddings::for([$cleanQuery])->generate()->first()
            : $this->buildDeterministicVector($cleanQuery);

        // Native Laravel 13 Vector Query Builder when running on Postgres (pgvector) or MariaDB 11.7+
        if ($this->supportsNativeVectorDatabase() && $useAiEmbeddings) {
            $docs = EmisSemanticDocument::query()
                ->where('user_id', $user->id)
                ->select(['id', 'source_type', 'source_id', 'title', 'content', 'metadata'])
                ->selectVectorDistance('embedding', $queryVector, 'vector_distance')
                ->whereVectorSimilarTo('embedding', $queryVector, minSimilarity: $minSimilarity)
                ->limit($limit)
                ->get();

            return [
                'engine' => 'native-vector-'.DB::getDriverName(),
                'results' => array_values($docs->map(function (EmisSemanticDocument $doc): array {
                    $dist = (float) ($doc->getAttribute('vector_distance') ?? 0);

                    return [
                        'id' => $doc->id,
                        'source_type' => $doc->source_type,
                        'source_id' => $doc->source_id,
                        'title' => $doc->title,
                        'content' => $doc->content,
                        'metadata' => $doc->metadata,
                        'similarity' => round(max(0, 1 - $dist), 4),
                    ];
                })->all()),
            ];
        }

        // SQLite / MySQL compatible cosine similarity over stored vector embeddings
        $docs = EmisSemanticDocument::query()
            ->where('user_id', $user->id)
            ->get();

        if ($docs->isEmpty()) {
            $this->indexUserRecords($user);
            $docs = EmisSemanticDocument::query()->where('user_id', $user->id)->get();
        }

        $scored = array_values($docs->map(function (EmisSemanticDocument $doc) use ($queryVector, $cleanQuery): array {
            $docVec = is_array($doc->embedding) ? $doc->embedding : $this->buildDeterministicVector($doc->content);
            $cosine = count($docVec) === count($queryVector)
                ? $this->cosineSimilarity($queryVector, $docVec)
                : 0.0;

            $keywordBoost = str_contains(mb_strtolower($doc->content), mb_strtolower($cleanQuery)) ? 0.25 : 0.0;
            $score = min(1.0, $cosine + $keywordBoost);

            return [
                'id' => $doc->id,
                'source_type' => $doc->source_type,
                'source_id' => $doc->source_id,
                'title' => $doc->title,
                'content' => $doc->content,
                'metadata' => $doc->metadata,
                'similarity' => round($score, 4),
            ];
        })
            ->filter(fn (array $row): bool => $row['similarity'] >= $minSimilarity)
            ->sortByDesc('similarity')
            ->take($limit)
            ->all());

        return [
            'engine' => $useAiEmbeddings ? 'ai-embeddings-cosine' : 'sqlite-cosine-trigram',
            'results' => $scored,
        ];
    }

    /**
     * Deterministic 64-dim trigram vector for offline/SQLite semantic similarity without external API keys.
     *
     * @return list<float>
     */
    private function buildDeterministicVector(string $text): array
    {
        $dims = 64;
        $vec = array_fill(0, $dims, 0.0);
        $norm = preg_replace('/\s+/', ' ', mb_strtolower(trim($text))) ?? '';
        $len = mb_strlen($norm);

        if ($len === 0) {
            return $vec;
        }

        for ($i = 0; $i < max(1, $len - 2); $i++) {
            $gram = mb_substr($norm, $i, 3);
            $bucket = abs(crc32($gram)) % $dims;
            $vec[$bucket] += 1.0;
        }

        $mag = sqrt(array_sum(array_map(fn (float $v): float => $v * $v, $vec)));
        if ($mag > 0.0) {
            foreach ($vec as $idx => $val) {
                $vec[$idx] = $val / $mag;
            }
        }

        return $vec;
    }

    /**
     * @param  array<int, float|int>  $a
     * @param  array<int, float|int>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $magA = 0.0;
        $magB = 0.0;

        foreach ($a as $i => $va) {
            $fa = (float) $va;
            $fb = (float) ($b[$i] ?? 0.0);
            $dot += $fa * $fb;
            $magA += $fa * $fa;
            $magB += $fb * $fb;
        }

        if ($magA <= 0.0 || $magB <= 0.0) {
            return 0.0;
        }

        return max(0.0, $dot / (sqrt($magA) * sqrt($magB)));
    }
}
