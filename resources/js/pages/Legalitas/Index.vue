<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Check,
    Eye,
    FileImage,
    LoaderCircle,
    RotateCcw,
    ShieldCheck,
    Stamp,
    Trash,
    Upload,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/app/ConfirmDialog.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { formatBytes, formatDateTime } from '@/lib/format';
import type { DocumentTypeCard, LegalDocumentType } from '@/types';

const props = defineProps<{
    documentTypes: DocumentTypeCard[];
    uploadedCount: number;
    requiredCount: number;
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'Legalitas Logo/TTD/Stempel', href: '/legalitas' },
        ],
    }),
});

const descriptionFor: Record<LegalDocumentType, string> = {
    logo: 'Logo resmi madrasah untuk kop surat, dokumen, dan media pembelajaran.',
    ttd_kepala: 'Tanda tangan kepala madrasah untuk pengesahan dokumen resmi.',
    ttd_guru: 'Tanda tangan guru pada perangkat ajar dan administrasi kelas.',
    stempel: 'Stempel resmi madrasah sebagai penanda keabsahan dokumen.',
};

const form = useForm<{ type: LegalDocumentType | ''; file: File | null }>({
    type: '',
    file: null,
});

const uploadingType = ref<LegalDocumentType | null>(null);
const previewDocument = ref<DocumentTypeCard | null>(null);
const deleting = ref<DocumentTypeCard | null>(null);
const deletingProcessing = ref(false);

const completion = computed(() =>
    props.requiredCount
        ? Math.round((props.uploadedCount / props.requiredCount) * 100)
        : 0,
);

const openPicker = (type: LegalDocumentType) => {
    const input = document.getElementById(
        `legal-file-${type}`,
    ) as HTMLInputElement | null;
    input?.click();
};

const onFileSelected = (type: LegalDocumentType, event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    if (!file) {
        return;
    }

    form.type = type;
    form.file = file;
    uploadingType.value = type;

    form.post('/legalitas', {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            uploadingType.value = null;
            form.reset();
            target.value = '';
        },
    });
};

