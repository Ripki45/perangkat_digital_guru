import {
    ArrowRightLeft,
    BookOpenCheck,
    BookOpenText,
    Building2,
    CalendarCheck,
    CalendarClock,
    CalendarDays,
    CalendarRange,
    ChartColumn,
    ClipboardCheck,
    ClipboardList,
    ClipboardPen,
    Database,
    FileSearch,
    FileSpreadsheet,
    FolderOpen,
    GraduationCap,
    House,
    LayoutGrid,
    LibraryBig,
    ListChecks,
    NotebookPen,
    Presentation,
    Printer,
    ScanSearch,
    ScrollText,
    Sparkles,
    SquareCheckBig,
    Stamp,
    Target,
    UsersRound,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

export type ModuleItem = {
    slug: string;
    title: string;
    href: string;
    icon: LucideIcon;
    description: string;
    features: string[];
    ready: boolean;
    roles?: string[];
};

export type ModuleGroup = {
    id: string;
    title: string;
    icon: LucideIcon;
    items: ModuleItem[];
};

export type ModuleInfo = ModuleItem & {
    groupId: string;
    groupTitle: string;
    groupIcon: LucideIcon;
};

const placeholder = (slug: string) => `/modul/${slug}`;

export const berandaNavItem: ModuleItem = {
    slug: 'beranda',
    title: 'Beranda & Mukodimah',
    href: '/beranda',
    icon: House,
    description:
        'Ringkasan aktivitas, statistik kelas, dan pintasan cepat menuju seluruh perangkat digital Anda.',
    features: [],
    ready: true,
};

export const moduleGroups: ModuleGroup[] = [
    {
        id: 'biodata',
        title: 'BIODATA',
        icon: FolderOpen,
        items: [
            {
                slug: 'data-madrasah',
                title: 'Data Madrasah',
                href: '/data-madrasah',
                icon: GraduationCap,
                description:
                    'Data lembaga madrasah, NSM/NPSN, alamat, kepala madrasah, operator EMIS, dan logo kop surat resmi.',
                features: [],
                ready: true,
            },
            {
                slug: 'identitas',
                title: 'Identitas dan Profil Guru',
                href: '/identitas',
                icon: Building2,
                description:
                    'Biodata induk guru, NIP/NUPTK, jabatan, tahun ajaran, semester, motto, serta keamanan akun login.',
                features: [],
                ready: true,
            },
            {
                slug: 'mapel-rombel',
                title: 'Data Mapel & Rombel',
                href: '/mapel-rombel',
                icon: ClipboardList,
                description:
                    'Pengelolaan mata pelajaran Kurikulum MI (KKM/JTM), rombongan belajar Kelas 1–6, wali kelas, dan kenaikan kelas.',
                features: [],
                ready: true,
                roles: ['superadmin', 'wali_kelas', 'guru_mapel'],
            },
            {
                slug: 'siswa',
                title: 'Data Siswa dan Kelas',
                href: '/siswa',
                icon: LayoutGrid,
                description:
                    'Kelola data induk peserta didik berstandar EMIS 4.0: form 6 langkah, pas foto 3x4, cetak Buku Induk, KTS, dan impor massal.',
                features: [],
                ready: true,
                roles: ['superadmin', 'wali_kelas'],
            },
        ],
    },
    {
        id: 'kelembagaan-ai',
        title: 'Kelembagaan & AI',
        icon: Sparkles,
        items: [
            {
                slug: 'gtk',
                title: 'Data Guru & GTK EMIS',
                href: '/gtk',
                icon: UsersRound,
                description:
                    'Kelola data Pendidik dan Tenaga Kependidikan berstandar EMIS 4.0: NPK, NUPTK, Sertifikasi, JTM, dan rekap kepegawaian.',
                features: [],
                ready: true,
                roles: ['superadmin'],
            },
            {
                slug: 'legalitas',
                title: 'Legalitas Logo/TTD/Stempel',
                href: '/legalitas',
                icon: Stamp,
                description:
                    'Pusat berkas legalitas: logo madrasah, tanda tangan guru, tanda tangan kepala madrasah, dan stempel resmi.',
                features: [],
                ready: true,
                roles: ['superadmin'],
            },
            {
                slug: 'asisten-ai',
                title: 'Asisten AI & Vektor',
                href: '/asisten-ai',
                icon: Sparkles,
                description:
                    'Asisten Laravel AI SDK, pencarian semantik vektor siswa & GTK, sinkronisasi antrean Queue::route, serta API JSON:API v1.1.',
                features: [],
                ready: true,
            },
        ],
    },
    {
        id: 'perencanaan',
        title: 'Perencanaan',
        icon: CalendarRange,
        items: [
            {
                slug: 'kaldik',
                title: 'Kaldik',
                href: placeholder('kaldik'),
                icon: CalendarDays,
                description:
                    'Kalender Pendidikan madrasah sebagai acuan penyusunan agenda pembelajaran sepanjang tahun ajaran.',
                features: [
                    'Penanda hari efektif, libur nasional, dan libur madrasah',
                    'Rekap minggu efektif per semester',
                    'Cetak kalender A4/F4 dengan identitas madrasah',
                ],
                ready: false,
            },
            {
                slug: 'heb',
                title: 'HEB',
                href: placeholder('heb'),
                icon: CalendarCheck,
                description:
                    'Hari Efektif Belajar untuk menghitung ketersediaan waktu pembelajaran setiap bulan.',
                features: [
                    'Perhitungan otomatis hari efektif per bulan',
                    'Rekap per kelas dan mata pelajaran',
                    'Dasar penyusunan Prota dan Promes',
                ],
                ready: false,
            },
            {
                slug: 'jeb',
                title: 'JEB',
                href: placeholder('jeb'),
                icon: ListChecks,
                description:
                    'Jurnal Efektif Belajar untuk mencatat realisasi kegiatan pembelajaran yang telah terlaksana.',
                features: [
                    'Pencatatan realisasi pembelajaran harian',
                    'Perbandingan rencana dan realisasi',
                    'Rekap capaian per semester',
                ],
                ready: false,
            },
            {
                slug: 'rpe',
                title: 'RPE',
                href: placeholder('rpe'),
                icon: ScrollText,
                description:
                    'Rencana Pekan Efektif yang memetakan alokasi waktu pembelajaran dalam setiap pekan.',
                features: [
                    'Peta pekan efektif dan tidak efektif',
                    'Alokasi jam per mata pelajaran',
                    'Siap dicetak sebagai lampiran perencanaan',
                ],
                ready: false,
            },
            {
                slug: 'jadwal',
                title: 'Jadwal Pelajaran',
                href: placeholder('jadwal'),
                icon: CalendarClock,
                description:
                    'Jadwal pelajaran mingguan yang rapi, mudah diubah, dan siap dipasang di kelas.',
                features: [
                    'Jadwal per kelas dan rombongan belajar',
                    'Pengaturan jam ke- dan istirahat',
                    'Ekspor dan cetak format A4/F4',
                ],
                ready: false,
            },
        ],
    },
    {
        id: 'perangkat-ajar',
        title: 'Perangkat Ajar',
        icon: BookOpenCheck,
        items: [
            {
                slug: 'cp-tp',
                title: 'CP & TP/ATP',
                href: placeholder('cp-tp'),
                icon: Target,
                description:
                    'Capaian Pembelajaran, Tujuan Pembelajaran, dan Alur Tujuan Pembelajaran Kurikulum Merdeka.',
                features: [
                    'Rujukan CP resmi per fase dan mata pelajaran',
                    'Penyusunan TP yang terukur',
                    'Alur Tujuan Pembelajaran siap cetak',
                ],
                ready: false,
            },
            {
                slug: 'prota-promes',
                title: 'Prota & Promes',
                href: placeholder('prota-promes'),
                icon: FileSpreadsheet,
                description:
                    'Program Tahunan dan Program Semester yang tersusun otomatis dari alokasi waktu efektif.',
                features: [
                    'Distribusi materi per semester',
                    'Sinkron dengan Kaldik dan HEB',
                    'Format tabel resmi siap cetak',
                ],
                ready: false,
            },
            {
                slug: 'modul-ajar',
                title: 'Modul Ajar/RPP',
                href: placeholder('modul-ajar'),
                icon: BookOpenText,
                description:
                    'Modul Ajar dan RPP berbasis Panca Cinta lengkap dengan langkah pembelajaran dan asesmen.',
                features: [
                    'Template Modul Ajar Kurikulum Merdeka',
                    'Integrasi nilai Panca Cinta',
                    'Cetak A4/F4 dengan kop madrasah',
                ],
                ready: false,
            },
            {
                slug: 'kktp',
                title: 'KKTP',
                href: placeholder('kktp'),
                icon: SquareCheckBig,
                description:
                    'Kriteria Ketercapaian Tujuan Pembelajaran untuk memantau perkembangan setiap peserta didik.',
                features: [
                    'Rubrik ketercapaian per tujuan pembelajaran',
                    'Deskripsi capaian otomatis',
                    'Rekap per siswa dan per kelas',
                ],
                ready: false,
            },
            {
                slug: 'lkpd',
                title: 'LKPD',
                href: placeholder('lkpd'),
                icon: ClipboardPen,
                description:
                    'Lembar Kerja Peserta Didik yang menarik, kontekstual, dan siap dibagikan.',
                features: [
                    'Pustaka template LKPD',
                    'Aktivitas berbasis proyek dan diskusi',
                    'Versi cetak dan versi digital',
                ],
                ready: false,
            },
            {
                slug: 'jurnal-mengajar',
                title: 'Jurnal Mengajar',
                href: placeholder('jurnal-mengajar'),
                icon: NotebookPen,
                description:
                    'Catatan harian pelaksanaan pembelajaran, kehadiran, dan refleksi guru.',
                features: [
                    'Jurnal per pertemuan',
                    'Catatan kehadiran dan refleksi',
                    'Rekap otomatis per bulan',
                ],
                ready: false,
            },
        ],
    },
    {
        id: 'kesiswaan-asesmen',
        title: 'Kesiswaan & Asesmen',
        icon: UsersRound,
        items: [
            {
                slug: 'mutasi',
                title: 'Mutasi & Surat Pindah',
                href: '/mutasi',
                icon: ArrowRightLeft,
                description:
                    'Pencatatan siswa mutasi masuk/keluar, alumni lulus, ekspor XLSX, dan cetak Surat Keterangan Pindah Madrasah.',
                features: [],
                ready: true,
                roles: ['superadmin', 'wali_kelas'],
            },
            {
                slug: 'cek-nisn',
                title: 'Cek Data Siswa (NISN)',
                href: '/cek-nisn',
                icon: ScanSearch,
                description:
                    'Pencarian biodata EMIS 4.0, rombel, bantuan sosial, dan rekap nilai RDM menggunakan 10 digit NISN.',
                features: [],
                ready: true,
            },
            {
                slug: 'rekap-emis',
                title: 'Rekap & Cetak Massal',
                href: '/rekap-emis',
                icon: Printer,
                description:
                    'Statistik grafik rombel & usia MI, validasi EMIS, cetak massal KTS/Buku Induk/Nominatif/Transkrip, dan backup JSON.',
                features: [],
                ready: true,
                roles: ['superadmin', 'wali_kelas'],
            },
            {
                slug: 'daftar-hadir',
                title: 'Daftar Hadir & Rekap Bulanan',
                href: placeholder('daftar-hadir'),
                icon: ClipboardCheck,
                description:
                    'Daftar hadir harian dan rekap kehadiran bulanan yang terhubung dengan master data siswa.',
                features: [
                    'Absensi harian per kelas',
                    'Rekap hadir, sakit, izin, dan alpa',
                    'Cetak rekap bulanan',
                ],
                ready: false,
            },
            {
                slug: 'bank-soal',
                title: 'Bank Soal',
                href: placeholder('bank-soal'),
                icon: Database,
                description:
                    'Penyimpanan soal terstruktur berdasarkan mata pelajaran, materi, dan level kognitif.',
                features: [
                    'Kategorisasi soal per mata pelajaran',
                    'Penanda level kognitif dan kunci jawaban',
                    'Pencarian dan pemakaian ulang soal',
                ],
                ready: false,
            },
            {
                slug: 'soal-kisi',
                title: 'Soal & Kisi-Kisi',
                href: placeholder('soal-kisi'),
                icon: FileSearch,
                description:
                    'Penyusunan kisi-kisi dan naskah soal yang selaras dengan tujuan pembelajaran.',
                features: [
                    'Matriks kisi-kisi otomatis',
                    'Naskah soal rapi siap cetak',
                    'Analisis keterwakilan materi',
                ],
                ready: false,
            },
            {
                slug: 'nilai-analisis',
                title: 'Nilai RDM & Analisis Hasil',
                href: '/rdm',
                icon: ChartColumn,
                description:
                    'Pengolahan nilai Rapor Digital Madrasah (RDM) 13 Mapel + IPAS × 12 Semester, impor leger Excel, dan cetak transkrip resmi.',
                features: [
                    'Input & edit nilai 13 mapel MI per semester (S1-S12)',
                    'Impor & ekspor leger Excel berstandar RDM',
                    'Cetak transkrip nilai satuan maupun massal',
                ],
                ready: true,
                roles: ['superadmin', 'wali_kelas', 'guru_mapel'],
            },
        ],
    },
    {
        id: 'media-panduan',
        title: 'Media & Panduan',
        icon: Presentation,
        items: [
            {
                slug: 'slide',
                title: 'Presentasi Slide Interaktif',
                href: placeholder('slide'),
                icon: Presentation,
                description:
                    'Media presentasi interaktif untuk pembelajaran di kelas, lengkap dengan ilustrasi dan aktivitas.',
                features: [
                    'Pustaka template presentasi',
                    'Sisipan gambar, kuis, dan refleksi',
                    'Tampilan kelas dan proyektor',
                ],
                ready: false,
            },
            {
                slug: 'e-book',
                title: 'E-Book Panduan',
                href: placeholder('e-book'),
                icon: LibraryBig,
                description:
                    'Buku panduan digital penggunaan aplikasi dan inspirasi pembelajaran Kurikulum Merdeka.',
                features: [
                    'Panduan langkah demi langkah',
                    'Tips implementasi Panca Cinta',
                    'Dapat dibaca dari perangkat mana pun',
                ],
                ready: false,
            },
        ],
    },
];

export const modules: ModuleInfo[] = [
    {
        ...berandaNavItem,
        groupId: 'home',
        groupTitle: 'Beranda & Mukodimah',
        groupIcon: House,
    },
    ...moduleGroups.flatMap((group) =>
        group.items.map((item) => ({
            ...item,
            groupId: group.id,
            groupTitle: group.title,
            groupIcon: group.icon,
        })),
    ),
];

export const moduleMap = new Map(
    modules.map((module) => [module.slug, module]),
);

export function findModule(slug: string): ModuleInfo | undefined {
    return moduleMap.get(slug);
}

export function canAccessModule(
    module: Pick<ModuleItem, 'roles'>,
    role?: string | null,
): boolean {
    if (!module.roles || module.roles.length === 0) {
        return true;
    }
    if (!role) {
        return true;
    }
    return module.roles.includes(role);
}

const quickSlugs = [
    'data-madrasah',
    'identitas',
    'mapel-rombel',
    'siswa',
    'nilai-analisis',
    'rekap-emis',
    'cek-nisn',
    'gtk',
    'asisten-ai',
];

export const quickModules: ModuleInfo[] = quickSlugs
    .map((slug) => moduleMap.get(slug))
    .filter((module): module is ModuleInfo => Boolean(module));

export function getQuickModulesForRole(role?: string | null): ModuleInfo[] {
    return quickModules
        .filter((module) => canAccessModule(module, role))
        .slice(0, 6);
}

export const pancaCinta = [
    {
        title: 'Cinta Allah & Rasul',
        description: 'Menumbuhkan kesadaran ibadah dan akhlak mulia.',
    },
    {
        title: 'Cinta Ilmu',
        description: 'Membangun rasa ingin tahu dan semangat belajar.',
    },
    {
        title: 'Cinta Lingkungan',
        description: 'Merawat alam sebagai amanah dan sumber belajar.',
    },
    {
        title: 'Cinta Diri & Sesama',
        description: 'Menghargai diri, keluarga, dan masyarakat.',
    },
    {
        title: 'Cinta Tanah Air',
        description: 'Menjaga persatuan dan kebanggaan sebagai bangsa.',
    },
];
