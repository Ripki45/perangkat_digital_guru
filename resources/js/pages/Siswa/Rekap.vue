<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Archive,
    Award,
    BarChart3,
    Download,
    FileSpreadsheet,
    Home,
    Printer,
    ShieldCheck,
    Trash2,
    Upload,
    UserMinus,
    Users,
} from '@lucide/vue';
import Chart from 'chart.js/auto';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import * as XLSX from 'xlsx';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import StatCard from '@/components/app/StatCard.vue';
import {
    buildBukuIndukSheetHtml,
    buildKtsCardHtml,
    buildNominatifTableHtml,
    buildTranskripRdmHtml,
    openPrintWindowWithContent,
} from '@/components/sim-emis/EmisPrintHelpers';
import { Button } from '@/components/ui/button';
import {
    calculateAge,
    GURU_STATUS_PEGAWAI_LIST,
    validateEmisStudent,
} from '@/types/emis';
import type {
    EmisGuru,
    EmisMadrasahProfile,
    EmisRombel,
    EmisStudent,
    RdmScoresMap,
} from '@/types/emis';

const props = defineProps<{
    students: EmisStudent[];
    gurus: EmisGuru[];
    rombels: EmisRombel[];
    rdmScores: RdmScoresMap;
    madrasahProfile: EmisMadrasahProfile;
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'Rekap & Cetak Massal EMIS', href: '/rekap-emis' },
        ],
    }),
});

/* ------------------------- Overview Stats --------------------------------- */

const activeStudents = computed(() =>
    props.students.filter((s) => s.statusSiswa === 'Aktif'),
);

const activeL = computed(
    () => activeStudents.value.filter((s) => s.jk === 'L').length,
);

const activeP = computed(
    () => activeStudents.value.filter((s) => s.jk === 'P').length,
);

const pipCount = computed(
    () => activeStudents.value.filter((s) => s.statusPip === 'Ya').length,
);

const pipPercent = computed(() =>
    activeStudents.value.length > 0
        ? Math.round((pipCount.value / activeStudents.value.length) * 100)
        : 0,
);

const mukimCount = computed(
    () =>
        activeStudents.value.filter(
            (s) =>
                (s.tempatTinggal || '').includes('Pesantren') ||
                Boolean(s.namaPondok),
        ).length,
);

const nonAktifCount = computed(
    () => props.students.filter((s) => s.statusSiswa !== 'Aktif').length,
);

const validationSummary = computed(() => {
    const incomplete: Array<{ student: EmisStudent; missing: string[] }> = [];
    let validCount = 0;

    props.students.forEach((s) => {
        const res = validateEmisStudent(s);
        if (res.isValid) {
            validCount++;
        } else {
            incomplete.push({ student: s, missing: res.missing });
        }
    });

    const percent =
        props.students.length > 0
            ? Math.round((validCount / props.students.length) * 100)
            : 100;

    return { validCount, percent, incomplete };
});

/* ------------------------- Rombel Breakdown ------------------------------- */

const rombelSummary = computed(() => {
    const codes = Array.from(
        new Set([
            ...props.rombels.map((r) => r.kode),
            ...activeStudents.value.map((s) => s.rombel).filter(Boolean),
        ]),
    ).sort((a, b) => a.localeCompare(b, 'id', { numeric: true }));

    return codes.map((kode) => {
        const meta = props.rombels.find((r) => r.kode === kode);
        const list = activeStudents.value.filter((s) => s.rombel === kode);
        const l = list.filter((s) => s.jk === 'L').length;
        const p = list.filter((s) => s.jk === 'P').length;
        return {
            kode,
            tingkat: meta?.tingkat || kode.split('-')[0] || '-',
            wali: meta?.wali || '-',
            l,
            p,
            total: l + p,
        };
    });
});

const exportRekapRombelXlsx = () => {
    const rows = rombelSummary.value.map((r, i) => ({
        NO: i + 1,
        TINGKAT: `Kelas ${r.tingkat}`,
        ROMBEL: r.kode,
        WALI_KELAS: r.wali,
        LAKI_LAKI: r.l,
        PEREMPUAN: r.p,
        TOTAL_AKTIF: r.total,
    }));
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Rekap_Rombel_MI');
    XLSX.writeFile(wb, 'Rekap_Rombel_EMIS_MI.xlsx');
};

