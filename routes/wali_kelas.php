<?php

use App\Http\Controllers\MutasiController;
use App\Http\Controllers\RekapEmisController;
use App\Http\Controllers\RombelController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:superadmin,wali_kelas'])->group(function () {
    // Master data siswa (EMIS 4.0)
    Route::get('siswa', [StudentController::class, 'index'])->name('siswa.index');
    Route::post('siswa', [StudentController::class, 'store'])->name('siswa.store');
    Route::post('siswa/impor-massal', [StudentController::class, 'bulkStore'])->name('siswa.bulk');
    Route::post('siswa/impor-emis', [StudentController::class, 'importEmis'])->name('siswa.import-emis');
    Route::post('siswa/sampel-emis', [StudentController::class, 'loadSample'])->name('siswa.sample');
    Route::put('siswa/{student}', [StudentController::class, 'update'])->name('siswa.update');
    Route::delete('siswa/{student}', [StudentController::class, 'destroy'])->name('siswa.destroy');

    // Rombel & Kenaikan Kelas MI
    Route::get('rombel', [RombelController::class, 'index'])->name('rombel.index');
    Route::post('rombel', [RombelController::class, 'store'])->name('rombel.store');
    Route::delete('rombel/{rombel}', [RombelController::class, 'destroy'])->name('rombel.destroy');
    Route::post('rombel/batch', [RombelController::class, 'batch'])->name('rombel.batch');

    // Mutasi Masuk/Keluar & Surat Pindah
    Route::get('mutasi', [MutasiController::class, 'index'])->name('mutasi.index');
    Route::post('mutasi', [MutasiController::class, 'store'])->name('mutasi.store');

    // Rekapitulasi Statistik, Usia MI, Cetak Massal & Cadangan JSON
    Route::get('rekap-emis', [RekapEmisController::class, 'index'])->name('rekap-emis.index');
    Route::post('rekap-emis/restore', [RekapEmisController::class, 'restoreBackup'])->name('rekap-emis.restore');
    Route::delete('rekap-emis/clear', [RekapEmisController::class, 'clearAll'])->name('rekap-emis.clear');
});
