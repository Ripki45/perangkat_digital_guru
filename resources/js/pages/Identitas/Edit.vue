<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    Building,
    Globe,
    IdCard,
    ImagePlus,
    LoaderCircle,
    Mail,
    MapPin,
    Phone,
    Save,
    School,
    Sparkles,
    Trash2,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import FormField from '@/components/app/FormField.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import { buildOfficialKopHtml } from '@/components/sim-emis/EmisPrintHelpers';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { classLabel } from '@/lib/format';
import type { TeacherProfile } from '@/types';
import type { EmisMadrasahProfile } from '@/types/emis';

const props = defineProps<{
    profile: TeacherProfile | null;
    defaults: {
        nama_guru: string;
        tahun_ajaran: string;
        semester: string;
        nama_madrasah: string;
    };
    completeness: number;
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'Identitas Guru & Madrasah', href: '/identitas' },
        ],
    }),
});

const form = useForm({
    nama_guru: props.profile?.nama_guru ?? props.defaults.nama_guru,
    nip: props.profile?.nip ?? '',
    nuptk: props.profile?.nuptk ?? '',
    jabatan: props.profile?.jabatan ?? 'Guru Kelas',
    mata_pelajaran: props.profile?.mata_pelajaran ?? '',
    kelas_wali: props.profile?.kelas_wali ?? '',
    tahun_ajaran: props.profile?.tahun_ajaran ?? props.defaults.tahun_ajaran,
    semester: props.profile?.semester ?? props.defaults.semester,
    motto: props.profile?.motto ?? '',
    nama_madrasah: props.profile?.nama_madrasah ?? props.defaults.nama_madrasah,
    npsn: props.profile?.npsn ?? '',
    nsm: props.profile?.nsm ?? '',
    kemenag_kab:
        props.profile?.kemenag_kab ?? 'Kantor Kementerian Agama Kab. Ciamis',
    tempat_surat:
        props.profile?.tempat_surat ?? props.profile?.kabupaten ?? 'Ciamis',
    alamat: props.profile?.alamat ?? '',
    desa: props.profile?.desa ?? '',
    kecamatan: props.profile?.kecamatan ?? '',
    kabupaten: props.profile?.kabupaten ?? '',
    provinsi: props.profile?.provinsi ?? '',
    kode_pos: props.profile?.kode_pos ?? '',
    telepon: props.profile?.telepon ?? '',
    email_madrasah: props.profile?.email_madrasah ?? '',
    website: props.profile?.website ?? '',
    nama_kepala_madrasah: props.profile?.nama_kepala_madrasah ?? '',
    nip_kepala_madrasah: props.profile?.nip_kepala_madrasah ?? '',
    operator_nama: props.profile?.operator_nama ?? '',
    operator_hp: props.profile?.operator_hp ?? '',
    logo_kiri: props.profile?.logo_kiri ?? '',
    logo_kanan: props.profile?.logo_kanan ?? '',
});

const logoKiriInputRef = ref<HTMLInputElement | null>(null);
const logoKananInputRef = ref<HTMLInputElement | null>(null);

const handleLogoUpload = (event: Event, target: 'logo_kiri' | 'logo_kanan') => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            const maxDim = 240;
            let width = img.width;
            let height = img.height;
            if (width > height && width > maxDim) {
                height = Math.round((height * maxDim) / width);
                width = maxDim;
            } else if (height > maxDim) {
                width = Math.round((width * maxDim) / height);
                height = maxDim;
            }
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx?.drawImage(img, 0, 0, width, height);
            form[target] = canvas.toDataURL('image/png');
            toast.success(
                'Logo berhasil dimuat. Klik Simpan Identitas untuk menyimpan.',
            );
        };
        img.src = String(e.target?.result || '');
    };
    reader.readAsDataURL(file);
    input.value = '';
};

const kopPreviewHtml = computed(() => {
    const alamatParts = [
        form.alamat,
        form.kecamatan,
        form.kabupaten,
        form.provinsi,
    ].filter(Boolean);
    const emisProfile: EmisMadrasahProfile = {
        namaMadrasah: form.nama_madrasah || 'MIN 1 Ciamis',
        tahunPelajaran: form.tahun_ajaran || '2025/2026',
        nsm: form.nsm || '-',
        npsn: form.npsn || '-',
        kemenagKab: form.kemenag_kab || 'Kantor Kementerian Agama',
        alamat: alamatParts.join(', ') || '-',
        tempatSurat: form.tempat_surat || form.kabupaten || 'Ciamis',
        website: form.website || '-',
        email: form.email_madrasah || '-',
        kepalaNama: form.nama_kepala_madrasah || '-',
        kepalaNip: form.nip_kepala_madrasah || '-',
        operatorNama: form.operator_nama || '-',
        operatorHp: form.operator_hp || '-',
        logoKiri: form.logo_kiri || '',
        logoKanan: form.logo_kanan || '',
    };
    return buildOfficialKopHtml(emisProfile);
});

