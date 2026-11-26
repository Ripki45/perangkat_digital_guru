<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Award,
    Database,
    Download,
    FileSpreadsheet,
    GraduationCap,
    LoaderCircle,
    Pencil,
    Search,
    ShieldCheck,
    Trash2,
    Upload,
    UserPlus,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import * as XLSX from 'xlsx';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import StatCard from '@/components/app/StatCard.vue';
import GuruFormModal from '@/components/sim-emis/GuruFormModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatNamaGelarGuru, GURU_STATUS_PEGAWAI_LIST } from '@/types/emis';
import type { EmisGuru } from '@/types/emis';

const props = defineProps<{
    gurus: EmisGuru[];
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'Data Guru & GTK (EMIS 4.0)', href: '/gtk' },
        ],
    }),
});

const search = ref('');
const pegawaiFilter = ref('');
const sertifikasiFilter = ref('');
const formOpen = ref(false);
const editingGuru = ref<EmisGuru | null>(null);
const deletingGuru = ref<EmisGuru | null>(null);
const deletingProcessing = ref(false);
const loadingSample = ref(false);
const xlsxInputRef = ref<HTMLInputElement | null>(null);

const activeGurus = computed(() =>
    props.gurus.filter((g) => (g.statusAktif || 'Aktif') === 'Aktif'),
);

const stats = computed(() => {
    const aktif = activeGurus.value;
    const l = aktif.filter((g) => g.jk === 'L').length;
    const p = aktif.filter((g) => g.jk === 'P').length;
    const asn = aktif.filter(
        (g) => g.statusPegawai === 'PNS' || g.statusPegawai === 'PPPK',
    ).length;
    const sert = aktif.filter(
        (g) => g.statusSertifikasi === 'Sudah Sertifikasi',
    ).length;
    const inp = aktif.filter((g) => g.inpassing === 'Sudah').length;
    const s1 = aktif.filter((g) =>
        ['D4/S1', 'S2', 'S3'].includes(g.pendidikan),
    ).length;

    return {
        total: aktif.length,
        genderHint: `${l} L / ${p} P`,
        asn,
        nonAsnHint: `Non-ASN: ${aktif.length - asn}`,
        sert,
        inpassingHint: `Inpassing: ${inp}`,
        s1,
    };
});

const rekapStatusRows = computed(() =>
    GURU_STATUS_PEGAWAI_LIST.map((st) => {
        const sub = activeGurus.value.filter((g) => g.statusPegawai === st);
        const l = sub.filter((g) => g.jk === 'L').length;
        const p = sub.filter((g) => g.jk === 'P').length;
        const sert = sub.filter(
            (g) => g.statusSertifikasi === 'Sudah Sertifikasi',
        ).length;
        const jml = sub.length;
        return {
            status: st,
            l,
            p,
            jml,
            sert,
            belum: jml - sert,
            pct: jml > 0 ? Math.round((sert / jml) * 100) : 0,
        };
    }),
);

const rekapTotals = computed(() => {
    const rows = rekapStatusRows.value;
    const l = rows.reduce((a, r) => a + r.l, 0);
    const p = rows.reduce((a, r) => a + r.p, 0);
    const jml = rows.reduce((a, r) => a + r.jml, 0);
    const sert = rows.reduce((a, r) => a + r.sert, 0);
    const belum = rows.reduce((a, r) => a + r.belum, 0);
    return {
        l,
        p,
        jml,
        sert,
        belum,
        pct: jml > 0 ? Math.round((sert / jml) * 100) : 0,
    };
});

const filteredGurus = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.gurus.filter((g) => {
        const fullName = formatNamaGelarGuru(g).toLowerCase();
        const matchQ =
            !q ||
            fullName.includes(q) ||
            (g.npk || '').toLowerCase().includes(q) ||
            (g.nuptk || '').toLowerCase().includes(q) ||
            (g.pegid || '').toLowerCase().includes(q) ||
            (g.nip || '').toLowerCase().includes(q) ||
            (g.nik || '').toLowerCase().includes(q) ||
            (g.mapelUtama || '').toLowerCase().includes(q);
        const matchPeg =
            !pegawaiFilter.value || g.statusPegawai === pegawaiFilter.value;
        const matchSert =
            !sertifikasiFilter.value ||
            g.statusSertifikasi === sertifikasiFilter.value;
        return matchQ && matchPeg && matchSert;
    });
});

