<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);

        $user = User::query()
            ->where('email', 'guru@madrasah.test')
            ->firstOrFail();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('beranda');
    }
}
