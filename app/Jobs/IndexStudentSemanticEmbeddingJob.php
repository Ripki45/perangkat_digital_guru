<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\EmisSemanticSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

#[Tries(3)]
#[Timeout(120)]
#[Backoff([10, 30, 60])]
#[DeleteWhenMissingModels]
class IndexStudentSemanticEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $user) {}

    public function handle(EmisSemanticSearchService $searchService): void
    {
        $searchService->indexUserRecords($this->user);
    }
}
