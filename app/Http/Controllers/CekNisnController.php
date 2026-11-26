<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Services\EmisPayloadMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CekNisnController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $sampleStudents = $user->students()
            ->whereNotNull('nisn')
            ->where('nisn', '!=', '')
            ->orderBy('kelas')
            ->orderBy('nama')
            ->limit(6)
            ->get(['nisn', 'kelas', 'status']);

        return Inertia::render('Siswa/CekNisn', [
            'sampleHints' => $sampleStudents->map(fn (Student $student): array => [
                'nisn' => (string) $student->nisn,
                'label' => $student->status === 'Lulus' ? 'Lulus' : "Kls {$student->kelas}",
            ])->values(),
            'madrasahProfile' => EmisPayloadMapper::madrasahProfilePayload($user),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nisn' => ['required', 'regex:/^\d{10}$/'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $student = $user->students()->where('nisn', $validated['nisn'])->first();

        if (! $student) {
            return response()->json([
                'found' => false,
                'message' => "Data siswa dengan NISN \"{$validated['nisn']}\" tidak ditemukan di database Anda.",
            ], 404);
        }

        $scores = $student->rdmScores()->get();
        $semesterBuckets = [];
        foreach ($scores as $score) {
            $semesterBuckets[$score->semester][] = $score->nilai;
        }

        $semesterAverages = [];
        $allAverages = [];
        for ($smt = 1; $smt <= 12; $smt++) {
            if (! empty($semesterBuckets[$smt])) {
                $avg = round(array_sum($semesterBuckets[$smt]) / count($semesterBuckets[$smt]), 1);
                $semesterAverages[$smt] = $avg;
                $allAverages[] = $avg;
            } else {
                $semesterAverages[$smt] = null;
            }
        }

        return response()->json([
            'found' => true,
            'student' => $student->toEmisPayload(),
            'rdm' => [
                'semesters' => $semesterAverages,
                'overall' => count($allAverages) > 0
                    ? round(array_sum($allAverages) / count($allAverages), 2)
                    : null,
            ],
        ]);
    }
}
