<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Download,
    FileSpreadsheet,
    GraduationCap,
    LoaderCircle,
    Printer,
    Save,
    Search,
    Upload,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import * as XLSX from 'xlsx';
import PageHeader from '@/components/app/PageHeader.vue';
import {
    buildTranskripRdmHtml,
    openPrintWindowWithContent,
} from '@/components/sim-emis/EmisPrintHelpers';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    getStudentSemesterAvg,
    RDM_SEMESTERS,
    RDM_SUBJECTS_MI,
} from '@/types/emis';
import type {
    EmisMadrasahProfile,
    EmisRombel,
    EmisStudent,
    RdmScoresMap,
} from '@/types/emis';

const props = defineProps<{
    students: EmisStudent[];
    rombels: EmisRombel[];
    rdmScores: RdmScoresMap;
    madrasahProfile: EmisMadrasahProfile;
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'RDM MI (13 Mapel)', href: '/rdm' },
        ],
    }),
});

const search = ref('');
const rombelFilter = ref('');
const templateSmt = ref(1);
const importSmt = ref('AUTO');
const rdmFileRef = ref<HTMLInputElement | null>(null);

const allRombelCodes = computed(() => {
    const set = new Set(props.rombels.map((r) => r.kode));
    props.students.forEach((s) => {
        if (s.rombel) set.add(s.rombel);
    });
    return Array.from(set).sort((a, b) =>
        a.localeCompare(b, 'id', { numeric: true }),
    );
});

const filteredStudents = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.students.filter((s) => {
        const mQ =
            !q ||
            s.nama.toLowerCase().includes(q) ||
            (s.nisn || '').includes(q);
        const mR = !rombelFilter.value || s.rombel === rombelFilter.value;
        return mQ && mR;
    });
});

const overallAverage = (studentId: number): string => {
    const valid = RDM_SEMESTERS.map((smt) =>
        getStudentSemesterAvg(props.rdmScores, studentId, smt),
    ).filter((v): v is number => v !== null);
    if (valid.length === 0) return '-';
    return (valid.reduce((a, b) => a + b, 0) / valid.length).toFixed(2);
};

/* ------------------------- Detail & Edit Modal ---------------------------- */

const detailStudent = ref<EmisStudent | null>(null);
const modalGrid = reactive<Record<string, Record<string, string>>>({});
const savingModal = ref(false);

const openDetail = (s: EmisStudent) => {
    detailStudent.value = s;
    const rec = props.rdmScores[String(s.id)] || {};
    RDM_SUBJECTS_MI.forEach((subj) => {
        modalGrid[subj] = {};
        RDM_SEMESTERS.forEach((smt) => {
            const val = rec[subj]?.[String(smt)];
            modalGrid[subj][String(smt)] =
                val !== undefined && val !== null ? String(val) : '';
        });
    });
};

const subjectRowAvg = (subj: string): string => {
    const nums = RDM_SEMESTERS.map((smt) => modalGrid[subj]?.[String(smt)])
        .filter((v) => v !== '' && v !== undefined && !Number.isNaN(Number(v)))
        .map(Number);
    if (nums.length === 0) return '-';
    return (nums.reduce((a, b) => a + b, 0) / nums.length).toFixed(1);
};

const saveDetailModal = () => {
    if (!detailStudent.value) return;
    savingModal.value = true;
    router.put(
        `/rdm/${detailStudent.value.id}`,
        { scores: modalGrid },
        {
            preserveScroll: true,
            onSuccess: () => {
                detailStudent.value = null;
            },
            onFinish: () => {
                savingModal.value = false;
            },
        },
    );
};

const printSingleTranskrip = () => {
    if (!detailStudent.value) return;
    const html = buildTranskripRdmHtml(
        detailStudent.value,
        props.rdmScores,
        props.madrasahProfile,
    );
    openPrintWindowWithContent(
        html,
        `Transkrip RDM MI - ${detailStudent.value.nama}`,
    );
};

/* ------------------------- Bulk Print Modal ------------------------------- */

const bulkOpen = ref(false);
const bulkFilter = ref('AKTIF');
const bulkSort = ref<'AZ' | 'ROMBEL_AZ'>('AZ');