const openCreate = () => {
    editingGuru.value = null;
    formOpen.value = true;
};

const openEdit = (g: EmisGuru) => {
    editingGuru.value = g;
    formOpen.value = true;
};

const confirmDelete = () => {
    if (!deletingGuru.value) return;
    deletingProcessing.value = true;
    router.delete(`/gtk/${deletingGuru.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingProcessing.value = false;
            deletingGuru.value = null;
        },
    });
};

const loadSampleGurus = () => {
    loadingSample.value = true;
    router.post(
        '/gtk/sampel',
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                loadingSample.value = false;
            },
        },
    );
};

const exportGuruExcelXLSX = () => {
    const rows = props.gurus.map((g, idx) => ({
        NO: idx + 1,
        GELAR_DEPAN: g.gelarDepan || '',
        NAMA_LENGKAP: g.nama || '',
        GELAR_BELAKANG: g.gelarBelakang || '',
        NIK: g.nik || '',
        NO_KK: g.noKK || '',
        JENIS_KELAMIN: g.jk || '',
        AGAMA: g.agama || '',
        TEMPAT_LAHIR: g.tempatLahir || '',
        TANGGAL_LAHIR: g.tglLahir || '',
        NAMA_IBU_KANDUNG: g.namaIbu || '',
        STATUS_PERKAWINAN: g.statusNikah || '',
        NO_HP: g.hp || '',
        EMAIL: g.email || '',
        ALAMAT: g.alamat || '',
        PEGID: g.pegid || '',
        NPK: g.npk || '',
        NUPTK: g.nuptk || '',
        NIP: g.nip || '',
        STATUS_PEGAWAI: g.statusPegawai || '',
        JENIS_PTK: g.jenisPtk || '',
        GOLONGAN: g.golongan || '',
        TMT_AWAL: g.tmtAwal || '',
        TMT_MADRASAH: g.tmtMadrasah || '',
        INPASSING: g.inpassing || '',
        STATUS_AKTIF: g.statusAktif || 'Aktif',
        NO_SK: g.noSk || '',
        PENDIDIKAN_TERAKHIR: g.pendidikan || '',
        PROGRAM_STUDI: g.prodi || '',
        PERGURUAN_TINGGI: g.kampus || '',
        TAHUN_LULUS: g.tahunLulus || '',
        STATUS_SERTIFIKASI: g.statusSertifikasi || '',
        NRG: g.nrg || '',
        NO_SERTIFIKAT_PENDIDIK: g.noSerdik || '',
        BIDANG_STUDI_SERTIFIKASI: g.mapelSertifikasi || '',
        TAHUN_SERTIFIKASI: g.tahunSertifikasi || '',
        SATMINKAL: g.satminkal || '',
        MAPEL_UTAMA: g.mapelUtama || '',
        JTM_PER_MINGGU: g.jtm || 0,
        TUGAS_TAMBAHAN: g.tugasTambahan || '',
        REKENING_TPG: g.rekening || '',
        NPWP: g.npwp || '',
    }));
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Data_GTK_MI');
    XLSX.writeFile(wb, 'Data_Guru_GTK_EMIS4.xlsx');
};

const downloadGuruTemplateXLSX = () => {
    const sample = [
        {
            GELAR_DEPAN: '',
            NAMA_LENGKAP: 'CONTOH NAMA GURU MI',
            GELAR_BELAKANG: 'S.Pd.I.',
            NIK: '3207140101850001',
            NO_KK: '3207140101120001',
            JENIS_KELAMIN: 'L',
            AGAMA: 'Islam',
            TEMPAT_LAHIR: 'Ciamis',
            TANGGAL_LAHIR: '1985-01-01',
            NAMA_IBU_KANDUNG: 'Siti Aminah',
            STATUS_PERKAWINAN: 'Kawin',
            NO_HP: '081234567890',
            EMAIL: 'guru@madrasah.sch.id',
            ALAMAT: 'Desa Maparah Kec. Panjalu Kab. Ciamis',
            PEGID: '60708447185001',
            NPK: '8851230045001',
            NUPTK: '1234567890123456',
            NIP: '',
            STATUS_PEGAWAI: 'GTY',
            JENIS_PTK: 'Guru Kelas MI',
            GOLONGAN: '-',
            TMT_AWAL: '2015-07-15',
            TMT_MADRASAH: '2015-07-15',
            INPASSING: 'Belum',
            STATUS_AKTIF: 'Aktif',
            NO_SK: '001/SK/2015',
            PENDIDIKAN_TERAKHIR: 'D4/S1',
            PROGRAM_STUDI: 'PGMI',
            PERGURUAN_TINGGI: 'UIN SGD Bandung',
            TAHUN_LULUS: '2008',
            STATUS_SERTIFIKASI: 'Belum Sertifikasi',
            NRG: '',
            NO_SERTIFIKAT_PENDIDIK: '',
            BIDANG_STUDI_SERTIFIKASI: '',
            TAHUN_SERTIFIKASI: '',
            SATMINKAL: 'Satminkal (Induk)',
            MAPEL_UTAMA: 'Guru Kelas 2-A',
            JTM_PER_MINGGU: 24,
            TUGAS_TAMBAHAN: 'Wali Kelas',
            REKENING_TPG: '',
            NPWP: '',
        },
    ];
    const ws = XLSX.utils.json_to_sheet(sample);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Template_GTK_MI');
    XLSX.writeFile(wb, 'Template_Import_GTK_EMIS4.xlsx');
};

const exportRekapGuruExcel = () => {
    const rows = rekapStatusRows.value.map((r) => ({
        STATUS_KEPEGAWAIAN: r.status,
        LAKI_LAKI: r.l,
        PEREMPUAN: r.p,
        JUMLAH_GTK: r.jml,
        SUDAH_SERTIFIKASI: r.sert,
        BELUM_SERTIFIKASI: r.belum,
    }));
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Rekap_GTK_MI');
    XLSX.writeFile(wb, 'Rekapitulasi_GTK_Madrasah.xlsx');
};

const handleImportGuruXlsx = (event: Event) => {
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

            const payload = rows
                .map((r) => {
                    const nama = String(r.NAMA_LENGKAP || '')
                        .trim()
                        .toUpperCase();
                    if (!nama) return null;
                    return {
                        gelarDepan: String(r.GELAR_DEPAN || '').trim(),
                        nama,
                        gelarBelakang: String(r.GELAR_BELAKANG || '').trim(),
                        nik: String(r.NIK || '').trim(),
                        noKK: String(r.NO_KK || '').trim(),
                        jk: String(r.JENIS_KELAMIN || 'L')
                            .trim()
                            .toUpperCase()
                            .startsWith('P')
                            ? 'P'
                            : 'L',
                        agama: String(r.AGAMA || 'Islam').trim(),
                        tempatLahir: String(r.TEMPAT_LAHIR || '').trim(),
                        tglLahir: String(r.TANGGAL_LAHIR || '').trim(),
                        namaIbu: String(r.NAMA_IBU_KANDUNG || '').trim(),
                        statusNikah: String(
                            r.STATUS_PERKAWINAN || 'Kawin',
                        ).trim(),
                        hp: String(r.NO_HP || '').trim(),
                        email: String(r.EMAIL || '').trim(),
                        alamat: String(r.ALAMAT || '').trim(),
                        pegid: String(r.PEGID || '').trim(),
                        npk: String(r.NPK || '').trim(),
                        nuptk: String(r.NUPTK || '').trim(),
                        nip: String(r.NIP || '').trim(),
                        statusPegawai: String(
                            r.STATUS_PEGAWAI || 'GTT / Honorer',
                        ).trim(),
                        jenisPtk: String(r.JENIS_PTK || 'Guru Kelas MI').trim(),
                        golongan: String(r.GOLONGAN || '-').trim(),
                        tmtAwal: String(r.TMT_AWAL || '').trim(),
                        tmtMadrasah: String(r.TMT_MADRASAH || '').trim(),
                        inpassing: String(r.INPASSING || 'Belum').trim(),
                        statusAktif: String(r.STATUS_AKTIF || 'Aktif').trim(),
                        noSk: String(r.NO_SK || '').trim(),
                        pendidikan: String(
                            r.PENDIDIKAN_TERAKHIR || 'D4/S1',
                        ).trim(),
                        prodi: String(r.PROGRAM_STUDI || '').trim(),
                        kampus: String(r.PERGURUAN_TINGGI || '').trim(),
                        tahunLulus: String(r.TAHUN_LULUS || '').trim(),
                        statusSertifikasi: String(
                            r.STATUS_SERTIFIKASI || 'Belum Sertifikasi',
                        ).trim(),
                        nrg: String(r.NRG || '').trim(),
                        noSerdik: String(r.NO_SERTIFIKAT_PENDIDIK || '').trim(),
                        mapelSertifikasi: String(
                            r.BIDANG_STUDI_SERTIFIKASI || '',
                        ).trim(),
                        tahunSertifikasi: String(
                            r.TAHUN_SERTIFIKASI || '',
                        ).trim(),
                        satminkal: String(
                            r.SATMINKAL || 'Satminkal (Induk)',
                        ).trim(),
                        mapelUtama: String(r.MAPEL_UTAMA || '').trim(),
                        jtm: Number(r.JTM_PER_MINGGU || 0),
                        tugasTambahan: String(
                            r.TUGAS_TAMBAHAN || 'Tidak Ada',
                        ).trim(),
                        rekening: String(r.REKENING_TPG || '').trim(),
                        npwp: String(r.NPWP || '').trim(),
                    };
                })
                .filter((r): r is NonNullable<typeof r> => Boolean(r));

            if (payload.length === 0) {
                toast.error('Tidak ada baris GTK valid di file Excel.');
                return;
            }

            router.post(
                '/gtk/impor',
                { gurus: payload },
                { preserveScroll: true },
            );
        } catch {
            toast.error('Gagal membaca berkas Excel GTK.');
        } finally {
            input.value = '';
        }
    };
    reader.readAsArrayBuffer(file);
};
</script>

<template>
    <Head title="Data Guru & GTK (EMIS 4.0)" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Superadmin · Pendataan GTK EMIS 4.0"
            title="Data Guru & Tenaga Kependidikan"
            description="Pendataan GTK sesuai standar EMIS 4.0 Kemenag RI: NPK, NUPTK, PegID, NIP, Sertifikasi/NRG, Inpassing, Kualifikasi S1/S2, dan JTM."
        >
            <template #actions>
                <input
                    ref="xlsxInputRef"
                    type="file"
                    accept=".xlsx,.xls"
                    class="hidden"
                    @change="handleImportGuruXlsx"
                />
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="loadingSample"
                    @click="loadSampleGurus"
                >
                    <LoaderCircle
                        v-if="loadingSample"
                        class="size-3.5 animate-spin"
                    />
                    <Database v-else class="size-3.5" />
                    Sampel GTK
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    @click="downloadGuruTemplateXLSX"
                >
                    <Download class="size-3.5" />
                    Template XLSX
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    @click="xlsxInputRef?.click()"
                >
                    <Upload class="size-3.5" />
                    Impor XLSX
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    @click="exportGuruExcelXLSX"
                >
                    <FileSpreadsheet class="size-3.5" />
                    Ekspor XLSX
                </Button>
                <Button size="sm" @click="openCreate">
                    <UserPlus class="size-3.5" />
                    Tambah GTK
                </Button>
            </template>
        </PageHeader>

        <!-- Stats -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Total GTK Aktif"
                :value="stats.total"
                :hint="stats.genderHint"
                :icon="Users"
                tone="emerald"
            />
            <StatCard
                label="ASN (PNS / PPPK)"
                :value="stats.asn"
                :hint="stats.nonAsnHint"
                :icon="ShieldCheck"
                tone="sky"
            />
            <StatCard
                label="Sudah Sertifikasi"
                :value="stats.sert"
                :hint="stats.inpassingHint"
                :icon="Award"
                tone="gold"
            />
            <StatCard
                label="Kualifikasi S1 / S2 / S3"
                :value="stats.s1"
                hint="Layak mengajar EMIS"
                :icon="GraduationCap"
                tone="emerald"
            />
        </div>

        <!-- Rekap GTK -->
        <div
            class="overflow-hidden rounded-2xl border border-border/70 bg-card shadow-xs"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-2 border-b border-border/70 bg-muted/40 px-4 py-3"
            >
                <span class="text-xs font-bold">
                    Rekapitulasi GTK Aktif Berdasarkan Status Pegawai, L/P &amp;
                    Sertifikasi
                </span>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    class="h-7 text-[11px]"
                    @click="exportRekapGuruExcel"
                >
                    <FileSpreadsheet class="size-3.5" />
                    Ekspor Rekap GTK
                </Button>
            </div>
            <div class="overflow-x-auto text-xs">
                <table class="w-full text-left">
                    <thead class="border-b border-border/70 bg-muted/30">
                        <tr>
                            <th class="px-4 py-2.5">Status Kepegawaian</th>
                            <th class="px-3 py-2.5 text-center text-blue-700">
                                Laki-laki (L)
                            </th>
                            <th class="px-3 py-2.5 text-center text-pink-700">
                                Perempuan (P)
                            </th>
                            <th class="bg-muted/40 px-3 py-2.5 text-center">
                                Jumlah GTK
                            </th>
                            <th
                                class="px-3 py-2.5 text-center text-emerald-700"
                            >
                                Sudah Sertifikasi
                            </th>
                            <th class="px-3 py-2.5 text-center text-amber-700">
                                Belum Sertifikasi
                            </th>
                            <th class="px-3 py-2.5 text-center">
                                % Sertifikasi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr v-for="r in rekapStatusRows" :key="r.status">
                            <td class="px-4 py-2 font-semibold">
                                {{ r.status }}
                            </td>
                            <td class="px-3 py-2 text-center font-mono">
                                {{ r.l }}
                            </td>
                            <td class="px-3 py-2 text-center font-mono">
                                {{ r.p }}
                            </td>
                            <td
                                class="bg-muted/30 px-3 py-2 text-center font-bold"
                            >
                                {{ r.jml }}
                            </td>
                            <td
                                class="px-3 py-2 text-center font-mono font-bold text-emerald-700"
                            >
                                {{ r.sert }}
                            </td>
                            <td
                                class="px-3 py-2 text-center font-mono text-amber-700"
                            >
                                {{ r.belum }}
                            </td>
                            <td class="px-3 py-2 text-center font-mono">
                                {{ r.pct }}%
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t border-border bg-muted/50 font-bold">
                        <tr>
                            <td class="px-4 py-2.5">TOTAL KESELURUHAN</td>
                            <td class="px-3 py-2.5 text-center font-mono">
                                {{ rekapTotals.l }}
                            </td>
                            <td class="px-3 py-2.5 text-center font-mono">
                                {{ rekapTotals.p }}
                            </td>
                            <td class="px-3 py-2.5 text-center font-mono">
                                {{ rekapTotals.jml }}
                            </td>
                            <td
                                class="px-3 py-2.5 text-center font-mono text-emerald-800"
                            >
                                {{ rekapTotals.sert }}
                            </td>
                            <td
                                class="px-3 py-2.5 text-center font-mono text-amber-800"
                            >
                                {{ rekapTotals.belum }}
                            </td>
                            <td class="px-3 py-2.5 text-center font-mono">
                                {{ rekapTotals.pct }}%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Filters + Table -->
        <div
            class="overflow-hidden rounded-2xl border border-border/70 bg-card shadow-xs"
        >
            <div
                class="grid grid-cols-1 gap-3 border-b border-border/70 p-4 text-xs sm:grid-cols-2 lg:grid-cols-4"
            >
                <div class="relative lg:col-span-2">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Cari Nama Guru, NPK, NUPTK, PegID, NIP, NIK, atau Mapel..."
                        class="pl-9"
                    />
                </div>
                <select
                    v-model="pegawaiFilter"
                    class="h-9 rounded-md border border-input bg-background px-3 text-xs"
                >
                    <option value="">Semua Status Pegawai</option>
                    <option
                        v-for="st in GURU_STATUS_PEGAWAI_LIST"
                        :key="st"
                        :value="st"
                    >
                        {{ st }}
                    </option>
                </select>
                <select
                    v-model="sertifikasiFilter"
                    class="h-9 rounded-md border border-input bg-background px-3 text-xs"
                >
                    <option value="">Semua Status Sertifikasi</option>
                    <option value="Sudah Sertifikasi">Sudah Sertifikasi</option>
                    <option value="Belum Sertifikasi">Belum Sertifikasi</option>
                </select>
            </div>

            <div class="overflow-x-auto text-xs">
                <table class="w-full min-w-[920px] text-left">
                    <thead
                        class="border-b border-border/70 bg-muted/40 text-[11px] font-semibold uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">Nama Lengkap &amp; NIK</th>
                            <th class="px-4 py-3">NPK / NUPTK / NIP</th>
                            <th class="px-4 py-3">Status &amp; Jenis PTK</th>
                            <th class="px-4 py-3">Pendidikan</th>
                            <th class="px-4 py-3">Mapel &amp; JTM</th>
                            <th class="px-4 py-3">Sertifikasi</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr v-if="filteredGurus.length === 0">
                            <td
                                colspan="9"
                                class="py-10 text-center text-muted-foreground"
                            >
                                Tidak ada data GTK. Klik
                                <strong>Tambah GTK</strong> atau
                                <strong>Sampel GTK</strong>.
                            </td>
                        </tr>
                        <tr
                            v-for="(g, i) in filteredGurus"
                            :key="g.id"
                            class="hover:bg-muted/30"
                        >
                            <td
                                class="px-4 py-2.5 font-mono text-muted-foreground"
                            >
                                {{ i + 1 }}
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="font-bold">
                                    {{ formatNamaGelarGuru(g) }}
                                </div>
                                <div
                                    class="font-mono text-[11px] text-muted-foreground"
                                >
                                    NIK: {{ g.nik || '-' }} ({{ g.jk }})
                                </div>
                            </td>
                            <td class="px-4 py-2.5 font-mono text-[11px]">
                                <div>
                                    NPK: <strong>{{ g.npk || '-' }}</strong>
                                </div>
                                <div class="text-muted-foreground">
                                    NUPTK: {{ g.nuptk || '-' }}
                                </div>
                                <div v-if="g.nip" class="text-blue-700">
                                    NIP: {{ g.nip }}
                                </div>
                            </td>
                            <td class="px-4 py-2.5">
                                <span
                                    class="rounded bg-blue-100 px-2 py-0.5 font-bold text-blue-800"
                                    >{{ g.statusPegawai }}</span
                                >
                                <div
                                    class="mt-0.5 text-[11px] text-muted-foreground"
                                >
                                    {{ g.jenisPtk }}
                                </div>
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="font-bold">{{ g.pendidikan }}</div>
                                <div class="text-[11px] text-muted-foreground">
                                    {{ g.prodi || '-' }}
                                </div>
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="font-semibold">
                                    {{ g.mapelUtama || '-' }}
                                </div>
                                <div
                                    class="font-mono text-[11px] text-emerald-700"
                                >
                                    {{ g.jtm || 0 }} JTM/mgg
                                </div>
                            </td>
                            <td class="px-4 py-2.5">
                                <template
                                    v-if="
                                        g.statusSertifikasi ===
                                        'Sudah Sertifikasi'
                                    "
                                >
                                    <span
                                        class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800"
                                        >Sudah</span
                                    >
                                    <div
                                        class="mt-0.5 font-mono text-[10px] text-muted-foreground"
                                    >
                                        NRG: {{ g.nrg || '-' }}
                                    </div>
                                </template>
                                <span
                                    v-else
                                    class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800"
                                    >Belum</span
                                >
                            </td>
                            <td class="px-4 py-2.5">
                                <span
                                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800"
                                >
                                    {{ g.statusAktif || 'Aktif' }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-2.5 text-center whitespace-nowrap"
                            >
                                <button
                                    type="button"
                                    class="rounded p-1.5 text-emerald-700 hover:bg-emerald-50"
                                    title="Edit GTK"
                                    @click="openEdit(g)"
                                >
                                    <Pencil class="size-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded p-1.5 text-destructive hover:bg-destructive/10"
                                    title="Hapus GTK"
                                    @click="deletingGuru = g"
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div
                class="flex items-center justify-between border-t border-border/70 bg-muted/20 px-4 py-2.5 text-xs text-muted-foreground"
            >
                <span>
                    Menampilkan <strong>{{ filteredGurus.length }}</strong> dari
                    <strong>{{ gurus.length }}</strong> GTK
                </span>
                <span class="text-[11px]"
                    >Standar Pendataan GTK EMIS 4.0 Kemenag RI</span
                >
            </div>
        </div>

        <GuruFormModal v-model:open="formOpen" :guru="editingGuru" />

        <ConfirmDialog
            :open="Boolean(deletingGuru)"
            title="Hapus Data Guru / GTK?"
            :description="`Apakah Anda yakin ingin menghapus data GTK ${formatNamaGelarGuru(deletingGuru)}?`"
            confirm-label="Ya, Hapus GTK"
            variant="destructive"
            :processing="deletingProcessing"
            @update:open="(v) => !v && (deletingGuru = null)"
            @confirm="confirmDelete"
        />
    </div>
</template>
