<?php

namespace App\Models;

use App\Http\Resources\JsonApi\StudentJsonApiResource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nama
 * @property string|null $nisn
 * @property string|null $nis
 * @property string|null $nik
 * @property string $jenis_kelamin
 * @property string $kelas
 * @property string|null $tingkat
 * @property string|null $rombel_masuk
 * @property string|null $tempat_lahir
 * @property Carbon|null $tanggal_lahir
 * @property string|null $nama_wali
 * @property string|null $no_hp
 * @property string|null $alamat
 * @property string $status
 * @property string|null $catatan
 * @property string|null $foto
 * @property array<string, mixed>|null $emis_data
 */
#[Table('students')]
#[Fillable([
    'nama',
    'nisn',
    'nis',
    'nik',
    'jenis_kelamin',
    'kelas',
    'tingkat',
    'rombel_masuk',
    'tempat_lahir',
    'tanggal_lahir',
    'nama_wali',
    'no_hp',
    'alamat',
    'status',
    'catatan',
    'foto',
    'emis_data',
])]
#[UseResource(StudentJsonApiResource::class)]
class Student extends Model
{
    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function aktif(Builder $query): void
    {
        $query->where('status', 'Aktif');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function byRombel(Builder $query, string $rombel): void
    {
        $query->where('kelas', $rombel);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
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
     * @return HasMany<RdmScore, $this>
     */
    public function rdmScores(): HasMany
    {
        return $this->hasMany(RdmScore::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toEmisPayload(): array
    {
        $extra = is_array($this->emis_data) ? $this->emis_data : [];
        $derivedTingkat = $this->tingkat;
        if (! $derivedTingkat && preg_match('/^([1-6])/', $this->kelas, $matches) === 1) {
            $derivedTingkat = $matches[1];
        }

        return [
            'id' => $this->id,
            'foto' => $this->foto ?? '',
            'nisLokal' => $this->nis ?? '',
            'nisn' => $this->nisn ?? '',
            'nik' => $this->nik ?? '',
            'noKK' => (string) ($extra['noKK'] ?? ''),
            'nama' => $this->nama,
            'tempatLahir' => $this->tempat_lahir ?? '',
            'tglLahir' => $this->tanggal_lahir?->format('Y-m-d') ?? '',
            'jk' => $this->jenis_kelamin,
            'agama' => (string) ($extra['agama'] ?? 'Islam'),
            'kewarganegaraan' => (string) ($extra['kewarganegaraan'] ?? 'WNI'),
            'anakKe' => (int) ($extra['anakKe'] ?? 1),
            'jmlSaudara' => (int) ($extra['jmlSaudara'] ?? 0),
            'hpSiswa' => $this->no_hp ?? '',
            'hobi' => (string) ($extra['hobi'] ?? ''),
            'citaCita' => (string) ($extra['citaCita'] ?? ''),
            'tingkat' => $derivedTingkat ?: '1',
            'rombel' => $this->kelas,
            'rombelMasuk' => $this->rombel_masuk ?? $this->kelas,
            'statusSiswa' => $this->status,
            'tglMasuk' => (string) ($extra['tglMasuk'] ?? ''),
            'jenisPendaftaran' => (string) ($extra['jenisPendaftaran'] ?? 'Siswa Baru'),
            'asalSekolah' => (string) ($extra['asalSekolah'] ?? ''),
            'npsnAsal' => (string) ($extra['npsnAsal'] ?? ''),
            'noIjazahAsal' => (string) ($extra['noIjazahAsal'] ?? ''),
            'noUnSebelumnya' => (string) ($extra['noUnSebelumnya'] ?? ''),
            'tempatTinggal' => (string) ($extra['tempatTinggal'] ?? 'Bersama Orang Tua'),
            'nspPesantren' => (string) ($extra['nspPesantren'] ?? ''),
            'namaPondok' => (string) ($extra['namaPondok'] ?? ''),
            'alamatJalan' => $this->alamat ?? '',
            'rt' => (string) ($extra['rt'] ?? ''),
            'rw' => (string) ($extra['rw'] ?? ''),
            'desa' => (string) ($extra['desa'] ?? ''),
            'kecamatan' => (string) ($extra['kecamatan'] ?? ''),
            'kabupaten' => (string) ($extra['kabupaten'] ?? ''),
            'provinsi' => (string) ($extra['provinsi'] ?? ''),
            'kodePos' => (string) ($extra['kodePos'] ?? ''),
            'jarak' => (string) ($extra['jarak'] ?? 'Kurang dari 5 km'),
            'transportasi' => (string) ($extra['transportasi'] ?? 'Jalan Kaki'),
            'waktuTempuh' => (string) ($extra['waktuTempuh'] ?? '1 - 10 Menit'),
            'ayahNama' => (string) ($extra['ayahNama'] ?? $this->nama_wali ?? ''),
            'ayahStatus' => (string) ($extra['ayahStatus'] ?? 'Masih Hidup'),
            'ayahNik' => (string) ($extra['ayahNik'] ?? ''),
            'ayahTtl' => (string) ($extra['ayahTtl'] ?? ''),
            'ayahPendidikan' => (string) ($extra['ayahPendidikan'] ?? 'SMA/Sederajat'),
            'ayahPekerjaan' => (string) ($extra['ayahPekerjaan'] ?? ''),
            'ayahPenghasilan' => (string) ($extra['ayahPenghasilan'] ?? ''),
            'ibuNama' => (string) ($extra['ibuNama'] ?? ''),
            'ibuStatus' => (string) ($extra['ibuStatus'] ?? 'Masih Hidup'),
            'ibuNik' => (string) ($extra['ibuNik'] ?? ''),
            'ibuTtl' => (string) ($extra['ibuTtl'] ?? ''),
            'ibuPendidikan' => (string) ($extra['ibuPendidikan'] ?? 'SMA/Sederajat'),
            'ibuPekerjaan' => (string) ($extra['ibuPekerjaan'] ?? 'Ibu Rumah Tangga'),
            'ibuPenghasilan' => (string) ($extra['ibuPenghasilan'] ?? 'Tidak Berpenghasilan'),
            'ortuHp' => (string) ($extra['ortuHp'] ?? $this->no_hp ?? ''),
            'waliNama' => (string) ($extra['waliNama'] ?? $this->nama_wali ?? ''),
            'waliNik' => (string) ($extra['waliNik'] ?? ''),
            'waliPekerjaan' => (string) ($extra['waliPekerjaan'] ?? ''),
            'waliPenghasilan' => (string) ($extra['waliPenghasilan'] ?? ''),
            'waliHubungan' => (string) ($extra['waliHubungan'] ?? ''),
            'waliHp' => (string) ($extra['waliHp'] ?? ''),
            'statusPip' => (string) ($extra['statusPip'] ?? 'Tidak'),
            'noKip' => (string) ($extra['noKip'] ?? ''),
            'rekPip' => (string) ($extra['rekPip'] ?? ''),
            'namaRekPip' => (string) ($extra['namaRekPip'] ?? ''),
            'statusPkh' => (string) ($extra['statusPkh'] ?? 'Tidak'),
            'noPkh' => (string) ($extra['noPkh'] ?? ''),
            'statusKks' => (string) ($extra['statusKks'] ?? 'Tidak'),
            'noKks' => (string) ($extra['noKks'] ?? ''),
            'tb' => (int) ($extra['tb'] ?? 0),
            'bb' => (int) ($extra['bb'] ?? 0),
            'lingkarKepala' => (int) ($extra['lingkarKepala'] ?? 0),
            'golDarah' => (string) ($extra['golDarah'] ?? 'Tidak Tahu'),
            'disabilitas' => (string) ($extra['disabilitas'] ?? 'Tidak Ada'),
            'keluarDariMadrasah' => (string) ($extra['keluarDariMadrasah'] ?? ($this->status === 'Aktif' ? 'Masih Aktif' : $this->status)),
            'tglKeluar' => (string) ($extra['tglKeluar'] ?? ''),
            'noSeriIjazahMadrasah' => (string) ($extra['noSeriIjazahMadrasah'] ?? ''),
            'sekolahTujuan' => (string) ($extra['sekolahTujuan'] ?? ''),
            'keteranganKeluar' => (string) ($extra['keteranganKeluar'] ?? $this->catatan ?? ''),
        ];
    }
}
