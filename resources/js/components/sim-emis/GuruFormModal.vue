<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, LoaderCircle, Save } from '@lucide/vue';
import { reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { createEmptyEmisGuru, formatNamaGelarGuru } from '@/types/emis';
import type { EmisGuru } from '@/types/emis';

const props = defineProps<{
    open: boolean;
    guru: EmisGuru | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const step = ref(1);
const processing = ref(false);
const errors = reactive<Record<string, boolean>>({});
const form = reactive(createEmptyEmisGuru());

const stepLabels = [
    '1. Identitas Pribadi',
    '2. Registrasi & Kepegawaian EMIS',
    '3. Pendidikan & Sertifikasi',
    '4. Penugasan & JTM',
];

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;
        step.value = 1;
        Object.keys(errors).forEach((k) => delete errors[k]);

        if (props.guru) {
            Object.assign(form, createEmptyEmisGuru(), props.guru);
        } else {
            Object.assign(form, createEmptyEmisGuru());
        }
    },
);

const submitForm = () => {
    Object.keys(errors).forEach((k) => delete errors[k]);
    const nama = form.nama.trim().toUpperCase();
    const nik = form.nik.trim();

    if (!nama) {
        step.value = 1;
        errors.nama = true;
        toast.error('Nama Lengkap Guru / GTK wajib diisi (Tab 1)!');
        return;
    }
    if (!nik || !/^\d{16}$/.test(nik)) {
        step.value = 1;
        errors.nik = true;
        toast.error('NIK Guru / GTK wajib diisi tepat 16 digit angka (Tab 1)!');
        return;
    }

    form.nama = nama;
    form.nik = nik;

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

    if (props.guru) {
        router.put(`/gtk/${props.guru.id}`, payload, options);
    } else {
        router.post('/gtk', payload, options);
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="flex max-h-[92vh] max-w-4xl flex-col overflow-hidden p-0"
        >
            <DialogHeader
                class="border-b border-border/70 bg-emerald-900 px-5 py-4 text-white"
            >
                <DialogTitle class="text-base font-bold text-white">
                    {{
                        guru
                            ? `Edit Data GTK: ${formatNamaGelarGuru(guru)}`
                            : 'Formulir Tambah Guru & GTK (EMIS 4.0)'
                    }}
                </DialogTitle>
                <DialogDescription class="text-xs text-emerald-100/80">
                    Lengkapi 4 tab atribut kepegawaian, registrasi
                    EMIS/Simpatika, pendidikan, sertifikasi &amp; JTM.
                </DialogDescription>
            </DialogHeader>

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
                    <div
                        v-show="step === 1"
                        class="grid grid-cols-1 gap-3.5 md:grid-cols-4"
                    >
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Gelar Depan</label
                            >
                            <input
                                v-model="form.gelarDepan"
                                type="text"
                                placeholder="Drs. / Hj."
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block font-semibold">
                                Nama Lengkap (Tanpa Gelar)
                                <span class="text-destructive">*</span>
                            </label>
                            <input
                                v-model="form.nama"
                                type="text"
                                class="w-full rounded-lg border px-2.5 py-2 font-bold uppercase outline-none focus:ring-2 focus:ring-emerald-600"
                                :class="
                                    errors.nama
                                        ? 'border-destructive bg-destructive/5'
                                        : 'border-input bg-background'
                                "
                                @input="errors.nama = false"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Gelar Belakang</label
                            >
                            <input
                                v-model="form.gelarBelakang"
                                type="text"
                                placeholder="S.Pd.I., M.Pd."
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold">
                                NIK (16 Digit KTP)
                                <span class="text-destructive">*</span>
                            </label>
                            <input
                                v-model="form.nik"
                                type="text"
                                maxlength="16"
                                inputmode="numeric"
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
                            <label class="mb-1 block font-semibold"
                                >No. Kartu Keluarga (KK)</label
                            >
                            <input
                                v-model="form.noKK"
                                type="text"
                                maxlength="16"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Jenis Kelamin</label
                            >
                            <select
                                v-model="form.jk"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Agama</label
                            >
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
                            <label class="mb-1 block font-semibold"
                                >Tempat Lahir</label
                            >
                            <input
                                v-model="form.tempatLahir"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Tanggal Lahir</label
                            >
                            <input
                                v-model="form.tglLahir"
                                type="date"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Nama Ibu Kandung</label
                            >
                            <input
                                v-model="form.namaIbu"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Status Perkawinan</label
                            >
                            <select
                                v-model="form.statusNikah"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Kawin">Kawin</option>
                                <option value="Belum Kawin">Belum Kawin</option>
                                <option value="Cerai Hidup">Cerai Hidup</option>
                                <option value="Cerai Mati">Cerai Mati</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >No. HP / WhatsApp</label
                            >
                            <input
                                v-model="form.hp"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Email Aktif</label
                            >
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block font-semibold"
                                >Alamat Domisili</label
                            >
                            <input
                                v-model="form.alamat"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                    </div>

                    <!-- STEP 2 -->
                    <div
                        v-show="step === 2"
                        class="grid grid-cols-1 gap-3.5 md:grid-cols-3"
                    >
                        <div>
                            <label class="mb-1 block font-semibold"
                                >PegID (Simpatika / EMIS)</label
                            >
                            <input
                                v-model="form.pegid"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >NPK (Nomor Pendidik Kemenag)</label
                            >
                            <input
                                v-model="form.npk"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >NUPTK (16 Digit)</label
                            >
                            <input
                                v-model="form.nuptk"
                                type="text"
                                maxlength="16"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >NIP (18 Digit PNS/PPPK)</label
                            >
                            <input
                                v-model="form.nip"
                                type="text"
                                maxlength="18"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Status Kepegawaian</label
                            >
                            <select
                                v-model="form.statusPegawai"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-semibold"
                            >
                                <option value="PNS">PNS</option>
                                <option value="PPPK">PPPK</option>
                                <option value="GTY">
                                    GTY (Guru Tetap Yayasan)
                                </option>
                                <option value="GTT / Honorer">
                                    GTT / Honorer
                                </option>
                                <option value="Tenaga Kependidikan">
                                    Tenaga Kependidikan
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Jenis PTK</label
                            >
                            <select
                                v-model="form.jenisPtk"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Guru Kelas MI">
                                    Guru Kelas MI
                                </option>
                                <option value="Guru Mata Pelajaran">
                                    Guru Mata Pelajaran
                                </option>
                                <option value="Kepala Madrasah">
                                    Kepala Madrasah
                                </option>
                                <option value="Guru BK / Konselor">
                                    Guru BK / Konselor
                                </option>
                                <option value="Kepala Tata Usaha">
                                    Kepala Tata Usaha
                                </option>
                                <option value="Staff Tata Usaha / Operator">
                                    Staff Tata Usaha / Operator
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Pangkat / Golongan</label
                            >
                            <input
                                v-model="form.golongan"
                                type="text"
                                placeholder="III/a, IV/a, IX (PPPK), atau -"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >TMT Pengangkatan Awal</label
                            >
                            <input
                                v-model="form.tmtAwal"
                                type="date"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >TMT Tugas di Madrasah</label
                            >
                            <input
                                v-model="form.tmtMadrasah"
                                type="date"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Status Inpassing (Non-PNS)</label
                            >
                            <select
                                v-model="form.inpassing"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Belum">Belum</option>
                                <option value="Sudah">Sudah Inpassing</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Status Keaktifan GTK</label
                            >
                            <select
                                v-model="form.statusAktif"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-semibold"
                            >
                                <option value="Aktif">Aktif</option>
                                <option value="Cuti">Cuti</option>
                                <option value="Mutasi Keluar">
                                    Mutasi Keluar
                                </option>
                                <option value="Pensiun">Pensiun</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Nomor SK Pengangkatan</label
                            >
                            <input
                                v-model="form.noSk"
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
                            <label class="mb-1 block font-semibold"
                                >Jenjang Pendidikan Terakhir</label
                            >
                            <select
                                v-model="form.pendidikan"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-semibold"
                            >
                                <option value="D4/S1">D4/S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                                <option value="D3">D3</option>
                                <option value="SMA/MA Sederajat">
                                    SMA/MA Sederajat
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Program Studi / Jurusan</label
                            >
                            <input
                                v-model="form.prodi"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Perguruan Tinggi / LPTK</label
                            >
                            <input
                                v-model="form.kampus"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Tahun Lulus</label
                            >
                            <input
                                v-model="form.tahunLulus"
                                type="text"
                                maxlength="4"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Status Sertifikasi Pendidik</label
                            >
                            <select
                                v-model="form.statusSertifikasi"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-semibold"
                            >
                                <option value="Belum Sertifikasi">
                                    Belum Sertifikasi
                                </option>
                                <option value="Sudah Sertifikasi">
                                    Sudah Sertifikasi
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >NRG (Nomor Registrasi Guru)</label
                            >
                            <input
                                v-model="form.nrg"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Nomor Sertifikat Pendidik</label
                            >
                            <input
                                v-model="form.noSerdik"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Bidang Studi Sertifikasi</label
                            >
                            <input
                                v-model="form.mapelSertifikasi"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Tahun Lulus Sertifikasi</label
                            >
                            <input
                                v-model="form.tahunSertifikasi"
                                type="text"
                                maxlength="4"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                    </div>

                    <!-- STEP 4 -->
                    <div
                        v-show="step === 4"
                        class="grid grid-cols-1 gap-3.5 md:grid-cols-3"
                    >
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Status Satminkal</label
                            >
                            <select
                                v-model="form.satminkal"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Satminkal (Induk)">
                                    Satminkal (Induk)
                                </option>
                                <option value="Non-Satminkal">
                                    Non-Satminkal
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Mata Pelajaran / Kelas Utama Diampu</label
                            >
                            <input
                                v-model="form.mapelUtama"
                                type="text"
                                placeholder="Guru Kelas 1-A / PJOK / PAI"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Total JTM per Minggu</label
                            >
                            <input
                                v-model.number="form.jtm"
                                type="number"
                                min="0"
                                max="60"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono font-bold"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Tugas Tambahan di Madrasah</label
                            >
                            <select
                                v-model="form.tugasTambahan"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2"
                            >
                                <option value="Tidak Ada">Tidak Ada</option>
                                <option value="Kepala Madrasah">
                                    Kepala Madrasah
                                </option>
                                <option value="Wakil Kepala Bidang Kurikulum">
                                    Wakil Kepala Bidang Kurikulum
                                </option>
                                <option value="Wakil Kepala Bidang Kesiswaan">
                                    Wakil Kepala Bidang Kesiswaan
                                </option>
                                <option value="Wali Kelas">Wali Kelas</option>
                                <option value="Kepala Perpustakaan / Lab">
                                    Kepala Perpustakaan / Lab
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold"
                                >Nomor Rekening TPG / Gaji</label
                            >
                            <input
                                v-model="form.rekening"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-semibold">NPWP</label>
                            <input
                                v-model="form.npwp"
                                type="text"
                                class="w-full rounded-lg border border-input bg-background px-2.5 py-2 font-mono"
                            />
                        </div>
                    </div>
                </div>

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
                            v-if="step < 4"
                            type="button"
                            variant="secondary"
                            size="sm"
                            @click="step = Math.min(4, step + 1)"
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
                            Simpan Data Guru
                        </Button>
                    </div>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
