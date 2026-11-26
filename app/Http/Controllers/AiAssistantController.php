<?php

namespace App\Http\Controllers;

use App\Ai\Agents\EmisAssistantAgent;
use App\Jobs\IndexStudentSemanticEmbeddingJob;
use App\Models\EmisSemanticDocument;
use App\Models\User;
use App\Services\EmisSemanticSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Ai;
use Throwable;

class AiAssistantController extends Controller
{
    public function index(Request $request, EmisSemanticSearchService $searchService): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Ai/Asisten', [
            'indexedCount' => EmisSemanticDocument::query()->where('user_id', $user->id)->count(),
            'dbDriver' => DB::getDriverName(),
            'supportsNativeVector' => $searchService->supportsNativeVectorDatabase(),
            'aiConfigured' => $this->hasConfiguredTextProvider(),
            'embeddingConfigured' => $searchService->hasConfiguredEmbeddingProvider(),
            'jsonApiEndpoints' => [
                ['label' => 'Daftar Siswa (JSON:API v1.1)', 'url' => '/api/v1/emis/students'],
                ['label' => 'Siswa Aktif + Include RDM', 'url' => '/api/v1/emis/students?aktif=1&include=rdmScores'],
                ['label' => 'Daftar Rombel MI (JSON:API)', 'url' => '/api/v1/emis/rombels'],
                ['label' => 'Daftar GTK EMIS (Superadmin)', 'url' => '/api/v1/emis/gurus'],
            ],
        ]);
    }

    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:1500'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! $this->hasConfiguredTextProvider() && ! Ai::hasFakeGatewayFor(EmisAssistantAgent::class)) {
            return response()->json([
                'ok' => false,
                'configured' => false,
                'answer' => 'Provider Laravel AI SDK belum memiliki API key di .env (misalnya OPENAI_API_KEY, GEMINI_API_KEY, atau ANTHROPIC_API_KEY). Tambahkan API key terlebih dahulu agar EmisAssistantAgent dapat menjawab langsung.',
            ]);
        }

        try {
            $response = (new EmisAssistantAgent($user))->prompt((string) $data['prompt']);

            return response()->json([
                'ok' => true,
                'configured' => true,
                'answer' => (string) $response->text,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'configured' => $this->hasConfiguredTextProvider(),
                'answer' => 'Gagal memanggil provider AI: '.$e->getMessage(),
            ], 422);
        }
    }

    public function semanticSearch(Request $request, EmisSemanticSearchService $searchService): JsonResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:300'],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json(
            $searchService->search($user, (string) $data['query']),
        );
    }

    public function syncVectors(Request $request, EmisSemanticSearchService $searchService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->boolean('queued')) {
            IndexStudentSemanticEmbeddingJob::dispatch($user);

            return response()->json([
                'queued' => true,
                'queue' => 'ai-embeddings',
                'message' => 'Job sinkronisasi vektor dikirim ke antrean "ai-embeddings" melalui Queue::route.',
            ]);
        }

        $result = $searchService->indexUserRecords($user);

        return response()->json([
            'queued' => false,
            'indexed' => $result['indexed'],
            'mode' => $result['mode'],
            'message' => "Berhasil mengindeks {$result['indexed']} dokumen ({$result['mode']}).",
        ]);
    }

    private function hasConfiguredTextProvider(): bool
    {
        if (Ai::hasFakeGatewayFor(EmisAssistantAgent::class)) {
            return true;
        }

        $provider = (string) config('ai.default', 'openai');
        $key = config("ai.providers.{$provider}.key");

        return is_string($key) && trim($key) !== '';
    }
}
