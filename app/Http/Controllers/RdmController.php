<?php

namespace App\Http\Controllers;

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

class RdmController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        EmisPayloadMapper::ensureDefaultRombels($user);
        $students = $user->students()->orderBy('kelas')->orderBy('nama')->get();
        $rombels = $user->rombels()->orderBy('tingkat')->orderBy('kode')->get();
        $scores = $user->rdmScores()->get();

        $rdmMap = [];
        foreach ($scores as $score) {
            $sid = (string) $score->student_id;
            $rdmMap[$sid][$score->mapel][(string) $score->semester] = $score->nilai;
        }

        return Inertia::render('Siswa/Rdm', [
            'students' => $students->map(fn (Student $student): array => $student->toEmisPayload())->values(),
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

    public function save(Request $request, Student $student): RedirectResponse
    {
        abort_unless($student->user_id === $request->user()->id, 403);

        $request->validate([
            'scores' => ['required', 'array'],
        ]);

        $scores = $request->input('scores', []);
        if (! is_array($scores)) {
            $scores = [];
        }

        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user, $student, $scores): void {
            foreach ($scores as $mapel => $semesters) {
                if (! is_string($mapel) || ! is_array($semesters)) {
                    continue;
                }

                foreach ($semesters as $smt => $nilai) {
                    $semester = (int) $smt;
                    if ($semester < 1 || $semester > 12) {
                        continue;
                    }

                    if ($nilai === null || $nilai === '') {
                        $student->rdmScores()
                            ->where('mapel', $mapel)
                            ->where('semester', $semester)
                            ->delete();
                    } elseif (is_numeric($nilai)) {
                        $numeric = max(0, min(100, (float) $nilai));
                        RdmScore::query()->updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'mapel' => $mapel,
                                'semester' => $semester,
                            ],
                            [
                                'user_id' => $user->id,
                                'nilai' => $numeric,
                            ],
                        );
                    }
                }
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Nilai RDM MI untuk {$student->nama} berhasil disimpan permanen.",
        ]);

        return back();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'entries' => ['required', 'array', 'min:1', 'max:2000'],
            'entries.*.nisn' => ['required', 'string', 'max:30'],
            'entries.*.semester' => ['required', 'integer', 'min:1', 'max:12'],
            'entries.*.scores' => ['required', 'array'],
        ]);

        $entries = $request->input('entries', []);
        if (! is_array($entries)) {
            $entries = [];
        }

        /** @var User $user */
        $user = $request->user();
        $studentsByNisn = $user->students()->whereNotNull('nisn')->get()->keyBy('nisn');
        $updated = 0;

        DB::transaction(function () use ($user, $entries, $studentsByNisn, &$updated): void {
            foreach ($entries as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $nisn = trim((string) ($entry['nisn'] ?? ''));
                /** @var Student|null $student */
                $student = $studentsByNisn->get($nisn);
                if (! $student) {
                    continue;
                }

                $semester = (int) ($entry['semester'] ?? 0);
                if ($semester < 1 || $semester > 12) {
                    continue;
                }

                $entryScores = $entry['scores'] ?? [];
                if (! is_array($entryScores)) {
                    continue;
                }

                foreach ($entryScores as $mapel => $nilai) {
                    if (! is_string($mapel) || ! is_numeric($nilai)) {
                        continue;
                    }

                    RdmScore::query()->updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'mapel' => $mapel,
                            'semester' => $semester,
                        ],
                        [
                            'user_id' => $user->id,
                            'nilai' => max(0, min(100, (float) $nilai)),
                        ],
                    );
                }

                $updated++;
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Berhasil mengimpor nilai RDM MI untuk {$updated} siswa.",
        ]);

        return back();
    }
}
