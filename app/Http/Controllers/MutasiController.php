<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmisPayloadMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MutasiController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $students = $user->students()->orderBy('kelas')->orderBy('nama')->get();

        return Inertia::render('Siswa/Mutasi', [
            'students' => $students->map(fn ($student): array => $student->toEmisPayload())->values(),
            'madrasahProfile' => EmisPayloadMapper::madrasahProfilePayload($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'alasan' => ['required', 'string', 'max:100'],
            'tgl_keluar' => ['nullable', 'date'],
            'no_surat' => ['nullable', 'string', 'max:120'],
            'sekolah_tujuan' => ['nullable', 'string', 'max:200'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $student = $user->students()->findOrFail((int) $validated['student_id']);

        $alasan = $validated['alasan'];
        $status = match ($alasan) {
            'Lulus' => 'Lulus',
            'Putus Sekolah' => 'Putus Sekolah',
            default => 'Mutasi Keluar',
        };

        $emis = $student->emis_data ?? [];
        $emis['keluarDariMadrasah'] = $alasan;
        $emis['tglKeluar'] = $validated['tgl_keluar'] ?: now()->format('Y-m-d');
        $emis['noSeriIjazahMadrasah'] = trim((string) ($validated['no_surat'] ?? ''));
        $emis['sekolahTujuan'] = trim((string) ($validated['sekolah_tujuan'] ?? ''));
        $emis['keteranganKeluar'] = trim((string) ($validated['keterangan'] ?? ''));

        $student->update([
            'status' => $status,
            'catatan' => $emis['keteranganKeluar'] ?: null,
            'emis_data' => $emis,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Mutasi keluar untuk {$student->nama} berhasil dicatat.",
        ]);

        return back();
    }
}
