<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDownUp,
    BookMarked,
    BookOpen,
    Check,
    ChevronLeft,
    ChevronRight,
    ClipboardPaste,
    Database,
    Download,
    FileSpreadsheet,
    IdCard,
    LoaderCircle,
    Pencil,
    Plus,
    Printer,
    Search,
    Trash,
    Upload,
    UserRound,
    Users,
    UsersRound,
    X,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import * as XLSX from 'xlsx';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import StatCard from '@/components/app/StatCard.vue';
import StudentEmisFormModal from '@/components/sim-emis/StudentEmisFormModal.vue';
import StudentEmisModals from '@/components/sim-emis/StudentEmisModals.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { classLabel, formatDate } from '@/lib/format';
import type { Student, StudentStats } from '@/types';
import { EMIS_68_HEADERS, validateEmisStudent } from '@/types/emis';
import type {
    EmisMadrasahProfile,
    EmisRombel,
    EmisStudent,
} from '@/types/emis';

type StudentRow = Student & EmisStudent;

const props = defineProps<{
    students: StudentRow[];
    classes: string[];
    rombels: EmisRombel[];
    stats: StudentStats;
    madrasahProfile: EmisMadrasahProfile;
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'Master Data Siswa (EMIS 4.0)', href: '/siswa' },
        ],
    }),
});

/* ---------------------------------- filters --------------------------------- */

const search = ref('');
const classFilter = ref('all');
const genderFilter = ref('all');
const statusFilter = ref('all');
const emisFilter = ref('all');
const sortBy = ref<'nama' | 'kelas' | 'nisn'>('kelas');
const sortDir = ref<'asc' | 'desc'>('asc');
const perPage = ref(10);
const page = ref(1);

const allRombelCodes = computed(() => {
    const set = new Set(props.rombels.map((r) => r.kode));
    props.students.forEach((s) => {
        if (s.kelas) set.add(s.kelas);
    });
    return Array.from(set).sort((a, b) =>
        a.localeCompare(b, 'id', { numeric: true }),
    );
});

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    const rows = props.students.filter((student) => {
        if (
            classFilter.value !== 'all' &&
            student.kelas !== classFilter.value
        ) {
            return false;
        }

        if (
            genderFilter.value !== 'all' &&
            student.jenis_kelamin !== genderFilter.value
        ) {
            return false;
        }

        if (
            statusFilter.value !== 'all' &&
            student.status !== statusFilter.value
        ) {
            return false;
        }

        if (emisFilter.value !== 'all') {
            const v = validateEmisStudent(student);
            if (emisFilter.value === 'Lengkap' && !v.isValid) return false;
            if (emisFilter.value === 'Belum' && v.isValid) return false;
        }

        if (!term) {
            return true;
        }

        return [
            student.nama,
            student.nisn,
            student.nis,
            student.nik,
            student.nama_wali,
            student.noSeriIjazahMadrasah,
        ]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(term));
    });

    return [...rows].sort((a, b) => {
        const factor = sortDir.value === 'asc' ? 1 : -1;

        if (sortBy.value === 'kelas') {
            const kelasCompare = a.kelas.localeCompare(b.kelas, 'id', {
                numeric: true,
            });

            return (
                (kelasCompare !== 0
                    ? kelasCompare
                    : a.nama.localeCompare(b.nama, 'id')) * factor
            );
        }

        const left = String(a[sortBy.value] ?? '');
        const right = String(b[sortBy.value] ?? '');

        return left.localeCompare(right, 'id', { numeric: true }) * factor;
    });
});

const totalPages = computed(() =>
    Math.max(Math.ceil(filtered.value.length / perPage.value), 1),
);

const paginated = computed(() => {
    const start = (page.value - 1) * perPage.value;
    return filtered.value.slice(start, start + perPage.value);
});

const rangeStart = computed(() =>
    filtered.value.length === 0 ? 0 : (page.value - 1) * perPage.value + 1,
);
const rangeEnd = computed(() =>
    Math.min(page.value * perPage.value, filtered.value.length),
);

const activeFilters = computed(
    () =>
        Number(search.value.trim() !== '') +
        Number(classFilter.value !== 'all') +
        Number(genderFilter.value !== 'all') +
        Number(statusFilter.value !== 'all') +
        Number(emisFilter.value !== 'all'),
);

watch(
    [search, classFilter, genderFilter, statusFilter, emisFilter, perPage],
    () => {
        page.value = 1;
    },
);

watch(totalPages, (value) => {
    if (page.value > value) {
        page.value = value;
    }
});

const resetFilters = () => {
    search.value = '';
    classFilter.value = 'all';
    genderFilter.value = 'all';
    statusFilter.value = 'all';
    emisFilter.value = 'all';
    page.value = 1;
};

const toggleSort = (column: 'nama' | 'kelas' | 'nisn') => {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        return;
    }

    sortBy.value = column;
    sortDir.value = 'asc';
};

/* ----------------------------- EMIS 4.0 modals ------------------------------ */

const formOpen = ref(false);
const defaultFormTab = ref(1);
const editing = ref<StudentRow | null>(null);
const deleting = ref<StudentRow | null>(null);
const deletingProcessing = ref(false);
const biodataStudent = ref<StudentRow | null>(null);
const ktsStudent = ref<StudentRow | null>(null);
const bulkIndukOpen = ref(false);
const kamusOpen = ref(false);
const loadingSample = ref(false);
const xlsxInputRef = ref<HTMLInputElement | null>(null);

