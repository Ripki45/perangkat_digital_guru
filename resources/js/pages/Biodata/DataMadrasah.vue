<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Building2,
    Globe,
    GraduationCap,
    ImagePlus,
    LoaderCircle,
    Mail,
    Phone,
    RotateCcw,
    Save,
    Stamp,
    Trash2,
    UserCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import FormField from '@/components/app/FormField.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import { buildOfficialKopHtml } from '@/components/sim-emis/EmisPrintHelpers';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { TeacherProfile } from '@/types';
import type { EmisMadrasahProfile } from '@/types/emis';

const props = defineProps<{
    profile: TeacherProfile | null;
    defaults: {
        nama_madrasah: string;
        tahun_ajaran: string;
        semester: string;
        nsm: string;
        npsn: string;
        kemenag_kab: string;
        tempat_surat: string;
    };
    fallbackLogoKiri?: string;
    canManageLegalitas: boolean;
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'BIODATA', href: '/data-madrasah' },
            { title: 'Data Madrasah', href: '/data-madrasah' },
        ],
    }),
});

const form = useForm({
    nama_madrasah: props.profile?.nama_madrasah ?? props.defaults.nama_madrasah,
    nsm: props.profile?.nsm ?? props.defaults.nsm,
    npsn: props.profile?.npsn ?? props.defaults.npsn,
    kemenag_kab: props.profile?.kemenag_kab ?? props.defaults.kemenag_kab,
    tahun_ajaran: props.profile?.tahun_ajaran ?? props.defaults.tahun_ajaran,
    semester: props.profile?.semester ?? props.defaults.semester,
    tempat_surat:
        props.profile?.tempat_surat ??
        props.profile?.kabupaten ??
        props.defaults.tempat_surat,
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
                'Logo berhasil dimuat (maks 240×240 PNG). Klik Simpan Data Madrasah.',
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
        form.desa,
        form.kecamatan,
        form.kabupaten,
        form.provinsi,
    ].filter(Boolean);

    const emisProfile: EmisMadrasahProfile = {
        namaMadrasah: form.nama_madrasah || 'MIN 1 CIAMIS',
        tahunPelajaran: `${form.tahun_ajaran || '2026/2027'} - ${form.semester || 'Ganjil'}`,
        nsm: form.nsm || '-',
        npsn: form.npsn || '-',
        kemenagKab: form.kemenag_kab || 'KANTOR KEMENTERIAN AGAMA',
        alamat: alamatParts.join(', ') || '-',
        tempatSurat: form.tempat_surat || form.kabupaten || 'Ciamis',
        website: form.website || '-',
        email: form.email_madrasah || '-',
        kepalaNama: form.nama_kepala_madrasah || '-',
        kepalaNip: form.nip_kepala_madrasah || '-',
        operatorNama: form.operator_nama || '-',
        operatorHp: form.operator_hp || '-',
        logoKiri: form.logo_kiri || props.fallbackLogoKiri || '',
        logoKanan: form.logo_kanan || '',
    };

    return buildOfficialKopHtml(emisProfile);
});

const submit = () => {
    form.put('/data-madrasah', {
        preserveScroll: true,
    });
};

const confirmResetOpen = ref(false);
const resetting = ref(false);

