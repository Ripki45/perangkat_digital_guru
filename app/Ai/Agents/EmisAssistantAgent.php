<?php

namespace App\Ai\Agents;

use App\Ai\Tools\EmisDirectoryLookupTool;
use App\Models\User;
use App\Services\EmisPayloadMapper;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

#[Temperature(0.3)]
#[MaxSteps(5)]
#[Timeout(45)]
class EmisAssistantAgent implements Agent, HasTools
{
    use Promptable;

    public function __construct(private readonly User $user) {}

    public function instructions(): Stringable|string
    {
        $profile = EmisPayloadMapper::madrasahProfilePayload($this->user);
        $totalStudents = $this->user->students()->count();
        $activeStudents = $this->user->students()->where('status', 'Aktif')->count();
        $totalGurus = $this->user->gurus()->count();

        return implode("\n", [
            'Anda adalah Asisten Cerdas SIM EMIS 4.0 & Kurikulum Merdeka Berbasis Panca Cinta MI.',
            "Madrasah aktif: {$profile['namaMadrasah']} (NSM: {$profile['nsm']}, NPSN: {$profile['npsn']}, TA: {$profile['tahunPelajaran']}).",
            "Ringkasan data pengguna saat ini: {$activeStudents} siswa aktif dari {$totalStudents} total data siswa, dan {$totalGurus} GTK terdata.",
            'Jawab dalam Bahasa Indonesia yang ringkas, sopan, dan akurat. Gunakan tool emis_directory_lookup bila perlu mengecek data siswa/GTK spesifik.',
            'Jangan pernah mengarang NISN, NIK, atau nilai RDM jika tidak ditemukan di database.',
        ]);
    }

    public function tools(): iterable
    {
        return [
            new EmisDirectoryLookupTool($this->user),
        ];
    }
}
