<?php

namespace App\Console\Commands;

use App\Jobs\IndexStudentSemanticEmbeddingJob;
use App\Models\User;
use App\Services\EmisSemanticSearchService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'emis:sync-vectors',
    description: 'Sinkronisasi indeks vektor semantik siswa & GTK untuk pencarian AI EMIS 4.0',
)]
class EmisSyncSemanticIndexCommand extends Command
{
    protected $signature = 'emis:sync-vectors {--user= : ID user tertentu} {--queue : Jalankan melalui Queue::route}';

    public function handle(EmisSemanticSearchService $searchService): int
    {
        $userId = $this->option('user');
        $useQueue = (bool) $this->option('queue');

        $users = User::query()
            ->when($userId !== null && $userId !== '', fn ($q) => $q->where('id', (int) $userId))
            ->get();

        foreach ($users as $user) {
            if ($useQueue) {
                IndexStudentSemanticEmbeddingJob::dispatch($user);
                $this->line("Dispatched semantic index job for {$user->email} -> queue [ai-embeddings]");
            } else {
                $result = $searchService->indexUserRecords($user);
                $this->info("Indexed {$result['indexed']} records ({$result['mode']}) for {$user->email}");
            }
        }

        return self::SUCCESS;
    }
}