const executeResetDefault = () => {
    resetting.value = true;
    router.post(
        '/identitas/reset-default',
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                form.nama_madrasah =
                    props.profile?.nama_madrasah ??
                    'MADRASAH IBTIDAIYAH NEGERI 1 CIAMIS';
                form.tahun_ajaran = props.profile?.tahun_ajaran ?? '2026/2027';
                form.semester = props.profile?.semester ?? 'Ganjil';
                form.nsm = props.profile?.nsm ?? '111132070001';
                form.npsn = props.profile?.npsn ?? '60708447';
                form.kemenag_kab =
                    props.profile?.kemenag_kab ??
                    'KANTOR KEMENTERIAN AGAMA KABUPATEN CIAMIS';
                form.alamat =
                    props.profile?.alamat ??
                    'Jl. Raya Panjalu - Kawali Dusun Sukamaju Desa Maparah Kec. Panjalu Kab. Ciamis';
                form.tempat_surat = props.profile?.tempat_surat ?? 'Ciamis';
                form.website =
                    props.profile?.website ?? 'www.min1ciamis.sch.id';
                form.email_madrasah =
                    props.profile?.email_madrasah ?? 'min1ciamis@kemenag.go.id';
                form.nama_kepala_madrasah =
                    props.profile?.nama_kepala_madrasah ??
                    'H. Dede Hidayat, S.Ag., M.Pd.I.';
                form.nip_kepala_madrasah =
                    props.profile?.nip_kepala_madrasah ?? '197204151998031003';
                form.operator_nama =
                    props.profile?.operator_nama ??
                    'Operator EMIS MIN 1 Ciamis';
                form.operator_hp = props.profile?.operator_hp ?? '081234567890';
                form.logo_kiri = props.profile?.logo_kiri ?? '';
                form.logo_kanan = props.profile?.logo_kanan ?? '';
                confirmResetOpen.value = false;
            },
            onFinish: () => {
                resetting.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Data Madrasah" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="BIODATA · Lembaga"
            title="Data Madrasah &amp; Kop Surat Resmi"
            description="Kelola NSM, NPSN, alamat lembaga, Kepala Madrasah, Operator EMIS, serta logo kop surat untuk seluruh dokumen cetak."
            :icon="GraduationCap"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/identitas">
                        <UserCheck class="size-4" />
                        Identitas &amp; Profil Guru
                    </Link>
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="resetting"
                    @click="confirmResetOpen = true"
                >
                    <RotateCcw class="size-4" />
                    Reset Default MIN 1
                </Button>
                <Button
                    type="submit"
                    form="data-madrasah-form"
                    :disabled="form.processing"
                >
                    <LoaderCircle v-if="form.processing" class="animate-spin" />
                    <Save v-else />
                    Simpan Data Madrasah
                </Button>
            </template>
        </PageHeader>

        <div class="grid items-start gap-5 xl:grid-cols-[1.5fr_1fr]">
            <form
                id="data-madrasah-form"
                class="space-y-5"
                @submit.prevent="submit"
            >
                <!-- Identitas Lembaga Madrasah -->
                <section
                    class="rounded-2xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(7,63,36,0.04)]"
                >
                    <div
                        class="flex items-center gap-3 border-b border-border/70 px-5 py-4"
                    >
                        <div
                            class="grid size-9 place-items-center rounded-lg bg-[#107c41]/8 text-[#107c41] ring-1 ring-[#107c41]/12"
                        >
                            <Building2 class="size-4" />
                        </div>
                        <div>
                            <h2
                                class="font-serif text-base font-bold text-foreground"
                            >
                                Identitas Lembaga Madrasah (EMIS 4.0)
                            </h2>
                            <p class="text-[11px] text-muted-foreground">
                                Dipakai otomatis sebagai kop surat resmi pada
                                Buku Induk, KTS, RDM, dan Surat Pindah.
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <FormField
                                label="Nama Madrasah"
                                for="nama_madrasah"
                                required
                                :error="form.errors.nama_madrasah"
                            >
                                <Input
                                    id="nama_madrasah"
                                    v-model="form.nama_madrasah"
                                    placeholder="MADRASAH IBTIDAIYAH NEGERI 1 CIAMIS"
                                />
                            </FormField>
                        </div>
                        <FormField
                            label="NSM (12 Digit)"
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
                            label="NPSN (8 Digit)"
                            for="npsn"
                            :error="form.errors.npsn"
                        >
                            <Input
                                id="npsn"
                                v-model="form.npsn"
                                placeholder="60708447"
                            />
                        </FormField>
                        <div class="sm:col-span-2">
                            <FormField
                                label="Instansi Kementerian Agama Kab/Kota"
                                for="kemenag_kab"
                                :error="form.errors.kemenag_kab"
                            >
                                <Input
                                    id="kemenag_kab"
                                    v-model="form.kemenag_kab"
                                    placeholder="KANTOR KEMENTERIAN AGAMA KABUPATEN CIAMIS"
                                />
                            </FormField>
                        </div>
                        <FormField
                            label="Tahun Ajaran Aktif"
                            for="tahun_ajaran"
                            required
                            :error="form.errors.tahun_ajaran"
                        >
                            <Input
                                id="tahun_ajaran"
                                v-model="form.tahun_ajaran"
                                placeholder="2026/2027"
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
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring dark:bg-input/30"
                            >
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </FormField>
                        <div class="sm:col-span-2">
                            <FormField
                                label="Alamat Lengkap Madrasah"
                                for="alamat"
                                :error="form.errors.alamat"
                            >
                                <textarea
                                    id="alamat"
                                    v-model="form.alamat"
                                    rows="2"
                                    placeholder="Jl. Raya Panjalu - Kawali..."
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring dark:bg-input/30"
                                />
                            </FormField>
                        </div>
                        <FormField
                            label="Desa / Kelurahan"
                            for="desa"
                            :error="form.errors.desa"
                        >
                            <Input id="desa" v-model="form.desa" />
                        </FormField>
                        <FormField
                            label="Kecamatan"
                            for="kecamatan"
                            :error="form.errors.kecamatan"
                        >
                            <Input id="kecamatan" v-model="form.kecamatan" />
                        </FormField>
                        <FormField
                            label="Kabupaten / Kota"
                            for="kabupaten"
                            :error="form.errors.kabupaten"
                        >
                            <Input id="kabupaten" v-model="form.kabupaten" />
                        </FormField>
                        <FormField
                            label="Provinsi"
                            for="provinsi"
                            :error="form.errors.provinsi"
                        >
                            <Input id="provinsi" v-model="form.provinsi" />
                        </FormField>
                        <FormField
                            label="Kode Pos"
                            for="kode_pos"
                            :error="form.errors.kode_pos"
                        >
                            <Input id="kode_pos" v-model="form.kode_pos" />
                        </FormField>
                        <FormField
                            label="Tempat Tanda Tangan Surat"
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
                            label="Telepon Madrasah"
                            for="telepon"
                            :error="form.errors.telepon"
                        >
                            <Input id="telepon" v-model="form.telepon" />
                        </FormField>
                        <FormField
                            label="Email Resmi Madrasah"
                            for="email_madrasah"
                            :error="form.errors.email_madrasah"
                        >
                            <Input
                                id="email_madrasah"
                                v-model="form.email_madrasah"
                                type="email"
                            />
                        </FormField>
                        <div class="sm:col-span-2">
                            <FormField
                                label="Website Madrasah"
                                for="website"
                                :error="form.errors.website"
                            >
                                <Input
                                    id="website"
                                    v-model="form.website"
                                    placeholder="www.min1ciamis.sch.id"
                                />
                            </FormField>
                        </div>
                    </div>
                </section>

                <!-- Kepala Madrasah & Operator EMIS -->
                <section
                    class="rounded-2xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(7,63,36,0.04)]"
                >
                    <div class="border-b border-border/70 px-5 py-4">
                        <h2
                            class="font-serif text-base font-bold text-foreground"
                        >
                            Kepala Madrasah &amp; Operator EMIS
                        </h2>
                        <p class="text-[11px] text-muted-foreground">
                            Digunakan pada blok tanda tangan resmi dan kontak
                            operator EMIS madrasah.
                        </p>
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
                            />
                        </FormField>
                    </div>
                </section>

                <!-- Upload Logo Kop Surat -->
                <section
                    class="rounded-2xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(7,63,36,0.04)]"
                >
                    <div
                        class="flex items-center justify-between gap-3 border-b border-border/70 px-5 py-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="grid size-9 place-items-center rounded-lg bg-[#107c41]/8 text-[#107c41]"
                            >
                                <ImagePlus class="size-4" />
                            </div>
                            <div>
                                <h2
                                    class="font-serif text-base font-bold text-foreground"
                                >
                                    Logo Kop Surat Resmi
                                </h2>
                                <p class="text-[11px] text-muted-foreground">
                                    Otomatis terkompresi maksimal 240×240 px.
                                </p>
                            </div>
                        </div>
                        <Button
                            v-if="canManageLegalitas"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <Link href="/legalitas">
                                <Stamp class="size-3.5" />
                                Kelola Stempel &amp; TTD
                            </Link>
                        </Button>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div
                            class="rounded-xl border border-border/80 bg-muted/20 p-3.5"
                        >
                            <p class="text-xs font-semibold text-foreground">
                                Logo 1 / Logo Kiri (Kemenag)
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
                                        Unggah Logo 1
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
                                Logo 2 / Logo Kanan (Madrasah)
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
                                        Unggah Logo 2
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
            </form>

            <!-- Live Preview Sidebar -->
            <aside class="space-y-4 xl:sticky xl:top-24">
                <div class="rounded-2xl border border-border/80 bg-card p-4">
                    <p
                        class="mb-2 text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Pratinjau Kop Surat Resmi (Real-Time)
                    </p>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <div
                        class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-3 text-slate-900"
                        v-html="kopPreviewHtml"
                    />
                </div>

                <div
                    class="space-y-2 rounded-2xl border border-border/80 bg-card p-4 text-xs"
                >
                    <div class="font-bold text-foreground">
                        Ringkasan Lembaga
                    </div>
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <Phone class="size-3.5 text-[#107c41]" />
                        <span>{{ form.telepon || 'Telepon belum diisi' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <Mail class="size-3.5 text-[#107c41]" />
                        <span>{{
                            form.email_madrasah || 'Email belum diisi'
                        }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <Globe class="size-3.5 text-[#107c41]" />
                        <span>{{ form.website || 'Website belum diisi' }}</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <ConfirmDialog
        v-model:open="confirmResetOpen"
        title="Reset Profil Madrasah ke Default MIN 1 Ciamis?"
        description="Data lembaga madrasah, NSM/NPSN, kepala madrasah, operator, dan logo kop surat akan dikembalikan ke data bawaan MIN 1 Ciamis."
        confirm-label="Ya, Reset Default"
        variant="destructive"
        :loading="resetting"
        @confirm="executeResetDefault"
    />
</template>
