export const EMIS_68_HEADERS = [
    "ID",
    "STATUS_FOTO_3X4",
    "NIS_LOKAL",
    "NISN",
    "NIK",
    "NO_KK",
    "NAMA_LENGKAP",
    "TEMPAT_LAHIR",
    "TANGGAL_LAHIR",
    "JENIS_KELAMIN",
    "AGAMA",
    "KEWARGANEGARAAN",
    "ANAK_KE",
    "JML_SAUDARA",
    "NO_HP",
    "HOBI",
    "CITA_CITA",
    "TINGKAT_KELAS",
    "NAMA_ROMBEL",
    "STATUS_SISWA",
    "TGL_MASUK",
    "JENIS_PENDAFTARAN",
    "ASAL_SEKOLAH",
    "NPSN_ASAL",
    "NO_SERI_IJAZAH_ASAL",
    "NO_UN_SEBELUMNYA",
    "JENIS_TEMPAT_TINGGAL",
    "NSP_PESANTREN",
    "NAMA_PESANTREN",
    "ALAMAT_JALAN",
    "RT",
    "RW",
    "KELURAHAN_DESA",
    "KECAMATAN",
    "KABUPATEN_KOTA",
    "PROVINSI",
    "KODE_POS",
    "JARAK_KE_MADRASAH",
    "TRANSPORTASI",
    "WAKTU_TEMPUH",
    "NAMA_AYAH",
    "STATUS_AYAH",
    "NIK_AYAH",
    "TTL_AYAH",
    "PENDIDIKAN_AYAH",
    "PEKERJAAN_AYAH",
    "PENGHASILAN_AYAH",
    "NAMA_IBU",
    "STATUS_IBU",
    "NIK_IBU",
    "TTL_IBU",
    "PENDIDIKAN_IBU",
    "PEKERJAAN_IBU",
    "PENGHASILAN_IBU",
    "ORTU_HP",
    "NAMA_WALI",
    "NIK_WALI",
    "PEKERJAAN_WALI",
    "PENGHASILAN_WALI",
    "HUBUNGAN_WALI",
    "WALI_HP",
    "STATUS_PIP",
    "NO_KIP",
    "NO_REKENING_PIP",
    "NAMA_REKENING_PIP",
    "STATUS_PKH",
    "NO_PKH",
    "STATUS_KKS",
    "NO_KKS",
    "TINGGI_BADAN",
    "BERAT_BADAN",
    "LINGKAR_KEPALA",
    "GOLONGAN_DARAH",
    "KEBUTUHAN_KHUSUS",
    "KELUAR_DARI_MADRASAH",
    "TANGGAL_KELUAR",
    "NO_SERI_IJAZAH_MADRASAH",
    "SEKOLAH_TUJUAN",
    "KETERANGAN_KELUAR",
] as const;

export const RDM_SUBJECTS_MI = [
    "Al-Qur'an Hadis",
    "Akidah Akhlak",
    "Fikih",
    "Sejarah Kebudayaan Islam (SKI)",
    "Pendidikan Pancasila",
    "Bahasa Indonesia",
    "Bahasa Arab",
    "Matematika",
    "IPAS (Ilmu Pengetahuan Alam dan Sosial)",
    "Bahasa Inggris",
    "Seni Budaya (SBdP)",
    "PJOK",
    "Muatan Lokal (Bahasa Sunda / BTQ)",
] as const;

export const RDM_SEMESTERS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12] as const;

export const GURU_STATUS_PEGAWAI_LIST = [
    'PNS',
    'PPPK',
    'GTY',
    'GTT / Honorer',
    'Tenaga Kependidikan',
] as const;

