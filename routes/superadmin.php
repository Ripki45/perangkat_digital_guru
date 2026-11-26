<?php

use App\Http\Controllers\GuruController;
use App\Http\Controllers\LegalDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:superadmin'])->group(function () {
    // Legalitas madrasah (logo, TTD kepala madrasah & stempel)
    Route::get('legalitas', [LegalDocumentController::class, 'index'])->name('legalitas.index');
    Route::post('legalitas', [LegalDocumentController::class, 'store'])->name('legalitas.store');
    Route::delete('legalitas/{legalDocument}', [LegalDocumentController::class, 'destroy'])->name('legalitas.destroy');

    // Data Guru & Tenaga Kependidikan (GTK EMIS 4.0)
    Route::get('gtk', [GuruController::class, 'index'])->name('gtk.index');
    Route::post('gtk', [GuruController::class, 'store'])->name('gtk.store');
    Route::post('gtk/impor', [GuruController::class, 'import'])->name('gtk.import');
    Route::post('gtk/sampel', [GuruController::class, 'loadSample'])->name('gtk.sample');
    Route::put('gtk/{guru}', [GuruController::class, 'update'])->name('gtk.update');
    Route::delete('gtk/{guru}', [GuruController::class, 'destroy'])->name('gtk.destroy');
});
