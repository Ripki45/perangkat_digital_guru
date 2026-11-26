<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowRightLeft,
    CheckSquare,
    GraduationCap,
    Layers,
    LoaderCircle,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { formatNamaGelarGuru } from '@/types/emis';
import type { EmisGuru, EmisRombel, EmisStudent } from '@/types/emis';

const props = defineProps<{
    rombels: EmisRombel[];
    students: EmisStudent[];
    gurus: EmisGuru[];
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'Rombel & Kenaikan Kelas', href: '/rombel' },
        ],
    }),
});

const rombelForm = reactive({
    tingkat: '1',
    nama: '',
    wali: '',
});

const savingRombel = ref(false);
const deletingRombel = ref<EmisRombel | null>(null);
const deletingProcessing = ref(false);

const allRombelCodes = computed(() => {
    const set = new Set(props.rombels.map((r) => r.kode));
    props.students.forEach((s) => {
        if (s.rombel) set.add(s.rombel);
    });
    return Array.from(set).sort((a, b) =>
        a.localeCompare(b, 'id', { numeric: true }),
    );
});

const activeStudents = computed(() =>
    props.students.filter((s) => s.statusSiswa === 'Aktif'),
);

const activeCountForRombel = (kode: string): number =>
    activeStudents.value.filter((s) => s.rombel === kode).length;

const totalCountForRombel = (kode: string): number =>
    props.students.filter((s) => s.rombel === kode).length;

const guruOptions = computed(() =>
    [...props.gurus]
        .map((g) => formatNamaGelarGuru(g))
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b, 'id')),
);

const submitRombel = () => {
    if (!rombelForm.nama.trim()) {
        toast.error('Masukkan nama atau kode rombel MI terlebih dahulu!');
        return;
    }

    savingRombel.value = true;
    router.post(
        '/rombel',
        { ...rombelForm },
        {
            preserveScroll: true,
            onSuccess: () => {
                rombelForm.nama = '';
            },
            onFinish: () => {
                savingRombel.value = false;
            },
        },
    );
};

const editRombelWali = (r: EmisRombel) => {
    rombelForm.tingkat = r.tingkat;
    rombelForm.nama = r.kode;
    rombelForm.wali = r.wali || '';
    toast.info(
        `Ubah wali kelas untuk ${r.kode} lalu klik Simpan / Update Rombel.`,
    );
};

