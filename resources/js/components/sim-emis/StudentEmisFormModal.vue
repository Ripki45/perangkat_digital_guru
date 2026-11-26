<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Camera,
    ChevronLeft,
    ChevronRight,
    LoaderCircle,
    Save,
    Trash2,
    UserRound,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { createEmptyEmisStudent } from '@/types/emis';
import type { EmisRombel, EmisStudent } from '@/types/emis';

const props = defineProps<{
    open: boolean;
    student: EmisStudent | null;
    rombels: EmisRombel[];
    allRombelCodes: string[];
    defaultTab?: number;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const step = ref(1);
const processing = ref(false);
const errors = reactive<Record<string, boolean>>({});
const form = reactive(createEmptyEmisStudent());

const stepLabels = [
    '1. Identitas & Foto 3x4',
    '2. Akademik, Rombel & RA/TK',
    '3. Alamat & Pesantren',
    '4. Orang Tua & Wali',
    '5. PIP / PKH & Kesehatan',
    '6. Riwayat Keluar & Ijazah',
];

const rombelGroups = computed(() => {
    const tingkat = form.tingkat || '1';
    const matching = props.allRombelCodes.filter((code) => {
        const reg = props.rombels.find((r) => r.kode === code);
        if (reg && reg.tingkat === tingkat) return true;
        return code.startsWith(`${tingkat}-`) || code === tingkat;
    });
    const others = props.allRombelCodes.filter((c) => !matching.includes(c));
    return { matching, others };
});

const rombelLabel = (code: string): string => {
    const reg = props.rombels.find((r) => r.kode === code);
    return reg?.wali ? `${code} (Wali: ${reg.wali})` : code;
};

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;
        step.value = props.defaultTab ?? 1;
        Object.keys(errors).forEach((k) => delete errors[k]);

        if (props.student) {
            Object.assign(form, createEmptyEmisStudent(), props.student);
        } else {
            Object.assign(form, createEmptyEmisStudent());
            if (rombelGroups.value.matching.length > 0) {
                form.rombel = rombelGroups.value.matching[0];
                form.rombelMasuk = rombelGroups.value.matching[0];
            }
        }
    },
);

const onTingkatChange = () => {
    if (rombelGroups.value.matching.length > 0) {
        form.rombel = rombelGroups.value.matching[0];
    } else {
        form.rombel = `${form.tingkat}-A`;
    }
};

const onRombelChange = () => {
    const reg = props.rombels.find((r) => r.kode === form.rombel);
    const detected = reg?.tingkat || form.rombel.split('-')[0];
    if (['1', '2', '3', '4', '5', '6'].includes(detected)) {
        form.tingkat = detected;
    }
};

const syncKeluarKeStatus = () => {
    if (form.keluarDariMadrasah === 'Masih Aktif') form.statusSiswa = 'Aktif';
    else if (form.keluarDariMadrasah === 'Lulus') form.statusSiswa = 'Lulus';
    else if (form.keluarDariMadrasah === 'Putus Sekolah')
        form.statusSiswa = 'Putus Sekolah';
    else form.statusSiswa = 'Mutasi Keluar';
};

const handleFotoUpload = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = 300;
            canvas.height = 400;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            const scale = Math.max(300 / img.width, 400 / img.height);
            const w = img.width * scale;
            const h = img.height * scale;
            ctx.drawImage(img, (300 - w) / 2, (400 - h) / 2, w, h);
            form.foto = canvas.toDataURL('image/jpeg', 0.75);
            toast.success('Pas foto 3x4 berhasil dikompresi dan dipasang.');
        };
        img.src = String(e.target?.result ?? '');
    };
    reader.readAsDataURL(file);
    input.value = '';
};

