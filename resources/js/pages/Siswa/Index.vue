<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDownUp,
    Check,
    ChevronLeft,
    ChevronRight,
    ClipboardPaste,
    FileSpreadsheet,
    LoaderCircle,
    Pencil,
    Plus,
    Search,
    Trash,
    UserRound,
    Users,
    UsersRound,
    X,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import FormField from '@/components/app/FormField.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import StatCard from '@/components/app/StatCard.vue';
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

const props = defineProps<{
    students: Student[];
    classes: string[];
    stats: StudentStats;
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'Master Data Siswa', href: '/siswa' },
        ],
    }),
});

/* ---------------------------------- filters --------------------------------- */

const search = ref('');
const classFilter = ref('all');
const genderFilter = ref('all');
const statusFilter = ref('all');
const sortBy = ref<'nama' | 'kelas' | 'nisn'>('kelas');
const sortDir = ref<'asc' | 'desc'>('asc');
const perPage = ref(10);
const page = ref(1);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    const rows = props.students.filter((student) => {
        if (classFilter.value !== 'all' && student.kelas !== classFilter.value) {
            return false;
        }

        if (
            genderFilter.value !== 'all' &&
            student.jenis_kelamin !== genderFilter.value
        ) {
            return false;
        }

        if (statusFilter.value !== 'all' && student.status !== statusFilter.value) {
            return false;
        }

        if (!term) {
            return true;
        }

        return [student.nama, student.nisn, student.nis, student.nama_wali]
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
        Number(statusFilter.value !== 'all'),
);

watch(
    [search, classFilter, genderFilter, statusFilter, perPage],
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

/* ----------------------------------- form ---------------------------------- */

type StudentForm = {
    nama: string;
    nisn: string;
    nis: string;
    jenis_kelamin: 'L' | 'P';
    kelas: string;
    tempat_lahir: string;
    tanggal_lahir: string;
    nama_wali: string;
    no_hp: string;
    alamat: string;
    status: string;
    catatan: string;
};

const emptyForm = (): StudentForm => ({
    nama: '',
    nisn: '',
    nis: '',
    jenis_kelamin: 'L',
    kelas: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    nama_wali: '',
    no_hp: '',
    alamat: '',
    status: 'Aktif',
    catatan: '',
});

const form = useForm<StudentForm>(emptyForm());

const formOpen = ref(false);
const editing = ref<Student | null>(null);
const deleting = ref<Student | null>(null);
const deletingProcessing = ref(false);

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    Object.assign(form, emptyForm());

    if (classFilter.value !== 'all') {
        form.kelas = classFilter.value;
    }

    formOpen.value = true;
};

const openEdit = (student: Student) => {
    editing.value = student;
    form.clearErrors();
    form.defaults({
        nama: student.nama,
        nisn: student.nisn ?? '',
        nis: student.nis ?? '',
        jenis_kelamin: student.jenis_kelamin,
        kelas: student.kelas,
        tempat_lahir: student.tempat_lahir ?? '',
        tanggal_lahir: student.tanggal_lahir ?? '',
        nama_wali: student.nama_wali ?? '',
        no_hp: student.no_hp ?? '',
        alamat: student.alamat ?? '',
        status: student.status,
        catatan: student.catatan ?? '',
    });
    form.reset();
    formOpen.value = true;
};

const submitForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
            editing.value = null;
        },
    };

    if (editing.value) {
        form.put(`/siswa/${editing.value.id}`, options);

        return;
    }

    form.post('/siswa', options);
};

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

/* -------------------------------- bulk import ------------------------------- */

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
    'Ahmad Fauzan Hidayat\tL\t0091234567\t20250001\t1A\tSukabumi\t12/04/2018\tBpk. Hidayat\t081234567890\tKp. Cibadak No. 3',
    'Aisyah Nur Fadilah\tP\t0091234568\t20250002\t1A\tBogor\t23/07/2018\tBpk. Fadil\t081234567891\tKp. Cibadak No. 4',
].join('\n');

const detectDelimiter = (line: string): string => {
    if (line.includes('\t')) {
        return '\t';
    }

    if (line.includes('|')) {
        return '|';
    }

    if (line.includes(';')) {
        return ';';
    }

    return ',';
};

