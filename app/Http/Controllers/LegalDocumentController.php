<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegalDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $documents = $user->legalDocuments()->get()->keyBy('type');

        $types = collect(LegalDocument::types())
            ->map(fn (string $label, string $type) => [
                'type' => $type,
                'label' => $label,
                'document' => $documents->has($type) ? $this->toPayload($documents->get($type)) : null,
            ])
            ->values();

        return Inertia::render('Legalitas/Index', [
            'documentTypes' => $types,
            'uploadedCount' => $documents->count(),
            'requiredCount' => count(LegalDocument::types()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(LegalDocument::types()))],
            'file' => ['required', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp,svg'],
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $existing = $user->legalDocuments()->where('type', $data['type'])->first();

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'png');
        $path = $file->storeAs(
            "legal/{$user->id}",
            Str::slug($data['type']).'-'.Str::random(12).'.'.$extension,
            'public',
        );

        $user->legalDocuments()->updateOrCreate(
            ['type' => $data['type']],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize() ?: 0,
            ],
        );

        if ($existing && $existing->file_path !== $path) {
            Storage::disk('public')->delete($existing->file_path);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => LegalDocument::labelFor($data['type']).' berhasil diunggah.',
        ]);

        return back();
    }

    public function destroy(Request $request, LegalDocument $legalDocument): RedirectResponse
    {
        $this->ensureOwner($request, $legalDocument);

        Storage::disk('public')->delete($legalDocument->file_path);
        $label = LegalDocument::labelFor($legalDocument->type);
        $legalDocument->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$label} berhasil dihapus.",
        ]);

        return back();
    }

    public function preview(Request $request, LegalDocument $legalDocument): StreamedResponse
    {
        $this->ensureOwner($request, $legalDocument);

        abort_unless(Storage::disk('public')->exists($legalDocument->file_path), 404);

        return Storage::disk('public')->response(
            $legalDocument->file_path,
            basename($legalDocument->file_path),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toPayload(LegalDocument $document): array
    {
        return [
            'id' => $document->id,
            'type' => $document->type,
            'label' => LegalDocument::labelFor($document->type),
            'url' => route('legalitas.preview', $document),
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'uploaded_at' => $document->updated_at?->toISOString(),
        ];
    }

    private function ensureOwner(Request $request, LegalDocument $legalDocument): void
    {
        abort_unless($legalDocument->user_id === $request->user()->id, 403);
    }
}