const bulkList = computed(() => {
    let list = [...props.students];
    if (bulkFilter.value === 'AKTIF') {
        list = list.filter((s) => s.statusSiswa === 'Aktif');
    } else if (bulkFilter.value === 'LULUS') {
        list = list.filter((s) => s.statusSiswa === 'Lulus');
    } else if (bulkFilter.value !== 'ALL') {
        list = list.filter((s) => s.rombel === bulkFilter.value);
    }

    return list.sort((a, b) => {
        if (bulkSort.value === 'ROMBEL_AZ') {
            const r = (a.rombel || '').localeCompare(b.rombel || '', 'id', {
                numeric: true,
            });
            if (r !== 0) return r;
        }
        return (a.nama || '').localeCompare(b.nama || '', 'id');
    });
});

const bulkHtml = computed(() =>
    bulkList.value
        .map((s) =>
            buildTranskripRdmHtml(s, props.rdmScores, props.madrasahProfile),
        )
        .join(''),
);

const printBulkTranskrip = () => {
    if (!bulkHtml.value) return;
    openPrintWindowWithContent(bulkHtml.value, 'Transkrip Nilai RDM MI Massal');
};

/* ------------------------- XLSX Template / Import / Export ---------------- */

const downloadRdmTemplate = () => {
    const smt = templateSmt.value;
    const actives = props.students.filter((s) => s.statusSiswa === 'Aktif');
    const source =
        actives.length > 0
            ? actives
            : [{ nisn: '0191234567', nama: 'CONTOH SISWA MI', rombel: '1-A' }];

    const rows = source.map((s) => {
        const row: Record<string, string | number> = {
            NISN: s.nisn,
            NAMA_SISWA: s.nama,
            ROMBEL: s.rombel,
            SEMESTER: smt,
        };
        RDM_SUBJECTS_MI.forEach((subj) => {
            row[subj] = '';
        });
        return row;
    });

    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Template_RDM_MI');
    XLSX.writeFile(wb, `Template_Import_RDM_MI_Smt${smt}.xlsx`);
};

const handleImportRdm = (event: Event) => {
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

            const entries = rows
                .map((r) => {
                    const nisn = String(r.NISN || '').trim();
                    const smt =
                        importSmt.value === 'AUTO'
                            ? Number(r.SEMESTER || 1)
                            : Number(importSmt.value);
                    if (!nisn || smt < 1 || smt > 12) return null;

                    const scores: Record<string, number> = {};
                    RDM_SUBJECTS_MI.forEach((subj) => {
                        const rawVal = subj.startsWith('IPAS')
                            ? r[subj] !== '' && r[subj] !== undefined
                                ? r[subj]
                                : (r.IPAS ?? '')
                            : r[subj];
                        if (
                            rawVal !== '' &&
                            rawVal !== undefined &&
                            !Number.isNaN(Number(rawVal))
                        ) {
                            scores[subj] = Number(rawVal);
                        }
                    });

                    return Object.keys(scores).length > 0
                        ? { nisn, semester: smt, scores }
                        : null;
                })
                .filter((x): x is NonNullable<typeof x> => Boolean(x));

            if (entries.length === 0) {
                toast.error('Tidak ada nilai RDM valid yang terbaca di Excel.');
                return;
            }

            router.post('/rdm/impor', { entries }, { preserveScroll: true });
        } catch {
            toast.error('Gagal membaca berkas Excel RDM.');
        } finally {
            input.value = '';
        }
    };
    reader.readAsArrayBuffer(file);
};

const exportAllRdmExcel = () => {
    const rows = props.students.map((s, idx) => {
        const avgs = RDM_SEMESTERS.map((smt) =>
            getStudentSemesterAvg(props.rdmScores, s.id, smt),
        );
        const valid = avgs.filter((v): v is number => v !== null);
        const overall = valid.length
            ? (valid.reduce((a, b) => a + b, 0) / valid.length).toFixed(2)
            : '';

        const row: Record<string, string | number> = {
            NO: idx + 1,
            NISN: s.nisn,
            NAMA_SISWA: s.nama,
            ROMBEL: s.rombel,
        };
        RDM_SEMESTERS.forEach((smt, i) => {
            row[`SMT_${smt}`] = avgs[i] !== null ? avgs[i]!.toFixed(2) : '';
        });
        row.RATA_RATA = overall;
        return row;
    });

    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Leger_RDM_MI');
    XLSX.writeFile(wb, 'Leger_RDM_MI_12Smt.xlsx');
};
</script>

