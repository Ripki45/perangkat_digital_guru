<?php

namespace App\Http\Resources\JsonApi;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @mixin Student
 */
class StudentJsonApiResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'students';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        $payload = $this->resource->toEmisPayload();

        return [
            'nama' => $payload['nama'],
            'nisn' => $payload['nisn'],
            'nis_lokal' => $payload['nisLokal'],
            'jk' => $payload['jk'],
            'tingkat' => $payload['tingkat'],
            'rombel' => $payload['rombel'],
            'status_siswa' => $payload['statusSiswa'],
            'tempat_lahir' => $payload['tempatLahir'],
            'tgl_lahir' => $payload['tglLahir'],
            'status_pip' => $payload['statusPip'],
            'no_kip' => $payload['noKip'],
            'tempat_tinggal' => $payload['tempatTinggal'],
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function toRelationships(Request $request): array
    {
        return [
            'rdmScores' => RdmScoreJsonApiResource::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toLinks(Request $request): array
    {
        return [
            'self' => route('api.v1.emis.students.show', ['student' => $this->id]),
        ];
    }
}
