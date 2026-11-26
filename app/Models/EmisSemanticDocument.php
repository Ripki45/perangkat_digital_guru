<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $source_type
 * @property int|null $source_id
 * @property string $title
 * @property string $content
 * @property array<string, mixed>|null $metadata
 * @property array<int, float>|null $embedding
 * @property string|null $embedding_model
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property float|null $similarity_score
 */
#[Table('emis_semantic_documents')]
#[Fillable([
    'user_id',
    'source_type',
    'source_id',
    'title',
    'content',
    'metadata',
    'embedding',
    'embedding_model',
])]
class EmisSemanticDocument extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'metadata' => 'array',
            'embedding' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
