<?php

namespace App\Http\Controllers;

use App\Models\Rombel;
use App\Models\Student;
use App\Models\User;
use App\Services\EmisPayloadMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        EmisPayloadMapper::ensureDefaultRombels($user);

        $students = $user->students()
            ->orderBy('kelas')
            ->orderBy('nama')
            ->get();

        $rombels = $user->rombels()->orderBy('tingkat')->orderBy('kode')->get();

        $classes = $students
            ->pluck('kelas')
            ->merge($rombels->pluck('kode'))
            ->filter()
            ->unique()
            ->sort(fn (string $a, string $b): int => strnatcasecmp($a, $b))
            ->values();

        return Inertia::render('Siswa/Index', [
            'students' => $students->map(fn (Student $student): array => $this->toPayload($student))->values(),
            'classes' => $classes,
            'rombels' => $rombels->map(fn (Rombel $rombel): array => [
                'id' => $rombel->id,
                'tingkat' => $rombel->tingkat,
                'kode' => $rombel->kode,
                'wali' => $rombel->wali ?? '',
            ])->values(),
            'stats' => [
                'total' => $students->count(),
                'male' => $students->where('jenis_kelamin', 'L')->count(),
                'female' => $students->where('jenis_kelamin', 'P')->count(),
                'classes' => $classes->count(),
            ],
            'madrasahProfile' => EmisPayloadMapper::madrasahProfilePayload($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($this->isEmisFormPayload($request)) {
            $data = $this->validateEmisStudent($request);
            $attributes = EmisPayloadMapper::studentAttributesFromEmis($data);
            $user->students()->create($attributes);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Data siswa {$attributes['nama']} berhasil ditambahkan.",
            ]);

            return back();
        }

        $data = $this->validateStudent($request);
        $user->students()->create($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Data siswa {$data['nama']} berhasil ditambahkan.",
        ]);

        return back();
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->ensureOwner($request, $student);

        if ($this->isEmisFormPayload($request)) {
            $data = $this->validateEmisStudent($request);
            $attributes = EmisPayloadMapper::studentAttributesFromEmis($data);
            $student->update($attributes);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Data siswa {$student->nama} berhasil diperbarui.",
            ]);

            return back();
        }

        $data = $this->validateStudent($request);
        $student->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Data siswa {$student->nama} berhasil diperbarui.",
        ]);

        return back();
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->ensureOwner($request, $student);

        $nama = $student->nama;
        $student->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Data siswa {$nama} berhasil dihapus.",
        ]);

        return back();
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payload' => ['required', 'string', 'max:200000'],
        ]);

        $lines = preg_split('/\r\n|\r|\n/', trim($validated['payload'])) ?: [];

        abort_if(count($lines) > 500, 422, 'Maksimal 500 baris dalam sekali impor.');

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($request, $lines, &$imported, &$skipped): void {
            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $columns = array_map('trim', explode($this->detectDelimiter($line), $line));

                if ($this->isHeaderRow($columns)) {
                    continue;
                }

                $nama = Str::limit($columns[0], 150, '');

                if ($nama === '') {
                    $skipped++;

                    continue;
                }

                $payload = [
                    'nama' => $nama,
                    'jenis_kelamin' => $this->normalizeGender($columns[1] ?? null),
                    'nisn' => Str::limit($columns[2] ?? '', 30, '') ?: null,
                    'nis' => Str::limit($columns[3] ?? '', 30, '') ?: null,
                    'kelas' => Str::limit($columns[4] ?? '', 20, '') ?: '1',
                    'tempat_lahir' => Str::limit($columns[5] ?? '', 150, '') ?: null,
                    'tanggal_lahir' => $this->parseDate($columns[6] ?? null),
                    'nama_wali' => Str::limit($columns[7] ?? '', 150, '') ?: null,
                    'no_hp' => Str::limit($columns[8] ?? '', 30, '') ?: null,
                    'alamat' => Str::limit($columns[9] ?? '', 500, '') ?: null,
                    'status' => 'Aktif',
                ];

                $validator = Validator::make($payload, [
                    'nama' => ['required', 'string', 'max:150'],
                    'jenis_kelamin' => ['required', 'in:L,P'],
                    'kelas' => ['required', 'string', 'max:20'],
                    'tanggal_lahir' => ['nullable', 'date'],
                ]);

                if ($validator->fails()) {
                    $skipped++;

                    continue;
                }

                $request->user()->students()->create($validator->validated());
                $imported++;
            }
        });

        $message = "{$imported} data siswa berhasil diimpor.";

        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati karena tidak lengkap.";
        }

        Inertia::flash('toast', [
            'type' => $imported > 0 ? 'success' : 'warning',
            'message' => $message,
        ]);

        return back();
    }

    public function importEmis(Request $request): RedirectResponse
    {
        $request->validate([
            'students' => ['required', 'array', 'min:1', 'max:1000'],
            'students.*.nama' => ['required', 'string', 'max:150'],
        ]);

        $rows = $request->input('students', []);
        if (! is_array($rows)) {
            $rows = [];
        }

        /** @var User $user */
        $user = $request->user();
        $imported = 0;

        DB::transaction(function () use ($user, $rows, &$imported): void {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $attrs = EmisPayloadMapper::studentAttributesFromEmis($row);
                if ($attrs['nama'] === '') {
                    continue;
                }

                $nisn = $attrs['nisn'];
                $existing = $nisn ? $user->students()->where('nisn', $nisn)->first() : null;

                if ($existing) {
                    if (empty($attrs['foto']) && $existing->foto) {
                        $attrs['foto'] = $existing->foto;
                    }
                    $existing->update($attrs);
                } else {
                    $user->students()->create($attrs);
                }

                $imported++;
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Berhasil mengimpor {$imported} data siswa EMIS 4.0 dari Excel.",
        ]);

        return back();
    }

    public function loadSample(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            foreach (EmisPayloadMapper::defaultRombels() as $rombel) {
                $user->rombels()->firstOrCreate(
                    ['kode' => $rombel['kode']],
                    ['tingkat' => $rombel['tingkat'], 'wali' => $rombel['wali'] ?: null],
                );
            }

            foreach (EmisPayloadMapper::sampleStudents() as $row) {
                $existing = $user->students()->where('nisn', $row['nisn'])->first();
                $attrs = EmisPayloadMapper::studentAttributesFromEmis($row);
                if ($existing) {
                    $existing->update($attrs);
                } else {
                    $user->students()->create($attrs);
                }
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Data sampel siswa EMIS 4.0 MIN 1 Ciamis berhasil dimuat.',
        ]);

        return back();
    }

    private function isEmisFormPayload(Request $request): bool
    {
        return $request->hasAny(['rombel', 'jk', 'nik', 'nisLokal', 'tingkat', 'statusSiswa']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStudent(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nisn' => ['nullable', 'string', 'max:30'],
            'nis' => ['nullable', 'string', 'max:30'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'kelas' => ['required', 'string', 'max:20'],
            'tempat_lahir' => ['nullable', 'string', 'max:150'],
            'tanggal_lahir' => ['nullable', 'date'],
            'nama_wali' => ['nullable', 'string', 'max:150'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:Aktif,Lulus,Pindah,Keluar,Mutasi Keluar,Putus Sekolah'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEmisStudent(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nisn' => ['required', 'regex:/^\d{10}$/'],
            'nik' => ['required', 'regex:/^\d{16}$/'],
            'jk' => ['required', 'in:L,P'],
            'rombel' => ['required', 'string', 'max:30'],
            'tingkat' => ['nullable', 'in:1,2,3,4,5,6'],
            'statusSiswa' => ['required', 'in:Aktif,Lulus,Pindah,Keluar,Mutasi Keluar,Putus Sekolah'],
            'tglLahir' => ['nullable', 'date'],
        ]) + $request->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toPayload(Student $student): array
    {
        return [
            ...$student->toEmisPayload(),
            'id' => $student->id,
            'nama' => $student->nama,
            'nisn' => $student->nisn,
            'nis' => $student->nis,
            'jenis_kelamin' => $student->jenis_kelamin,
            'kelas' => $student->kelas,
            'tempat_lahir' => $student->tempat_lahir,
            'tanggal_lahir' => $student->tanggal_lahir?->format('Y-m-d'),
            'nama_wali' => $student->nama_wali,
            'no_hp' => $student->no_hp,
            'alamat' => $student->alamat,
            'status' => $student->status,
            'catatan' => $student->catatan,
            'created_at' => $student->created_at?->toISOString(),
        ];
    }

    private function ensureOwner(Request $request, Student $student): void
    {
        abort_unless($student->user_id === $request->user()->id, 403);
    }

    /**
     * @return non-empty-string
     */
    private function detectDelimiter(string $line): string
    {
        return match (true) {
            str_contains($line, "\t") => "\t",
            str_contains($line, '|') => '|',
            str_contains($line, ';') => ';',
            default => ',',
        };
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function isHeaderRow(array $columns): bool
    {
        $first = Str::lower(trim($columns[0] ?? ''));

        return in_array($first, ['nama', 'nama siswa', 'name'], true);
    }

    private function normalizeGender(?string $value): string
    {
        $value = Str::upper(trim((string) $value));

        return Str::startsWith($value, ['P', 'W', 'F']) ? 'P' : 'L';
    }

    private function parseDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
