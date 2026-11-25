<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ModulePlaceholderController extends Controller
{
    public function __invoke(string $module): Response
    {
        return Inertia::render('Modules/Placeholder', [
            'slug' => $module,
        ]);
    }
}