const confirmDeleteRombel = () => {
    if (!deletingRombel.value) return;
    deletingProcessing.value = true;
    router.delete(`/rombel/${deletingRombel.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingProcessing.value = false;
            deletingRombel.value = null;
        },
    });
};

/* ------------------------- Batch Rombel & Kenaikan ------------------------- */

const sourceRombel = ref(allRombelCodes.value[0] || 'ALL');
const targetRombel = ref(
    allRombelCodes.value[1] || allRombelCodes.value[0] || '',
);
const batchNewTingkat = ref('2');
const batchTglLulus = ref(new Date().toISOString().slice(0, 10));
const batchKetLulus = ref('Lulus Ujian Madrasah Ibtidaiyah');
const selectedIds = ref<number[]>([]);
const batchProcessing = ref(false);

const rosterStudents = computed(() =>
    activeStudents.value.filter(
        (s) => sourceRombel.value === 'ALL' || s.rombel === sourceRombel.value,
    ),
);

watch(sourceRombel, () => {
    selectedIds.value = [];
});

const allSelected = computed(
    () =>
        rosterStudents.value.length > 0 &&
        rosterStudents.value.every((s) => selectedIds.value.includes(s.id)),
);

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = rosterStudents.value.map((s) => s.id);
    }
};

const executeBatch = (selectAllFirst = false) => {
    const ids = selectAllFirst
        ? rosterStudents.value.map((s) => s.id)
        : selectedIds.value;

    if (ids.length === 0) {
        toast.error('Pilih minimal satu siswa terlebih dahulu!');
        return;
    }
    if (!targetRombel.value) {
        toast.error('Pilih tujuan rombel atau aksi terlebih dahulu!');
        return;
    }

    batchProcessing.value = true;
    router.post(
        '/rombel/batch',
        {
            student_ids: ids,
            target: targetRombel.value,
            new_tingkat: batchNewTingkat.value,
            tgl_lulus: batchTglLulus.value,
            ket_lulus: batchKetLulus.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                batchProcessing.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Rombel & Kenaikan Kelas MI" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Kesiswaan EMIS 4.0"
            title="Manajemen Rombel & Kenaikan Kelas MI"
            description="Kelola daftar rombongan belajar Kelas 1–6 MI beserta wali kelasnya, pindahkan rombel secara massal, atau proses kelulusan Kelas 6."
        />

        <!-- Add / Edit Rombel Card -->
        <div
            class="space-y-4 rounded-2xl border border-border/70 bg-card p-5 shadow-xs"
        >
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <Layers class="size-4 text-emerald-700" />
                Kelola Rombongan Belajar &amp; Wali Kelas
            </h2>

            <form
                class="grid grid-cols-1 gap-3 text-xs md:grid-cols-4"
                @submit.prevent="submitRombel"
            >
                <div>
                    <label class="mb-1 block font-semibold">Tingkat MI</label>
                    <select
                        v-model="rombelForm.tingkat"
                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-semibold"
                    >
                        <option value="1">Kelas 1 MI</option>
                        <option value="2">Kelas 2 MI</option>
                        <option value="3">Kelas 3 MI</option>
                        <option value="4">Kelas 4 MI</option>
                        <option value="5">Kelas 5 MI</option>
                        <option value="6">Kelas 6 MI</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block font-semibold"
                        >Nama / Kode Rombel</label
                    >
                    <input
                        v-model="rombelForm.nama"
                        type="text"
                        :placeholder="`Contoh: C atau ${rombelForm.tingkat}-C`"
                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2 uppercase"
                    />
                </div>
                <div>
                    <label class="mb-1 block font-semibold"
                        >Wali Kelas (Opsional)</label
                    >
                    <input
                        v-model="rombelForm.wali"
                        list="guru-wali-list"
                        type="text"
                        placeholder="Pilih atau ketik nama wali kelas"
                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                    />
                    <datalist id="guru-wali-list">
                        <option v-for="g in guruOptions" :key="g" :value="g" />
                    </datalist>
                </div>
                <div class="flex items-end">
                    <Button
                        type="submit"
                        :disabled="savingRombel"
                        class="w-full bg-emerald-700 text-white hover:bg-emerald-800"
                    >
                        <LoaderCircle
                            v-if="savingRombel"
                            class="size-3.5 animate-spin"
                        />
                        <Plus v-else class="size-3.5" />
                        Simpan / Update Rombel
                    </Button>
                </div>
            </form>

            <!-- Registered Rombel Tags -->
            <div class="flex flex-wrap gap-2 pt-1">
                <div
                    v-for="r in rombels"
                    :key="r.id"
                    class="inline-flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-3 py-1.5 text-xs"
                >
                    <span
                        class="font-bold text-emerald-800 dark:text-emerald-300"
                        >{{ r.kode }}</span
                    >
                    <span class="text-muted-foreground"
                        >({{ activeCountForRombel(r.kode) }} siswa)</span
                    >
                    <span
                        v-if="r.wali"
                        class="text-[11px] font-medium text-blue-700 dark:text-blue-300"
                        >| Wali: {{ r.wali }}</span
                    >
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-blue-600"
                        title="Ubah Wali Kelas"
                        @click="editRombelWali(r)"
                    >
                        <Pencil class="size-3" />
                    </button>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-destructive"
                        title="Hapus Rombel"
                        @click="
                            totalCountForRombel(r.kode) > 0
                                ? toast.error(
                                      `Rombel ${r.kode} masih memiliki ${totalCountForRombel(r.kode)} siswa!`,
                                  )
                                : (deletingRombel = r)
                        "
                    >
                        <Trash2 class="size-3" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Batch Kenaikan Kelas / Pindah Rombel -->
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1.4fr_1fr]">
            <!-- Roster Selection -->
            <div
                class="space-y-3 rounded-2xl border border-border/70 bg-card p-5 text-xs shadow-xs"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold">
                            Pilih Siswa Rombel Asal
                        </h3>
                        <p class="text-[11px] text-muted-foreground">
                            Menampilkan {{ rosterStudents.length }} siswa aktif
                            · {{ selectedIds.length }} dipilih
                        </p>
                    </div>
                    <select
                        v-model="sourceRombel"
                        class="rounded-lg border border-input bg-background px-2.5 py-1.5 font-semibold"
                    >
                        <option
                            v-for="code in allRombelCodes"
                            :key="code"
                            :value="code"
                        >
                            Rombel Asal: Kelas {{ code }}
                        </option>
                        <option value="ALL">
                            Tampilkan Semua Rombel Aktif
                        </option>
                    </select>
                </div>

                <div
                    class="flex items-center justify-between rounded-lg bg-muted/50 px-3 py-2"
                >
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 font-semibold"
                    >
                        <input
                            type="checkbox"
                            :checked="allSelected"
                            class="rounded text-emerald-600"
                            @change="toggleSelectAll"
                        />
                        Pilih Semua di Daftar
                    </label>
                    <span class="font-mono text-[11px] text-muted-foreground">
                        Terpilih: {{ selectedIds.length }}
                    </span>
                </div>

                <div class="max-h-96 space-y-1.5 overflow-y-auto pr-1">
                    <div
                        v-if="rosterStudents.length === 0"
                        class="py-8 text-center text-muted-foreground"
                    >
                        Tidak ada siswa aktif pada rombel ini.
                    </div>
                    <label
                        v-for="s in rosterStudents"
                        :key="s.id"
                        class="flex cursor-pointer items-center justify-between rounded-lg border border-border/70 p-2.5 transition hover:bg-muted/40"
                    >
                        <span class="flex items-center gap-2.5">
                            <input
                                v-model="selectedIds"
                                type="checkbox"
                                :value="s.id"
                                class="rounded text-emerald-600"
                            />
                            <span class="font-bold uppercase">{{
                                s.nama
                            }}</span>
                            <span class="font-mono text-muted-foreground"
                                >({{ s.nisn || '-' }})</span
                            >
                        </span>
                        <span
                            class="rounded bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800"
                        >
                            {{ s.rombel }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Target Action -->
            <div
                class="h-fit space-y-4 rounded-2xl border border-border/70 bg-card p-5 text-xs shadow-xs"
            >
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <ArrowRightLeft class="size-4 text-emerald-700" />
                    Tujuan Pindah / Kenaikan Kelas
                </h3>

                <div>
                    <label class="mb-1 block font-semibold">
                        Pilih Rombel Tujuan atau Aksi Massal
                    </label>
                    <select
                        v-model="targetRombel"
                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-semibold"
                    >
                        <option
                            v-for="code in allRombelCodes"
                            :key="code"
                            :value="code"
                        >
                            Pindah / Naik ke Kelas {{ code }}
                        </option>
                        <option value="UPDATE_TINGKAT_BATCH">
                            Naik Tingkat Kelas MI Otomatis...
                        </option>
                        <option value="LULUS_BATCH">
                            Proses KELULUSAN Kelas 6 (Alumni)
                        </option>
                    </select>
                </div>

                <div
                    v-if="targetRombel === 'UPDATE_TINGKAT_BATCH'"
                    class="space-y-2 rounded-xl border border-blue-200 bg-blue-50/70 p-3"
                >
                    <label class="block font-semibold text-blue-950"
                        >Tingkat Kelas Baru</label
                    >
                    <select
                        v-model="batchNewTingkat"
                        class="w-full rounded-lg border border-blue-200 bg-white px-2.5 py-2 font-semibold text-slate-900"
                    >
                        <option value="2">Kelas 2 MI</option>
                        <option value="3">Kelas 3 MI</option>
                        <option value="4">Kelas 4 MI</option>
                        <option value="5">Kelas 5 MI</option>
                        <option value="6">Kelas 6 MI</option>
                    </select>
                </div>

                <div
                    v-if="targetRombel === 'LULUS_BATCH'"
                    class="space-y-2.5 rounded-xl border border-purple-200 bg-purple-50/70 p-3 text-slate-900"
                >
                    <div>
                        <label class="mb-1 block font-semibold"
                            >Tanggal Kelulusan</label
                        >
                        <input
                            v-model="batchTglLulus"
                            type="date"
                            class="w-full rounded-lg border border-purple-200 bg-white px-2.5 py-1.5"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold"
                            >Keterangan Kelulusan</label
                        >
                        <input
                            v-model="batchKetLulus"
                            type="text"
                            class="w-full rounded-lg border border-purple-200 bg-white px-2.5 py-1.5"
                        />
                    </div>
                </div>

                <div class="flex flex-col gap-2 pt-1">
                    <Button
                        type="button"
                        :disabled="batchProcessing"
                        class="w-full bg-emerald-700 font-bold text-white hover:bg-emerald-800"
                        @click="executeBatch(false)"
                    >
                        <LoaderCircle
                            v-if="batchProcessing"
                            class="size-3.5 animate-spin"
                        />
                        <CheckSquare v-else class="size-3.5" />
                        Proses Siswa Terpilih ({{ selectedIds.length }})
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="
                            batchProcessing || rosterStudents.length === 0
                        "
                        class="w-full"
                        @click="executeBatch(true)"
                    >
                        <GraduationCap class="size-3.5" />
                        Pindahkan Seluruh Rombel Asal ({{
                            rosterStudents.length
                        }})
                    </Button>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :open="Boolean(deletingRombel)"
            title="Hapus Rombel?"
            :description="`Rombel ${deletingRombel?.kode ?? ''} akan dihapus dari daftar rombongan belajar.`"
            confirm-label="Ya, Hapus Rombel"
            variant="destructive"
            :processing="deletingProcessing"
            @update:open="(v) => !v && (deletingRombel = null)"
            @confirm="confirmDeleteRombel"
        />
    </div>
</template>
