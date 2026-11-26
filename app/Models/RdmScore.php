<?php

namespace App\Models;

use App\Http\Resources\JsonApi\RdmScoreJsonApiResource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $student_id
 * @property string $mapel
 * @property int $semester
 * @property float $nilai
 */
#[Table('rdm_scores')]
#[Fillable([
    'user_id',
    'student_id',
    'mapel',
    'semester',
    'nilai',
])]
#[UseResource(RdmScoreJsonApiResource::class)]
class RdmScore extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'nilai' => 'float',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
