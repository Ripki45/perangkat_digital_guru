<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DataMadrasahController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $user->teacherProfile;
        /** @var LegalDocument|null $logoDoc */
        $logoDoc = $user->legalDocuments()->where('type', 'logo')->first();

        return Inertia::render('Biodata/DataMadrasah', [
            'profile' => $profile,
            'defaults' => [
                'nama_madrasah' => 'MADRASAH IBTIDAIYAH NEGERI 1 CIAMIS',
                'tahun_ajaran' => '2026/2027',
                'semester' => 'Ganjil',
                'nsm' => '111132070001',
                'npsn' => '60708447',
                'kemenag_kab' => 'KANTOR KEMENTERIAN AGAMA KABUPATEN CIAMIS',
                'tempat_surat' => 'Ciamis',
            ],
            'fallbackLogoKiri' => $logoDoc ? route('legalitas.preview', $logoDoc) : '',
            'canManageLegalitas' => $user->isSuperadmin(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $existing = $user->teacherProfile;

        $data = $request->validate([
            'nama_madrasah' => ['required', 'string', 'max:200'],
            'nsm' => ['nullable', 'string', 'max:30'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'kemenag_kab' => ['nullable', 'string', 'max:200'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'tempat_surat' => ['nullable', 'string', 'max:100'],
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
            'operator_nama' => ['nullable', 'string', 'max:150'],
            'operator_hp' => ['nullable', 'string', 'max:30'],
            'logo_kiri' => ['nullable', 'string'],
            'logo_kanan' => ['nullable', 'string'],
        ]);

        $user->teacherProfile()->updateOrCreate([], [
            'nama_guru' => $existing?->nama_guru ?: $user->name,
            ...$data,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Data Madrasah & Kop Surat Resmi berhasil disimpan.',
        ]);

        return back();
    }
}
