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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('nama');
            $table->string('nisn', 30)->nullable();
            $table->string('nis', 30)->nullable();
            $table->string('jenis_kelamin', 1)->default('L');
            $table->string('kelas', 20)->default('1');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nama_wali')->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->string('status', 20)->default('Aktif');
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'kelas']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
