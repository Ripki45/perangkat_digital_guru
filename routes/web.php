<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\ModulePlaceholderController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherProfileController;
use App\Http\Controllers\Teams\TeamInvitationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('beranda');
    }

    return Inertia::render('Welcome', [
        'demoLogin' => app()->environment('local'),
    ]);
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('beranda', BerandaController::class)->name('beranda');

    Route::get('dashboard', fn () => redirect()->route('beranda'))->name('dashboard');
    Route::get('{current_team}/dashboard', fn () => redirect()->route('beranda'))
        ->where('current_team', '[A-Za-z0-9-]+')
        ->name('team.dashboard');

    // Identitas guru & madrasah
    Route::get('identitas', [TeacherProfileController::class, 'edit'])->name('identitas.edit');
    Route::put('identitas', [TeacherProfileController::class, 'update'])->name('identitas.update');

    // Legalitas: logo, tanda tangan & stempel
    Route::get('legalitas', [LegalDocumentController::class, 'index'])->name('legalitas.index');
    Route::post('legalitas', [LegalDocumentController::class, 'store'])->name('legalitas.store');
    Route::get('legalitas/{legalDocument}/preview', [LegalDocumentController::class, 'preview'])->name('legalitas.preview');
    Route::delete('legalitas/{legalDocument}', [LegalDocumentController::class, 'destroy'])->name('legalitas.destroy');

    // Master data siswa
    Route::get('siswa', [StudentController::class, 'index'])->name('siswa.index');
    Route::post('siswa', [StudentController::class, 'store'])->name('siswa.store');
    Route::post('siswa/impor-massal', [StudentController::class, 'bulkStore'])->name('siswa.bulk');
    Route::put('siswa/{student}', [StudentController::class, 'update'])->name('siswa.update');
    Route::delete('siswa/{student}', [StudentController::class, 'destroy'])->name('siswa.destroy');

    // Modul tahap berikutnya
    Route::get('modul/{module}', ModulePlaceholderController::class)
        ->where('module', '[a-z0-9-]+')
        ->name('module.placeholder');

    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

if (app()->environment('local')) {
    Route::post('demo-login', DemoLoginController::class)->name('demo.login');
}

require __DIR__.'/settings.php';