const openCreate = (tab = 1) => {
    editing.value = null;
    defaultFormTab.value = tab;
    formOpen.value = true;
};

const openEdit = (student: StudentRow) => {
    editing.value = student;
    defaultFormTab.value = 1;
    formOpen.value = true;
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('action') === 'mutasi-masuk') {
        openCreate(2);
    }
});

const confirmDelete = () => {
    if (!deleting.value) {
        return;
    }

    deletingProcessing.value = true;

    router.delete(`/siswa/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingProcessing.value = false;
            deleting.value = null;
        },
    });
};

const loadSampleData = () => {
    loadingSample.value = true;
    router.post(
        '/siswa/sampel-emis',
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                loadingSample.value = false;
            },
        },
    );
};

/* --------------------------- XLSX 68-Col Import/Export ---------------------- */

const exportExcelXLSX = () => {
    const rows = props.students.map((s) => ({
        ID: s.id,
        STATUS_FOTO_3X4: s.foto ? 'ADA_FOTO' : 'KOSONG',
        NIS_LOKAL: s.nisLokal || '',
        NISN: s.nisn || '',
        NIK: s.nik || '',
        NO_KK: s.noKK || '',
        NAMA_LENGKAP: s.nama || '',
        TEMPAT_LAHIR: s.tempatLahir || '',
        TANGGAL_LAHIR: s.tglLahir || '',
        JENIS_KELAMIN: s.jk || '',
        AGAMA: s.agama || '',
        KEWARGANEGARAAN: s.kewarganegaraan || '',
        ANAK_KE: s.anakKe || 1,
        JML_SAUDARA: s.jmlSaudara || 0,
        NO_HP: s.hpSiswa || '',
        HOBI: s.hobi || '',
        CITA_CITA: s.citaCita || '',
        TINGKAT_KELAS: s.tingkat || '',
        NAMA_ROMBEL: s.rombel || '',
        STATUS_SISWA: s.statusSiswa || '',
        TGL_MASUK: s.tglMasuk || '',
        JENIS_PENDAFTARAN: s.jenisPendaftaran || '',
        ASAL_SEKOLAH: s.asalSekolah || '',
        NPSN_ASAL: s.npsnAsal || '',
        NO_SERI_IJAZAH_ASAL: s.noIjazahAsal || '',
        NO_UN_SEBELUMNYA: s.noUnSebelumnya || '',
        JENIS_TEMPAT_TINGGAL: s.tempatTinggal || '',
        NSP_PESANTREN: s.nspPesantren || '',
        NAMA_PESANTREN: s.namaPondok || '',
        ALAMAT_JALAN: s.alamatJalan || '',
        RT: s.rt || '',
        RW: s.rw || '',
        KELURAHAN_DESA: s.desa || '',
        KECAMATAN: s.kecamatan || '',
        KABUPATEN_KOTA: s.kabupaten || '',
        PROVINSI: s.provinsi || '',
        KODE_POS: s.kodePos || '',
        JARAK_KE_MADRASAH: s.jarak || '',
        TRANSPORTASI: s.transportasi || '',
        WAKTU_TEMPUH: s.waktuTempuh || '',
        NAMA_AYAH: s.ayahNama || '',
        STATUS_AYAH: s.ayahStatus || '',
        NIK_AYAH: s.ayahNik || '',
        TTL_AYAH: s.ayahTtl || '',
        PENDIDIKAN_AYAH: s.ayahPendidikan || '',
        PEKERJAAN_AYAH: s.ayahPekerjaan || '',
        PENGHASILAN_AYAH: s.ayahPenghasilan || '',
        NAMA_IBU: s.ibuNama || '',
        STATUS_IBU: s.ibuStatus || '',
        NIK_IBU: s.ibuNik || '',
        TTL_IBU: s.ibuTtl || '',
        PENDIDIKAN_IBU: s.ibuPendidikan || '',
        PEKERJAAN_IBU: s.ibuPekerjaan || '',
        PENGHASILAN_IBU: s.ibuPenghasilan || '',
        ORTU_HP: s.ortuHp || '',
        NAMA_WALI: s.waliNama || '',
        NIK_WALI: s.waliNik || '',
        PEKERJAAN_WALI: s.waliPekerjaan || '',
        PENGHASILAN_WALI: s.waliPenghasilan || '',
        HUBUNGAN_WALI: s.waliHubungan || '',
        WALI_HP: s.waliHp || '',
        STATUS_PIP: s.statusPip || '',
        NO_KIP: s.noKip || '',
        NO_REKENING_PIP: s.rekPip || '',
        NAMA_REKENING_PIP: s.namaRekPip || '',
        STATUS_PKH: s.statusPkh || '',
        NO_PKH: s.noPkh || '',
        STATUS_KKS: s.statusKks || '',
        NO_KKS: s.noKks || '',
        TINGGI_BADAN: s.tb || '',
        BERAT_BADAN: s.bb || '',
        LINGKAR_KEPALA: s.lingkarKepala || '',
        GOLONGAN_DARAH: s.golDarah || '',
        KEBUTUHAN_KHUSUS: s.disabilitas || '',
        KELUAR_DARI_MADRASAH: s.keluarDariMadrasah || '',
        TANGGAL_KELUAR: s.tglKeluar || '',
        NO_SERI_IJAZAH_MADRASAH: s.noSeriIjazahMadrasah || '',
        SEKOLAH_TUJUAN: s.sekolahTujuan || '',
        KETERANGAN_KELUAR: s.keteranganKeluar || '',
    }));

    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'EMIS_Siswa_MI');
    XLSX.writeFile(wb, 'Data_Siswa_EMIS4.xlsx');
};

const downloadBlankTemplate = () => {
    const emptyRow: Record<string, string> = {};
    EMIS_68_HEADERS.forEach((h) => {
        emptyRow[h] = '';
    });
    emptyRow.TINGKAT_KELAS = '1';
    emptyRow.NAMA_ROMBEL = '1-A';
    emptyRow.STATUS_SISWA = 'Aktif';
    const ws = XLSX.utils.json_to_sheet([emptyRow]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Template_EMIS_MI');
    XLSX.writeFile(wb, 'Template_Kosong_Siswa_EMIS4.xlsx');
};

const handleImportXlsx = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const wb = XLSX.read(e.target?.result, { type: 'array' });
            const rows = XLSX.utils.sheet_to_json<Record<string, unknown>>(
                wb.Sheets[wb.SheetNames[0]],
                { defval: '' },
            );

            const studentsPayload = rows
                .map((r) => {
                    const nama = String(r.NAMA_LENGKAP || '')
                        .trim()
                        .toUpperCase();
                    if (!nama) return null;
                    const rawTingkat = String(r.TINGKAT_KELAS || '1').trim();
                    const tingkat = ['1', '2', '3', '4', '5', '6'].includes(
                        rawTingkat,
                    )
                        ? rawTingkat
                        : '1';
                    return {
                        nisLokal: String(r.NIS_LOKAL || '').trim(),
                        nisn: String(r.NISN || '').trim(),
                        nik: String(r.NIK || '').trim(),
                        noKK: String(r.NO_KK || '').trim(),
                        nama,
                        tempatLahir: String(r.TEMPAT_LAHIR || '').trim(),
                        tglLahir: String(r.TANGGAL_LAHIR || '').trim(),
                        jk: String(r.JENIS_KELAMIN || 'L')
                            .trim()
                            .toUpperCase()
                            .startsWith('P')
                            ? 'P'
                            : 'L',
                        agama: String(r.AGAMA || 'Islam').trim(),
                        kewarganegaraan: String(
                            r.KEWARGANEGARAAN || 'WNI',
                        ).trim(),
                        anakKe: Number(r.ANAK_KE || 1),
                        jmlSaudara: Number(r.JML_SAUDARA || 0),
                        hpSiswa: String(r.NO_HP || '').trim(),
                        hobi: String(r.HOBI || '').trim(),
                        citaCita: String(r.CITA_CITA || '').trim(),
                        tingkat,
                        rombel: String(r.NAMA_ROMBEL || `${tingkat}-A`).trim(),
                        rombelMasuk: String(
                            r.NAMA_ROMBEL || `${tingkat}-A`,
                        ).trim(),
                        statusSiswa: String(r.STATUS_SISWA || 'Aktif').trim(),
                        tglMasuk: String(r.TGL_MASUK || '').trim(),
                        jenisPendaftaran: String(
                            r.JENIS_PENDAFTARAN || 'Siswa Baru',
                        ).trim(),
                        asalSekolah: String(r.ASAL_SEKOLAH || '').trim(),
                        npsnAsal: String(r.NPSN_ASAL || '').trim(),
                        noIjazahAsal: String(
                            r.NO_SERI_IJAZAH_ASAL || '',
                        ).trim(),
                        noUnSebelumnya: String(r.NO_UN_SEBELUMNYA || '').trim(),
                        tempatTinggal: String(
                            r.JENIS_TEMPAT_TINGGAL || 'Bersama Orang Tua',
                        ).trim(),
                        nspPesantren: String(r.NSP_PESANTREN || '').trim(),
                        namaPondok: String(r.NAMA_PESANTREN || '').trim(),
                        alamatJalan: String(r.ALAMAT_JALAN || '').trim(),
                        rt: String(r.RT || '').trim(),
                        rw: String(r.RW || '').trim(),
                        desa: String(r.KELURAHAN_DESA || '').trim(),
                        kecamatan: String(r.KECAMATAN || '').trim(),
                        kabupaten: String(r.KABUPATEN_KOTA || '').trim(),
                        provinsi: String(r.PROVINSI || '').trim(),
                        kodePos: String(r.KODE_POS || '').trim(),
                        jarak: String(r.JARAK_KE_MADRASAH || '').trim(),
                        transportasi: String(r.TRANSPORTASI || '').trim(),
                        waktuTempuh: String(r.WAKTU_TEMPUH || '').trim(),
                        ayahNama: String(r.NAMA_AYAH || '').trim(),
                        ayahStatus: String(
                            r.STATUS_AYAH || 'Masih Hidup',
                        ).trim(),
                        ayahNik: String(r.NIK_AYAH || '').trim(),
                        ayahTtl: String(r.TTL_AYAH || '').trim(),
                        ayahPendidikan: String(r.PENDIDIKAN_AYAH || '').trim(),
                        ayahPekerjaan: String(r.PEKERJAAN_AYAH || '').trim(),
                        ayahPenghasilan: String(
                            r.PENGHASILAN_AYAH || '',
                        ).trim(),
                        ibuNama: String(r.NAMA_IBU || '').trim(),
                        ibuStatus: String(r.STATUS_IBU || 'Masih Hidup').trim(),
                        ibuNik: String(r.NIK_IBU || '').trim(),
                        ibuTtl: String(r.TTL_IBU || '').trim(),
                        ibuPendidikan: String(r.PENDIDIKAN_IBU || '').trim(),
                        ibuPekerjaan: String(r.PEKERJAAN_IBU || '').trim(),
                        ibuPenghasilan: String(r.PENGHASILAN_IBU || '').trim(),
                        ortuHp: String(r.ORTU_HP || '').trim(),
                        waliNama: String(r.NAMA_WALI || '').trim(),
                        waliNik: String(r.NIK_WALI || '').trim(),
                        waliPekerjaan: String(r.PEKERJAAN_WALI || '').trim(),
                        waliPenghasilan: String(
                            r.PENGHASILAN_WALI || '',
                        ).trim(),
                        waliHubungan: String(r.HUBUNGAN_WALI || '').trim(),
                        waliHp: String(r.WALI_HP || '').trim(),
                        statusPip: String(r.STATUS_PIP || 'Tidak').trim(),
                        noKip: String(r.NO_KIP || '').trim(),
                        rekPip: String(r.NO_REKENING_PIP || '').trim(),
                        namaRekPip: String(r.NAMA_REKENING_PIP || '').trim(),
                        statusPkh: String(r.STATUS_PKH || 'Tidak').trim(),
                        noPkh: String(r.NO_PKH || '').trim(),
                        statusKks: String(r.STATUS_KKS || 'Tidak').trim(),
                        noKks: String(r.NO_KKS || '').trim(),
                        tb: Number(r.TINGGI_BADAN || 0),
                        bb: Number(r.BERAT_BADAN || 0),
                        lingkarKepala: Number(r.LINGKAR_KEPALA || 0),
                        golDarah: String(
                            r.GOLONGAN_DARAH || 'Tidak Tahu',
                        ).trim(),
                        disabilitas: String(
                            r.KEBUTUHAN_KHUSUS || 'Tidak Ada',
                        ).trim(),
                        keluarDariMadrasah: String(
                            r.KELUAR_DARI_MADRASAH || 'Masih Aktif',
                        ).trim(),
                        tglKeluar: String(r.TANGGAL_KELUAR || '').trim(),
                        noSeriIjazahMadrasah: String(
                            r.NO_SERI_IJAZAH_MADRASAH || '',
                        ).trim(),
                        sekolahTujuan: String(r.SEKOLAH_TUJUAN || '').trim(),
                        keteranganKeluar: String(
                            r.KETERANGAN_KELUAR || '',
                        ).trim(),
                    };
                })
                .filter((row): row is NonNullable<typeof row> => Boolean(row));

            if (studentsPayload.length === 0) {
                toast.error('Tidak ada baris siswa valid di file Excel.');
                return;
            }

            router.post(
                '/siswa/impor-emis',
                { students: studentsPayload },
                { preserveScroll: true },
            );
        } catch {
            toast.error('Gagal membaca berkas Excel siswa.');
        } finally {
            input.value = '';
        }
    };
    reader.readAsArrayBuffer(file);
};

/* -------------------------------- bulk paste -------------------------------- */

const bulkOpen = ref(false);
const bulkText = ref('');
const bulkForm = useForm<{ payload: string }>({ payload: '' });

const bulkFormat = [
    'Nama',
    'JK',
    'NISN',
    'NIS',
    'Kelas',
    'Tempat Lahir',
    'Tanggal Lahir',
    'Nama Wali',
    'No HP',
    'Alamat',
];

const sampleBulk = [
    'Ahmad Fauzan Hidayat\tL\t0091234567\t20250001\t1-A\tSukabumi\t12/04/2018\tBpk. Hidayat\t081234567890\tKp. Cibadak No. 3',
    'Aisyah Nur Fadilah\tP\t0091234568\t20250002\t1-A\tBogor\t23/07/2018\tBpk. Fadil\t081234567891\tKp. Cibadak No. 4',
].join('\n');

const detectDelimiter = (line: string): string => {
    if (line.includes('\t')) return '\t';
    if (line.includes('|')) return '|';
    if (line.includes(';')) return ';';
    return ',';
};

const isHeaderRow = (first: string) =>
    ['nama', 'nama siswa', 'name'].includes(first.trim().toLowerCase());

type ParsedRow = {
    line: number;
    nama: string;
    jenis_kelamin: string;
    nisn: string;
    kelas: string;
    tanggal_lahir: string;
    valid: boolean;
};

const parsedRows = computed<ParsedRow[]>(() => {
    const raw = bulkText.value.trim();
    if (!raw) return [];

    return raw
        .split(/\r\n|\r|\n/)
        .reduce<ParsedRow[]>((rows, lineText, idx) => {
            const trimmed = lineText.trim();
            if (!trimmed) return rows;

            const columns = trimmed
                .split(detectDelimiter(trimmed))
                .map((cell) => cell.trim());

            if (rows.length === 0 && isHeaderRow(columns[0] ?? '')) {
                return rows;
            }

            const nama = (columns[0] ?? '').slice(0, 150);
            const genderRaw = (columns[1] ?? '').trim().toUpperCase();
            rows.push({
                line: idx + 1,
                nama,
                jenis_kelamin:
                    genderRaw.startsWith('P') ||
                    genderRaw.startsWith('W') ||
                    genderRaw.startsWith('F')
                        ? 'P'
                        : 'L',
                nisn: (columns[2] ?? '').slice(0, 30),
                kelas: (columns[4] ?? '').slice(0, 20) || '1-A',
                tanggal_lahir: columns[6] ?? '',
                valid: nama !== '',
            });

            return rows;
        }, []);
});

const validRows = computed(() => parsedRows.value.filter((row) => row.valid));
const invalidRows = computed(() =>
    parsedRows.value.filter((row) => !row.valid),
);

const openBulk = () => {
    bulkText.value = '';
    bulkForm.reset();
    bulkForm.clearErrors();
    bulkOpen.value = true;
};

const submitBulk = () => {
    bulkForm.payload = bulkText.value;
    bulkForm.post('/siswa/impor-massal', {
        preserveScroll: true,
        onSuccess: () => {
            bulkOpen.value = false;
            bulkText.value = '';
        },
    });
};

const genderTone = (gender: Student['jenis_kelamin']) =>
    gender === 'P'
        ? 'bg-rose-500/10 text-rose-600 dark:text-rose-300'
        : 'bg-sky-500/10 text-sky-700 dark:text-sky-300';

const statusTone = (status: string) => {
    switch (status) {
        case 'Lulus':
            return 'bg-purple-500/12 text-purple-700 dark:text-purple-300';
        case 'Pindah':
        case 'Mutasi Keluar':
            return 'bg-[#c99700]/12 text-[#a87c00]';
        case 'Keluar':
        case 'Putus Sekolah':
            return 'bg-destructive/10 text-destructive';
        default:
            return 'bg-[#107c41]/8 text-[#0b5a31] dark:text-[#6fd39d]';
    }
};

const statusOptions = [
    'Aktif',
    'Lulus',
    'Mutasi Keluar',
    'Putus Sekolah',
    'Pindah',
    'Keluar',
];

const columnHeader =
    'px-3 py-2.5 text-left text-[10px] font-semibold tracking-[0.12em] text-muted-foreground uppercase whitespace-nowrap';

const initials = (name: string) =>
    name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
</script>

<template>
    <Head title="Master Data Siswa (EMIS 4.0)" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Kesiswaan & Asesmen EMIS 4.0"
            title="Master Data Siswa (EMIS 4.0)"
            description="Kelola data induk peserta didik berstandar EMIS 4.0 (6 tahap biodata, pas foto 3x4, cetak Buku Induk, KTS, dan ekspor/impor Excel 68 kolom)."
            :icon="Users"
        >
            <template #actions>
                <input
                    ref="xlsxInputRef"
                    type="file"
                    accept=".xlsx,.xls"
                    class="hidden"
                    @change="handleImportXlsx"
                />
                <Button variant="outline" size="sm" @click="kamusOpen = true">
                    <BookMarked class="size-3.5" />
                    Kamus EMIS
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    @click="bulkIndukOpen = true"
                >
                    <Printer class="size-3.5" />
                    Buku Induk Massal
                </Button>
                <Button variant="outline" size="sm" @click="openBulk">
                    <ClipboardPaste class="size-3.5" />
                    Tempel Excel
                </Button>
                <Button size="sm" @click="openCreate(1)">
                    <Plus class="size-3.5" />
                    Tambah Siswa
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                label="Total Siswa"
                :value="stats.total"
                hint="Seluruh peserta didik terdata"
                :icon="UsersRound"
                tone="emerald"
            />
            <StatCard
                label="Laki-laki"
                :value="stats.male"
                hint="Siswa berjenis kelamin L"
                :icon="UserRound"
                tone="sky"
            />
            <StatCard
                label="Perempuan"
                :value="stats.female"
                hint="Siswa berjenis kelamin P"
                :icon="UserRound"
                tone="rose"
            />
            <StatCard
                label="Kelas Terdata"
                :value="stats.classes"
                hint="Rombongan belajar aktif"
                :icon="FileSpreadsheet"
                tone="gold"
            />
        </div>

        <!-- Bar Ekspor/Impor XLSX 68 Kolom -->
        <div
            class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-border/70 bg-card px-4 py-2.5 text-xs shadow-2xs"
        >
            <span class="font-semibold text-muted-foreground">
                Alat Excel EMIS 4.0 (68 Kolom):
            </span>
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 text-xs"
                    @click="downloadBlankTemplate"
                >
                    <Download class="size-3.5" />
                    Template Kosong XLSX
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 text-xs"
                    @click="xlsxInputRef?.click()"
                >
                    <Upload class="size-3.5" />
                    Impor XLSX
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 text-xs"
                    @click="exportExcelXLSX"
                >
                    <FileSpreadsheet class="size-3.5" />
                    Ekspor XLSX
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 text-xs text-emerald-700"
                    :disabled="loadingSample"
                    @click="loadSampleData"
                >
                    <LoaderCircle
                        v-if="loadingSample"
                        class="size-3.5 animate-spin"
                    />
                    <Database v-else class="size-3.5" />
                    Muat Sampel MIN 1 Ciamis
                </Button>
            </div>
        </div>

        <section
            class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(7,63,36,0.04)]"
        >
            <!-- Toolbar -->
            <div
                class="flex flex-col gap-3 border-b border-border/70 p-4 lg:flex-row lg:items-center"
            >
                <div class="relative min-w-0 flex-1 lg:max-w-xs">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Cari nama, NISN, NIK, NIS, atau wali…"
                        class="pl-9"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="classFilter"
                        class="h-9 rounded-md border border-input bg-transparent px-2.5 text-[12px] shadow-xs outline-none focus-visible:border-ring dark:bg-input/30"
                    >
                        <option value="all">Semua kelas</option>
                        <option
                            v-for="kelas in classes"
                            :key="kelas"
                            :value="kelas"
                        >
                            {{ classLabel(kelas) }}
                        </option>
                    </select>

                    <select
                        v-model="genderFilter"
                        class="h-9 rounded-md border border-input bg-transparent px-2.5 text-[12px] shadow-xs outline-none focus-visible:border-ring dark:bg-input/30"
                    >
                        <option value="all">Semua gender</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>

                    <select
                        v-model="statusFilter"
                        class="h-9 rounded-md border border-input bg-transparent px-2.5 text-[12px] shadow-xs outline-none focus-visible:border-ring dark:bg-input/30"
                    >
                        <option value="all">Semua status</option>
                        <option
                            v-for="status in statusOptions"
                            :key="status"
                            :value="status"
                        >
                            {{ status }}
                        </option>
                    </select>

                    <select
                        v-model="emisFilter"
                        class="h-9 rounded-md border border-input bg-transparent px-2.5 text-[12px] shadow-xs outline-none focus-visible:border-ring dark:bg-input/30"
                    >
                        <option value="all">Validasi EMIS</option>
                        <option value="Lengkap">Lengkap EMIS</option>
                        <option value="Belum">Belum Lengkap</option>
                    </select>

                    <button
                        v-if="activeFilters > 0"
                        type="button"
                        class="inline-flex h-9 items-center gap-1 rounded-md border border-[#107c41]/20 bg-[#107c41]/5 px-2.5 text-[11.5px] font-medium text-[#107c41] transition hover:bg-[#107c41]/10"
                        @click="resetFilters"
                    >
                        <X class="size-3.5" />
                        Reset ({{ activeFilters }})
                    </button>
                </div>

                <div
                    class="ml-auto flex items-center gap-2 text-[11px] text-muted-foreground"
                >
                    <span class="hidden sm:inline">Tampilkan</span>
                    <select
                        v-model.number="perPage"
                        class="h-8 rounded-md border border-input bg-transparent px-2 text-[11.5px] outline-none dark:bg-input/30"
                    >
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <span>baris</span>
                </div>
            </div>

            <!-- Empty -->
            <div
                v-if="props.students.length === 0"
                class="flex flex-col items-center justify-center px-6 py-14 text-center"
            >
                <div
                    class="grid size-14 place-items-center rounded-2xl bg-[#107c41]/8 text-[#107c41] ring-1 ring-[#107c41]/15"
                >
                    <Users class="size-6" />
                </div>
                <h3 class="mt-4 font-serif text-base font-bold text-foreground">
                    Belum ada data siswa
                </h3>
                <p
                    class="mt-1 max-w-md text-[12.5px] leading-relaxed text-muted-foreground"
                >
                    Mulai dengan menambahkan siswa lewat formulir EMIS 4.0,
                    mengimpor file Excel XLSX, atau memuat data sampel MIN 1
                    Ciamis.
                </p>
                <div
                    class="mt-4 flex flex-wrap items-center justify-center gap-2"
                >
                    <Button @click="openCreate(1)">
                        <Plus />
                        Tambah Siswa
                    </Button>
                    <Button variant="outline" @click="loadSampleData">
                        <Database />
                        Muat Sampel MIN 1 Ciamis
                    </Button>
                </div>
            </div>

            <template v-else>
                <!-- Desktop table -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[900px] border-collapse">
                        <thead class="bg-muted/40">
                            <tr>
                                <th :class="columnHeader">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 hover:text-[#107c41]"
                                        @click="toggleSort('nama')"
                                    >
                                        Siswa &amp; Foto
                                        <ArrowDownUp
                                            class="size-3 opacity-60"
                                        />
                                    </button>
                                </th>
                                <th :class="columnHeader">NISN / NIK / NIS</th>
                                <th :class="columnHeader">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 hover:text-[#107c41]"
                                        @click="toggleSort('kelas')"
                                    >
                                        Rombel
                                        <ArrowDownUp
                                            class="size-3 opacity-60"
                                        />
                                    </button>
                                </th>
                                <th :class="columnHeader">L/P</th>
                                <th :class="columnHeader">Status &amp; PIP</th>
                                <th :class="columnHeader">Validasi EMIS</th>
                                <th :class="columnHeader + ' text-right'">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="student in paginated"
                                :key="student.id"
                                class="border-t border-border/60 transition-colors hover:bg-[#107c41]/[0.025]"
                            >
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            v-if="student.foto"
                                            class="h-10 w-8 shrink-0 overflow-hidden rounded border border-border bg-muted"
                                        >
                                            <img
                                                :src="student.foto"
                                                alt="Foto"
                                                class="size-full object-cover"
                                            />
                                        </div>
                                        <div
                                            v-else
                                            class="grid size-8 shrink-0 place-items-center rounded-full text-[10px] font-bold"
                                            :class="
                                                genderTone(
                                                    student.jenis_kelamin,
                                                )
                                            "
                                        >
                                            {{ initials(student.nama) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-[12.5px] font-semibold text-foreground uppercase"
                                            >
                                                {{ student.nama }}
                                            </p>
                                            <p
                                                class="truncate text-[10.5px] text-muted-foreground"
                                            >
                                                {{
                                                    student.tempat_lahir || '—'
                                                }}
                                                <template
                                                    v-if="student.tanggal_lahir"
                                                >
                                                    ·
                                                    {{
                                                        formatDate(
                                                            student.tanggal_lahir,
                                                        )
                                                    }}
                                                </template>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <p
                                        class="font-mono text-[11px] text-foreground/85"
                                    >
                                        NISN: {{ student.nisn || '—' }}
                                    </p>
                                    <p
                                        class="font-mono text-[10px] text-muted-foreground"
                                    >
                                        NIK: {{ student.nik || '—' }}
                                    </p>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span
                                        class="rounded-md bg-[#107c41]/8 px-2 py-0.5 text-[11px] font-semibold text-[#0b5a31] dark:text-[#6fd39d]"
                                    >
                                        {{ student.kelas }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span
                                        class="inline-grid size-6 place-items-center rounded-full text-[10px] font-bold"
                                        :class="
                                            genderTone(student.jenis_kelamin)
                                        "
                                    >
                                        {{ student.jenis_kelamin }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div
                                        class="flex flex-wrap items-center gap-1"
                                    >
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[9.5px] font-semibold tracking-wide uppercase"
                                            :class="statusTone(student.status)"
                                        >
                                            {{ student.status }}
                                        </span>
                                        <span
                                            v-if="student.statusPip === 'Ya'"
                                            class="rounded bg-blue-100 px-1.5 py-0.5 text-[9.5px] font-bold text-blue-800"
                                        >
                                            PIP
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span
                                        v-if="
                                            validateEmisStudent(student).isValid
                                        "
                                        class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800"
                                    >
                                        Lengkap
                                    </span>
                                    <span
                                        v-else
                                        class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800"
                                        :title="`Kurang: ${validateEmisStudent(student).missing.join(', ')}`"
                                    >
                                        Belum ({{
                                            validateEmisStudent(student).missing
                                                .length
                                        }})
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-purple-700 hover:bg-purple-50"
                                            title="Buku Induk Siswa"
                                            @click="biodataStudent = student"
                                        >
                                            <BookOpen class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-indigo-700 hover:bg-indigo-50"
                                            title="Kartu Tanda Siswa (KTS)"
                                            @click="ktsStudent = student"
                                        >
                                            <IdCard class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-[#107c41]"
                                            title="Edit data EMIS"
                                            @click="openEdit(student)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-destructive"
                                            title="Hapus data siswa"
                                            @click="deleting = student"
                                        >
                                            <Trash class="size-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile cards -->
                <div class="divide-y divide-border/60 md:hidden">
                    <div
                        v-for="student in paginated"
                        :key="student.id"
                        class="flex items-start gap-3 p-3.5"
                    >
                        <div
                            class="grid size-9 shrink-0 place-items-center rounded-full text-[10.5px] font-bold"
                            :class="genderTone(student.jenis_kelamin)"
                        >
                            {{ initials(student.nama) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-[12.5px] font-semibold text-foreground uppercase"
                                    >
                                        {{ student.nama }}
                                    </p>
                                    <p
                                        class="text-[10.5px] text-muted-foreground"
                                    >
                                        NISN {{ student.nisn || '—' }} ·
                                        {{ classLabel(student.kelas) }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase"
                                    :class="statusTone(student.status)"
                                >
                                    {{ student.status }}
                                </span>
                            </div>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-1.5"
                            >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 px-2 text-[11px]"
                                    @click="biodataStudent = student"
                                >
                                    <BookOpen class="size-3" />
                                    Induk
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 px-2 text-[11px]"
                                    @click="ktsStudent = student"
                                >
                                    <IdCard class="size-3" />
                                    KTS
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 px-2 text-[11px]"
                                    @click="openEdit(student)"
                                >
                                    <Pencil class="size-3" />
                                    Edit
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-[11px] text-destructive"
                                    @click="deleting = student"
                                >
                                    <Trash class="size-3" />
                                    Hapus
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No results -->
                <div
                    v-if="filtered.length === 0"
                    class="px-6 py-12 text-center text-[12.5px] text-muted-foreground"
                >
                    Tidak ada siswa yang cocok dengan filter.
                    <button
                        type="button"
                        class="ml-1 font-semibold text-[#107c41] hover:underline"
                        @click="resetFilters"
                    >
                        Bersihkan filter
                    </button>
                </div>

                <!-- Pagination -->
                <div
                    class="flex flex-col gap-2 border-t border-border/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-[11px] text-muted-foreground">
                        Menampilkan
                        <span class="font-semibold text-foreground">{{
                            rangeStart
                        }}</span>
                        –
                        <span class="font-semibold text-foreground">{{
                            rangeEnd
                        }}</span>
                        dari
                        <span class="font-semibold text-foreground">{{
                            filtered.length
                        }}</span>
                        siswa
                    </p>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-7 px-2 text-[11px]"
                            :disabled="page <= 1"
                            @click="page--"
                        >
                            <ChevronLeft class="size-3.5" />
                            Sebelumnya
                        </Button>
                        <div class="flex items-center gap-1 px-1">
                            <button
                                v-for="number in totalPages"
                                :key="number"
                                type="button"
                                class="grid size-7 place-items-center rounded-md text-[11.5px] font-medium transition"
                                :class="
                                    number === page
                                        ? 'bg-[#107c41] text-white'
                                        : 'text-muted-foreground hover:bg-muted'
                                "
                                @click="page = number"
                            >
                                {{ number }}
                            </button>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-7 px-2 text-[11px]"
                            :disabled="page >= totalPages"
                            @click="page++"
                        >
                            Berikutnya
                            <ChevronRight class="size-3.5" />
                        </Button>
                    </div>
                </div>
            </template>
        </section>
    </div>

    <!-- 6-Step EMIS 4.0 Student Form Modal -->
    <StudentEmisFormModal
        v-model:open="formOpen"
        :student="editing"
        :rombels="rombels"
        :all-rombel-codes="allRombelCodes"
        :default-tab="defaultFormTab"
    />

    <!-- Buku Induk, KTS, Kamus EMIS Modals -->
    <StudentEmisModals
        :students="students"
        :all-rombel-codes="allRombelCodes"
        :profile="madrasahProfile"
        :biodata-student="biodataStudent"
        :kts-student="ktsStudent"
        :bulk-induk-open="bulkIndukOpen"
        :kamus-open="kamusOpen"
        @close-biodata="biodataStudent = null"
        @close-kts="ktsStudent = null"
        @update:bulk-induk-open="bulkIndukOpen = $event"
        @update:kamus-open="kamusOpen = $event"
    />

    <!-- Bulk paste dialog -->
    <Dialog v-model:open="bulkOpen">
        <DialogContent class="max-h-[92vh] overflow-y-auto sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Impor Massal Data Siswa</DialogTitle>
                <DialogDescription>
                    Salin data dari Excel, Google Sheets, atau CSV lalu tempel
                    di bawah. Pemisah kolom yang didukung: Tab, pipe (|), titik
                    koma (;), atau koma.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <div
                    class="rounded-xl border border-[#107c41]/15 bg-[#107c41]/[0.03] p-3 text-[11px] leading-relaxed text-muted-foreground"
                >
                    <p class="font-semibold text-[#107c41]">Urutan kolom</p>
                    <div class="mt-1.5 flex flex-wrap gap-1">
                        <span
                            v-for="(column, index) in bulkFormat"
                            :key="column"
                            class="inline-flex items-center gap-1 rounded-md border border-border bg-card px-1.5 py-0.5 text-[10px]"
                        >
                            <span class="font-mono text-[9px] text-[#a87c00]">{{
                                index + 1
                            }}</span>
                            {{ column }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <label
                        for="bulk-text"
                        class="text-[12px] font-semibold text-foreground"
                    >
                        Tempel data di sini
                    </label>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#107c41] hover:underline"
                        @click="bulkText = sampleBulk"
                    >
                        <ClipboardPaste class="size-3" />
                        Gunakan contoh
                    </button>
                </div>

                <textarea
                    id="bulk-text"
                    v-model="bulkText"
                    rows="7"
                    spellcheck="false"
                    placeholder="Nama	L/P	NISN	NIS	Kelas	Tempat Lahir	Tanggal Lahir	Nama Wali	No HP	Alamat"
                    class="w-full rounded-lg border border-input bg-transparent px-3 py-2 font-mono text-[11.5px] leading-relaxed shadow-xs outline-none dark:bg-input/30"
                />

                <div v-if="parsedRows.length > 0" class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-[#107c41]/10 px-2 py-0.5 font-semibold text-[#107c41]"
                        >
                            <Check class="size-3" />
                            {{ validRows.length }} baris siap diimpor
                        </span>
                        <span
                            v-if="invalidRows.length"
                            class="inline-flex items-center gap-1 rounded-full bg-destructive/10 px-2 py-0.5 font-semibold text-destructive"
                        >
                            <X class="size-3" />
                            {{ invalidRows.length }} baris tanpa nama
                        </span>
                    </div>
                </div>
            </div>

            <DialogFooter class="mt-2 gap-2">
                <Button
                    variant="outline"
                    :disabled="bulkForm.processing"
                    @click="bulkOpen = false"
                >
                    Batal
                </Button>
                <Button
                    :disabled="bulkForm.processing || validRows.length === 0"
                    @click="submitBulk"
                >
                    <LoaderCircle
                        v-if="bulkForm.processing"
                        class="animate-spin"
                    />
                    <FileSpreadsheet v-else />
                    Impor {{ validRows.length }} Siswa
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="Boolean(deleting)"
        title="Hapus data siswa?"
        :description="
            deleting
                ? `${deleting.nama} akan dihapus secara permanen dari master data siswa.`
                : ''
        "
        :processing="deletingProcessing"
        @update:open="(open) => !open && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
