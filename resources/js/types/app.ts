export type TeacherProfile = {
    nama_guru: string;
    nip: string | null;
    nuptk: string | null;
    jabatan: string | null;
    mata_pelajaran: string | null;
    kelas_wali: string | null;
    tahun_ajaran: string;
    semester: 'Ganjil' | 'Genap' | string;
    motto: string | null;
    nama_madrasah: string;
    npsn: string | null;
    nsm?: string | null;
    kemenag_kab?: string | null;
    tempat_surat?: string | null;
    alamat: string | null;
    desa: string | null;
    kecamatan: string | null;
    kabupaten: string | null;
    provinsi: string | null;
    kode_pos: string | null;
    telepon: string | null;
    email_madrasah: string | null;
    website: string | null;
    nama_kepala_madrasah: string | null;
    nip_kepala_madrasah: string | null;
    operator_nama?: string | null;
    operator_hp?: string | null;
    logo_kiri?: string | null;
    logo_kanan?: string | null;
};

export type LegalDocumentType = 'logo' | 'ttd_kepala' | 'ttd_guru' | 'stempel';

export type LegalDocumentPayload = {
    id: number;
    type: LegalDocumentType;
    label: string;
    url: string;
    original_name: string;
    mime_type: string | null;
    file_size: number;
    uploaded_at: string | null;
};

export type DocumentTypeCard = {
    type: LegalDocumentType;
    label: string;
    document: LegalDocumentPayload | null;
};

export type Student = {
    id: number;
    nama: string;
    nisn: string | null;
    nis: string | null;
    jenis_kelamin: 'L' | 'P';
    kelas: string;
    tempat_lahir: string | null;
    tanggal_lahir: string | null;
    nama_wali: string | null;
    no_hp: string | null;
    alamat: string | null;
    status: 'Aktif' | 'Lulus' | 'Pindah' | 'Keluar' | string;
    catatan: string | null;
    created_at: string | null;
};

export type StudentStats = {
    total: number;
    male: number;
    female: number;
    classes: number;
};

export type BerandaStats = {
    students: number;
    male: number;
    female: number;
    classes: number;
    gurus?: number;
    documents: number;
    documents_required: number;
    profile_completeness: number;
};

export type RecentStudent = {
    id: number;
    nama: string;
    nisn: string | null;
    kelas: string;
    jenis_kelamin: 'L' | 'P';
    status: string;
};

export type ClassDistributionItem = {
    kelas: string;
    total: number;
};

export type DocumentCheckItem = {
    type: LegalDocumentType;
    label: string;
    uploaded: boolean;
};
