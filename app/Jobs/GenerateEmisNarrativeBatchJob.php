<?php

namespace App\Jobs;

use App\Ai\Agents\EmisAssistantAgent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

#[Tries(2)]
#[Timeout(180)]
#[Backoff([15, 60])]
#[DeleteWhenMissingModels]
class GenerateEmisNarrativeBatchJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $user,
        public readonly Student $student,
    ) {}

    public function handle(): void
    {
        if ($this->student->user_id !== $this->user->id) {
            return;
        }

        $payload = $this->student->toEmisPayload();
        $prompt = "Buatkan catatan perkembangan singkat Panca Cinta untuk siswa {$payload['nama']} di kelas {$payload['rombel']} (hobi: {$payload['hobi']}).";

        $response = (new EmisAssistantAgent($this->user))->prompt($prompt);

        $this->student->update([
            'catatan' => mb_substr((string) $response->text, 0, 500),
        ]);
    }
}
