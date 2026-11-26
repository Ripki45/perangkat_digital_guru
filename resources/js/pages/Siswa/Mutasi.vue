<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    FileSpreadsheet,
    LoaderCircle,
    Printer,
    Send,
    UserCheck,
    UserMinus,
    UserPlus,
    UserX,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import * as XLSX from 'xlsx';
import PageHeader from '@/components/app/PageHeader.vue';
import StatCard from '@/components/app/StatCard.vue';
import {
    buildOfficialKopHtml,
    escapeHtml,
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
import type { EmisMadrasahProfile, EmisStudent } from '@/types/emis';

const props = defineProps<{
    students: EmisStudent[];
    madrasahProfile: EmisMadrasahProfile;
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'Mutasi & Surat Pindah', href: '/mutasi' },
        ],
    }),
});

const activeStudents = computed(() =>
    props.students.filter((s) => s.statusSiswa === 'Aktif'),
);
const masukList = computed(() =>
    props.students.filter((s) => s.jenisPendaftaran === 'Pindahan'),
);
const keluarList = computed(() =>
    props.students.filter((s) => s.statusSiswa === 'Mutasi Keluar'),
);
const lulusList = computed(() =>
    props.students.filter((s) => s.statusSiswa === 'Lulus'),
);
const doList = computed(() =>
    props.students.filter((s) => s.statusSiswa === 'Putus Sekolah'),
);
const nonActiveList = computed(() =>
    props.students.filter((s) => s.statusSiswa !== 'Aktif'),
);

const mutasiForm = reactive({
    student_id: activeStudents.value[0]?.id ?? 0,
    alasan: 'Mutasi / Pindah Sekolah',
    tgl_keluar: new Date().toISOString().slice(0, 10),
    no_surat: '',
    sekolah_tujuan: '',
    keterangan: '',
});

const savingMutasi = ref(false);

const submitMutasi = () => {
    if (!mutasiForm.student_id) {
        toast.error('Tidak ada siswa aktif yang dipilih.');
        return;
    }

    savingMutasi.value = true;
    router.post(
        '/mutasi',
        { ...mutasiForm },
        {
            preserveScroll: true,
            onSuccess: () => {
                mutasiForm.no_surat = '';
                mutasiForm.sekolah_tujuan = '';
                mutasiForm.keterangan = '';
                mutasiForm.student_id = activeStudents.value[0]?.id ?? 0;
            },
            onFinish: () => {
                savingMutasi.value = false;
            },
        },
    );
};

const exportMutasiExcel = (type: 'MASUK' | 'KELUAR') => {
    const list = type === 'MASUK' ? masukList.value : nonActiveList.value;
    const rows = list.map((s, i) => ({
        NO: i + 1,
        NAMA_SISWA: s.nama,
        NISN: s.nisn,
        ROMBEL: s.rombel,
        STATUS_SISWA: s.statusSiswa,
        JENIS_PENDAFTARAN: s.jenisPendaftaran,
        TGL_MASUK: s.tglMasuk,
        ASAL_SEKOLAH: s.asalSekolah,
        TGL_KELUAR: s.tglKeluar,
        SEKOLAH_TUJUAN: s.sekolahTujuan,
        NO_SURAT_IJAZAH: s.noSeriIjazahMadrasah,
    }));
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Mutasi_MI');
    XLSX.writeFile(wb, `Rekap_Mutasi_Madrasah_${type}.xlsx`);
};

/* ----------------------- Surat Keterangan Pindah Modal ---------------------- */

const suratOpen = ref(false);
const suratStudentId = ref<number>(0);
const suratNomor = ref('B-001/Mi.10.07.01/PP.00.4/2026');
const suratPermintaan = ref('Orang Tua / Wali Siswa');
const suratEmis = ref('');
const suratBerkas = ref(
    'Rapor Asli, Surat Keterangan Kelakuan Baik, dan Bukti Mutasi EMIS 4.0',
);

const suratStudent = computed(
    () =>
        props.students.find((s) => s.id === suratStudentId.value) ??
        props.students[0] ??
        null,
);

const openSuratModal = (studentId?: number) => {
    suratStudentId.value =
        studentId ?? nonActiveList.value[0]?.id ?? props.students[0]?.id ?? 0;
    suratOpen.value = true;
};

