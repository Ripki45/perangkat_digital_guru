<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use App\Models\User;
use App\Services\EmisPayloadMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeacherProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $user->teacherProfile;
        /** @var LegalDocument|null $logoDoc */
        $logoDoc = $user->legalDocuments()->where('type', 'logo')->first();

        return Inertia::render('Identitas/Edit', [
            'profile' => $profile,
            'defaults' => [
                'nama_guru' => $user->name,
                'tahun_ajaran' => '2025/2026',
                'semester' => 'Ganjil',
                'nama_madrasah' => 'MI ',
            ],
            'completeness' => $profile?->completeness() ?? 0,
            'fallbackLogoKiri' => $logoDoc ? route('legalitas.preview', $logoDoc) : '',
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
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
            'nsm' => ['nullable', 'string', 'max:30'],
            'kemenag_kab' => ['nullable', 'string', 'max:200'],
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

        $request->user()->teacherProfile()->updateOrCreate([], $data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Identitas guru dan madrasah berhasil disimpan.',
        ]);

        return back();
    }

    public function resetDefault(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $existing = $user->teacherProfile;
        $defaults = EmisPayloadMapper::defaultMadrasahProfile();

        [$tahunAjaran, $semester] = array_pad(explode(' - ', $defaults['tahunPelajaran']), 2, 'Ganjil');

        $user->teacherProfile()->updateOrCreate([], [
            'nama_guru' => $existing?->nama_guru ?: $user->name,
            'tahun_ajaran' => trim($tahunAjaran) ?: '2026/2027',
            'semester' => in_array(trim($semester), ['Ganjil', 'Genap'], true) ? trim($semester) : 'Ganjil',
            'nama_madrasah' => $defaults['namaMadrasah'],
            'nsm' => $defaults['nsm'],
            'npsn' => $defaults['npsn'],
            'kemenag_kab' => $defaults['kemenagKab'],
            'alamat' => $defaults['alamat'],
            'tempat_surat' => $defaults['tempatSurat'],
            'website' => $defaults['website'],
            'email_madrasah' => $defaults['email'],
            'nama_kepala_madrasah' => $defaults['kepalaNama'],
            'nip_kepala_madrasah' => $defaults['kepalaNip'],
            'operator_nama' => $defaults['operatorNama'],
            'operator_hp' => $defaults['operatorHp'],
            'logo_kiri' => null,
            'logo_kanan' => null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Profil Madrasah dikembalikan ke pengaturan default MIN 1 Ciamis.',
        ]);

        return back();
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Informasi akun login berhasil diperbarui.',
        ]);

        return back();
    }
}
