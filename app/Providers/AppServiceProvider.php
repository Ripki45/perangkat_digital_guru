<?php

namespace App\Providers;

use App\Jobs\GenerateEmisNarrativeBatchJob;
use App\Jobs\IndexStudentSemanticEmbeddingJob;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureJsonApiAndQueues();
    }

    protected function configureJsonApiAndQueues(): void
    {
        JsonApiResource::configure(
            version: '1.1',
            meta: ['standard' => 'EMIS 4.0 Kemenag RI'],
        );

        Queue::route(IndexStudentSemanticEmbeddingJob::class, queue: 'ai-embeddings');
        Queue::route(GenerateEmisNarrativeBatchJob::class, queue: 'emis-reports');
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
