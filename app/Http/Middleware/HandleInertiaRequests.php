<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->toArray(),
                    'role' => $request->user()->role?->value,
                    'role_label' => $request->user()->role?->label(),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => fn () => $user?->currentTeam ? $user->toUserTeam($user->currentTeam) : null,
            'teams' => fn () => $user?->toUserTeams(includeCurrent: true) ?? [],
            'teacherProfile' => fn () => $user?->teacherProfile?->only([
                'nama_guru', 'nip', 'nuptk', 'jabatan', 'mata_pelajaran', 'kelas_wali',
                'nama_madrasah', 'npsn', 'nsm', 'kemenag_kab', 'tempat_surat',
                'alamat', 'desa', 'kecamatan', 'kabupaten',
                'provinsi', 'kode_pos', 'telepon', 'email_madrasah', 'website',
                'nama_kepala_madrasah', 'nip_kepala_madrasah',
                'operator_nama', 'operator_hp',
                'tahun_ajaran', 'semester', 'motto',
            ]),
            'demoLogin' => app()->environment('local'),
        ];
    }
}
