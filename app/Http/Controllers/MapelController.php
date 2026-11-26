<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MapelController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'kode' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Mapel::class, 'kode')->where('user_id', $user->id),
            ],
            'nama' => ['required', 'string', 'max:120'],
            'kelompok' => ['required', 'string', 'max:60'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
            'jtm' => ['required', 'integer', 'min:1', 'max:40'],
            'tingkat' => ['required', 'string', 'max:30'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $user->mapels()->create([
            'kode' => mb_strtoupper(trim($validated['kode']), 'UTF-8'),
            'nama' => trim($validated['nama']),
            'kelompok' => trim($validated['kelompok']),
            'kkm' => (int) $validated['kkm'],
            'jtm' => (int) $validated['jtm'],
            'tingkat' => trim($validated['tingkat']),
            'aktif' => (bool) ($validated['aktif'] ?? true),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Mata pelajaran {$validated['nama']} berhasil ditambahkan.",
        ]);

        return back();
    }

    public function update(Request $request, Mapel $mapel): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($mapel->user_id === $user->id, 403);

        $validated = $request->validate([
            'kode' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Mapel::class, 'kode')
                    ->where('user_id', $user->id)
                    ->ignore($mapel->id),
            ],
            'nama' => ['required', 'string', 'max:120'],
            'kelompok' => ['required', 'string', 'max:60'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
            'jtm' => ['required', 'integer', 'min:1', 'max:40'],
            'tingkat' => ['required', 'string', 'max:30'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $mapel->update([
            'kode' => mb_strtoupper(trim($validated['kode']), 'UTF-8'),
            'nama' => trim($validated['nama']),
            'kelompok' => trim($validated['kelompok']),
            'kkm' => (int) $validated['kkm'],
            'jtm' => (int) $validated['jtm'],
            'tingkat' => trim($validated['tingkat']),
            'aktif' => (bool) ($validated['aktif'] ?? true),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Mata pelajaran {$mapel->nama} berhasil diperbarui.",
        ]);

        return back();
    }

    public function destroy(Request $request, Mapel $mapel): RedirectResponse
    {
        abort_unless($mapel->user_id === $request->user()->id, 403);

        $nama = $mapel->nama;
        $mapel->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Mata pelajaran {$nama} dihapus.",
        ]);

        return back();
    }
}