export type EmisStudent = {
    id: number;
    foto: string;
    nisLokal: string;
    nisn: string;
    nik: string;
    noKK: string;
    nama: string;
    tempatLahir: string;
    tglLahir: string;
    jk: "L" | "P";
    agama: string;
    kewarganegaraan: string;
    anakKe: number;
    jmlSaudara: number;
    hpSiswa: string;
    hobi: string;
    citaCita: string;
    tingkat: string;
    rombel: string;
    rombelMasuk: string;
    statusSiswa: string;
    tglMasuk: string;
    jenisPendaftaran: string;
    asalSekolah: string;
    npsnAsal: string;
    noIjazahAsal: string;
    noUnSebelumnya: string;
    tempatTinggal: string;
    nspPesantren: string;
    namaPondok: string;
    alamatJalan: string;
    rt: string;
    rw: string;
    desa: string;
    kecamatan: string;
    kabupaten: string;
    provinsi: string;
    kodePos: string;
    jarak: string;
    transportasi: string;
    waktuTempuh: string;
    ayahNama: string;
    ayahStatus: string;
    ayahNik: string;
    ayahTtl: string;
    ayahPendidikan: string;
    ayahPekerjaan: string;
    ayahPenghasilan: string;
    ibuNama: string;
    ibuStatus: string;
    ibuNik: string;
    ibuTtl: string;
    ibuPendidikan: string;
    ibuPekerjaan: string;
    ibuPenghasilan: string;
    ortuHp: string;
    waliNama: string;
    waliNik: string;
    waliPekerjaan: string;
    waliPenghasilan: string;
    waliHubungan: string;
    waliHp: string;
    statusPip: string;
    noKip: string;
    rekPip: string;
    namaRekPip: string;
    statusPkh: string;
    noPkh: string;
    statusKks: string;
    noKks: string;
    tb: number;
    bb: number;
    lingkarKepala: number;
    golDarah: string;
    disabilitas: string;
    keluarDariMadrasah: string;
    tglKeluar: string;
    noSeriIjazahMadrasah: string;
    sekolahTujuan: string;
    keteranganKeluar: string;
};

export type EmisRombel = {
    id: number;
    tingkat: string;
    kode: string;
    wali: string;
};

export type EmisGuru = {
    id: number;
    gelarDepan: string;
    nama: string;
    gelarBelakang: string;
    nik: string;
    noKK: string;
    jk: 'L' | 'P';
    agama: string;
    tempatLahir: string;
    tglLahir: string;
    namaIbu: string;
    statusNikah: string;
    hp: string;
    email: string;
    alamat: string;
    pegid: string;
    npk: string;
    nuptk: string;
    nip: string;
    statusPegawai: string;
    jenisPtk: string;
    golongan: string;
    tmtAwal: string;
    tmtMadrasah: string;
    inpassing: string;
    statusAktif: string;
    noSk: string;
    pendidikan: string;
    prodi: string;
    kampus: string;
    tahunLulus: string;
    statusSertifikasi: string;
    nrg: string;
    noSerdik: string;
    mapelSertifikasi: string;
    tahunSertifikasi: string;
    satminkal: string;
    mapelUtama: string;
    jtm: number;
    tugasTambahan: string;
    rekening: string;
    npwp: string;
};

export type EmisMadrasahProfile = {
    namaMadrasah: string;
    tahunPelajaran: string;
    nsm: string;
    npsn: string;
    kemenagKab: string;
    alamat: string;
    tempatSurat: string;
    website: string;
    email: string;
    kepalaNama: string;
    kepalaNip: string;
    operatorNama: string;
    operatorHp: string;
    logoKiri: string;
    logoKanan: string;
};

export type RdmScoresMap = Record<
    string,
    Record<string, Record<string, number>>
>;

export function createEmptyEmisStudent(): Omit<EmisStudent, "id"> {
    return {
        foto: "",
        nisLokal: "",
        nisn: "",
        nik: "",
        noKK: "",
        nama: "",
        tempatLahir: "",
        tglLahir: "",
        jk: "L",
        agama: "Islam",
        kewarganegaraan: "WNI",
        anakKe: 1,
        jmlSaudara: 0,
        hpSiswa: "",
        hobi: "",
        citaCita: "",
        tingkat: "1",
        rombel: "1-A",
        rombelMasuk: "1-A",
        statusSiswa: "Aktif",
        tglMasuk: "",
        jenisPendaftaran: "Siswa Baru",
        asalSekolah: "",
        npsnAsal: "",
        noIjazahAsal: "",
        noUnSebelumnya: "",
        tempatTinggal: "Bersama Orang Tua",
        nspPesantren: "",
        namaPondok: "",
        alamatJalan: "",
        rt: "",
        rw: "",
        desa: "Maparah",
        kecamatan: "Panjalu",
        kabupaten: "Kab. Ciamis",
        provinsi: "Jawa Barat",
        kodePos: "46264",
        jarak: "Kurang dari 5 km",
        transportasi: "Jalan Kaki",
        waktuTempuh: "1 - 10 Menit",
        ayahNama: "",
        ayahStatus: "Masih Hidup",
        ayahNik: "",
        ayahTtl: "",
        ayahPendidikan: "SMA/Sederajat",
        ayahPekerjaan: "Petani/Peternak",
        ayahPenghasilan: "Rp 1.000.000 - Rp 1.999.999",
        ibuNama: "",
        ibuStatus: "Masih Hidup",
        ibuNik: "",
        ibuTtl: "",
        ibuPendidikan: "SMA/Sederajat",
        ibuPekerjaan: "Ibu Rumah Tangga",
        ibuPenghasilan: "Tidak Berpenghasilan",
        ortuHp: "",
        waliNama: "",
        waliNik: "",
        waliPekerjaan: "",
        waliPenghasilan: "",
        waliHubungan: "",
        waliHp: "",
        statusPip: "Tidak",
        noKip: "",
        rekPip: "",
        namaRekPip: "",
        statusPkh: "Tidak",
        noPkh: "",
        statusKks: "Tidak",
        noKks: "",
        tb: 0,
        bb: 0,
        lingkarKepala: 0,
        golDarah: "Tidak Tahu",
        disabilitas: "Tidak Ada",
        keluarDariMadrasah: "Masih Aktif",
        tglKeluar: "",
        noSeriIjazahMadrasah: "",
        sekolahTujuan: "",
        keteranganKeluar: "",
    };
}

