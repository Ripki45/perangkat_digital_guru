<?php

namespace App\Models;

use App\Http\Resources\JsonApi\GuruJsonApiResource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nama
 * @property string|null $gelar_depan
 * @property string|null $gelar_belakang
 * @property string|null $nik
 * @property string|null $no_kk
 * @property string $jk
 * @property string|null $tempat_lahir
 * @property Carbon|null $tgl_lahir
 * @property string|null $hp
 * @property string|null $email
 * @property string|null $pegid
 * @property string|null $npk
 * @property string|null $nuptk
 * @property string|null $nip
 * @property string $status_pegawai
 * @property string $jenis_ptk
 * @property string|null $golongan
 * @property string $status_aktif
 * @property string $pendidikan
 * @property string $status_sertifikasi
 * @property string $satminkal
 * @property string|null $mapel_utama
 * @property int $jtm
 * @property string|null $tugas_tambahan
 * @property array<string, mixed>|null $emis_data
 */
#[Table('gurus')]
#[Fillable([
    'nama',
    'gelar_depan',
    'gelar_belakang',
    'nik',
    'no_kk',
    'jk',
    'tempat_lahir',
    'tgl_lahir',
    'hp',
    'email',
    'pegid',
    'npk',
    'nuptk',
    'nip',
    'status_pegawai',
    'jenis_ptk',
    'golongan',
    'status_aktif',
    'pendidikan',
    'status_sertifikasi',
    'satminkal',
    'mapel_utama',
    'jtm',
    'tugas_tambahan',
    'emis_data',
])]
#[UseResource(GuruJsonApiResource::class)]
class Guru extends Model
{
    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function aktif(Builder $query): void
    {
        $query->where('status_aktif', 'Aktif');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_lahir' => 'date',
            'jtm' => 'integer',
            'emis_data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toEmisPayload(): array
    {
        $extra = $this->emis_data ?? [];

        return [
            'id' => $this->id,
            'gelarDepan' => $this->gelar_depan ?? '',
            'nama' => $this->nama,
            'gelarBelakang' => $this->gelar_belakang ?? '',
            'nik' => $this->nik ?? '',
            'noKK' => $this->no_kk ?? '',
            'jk' => $this->jk,
            'agama' => (string) ($extra['agama'] ?? 'Islam'),
            'tempatLahir' => $this->tempat_lahir ?? '',
            'tglLahir' => $this->tgl_lahir?->format('Y-m-d') ?? '',
            'namaIbu' => (string) ($extra['namaIbu'] ?? ''),
            'statusNikah' => (string) ($extra['statusNikah'] ?? 'Kawin'),
            'hp' => $this->hp ?? '',
            'email' => $this->email ?? '',
            'alamat' => (string) ($extra['alamat'] ?? ''),
            'pegid' => $this->pegid ?? '',
            'npk' => $this->npk ?? '',
            'nuptk' => $this->nuptk ?? '',
            'nip' => $this->nip ?? '',
            'statusPegawai' => $this->status_pegawai,
            'jenisPtk' => $this->jenis_ptk,
            'golongan' => $this->golongan ?? '-',
            'tmtAwal' => (string) ($extra['tmtAwal'] ?? ''),
            'tmtMadrasah' => (string) ($extra['tmtMadrasah'] ?? ''),
            'inpassing' => (string) ($extra['inpassing'] ?? 'Belum'),
            'statusAktif' => $this->status_aktif,
            'noSk' => (string) ($extra['noSk'] ?? ''),
            'pendidikan' => $this->pendidikan,
            'prodi' => (string) ($extra['prodi'] ?? ''),
            'kampus' => (string) ($extra['kampus'] ?? ''),
            'tahunLulus' => (string) ($extra['tahunLulus'] ?? ''),
            'statusSertifikasi' => $this->status_sertifikasi,
            'nrg' => (string) ($extra['nrg'] ?? ''),
            'noSerdik' => (string) ($extra['noSerdik'] ?? ''),
            'mapelSertifikasi' => (string) ($extra['mapelSertifikasi'] ?? ''),
            'tahunSertifikasi' => (string) ($extra['tahunSertifikasi'] ?? ''),
            'satminkal' => $this->satminkal,
            'mapelUtama' => $this->mapel_utama ?? '',
            'jtm' => $this->jtm,
            'tugasTambahan' => $this->tugas_tambahan ?? 'Tidak Ada',
            'rekening' => (string) ($extra['rekening'] ?? ''),
            'npwp' => (string) ($extra['npwp'] ?? ''),
        ];
    }
}