const isHeaderRow = (first: string) =>
    ['nama', 'nama siswa', 'name'].includes(first.trim().toLowerCase());

const normalizeGender = (value: string): 'L' | 'P' => {
    const clean = value.trim().toUpperCase();

    return ['P', 'W', 'F', 'PEREMPUAN'].some((prefix) =>
        clean.startsWith(prefix),
    )
        ? 'P'
        : 'L';
};

const parseDate = (value: string): string => {
    const clean = value.trim();

    if (!clean) {
        return '';
    }

    const dmy = clean.match(/^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$/);

    if (dmy) {
        const [, day, month, year] = dmy;

        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }

    const ymd = clean.match(/^(\d{4})[/.-](\d{1,2})[/.-](\d{1,2})$/);

    if (ymd) {
        const [, year, month, day] = ymd;

        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }

    return '';
};

type ParsedRow = {
    line: number;
    nama: string;
    jenis_kelamin: 'L' | 'P';
    nisn: string;
    nis: string;
    kelas: string;
    tempat_lahir: string;
    tanggal_lahir: string;
    nama_wali: string;
    no_hp: string;
    alamat: string;
    valid: boolean;
};

const parsedRows = computed<ParsedRow[]>(() => {
    const lines = bulkText.value.split(/\r\n|\r|\n/);

    return lines.reduce<ParsedRow[]>((rows, raw, index) => {
        const line = raw.trim();

        if (!line) {
            return rows;
        }

        const columns = line.split(detectDelimiter(line)).map((part) => part.trim());

        if (isHeaderRow(columns[0] ?? '')) {
            return rows;
        }

        const nama = (columns[0] ?? '').slice(0, 150);

        rows.push({
            line: index + 1,
            nama,
            jenis_kelamin: normalizeGender(columns[1] ?? ''),
            nisn: (columns[2] ?? '').slice(0, 30),
            nis: (columns[3] ?? '').slice(0, 30),
            kelas: (columns[4] ?? '').slice(0, 20) || '1',
            tempat_lahir: (columns[5] ?? '').slice(0, 150),
            tanggal_lahir: parseDate(columns[6] ?? ''),
            nama_wali: (columns[7] ?? '').slice(0, 150),
            no_hp: (columns[8] ?? '').slice(0, 30),
            alamat: (columns[9] ?? '').slice(0, 500),
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

const useSample = () => {
    bulkText.value = sampleBulk;
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
            return 'bg-[#107c41]/10 text-[#107c41]';
        case 'Pindah':
            return 'bg-[#c99700]/12 text-[#a87c00]';
        case 'Keluar':
            return 'bg-destructive/10 text-destructive';
        default:
            return 'bg-[#107c41]/8 text-[#0b5a31] dark:text-[#6fd39d]';
    }
};

const statusOptions = ['Aktif', 'Lulus', 'Pindah', 'Keluar'];

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
    <Head title="Master Data Siswa" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Kesiswaan & Asesmen"
            title="Master Data Siswa"
            description="Kelola data peserta didik sekali di sini. Seluruh modul absensi, nilai, dan asesmen akan otomatis memakai data ini."
            :icon="Users"
        >
            <template #actions>
                <Button variant="outline" @click="openBulk">
                    <ClipboardPaste />
                    Impor Massal
                </Button>
                <Button @click="openCreate">
                    <Plus />
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

        <section
            class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(7,63,36,0.04)]"
        >
            <!-- Toolbar -->
            <div class="flex flex-col gap-3 border-b border-border/70 p-4 lg:flex-row lg:items-center">
                <div class="relative min-w-0 flex-1 lg:max-w-xs">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Cari nama, NISN, NIS, atau wali…"
                        class="pl-9"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="classFilter"
                        class="h-9 rounded-md border border-input bg-transparent px-2.5 text-[12px] shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    >
                        <option value="all">Semua kelas</option>
                        <option v-for="kelas in classes" :key="kelas" :value="kelas">
                            {{ classLabel(kelas) }}
                        </option>
                    </select>

                    <select
                        v-model="genderFilter"
                        class="h-9 rounded-md border border-input bg-transparent px-2.5 text-[12px] shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    >
                        <option value="all">Semua gender</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>

                    <select
                        v-model="statusFilter"
                        class="h-9 rounded-md border border-input bg-transparent px-2.5 text-[12px] shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    >
                        <option value="all">Semua status</option>
                        <option v-for="status in statusOptions" :key="status" :value="status">
                            {{ status }}
                        </option>
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

                <div class="ml-auto flex items-center gap-2 text-[11px] text-muted-foreground">
                    <span class="hidden sm:inline">Tampilkan</span>
                    <select
                        v-model.number="perPage"
                        class="h-8 rounded-md border border-input bg-transparent px-2 text-[11.5px] outline-none focus-visible:border-ring dark:bg-input/30"
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
                <p class="mt-1 max-w-md text-[12.5px] leading-relaxed text-muted-foreground">
                    Mulai dengan menambahkan siswa satu per satu, atau tempel
                    data dari Excel/Google Sheets menggunakan fitur impor massal.
                </p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    <Button @click="openCreate">
                        <Plus />
                        Tambah Siswa
                    </Button>
                    <Button variant="outline" @click="openBulk">
                        <ClipboardPaste />
                        Impor Massal
                    </Button>
                </div>
            </div>

            <template v-else>
                <!-- Desktop table -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[820px] border-collapse">
                        <thead class="bg-muted/40">
                            <tr>
                                <th :class="columnHeader">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 hover:text-[#107c41]"
                                        @click="toggleSort('nama')"
                                    >
                                        Nama Siswa
                                        <ArrowDownUp class="size-3 opacity-60" />
                                    </button>
                                </th>
                                <th :class="columnHeader">NISN / NIS</th>
                                <th :class="columnHeader">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 hover:text-[#107c41]"
                                        @click="toggleSort('kelas')"
                                    >
                                        Kelas
                                        <ArrowDownUp class="size-3 opacity-60" />
                                    </button>
                                </th>
                                <th :class="columnHeader">L/P</th>
                                <th :class="columnHeader">Wali & Kontak</th>
                                <th :class="columnHeader">Status</th>
                                <th :class="columnHeader + ' text-right'">Aksi</th>
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
                                            class="grid size-8 shrink-0 place-items-center rounded-full text-[10px] font-bold"
                                            :class="genderTone(student.jenis_kelamin)"
                                        >
                                            {{ initials(student.nama) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-[12.5px] font-semibold text-foreground">
                                                {{ student.nama }}
                                            </p>
                                            <p class="truncate text-[10.5px] text-muted-foreground">
                                                {{ student.tempat_lahir || '—' }}
                                                <template v-if="student.tanggal_lahir">
                                                    · {{ formatDate(student.tanggal_lahir) }}
                                                </template>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <p class="font-mono text-[11.5px] text-foreground/85">
                                        {{ student.nisn || '—' }}
                                    </p>
                                    <p class="font-mono text-[10px] text-muted-foreground">
                                        {{ student.nis || '—' }}
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
                                        :class="genderTone(student.jenis_kelamin)"
                                    >
                                        {{ student.jenis_kelamin }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <p class="truncate text-[11.5px] text-foreground/80">
                                        {{ student.nama_wali || '—' }}
                                    </p>
                                    <p class="truncate text-[10.5px] text-muted-foreground">
                                        {{ student.no_hp || '—' }}
                                    </p>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[9.5px] font-semibold tracking-wide uppercase"
                                        :class="statusTone(student.status)"
                                    >
                                        {{ student.status }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-[#107c41]"
                                            title="Edit data siswa"
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
                                    <p class="truncate text-[12.5px] font-semibold text-foreground">
                                        {{ student.nama }}
                                    </p>
                                    <p class="text-[10.5px] text-muted-foreground">
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
                            <div class="mt-1.5 flex items-center gap-2 text-[10.5px] text-muted-foreground">
                                <span class="truncate">{{ student.nama_wali || 'Wali belum diisi' }}</span>
                                <span class="size-1 shrink-0 rounded-full bg-border" />
                                <span class="truncate">{{ student.no_hp || '—' }}</span>
                            </div>
                            <div class="mt-2 flex items-center gap-1.5">
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
                                    class="h-7 px-2 text-[11px] text-destructive hover:text-destructive"
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
                        <span class="font-semibold text-foreground">{{ rangeStart }}</span>
                        –
                        <span class="font-semibold text-foreground">{{ rangeEnd }}</span>
                        dari
                        <span class="font-semibold text-foreground">{{ filtered.length }}</span>
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

    <!-- Create / edit dialog -->
    <Dialog v-model:open="formOpen">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{ editing ? 'Edit Data Siswa' : 'Tambah Siswa Baru' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        editing
                            ? 'Perbarui data peserta didik. Perubahan berlaku pada seluruh modul.'
                            : 'Isi data pokok peserta didik. Kolom bertanda bintang wajib diisi.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-3.5 sm:grid-cols-2" @submit.prevent="submitForm">
                <div class="sm:col-span-2">
                    <FormField
                        label="Nama Lengkap"
                        for="student-nama"
                        required
                        :error="form.errors.nama"
                    >
                        <Input
                            id="student-nama"
                            v-model="form.nama"
                            placeholder="Ahmad Fauzan Hidayat"
                        />
                    </FormField>
                </div>

                <FormField label="NISN" for="student-nisn" :error="form.errors.nisn">
                    <Input id="student-nisn" v-model="form.nisn" placeholder="0091234567" />
                </FormField>
                <FormField label="NIS" for="student-nis" :error="form.errors.nis">
                    <Input id="student-nis" v-model="form.nis" placeholder="20250001" />
                </FormField>

                <FormField
                    label="Jenis Kelamin"
                    for="student-gender"
                    required
                    :error="form.errors.jenis_kelamin"
                >
                    <select
                        id="student-gender"
                        v-model="form.jenis_kelamin"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    >
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </FormField>
                <FormField
                    label="Kelas"
                    for="student-kelas"
                    required
                    hint="Contoh: 1A, 4B, atau 6"
                    :error="form.errors.kelas"
                >
                    <Input id="student-kelas" v-model="form.kelas" placeholder="5A" />
                </FormField>

                <FormField
                    label="Tempat Lahir"
                    for="student-tempat"
                    :error="form.errors.tempat_lahir"
                >
                    <Input
                        id="student-tempat"
                        v-model="form.tempat_lahir"
                        placeholder="Sukabumi"
                    />
                </FormField>
                <FormField
                    label="Tanggal Lahir"
                    for="student-tanggal"
                    :error="form.errors.tanggal_lahir"
                >
                    <Input
                        id="student-tanggal"
                        v-model="form.tanggal_lahir"
                        type="date"
                    />
                </FormField>

                <FormField
                    label="Nama Wali"
                    for="student-wali"
                    :error="form.errors.nama_wali"
                >
                    <Input
                        id="student-wali"
                        v-model="form.nama_wali"
                        placeholder="Bpk. Hidayat"
                    />
                </FormField>
                <FormField
                    label="No. HP Wali"
                    for="student-hp"
                    :error="form.errors.no_hp"
                >
                    <Input
                        id="student-hp"
                        v-model="form.no_hp"
                        placeholder="081234567890"
                    />
                </FormField>

                <div class="sm:col-span-2">
                    <FormField label="Alamat" for="student-alamat" :error="form.errors.alamat">
                        <textarea
                            id="student-alamat"
                            v-model="form.alamat"
                            rows="2"
                            placeholder="Kp. Cibadak No. 3, Sukamaju"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        />
                    </FormField>
                </div>

                <FormField
                    label="Status"
                    for="student-status"
                    required
                    :error="form.errors.status"
                >
                    <select
                        id="student-status"
                        v-model="form.status"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    >
                        <option v-for="status in statusOptions" :key="status" :value="status">
                            {{ status }}
                        </option>
                    </select>
                </FormField>
                <FormField
                    label="Catatan"
                    for="student-catatan"
                    :error="form.errors.catatan"
                >
                    <Input
                        id="student-catatan"
                        v-model="form.catatan"
                        placeholder="Opsional"
                    />
                </FormField>

                <div class="mt-1 flex items-center justify-end gap-2 sm:col-span-2">
                    <Button
                        variant="outline"
                        type="button"
                        :disabled="form.processing"
                        @click="formOpen = false"
                    >
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="animate-spin" />
                        <Check v-else />
                        {{ editing ? 'Simpan Perubahan' : 'Tambah Siswa' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Bulk import dialog -->
    <Dialog v-model:open="bulkOpen">
        <DialogContent class="max-h-[92vh] overflow-y-auto sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Impor Massal Data Siswa</DialogTitle>
                <DialogDescription>
                    Salin data dari Excel, Google Sheets, atau CSV lalu tempel di
                    bawah. Pemisah kolom yang didukung: Tab, pipe (|), titik koma
                    (;), atau koma.
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
                            <span class="font-mono text-[9px] text-[#a87c00]">{{ index + 1 }}</span>
                            {{ column }}
                        </span>
                    </div>
                    <p class="mt-1.5">
                        Hanya kolom Nama yang wajib. Baris judul (header) otomatis
                        dilewati. Tanggal dapat ditulis dd/mm/yyyy.
                    </p>
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
                        @click="useSample"
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
                    class="w-full rounded-lg border border-input bg-transparent px-3 py-2 font-mono text-[11.5px] leading-relaxed shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                />

                <p v-if="bulkForm.errors.payload" class="text-[11.5px] font-medium text-destructive">
                    {{ bulkForm.errors.payload }}
                </p>

                <!-- Preview -->
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
                        <span class="text-muted-foreground">Pratinjau data:</span>
                    </div>

                    <div class="max-h-56 overflow-auto rounded-lg border border-border">
                        <table class="w-full min-w-[560px] border-collapse text-[11px]">
                            <thead class="sticky top-0 bg-muted/70 backdrop-blur">
                                <tr>
                                    <th class="px-2 py-1.5 text-left font-semibold text-muted-foreground">#</th>
                                    <th class="px-2 py-1.5 text-left font-semibold text-muted-foreground">Nama</th>
                                    <th class="px-2 py-1.5 text-left font-semibold text-muted-foreground">L/P</th>
                                    <th class="px-2 py-1.5 text-left font-semibold text-muted-foreground">NISN</th>
                                    <th class="px-2 py-1.5 text-left font-semibold text-muted-foreground">Kelas</th>
                                    <th class="px-2 py-1.5 text-left font-semibold text-muted-foreground">Tgl Lahir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in parsedRows.slice(0, 40)"
                                    :key="row.line"
                                    class="border-t border-border/60"
                                    :class="!row.valid ? 'bg-destructive/5' : ''"
                                >
                                    <td class="px-2 py-1.5 text-muted-foreground">{{ row.line }}</td>
                                    <td class="max-w-[200px] truncate px-2 py-1.5 font-medium text-foreground">
                                        {{ row.nama || '(kosong)' }}
                                    </td>
                                    <td class="px-2 py-1.5">{{ row.jenis_kelamin }}</td>
                                    <td class="px-2 py-1.5 font-mono text-[10.5px]">{{ row.nisn || '—' }}</td>
                                    <td class="px-2 py-1.5">{{ row.kelas }}</td>
                                    <td class="px-2 py-1.5">{{ row.tanggal_lahir || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p
                        v-if="parsedRows.length > 40"
                        class="text-[10.5px] text-muted-foreground"
                    >
                        Menampilkan 40 baris pertama dari {{ parsedRows.length }} baris.
                    </p>
                </div>
            </div>

            <DialogFooter class="mt-2 gap-2">
                <Button variant="outline" :disabled="bulkForm.processing" @click="bulkOpen = false">
                    Batal
                </Button>
                <Button
                    :disabled="bulkForm.processing || validRows.length === 0"
                    @click="submitBulk"
                >
                    <LoaderCircle v-if="bulkForm.processing" class="animate-spin" />
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
                ? `${deleting.nama} akan dihapus dari master data. Data absensi dan nilai yang terkait juga akan kehilangan rujukan.`
                : ''
        "
        :processing="deletingProcessing"
        @update:open="(open) => !open && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
