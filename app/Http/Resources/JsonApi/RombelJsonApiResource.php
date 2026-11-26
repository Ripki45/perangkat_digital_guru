<?php

namespace App\Http\Resources\JsonApi;

use App\Models\Rombel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @mixin Rombel
 */
class RombelJsonApiResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'rombels';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        return [
            'tingkat' => $this->tingkat,
            'kode' => $this->kode,
            'wali' => $this->wali,
        ];
    }
}
