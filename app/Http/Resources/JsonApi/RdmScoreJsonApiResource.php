<?php

namespace App\Http\Resources\JsonApi;

use App\Models\RdmScore;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @mixin RdmScore
 */
class RdmScoreJsonApiResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'rdm-scores';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'student_id' => $this->student_id,
            'mapel' => $this->mapel,
            'semester' => $this->semester,
            'nilai' => $this->nilai,
        ];
    }
}