const submitForm = () => {
    Object.keys(errors).forEach((k) => delete errors[k]);
    const nama = form.nama.trim().toUpperCase();
    const nisn = form.nisn.trim();
    const nik = form.nik.trim();

    if (!nama) {
        step.value = 1;
        errors.nama = true;
        toast.error('Nama Lengkap Siswa wajib diisi (Tab 1)!');
        return;
    }
    if (!nisn || !/^\d{10}$/.test(nisn)) {
        step.value = 1;
        errors.nisn = true;
        toast.error('NISN wajib diisi tepat 10 digit angka (Tab 1)!');
        return;
    }
    if (!nik || !/^\d{16}$/.test(nik)) {
        step.value = 1;
        errors.nik = true;
        toast.error('NIK Siswa wajib diisi tepat 16 digit angka (Tab 1)!');
        return;
    }

    form.nama = nama;
    form.nisn = nisn;
    form.nik = nik;
    if (!form.rombelMasuk.trim()) {
        form.rombelMasuk = form.rombel;
    }

    processing.value = true;
    const payload = { ...form };

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
        },
        onError: (serverErrors: Record<string, string>) => {
            const firstMsg = Object.values(serverErrors)[0];
            if (firstMsg) {
                toast.error(firstMsg);
            }
        },
        onFinish: () => {
            processing.value = false;
        },
    };

    if (props.student) {
        router.put(`/siswa/${props.student.id}`, payload, options);
    } else {
        router.post('/siswa', payload, options);
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="flex max-h-[92vh] max-w-5xl flex-col overflow-hidden p-0"
        >
            <DialogHeader
                class="border-b border-border/70 bg-emerald-900 px-5 py-4 text-white"
            >
                <DialogTitle class="text-base font-bold text-white">
                    {{
                        student
                            ? `Edit Data Siswa MI: ${student.nama}`
                            : 'Formulir Tambah Siswa MI (EMIS 4.0)'
                    }}
                </DialogTitle>
                <DialogDescription class="text-xs text-emerald-100/80">
                    Lengkapi 6 tahap biodata EMIS 4.0 peserta didik Madrasah
                    Ibtidaiyah.
                </DialogDescription>
            </DialogHeader>

            <!-- Stepper Tabs -->
            <div
                class="flex gap-1 overflow-x-auto border-b border-border/70 bg-muted/40 px-4 py-2"
            >
                <button
                    v-for="(label, idx) in stepLabels"
                    :key="label"
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium whitespace-nowrap transition"
                    :class="
                        step === idx + 1
                            ? 'bg-emerald-700 font-semibold text-white shadow-xs'
                            : 'text-muted-foreground hover:bg-muted'
                    "
                    @click="step = idx + 1"
                >
                    {{ label }}
                </button>
            </div>

            <form
                class="flex min-h-0 flex-1 flex-col"
                novalidate
                @submit.prevent="submitForm"
            >
                <div class="flex-1 overflow-y-auto p-5 text-xs">
                    <!-- STEP 1 -->
                    <div v-show="step === 1" class="space-y-4">
                        <div class="flex flex-col gap-5 lg:flex-row">
                            <!-- Pas Foto 3x4 -->
                            <div
                                class="flex shrink-0 flex-col items-center gap-2 rounded-xl border border-border/70 bg-muted/30 p-3 lg:w-44"
                            >
                                <div
                                    class="flex h-40 w-30 items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-border bg-white shadow-xs"
                                >
                                    <img
                                        v-if="form.foto"
                                        :src="form.foto"
                                        alt="Pas Foto 3x4"
                                        class="size-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="px-2 text-center text-muted-foreground"
                                    >
                                        <UserRound
                                            class="mx-auto mb-1 size-7 opacity-50"
                                        />
                                        <span
                                            class="text-[10px] font-bold uppercase"
                                        >
                                            Pas Foto 3x4
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-1.5">
                                    <label
                                        class="inline-flex cursor-pointer items-center gap-1 rounded-md bg-emerald-700 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-800"
                                    >
                                        <Camera class="size-3" />
                                        Pilih Foto
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="handleFotoUpload"
                                        />
                                    </label>
                                    <button
                                        v-if="form.foto"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-md bg-destructive/10 px-2 py-1.5 text-[11px] font-semibold text-destructive hover:bg-destructive/20"
                                        @click="form.foto = ''"
                                    >
                                        <Trash2 class="size-3" />
                                    </button>
                                </div>
                            </div>

                            <!-- Identitas -->
                            <div
                                class="grid flex-1 grid-cols-1 gap-3 md:grid-cols-3"
                            >
                                <div class="md:col-span-2">
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        Nama Lengkap Siswa
                                        <span class="text-destructive">*</span>
                                    </label>
                                    <input
                                        v-model="form.nama"
                                        type="text"
                                        placeholder="Sesuai Akta Kelahiran / KK"
                                        class="w-full rounded-lg border px-2.5 py-2 uppercase outline-none focus:ring-2 focus:ring-emerald-600"
                                        :class="
                                            errors.nama
                                                ? 'border-destructive bg-destructive/5'
                                                : 'border-input bg-background'
                                        "
                                        @input="errors.nama = false"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        Jenis Kelamin
                                    </label>
                                    <select
                                        v-model="form.jk"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                    >
                                        <option value="L">Laki-laki (L)</option>
                                        <option value="P">Perempuan (P)</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        NISN (10 Digit)
                                        <span class="text-destructive">*</span>
                                    </label>
                                    <input
                                        v-model="form.nisn"
                                        type="text"
                                        maxlength="10"
                                        inputmode="numeric"
                                        placeholder="Contoh: 0191234567"
                                        class="w-full rounded-lg border px-2.5 py-2 font-mono outline-none focus:ring-2 focus:ring-emerald-600"
                                        :class="
                                            errors.nisn
                                                ? 'border-destructive bg-destructive/5'
                                                : 'border-input bg-background'
                                        "
                                        @input="errors.nisn = false"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        NIK Siswa (16 Digit)
                                        <span class="text-destructive">*</span>
                                    </label>
                                    <input
                                        v-model="form.nik"
                                        type="text"
                                        maxlength="16"
                                        inputmode="numeric"
                                        placeholder="16 Digit Angka NIK"
                                        class="w-full rounded-lg border px-2.5 py-2 font-mono outline-none focus:ring-2 focus:ring-emerald-600"
                                        :class="
                                            errors.nik
                                                ? 'border-destructive bg-destructive/5'
                                                : 'border-input bg-background'
                                        "
                                        @input="errors.nik = false"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        No. Kartu Keluarga (KK)
                                    </label>
                                    <input
                                        v-model="form.noKK"
                                        type="text"
                                        maxlength="16"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        NIS Lokal Madrasah
                                    </label>
                                    <input
                                        v-model="form.nisLokal"
                                        type="text"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        Tempat Lahir
                                    </label>
                                    <input
                                        v-model="form.tempatLahir"
                                        type="text"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        Tanggal Lahir
                                    </label>
                                    <input
                                        v-model="form.tglLahir"
                                        type="date"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        Agama
                                    </label>
                                    <select
                                        v-model="form.agama"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                    >
                                        <option value="Islam">Islam</option>
                                        <option value="Kristen">Kristen</option>
                                        <option value="Katolik">Katolik</option>
                                        <option value="Hindu">Hindu</option>
                                        <option value="Buddha">Buddha</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        Kewarganegaraan
                                    </label>
                                    <select
                                        v-model="form.kewarganegaraan"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                    >
                                        <option value="WNI">WNI</option>
                                        <option value="WNA">WNA</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label
                                            class="mb-1 block font-semibold text-foreground"
                                        >
                                            Anak Ke-
                                        </label>
                                        <input
                                            v-model.number="form.anakKe"
                                            type="number"
                                            min="1"
                                            class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="mb-1 block font-semibold text-foreground"
                                        >
                                            Jml Saudara
                                        </label>
                                        <input
                                            v-model.number="form.jmlSaudara"
                                            type="number"
                                            min="0"
                                            class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        No. HP Siswa / Ortu
                                    </label>
                                    <input
                                        v-model="form.hpSiswa"
                                        type="text"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        Cita-Cita
                                    </label>
                                    <input
                                        v-model="form.citaCita"
                                        type="text"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block font-semibold text-foreground"
                                    >
                                        Hobi
                                    </label>
                                    <input
                                        v-model="form.hobi"
                                        type="text"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2 -->
                    <div
                        v-show="step === 2"
                        class="grid grid-cols-1 gap-3.5 md:grid-cols-3"
                    >
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Tingkat Kelas MI
                            </label>
                            <select
                                v-model="form.tingkat"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-bold"
                                @change="onTingkatChange"
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
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Rombel Aktif
                            </label>
                            <select
                                v-model="form.rombel"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-semibold"
                                @change="onRombelChange"
                            >
                                <optgroup
                                    v-if="rombelGroups.matching.length > 0"
                                    :label="`Rombel Kelas ${form.tingkat} MI`"
                                >
                                    <option
                                        v-for="c in rombelGroups.matching"
                                        :key="c"
                                        :value="c"
                                    >
                                        {{ rombelLabel(c) }}
                                    </option>
                                </optgroup>
                                <optgroup
                                    v-if="rombelGroups.others.length > 0"
                                    label="Rombel MI Lainnya"
                                >
                                    <option
                                        v-for="c in rombelGroups.others"
                                        :key="c"
                                        :value="c"
                                    >
                                        {{ rombelLabel(c) }}
                                    </option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Rombel Awal Masuk
                            </label>
                            <input
                                v-model="form.rombelMasuk"
                                type="text"
                                placeholder="Contoh: 1-A"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 uppercase"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Tanggal Masuk Madrasah
                            </label>
                            <input
                                v-model="form.tglMasuk"
                                type="date"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Jenis Pendaftaran
                            </label>
                            <select
                                v-model="form.jenisPendaftaran"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Siswa Baru">Siswa Baru</option>
                                <option value="Pindahan">
                                    Pindahan (Mutasi Masuk)
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Status Siswa
                            </label>
                            <select
                                v-model="form.statusSiswa"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-semibold"
                            >
                                <option value="Aktif">Aktif</option>
                                <option value="Lulus">Lulus</option>
                                <option value="Mutasi Keluar">
                                    Mutasi Keluar
                                </option>
                                <option value="Putus Sekolah">
                                    Putus Sekolah
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Asal Sekolah (RA / TK / PAUD)
                            </label>
                            <input
                                v-model="form.asalSekolah"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                NPSN RA / TK Asal
                            </label>
                            <input
                                v-model="form.npsnAsal"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                No. Surat Keterangan RA/TK
                            </label>
                            <input
                                v-model="form.noIjazahAsal"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div class="md:col-span-3">
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                No. Registrasi Akta Kelahiran
                            </label>
                            <input
                                v-model="form.noUnSebelumnya"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                    </div>

                    <!-- STEP 3 -->
                    <div
                        v-show="step === 3"
                        class="grid grid-cols-1 gap-3.5 md:grid-cols-3"
                    >
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Jenis Tempat Tinggal
                            </label>
                            <select
                                v-model="form.tempatTinggal"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Bersama Orang Tua">
                                    Bersama Orang Tua
                                </option>
                                <option value="Pondok Pesantren">
                                    Pondok Pesantren (Santri Mukim)
                                </option>
                                <option value="Wali">Wali</option>
                                <option value="Kos/Asrama">Kos/Asrama</option>
                                <option value="Panti Asuhan">
                                    Panti Asuhan
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Nama Pondok Pesantren
                            </label>
                            <input
                                v-model="form.namaPondok"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                NSP Pesantren
                            </label>
                            <input
                                v-model="form.nspPesantren"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Alamat Jalan / Dusun
                            </label>
                            <input
                                v-model="form.alamatJalan"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    RT
                                </label>
                                <input
                                    v-model="form.rt"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    RW
                                </label>
                                <input
                                    v-model="form.rw"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                                />
                            </div>
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Kelurahan / Desa
                            </label>
                            <input
                                v-model="form.desa"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Kecamatan
                            </label>
                            <input
                                v-model="form.kecamatan"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Kabupaten / Kota
                            </label>
                            <input
                                v-model="form.kabupaten"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Provinsi
                            </label>
                            <input
                                v-model="form.provinsi"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Kode Pos
                            </label>
                            <input
                                v-model="form.kodePos"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Jarak ke Madrasah
                            </label>
                            <input
                                v-model="form.jarak"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Moda Transportasi
                            </label>
                            <input
                                v-model="form.transportasi"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Waktu Tempuh
                            </label>
                            <input
                                v-model="form.waktuTempuh"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                    </div>

                    <!-- STEP 4 -->
                    <div
                        v-show="step === 4"
                        class="grid grid-cols-1 gap-3.5 md:grid-cols-3"
                    >
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Nama Ayah Kandung
                            </label>
                            <input
                                v-model="form.ayahNama"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Status Ayah
                            </label>
                            <select
                                v-model="form.ayahStatus"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Masih Hidup">Masih Hidup</option>
                                <option value="Sudah Meninggal">
                                    Sudah Meninggal
                                </option>
                                <option value="Tidak Diketahui">
                                    Tidak Diketahui
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                NIK Ayah
                            </label>
                            <input
                                v-model="form.ayahNik"
                                type="text"
                                maxlength="16"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Tempat, Tgl Lahir Ayah
                            </label>
                            <input
                                v-model="form.ayahTtl"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Pendidikan Ayah
                            </label>
                            <input
                                v-model="form.ayahPendidikan"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    Pekerjaan Ayah
                                </label>
                                <input
                                    v-model="form.ayahPekerjaan"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    Penghasilan Ayah
                                </label>
                                <input
                                    v-model="form.ayahPenghasilan"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Nama Ibu Kandung
                            </label>
                            <input
                                v-model="form.ibuNama"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Status Ibu
                            </label>
                            <select
                                v-model="form.ibuStatus"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Masih Hidup">Masih Hidup</option>
                                <option value="Sudah Meninggal">
                                    Sudah Meninggal
                                </option>
                                <option value="Tidak Diketahui">
                                    Tidak Diketahui
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                NIK Ibu
                            </label>
                            <input
                                v-model="form.ibuNik"
                                type="text"
                                maxlength="16"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Tempat, Tgl Lahir Ibu
                            </label>
                            <input
                                v-model="form.ibuTtl"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Pendidikan Ibu
                            </label>
                            <input
                                v-model="form.ibuPendidikan"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    Pekerjaan Ibu
                                </label>
                                <input
                                    v-model="form.ibuPekerjaan"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    Penghasilan Ibu
                                </label>
                                <input
                                    v-model="form.ibuPenghasilan"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                No. HP / WhatsApp Ortu
                            </label>
                            <input
                                v-model="form.ortuHp"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Nama Wali (Opsional)
                            </label>
                            <input
                                v-model="form.waliNama"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                NIK Wali
                            </label>
                            <input
                                v-model="form.waliNik"
                                type="text"
                                maxlength="16"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Pekerjaan Wali
                            </label>
                            <input
                                v-model="form.waliPekerjaan"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Penghasilan Wali
                            </label>
                            <input
                                v-model="form.waliPenghasilan"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    Hubungan Wali
                                </label>
                                <input
                                    v-model="form.waliHubungan"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    No. HP Wali
                                </label>
                                <input
                                    v-model="form.waliHp"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5 -->
                    <div
                        v-show="step === 5"
                        class="grid grid-cols-1 gap-3.5 md:grid-cols-3"
                    >
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Status Penerima PIP
                            </label>
                            <select
                                v-model="form.statusPip"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Tidak">Tidak</option>
                                <option value="Ya">Ya</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Nomor KIP
                            </label>
                            <input
                                v-model="form.noKip"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    No. Rek PIP
                                </label>
                                <input
                                    v-model="form.rekPip"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    Atas Nama PIP
                                </label>
                                <input
                                    v-model="form.namaRekPip"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    Status PKH
                                </label>
                                <select
                                    v-model="form.statusPkh"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                >
                                    <option value="Tidak">Tidak</option>
                                    <option value="Ya">Ya</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    Nomor PKH
                                </label>
                                <input
                                    v-model="form.noPkh"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    Status KKS
                                </label>
                                <select
                                    v-model="form.statusKks"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                >
                                    <option value="Tidak">Tidak</option>
                                    <option value="Ya">Ya</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    Nomor KKS
                                </label>
                                <input
                                    v-model="form.noKks"
                                    type="text"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    TB (cm)
                                </label>
                                <input
                                    v-model.number="form.tb"
                                    type="number"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    BB (kg)
                                </label>
                                <input
                                    v-model.number="form.bb"
                                    type="number"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block font-semibold text-foreground"
                                >
                                    LK (cm)
                                </label>
                                <input
                                    v-model.number="form.lingkarKepala"
                                    type="number"
                                    class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                                />
                            </div>
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Golongan Darah
                            </label>
                            <select
                                v-model="form.golDarah"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Tidak Tahu">Tidak Tahu</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Kebutuhan Khusus / Disabilitas
                            </label>
                            <input
                                v-model="form.disabilitas"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                    </div>

                    <!-- STEP 6 -->
                    <div
                        v-show="step === 6"
                        class="grid grid-cols-1 gap-3.5 md:grid-cols-2"
                    >
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Alasan Keluar dari Madrasah
                            </label>
                            <select
                                v-model="form.keluarDariMadrasah"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-semibold"
                                @change="syncKeluarKeStatus"
                            >
                                <option value="Masih Aktif">Masih Aktif</option>
                                <option value="Lulus">Lulus</option>
                                <option value="Mutasi / Pindah Sekolah">
                                    Mutasi / Pindah Sekolah
                                </option>
                                <option value="Mengundurkan Diri">
                                    Mengundurkan Diri
                                </option>
                                <option value="Putus Sekolah">
                                    Putus Sekolah
                                </option>
                                <option value="Wafat">Wafat</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Tanggal Keluar / Lulus
                            </label>
                            <input
                                v-model="form.tglKeluar"
                                type="date"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Nomor Seri Ijazah MI
                            </label>
                            <input
                                v-model="form.noSeriIjazahMadrasah"
                                type="text"
                                placeholder="Contoh: MI-26/02/00123"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                MTs / SMP Tujuan Lanjutan
                            </label>
                            <input
                                v-model="form.sekolahTujuan"
                                type="text"
                                placeholder="Contoh: MTsN 1 Ciamis"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label
                                class="mb-1 block font-semibold text-foreground"
                            >
                                Keterangan Keluar
                            </label>
                            <input
                                v-model="form.keteranganKeluar"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div
                    class="flex items-center justify-between border-t border-border/70 bg-muted/30 px-5 py-3"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="step === 1"
                        @click="step = Math.max(1, step - 1)"
                    >
                        <ChevronLeft class="size-3.5" />
                        Sebelumnya
                    </Button>
                    <div class="flex items-center gap-2">
                        <Button
                            v-if="step < 6"
                            type="button"
                            variant="secondary"
                            size="sm"
                            @click="step = Math.min(6, step + 1)"
                        >
                            Selanjutnya
                            <ChevronRight class="size-3.5" />
                        </Button>
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="processing"
                            class="bg-emerald-700 text-white hover:bg-emerald-800"
                        >
                            <LoaderCircle
                                v-if="processing"
                                class="size-3.5 animate-spin"
                            />
                            <Save v-else class="size-3.5" />
                            Simpan Data Siswa
                        </Button>
                    </div>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
