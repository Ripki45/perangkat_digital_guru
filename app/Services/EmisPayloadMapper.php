<?php

namespace App\Services;

use App\Models\LegalDocument;
use App\Models\TeacherProfile;
use App\Models\User;

class EmisPayloadMapper
{
    /**
     * @var list<string>
     */
    public const RDM_SUBJECTS_MI = [
        "Al-Qur'an Hadis",
        'Akidah Akhlak',
        'Fikih',
        'Sejarah Kebudayaan Islam (SKI)',
        'Pendidikan Pancasila',
        'Bahasa Indonesia',
        'Bahasa Arab',
        'Matematika',
        'IPAS (Ilmu Pengetahuan Alam dan Sosial)',
        'Bahasa Inggris',
        'Seni Budaya (SBdP)',
        'PJOK',
        'Muatan Lokal (Bahasa Sunda / BTQ)',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function studentAttributesFromEmis(array $data): array
    {
        $rombel = trim((string) ($data['rombel'] ?? '1-A')) ?: '1-A';
        $tingkat = trim((string) ($data['tingkat'] ?? ''));

        if ($tingkat === '' && preg_match('/^([1-6])/', $rombel, $m) === 1) {
            $tingkat = $m[1];
        }

        $extraKeys = [
            'noKK', 'agama', 'kewarganegaraan', 'anakKe', 'jmlSaudara', 'hobi', 'citaCita',
            'tglMasuk', 'jenisPendaftaran', 'asalSekolah', 'npsnAsal', 'noIjazahAsal', 'noUnSebelumnya',
            'tempatTinggal', 'nspPesantren', 'namaPondok', 'rt', 'rw', 'desa', 'kecamatan',
            'kabupaten', 'provinsi', 'kodePos', 'jarak', 'transportasi', 'waktuTempuh',
            'ayahNama', 'ayahStatus', 'ayahNik', 'ayahTtl', 'ayahPendidikan', 'ayahPekerjaan', 'ayahPenghasilan',
            'ibuNama', 'ibuStatus', 'ibuNik', 'ibuTtl', 'ibuPendidikan', 'ibuPekerjaan', 'ibuPenghasilan', 'ortuHp',
            'waliNama', 'waliNik', 'waliPekerjaan', 'waliPenghasilan', 'waliHubungan', 'waliHp',
            'statusPip', 'noKip', 'rekPip', 'namaRekPip', 'statusPkh', 'noPkh', 'statusKks', 'noKks',
            'tb', 'bb', 'lingkarKepala', 'golDarah', 'disabilitas',
            'keluarDariMadrasah', 'tglKeluar', 'noSeriIjazahMadrasah', 'sekolahTujuan', 'keteranganKeluar',
        ];

        $emisData = [];
        foreach ($extraKeys as $key) {
            if (array_key_exists($key, $data)) {
                $emisData[$key] = $data[$key];
            }
        }

        $tglLahir = trim((string) ($data['tglLahir'] ?? ''));
        $waliDisplay = trim((string) ($data['waliNama'] ?? ''))
            ?: trim((string) ($data['ayahNama'] ?? ''))
            ?: trim((string) ($data['ibuNama'] ?? ''));

        return [
            'nama' => mb_strtoupper(trim((string) ($data['nama'] ?? '')), 'UTF-8'),
            'nisn' => self::nullableString($data['nisn'] ?? null),
            'nis' => self::nullableString($data['nisLokal'] ?? null),
            'nik' => self::nullableString($data['nik'] ?? null),
            'jenis_kelamin' => strtoupper(trim((string) ($data['jk'] ?? 'L'))) === 'P' ? 'P' : 'L',
            'kelas' => $rombel,
            'tingkat' => $tingkat ?: '1',
            'rombel_masuk' => self::nullableString($data['rombelMasuk'] ?? null) ?? $rombel,
            'tempat_lahir' => self::nullableString($data['tempatLahir'] ?? null),
            'tanggal_lahir' => $tglLahir !== '' ? $tglLahir : null,
            'nama_wali' => $waliDisplay !== '' ? $waliDisplay : null,
            'no_hp' => self::nullableString($data['hpSiswa'] ?? $data['ortuHp'] ?? null),
            'alamat' => self::nullableString($data['alamatJalan'] ?? null),
            'status' => trim((string) ($data['statusSiswa'] ?? 'Aktif')) ?: 'Aktif',
            'catatan' => self::nullableString($data['keteranganKeluar'] ?? null),
            'foto' => self::nullableString($data['foto'] ?? null),
            'emis_data' => $emisData,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function guruAttributesFromEmis(array $data): array
    {
        $extraKeys = [
            'agama', 'namaIbu', 'statusNikah', 'alamat',
            'tmtAwal', 'tmtMadrasah', 'inpassing', 'noSk',
            'prodi', 'kampus', 'tahunLulus',
            'nrg', 'noSerdik', 'mapelSertifikasi', 'tahunSertifikasi',
            'rekening', 'npwp',
        ];

        $emisData = [];
        foreach ($extraKeys as $key) {
            if (array_key_exists($key, $data)) {
                $emisData[$key] = $data[$key];
            }
        }

        $tglLahir = trim((string) ($data['tglLahir'] ?? ''));

        return [
            'nama' => mb_strtoupper(trim((string) ($data['nama'] ?? '')), 'UTF-8'),
            'gelar_depan' => self::nullableString($data['gelarDepan'] ?? null),
            'gelar_belakang' => self::nullableString($data['gelarBelakang'] ?? null),
            'nik' => self::nullableString($data['nik'] ?? null),
            'no_kk' => self::nullableString($data['noKK'] ?? null),
            'jk' => strtoupper(trim((string) ($data['jk'] ?? 'L'))) === 'P' ? 'P' : 'L',
            'tempat_lahir' => self::nullableString($data['tempatLahir'] ?? null),
            'tgl_lahir' => $tglLahir !== '' ? $tglLahir : null,
            'hp' => self::nullableString($data['hp'] ?? null),
            'email' => self::nullableString($data['email'] ?? null),
            'pegid' => self::nullableString($data['pegid'] ?? null),
            'npk' => self::nullableString($data['npk'] ?? null),
            'nuptk' => self::nullableString($data['nuptk'] ?? null),
            'nip' => self::nullableString($data['nip'] ?? null),
            'status_pegawai' => trim((string) ($data['statusPegawai'] ?? 'PNS')) ?: 'PNS',
            'jenis_ptk' => trim((string) ($data['jenisPtk'] ?? 'Guru Kelas MI')) ?: 'Guru Kelas MI',
            'golongan' => self::nullableString($data['golongan'] ?? null),
            'status_aktif' => trim((string) ($data['statusAktif'] ?? 'Aktif')) ?: 'Aktif',
            'pendidikan' => trim((string) ($data['pendidikan'] ?? 'D4/S1')) ?: 'D4/S1',
            'status_sertifikasi' => trim((string) ($data['statusSertifikasi'] ?? 'Belum Sertifikasi')) ?: 'Belum Sertifikasi',
            'satminkal' => trim((string) ($data['satminkal'] ?? 'Satminkal (Induk)')) ?: 'Satminkal (Induk)',
            'mapel_utama' => self::nullableString($data['mapelUtama'] ?? null),
            'jtm' => max(0, (int) ($data['jtm'] ?? 0)),
            'tugas_tambahan' => self::nullableString($data['tugasTambahan'] ?? null),
            'emis_data' => $emisData,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function madrasahProfilePayload(User $user): array
    {
        /** @var TeacherProfile|null $profile */
        $profile = $user->teacherProfile;
        /** @var LegalDocument|null $logoDoc */
        $logoDoc = $user->legalDocuments()->where('type', 'logo')->first();

        $defaults = self::defaultMadrasahProfile();
        $tahunPelajaran = $profile
            ? trim("{$profile->tahun_ajaran} - {$profile->semester}")
            : $defaults['tahunPelajaran'];

        return [
            'namaMadrasah' => $profile?->nama_madrasah ?: $defaults['namaMadrasah'],
            'tahunPelajaran' => $tahunPelajaran,
            'nsm' => $profile?->nsm ?: $defaults['nsm'],
            'npsn' => $profile?->npsn ?: $defaults['npsn'],
            'kemenagKab' => $profile?->kemenag_kab ?: $defaults['kemenagKab'],
            'alamat' => $profile?->alamat ?: $defaults['alamat'],
            'tempatSurat' => $profile?->tempat_surat ?: ($profile?->kabupaten ?: $defaults['tempatSurat']),
            'website' => $profile?->website ?: $defaults['website'],
            'email' => $profile?->email_madrasah ?: $defaults['email'],
            'kepalaNama' => $profile?->nama_kepala_madrasah ?: $defaults['kepalaNama'],
            'kepalaNip' => $profile?->nip_kepala_madrasah ?: $defaults['kepalaNip'],
            'operatorNama' => $profile?->operator_nama ?: ($profile?->nama_guru ?: $user->name),
            'operatorHp' => $profile?->operator_hp ?: $defaults['operatorHp'],
            'logoKiri' => $profile?->logo_kiri ?: ($logoDoc ? route('legalitas.preview', $logoDoc) : ''),
            'logoKanan' => $profile?->logo_kanan ?: '',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultMadrasahProfile(): array
    {
        return [
            'namaMadrasah' => 'MADRASAH IBTIDAIYAH NEGERI 1 CIAMIS',
            'tahunPelajaran' => '2026/2027 - Ganjil',
            'nsm' => '111132070001',
            'npsn' => '60708447',
            'kemenagKab' => 'KANTOR KEMENTERIAN AGAMA KABUPATEN CIAMIS',
            'alamat' => 'Jl. Raya Panjalu - Kawali Dusun Sukamaju Desa Maparah Kec. Panjalu Kab. Ciamis',
            'tempatSurat' => 'Ciamis',
            'website' => 'www.min1ciamis.sch.id',
            'email' => 'min1ciamis@kemenag.go.id',
            'kepalaNama' => 'H. Dede Hidayat, S.Ag., M.Pd.I.',
            'kepalaNip' => '197204151998031003',
            'operatorNama' => 'Operator EMIS MIN 1 Ciamis',
            'operatorHp' => '081234567890',
            'logoKiri' => '',
            'logoKanan' => '',
        ];
    }

    /**
     * @return list<array{tingkat: string, kode: string, wali: string}>
     */
    public static function defaultRombels(): array
    {
        return [
            ['tingkat' => '1', 'kode' => '1-A', 'wali' => 'Siti Aminah, S.Pd.I.'],
            ['tingkat' => '1', 'kode' => '1-B', 'wali' => ''],
            ['tingkat' => '2', 'kode' => '2-A', 'wali' => ''],
            ['tingkat' => '2', 'kode' => '2-B', 'wali' => ''],
            ['tingkat' => '3', 'kode' => '3-A', 'wali' => ''],
            ['tingkat' => '3', 'kode' => '3-B', 'wali' => ''],
            ['tingkat' => '4', 'kode' => '4-A', 'wali' => 'Rina Marlina, S.Pd.'],
            ['tingkat' => '4', 'kode' => '4-B', 'wali' => ''],
            ['tingkat' => '5', 'kode' => '5-A', 'wali' => ''],
            ['tingkat' => '5', 'kode' => '5-B', 'wali' => ''],
            ['tingkat' => '6', 'kode' => '6-A', 'wali' => 'Ahmad Fauzan, S.Pd.I., M.Pd.'],
            ['tingkat' => '6', 'kode' => '6-B', 'wali' => ''],
        ];
    }

    public static function ensureDefaultRombels(User $user): void
    {
        if ($user->rombels()->doesntExist()) {
            foreach (self::defaultRombels() as $rombel) {
                $user->rombels()->firstOrCreate(
                    ['kode' => $rombel['kode']],
                    ['tingkat' => $rombel['tingkat'], 'wali' => $rombel['wali'] ?: null],
                );
            }
        }
    }

    /**
     * @return list<array{kode: string, nama: string, kelompok: string, kkm: int, jtm: int, tingkat: string}>
     */
    public static function defaultMapels(): array
    {
        return [
            ['kode' => 'QH', 'nama' => "Al-Qur'an Hadis", 'kelompok' => 'PAI & Bahasa Arab', 'kkm' => 75, 'jtm' => 2, 'tingkat' => '1-6'],
            ['kode' => 'AA', 'nama' => 'Akidah Akhlak', 'kelompok' => 'PAI & Bahasa Arab', 'kkm' => 75, 'jtm' => 2, 'tingkat' => '1-6'],
            ['kode' => 'FIK', 'nama' => 'Fikih', 'kelompok' => 'PAI & Bahasa Arab', 'kkm' => 75, 'jtm' => 2, 'tingkat' => '1-6'],
            ['kode' => 'SKI', 'nama' => 'Sejarah Kebudayaan Islam', 'kelompok' => 'PAI & Bahasa Arab', 'kkm' => 75, 'jtm' => 2, 'tingkat' => '3-6'],
            ['kode' => 'ARB', 'nama' => 'Bahasa Arab', 'kelompok' => 'PAI & Bahasa Arab', 'kkm' => 75, 'jtm' => 2, 'tingkat' => '1-6'],
            ['kode' => 'PP', 'nama' => 'Pendidikan Pancasila', 'kelompok' => 'Umum', 'kkm' => 75, 'jtm' => 4, 'tingkat' => '1-6'],
            ['kode' => 'IND', 'nama' => 'Bahasa Indonesia', 'kelompok' => 'Umum', 'kkm' => 75, 'jtm' => 6, 'tingkat' => '1-6'],
            ['kode' => 'MTK', 'nama' => 'Matematika', 'kelompok' => 'Umum', 'kkm' => 75, 'jtm' => 5, 'tingkat' => '1-6'],
            ['kode' => 'IPAS', 'nama' => 'IPAS', 'kelompok' => 'Umum', 'kkm' => 75, 'jtm' => 5, 'tingkat' => '3-6'],
            ['kode' => 'PJK', 'nama' => 'PJOK', 'kelompok' => 'Umum', 'kkm' => 75, 'jtm' => 3, 'tingkat' => '1-6'],
            ['kode' => 'SBD', 'nama' => 'Seni Budaya & Prakarya', 'kelompok' => 'Umum', 'kkm' => 75, 'jtm' => 3, 'tingkat' => '1-6'],
            ['kode' => 'ING', 'nama' => 'Bahasa Inggris', 'kelompok' => 'Umum', 'kkm' => 75, 'jtm' => 2, 'tingkat' => '1-6'],
            ['kode' => 'SUN', 'nama' => 'Bahasa Daerah / Sunda', 'kelompok' => 'Muatan Lokal', 'kkm' => 75, 'jtm' => 2, 'tingkat' => '1-6'],
            ['kode' => 'MUL', 'nama' => 'Muatan Lokal / BTQ', 'kelompok' => 'Muatan Lokal', 'kkm' => 75, 'jtm' => 2, 'tingkat' => '1-6'],
        ];
    }

    public static function ensureDefaultMapels(User $user): void
    {
        if ($user->mapels()->doesntExist()) {
            foreach (self::defaultMapels() as $mapel) {
                $user->mapels()->firstOrCreate(
                    ['kode' => $mapel['kode']],
                    $mapel,
                );
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sampleStudents(): array
    {
        return [
            [
                'nisLokal' => '202601001', 'nisn' => '0191234567', 'nik' => '3207141205190001', 'noKK' => '3207140101180011',
                'nama' => 'MUHAMMAD ZAYN AL-FATIH', 'tempatLahir' => 'Ciamis', 'tglLahir' => '2019-05-12', 'jk' => 'L',
                'agama' => 'Islam', 'kewarganegaraan' => 'WNI', 'anakKe' => 1, 'jmlSaudara' => 1, 'hpSiswa' => '081223344551',
                'hobi' => 'Menggambar & Hafalan Juz Amma', 'citaCita' => 'Dokter', 'tingkat' => '1', 'rombel' => '1-A',
                'rombelMasuk' => '1-A', 'statusSiswa' => 'Aktif', 'tglMasuk' => '2026-07-13', 'jenisPendaftaran' => 'Siswa Baru',
                'asalSekolah' => 'RA Al-Huda Maparah', 'npsnAsal' => '69738120', 'noIjazahAsal' => 'RA-26/0012',
                'noUnSebelumnya' => '3207-LU-2019-00123', 'tempatTinggal' => 'Bersama Orang Tua',
                'alamatJalan' => 'Dusun Sukamaju RT 002 RW 001', 'rt' => '002', 'rw' => '001', 'desa' => 'Maparah',
                'kecamatan' => 'Panjalu', 'kabupaten' => 'Kab. Ciamis', 'provinsi' => 'Jawa Barat', 'kodePos' => '46264',
                'jarak' => 'Kurang dari 5 km', 'transportasi' => 'Jalan Kaki', 'waktuTempuh' => '1 - 10 Menit',
                'ayahNama' => 'Asep Saepuloh', 'ayahStatus' => 'Masih Hidup', 'ayahNik' => '3207141008880002',
                'ayahTtl' => 'Ciamis, 1988-08-10', 'ayahPendidikan' => 'SMA/Sederajat', 'ayahPekerjaan' => 'Wiraswasta',
                'ayahPenghasilan' => 'Rp 2.000.000 - Rp 4.999.999', 'ibuNama' => 'Neng Yuliani', 'ibuStatus' => 'Masih Hidup',
                'ibuNik' => '3207145203910003', 'ibuTtl' => 'Ciamis, 1991-03-12', 'ibuPendidikan' => 'D4/S1',
                'ibuPekerjaan' => 'Ibu Rumah Tangga', 'ibuPenghasilan' => 'Tidak Berpenghasilan', 'ortuHp' => '081223344551',
                'statusPip' => 'Tidak', 'statusPkh' => 'Tidak', 'statusKks' => 'Tidak',
                'tb' => 118, 'bb' => 22, 'lingkarKepala' => 50, 'golDarah' => 'O', 'disabilitas' => 'Tidak Ada',
                'keluarDariMadrasah' => 'Masih Aktif',
            ],
            [
                'nisLokal' => '202304015', 'nisn' => '0162345678', 'nik' => '3207144809160004', 'noKK' => '3207140101150045',
                'nama' => 'AISYAH HUMAIRA PUTRI', 'tempatLahir' => 'Ciamis', 'tglLahir' => '2016-09-08', 'jk' => 'P',
                'agama' => 'Islam', 'kewarganegaraan' => 'WNI', 'anakKe' => 2, 'jmlSaudara' => 2, 'hpSiswa' => '085211223344',
                'hobi' => 'Membaca & Kaligrafi', 'citaCita' => 'Guru / Dosen', 'tingkat' => '4', 'rombel' => '4-A',
                'rombelMasuk' => '1-A', 'statusSiswa' => 'Aktif', 'tglMasuk' => '2023-07-17', 'jenisPendaftaran' => 'Siswa Baru',
                'asalSekolah' => 'TK PGRI Panjalu', 'npsnAsal' => '20254321', 'noIjazahAsal' => 'TK-23/0045',
                'noUnSebelumnya' => '3207-LU-2016-00456', 'tempatTinggal' => 'Bersama Orang Tua',
                'alamatJalan' => 'Dusun Banjarwaru RT 004 RW 002', 'rt' => '004', 'rw' => '002', 'desa' => 'Maparah',
                'kecamatan' => 'Panjalu', 'kabupaten' => 'Kab. Ciamis', 'provinsi' => 'Jawa Barat', 'kodePos' => '46264',
                'jarak' => 'Kurang dari 5 km', 'transportasi' => 'Sepeda Motor', 'waktuTempuh' => '1 - 10 Menit',
                'ayahNama' => 'Dedi Kurniawan', 'ayahStatus' => 'Masih Hidup', 'ayahNik' => '3207141504840001',
                'ayahTtl' => 'Ciamis, 1984-04-15', 'ayahPendidikan' => 'SMA/Sederajat', 'ayahPekerjaan' => 'Petani/Peternak',
                'ayahPenghasilan' => 'Rp 1.000.000 - Rp 1.999.999', 'ibuNama' => 'Ai Rosmiati', 'ibuStatus' => 'Masih Hidup',
                'ibuNik' => '3207145507860002', 'ibuTtl' => 'Ciamis, 1986-07-15', 'ibuPendidikan' => 'SMA/Sederajat',
                'ibuPekerjaan' => 'Ibu Rumah Tangga', 'ibuPenghasilan' => 'Tidak Berpenghasilan', 'ortuHp' => '085211223344',
                'statusPip' => 'Ya', 'noKip' => 'KIP-32071401', 'rekPip' => 'BRI: 4012-01-012345-53-1',
                'namaRekPip' => 'AISYAH HUMAIRA PUTRI', 'statusPkh' => 'Ya', 'noPkh' => 'PKH-3207-0881', 'statusKks' => 'Tidak',
                'tb' => 135, 'bb' => 31, 'lingkarKepala' => 52, 'golDarah' => 'A', 'disabilitas' => 'Tidak Ada',
                'keluarDariMadrasah' => 'Masih Aktif',
            ],
            [
                'nisLokal' => '202106008', 'nisn' => '0143456789', 'nik' => '3207142002140007', 'noKK' => '3207140101130077',
                'nama' => 'RAFFA PRATAMA WIJAYA', 'tempatLahir' => 'Tasikmalaya', 'tglLahir' => '2014-02-20', 'jk' => 'L',
                'agama' => 'Islam', 'kewarganegaraan' => 'WNI', 'anakKe' => 1, 'jmlSaudara' => 1, 'hpSiswa' => '082199887766',
                'hobi' => 'Sepak Bola & Pramuka', 'citaCita' => 'TNI / Polri', 'tingkat' => '6', 'rombel' => '6-A',
                'rombelMasuk' => '1-A', 'statusSiswa' => 'Aktif', 'tglMasuk' => '2021-07-19', 'jenisPendaftaran' => 'Siswa Baru',
                'asalSekolah' => 'RA Persis Panjalu', 'npsnAsal' => '69738125', 'noIjazahAsal' => 'RA-21/0088',
                'noUnSebelumnya' => '3207-LU-2014-00789', 'tempatTinggal' => 'Pondok Pesantren',
                'nspPesantren' => '510032070045', 'namaPondok' => 'PP. Miftahul Ulum Panjalu',
                'alamatJalan' => 'Dusun Cibeureum RT 001 RW 003', 'rt' => '001', 'rw' => '003', 'desa' => 'Ciomas',
                'kecamatan' => 'Panjalu', 'kabupaten' => 'Kab. Ciamis', 'provinsi' => 'Jawa Barat', 'kodePos' => '46264',
                'jarak' => '5 - 10 km', 'transportasi' => 'Angkutan Umum', 'waktuTempuh' => '11 - 19 Menit',
                'ayahNama' => 'Hendra Wijaya', 'ayahStatus' => 'Masih Hidup', 'ayahNik' => '3207140306820005',
                'ayahTtl' => 'Ciamis, 1982-06-03', 'ayahPendidikan' => 'D4/S1', 'ayahPekerjaan' => 'Pegawai Swasta',
                'ayahPenghasilan' => 'Rp 2.000.000 - Rp 4.999.999', 'ibuNama' => 'Wulan Sari', 'ibuStatus' => 'Masih Hidup',
                'ibuNik' => '3207144911850006', 'ibuTtl' => 'Tasikmalaya, 1985-11-09', 'ibuPendidikan' => 'D4/S1',
                'ibuPekerjaan' => 'Guru', 'ibuPenghasilan' => 'Rp 2.000.000 - Rp 4.999.999', 'ortuHp' => '082199887766',
                'statusPip' => 'Tidak', 'statusPkh' => 'Tidak', 'statusKks' => 'Tidak',
                'tb' => 147, 'bb' => 39, 'lingkarKepala' => 53, 'golDarah' => 'B', 'disabilitas' => 'Tidak Ada',
                'keluarDariMadrasah' => 'Masih Aktif',
            ],
            [
                'nisLokal' => '202006022', 'nisn' => '0139988776', 'nik' => '3207146107130009', 'noKK' => '3207140101120099',
                'nama' => 'NAYLA AZKIA RAHMAH', 'tempatLahir' => 'Ciamis', 'tglLahir' => '2013-07-21', 'jk' => 'P',
                'agama' => 'Islam', 'kewarganegaraan' => 'WNI', 'anakKe' => 2, 'jmlSaudara' => 1, 'hpSiswa' => '081355667788',
                'hobi' => "Tilawah Al-Qur'an", 'citaCita' => 'Ustadzah / Dosen', 'tingkat' => '6', 'rombel' => '6-A',
                'rombelMasuk' => '1-B', 'statusSiswa' => 'Lulus', 'tglMasuk' => '2020-07-13', 'jenisPendaftaran' => 'Siswa Baru',
                'asalSekolah' => 'RA Al-Ikhlas Maparah', 'npsnAsal' => '69738122', 'noIjazahAsal' => 'RA-20/0031',
                'noUnSebelumnya' => '3207-LU-2013-00912', 'tempatTinggal' => 'Bersama Orang Tua',
                'alamatJalan' => 'Dusun Sukamaju RT 003 RW 001', 'rt' => '003', 'rw' => '001', 'desa' => 'Maparah',
                'kecamatan' => 'Panjalu', 'kabupaten' => 'Kab. Ciamis', 'provinsi' => 'Jawa Barat', 'kodePos' => '46264',
                'jarak' => 'Kurang dari 5 km', 'transportasi' => 'Jalan Kaki', 'waktuTempuh' => '1 - 10 Menit',
                'ayahNama' => 'Ustadz Ridwan Kamil', 'ayahStatus' => 'Masih Hidup', 'ayahNik' => '3207140501800008',
                'ayahTtl' => 'Ciamis, 1980-01-05', 'ayahPendidikan' => 'S2', 'ayahPekerjaan' => 'PNS/TNI/Polri',
                'ayahPenghasilan' => 'Rp 5.000.000 - Rp 20.000.000', 'ibuNama' => 'Hj. Fatimah Zahra', 'ibuStatus' => 'Masih Hidup',
                'ibuNik' => '3207144404830009', 'ibuTtl' => 'Ciamis, 1983-04-04', 'ibuPendidikan' => 'D4/S1',
                'ibuPekerjaan' => 'Guru', 'ibuPenghasilan' => 'Rp 2.000.000 - Rp 4.999.999', 'ortuHp' => '081355667788',
                'statusPip' => 'Tidak', 'statusPkh' => 'Tidak', 'statusKks' => 'Tidak',
                'tb' => 151, 'bb' => 42, 'lingkarKepala' => 54, 'golDarah' => 'AB', 'disabilitas' => 'Tidak Ada',
                'keluarDariMadrasah' => 'Lulus', 'tglKeluar' => '2026-06-15',
                'noSeriIjazahMadrasah' => 'MI-26/02/00412', 'sekolahTujuan' => 'MTsN 1 Ciamis',
                'keteranganKeluar' => 'Lulus Ujian Madrasah Ibtidaiyah T.P. 2025/2026',
            ],
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sampleGurus(): array
    {
        return [
            [
                'gelarDepan' => 'H.', 'nama' => 'DEDE HIDAYAT', 'gelarBelakang' => 'S.Ag., M.Pd.I.',
                'nik' => '3207141504720001', 'noKK' => '3207140101000012', 'jk' => 'L', 'agama' => 'Islam',
                'tempatLahir' => 'Ciamis', 'tglLahir' => '1972-04-15', 'namaIbu' => 'Hj. Euis Rohayati',
                'statusNikah' => 'Kawin', 'hp' => '081234567890', 'email' => 'dede.hidayat@min1ciamis.sch.id',
                'alamat' => 'Dusun Sukamaju RT 001 RW 001 Desa Maparah Kec. Panjalu',
                'pegid' => '60708447172001', 'npk' => '7721450012001', 'nuptk' => '4747750652200012',
                'nip' => '197204151998031003', 'statusPegawai' => 'PNS', 'jenisPtk' => 'Kepala Madrasah',
                'golongan' => 'IV/b', 'tmtAwal' => '1998-03-01', 'tmtMadrasah' => '2022-01-03',
                'inpassing' => 'Sudah', 'statusAktif' => 'Aktif', 'noSk' => 'B-215/Kw.10/Kp.07.6/01/2022',
                'pendidikan' => 'S2', 'prodi' => 'Manajemen Pendidikan Islam', 'kampus' => 'UIN Sunan Gunung Djati Bandung',
                'tahunLulus' => '2014', 'statusSertifikasi' => 'Sudah Sertifikasi', 'nrg' => '100912345001',
                'noSerdik' => '102100912345', 'mapelSertifikasi' => 'Guru Kelas MI', 'tahunSertifikasi' => '2010',
                'satminkal' => 'Satminkal (Induk)', 'mapelUtama' => 'Guru Kelas MI', 'jtm' => 6,
                'tugasTambahan' => 'Kepala Madrasah', 'rekening' => 'BRI: 4012-01-001122-50-1', 'npwp' => '14.234.567.8-442.000',
            ],
            [
                'gelarDepan' => '', 'nama' => 'SITI AMINAH', 'gelarBelakang' => 'S.Pd.I.',
                'nik' => '3207145006870002', 'noKK' => '3207140101120034', 'jk' => 'P', 'agama' => 'Islam',
                'tempatLahir' => 'Ciamis', 'tglLahir' => '1987-06-10', 'namaIbu' => 'Tati Suryati',
                'statusNikah' => 'Kawin', 'hp' => '085223344559', 'email' => 'siti.aminah@min1ciamis.sch.id',
                'alamat' => 'Desa Maparah Kec. Panjalu Kab. Ciamis',
                'pegid' => '60708447187002', 'npk' => '8872340056011', 'nuptk' => '6942765666230102',
                'nip' => '198706102023212014', 'statusPegawai' => 'PPPK', 'jenisPtk' => 'Guru Kelas MI',
                'golongan' => 'IX', 'tmtAwal' => '2012-07-16', 'tmtMadrasah' => '2012-07-16',
                'inpassing' => 'Belum', 'statusAktif' => 'Aktif', 'noSk' => '1208/SJ/B.II.2/KP.00.1/08/2023',
                'pendidikan' => 'D4/S1', 'prodi' => 'Pendidikan Guru Madrasah Ibtidaiyah (PGMI)',
                'kampus' => 'IAIN Syekh Nurjati Cirebon', 'tahunLulus' => '2011', 'statusSertifikasi' => 'Sudah Sertifikasi',
                'nrg' => '170934567002', 'noSerdik' => '102170934567', 'mapelSertifikasi' => 'Guru Kelas MI',
                'tahunSertifikasi' => '2017', 'satminkal' => 'Satminkal (Induk)', 'mapelUtama' => 'Guru Kelas 1-A',
                'jtm' => 24, 'tugasTambahan' => 'Wali Kelas', 'rekening' => 'Mandiri: 131-00-9876543-2', 'npwp' => '48.123.456.7-442.000',
            ],
            [
                'gelarDepan' => '', 'nama' => 'AHMAD FAUZAN', 'gelarBelakang' => 'S.Pd.I., M.Pd.',
                'nik' => '3207142209910003', 'noKK' => '3207140101180055', 'jk' => 'L', 'agama' => 'Islam',
                'tempatLahir' => 'Ciamis', 'tglLahir' => '1991-09-22', 'namaIbu' => 'Mimin Aminah',
                'statusNikah' => 'Kawin', 'hp' => '082112233448', 'email' => 'ahmad.fauzan@min1ciamis.sch.id',
                'alamat' => 'Desa Ciomas Kec. Panjalu Kab. Ciamis',
                'pegid' => '60708447191003', 'npk' => '9913450078022', 'nuptk' => '5254769670130031',
                'nip' => '', 'statusPegawai' => 'GTT / Honorer', 'jenisPtk' => 'Guru Kelas MI',
                'golongan' => '-', 'tmtAwal' => '2017-07-17', 'tmtMadrasah' => '2017-07-17',
                'inpassing' => 'Belum', 'statusAktif' => 'Aktif', 'noSk' => '018/Mi.10.07.01/KP.01/07/2017',
                'pendidikan' => 'S2', 'prodi' => 'Pendidikan Dasar', 'kampus' => 'Universitas Pendidikan Indonesia',
                'tahunLulus' => '2019', 'statusSertifikasi' => 'Belum Sertifikasi', 'satminkal' => 'Satminkal (Induk)',
                'mapelUtama' => 'Guru Kelas 6-A', 'jtm' => 24, 'tugasTambahan' => 'Wali Kelas',
            ],
        ];
    }
}