<template>
    <Head title="Rapor Digital Madrasah (RDM MI)" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Kesiswaan & Asesmen · Kurikulum MI"
            title="Rapor Digital Madrasah (RDM) — 13 Mapel + IPAS"
            description="Impor nilai RDM via Excel, input atau edit nilai tiap mata pelajaran MI (Semester 1–12), serta cetak transkrip satuan & massal."
        >
            <template #actions>
                <Button
                    type="button"
                    size="sm"
                    class="bg-purple-700 text-white hover:bg-purple-800"
                    @click="bulkOpen = true"
                >
                    <Printer class="size-3.5" />
                    Cetak Transkrip Massal
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="exportAllRdmExcel"
                >
                    <FileSpreadsheet class="size-3.5" />
                    Ekspor Leger RDM (.xlsx)
                </Button>
            </template>
        </PageHeader>

        <!-- Import Bar -->
        <div
            class="grid grid-cols-1 items-end gap-3 rounded-2xl border border-border/70 bg-card p-4 text-xs shadow-xs md:grid-cols-3"
        >
            <div>
                <label class="mb-1 block font-bold"
                    >1. Unduh Template Excel RDM MI (13 Mapel)</label
                >
                <div class="flex gap-2">
                    <select
                        v-model.number="templateSmt"
                        class="flex-1 rounded-lg border border-input bg-background p-2 font-semibold"
                        @change="importSmt = String(templateSmt)"
                    >
                        <option
                            v-for="smt in RDM_SEMESTERS"
                            :key="smt"
                            :value="smt"
                        >
                            Semester {{ smt }} (Kls
                            {{ Math.ceil(smt / 2) }}
                            {{ smt % 2 === 1 ? 'Ganjil' : 'Genap' }})
                        </option>
                    </select>
                    <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        @click="downloadRdmTemplate"
                    >
                        <Download class="size-3.5" />
                        Template
                    </Button>
                </div>
            </div>

            <div>
                <label class="mb-1 block font-bold"
                    >2. Target Semester Impor</label
                >
                <select
                    v-model="importSmt"
                    class="w-full rounded-lg border border-input bg-background p-2 font-semibold"
                >
                    <option value="AUTO">Otomatis dari Kolom SEMESTER</option>
                    <option
                        v-for="smt in RDM_SEMESTERS"
                        :key="smt"
                        :value="String(smt)"
                    >
                        Semester {{ smt }}
                    </option>
                </select>
            </div>

            <div>
                <label class="mb-1 block font-bold"
                    >3. Unggah File Excel Nilai RDM MI</label
                >
                <input
                    ref="rdmFileRef"
                    type="file"
                    accept=".xlsx,.xls"
                    class="hidden"
                    @change="handleImportRdm"
                />
                <Button
                    type="button"
                    class="w-full bg-emerald-700 font-bold text-white hover:bg-emerald-800"
                    @click="rdmFileRef?.click()"
                >
                    <Upload class="size-3.5" />
                    Pilih &amp; Impor File RDM
                </Button>
            </div>
        </div>

        <!-- Table RDM -->
        <div
            class="overflow-hidden rounded-2xl border border-border/70 bg-card shadow-xs"
        >
            <div
                class="flex flex-col gap-3 border-b border-border/70 p-4 sm:flex-row"
            >
                <div class="relative flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Cari Nama Siswa atau NISN..."
                        class="pl-9"
                    />
                </div>
                <select
                    v-model="rombelFilter"
                    class="h-9 rounded-md border border-input bg-background px-3 text-xs"
                >
                    <option value="">Semua Rombel MI</option>
                    <option
                        v-for="code in allRombelCodes"
                        :key="code"
                        :value="code"
                    >
                        Kelas {{ code }}
                    </option>
                </select>
            </div>

            <div class="overflow-x-auto text-xs">
                <table class="w-full text-left">
                    <thead
                        class="border-b border-border/70 bg-muted/40 font-semibold"
                    >
                        <tr>
                            <th class="px-2.5 py-2.5">No</th>
                            <th class="px-3 py-2.5">Nama Siswa / NISN</th>
                            <th class="px-2.5 py-2.5">Rombel</th>
                            <th
                                v-for="smt in RDM_SEMESTERS"
                                :key="smt"
                                class="px-1.5 py-2.5 text-center"
                            >
                                S{{ smt }}
                            </th>
                            <th
                                class="bg-emerald-50/70 px-2.5 py-2.5 text-center text-emerald-900"
                            >
                                Rata2
                            </th>
                            <th class="px-3 py-2.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr v-if="filteredStudents.length === 0">
                            <td
                                colspan="17"
                                class="py-10 text-center text-muted-foreground"
                            >
                                Belum ada data siswa untuk ditampilkan.
                            </td>
                        </tr>
                        <tr
                            v-for="(s, idx) in filteredStudents"
                            :key="s.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-2.5 py-2 font-mono">{{ idx + 1 }}</td>
                            <td class="px-3 py-2">
                                <div class="font-bold uppercase">
                                    {{ s.nama }}
                                </div>
                                <div
                                    class="font-mono text-[10px] text-muted-foreground"
                                >
                                    {{ s.nisn || '-' }}
                                </div>
                            </td>
                            <td class="px-2.5 py-2 font-bold">
                                {{ s.rombel }}
                            </td>
                            <td
                                v-for="smt in RDM_SEMESTERS"
                                :key="smt"
                                class="px-1.5 py-2 text-center font-mono text-[11px]"
                            >
                                {{
                                    getStudentSemesterAvg(
                                        rdmScores,
                                        s.id,
                                        smt,
                                    ) !== null
                                        ? getStudentSemesterAvg(
                                              rdmScores,
                                              s.id,
                                              smt,
                                          )!.toFixed(1)
                                        : '-'
                                }}
                            </td>
                            <td
                                class="bg-emerald-50/60 px-2.5 py-2 text-center font-mono font-bold text-emerald-800"
                            >
                                {{ overallAverage(s.id) }}
                            </td>
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                <Button
                                    type="button"
                                    size="sm"
                                    class="h-7 bg-emerald-700 text-[11px] font-bold text-white hover:bg-emerald-800"
                                    @click="openDetail(s)"
                                >
                                    <GraduationCap class="size-3" />
                                    Input / Detail
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Detail & Edit Nilai RDM -->
        <Dialog
            :open="Boolean(detailStudent)"
            @update:open="(v) => !v && (detailStudent = null)"
        >
            <DialogContent
                class="flex max-h-[90vh] max-w-7xl flex-col overflow-hidden p-0"
            >
                <DialogHeader
                    class="flex flex-row flex-wrap items-center justify-between gap-2 border-b bg-emerald-900 px-5 py-3 text-white"
                >
                    <div class="text-xs">
                        <DialogTitle
                            class="text-sm font-bold text-white uppercase"
                        >
                            {{ detailStudent?.nama }}
                        </DialogTitle>
                        <DialogDescription class="text-xs text-emerald-100/80">
                            NISN: {{ detailStudent?.nisn || '-' }} | Rombel:
                            {{ detailStudent?.rombel || '-' }}
                        </DialogDescription>
                    </div>
                    <div class="mr-6 flex items-center gap-2">
                        <Button
                            type="button"
                            size="sm"
                            class="bg-purple-600 text-white hover:bg-purple-700"
                            @click="printSingleTranskrip"
                        >
                            <Printer class="size-3.5" />
                            Cetak Transkrip
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :disabled="savingModal"
                            class="bg-emerald-500 font-bold text-slate-950 hover:bg-emerald-400"
                            @click="saveDetailModal"
                        >
                            <LoaderCircle
                                v-if="savingModal"
                                class="size-3.5 animate-spin"
                            />
                            <Save v-else class="size-3.5" />
                            Simpan Nilai
                        </Button>
                    </div>
                </DialogHeader>
                <div class="flex-1 overflow-auto p-4 text-xs">
                    <table class="w-full border-collapse border border-border">
                        <thead class="bg-muted font-bold">
                            <tr>
                                <th class="w-8 border border-border p-1.5">
                                    No
                                </th>
                                <th
                                    class="min-w-[190px] border border-border p-1.5 text-left"
                                >
                                    Mata Pelajaran MI (Kurikulum Merdeka)
                                </th>
                                <th
                                    v-for="smt in RDM_SEMESTERS"
                                    :key="smt"
                                    class="border border-border p-1.5 text-center"
                                >
                                    S{{ smt }}
                                    <div
                                        class="text-[9px] font-normal text-muted-foreground"
                                    >
                                        K{{ Math.ceil(smt / 2) }}.{{
                                            smt % 2 === 1 ? 1 : 2
                                        }}
                                    </div>
                                </th>
                                <th
                                    class="border border-border bg-emerald-50/70 p-1.5 text-center text-emerald-900"
                                >
                                    Rata2
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(subj, idx) in RDM_SUBJECTS_MI"
                                :key="subj"
                            >
                                <td
                                    class="border border-border p-1.5 text-center"
                                >
                                    {{ idx + 1 }}
                                </td>
                                <td
                                    class="border border-border p-1.5 font-semibold"
                                >
                                    {{ subj }}
                                </td>
                                <td
                                    v-for="smt in RDM_SEMESTERS"
                                    :key="smt"
                                    class="border border-border p-1 text-center"
                                >
                                    <input
                                        v-model="modalGrid[subj][String(smt)]"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.1"
                                        class="w-12 rounded border border-input bg-background p-1 text-center font-mono text-[11px]"
                                    />
                                </td>
                                <td
                                    class="border border-border bg-emerald-50/50 p-1.5 text-center font-mono font-bold text-emerald-900"
                                >
                                    {{ subjectRowAvg(subj) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Modal Transkrip RDM Massal -->
        <Dialog :open="bulkOpen" @update:open="bulkOpen = $event">
            <DialogContent
                class="flex max-h-[90vh] max-w-5xl flex-col overflow-hidden p-0"
            >
                <DialogHeader
                    class="flex flex-row flex-wrap items-center justify-between gap-2 border-b bg-purple-900 px-5 py-3 text-white"
                >
                    <div>
                        <DialogTitle class="text-sm font-bold text-white">
                            Cetak Transkrip Nilai RDM MI Massal ({{
                                bulkList.length
                            }}
                            Lembar)
                        </DialogTitle>
                        <DialogDescription class="sr-only">
                            Pratinjau cetak massal transkrip nilai RDM
                        </DialogDescription>
                    </div>
                    <div class="mr-6 flex flex-wrap items-center gap-2 text-xs">
                        <select
                            v-model="bulkFilter"
                            class="rounded bg-white px-2 py-1 font-semibold text-slate-800"
                        >
                            <option value="ALL">Semua Siswa</option>
                            <option value="AKTIF">Siswa Aktif</option>
                            <option value="LULUS">Alumni / Lulus MI</option>
                            <option
                                v-for="code in allRombelCodes"
                                :key="code"
                                :value="code"
                            >
                                Kelas {{ code }}
                            </option>
                        </select>
                        <select
                            v-model="bulkSort"
                            class="rounded bg-white px-2 py-1 font-semibold text-slate-800"
                        >
                            <option value="AZ">Alfabet (A - Z)</option>
                            <option value="ROMBEL_AZ">Rombel + Alfabet</option>
                        </select>
                        <Button
                            type="button"
                            size="sm"
                            class="bg-emerald-500 font-bold text-slate-950 hover:bg-emerald-400"
                            @click="printBulkTranskrip"
                        >
                            <Printer class="size-3.5" />
                            Cetak Semua
                        </Button>
                    </div>
                </DialogHeader>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <div
                    class="flex-1 space-y-4 overflow-y-auto bg-slate-200 p-5"
                    v-html="bulkHtml"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