const suratHtml = computed(() => {
    const s = suratStudent.value;
    if (!s) return '';
    const p = props.madrasahProfile;
    const tglSurat = new Date().toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    return `
        <div style="background:#fff;max-width:210mm;margin:0 auto;padding:28px;border:1px solid #cbd5e1;font-family:'Times New Roman',Times,serif;font-size:12px;color:#000;">
            ${buildOfficialKopHtml(p)}
            <div style="text-align:center;margin-bottom:14px;">
                <div style="font-weight:bold;font-size:14px;text-decoration:underline;">SURAT KETERANGAN PINDAH MADRASAH / SEKOLAH</div>
                <div>Nomor: <strong>${escapeHtml(suratNomor.value)}</strong></div>
            </div>
            <p style="margin-bottom:10px;">Yang bertanda tangan di bawah ini Kepala <strong>${escapeHtml(p.namaMadrasah)}</strong> menerangkan bahwa:</p>
            <table style="width:100%;margin-bottom:12px;">
                <tr><td style="width:190px;padding:2px 0;">Nama Peserta Didik</td><td style="width:12px;">:</td><td style="font-weight:bold;text-transform:uppercase;">${escapeHtml(s.nama)}</td></tr>
                <tr><td style="padding:2px 0;">Jenis Kelamin</td><td>:</td><td>${s.jk === 'P' ? 'Perempuan' : 'Laki-laki'}</td></tr>
                <tr><td style="padding:2px 0;">Kewarganegaraan / Agama</td><td>:</td><td>${escapeHtml(s.kewarganegaraan || 'WNI')} / ${escapeHtml(s.agama || 'Islam')}</td></tr>
                <tr><td style="padding:2px 0;">Tempat, Tanggal Lahir</td><td>:</td><td>${escapeHtml(s.tempatLahir || '-')}, ${escapeHtml(s.tglLahir || '-')}</td></tr>
                <tr><td style="padding:2px 0;">NISN</td><td>:</td><td style="font-family:monospace;">${escapeHtml(s.nisn || '-')}</td></tr>
                <tr><td style="padding:2px 0;">Kelas / Rombel</td><td>:</td><td>Kelas ${escapeHtml(s.rombel || '-')}</td></tr>
                <tr><td style="padding:2px 0;">Nama Orang Tua / Wali</td><td>:</td><td>${escapeHtml(s.ayahNama || s.ibuNama || s.waliNama || '-')}</td></tr>
                <tr><td style="padding:2px 0;">Tanggal Pindah / Keluar</td><td>:</td><td>${escapeHtml(s.tglKeluar || '-')}</td></tr>
                <tr><td style="padding:2px 0;">Madrasah / Sekolah Tujuan</td><td>:</td><td style="font-weight:bold;">${escapeHtml(s.sekolahTujuan || '-')}</td></tr>
                <tr><td style="padding:2px 0;">Atas Permintaan</td><td>:</td><td>${escapeHtml(suratPermintaan.value || '-')}</td></tr>
                <tr><td style="padding:2px 0;">No. Bukti Mutasi EMIS</td><td>:</td><td style="font-family:monospace;">${escapeHtml(suratEmis.value || '-')}</td></tr>
                <tr><td style="padding:2px 0;">Lampiran Berkas</td><td>:</td><td>${escapeHtml(suratBerkas.value || '-')}</td></tr>
            </table>
            <p style="margin-bottom:24px;">Demikian surat keterangan pindah ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
            <div style="display:flex;justify-content:flex-end;">
                <div style="width:240px;line-height:1.4;">
                    <div>${escapeHtml(p.tempatSurat)}, ${escapeHtml(tglSurat)}</div>
                    <div>Kepala Madrasah,</div>
                    <div style="height:56px;"></div>
                    <div style="font-weight:bold;text-decoration:underline;">${escapeHtml(p.kepalaNama)}</div>
                    <div>NIP. ${escapeHtml(p.kepalaNip)}</div>
                </div>
            </div>
        </div>
    `;
});

const printSurat = () => {
    if (!suratHtml.value) return;
    openPrintWindowWithContent(
        suratHtml.value,
        'Surat Keterangan Pindah Madrasah',
    );
};
</script>

