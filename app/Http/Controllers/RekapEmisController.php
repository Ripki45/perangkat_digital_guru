<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\RdmScore;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\User;
use App\Services\EmisPayloadMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RekapEmisController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        EmisPayloadMapper::ensureDefaultRombels($user);
        $students = $user->students()->orderBy('kelas')->orderBy('nama')->get();
        $gurus = $user->gurus()->orderBy('nama')->get();
        $rombels = $user->rombels()->orderBy('tingkat')->orderBy('kode')->get();
        $scores = $user->rdmScores()->get();

        $rdmMap = [];
        foreach ($scores as $score) {
            $sid = (string) $score->student_id;
            $rdmMap[$sid][$score->mapel][(string) $score->semester] = $score->nilai;
        }

        return Inertia::render('Siswa/Rekap', [
            'students' => $students->map(fn (Student $student): array => $student->toEmisPayload())->values(),
            'gurus' => $gurus->map(fn (Guru $guru): array => $guru->toEmisPayload())->values(),
            'rombels' => $rombels->map(fn (Rombel $rombel): array => [
                'id' => $rombel->id,
                'tingkat' => $rombel->tingkat,
                'kode' => $rombel->kode,
                'wali' => $rombel->wali ?? '',
            ])->values(),
            'rdmScores' => $rdmMap,
            'madrasahProfile' => EmisPayloadMapper::madrasahProfilePayload($user),
        ]);
    }

    public function restoreBackup(Request $request): RedirectResponse
    {
        $request->validate([
            'madrasahProfile' => ['nullable', 'array'],
            'customRombels' => ['nullable', 'array'],
            'students' => ['nullable', 'array'],
            'gurus' => ['nullable', 'array'],
            'rdmScores' => ['nullable', 'array'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $profile = $request->input('madrasahProfile');
        $rombels = $request->input('customRombels');
        $students = $request->input('students');
        $gurus = $request->input('gurus');
        $rdmScores = $request->input('rdmScores');

        DB::transaction(function () use ($user, $profile, $rombels, $students, $gurus, $rdmScores): void {
            if (is_array($profile)) {
                $existing = $user->teacherProfile;
                $user->teacherProfile()->updateOrCreate([], [
                    'nama_guru' => $existing?->nama_guru ?: ((string) ($profile['operatorNama'] ?? $user->name)),
                    'tahun_ajaran' => $existing?->tahun_ajaran ?: '2026/2027',
                    'semester' => $existing?->semester ?: 'Ganjil',
                    'nama_madrasah' => (string) ($profile['namaMadrasah'] ?? 'MADRASAH IBTIDAIYAH NEGERI 1 CIAMIS'),
                    'npsn' => ! empty($profile['npsn']) ? (string) $profile['npsn'] : null,
                    'nsm' => ! empty($profile['nsm']) ? (string) $profile['nsm'] : null,
                    'kemenag_kab' => ! empty($profile['kemenagKab']) ? (string) $profile['kemenagKab'] : null,
                    'tempat_surat' => ! empty($profile['tempatSurat']) ? (string) $profile['tempatSurat'] : null,
                    'alamat' => ! empty($profile['alamat']) ? (string) $profile['alamat'] : null,
                    'website' => ! empty($profile['website']) ? (string) $profile['website'] : null,
                    'email_madrasah' => ! empty($profile['email']) ? (string) $profile['email'] : null,
                    'nama_kepala_madrasah' => ! empty($profile['kepalaNama']) ? (string) $profile['kepalaNama'] : null,
                    'nip_kepala_madrasah' => ! empty($profile['kepalaNip']) ? (string) $profile['kepalaNip'] : null,
                    'operator_nama' => ! empty($profile['operatorNama']) ? (string) $profile['operatorNama'] : null,
                    'operator_hp' => ! empty($profile['operatorHp']) ? (string) $profile['operatorHp'] : null,
                    'logo_kiri' => ! empty($profile['logoKiri']) ? (string) $profile['logoKiri'] : null,
                    'logo_kanan' => ! empty($profile['logoKanan']) ? (string) $profile['logoKanan'] : null,
                ]);
            }

            if (is_array($rombels)) {
                $user->rombels()->delete();
                foreach ($rombels as $rombel) {
                    if (! is_array($rombel) || empty($rombel['kode'])) {
                        continue;
                    }
                    $user->rombels()->create([
                        'tingkat' => (string) ($rombel['tingkat'] ?? '1'),
                        'kode' => (string) $rombel['kode'],
                        'wali' => ! empty($rombel['wali']) ? (string) $rombel['wali'] : null,
                    ]);
                }
            }

            $oldToNewStudentIds = [];
            if (is_array($students)) {
                $user->rdmScores()->delete();
                $user->students()->delete();
                foreach ($students as $row) {
                    if (! is_array($row) || empty($row['nama'])) {
                        continue;
                    }
                    $oldId = (string) ($row['id'] ?? '');
                    $created = $user->students()->create(EmisPayloadMapper::studentAttributesFromEmis($row));
                    if ($oldId !== '') {
                        $oldToNewStudentIds[$oldId] = $created->id;
                    }
                }
            }

            if (is_array($gurus)) {
                $user->gurus()->delete();
                foreach ($gurus as $row) {
                    if (! is_array($row) || empty($row['nama'])) {
                        continue;
                    }
                    $user->gurus()->create(EmisPayloadMapper::guruAttributesFromEmis($row));
                }
            }

            if (is_array($rdmScores)) {
                foreach ($rdmScores as $oldSid => $subjects) {
                    $newStudentId = $oldToNewStudentIds[(string) $oldSid] ?? null;
                    if (! $newStudentId || ! is_array($subjects)) {
                        continue;
                    }
                    foreach ($subjects as $mapel => $semesters) {
                        if (! is_string($mapel) || ! is_array($semesters)) {
                            continue;
                        }
                        foreach ($semesters as $smt => $nilai) {
                            $semester = (int) $smt;
                            if ($semester < 1 || $semester > 12 || ! is_numeric($nilai)) {
                                continue;
                            }
                            RdmScore::query()->updateOrCreate(
                                [
                                    'student_id' => $newStudentId,
                                    'mapel' => $mapel,
                                    'semester' => $semester,
                                ],
                                [
                                    'user_id' => $user->id,
                                    'nilai' => max(0, min(100, (float) $nilai)),
                                ],
                            );
                        }
                    }
                }
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Database berhasil dipulihkan dari file backup JSON.',
        ]);

        return back();
    }

    public function clearAll(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $user->rdmScores()->delete();
            $user->students()->delete();
            $user->gurus()->delete();
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Seluruh data siswa, RDM, dan GTK berhasil dikosongkan.',
        ]);

        return back();
    }
}
