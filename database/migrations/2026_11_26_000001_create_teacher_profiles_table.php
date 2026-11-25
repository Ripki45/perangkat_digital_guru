<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Identitas guru
            $table->string('nama_guru');
            $table->string('nip', 30)->nullable();
            $table->string('nuptk', 30)->nullable();
            $table->string('jabatan')->nullable();
            $table->string('mata_pelajaran')->nullable();
            $table->string('kelas_wali')->nullable();
            $table->string('tahun_ajaran', 20)->default('2025/2026');
            $table->string('semester', 20)->default('Ganjil');
            $table->string('motto')->nullable();

            // Identitas madrasah
            $table->string('nama_madrasah');
            $table->string('npsn', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('email_madrasah')->nullable();
            $table->string('website')->nullable();

            // Kepala madrasah
            $table->string('nama_kepala_madrasah')->nullable();
            $table->string('nip_kepala_madrasah', 30)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