/* ------------------------- Age Matrix (MI Kelas 1-6) ---------------------- */

const ageBuckets = [
    { key: '<7', label: '< 7 Tahun', test: (a: number) => a < 7 },
    { key: '7', label: '7 Tahun', test: (a: number) => a === 7 },
    { key: '8', label: '8 Tahun', test: (a: number) => a === 8 },
    { key: '9', label: '9 Tahun', test: (a: number) => a === 9 },
    { key: '10', label: '10 Tahun', test: (a: number) => a === 10 },
    { key: '11', label: '11 Tahun', test: (a: number) => a === 11 },
    { key: '12', label: '12 Tahun', test: (a: number) => a === 12 },
    { key: '>12', label: '> 12 Tahun', test: (a: number) => a > 12 },
];

const tingkatList = ['1', '2', '3', '4', '5', '6'];

const ageMatrix = computed(() => {
    const totalAktif = activeStudents.value.length;
    return ageBuckets.map((bucket) => {
        const byTingkat: Record<string, { l: number; p: number }> = {};
        tingkatList.forEach((t) => {
            byTingkat[t] = { l: 0, p: 0 };
        });

        let rowL = 0;
        let rowP = 0;
        activeStudents.value.forEach((s) => {
            const age = calculateAge(s.tglLahir);
            if (age === null || !bucket.test(age)) return;
            const tingkat = String(s.tingkat || s.rombel?.split('-')[0] || '');
            if (!byTingkat[tingkat]) return;
            if (s.jk === 'P') {
                byTingkat[tingkat].p++;
                rowP++;
            } else {
                byTingkat[tingkat].l++;
                rowL++;
            }
        });

        const total = rowL + rowP;
        const pct =
            totalAktif > 0 ? ((total / totalAktif) * 100).toFixed(1) : '0.0';
        return {
            key: bucket.key,
            label: bucket.label,
            byTingkat,
            rowL,
            rowP,
            total,
            pct,
        };
    });
});

/* ------------------------- Mini Rekap Tables ------------------------------ */

const groupByField = (
    list: EmisStudent[],
    extractor: (s: EmisStudent) => string,
) => {
    const map = new Map<string, number>();
    list.forEach((s) => {
        const label = extractor(s) || 'Belum Diisi';
        map.set(label, (map.get(label) ?? 0) + 1);
    });
    return Array.from(map.entries())
        .sort((a, b) => b[1] - a[1])
        .map(([label, count]) => ({ label, count }));
};

const rekapTinggal = computed(() =>
    groupByField(activeStudents.value, (s) => s.tempatTinggal),
);
const rekapAsal = computed(() =>
    groupByField(
        activeStudents.value,
        (s) => s.jenisPendaftaran || s.asalSekolah || 'Belum Diisi',
    ),
);
const rekapStatus = computed(() =>
    groupByField(props.students, (s) => s.statusSiswa || 'Aktif'),
);

const rekapGtk = computed(() => {
    const aktif = props.gurus.filter(
        (g) => (g.statusAktif || 'Aktif') === 'Aktif',
    );
    return GURU_STATUS_PEGAWAI_LIST.map((st) => {
        const sub = aktif.filter((g) => g.statusPegawai === st);
        return {
            status: st,
            total: sub.length,
            sert: sub.filter((g) => g.statusSertifikasi === 'Sudah Sertifikasi')
                .length,
        };
    });
});

/* ------------------------- Chart.js Charts -------------------------------- */

const rombelChartCanvas = ref<HTMLCanvasElement | null>(null);
const statusChartCanvas = ref<HTMLCanvasElement | null>(null);
let rombelChartInstance: Chart | null = null;
let statusChartInstance: Chart | null = null;

const destroyCharts = () => {
    rombelChartInstance?.destroy();
    rombelChartInstance = null;
    statusChartInstance?.destroy();
    statusChartInstance = null;
};

