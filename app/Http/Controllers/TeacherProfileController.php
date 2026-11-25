<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $profile = $request->user()->teacherProfile;

        return Inertia::render('Identitas/Edit', [
            'profile' => $profile,
            'defaults' => [
                'nama_guru' => $request->user()->name,
                'tahun_ajaran' => '2025/2026',
                'semester' => 'Ganjil',
                'nama_madrasah' => 'MI ',
            ],
            'completeness' => $profile?->completeness() ?? 0,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_guru' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'string', 'max:30'],
            'nuptk' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'mata_pelajaran' => ['nullable', 'string', 'max:100'],
            'kelas_wali' => ['nullable', 'string', 'max:50'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'motto' => ['nullable', 'string', 'max:255'],
            'nama_madrasah' => ['required', 'string', 'max:200'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'desa' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kabupaten' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'email_madrasah' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'string', 'max:150'],
            'nama_kepala_madrasah' => ['nullable', 'string', 'max:150'],
            'nip_kepala_madrasah' => ['nullable', 'string', 'max:30'],
        ]);

        $request->user()->teacherProfile()->updateOrCreate([], $data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Identitas guru dan madrasah berhasil disimpan.',
        ]);

        return back();
    }
}