const trackedValues = computed(() => [
    form.nama_guru,
    form.nip,
    form.jabatan,
    form.mata_pelajaran,
    form.nama_madrasah,
    form.npsn,
    form.alamat,
    form.kecamatan,
    form.kabupaten,
    form.tahun_ajaran,
    form.semester,
    form.nama_kepala_madrasah,
]);

const liveCompleteness = computed(() => {
    const filled = trackedValues.value.filter(
        (value) => String(value ?? '').trim() !== '',
    ).length;

    return Math.round((filled / trackedValues.value.length) * 100);
});

const departure = computed(
    () =>
        [form.kabupaten, form.provinsi]
            .filter((value) => String(value ?? '').trim() !== '')
            .join(', ') || 'Kabupaten, Provinsi',
);

const submit = () => {
    form.put('/identitas', {
        preserveScroll: true,
    });
};

const websitePlaceholder = 'https://mialhikmah.sch.id';
</script>

<template>
    <Head title="Identitas Guru & Madrasah" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Beranda & Profil"
            title="Identitas Guru & Madrasah"
            description="Data ini menjadi rujukan otomatis untuk kop dokumen, tanda tangan, dan seluruh perangkat administrasi Anda."
            :icon="IdCard"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/beranda">
                        <ArrowLeft />
                        Beranda
                    </Link>
                </Button>
                <Button
                    type="submit"
                    form="identitas-form"
                    :disabled="form.processing"
                >
                    <LoaderCircle v-if="form.processing" class="animate-spin" />
                    <Save v-else />
                    Simpan Identitas
                </Button>
            </template>
        </PageHeader>

        <form
            id="identitas-form"
            class="grid items-start gap-5 xl:grid-cols-[1.55fr_1fr]"
            @submit.prevent="submit"
        >
            <div class="space-y-5">
                <!-- Data guru -->
                <section
                    class="rounded-2xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(7,63,36,0.04)]"
                >
                    <div
                        class="flex items-center gap-3 border-b border-border/70 px-5 py-4"
                    >
                        <div
                            class="grid size-9 place-items-center rounded-lg bg-[#107c41]/8 text-[#107c41] ring-1 ring-[#107c41]/12"
                        >
                            <UserRound class="size-4" />
                        </div>
                        <div>
                            <h2
                                class="font-serif text-base font-bold text-foreground"
                            >
                                Data Guru
                            </h2>
                            <p class="text-[11px] text-muted-foreground">
                                Identitas utama pemilik akun perangkat digital.
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <FormField
                                label="Nama Lengkap & Gelar"
                                for="nama_guru"
                                required
                                :error="form.errors.nama_guru"
                            >
                                <Input
                                    id="nama_guru"
                                    v-model="form.nama_guru"
                                    placeholder="Contoh: Hj. Siti Aminah, S.Pd.I."
                                />
                            </FormField>
                        </div>
                        <FormField
                            label="NIP"
                            for="nip"
                            :error="form.errors.nip"
                        >
                            <Input
                                id="nip"
                                v-model="form.nip"
                                placeholder="198504122010012003"
                            />
                        </FormField>
                        <FormField
                            label="NUPTK"
                            for="nuptk"
                            :error="form.errors.nuptk"
                        >
                            <Input
                                id="nuptk"
                                v-model="form.nuptk"
                                placeholder="Opsional"
                            />
                        </FormField>
                        <FormField
                            label="Jabatan"
                            for="jabatan"
                            :error="form.errors.jabatan"
                        >
                            <Input
                                id="jabatan"
                                v-model="form.jabatan"
                                placeholder="Guru Kelas / Wali Kelas"
                            />
                        </FormField>
                        <FormField
                            label="Mata Pelajaran"
                            for="mata_pelajaran"
                            :error="form.errors.mata_pelajaran"
                        >
                            <Input
                                id="mata_pelajaran"
                                v-model="form.mata_pelajaran"
                                placeholder="Guru Kelas"
                            />
                        </FormField>
                        <FormField
                            label="Kelas Wali"
                            for="kelas_wali"
                            :error="form.errors.kelas_wali"
                        >
                            <Input
                                id="kelas_wali"
                                v-model="form.kelas_wali"
                                placeholder="5A"
                            />
                        </FormField>
                        <FormField
                            label="Tahun Ajaran"
                            for="tahun_ajaran"
                            required
                            hint="Format: 2025/2026"
                            :error="form.errors.tahun_ajaran"
                        >
                            <Input
                                id="tahun_ajaran"
                                v-model="form.tahun_ajaran"
                                placeholder="2025/2026"
                            />
                        </FormField>
                        <FormField
                            label="Semester"
                            for="semester"
                            required
                            :error="form.errors.semester"
                        >
                            <select
                                id="semester"
                                v-model="form.semester"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </FormField>
                        <div class="sm:col-span-2">
                            <FormField
                                label="Moto / Kutipan"
                                for="motto"
                                hint="Ditampilkan pada kartu profil di sidebar."
                                :error="form.errors.motto"
                            >
                                <Input
                                    id="motto"
                                    v-model="form.motto"
                                    placeholder="Mendidik dengan hati, menumbuhkan cinta ilmu."
                                />
                            </FormField>
                        </div>
                    </div>
                </section>

                <!-- Data madrasah -->
                <section
                    class="rounded-2xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(7,63,36,0.04)]"
                >
                    <div
                        class="flex items-center gap-3 border-b border-border/70 px-5 py-4"
                    >
                        <div
                            class="grid size-9 place-items-center rounded-lg bg-[#c99700]/10 text-[#a87c00] ring-1 ring-[#c99700]/15"
                        >
                            <School class="size-4" />
                        </div>
                        <div>
                            <h2
                                class="font-serif text-base font-bold text-foreground"
                            >
                                Data Madrasah
                            </h2>
                            <p class="text-[11px] text-muted-foreground">
                                Digunakan pada kop dan identitas dokumen resmi.
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <FormField
                            label="Nama Madrasah"
                            for="nama_madrasah"
                            required
                            :error="form.errors.nama_madrasah"
                        >
                            <Input
                                id="nama_madrasah"
                                v-model="form.nama_madrasah"
                                placeholder="MI Al-Hikmah"
                            />
                        </FormField>
                        <FormField
                            label="NPSN"
                            for="npsn"
                            :error="form.errors.npsn"
                        >
                            <Input
                                id="npsn"
                                v-model="form.npsn"
                                placeholder="20512345"
                            />
                        </FormField>
                        <FormField
                            label="NSM (Nomor Statistik Madrasah)"
                            for="nsm"
                            :error="form.errors.nsm"
                        >
                            <Input
                                id="nsm"
                                v-model="form.nsm"
                                placeholder="111132070001"
                            />
                        </FormField>
                        <FormField
                            label="Kantor Kemenag Kab/Kota"
                            for="kemenag_kab"
                            :error="form.errors.kemenag_kab"
                        >
                            <Input
                                id="kemenag_kab"
                                v-model="form.kemenag_kab"
                                placeholder="Kantor Kementerian Agama Kab. Ciamis"
                            />
                        </FormField>
                        <FormField
                            label="Kota / Tempat Surat"
                            for="tempat_surat"
                            :error="form.errors.tempat_surat"
                        >
                            <Input
                                id="tempat_surat"
                                v-model="form.tempat_surat"
                                placeholder="Ciamis"
                            />
                        </FormField>
                        <FormField
                            label="Nama Operator EMIS"
                            for="operator_nama"
                            :error="form.errors.operator_nama"
                        >
                            <Input
                                id="operator_nama"
                                v-model="form.operator_nama"
                                placeholder="Nama Operator"
                            />
                        </FormField>
                        <div class="sm:col-span-2">
                            <FormField
                                label="Alamat"
                                for="alamat"
                                :error="form.errors.alamat"
                            >
                                <textarea
                                    id="alamat"
                                    v-model="form.alamat"
                                    rows="2"
                                    placeholder="Jl. Raya Madrasah No. 12"
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                />
                            </FormField>
                        </div>
                        <FormField
                            label="Desa / Kelurahan"
                            for="desa"
                            :error="form.errors.desa"
                        >
                            <Input
                                id="desa"
                                v-model="form.desa"
                                placeholder="Sukamaju"
                            />
                        </FormField>
                        <FormField
                            label="Kecamatan"
                            for="kecamatan"
                            :error="form.errors.kecamatan"
                        >
                            <Input
                                id="kecamatan"
                                v-model="form.kecamatan"
                                placeholder="Cibadak"
                            />
                        </FormField>
                        <FormField
                            label="Kabupaten / Kota"
                            for="kabupaten"
                            :error="form.errors.kabupaten"
                        >
                            <Input
                                id="kabupaten"
                                v-model="form.kabupaten"
                                placeholder="Sukabumi"
                            />
                        </FormField>
                        <FormField
                            label="Provinsi"
                            for="provinsi"
                            :error="form.errors.provinsi"
                        >
                            <Input
                                id="provinsi"
                                v-model="form.provinsi"
                                placeholder="Jawa Barat"
                            />
                        </FormField>
                        <FormField
                            label="Kode Pos"
                            for="kode_pos"
                            :error="form.errors.kode_pos"
                        >
                            <Input
                                id="kode_pos"
                                v-model="form.kode_pos"
                                placeholder="43351"
                            />
                        </FormField>
                        <FormField
                            label="Telepon"
                            for="telepon"
                            :error="form.errors.telepon"
                        >
                            <Input
                                id="telepon"
                                v-model="form.telepon"
                                placeholder="0266-123456"
                            />
                        </FormField>
                        <FormField
                            label="Email Madrasah"
                            for="email_madrasah"
                            :error="form.errors.email_madrasah"
                        >
                            <Input
                                id="email_madrasah"
                                v-model="form.email_madrasah"
                                type="email"
                                placeholder="info@mialhikmah.sch.id"
                            />
                        </FormField>
                        <FormField
                            label="Website"
                            for="website"
                            :error="form.errors.website"
                        >
                            <Input
                                id="website"
                                v-model="form.website"
                                :placeholder="websitePlaceholder"
                            />
                        </FormField>
                        <FormField
                            label="No. HP / WA Operator EMIS"
                            for="operator_hp"
                            :error="form.errors.operator_hp"
                        >
                            <Input
                                id="operator_hp"
                                v-model="form.operator_hp"
                                placeholder="0812xxxxxxxx"
                            />
                        </FormField>
                    </div>
                </section>

                <!-- Kop Surat & Logo Resmi (EMIS 4.0) -->
                <section
                    class="rounded-2xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(7,63,36,0.04)]"
                >
                    <div
                        class="flex items-center gap-3 border-b border-border/70 px-5 py-4"
                    >
                        <div
                            class="grid size-9 place-items-center rounded-lg bg-[#107c41]/8 text-[#107c41] ring-1 ring-[#107c41]/12"
                        >
                            <ImagePlus class="size-4" />
                        </div>
                        <div>
                            <h2
                                class="font-serif text-base font-bold text-foreground"
                            >
                                Logo Kop Surat Resmi (EMIS 4.0)
                            </h2>
                            <p class="text-[11px] text-muted-foreground">
                                Digunakan otomatis pada cetak KTS, Buku Induk,
                                Surat Pindah, dan Transkrip Nilai RDM.
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div
                            class="rounded-xl border border-border/80 bg-muted/20 p-3.5"
                        >
                            <p class="text-xs font-semibold text-foreground">
                                Logo Kiri (Kemenag / Instansi)
                            </p>
                            <div class="mt-3 flex items-center gap-3">
                                <div
                                    class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-border bg-white"
                                >
                                    <img
                                        v-if="form.logo_kiri"
                                        :src="form.logo_kiri"
                                        alt="Logo Kiri"
                                        class="size-full object-contain p-1"
                                    />
                                    <span
                                        v-else
                                        class="text-[10px] text-muted-foreground"
                                        >Kosong</span
                                    >
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        @click="logoKiriInputRef?.click()"
                                    >
                                        Unggah
                                    </Button>
                                    <Button
                                        v-if="form.logo_kiri"
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        class="text-rose-600"
                                        @click="form.logo_kiri = ''"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </Button>
                                </div>
                            </div>
                            <input
                                ref="logoKiriInputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleLogoUpload($event, 'logo_kiri')"
                            />
                        </div>

                        <div
                            class="rounded-xl border border-border/80 bg-muted/20 p-3.5"
                        >
                            <p class="text-xs font-semibold text-foreground">
                                Logo Kanan (Madrasah)
                            </p>
                            <div class="mt-3 flex items-center gap-3">
                                <div
                                    class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-border bg-white"
                                >
                                    <img
                                        v-if="form.logo_kanan"
                                        :src="form.logo_kanan"
                                        alt="Logo Kanan"
                                        class="size-full object-contain p-1"
                                    />
                                    <span
                                        v-else
                                        class="text-[10px] text-muted-foreground"
                                        >Kosong</span
                                    >
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        @click="logoKananInputRef?.click()"
                                    >
                                        Unggah
                                    </Button>
                                    <Button
                                        v-if="form.logo_kanan"
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        class="text-rose-600"
                                        @click="form.logo_kanan = ''"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </Button>
                                </div>
                            </div>
                            <input
                                ref="logoKananInputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleLogoUpload($event, 'logo_kanan')"
                            />
                        </div>
                    </div>
                </section>

                <!-- Kepala madrasah -->
                <section
                    class="rounded-2xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(7,63,36,0.04)]"
                >
                    <div
                        class="flex items-center gap-3 border-b border-border/70 px-5 py-4"
                    >
                        <div
                            class="grid size-9 place-items-center rounded-lg bg-[#107c41]/8 text-[#107c41] ring-1 ring-[#107c41]/12"
                        >
                            <BadgeCheck class="size-4" />
                        </div>
                        <div>
                            <h2
                                class="font-serif text-base font-bold text-foreground"
                            >
                                Kepala Madrasah
                            </h2>
                            <p class="text-[11px] text-muted-foreground">
                                Nama penanda tangan pada dokumen resmi.
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <FormField
                            label="Nama Kepala Madrasah"
                            for="nama_kepala_madrasah"
                            :error="form.errors.nama_kepala_madrasah"
                        >
                            <Input
                                id="nama_kepala_madrasah"
                                v-model="form.nama_kepala_madrasah"
                                placeholder="H. Abdul Rahman, S.Pd.I., M.M."
                            />
                        </FormField>
                        <FormField
                            label="NIP Kepala Madrasah"
                            for="nip_kepala_madrasah"
                            :error="form.errors.nip_kepala_madrasah"
                        >
                            <Input
                                id="nip_kepala_madrasah"
                                v-model="form.nip_kepala_madrasah"
                                placeholder="197003151998031002"
                            />
                        </FormField>
                    </div>
                </section>

                <div class="flex items-center justify-end gap-2 pb-2">
                    <Button variant="outline" as-child type="button">
                        <Link href="/beranda">Batal</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle
                            v-if="form.processing"
                            class="animate-spin"
                        />
                        <Save v-else />
                        Simpan Identitas
                    </Button>
                </div>
            </div>

            <!-- Live preview -->
            <aside class="space-y-4 xl:sticky xl:top-24">
                <div class="rounded-2xl border border-border/80 bg-card p-4">
                    <p
                        class="mb-2 text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Pratinjau Kop Surat Resmi (Live)
                    </p>
                    <div
                        class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-3 text-slate-900"
                        v-html="kopPreviewHtml"
                    />
                </div>
                <div
                    class="overflow-hidden rounded-2xl border border-[#073f24]/15 bg-[linear-gradient(145deg,#073f24,#0b5a31_60%,#107c41)] text-white shadow-[0_18px_40px_-28px_rgba(7,63,36,0.8)]"
                >
                    <div class="relative p-5">
                        <div
                            class="pointer-events-none absolute -top-10 -right-8 size-32 rounded-full bg-[#c99700]/20 blur-2xl"
                        />
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p
                                    class="text-[9px] font-semibold tracking-[0.2em] text-[#e8c96b] uppercase"
                                >
                                    Kartu Identitas Digital
                                </p>
                                <p
                                    class="mt-1 font-serif text-lg leading-tight font-bold"
                                >
                                    {{ form.nama_guru || 'Nama Guru' }}
                                </p>
                                <p class="text-[11px] text-white/60">
                                    NIP {{ form.nip || '—' }}
                                </p>
                            </div>
                            <div
                                class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#c99700] text-[#1b1403]"
                            >
                                <IdCard class="size-5" />
                            </div>
                        </div>

                        <div
                            class="mt-4 grid grid-cols-2 gap-3 border-t border-white/10 pt-3"
                        >
                            <div>
                                <p
                                    class="text-[8.5px] tracking-[0.14em] text-white/40 uppercase"
                                >
                                    Madrasah
                                </p>
                                <p
                                    class="mt-0.5 truncate text-[11px] font-medium text-white/90"
                                >
                                    {{ form.nama_madrasah || '—' }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[8.5px] tracking-[0.14em] text-white/40 uppercase"
                                >
                                    Jabatan
                                </p>
                                <p
                                    class="mt-0.5 truncate text-[11px] font-medium text-white/90"
                                >
                                    {{ form.jabatan || '—' }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[8.5px] tracking-[0.14em] text-white/40 uppercase"
                                >
                                    Kelas Wali
                                </p>
                                <p
                                    class="mt-0.5 truncate text-[11px] font-medium text-white/90"
                                >
                                    {{
                                        form.kelas_wali
                                            ? classLabel(form.kelas_wali)
                                            : '—'
                                    }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[8.5px] tracking-[0.14em] text-white/40 uppercase"
                                >
                                    Tahun Ajaran
                                </p>
                                <p
                                    class="mt-0.5 truncate text-[11px] font-medium text-white/90"
                                >
                                    {{ form.tahun_ajaran }} ·
                                    {{ form.semester }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-3 flex items-center gap-1.5 text-[9.5px] text-white/45"
                        >
                            <MapPin class="size-3 text-[#e8c96b]" />
                            <span class="truncate">{{ departure }}</span>
                        </div>
                    </div>
                    <div
                        class="flex items-center justify-between border-t border-white/10 bg-black/15 px-5 py-2.5"
                    >
                        <span
                            class="text-[9px] tracking-wider text-white/40 uppercase"
                        >
                            {{ form.nama_kepala_madrasah || 'Kepala Madrasah' }}
                        </span>
                        <span class="text-[9px] font-semibold text-[#e8c96b]">
                            {{ form.npsn || 'NPSN —' }}
                        </span>
                    </div>
                </div>

                <div class="rounded-2xl border border-border/80 bg-card p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="grid size-9 place-items-center rounded-lg bg-[#c99700]/10 text-[#a87c00]"
                            >
                                <Sparkles class="size-4" />
                            </div>
                            <div>
                                <p
                                    class="text-[11.5px] font-semibold text-foreground"
                                >
                                    Kelengkapan profil
                                </p>
                                <p class="text-[10px] text-muted-foreground">
                                    {{ liveCompleteness }}% terisi
                                </p>
                            </div>
                        </div>
                        <span
                            class="rounded-full bg-[#107c41]/8 px-2.5 py-1 text-[11px] font-bold text-[#107c41]"
                        >
                            {{ liveCompleteness }}%
                        </span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[#107c41] to-[#c99700] transition-all duration-500"
                            :style="{ width: `${liveCompleteness}%` }"
                        />
                    </div>
                    <p
                        class="mt-2 text-[10.5px] leading-relaxed text-muted-foreground"
                    >
                        Lengkapi data agar kop dokumen, kartu profil, dan tanda
                        tangan otomatis terisi dengan benar.
                    </p>
                    <div
                        class="mt-3 space-y-1.5 border-t border-border/70 pt-3 text-[10.5px]"
                    >
                        <div
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Phone class="size-3 text-[#107c41]" />
                            <span class="truncate">{{
                                form.telepon || 'Telepon belum diisi'
                            }}</span>
                        </div>
                        <div
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Mail class="size-3 text-[#107c41]" />
                            <span class="truncate">{{
                                form.email_madrasah || 'Email belum diisi'
                            }}</span>
                        </div>
                        <div
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Globe class="size-3 text-[#107c41]" />
                            <span class="truncate">{{
                                form.website || 'Website belum diisi'
                            }}</span>
                        </div>
                        <div
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Building class="size-3 text-[#107c41]" />
                            <span class="truncate">{{
                                form.kecamatan || 'Kecamatan belum diisi'
                            }}</span>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-[#107c41]/15 bg-[#107c41]/[0.035] p-3.5 text-[10.5px] leading-relaxed text-muted-foreground"
                >
                    <p class="font-semibold text-[#107c41]">Tips</p>
                    <p class="mt-1">
                        Gunakan nama lengkap beserta gelar. Data NIP dan nama
                        kepala madrasah akan dipakai pada blok tanda tangan
                        dokumen resmi.
                    </p>
                </div>
            </aside>
        </form>
    </div>
</template>
