<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nama
 * @property string|null $nisn
 * @property string|null $nis
 * @property string $jenis_kelamin
 * @property string $kelas
 * @property string|null $tempat_lahir
 * @property Carbon|null $tanggal_lahir
 * @property string|null $nama_wali
 * @property string|null $no_hp
 * @property string|null $alamat
 * @property string $status
 * @property string|null $catatan
 */
class Student extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'nisn',
        'nis',
        'jenis_kelamin',
        'kelas',
        'tempat_lahir',
        'tanggal_lahir',
        'nama_wali',
        'no_hp',
        'alamat',
        'status',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
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
