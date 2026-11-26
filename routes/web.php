<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\Api\V1\EmisJsonApiController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CekNisnController;
use App\Http\Controllers\DataMadrasahController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\ModulePlaceholderController;
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

    // Identitas guru & profil lembaga madrasah (BIODATA)
    Route::get('data-madrasah', [DataMadrasahController::class, 'edit'])->name('data-madrasah.edit');
    Route::put('data-madrasah', [DataMadrasahController::class, 'update'])->name('data-madrasah.update');

    Route::get('identitas', [TeacherProfileController::class, 'edit'])->name('identitas.edit');
    Route::put('identitas', [TeacherProfileController::class, 'update'])->name('identitas.update');
    Route::post('identitas/reset-default', [TeacherProfileController::class, 'resetDefault'])->name('identitas.reset-default');
    Route::put('identitas/akun', [TeacherProfileController::class, 'updateAccount'])->name('identitas.account');

    // Preview aset legalitas agar bisa tampil di dokumen cetak semua guru
    Route::get('legalitas/{legalDocument}/preview', [LegalDocumentController::class, 'preview'])->name('legalitas.preview');

    // Portal Cek Data Siswa via NISN (khusus setelah login)
    Route::get('cek-nisn', [CekNisnController::class, 'index'])->name('cek-nisn.index');
    Route::get('cek-nisn/cari', [CekNisnController::class, 'lookup'])->name('cek-nisn.lookup');

    // Asisten AI SDK & Pencarian Semantik Vektor EMIS 4.0
    Route::get('asisten-ai', [AiAssistantController::class, 'index'])->name('asisten-ai.index');
    Route::post('asisten-ai/tanya', [AiAssistantController::class, 'ask'])->name('asisten-ai.ask');
    Route::get('asisten-ai/pencarian', [AiAssistantController::class, 'semanticSearch'])->name('asisten-ai.search');
    Route::post('asisten-ai/sinkron', [AiAssistantController::class, 'syncVectors'])->name('asisten-ai.sync');

    // Endpoint JSON:API v1.1 Resmi (application/vnd.api+json)
    Route::prefix('api/v1/emis')->name('api.v1.emis.')->group(function () {
        Route::get('students', [EmisJsonApiController::class, 'students'])->name('students.index');
        Route::get('students/{student}', [EmisJsonApiController::class, 'showStudent'])->name('students.show');
        Route::get('gurus', [EmisJsonApiController::class, 'gurus'])->name('gurus.index');
        Route::get('rombels', [EmisJsonApiController::class, 'rombels'])->name('rombels.index');
    });

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

require __DIR__.'/superadmin.php';
require __DIR__.'/wali_kelas.php';
require __DIR__.'/guru_mapel.php';
require __DIR__.'/settings.php';
