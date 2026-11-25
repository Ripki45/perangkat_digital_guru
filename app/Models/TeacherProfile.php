<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_guru',
        'nip',
        'nuptk',
        'jabatan',
        'mata_pelajaran',
        'kelas_wali',
        'tahun_ajaran',
        'semester',
        'motto',
        'nama_madrasah',
        'npsn',
        'alamat',
        'desa',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'kode_pos',
        'telepon',
        'email_madrasah',
        'website',
        'nama_kepala_madrasah',
        'nip_kepala_madrasah',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Persentase kelengkapan data identitas.
     */
    public function completeness(): int
    {
        $fields = [
            'nama_guru',
            'nip',
            'jabatan',
            'mata_pelajaran',
            'nama_madrasah',
            'npsn',
            'alamat',
            'kecamatan',
            'kabupaten',
            'tahun_ajaran',
            'semester',
            'nama_kepala_madrasah',
        ];

        $filled = collect($fields)
            ->filter(fn (string $field): bool => filled($this->getAttribute($field)))
            ->count();

        return (int) round(($filled / count($fields)) * 100);
    }
}