const renderCharts = () => {
    destroyCharts();

    if (rombelChartCanvas.value) {
        rombelChartInstance = new Chart(rombelChartCanvas.value, {
            type: 'bar',
            data: {
                labels: rombelSummary.value.map((r) => `Kelas ${r.kode}`),
                datasets: [
                    {
                        label: 'Laki-laki',
                        data: rombelSummary.value.map((r) => r.l),
                        backgroundColor: '#0ea5e9',
                        borderRadius: 4,
                    },
                    {
                        label: 'Perempuan',
                        data: rombelSummary.value.map((r) => r.p),
                        backgroundColor: '#ec4899',
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });
    }

    if (statusChartCanvas.value) {
        const statuses = ['Aktif', 'Mutasi Keluar', 'Lulus', 'Putus Sekolah'];
        const counts = statuses.map(
            (st) => props.students.filter((s) => s.statusSiswa === st).length,
        );
        statusChartInstance = new Chart(statusChartCanvas.value, {
            type: 'doughnut',
            data: {
                labels: statuses,
                datasets: [
                    {
                        data: counts,
                        backgroundColor: [
                            '#10b981',
                            '#f59e0b',
                            '#3b82f6',
                            '#ef4444',
                        ],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
            },
        });
    }
};

onMounted(renderCharts);
watch([() => props.students, () => props.rombels], renderCharts, {
    deep: true,
});
onBeforeUnmount(destroyCharts);

/* ------------------------- Cetak Massal Terpadu --------------------------- */

const cmDocType = ref<'KTS' | 'NOMINATIF' | 'BUKU_INDUK' | 'TRANSKRIP_RDM'>(
    'KTS',
);
const cmFilterRombel = ref('AKTIF');
const cmSortBy = ref<'AZ' | 'ZA' | 'ROMBEL_AZ' | 'NIS'>('AZ');

const allRombelFilterOptions = computed(() =>
    rombelSummary.value.map((r) => r.kode),
);

const filteredBulkStudents = computed(() => {
    let list = [...props.students];
    if (cmFilterRombel.value === 'AKTIF') {
        list = list.filter((s) => s.statusSiswa === 'Aktif');
    } else if (cmFilterRombel.value === 'LULUS') {
        list = list.filter((s) => s.statusSiswa === 'Lulus');
    } else if (cmFilterRombel.value !== 'ALL') {
        list = list.filter((s) => s.rombel === cmFilterRombel.value);
    }

    return list.sort((a, b) => {
        if (cmSortBy.value === 'ZA') {
            return (b.nama || '').localeCompare(a.nama || '', 'id');
        }
        if (cmSortBy.value === 'NIS') {
            return (a.nisn || '').localeCompare(b.nisn || '', 'id');
        }
        if (cmSortBy.value === 'ROMBEL_AZ') {
            const r = (a.rombel || '').localeCompare(b.rombel || '', 'id', {
                numeric: true,
            });
            if (r !== 0) return r;
        }
        return (a.nama || '').localeCompare(b.nama || '', 'id');
    });
});

const bulkPreviewHtml = computed(() => {
    const list = filteredBulkStudents.value;
    if (list.length === 0) {
        return '<div class="py-8 text-center text-sm text-slate-400">Tidak ada siswa yang sesuai dengan filter cetak.</div>';
    }
    if (cmDocType.value === 'BUKU_INDUK') {
        return list
            .map((s) => buildBukuIndukSheetHtml(s, props.madrasahProfile))
            .join('');
    }
    if (cmDocType.value === 'TRANSKRIP_RDM') {
        return list
            .map((s) =>
                buildTranskripRdmHtml(
                    s,
                    props.rdmScores,
                    props.madrasahProfile,
                ),
            )
            .join('');
    }
    if (cmDocType.value === 'NOMINATIF') {
        return buildNominatifTableHtml(list, props.madrasahProfile);
    }
    return `<div class="grid grid-cols-1 md:grid-cols-2 gap-3">${list
        .map((s) => buildKtsCardHtml(s, props.madrasahProfile))
        .join('')}</div>`;
});

const printBulkDocument = () => {
    if (filteredBulkStudents.value.length === 0) {
        toast.error('Tidak ada data siswa untuk dicetak.');
        return;
    }
    const labels: Record<typeof cmDocType.value, string> = {
        KTS: 'Cetak Massal KTS',
        NOMINATIF: 'Daftar Nominatif Peserta Didik',
        BUKU_INDUK: 'Buku Induk Peserta Didik A4',
        TRANSKRIP_RDM: 'Transkrip Nilai RDM MI',
    };
    openPrintWindowWithContent(bulkPreviewHtml.value, labels[cmDocType.value]);
};

/* ------------------------- JSON Backup / Restore / Clear ------------------ */

const backupFileRef = ref<HTMLInputElement | null>(null);
const restoring = ref(false);
const confirmClearOpen = ref(false);
const clearing = ref(false);

const exportJsonBackup = () => {
    const payload = {
        exportedAt: new Date().toISOString(),
        madrasahProfile: props.madrasahProfile,
        customRombels: props.rombels,
        students: props.students,
        gurus: props.gurus,
        rdmScores: props.rdmScores,
    };
    const blob = new Blob([JSON.stringify(payload, null, 2)], {
        type: 'application/json',
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Backup_SIM_EMIS_MI_${new Date().toISOString().slice(0, 10)}.json`;
    a.click();
    URL.revokeObjectURL(url);
    toast.success('Cadangan JSON lengkap berhasil diunduh.');
};

const handleRestoreJson = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const parsed = JSON.parse(String(e.target?.result || '{}'));
            if (typeof parsed !== 'object' || parsed === null) {
                throw new Error('Invalid JSON');
            }
            restoring.value = true;
            router.post('/rekap-emis/restore', parsed, {
                preserveScroll: true,
                onFinish: () => {
                    restoring.value = false;
                },
            });
        } catch {
            toast.error('File cadangan JSON tidak valid.');
        } finally {
            input.value = '';
        }
    };
    reader.readAsText(file);
};

const executeClearAll = () => {
    clearing.value = true;
    router.delete('/rekap-emis/clear', {
        preserveScroll: true,
        onFinish: () => {
            clearing.value = false;
            confirmClearOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="Rekap & Cetak Massal EMIS" />

    <div class="space-y-6">
        <PageHeader
            badge="EMIS 4.0 Analytics & Print Center"
            title="Rekapitulasi Statistik, Cetak Massal & Cadangan"
            subtitle="Dashboard grafik rombel MI, matriks kelompok usia, validasi data EMIS, pencetakan massal, dan backup JSON penuh."
        >
            <template #actions>
                <Button variant="outline" size="sm" @click="exportJsonBackup">
                    <Archive class="size-4" />
                    Ekspor Backup JSON
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="restoring"
                    @click="backupFileRef?.click()"
                >
                    <Upload class="size-4" />
                    Pulihkan JSON
                </Button>
                <input
                    ref="backupFileRef"
                    type="file"
                    accept="application/json,.json"
                    class="hidden"
                    @change="handleRestoreJson"
                />
                <Button
                    variant="destructive"
                    size="sm"
                    :disabled="clearing"
                    @click="confirmClearOpen = true"
                >
                    <Trash2 class="size-4" />
                    Kosongkan Data
                </Button>
            </template>
        </PageHeader>

        <!-- 5 Key StatCards -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <StatCard
                label="Siswa Aktif"
                :value="activeStudents.length"
                :hint="`L: ${activeL} | P: ${activeP}`"
                :icon="Users"
                tone="emerald"
            />
            <StatCard
                label="Penerima PIP"
                :value="pipCount"
                :hint="`${pipPercent}% dari aktif`"
                :icon="Award"
                tone="gold"
            />
            <StatCard
                label="Santri Mukim"
                :value="mukimCount"
                hint="Tinggal di Pondok"
                :icon="Home"
                tone="sky"
            />
            <StatCard
                label="Keluar / Lulus"
                :value="nonAktifCount"
                hint="Mutasi & Alumni"
                :icon="UserMinus"
                tone="rose"
            />
            <StatCard
                label="Validasi EMIS"
                :value="`${validationSummary.percent}%`"
                :hint="`${validationSummary.validCount}/${students.length} valid`"
                :icon="ShieldCheck"
                tone="emerald"
            />
        </div>

        <!-- Charts Row -->
        <div class="grid gap-4 lg:grid-cols-3">
            <section
                class="rounded-2xl border border-border/80 bg-card p-5 lg:col-span-2"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h2
                        class="flex items-center gap-2 text-sm font-semibold text-foreground"
                    >
                        <BarChart3 class="size-4 text-[#107c41]" />
                        Sebaran Siswa Aktif per Rombel MI (L / P)
                    </h2>
                </div>
                <div class="h-64">
                    <canvas ref="rombelChartCanvas" />
                </div>
            </section>
            <section class="rounded-2xl border border-border/80 bg-card p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-foreground">
                        Proporsi Status Peserta Didik
                    </h2>
                </div>
                <div class="h-64">
                    <canvas ref="statusChartCanvas" />
                </div>
            </section>
        </div>

        <!-- Incomplete EMIS Validation Alert Table -->
        <section
            v-if="validationSummary.incomplete.length > 0"
            class="rounded-2xl border border-amber-300/70 bg-amber-50/50 p-5"
        >
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2
                        class="flex items-center gap-2 text-sm font-bold text-amber-900"
                    >
                        <AlertTriangle class="size-4 text-amber-600" />
                        Peringatan Kelengkapan Data EMIS ({{
                            validationSummary.incomplete.length
                        }}
                        Siswa)
                    </h2>
                    <p class="text-xs text-amber-800/80">
                        Kolom wajib EMIS (Nama, NISN 10 digit, NIK 16 digit,
                        TTL, Ibu Kandung, Alamat) yang belum lengkap.
                    </p>
                </div>
                <Button size="sm" variant="outline" as-child>
                    <Link href="/siswa">Perbaiki di Data Siswa</Link>
                </Button>
            </div>
            <div
                class="overflow-x-auto rounded-xl border border-amber-200 bg-white"
            >
                <table class="w-full text-left text-xs">
                    <thead class="bg-amber-100/70 text-[11px] uppercase">
                        <tr>
                            <th class="px-3 py-2">Nama Siswa</th>
                            <th class="px-3 py-2">Rombel</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Field Wajib Belum Terisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-100">
                        <tr
                            v-for="item in validationSummary.incomplete"
                            :key="item.student.id"
                        >
                            <td class="px-3 py-2 font-semibold">
                                {{ item.student.nama }}
                            </td>
                            <td class="px-3 py-2">
                                Kelas {{ item.student.rombel || '-' }}
                            </td>
                            <td class="px-3 py-2">
                                {{ item.student.statusSiswa }}
                            </td>
                            <td class="px-3 py-2 text-rose-700">
                                {{ item.missing.join(', ') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Rombel Rekap Table -->
        <section class="rounded-2xl border border-border/80 bg-card p-5">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-bold text-foreground">
                    Rekapitulasi Siswa Aktif per Rombel MI
                </h2>
                <Button
                    size="sm"
                    variant="outline"
                    @click="exportRekapRombelXlsx"
                >
                    <FileSpreadsheet class="size-4" />
                    Ekspor XLSX Rombel
                </Button>
            </div>
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full text-left text-xs">
                    <thead class="bg-muted/60 text-[11px] uppercase">
                        <tr>
                            <th class="px-3 py-2.5">Rombel</th>
                            <th class="px-3 py-2.5">Tingkat</th>
                            <th class="px-3 py-2.5">Wali Kelas</th>
                            <th class="px-3 py-2.5 text-center">L</th>
                            <th class="px-3 py-2.5 text-center">P</th>
                            <th class="px-3 py-2.5 text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/70">
                        <tr v-for="r in rombelSummary" :key="r.kode">
                            <td class="px-3 py-2 font-semibold">
                                Kelas {{ r.kode }}
                            </td>
                            <td class="px-3 py-2">Tingkat {{ r.tingkat }}</td>
                            <td class="px-3 py-2">{{ r.wali }}</td>
                            <td class="px-3 py-2 text-center font-mono">
                                {{ r.l }}
                            </td>
                            <td class="px-3 py-2 text-center font-mono">
                                {{ r.p }}
                            </td>
                            <td
                                class="px-3 py-2 text-center font-mono font-bold text-[#107c41]"
                            >
                                {{ r.total }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Age Matrix Table -->
        <section class="rounded-2xl border border-border/80 bg-card p-5">
            <h2 class="mb-3 text-sm font-bold text-foreground">
                Matriks Kelompok Usia Peserta Didik Aktif MI (Kelas 1 - 6)
            </h2>
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full border-collapse text-center text-[11px]">
                    <thead class="bg-muted/70 font-semibold uppercase">
                        <tr>
                            <th
                                rowspan="2"
                                class="border border-border px-2 py-2 text-left"
                            >
                                Kelompok Usia
                            </th>
                            <th
                                v-for="t in tingkatList"
                                :key="t"
                                colspan="2"
                                class="border border-border px-2 py-1"
                            >
                                Kelas {{ t }}
                            </th>
                            <th
                                colspan="3"
                                class="border border-border px-2 py-1"
                            >
                                Total
                            </th>
                            <th
                                rowspan="2"
                                class="border border-border px-2 py-2"
                            >
                                %
                            </th>
                        </tr>
                        <tr>
                            <template v-for="t in tingkatList" :key="`lp-${t}`">
                                <th class="border border-border px-1.5 py-1">
                                    L
                                </th>
                                <th class="border border-border px-1.5 py-1">
                                    P
                                </th>
                            </template>
                            <th class="border border-border px-1.5 py-1">L</th>
                            <th class="border border-border px-1.5 py-1">P</th>
                            <th class="border border-border px-1.5 py-1">
                                Jml
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in ageMatrix"
                            :key="row.key"
                            class="hover:bg-muted/30"
                        >
                            <td
                                class="border border-border px-2 py-1.5 text-left font-medium"
                            >
                                {{ row.label }}
                            </td>
                            <template
                                v-for="t in tingkatList"
                                :key="`${row.key}-${t}`"
                            >
                                <td
                                    class="border border-border px-1.5 py-1 font-mono"
                                >
                                    {{ row.byTingkat[t].l || '-' }}
                                </td>
                                <td
                                    class="border border-border px-1.5 py-1 font-mono"
                                >
                                    {{ row.byTingkat[t].p || '-' }}
                                </td>
                            </template>
                            <td
                                class="border border-border px-1.5 py-1 font-mono"
                            >
                                {{ row.rowL }}
                            </td>
                            <td
                                class="border border-border px-1.5 py-1 font-mono"
                            >
                                {{ row.rowP }}
                            </td>
                            <td
                                class="border border-border px-1.5 py-1 font-mono font-bold text-[#107c41]"
                            >
                                {{ row.total }}
                            </td>
                            <td
                                class="border border-border px-1.5 py-1 font-mono"
                            >
                                {{ row.pct }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Mini Distribution & GTK Tables -->
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-2xl border border-border/80 bg-card p-4">
                <h3
                    class="mb-2 text-xs font-bold text-muted-foreground uppercase"
                >
                    Tempat Tinggal (Aktif)
                </h3>
                <div class="space-y-1.5 text-xs">
                    <div
                        v-for="item in rekapTinggal"
                        :key="item.label"
                        class="flex items-center justify-between border-b border-border/50 pb-1"
                    >
                        <span class="truncate pr-2">{{ item.label }}</span>
                        <span class="font-mono font-bold">{{
                            item.count
                        }}</span>
                    </div>
                </div>
            </section>
            <section class="rounded-2xl border border-border/80 bg-card p-4">
                <h3
                    class="mb-2 text-xs font-bold text-muted-foreground uppercase"
                >
                    Asal / Jenis Pendaftaran
                </h3>
                <div class="space-y-1.5 text-xs">
                    <div
                        v-for="item in rekapAsal"
                        :key="item.label"
                        class="flex items-center justify-between border-b border-border/50 pb-1"
                    >
                        <span class="truncate pr-2">{{ item.label }}</span>
                        <span class="font-mono font-bold">{{
                            item.count
                        }}</span>
                    </div>
                </div>
            </section>
            <section class="rounded-2xl border border-border/80 bg-card p-4">
                <h3
                    class="mb-2 text-xs font-bold text-muted-foreground uppercase"
                >
                    Status Keaktifan Siswa
                </h3>
                <div class="space-y-1.5 text-xs">
                    <div
                        v-for="item in rekapStatus"
                        :key="item.label"
                        class="flex items-center justify-between border-b border-border/50 pb-1"
                    >
                        <span class="truncate pr-2">{{ item.label }}</span>
                        <span class="font-mono font-bold">{{
                            item.count
                        }}</span>
                    </div>
                </div>
            </section>
            <section class="rounded-2xl border border-border/80 bg-card p-4">
                <h3
                    class="mb-2 text-xs font-bold text-muted-foreground uppercase"
                >
                    Rekap Status GTK Aktif
                </h3>
                <div class="space-y-1.5 text-xs">
                    <div
                        v-for="item in rekapGtk"
                        :key="item.status"
                        class="flex items-center justify-between border-b border-border/50 pb-1"
                    >
                        <span class="truncate pr-2">{{ item.status }}</span>
                        <span class="font-mono font-bold">
                            {{ item.total }} ({{ item.sert }} Sert.)
                        </span>
                    </div>
                </div>
            </section>
        </div>

        <!-- Cetak Massal Dokumen Terpadu -->
        <section
            class="space-y-4 rounded-2xl border border-border/80 bg-card p-5"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-foreground">
                        Cetak Massal Dokumen Terpadu
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Cetak Kartu Tanda Siswa (KTS), Daftar Nominatif, Buku
                        Induk A4, atau Transkrip Nilai RDM secara massal ({{
                            filteredBulkStudents.length
                        }}
                        siswa terpilih).
                    </p>
                </div>
                <Button size="sm" @click="printBulkDocument">
                    <Printer class="size-4" />
                    Cetak Sekarang
                </Button>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium">
                        Jenis Dokumen
                    </label>
                    <select
                        v-model="cmDocType"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-xs"
                    >
                        <option value="KTS">Kartu Tanda Siswa (KTS)</option>
                        <option value="NOMINATIF">
                            Daftar Nominatif & Presensi
                        </option>
                        <option value="BUKU_INDUK">
                            Lembar Buku Induk Siswa (A4)
                        </option>
                        <option value="TRANSKRIP_RDM">
                            Transkrip Nilai RDM MI (Smt 1 - 12)
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">
                        Filter Siswa / Rombel
                    </label>
                    <select
                        v-model="cmFilterRombel"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-xs"
                    >
                        <option value="AKTIF">Semua Siswa Aktif</option>
                        <option value="ALL">
                            Semua Siswa (Termasuk Alumni)
                        </option>
                        <option value="LULUS">Alumni / Lulus</option>
                        <option
                            v-for="kode in allRombelFilterOptions"
                            :key="kode"
                            :value="kode"
                        >
                            Kelas {{ kode }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">
                        Urutkan
                    </label>
                    <select
                        v-model="cmSortBy"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-xs"
                    >
                        <option value="AZ">Nama A - Z</option>
                        <option value="ZA">Nama Z - A</option>
                        <option value="ROMBEL_AZ">Rombel lalu Nama</option>
                        <option value="NIS">Nomor NISN</option>
                    </select>
                </div>
            </div>

            <div
                class="max-h-[460px] overflow-y-auto rounded-xl border border-border bg-slate-100 p-4"
                v-html="bulkPreviewHtml"
            />
        </section>

        <!-- Database Backup Footer Card -->
        <section class="rounded-2xl border border-border/80 bg-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-foreground">
                        Cadangan & Pemulihan Database (JSON)
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Cadangkan seluruh data siswa, rombel, GTK, nilai RDM,
                        dan profil madrasah ke berkas `.json` atau pulihkan dari
                        cadangan sebelumnya.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="exportJsonBackup"
                    >
                        <Download class="size-4" />
                        Unduh Backup JSON
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="restoring"
                        @click="backupFileRef?.click()"
                    >
                        <Upload class="size-4" />
                        {{ restoring ? 'Memulihkan...' : 'Impor Backup JSON' }}
                    </Button>
                </div>
            </div>
        </section>
    </div>

    <ConfirmDialog
        v-model:open="confirmClearOpen"
        title="Kosongkan Seluruh Data EMIS?"
        description="Tindakan ini akan menghapus seluruh data Siswa, GTK, dan Nilai RDM milik akun Anda dari database. Pastikan Anda sudah mengunduh Backup JSON terlebih dahulu."
        confirm-label="Ya, Kosongkan Semua"
        variant="destructive"
        :loading="clearing"
        @confirm="executeClearAll"
    />
</template>
