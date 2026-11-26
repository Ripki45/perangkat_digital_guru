<?php

namespace App\Http\Resources\JsonApi;

use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @mixin Guru
 */
class GuruJsonApiResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'gurus';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        $payload = $this->resource->toEmisPayload();

        return [
            'gelar_depan' => $payload['gelarDepan'],
            'nama' => $payload['nama'],
            'gelar_belakang' => $payload['gelarBelakang'],
            'nuptk' => $payload['nuptk'],
            'nip' => $payload['nip'],
            'npk' => $payload['npk'],
            'jk' => $payload['jk'],
            'status_pegawai' => $payload['statusPegawai'],
            'jenis_ptk' => $payload['jenisPtk'],
            'status_aktif' => $payload['statusAktif'],
            'pendidikan' => $payload['pendidikan'],
            'status_sertifikasi' => $payload['statusSertifikasi'],
            'mapel_utama' => $payload['mapelUtama'],
            'jtm' => $payload['jtm'],
        ];
    }
}
