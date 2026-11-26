<?php

use App\Http\Controllers\RdmController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:superadmin,wali_kelas,guru_mapel'])->group(function () {
    // Rapor Digital Madrasah (RDM) 13 Mapel MI x 12 Semester
    Route::get('rdm', [RdmController::class, 'index'])->name('rdm.index');
    Route::put('rdm/{student}', [RdmController::class, 'save'])->name('rdm.save');
    Route::post('rdm/impor', [RdmController::class, 'import'])->name('rdm.import');
});
