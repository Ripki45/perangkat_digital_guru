import {
    BookOpenCheck,
    BookOpenText,
    CalendarCheck,
    CalendarClock,
    CalendarDays,
    CalendarRange,
    ChartColumn,
    ClipboardCheck,
    ClipboardPen,
    Database,
    FileSearch,
    FileSpreadsheet,
    House,
    IdCard,
    LibraryBig,
    ListChecks,
    NotebookPen,
    Presentation,
    ScrollText,
    SquareCheckBig,
    Stamp,
    Target,
    Users,
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

export const moduleGroups: ModuleGroup[] = [
    {
        id: 'beranda-profil',
        title: 'Beranda & Profil',
        icon: House,
        items: [
            {
                slug: 'beranda',
                title: 'Beranda Mukodimah',
                href: '/beranda',
                icon: House,
                description:
                    'Ringkasan aktivitas, statistik kelas, dan pintasan cepat menuju seluruh perangkat digital Anda.',
                features: [],
                ready: true,
            },
            {
                slug: 'identitas',
                title: 'Identitas Guru & Madrasah',
                href: '/identitas',
                icon: IdCard,
                description:
                    'Data induk guru, madrasah, kepala madrasah, tahun ajaran, dan semester yang dipakai otomatis pada semua dokumen.',
                features: [],
                ready: true,
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
                slug: 'siswa',
                title: 'Master Data Siswa',
                href: '/siswa',
                icon: Users,
                description:
                    'Kelola data seluruh peserta didik: tambah satuan, edit, hapus, dan impor massal dari salinan Excel.',
                features: [],
                ready: true,
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
                title: 'Nilai & Analisis Hasil',
                href: placeholder('nilai-analisis'),
                icon: ChartColumn,
                description:
                    'Pengolahan nilai, analisis butir soal, dan laporan hasil belajar peserta didik.',
                features: [
                    'Input nilai formatif dan sumatif',
                    'Analisis ketuntasan belajar',
                    'Cetak leger dan rapor',
                ],
                ready: false,
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

export const modules: ModuleInfo[] = moduleGroups.flatMap((group) =>
    group.items.map((item) => ({
        ...item,
        groupId: group.id,
        groupTitle: group.title,
        groupIcon: group.icon,
    })),
);

export const moduleMap = new Map(modules.map((module) => [module.slug, module]));

export function findModule(slug: string): ModuleInfo | undefined {
    return moduleMap.get(slug);
}

export const quickModules: ModuleInfo[] = [
    'identitas',
    'legalitas',
    'siswa',
    'modul-ajar',
    'bank-soal',
    'e-book',
]
    .map((slug) => moduleMap.get(slug))
    .filter((module): module is ModuleInfo => Boolean(module));

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
