<?php

namespace App\Http\Responses\Concerns;

use Illuminate\Http\Request;

trait RedirectsToCurrentTeam
{
    /**
     * Tujuan setelah autentikasi: beranda aplikasi guru.
     */
    protected function redirectPathForCurrentTeam(Request $request, string $redirect): string
    {
        return '/beranda';
    }
}
