<?php

namespace App\Models;

use App\Http\Resources\JsonApi\RombelJsonApiResource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $tingkat
 * @property string $kode
 * @property string|null $wali
 */
#[Table('rombels')]
#[Fillable(['tingkat', 'kode', 'wali'])]
#[UseResource(RombelJsonApiResource::class)]
class Rombel extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