const confirmDelete = () => {
    if (!deleting.value?.document) {
        return;
    }

    deletingProcessing.value = true;

    router.delete(`/legalitas/${deleting.value.document.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingProcessing.value = false;
            deleting.value = null;
        },
    });
};
</script>

<template>
    <Head title="Legalitas Logo/TTD/Stempel" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Beranda & Profil"
            title="Legalitas Logo, TTD & Stempel"
            description="Unggah berkas resmi sekali saja. Logo, tanda tangan, dan stempel akan otomatis dipakai pada seluruh dokumen yang dicetak dari aplikasi."
            :icon="ShieldCheck"
        >
            <template #actions>
                <div
                    class="flex items-center gap-2 rounded-lg border border-[#107c41]/15 bg-card px-3 py-2 text-[11.5px]"
                >
                    <BadgeCheck class="size-4 text-[#107c41]" />
                    <span class="font-semibold text-foreground">
                        {{ uploadedCount }}/{{ requiredCount }} berkas
                    </span>
                    <span class="text-muted-foreground">lengkap</span>
                </div>
            </template>
        </PageHeader>

        <div
            class="flex flex-col gap-3 rounded-2xl border border-[#c99700]/20 bg-[linear-gradient(100deg,#fbf5e2,#fffdf6_60%,#f6efe0)] p-4 sm:flex-row sm:items-center sm:justify-between dark:border-[#c99700]/15 dark:bg-[linear-gradient(100deg,#1a1505,#0f1d13)]"
        >
            <div class="flex items-center gap-3">
                <div
                    class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#c99700]/15 text-[#a87c00]"
                >
                    <Stamp class="size-5" />
                </div>
                <div>
                    <p class="text-[12.5px] font-semibold text-[#073f24] dark:text-[#f3d583]">
                        Kelengkapan berkas legalitas
                    </p>
                    <p class="text-[11px] text-muted-foreground">
                        Format PNG, JPG, WEBP, atau SVG. Maksimal 4 MB per berkas.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 sm:w-64">
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-[#c99700]/15">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-[#c99700] to-[#e0b84a] transition-all duration-500"
                        :style="{ width: `${completion}%` }"
                    />
                </div>
                <span class="text-[11.5px] font-bold text-[#a87c00]">
                    {{ completion }}%
                </span>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <section
                v-for="item in documentTypes"
                :key="item.type"
                class="group overflow-hidden rounded-2xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(7,63,36,0.04)] transition-shadow hover:shadow-[0_16px_36px_-24px_rgba(7,63,36,0.4)]"
            >
                <div class="flex items-start justify-between gap-3 border-b border-border/70 px-4 py-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span
                                class="grid size-6 place-items-center rounded-full"
                                :class="
                                    item.document
                                        ? 'bg-[#107c41]/10 text-[#107c41]'
                                        : 'bg-[#c99700]/12 text-[#a87c00]'
                                "
                            >
                                <Check v-if="item.document" class="size-3.5" />
                                <FileImage v-else class="size-3.5" />
                            </span>
                            <h2 class="truncate font-serif text-sm font-bold text-foreground">
                                {{ item.label }}
                            </h2>
                        </div>
                        <p class="mt-1 text-[11px] leading-relaxed text-muted-foreground">
                            {{ descriptionFor[item.type] }}
                        </p>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-semibold tracking-wider uppercase"
                        :class="
                            item.document
                                ? 'bg-[#107c41]/10 text-[#107c41]'
                                : 'bg-[#c99700]/12 text-[#a87c00]'
                        "
                    >
                        {{ item.document ? 'Tersedia' : 'Belum ada' }}
                    </span>
                </div>

                <div
                    class="flex min-h-[150px] items-center justify-center bg-[linear-gradient(135deg,#f6faf7,#eef4ef)] p-4 dark:bg-[linear-gradient(135deg,#0a1c12,#0c2417)]"
                >
                    <div
                        v-if="item.document && item.type === 'logo'"
                        class="grid size-24 place-items-center overflow-hidden rounded-full bg-white p-2 ring-1 ring-border"
                    >
                        <img
                            :src="item.document.url"
                            :alt="item.label"
                            class="size-full object-contain"
                        />
                    </div>
                    <div
                        v-else-if="item.document && (item.type === 'ttd_kepala' || item.type === 'ttd_guru')"
                        class="grid h-24 w-full max-w-[220px] place-items-center overflow-hidden rounded-lg bg-white p-2 ring-1 ring-border"
                    >
                        <img
                            :src="item.document.url"
                            :alt="item.label"
                            class="max-h-full w-full object-contain"
                        />
                    </div>
                    <div
                        v-else-if="item.document && item.type === 'stempel'"
                        class="grid size-24 place-items-center overflow-hidden rounded-full bg-white p-1 ring-1 ring-border"
                    >
                        <img
                            :src="item.document.url"
                            :alt="item.label"
                            class="size-full object-contain"
                        />
                    </div>
                    <div v-else class="text-center">
                        <div
                            class="mx-auto grid size-11 place-items-center rounded-full bg-white text-muted-foreground/60 ring-1 ring-border"
                        >
                            <Upload class="size-5" />
                        </div>
                        <p class="mt-2 text-[10.5px] text-muted-foreground">
                            Belum ada berkas diunggah
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-border/70 px-4 py-2.5">
                    <div class="min-w-0">
                        <p v-if="item.document" class="truncate text-[10.5px] font-medium text-foreground/80">
                            {{ item.document.original_name }}
                        </p>
                        <p v-if="item.document" class="text-[9.5px] text-muted-foreground">
                            {{ formatBytes(item.document.file_size) }} ·
                            {{ formatDateTime(item.document.uploaded_at) }}
                        </p>
                        <p v-else class="text-[10px] text-muted-foreground">
                            Unggah berkas resmi madrasah
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <input
                            :id="`legal-file-${item.type}`"
                            type="file"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                            class="hidden"
                            @change="onFileSelected(item.type, $event)"
                        />
                        <Button
                            v-if="item.document"
                            variant="ghost"
                            size="icon"
                            class="size-8 text-muted-foreground hover:text-[#107c41]"
                            :title="`Pratinjau ${item.label}`"
                            @click="previewDocument = item"
                        >
                            <Eye class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8 text-muted-foreground hover:text-[#107c41]"
                            :title="item.document ? 'Ganti berkas' : 'Unggah berkas'"
                            :disabled="uploadingType === item.type"
                            @click="openPicker(item.type)"
                        >
                            <LoaderCircle
                                v-if="uploadingType === item.type"
                                class="size-4 animate-spin"
                            />
                            <RotateCcw v-else-if="item.document" class="size-4" />
                            <Upload v-else class="size-4" />
                        </Button>
                        <Button
                            v-if="item.document"
                            variant="ghost"
                            size="icon"
                            class="size-8 text-muted-foreground hover:text-destructive"
                            :title="`Hapus ${item.label}`"
                            @click="deleting = item"
                        >
                            <Trash class="size-4" />
                        </Button>
                    </div>
                </div>
            </section>
        </div>

        <section class="rounded-2xl border border-border/80 bg-card p-4 md:p-5">
            <h2 class="font-serif text-base font-bold text-foreground">
                Catatan penting
            </h2>
            <ul class="mt-2 grid gap-2 text-[11.5px] leading-relaxed text-muted-foreground sm:grid-cols-2">
                <li class="flex gap-2">
                    <span class="mt-1 size-1.5 shrink-0 rounded-full bg-[#c99700]" />
                    Gunakan berkas dengan latar transparan (PNG/SVG) agar hasil cetak rapi.
                </li>
                <li class="flex gap-2">
                    <span class="mt-1 size-1.5 shrink-0 rounded-full bg-[#c99700]" />
                    Berkas disimpan privat dan hanya dapat diakses oleh akun Anda.
                </li>
                <li class="flex gap-2">
                    <span class="mt-1 size-1.5 shrink-0 rounded-full bg-[#c99700]" />
                    Rasio ideal logo dan stempel adalah 1:1 (persegi).
                </li>
                <li class="flex gap-2">
                    <span class="mt-1 size-1.5 shrink-0 rounded-full bg-[#c99700]" />
                    Tanda tangan sebaiknya berukuran lebar (landscape) 3:1.
                </li>
            </ul>
        </section>
    </div>

    <!-- Preview modal -->
    <div
        v-if="previewDocument?.document"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#03150c]/70 p-4 backdrop-blur-sm"
        @click.self="previewDocument = null"
    >
        <div
            class="w-full max-w-2xl overflow-hidden rounded-2xl border border-border bg-card shadow-2xl"
        >
            <div class="flex items-center justify-between gap-3 border-b border-border/70 px-4 py-3">
                <div class="min-w-0">
                    <p class="truncate font-serif text-sm font-bold text-foreground">
                        {{ previewDocument.label }}
                    </p>
                    <p class="truncate text-[10.5px] text-muted-foreground">
                        {{ previewDocument.document.original_name }}
                    </p>
                </div>
                <Button variant="ghost" size="sm" @click="previewDocument = null">
                    Tutup
                </Button>
            </div>
            <div
                class="grid max-h-[62vh] place-items-center overflow-auto bg-[linear-gradient(135deg,#f6faf7,#eef4ef)] p-6 dark:bg-[linear-gradient(135deg,#0a1c12,#0c2417)]"
            >
                <img
                    :src="previewDocument.document.url"
                    :alt="previewDocument.label"
                    class="max-h-[52vh] max-w-full object-contain"
                />
            </div>
        </div>
    </div>

    <ConfirmDialog
        :open="Boolean(deleting)"
        title="Hapus berkas legalitas?"
        :description="
            deleting
                ? `Berkas ${deleting.label} akan dihapus permanen. Anda dapat mengunggah ulang kapan saja.`
                : ''
        "
        :processing="deletingProcessing"
        @update:open="(open) => !open && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
