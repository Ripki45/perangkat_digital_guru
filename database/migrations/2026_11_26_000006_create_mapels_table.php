<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kode', 20);
            $table->string('nama', 120);
            $table->string('kelompok', 60)->default('Umum');
            $table->unsignedTinyInteger('kkm')->default(75);
            $table->unsignedTinyInteger('jtm')->default(2);
            $table->string('tingkat', 30)->default('1-6');
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapels');
    }
};
