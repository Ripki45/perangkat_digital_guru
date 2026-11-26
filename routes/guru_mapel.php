<?php

use App\Http\Controllers\MapelController;
use App\Http\Controllers\RdmController;
use App\Http\Controllers\RombelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:superadmin,wali_kelas,guru_mapel'])->group(function () {
    // Data Mapel & Rombel (menu BIODATA)
    Route::get('mapel-rombel', [RombelController::class, 'index'])->name('mapel-rombel.index');
    Route::post('mapel', [MapelController::class, 'store'])->name('mapel.store');
    Route::put('mapel/{mapel}', [MapelController::class, 'update'])->name('mapel.update');
    Route::delete('mapel/{mapel}', [MapelController::class, 'destroy'])->name('mapel.destroy');

    // Rapor Digital Madrasah (RDM) 13 Mapel MI x 12 Semester
    Route::get('rdm', [RdmController::class, 'index'])->name('rdm.index');
    Route::put('rdm/{student}', [RdmController::class, 'save'])->name('rdm.save');
    Route::post('rdm/impor', [RdmController::class, 'import'])->name('rdm.import');
});
