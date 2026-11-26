<?php

namespace App\Ai\Tools;

use App\Models\Guru;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class EmisDirectoryLookupTool implements Tool
{
    public function __construct(private readonly User $user) {}

    public function description(): Stringable|string
    {
        return 'Cari data siswa EMIS 4.0 (nama, NISN, rombel, PIP, alamat) atau GTK (nama, NPK, NUPTK, mapel) milik pengguna terautentikasi.';
    }

    public function handle(Request $request): Stringable|string
    {
        $category = (string) $request->string('kategori', 'student');
        $keyword = trim((string) $request->string('kata_kunci', ''));
        $limit = max(1, min(10, (int) $request->integer('limit', 5)));

        if ($category === 'guru') {
            $gurus = Guru::query()
                ->where('user_id', $this->user->id)
                ->when($keyword !== '', function ($q) use ($keyword): void {
                    $q->where(function ($sub) use ($keyword): void {
                        $sub->where('nama', 'like', "%{$keyword}%")
                            ->orWhere('nuptk', 'like', "%{$keyword}%")
                            ->orWhere('mapel_utama', 'like', "%{$keyword}%");
                    });
                })
                ->limit($limit)
                ->get(['id', 'nama', 'status_pegawai', 'jenis_ptk', 'mapel_utama', 'status_sertifikasi', 'jtm']);

            return json_encode(['gurus' => $gurus->toArray()], JSON_THROW_ON_ERROR);
        }

        $students = Student::query()
            ->where('user_id', $this->user->id)
            ->when($keyword !== '', function ($q) use ($keyword): void {
                $q->where(function ($sub) use ($keyword): void {
                    $sub->where('nama', 'like', "%{$keyword}%")
                        ->orWhere('nisn', 'like', "%{$keyword}%")
                        ->orWhere('kelas', 'like', "%{$keyword}%")
                        ->orWhere('alamat', 'like', "%{$keyword}%");
                });
            })
            ->limit($limit)
            ->get()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'nama' => $student->nama,
                'nisn' => $student->nisn,
                'rombel' => $student->kelas,
                'status' => $student->status,
                'pip' => $student->emis_data['statusPip'] ?? 'Tidak',
            ]);

        return json_encode(['students' => $students->values()->all()], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'kategori' => $schema->string()
                ->enum(['student', 'guru'])
                ->description('Jenis data EMIS: student untuk peserta didik, guru untuk GTK.')
                ->required(),
            'kata_kunci' => $schema->string()
                ->description('Nama, NISN, rombel, atau mapel yang dicari (boleh kosong untuk sampel teratas).'),
            'limit' => $schema->integer()
                ->min(1)
                ->max(10)
                ->description('Maksimal baris data yang diambil.'),
        ];
    }
}
