<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('nik', 30)->nullable()->after('nis');
            $table->string('tingkat', 10)->nullable()->after('kelas');
            $table->string('rombel_masuk', 20)->nullable()->after('tingkat');
            $table->longText('foto')->nullable()->after('catatan');
            $table->json('emis_data')->nullable()->after('foto');

            $table->index(['user_id', 'nisn']);
            $table->index(['user_id', 'nik']);
        });

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->string('nsm', 30)->nullable()->after('npsn');
            $table->string('kemenag_kab', 200)->nullable()->after('nsm');
            $table->string('tempat_surat', 100)->nullable()->after('kemenag_kab');
            $table->string('operator_nama', 150)->nullable()->after('nip_kepala_madrasah');
            $table->string('operator_hp', 30)->nullable()->after('operator_nama');
            $table->longText('logo_kiri')->nullable()->after('operator_hp');
            $table->longText('logo_kanan')->nullable()->after('logo_kiri');
        });

        Schema::create('rombels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tingkat', 10)->default('1');
            $table->string('kode', 30);
            $table->string('wali', 150)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'kode']);
        });

        Schema::create('gurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama', 150);
            $table->string('gelar_depan', 50)->nullable();
            $table->string('gelar_belakang', 80)->nullable();
            $table->string('nik', 30)->nullable();
            $table->string('no_kk', 30)->nullable();
            $table->string('jk', 1)->default('L');
            $table->string('tempat_lahir', 150)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('hp', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('pegid', 40)->nullable();
            $table->string('npk', 40)->nullable();
            $table->string('nuptk', 40)->nullable();
            $table->string('nip', 40)->nullable();
            $table->string('status_pegawai', 60)->default('PNS');
            $table->string('jenis_ptk', 80)->default('Guru Kelas MI');
            $table->string('golongan', 30)->nullable();
            $table->string('status_aktif', 40)->default('Aktif');
            $table->string('pendidikan', 50)->default('D4/S1');
            $table->string('status_sertifikasi', 50)->default('Belum Sertifikasi');
            $table->string('satminkal', 50)->default('Satminkal (Induk)');
            $table->string('mapel_utama', 150)->nullable();
            $table->unsignedSmallInteger('jtm')->default(0);
            $table->string('tugas_tambahan', 100)->nullable();
            $table->json('emis_data')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'nik']);
            $table->index(['user_id', 'npk']);
        });

        Schema::create('rdm_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('mapel', 120);
            $table->unsignedTinyInteger('semester');
            $table->decimal('nilai', 5, 2);
            $table->timestamps();

            $table->unique(['student_id', 'mapel', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rdm_scores');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('rombels');

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'nsm',
                'kemenag_kab',
                'tempat_surat',
                'operator_nama',
                'operator_hp',
                'logo_kiri',
                'logo_kanan',
            ]);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'nisn']);
            $table->dropIndex(['user_id', 'nik']);
            $table->dropColumn([
                'nik',
                'tingkat',
                'rombel_masuk',
                'foto',
                'emis_data',
            ]);
        });
    }
};
