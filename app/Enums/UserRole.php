<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case WaliKelas = 'wali_kelas';
    case GuruMapel = 'guru_mapel';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::WaliKelas => 'Guru Wali Kelas',
            self::GuruMapel => 'Guru Mata Pelajaran',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
