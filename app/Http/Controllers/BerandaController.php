<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BerandaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->teacherProfile;
        $students = $user->students()->get();
        $documents = $user->legalDocuments()->get();

        $classes = $students
            ->pluck('kelas')
            ->filter()
            ->unique()
            ->sort(fn (string $a, string $b) => strnatcasecmp($a, $b))
            ->values();

        $classDistribution = $classes->map(fn (string $kelas) => [
            'kelas' => $kelas,
            'total' => $students->where('kelas', $kelas)->count(),
        ]);

        $recentStudents = $students
            ->sortByDesc('created_at')
            ->take(5)
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'nama' => $student->nama,
                'nisn' => $student->nisn,
                'kelas' => $student->kelas,
                'jenis_kelamin' => $student->jenis_kelamin,
                'status' => $student->status,
            ])
            ->values();

        return Inertia::render('Beranda', [
            'profile' => $profile?->only([
                'nama_guru', 'nip', 'jabatan', 'mata_pelajaran', 'kelas_wali',
                'nama_madrasah', 'npsn', 'alamat', 'kecamatan', 'kabupaten',
                'provinsi', 'tahun_ajaran', 'semester', 'nama_kepala_madrasah',
                'motto',
            ]),
            'stats' => [
                'students' => $students->count(),
                'male' => $students->where('jenis_kelamin', 'L')->count(),
                'female' => $students->where('jenis_kelamin', 'P')->count(),
                'classes' => $classes->count(),
                'documents' => $documents->count(),
                'documents_required' => count(LegalDocument::TYPES),
                'profile_completeness' => $profile?->completeness() ?? 0,
            ],
            'classDistribution' => $classDistribution,
            'recentStudents' => $recentStudents,
            'documentTypes' => collect(LegalDocument::TYPES)
                ->map(fn (string $label, string $type) => [
                    'type' => $type,
                    'label' => $label,
                    'uploaded' => $documents->contains('type', $type),
                ])
                ->values(),
        ]);
    }
}
