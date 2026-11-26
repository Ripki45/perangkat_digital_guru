<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsNativeVector = in_array(DB::getDriverName(), ['pgsql', 'mariadb'], true);

        Schema::create('emis_semantic_documents', function (Blueprint $table) use ($supportsNativeVector): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 40); // student, guru, panduan
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->json('metadata')->nullable();

            if ($supportsNativeVector) {
                $table->vector('embedding', dimensions: 1536)->nullable();
                $table->vectorIndex('embedding');
            } else {
                $table->json('embedding')->nullable();
            }

            $table->string('embedding_model', 100)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emis_semantic_documents');
    }
};
