<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $kode
 * @property string $nama
 * @property string $kelompok
 * @property int $kkm
 * @property int $jtm
 * @property string $tingkat
 * @property bool $aktif
 */
#[Table('mapels')]
#[Fillable([
    'kode',
    'nama',
    'kelompok',
    'kkm',
    'jtm',
    'tingkat',
    'aktif',
])]
class Mapel extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kkm' => 'integer',
            'jtm' => 'integer',
            'aktif' => 'boolean',
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
