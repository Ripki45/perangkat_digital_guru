<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Mapel;
use App\Models\Rombel;
use App\Models\User;
use App\Services\EmisPayloadMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RombelController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        EmisPayloadMapper::ensureDefaultRombels($user);
        EmisPayloadMapper::ensureDefaultMapels($user);

        $rombels = $user->rombels()->orderBy('tingkat')->orderBy('kode')->get();
        $mapels = $user->mapels()->orderBy('kelompok')->orderBy('nama')->get();
        $students = $user->students()->orderBy('kelas')->orderBy('nama')->get();
        $gurus = $user->gurus()->where('status_aktif', 'Aktif')->orderBy('nama')->get();

        return Inertia::render('Siswa/Rombel', [
            'rombels' => $rombels->map(fn (Rombel $rombel): array => [
                'id' => $rombel->id,
                'tingkat' => $rombel->tingkat,
                'kode' => $rombel->kode,
                'wali' => $rombel->wali ?? '',
            ])->values(),
            'mapels' => $mapels->map(fn (Mapel $mapel): array => [
                'id' => $mapel->id,
                'kode' => $mapel->kode,
                'nama' => $mapel->nama,
                'kelompok' => $mapel->kelompok,
                'kkm' => $mapel->kkm,
                'jtm' => $mapel->jtm,
                'tingkat' => $mapel->tingkat,
                'aktif' => $mapel->aktif,
            ])->values(),
            'students' => $students->map(fn ($student): array => $student->toEmisPayload())->values(),
            'gurus' => $gurus->map(fn ($guru): array => $guru->toEmisPayload())->values(),
            'canManageRombel' => in_array($user->role, [UserRole::Superadmin, UserRole::WaliKelas], true),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tingkat' => ['required', 'in:1,2,3,4,5,6'],
            'nama' => ['required', 'string', 'max:30'],
            'wali' => ['nullable', 'string', 'max:150'],
        ]);

        $tingkat = $validated['tingkat'];
        $raw = mb_strtoupper(trim($validated['nama']), 'UTF-8');
        $kode = str_starts_with($raw, "{$tingkat}-") || $raw === $tingkat
            ? $raw
            : "{$tingkat}-{$raw}";

        /** @var User $user */
        $user = $request->user();
        $user->rombels()->updateOrCreate(
            ['kode' => $kode],
            ['tingkat' => $tingkat, 'wali' => trim((string) ($validated['wali'] ?? '')) ?: null],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Rombel {$kode} berhasil disimpan.",
        ]);

        return back();
    }

    public function destroy(Request $request, Rombel $rombel): RedirectResponse
    {
        abort_unless($rombel->user_id === $request->user()->id, 403);

        /** @var User $user */
        $user = $request->user();
        $count = $user->students()->where('kelas', $rombel->kode)->count();

        if ($count > 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Rombel {$rombel->kode} masih memiliki {$count} siswa! Pindahkan siswa terlebih dahulu.",
            ]);

            return back();
        }

        $kode = $rombel->kode;
        $rombel->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Rombel {$kode} berhasil dihapus.",
        ]);

        return back();
    }

    public function batch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
            'target' => ['required', 'string', 'max:50'],
            'new_tingkat' => ['nullable', 'in:1,2,3,4,5,6'],
            'tgl_lulus' => ['nullable', 'date'],
            'ket_lulus' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $students = $user->students()->whereIn('id', $validated['student_ids'])->get();
        $rombelCodes = $user->rombels()->pluck('kode')->all();
        $target = $validated['target'];

        DB::transaction(function () use ($students, $target, $validated, $rombelCodes): void {
            foreach ($students as $student) {
                $emis = is_array($student->emis_data) ? $student->emis_data : [];

                if ($target === 'LULUS_BATCH') {
                    $tgl = $validated['tgl_lulus'] ?: now()->format('Y-m-d');
                    $ket = $validated['ket_lulus'] ?: 'Lulus Ujian Madrasah Ibtidaiyah';
                    $emis['keluarDariMadrasah'] = 'Lulus';
                    $emis['tglKeluar'] = $tgl;
                    $emis['keteranganKeluar'] = $ket;
                    $student->update([
                        'status' => 'Lulus',
                        'catatan' => $ket,
                        'emis_data' => $emis,
                    ]);
                } elseif ($target === 'UPDATE_TINGKAT_BATCH') {
                    $newTingkat = $validated['new_tingkat'] ?: '2';
                    $suffix = str_contains($student->kelas, '-')
                        ? explode('-', $student->kelas, 2)[1]
                        : 'A';
                    $candidate = "{$newTingkat}-{$suffix}";
                    $student->update([
                        'tingkat' => $newTingkat,
                        'kelas' => in_array($candidate, $rombelCodes, true) ? $candidate : $student->kelas,
                    ]);
                } else {
                    $tingkat = str_contains($target, '-') ? explode('-', $target, 2)[0] : $target;
                    $student->update([
                        'kelas' => $target,
                        'tingkat' => in_array($tingkat, ['1', '2', '3', '4', '5', '6'], true) ? $tingkat : $student->tingkat,
                    ]);
                }
            }
        });

        $count = $students->count();
        $message = match ($target) {
            'LULUS_BATCH' => "{$count} siswa MI berhasil diproses LULUS.",
            'UPDATE_TINGKAT_BATCH' => 'Tingkat kelas '.$count.' siswa diperbarui ke Kelas '.($validated['new_tingkat'] ?? '2').' MI.',
            default => "{$count} siswa berhasil dipindahkan ke Kelas {$target}.",
        };

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message,
        ]);

        return back();
    }
}