<template>
    <Head title="Mutasi & Surat Pindah" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Kesiswaan EMIS 4.0"
            title="Mutasi Masuk, Keluar & Surat Pindah"
            description="Catat peserta didik pindahan, proses mutasi keluar/lulus/putus sekolah, ekspor rekap Excel, dan cetak Surat Keterangan Pindah Madrasah."
        >
            <template #actions>
                <Button as-child variant="outline" size="sm">
                    <Link href="/siswa?action=mutasi-masuk">
                        <UserPlus class="size-3.5" />
                        Tambah Siswa Pindahan
                    </Link>
                </Button>
                <Button
                    type="button"
                    size="sm"
                    class="bg-amber-700 text-white hover:bg-amber-800"
                    @click="openSuratModal()"
                >
                    <Printer class="size-3.5" />
                    Cetak Surat Pindah
                </Button>
            </template>
        </PageHeader>

        <!-- Stats -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Mutasi Masuk (Pindahan)"
                :value="masukList.length"
                :icon="UserPlus"
                tone="emerald"
            />
            <StatCard
                label="Mutasi Keluar"
                :value="keluarList.length"
                :icon="UserMinus"
                tone="gold"
            />
            <StatCard
                label="Lulus / Alumni MI"
                :value="lulusList.length"
                :icon="UserCheck"
                tone="sky"
            />
            <StatCard
                label="Putus Sekolah"
                :value="doList.length"
                :icon="UserX"
                tone="rose"
            />
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1fr_1.4fr]">
            <!-- Form Mutasi Keluar Cepat -->
            <form
                class="h-fit space-y-3.5 rounded-2xl border border-border/70 bg-card p-5 text-xs shadow-xs"
                @submit.prevent="submitMutasi"
            >
                <h2 class="text-sm font-bold">Proses Mutasi Keluar Cepat</h2>

                <div>
                    <label class="mb-1 block font-semibold"
                        >Pilih Siswa Aktif</label
                    >
                    <select
                        v-model.number="mutasiForm.student_id"
                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-semibold"
                    >
                        <option
                            v-for="s in activeStudents"
                            :key="s.id"
                            :value="s.id"
                        >
                            {{ s.nama }} (Kelas {{ s.rombel }})
                        </option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="mb-1 block font-semibold">Alasan</label>
                        <select
                            v-model="mutasiForm.alasan"
                            class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                        >
                            <option value="Mutasi / Pindah Sekolah">
                                Mutasi / Pindah Sekolah
                            </option>
                            <option value="Lulus">Lulus</option>
                            <option value="Mengundurkan Diri">
                                Mengundurkan Diri
                            </option>
                            <option value="Putus Sekolah">Putus Sekolah</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold"
                            >Tanggal Keluar</label
                        >
                        <input
                            v-model="mutasiForm.tgl_keluar"
                            type="date"
                            class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                        />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block font-semibold"
                        >No. Surat / No. Seri Ijazah</label
                    >
                    <input
                        v-model="mutasiForm.no_surat"
                        type="text"
                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                    />
                </div>

                <div>
                    <label class="mb-1 block font-semibold"
                        >Madrasah / Sekolah Tujuan</label
                    >
                    <input
                        v-model="mutasiForm.sekolah_tujuan"
                        type="text"
                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                    />
                </div>

                <div>
                    <label class="mb-1 block font-semibold">Keterangan</label>
                    <input
                        v-model="mutasiForm.keterangan"
                        type="text"
                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                    />
                </div>

                <Button
                    type="submit"
                    :disabled="savingMutasi || activeStudents.length === 0"
                    class="w-full bg-amber-700 font-bold text-white hover:bg-amber-800"
                >
                    <LoaderCircle
                        v-if="savingMutasi"
                        class="size-3.5 animate-spin"
                    />
                    <Send v-else class="size-3.5" />
                    Simpan Mutasi Keluar
                </Button>
            </form>

            <!-- Tables: Masuk & Keluar -->
            <div class="space-y-5">
                <!-- Mutasi Masuk -->
                <div
                    class="overflow-hidden rounded-2xl border border-border/70 bg-card shadow-xs"
                >
                    <div
                        class="flex items-center justify-between border-b border-border/70 px-4 py-3"
                    >
                        <h3 class="text-sm font-bold">
                            Daftar Mutasi Masuk (Pindahan)
                        </h3>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="exportMutasiExcel('MASUK')"
                        >
                            <FileSpreadsheet class="size-3.5" />
                            Ekspor XLSX
                        </Button>
                    </div>
                    <div class="overflow-x-auto text-xs">
                        <table class="w-full">
                            <thead class="bg-muted/50 text-left">
                                <tr>
                                    <th class="px-3 py-2">Nama Siswa</th>
                                    <th class="px-3 py-2">Rombel</th>
                                    <th class="px-3 py-2">
                                        Tgl Masuk &amp; Asal
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/70">
                                <tr v-if="masukList.length === 0">
                                    <td
                                        colspan="3"
                                        class="py-6 text-center text-muted-foreground"
                                    >
                                        Belum ada data mutasi masuk.
                                    </td>
                                </tr>
                                <tr v-for="s in masukList" :key="s.id">
                                    <td class="px-3 py-2 font-bold uppercase">
                                        {{ s.nama }}
                                    </td>
                                    <td class="px-3 py-2">{{ s.rombel }}</td>
                                    <td class="px-3 py-2">
                                        {{ s.tglMasuk || '-' }} ({{
                                            s.asalSekolah || '-'
                                        }})
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mutasi Keluar / Lulus -->
                <div
                    class="overflow-hidden rounded-2xl border border-border/70 bg-card shadow-xs"
                >
                    <div
                        class="flex items-center justify-between border-b border-border/70 px-4 py-3"
                    >
                        <h3 class="text-sm font-bold">
                            Daftar Mutasi Keluar &amp; Lulus
                        </h3>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="exportMutasiExcel('KELUAR')"
                        >
                            <FileSpreadsheet class="size-3.5" />
                            Ekspor XLSX
                        </Button>
                    </div>
                    <div class="overflow-x-auto text-xs">
                        <table class="w-full">
                            <thead class="bg-muted/50 text-left">
                                <tr>
                                    <th class="px-3 py-2">Nama Siswa</th>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2">Tgl &amp; Tujuan</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/70">
                                <tr v-if="nonActiveList.length === 0">
                                    <td
                                        colspan="4"
                                        class="py-6 text-center text-muted-foreground"
                                    >
                                        Belum ada data mutasi keluar / lulus.
                                    </td>
                                </tr>
                                <tr v-for="s in nonActiveList" :key="s.id">
                                    <td class="px-3 py-2 font-bold uppercase">
                                        {{ s.nama }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800"
                                        >
                                            {{ s.statusSiswa }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ s.tglKeluar || '-' }} &rarr;
                                        {{ s.sekolahTujuan || '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button
                                            type="button"
                                            class="font-semibold text-amber-700 hover:underline"
                                            @click="openSuratModal(s.id)"
                                        >
                                            Surat Pindah
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Surat Keterangan Pindah -->
        <Dialog :open="suratOpen" @update:open="suratOpen = $event">
            <DialogContent
                class="flex max-h-[90vh] max-w-4xl flex-col overflow-hidden p-0"
            >
                <DialogHeader
                    class="flex flex-row items-center justify-between border-b bg-amber-800 px-5 py-3 text-white"
                >
                    <div>
                        <DialogTitle class="text-sm font-bold text-white">
                            Cetak Surat Keterangan Pindah Madrasah
                        </DialogTitle>
                        <DialogDescription class="sr-only">
                            Pratinjau dan cetak surat keterangan pindah madrasah
                        </DialogDescription>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        class="mr-6 bg-emerald-500 font-bold text-slate-950 hover:bg-emerald-400"
                        @click="printSurat"
                    >
                        <Printer class="size-3.5" />
                        Cetak Surat
                    </Button>
                </DialogHeader>
                <div
                    class="grid grid-cols-1 gap-2.5 border-b bg-muted/40 p-4 text-xs sm:grid-cols-4"
                >
                    <div>
                        <label class="mb-1 block font-semibold"
                            >Pilih Siswa</label
                        >
                        <select
                            v-model.number="suratStudentId"
                            class="w-full rounded border border-input bg-background p-1.5"
                        >
                            <option
                                v-for="s in students"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.nama }} ({{ s.statusSiswa }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold"
                            >Nomor Surat</label
                        >
                        <input
                            v-model="suratNomor"
                            type="text"
                            class="w-full rounded border border-input bg-background p-1.5 font-mono"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold"
                            >Permintaan Dari</label
                        >
                        <input
                            v-model="suratPermintaan"
                            type="text"
                            class="w-full rounded border border-input bg-background p-1.5"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold"
                            >No. Dokumen EMIS</label
                        >
                        <input
                            v-model="suratEmis"
                            type="text"
                            class="w-full rounded border border-input bg-background p-1.5 font-mono"
                        />
                    </div>
                    <div class="sm:col-span-4">
                        <label class="mb-1 block font-semibold"
                            >Berkas Lampiran</label
                        >
                        <input
                            v-model="suratBerkas"
                            type="text"
                            class="w-full rounded border border-input bg-background p-1.5"
                        />
                    </div>
                </div>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <div
                    class="flex-1 overflow-y-auto bg-slate-200 p-5"
                    v-html="suratHtml"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
