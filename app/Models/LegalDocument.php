<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalDocument extends Model
{
    /**
     * Dokumen legalitas yang didukung aplikasi.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'logo' => 'Logo Madrasah',
        'ttd_kepala' => 'Tanda Tangan Kepala Madrasah',
        'ttd_guru' => 'Tanda Tangan Guru',
        'stempel' => 'Stempel Madrasah',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return self::TYPES;
    }

    public static function labelFor(string $type): string
    {
        return self::TYPES[$type] ?? 'Dokumen';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
