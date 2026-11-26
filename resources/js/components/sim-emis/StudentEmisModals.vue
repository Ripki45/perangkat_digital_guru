<script setup lang="ts">
import { BookOpen, IdCard, Printer, UserRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    buildBukuIndukSheetHtml,
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
import { EMIS_68_HEADERS } from '@/types/emis';
import type { EmisMadrasahProfile, EmisStudent } from '@/types/emis';

const props = defineProps<{
    students: EmisStudent[];
    allRombelCodes: string[];
    profile: EmisMadrasahProfile;
    biodataStudent: EmisStudent | null;
    ktsStudent: EmisStudent | null;
    bulkIndukOpen: boolean;
    kamusOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'close-biodata'): void;
    (e: 'close-kts'): void;
    (e: 'update:bulkIndukOpen', value: boolean): void;
    (e: 'update:kamusOpen', value: boolean): void;
}>();

const bulkFilter = ref('AKTIF');
const bulkSort = ref<'AZ' | 'ROMBEL_AZ'>('AZ');

const singleIndukHtml = computed(() =>
    props.biodataStudent
        ? buildBukuIndukSheetHtml(props.biodataStudent, props.profile)
        : '',
);

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
        .map((s) => buildBukuIndukSheetHtml(s, props.profile))
        .join(''),
);

const printSingleInduk = () => {
    if (!singleIndukHtml.value) return;
    openPrintWindowWithContent(
        singleIndukHtml.value,
        `Buku Induk - ${props.biodataStudent?.nama ?? 'Siswa'}`,
    );
};

const printBulkInduk = () => {
    if (!bulkHtml.value) return;
    openPrintWindowWithContent(bulkHtml.value, 'Buku Induk Siswa MI Massal');
};

const printKts = () => {
    const el = document.getElementById('kts-print-preview');
    if (!el) return;
    openPrintWindowWithContent(el.innerHTML, 'Kartu Tanda Siswa (KTS)');
};
</script>