export function validateEmisStudent(s: Partial<EmisStudent>): {
    isValid: boolean;
    missing: string[];
} {
    const missing: string[] = [];
    if (!s.nama) missing.push("Nama");
    if (!s.nisn || String(s.nisn).length !== 10)
        missing.push("NISN (10 digit)");
    if (!s.nik || String(s.nik).length !== 16) missing.push("NIK (16 digit)");
    if (!s.tempatLahir || !s.tglLahir) missing.push("TTL");
    if (!s.ibuNama) missing.push("Nama Ibu");
    if (!s.alamatJalan && !s.desa) missing.push("Alamat");
    return { isValid: missing.length === 0, missing };
}

export function formatNamaGelarGuru(
    g: Partial<EmisGuru> | null | undefined,
): string {
    if (!g) return "";
    const depan = (g.gelarDepan || "").trim();
    const nama = (g.nama || "").trim();
    const belakang = (g.gelarBelakang || "").trim();
    return `${depan ? depan + " " : ""}${nama}${belakang ? ", " + belakang : ""}`;
}

export function maskLast10Digits(val: string | null | undefined): string {
    const s = String(val || "").trim();
    if (!s) return "-";
    if (s.length <= 10) return "X".repeat(s.length);
    return s.slice(0, s.length - 10) + "XXXXXXXXXX";
}

export function createEmptyEmisGuru(): Omit<EmisGuru, 'id'> {
    return {
        gelarDepan: '',
        nama: '',
        gelarBelakang: '',
        nik: '',
        noKK: '',
        jk: 'L',
        agama: 'Islam',
        tempatLahir: '',
        tglLahir: '',
        namaIbu: '',
        statusNikah: 'Kawin',
        hp: '',
        email: '',
        alamat: '',
        pegid: '',
        npk: '',
        nuptk: '',
        nip: '',
        statusPegawai: 'PNS',
        jenisPtk: 'Guru Kelas MI',
        golongan: '-',
        tmtAwal: '',
        tmtMadrasah: '',
        inpassing: 'Belum',
        statusAktif: 'Aktif',
        noSk: '',
        pendidikan: 'D4/S1',
        prodi: 'PGMI',
        kampus: '',
        tahunLulus: '',
        statusSertifikasi: 'Belum Sertifikasi',
        nrg: '',
        noSerdik: '',
        mapelSertifikasi: '',
        tahunSertifikasi: '',
        satminkal: 'Satminkal (Induk)',
        mapelUtama: '',
        jtm: 24,
        tugasTambahan: 'Tidak Ada',
        rekening: '',
        npwp: '',
    };
}

export function calculateAge(
    birthDateString: string | null | undefined,
): number | null {
    if (!birthDateString) return null;
    const birthDate = new Date(birthDateString);
    if (Number.isNaN(birthDate.getTime())) return null;
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
}

export function getStudentSemesterAvg(
    scoresMap: RdmScoresMap,
    studentId: string | number,
    smt: string | number,
): number | null {
    const rec = scoresMap[String(studentId)];
    if (!rec) return null;
    let sum = 0;
    let count = 0;
    Object.values(rec).forEach((subjMap) => {
        const raw = subjMap?.[String(smt)];
        if (raw === undefined || raw === null) return;
        const n = Number(raw);
        if (!Number.isNaN(n) && n >= 0) {
            sum += n;
            count++;
        }
    });
    return count > 0 ? Number((sum / count).toFixed(1)) : null;
}