<template>
    <!-- MODAL BIODATA BUKU INDUK SATUAN -->
    <Dialog
        :open="Boolean(biodataStudent)"
        @update:open="(v) => !v && emit('close-biodata')"
    >
        <DialogContent
            class="flex max-h-[90vh] max-w-4xl flex-col overflow-hidden p-0"
        >
            <DialogHeader
                class="flex flex-row items-center justify-between border-b bg-emerald-900 px-5 py-3 text-white"
            >
                <div>
                    <DialogTitle
                        class="flex items-center gap-2 text-sm font-bold text-white"
                    >
                        <BookOpen class="size-4" />
                        Lembar Buku Induk Siswa MI — EMIS 4.0
                    </DialogTitle>
                    <DialogDescription class="sr-only">
                        Pratinjau dan cetak Buku Induk Siswa EMIS 4.0
                    </DialogDescription>
                </div>
                <Button
                    type="button"
                    size="sm"
                    class="mr-6 bg-emerald-500 font-bold text-slate-950 hover:bg-emerald-400"
                    @click="printSingleInduk"
                >
                    <Printer class="size-3.5" />
                    Cetak A4
                </Button>
            </DialogHeader>
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div
                class="flex-1 overflow-y-auto bg-slate-100 p-5"
                v-html="singleIndukHtml"
            />
        </DialogContent>
    </Dialog>

    <!-- MODAL BUKU INDUK MASSAL -->
    <Dialog
        :open="bulkIndukOpen"
        @update:open="emit('update:bulkIndukOpen', $event)"
    >
        <DialogContent
            class="flex max-h-[90vh] max-w-5xl flex-col overflow-hidden p-0"
        >
            <DialogHeader
                class="flex flex-row flex-wrap items-center justify-between gap-2 border-b bg-purple-900 px-5 py-3 text-white"
            >
                <div>
                    <DialogTitle class="text-sm font-bold text-white">
                        Cetak Buku Induk Siswa MI Massal ({{ bulkList.length }}
                        Lembar)
                    </DialogTitle>
                    <DialogDescription class="sr-only">
                        Pratinjau cetak massal buku induk
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
                        @click="printBulkInduk"
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

    <!-- MODAL KARTU TANDA SISWA (KTS) SATUAN -->
    <Dialog
        :open="Boolean(ktsStudent)"
        @update:open="(v) => !v && emit('close-kts')"
    >
        <DialogContent class="max-w-md overflow-hidden p-0">
            <DialogHeader
                class="flex flex-row items-center justify-between border-b bg-indigo-900 px-5 py-3 text-white"
            >
                <div>
                    <DialogTitle
                        class="flex items-center gap-2 text-sm font-bold text-white"
                    >
                        <IdCard class="size-4" />
                        Kartu Tanda Siswa (KTS)
                    </DialogTitle>
                    <DialogDescription class="sr-only">
                        Pratinjau dan cetak Kartu Tanda Siswa
                    </DialogDescription>
                </div>
                <Button
                    type="button"
                    size="sm"
                    class="mr-6 bg-emerald-500 font-bold text-slate-950 hover:bg-emerald-400"
                    @click="printKts"
                >
                    <Printer class="size-3.5" />
                    Cetak
                </Button>
            </DialogHeader>
            <div
                v-if="ktsStudent"
                id="kts-print-preview"
                class="bg-slate-100 p-5"
            >
                <div
                    class="overflow-hidden rounded-xl border-2 border-emerald-800 bg-white shadow"
                >
                    <div
                        class="flex items-center gap-2.5 bg-emerald-800 px-4 py-2.5 text-white"
                    >
                        <div
                            class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded bg-white/15"
                        >
                            <img
                                v-if="profile.logoKiri"
                                :src="profile.logoKiri"
                                alt="Logo"
                                class="size-full bg-white object-contain p-0.5"
                            />
                            <IdCard v-else class="size-4 text-emerald-200" />
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase">
                                KARTU TANDA SISWA (KTS)
                            </div>
                            <div class="text-[10px] text-emerald-200">
                                {{ profile.namaMadrasah }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3.5 p-4 text-xs">
                        <div
                            class="flex h-28 w-20 shrink-0 items-center justify-center overflow-hidden rounded border border-slate-400 bg-slate-100"
                        >
                            <img
                                v-if="ktsStudent.foto"
                                :src="ktsStudent.foto"
                                alt="Foto Siswa"
                                class="size-full object-cover"
                            />
                            <UserRound v-else class="size-7 text-slate-400" />
                        </div>
                        <div class="space-y-1">
                            <div
                                class="text-sm font-bold text-slate-900 uppercase"
                            >
                                {{ ktsStudent.nama }}
                            </div>
                            <div>
                                <span class="text-slate-500">NISN:</span>
                                <strong class="ml-1 font-mono">{{
                                    ktsStudent.nisn || '-'
                                }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-500">Kelas:</span>
                                <strong class="ml-1 text-emerald-800"
                                    >Kelas
                                    {{ ktsStudent.rombel || '-' }}</strong
                                >
                            </div>
                            <div>
                                <span class="text-slate-500">TTL:</span>
                                <span class="ml-1"
                                    >{{ ktsStudent.tempatLahir || '-' }},
                                    {{ ktsStudent.tglLahir || '-' }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <!-- MODAL KAMUS REFERENSI KOLOM EXCEL EMIS 4.0 -->
    <Dialog :open="kamusOpen" @update:open="emit('update:kamusOpen', $event)">
        <DialogContent
            class="flex max-h-[85vh] max-w-3xl flex-col overflow-hidden p-0"
        >
            <DialogHeader class="border-b bg-emerald-800 px-5 py-3 text-white">
                <DialogTitle class="text-sm font-bold text-white">
                    Kamus Referensi Kolom Excel EMIS 4.0 ({{
                        EMIS_68_HEADERS.length
                    }}
                    Kolom)
                </DialogTitle>
                <DialogDescription class="sr-only">
                    Daftar kolom standar impor dan ekspor Excel EMIS 4.0
                </DialogDescription>
            </DialogHeader>
            <div class="flex-1 overflow-y-auto p-4 text-xs">
                <table class="w-full border-collapse border border-border">
                    <thead class="bg-muted font-bold">
                        <tr>
                            <th class="w-12 border border-border p-2 text-left">
                                No
                            </th>
                            <th class="border border-border p-2 text-left">
                                Nama Header Kolom
                            </th>
                            <th class="border border-border p-2 text-left">
                                Kelompok Data
                            </th>
                            <th class="border border-border p-2 text-left">
                                Keterangan Format
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="(header, idx) in EMIS_68_HEADERS"
                            :key="header"
                        >
                            <td class="border border-border p-1.5 font-mono">
                                {{ idx + 1 }}
                            </td>
                            <td
                                class="border border-border p-1.5 font-mono font-bold text-emerald-800 dark:text-emerald-300"
                            >
                                {{ header }}
                            </td>
                            <td class="border border-border p-1.5">
                                EMIS 4.0 MI Kemenag RI
                            </td>
                            <td class="border border-border p-1.5">
                                Teks / Angka Standar
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </DialogContent>
    </Dialog>
</template>
