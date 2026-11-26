<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertCircle,
    GraduationCap,
    IdCard,
    Info,
    LoaderCircle,
    Printer,
    Search,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/app/PageHeader.vue';
import {
    buildOfficialKopHtml,
    escapeHtml,
    openPrintWindowWithContent,
} from '@/components/sim-emis/EmisPrintHelpers';
import { Button } from '@/components/ui/button';
import { RDM_SEMESTERS } from '@/types/emis';
import type { EmisMadrasahProfile, EmisStudent } from '@/types/emis';

const props = defineProps<{
    sampleHints: Array<{ nisn: string; label: string }>;
    madrasahProfile: EmisMadrasahProfile;
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Beranda', href: '/beranda' },
            { title: 'Cek Data Siswa (NISN)', href: '/cek-nisn' },
        ],
    }),
});

type RdmSummary = {
    semesters: Record<number, number | null>;
    overall: number | null;
};

const nisnInput = ref('');
const loading = ref(false);
const errorMessage = ref('');
const foundStudent = ref<EmisStudent | null>(null);
const rdmSummary = ref<RdmSummary | null>(null);

const statusBadgeClass = computed(() => {
    const st = foundStudent.value?.statusSiswa;
    if (st === 'Aktif') return 'bg-emerald-100 text-emerald-800';
    if (st === 'Lulus') return 'bg-purple-100 text-purple-800';
    return 'bg-amber-100 text-amber-800';
});

const fullAddress = computed(() => {
    const s = foundStudent.value;
    if (!s) return '-';
    return (
        [s.alamatJalan, s.desa, s.kecamatan, s.kabupaten]
            .filter(Boolean)
            .join(', ') || '-'
    );
});

const handleLookup = async (overrideNisn?: string) => {
    const target = (overrideNisn ?? nisnInput.value).trim();
    nisnInput.value = target;
    errorMessage.value = '';
    foundStudent.value = null;
    rdmSummary.value = null;

    if (!/^\d{10}$/.test(target)) {
        errorMessage.value = 'Masukkan tepat 10 digit angka NISN siswa.';
        return;
    }

    loading.value = true;
    try {
        const res = await fetch(
            `/cek-nisn/cari?nisn=${encodeURIComponent(target)}`,
            {
                headers: { Accept: 'application/json' },
            },
        );
        const data = (await res.json()) as {
            found?: boolean;
            message?: string;
            student?: EmisStudent;
            rdm?: RdmSummary;
        };

        if (!res.ok || !data.found || !data.student) {
            errorMessage.value =
                data.message ||
                `Data siswa dengan NISN "${target}" tidak ditemukan.`;
            return;
        }

        foundStudent.value = data.student;
        rdmSummary.value = data.rdm ?? null;
    } catch {
        errorMessage.value = 'Gagal mengambil data NISN. Silakan coba lagi.';
    } finally {
        loading.value = false;
    }
};

const printSummary = () => {
    const s = foundStudent.value;
    if (!s) return;

    const rdmGridHtml = RDM_SEMESTERS.map((smt) => {
        const val = rdmSummary.value?.semesters?.[smt];
        return `<div style="border:1px solid #cbd5e1;border-radius:6px;padding:6px;text-align:center;">
            <div style="font-size:10px;color:#64748b;">Smt ${smt}</div>
            <div style="font-weight:bold;font-size:12px;">${val !== null && val !== undefined ? val.toFixed(1) : '-'}</div>
        </div>`;
    }).join('');

    const html = `
        <div style="max-width:720px;margin:0 auto;font-family:Arial,sans-serif;font-size:12px;color:#0f172a;">
            ${buildOfficialKopHtml(props.madrasahProfile)}
            <h2 style="text-align:center;font-size:14px;font-weight:bold;text-decoration:underline;margin:12px 0;">
                RINGKASAN BIODATA &amp; NILAI RDM PESERTA DIDIK
            </h2>
            <table style="width:100%;border-collapse:collapse;margin-bottom:12px;">
                <tr><td style="padding:4px;width:180px;">Nama Lengkap</td><td>: <strong>${escapeHtml(s.nama)}</strong></td></tr>
                <tr><td style="padding:4px;">NISN / NIS Lokal</td><td>: ${escapeHtml(s.nisn || '-')} / ${escapeHtml(s.nisLokal || '-')}</td></tr>
                <tr><td style="padding:4px;">Rombel / Status</td><td>: Kelas ${escapeHtml(s.rombel || '-')} (${escapeHtml(s.statusSiswa || '-')})</td></tr>
                <tr><td style="padding:4px;">Tempat, Tanggal Lahir</td><td>: ${escapeHtml(s.tempatLahir || '-')}, ${escapeHtml(s.tglLahir || '-')}</td></tr>
                <tr><td style="padding:4px;">Nama Orang Tua</td><td>: ${escapeHtml(s.ayahNama || '-')} / ${escapeHtml(s.ibuNama || '-')}</td></tr>
                <tr><td style="padding:4px;">Alamat</td><td>: ${escapeHtml(fullAddress.value)}</td></tr>
            </table>
            <div style="font-weight:bold;margin-bottom:6px;">Rekap Nilai RDM (Rata-Rata: ${rdmSummary.value?.overall !== null && rdmSummary.value?.overall !== undefined ? rdmSummary.value.overall.toFixed(2) : '-'})</div>
            <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:6px;">${rdmGridHtml}</div>
        </div>
    `;

    openPrintWindowWithContent(html, `Ringkasan NISN - ${s.nama}`);
};
</script>

<template>
    <Head title="Cek Data Siswa (NISN)" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Portal Mandiri Terotentikasi"
            title="Cek Data Siswa (NISN)"
            description="Cari biodata EMIS 4.0, rombel aktif, status bantuan sosial, riwayat kelulusan/mutasi, dan rekap nilai RDM berdasarkan 10 digit NISN."
        />

        <div
            class="mx-auto w-full max-w-4xl space-y-4 rounded-2xl border border-border/70 bg-card p-5 shadow-xs"
        >
            <div
                class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50/70 p-3.5 text-xs text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100"
            >
                <Info class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                <div>
                    <div class="font-bold">
                        Layanan Cek Data Induk &amp; Nilai RDM MI
                    </div>
                    <p class="mt-0.5 text-[11px] opacity-85">
                        Masukkan <strong>10 digit NISN</strong> peserta didik
                        untuk melihat biodata EMIS 4.0, kelas/rombel MI, status
                        keaktifan, dan rekap nilai RDM Semester 1 - 12.
                    </p>
                </div>
            </div>

            <form
                class="flex flex-col gap-2.5 sm:flex-row"
                novalidate
                @submit.prevent="handleLookup()"
            >
                <div class="relative flex-1">
                    <IdCard
                        class="absolute top-3 left-3.5 size-4 text-muted-foreground"
                    />
                    <input
                        v-model="nisnInput"
                        type="text"
                        maxlength="10"
                        inputmode="numeric"
                        placeholder="Masukkan 10 digit NISN (Contoh: 0191234567)"
                        class="w-full rounded-xl border border-input bg-background py-2.5 pr-3 pl-10 font-mono text-sm font-bold outline-none focus:ring-2 focus:ring-emerald-600"
                    />
                </div>
                <Button
                    type="submit"
                    :disabled="loading"
                    class="h-10.5 bg-emerald-700 px-5 font-bold text-white hover:bg-emerald-800"
                >
                    <LoaderCircle v-if="loading" class="size-4 animate-spin" />
                    <Search v-else class="size-4" />
                    Cari Data Siswa
                </Button>
            </form>

            <!-- Quick Sample Hints -->
            <div
                v-if="sampleHints.length > 0"
                class="flex flex-wrap items-center gap-1.5 text-[11px] text-muted-foreground"
            >
                <span>NISN terdaftar:</span>
                <button
                    v-for="hint in sampleHints"
                    :key="hint.nisn"
                    type="button"
                    class="rounded-md border border-border bg-muted/60 px-2 py-0.5 font-mono font-semibold text-foreground transition hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-900"
                    @click="handleLookup(hint.nisn)"
                >
                    {{ hint.nisn }} ({{ hint.label }})
                </button>
            </div>

            <!-- Error Alert -->
            <div
                v-if="errorMessage"
                class="flex items-center gap-2 rounded-xl border border-destructive/30 bg-destructive/10 px-3.5 py-2.5 text-xs font-medium text-destructive"
            >
                <AlertCircle class="size-4 shrink-0" />
                <span>{{ errorMessage }}</span>
            </div>

            <!-- Result Card -->
            <div
                v-if="foundStudent"
                class="overflow-hidden rounded-2xl border border-border bg-muted/20"
            >
                <div
                    class="flex flex-col items-center gap-4 border-b border-border bg-card p-4 sm:flex-row sm:items-start"
                >
                    <div
                        class="flex h-28 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border-2 border-emerald-600 bg-muted"
                    >
                        <img
                            v-if="foundStudent.foto"
                            :src="foundStudent.foto"
                            alt="Pas Foto"
                            class="size-full object-cover"
                        />
                        <div v-else class="text-center text-muted-foreground">
                            <UserRound class="mx-auto size-6" />
                            <span class="text-[9px] font-bold">FOTO 3x4</span>
                        </div>
                    </div>
                    <div
                        class="flex-1 space-y-1 text-center text-xs sm:text-left"
                    >
                        <div
                            class="flex flex-wrap items-center justify-center gap-2 sm:justify-start"
                        >
                            <h2 class="text-base font-bold uppercase">
                                {{ foundStudent.nama }}
                            </h2>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                :class="statusBadgeClass"
                            >
                                {{ foundStudent.statusSiswa || 'Aktif' }}
                            </span>
                        </div>
                        <div class="font-mono text-muted-foreground">
                            NISN:
                            <strong class="text-foreground">{{
                                foundStudent.nisn || '-'
                            }}</strong>
                            | NIS Lokal:
                            <strong class="text-foreground">{{
                                foundStudent.nisLokal || '-'
                            }}</strong>
                        </div>
                        <div class="text-muted-foreground">
                            Rombel MI:
                            <strong
                                class="text-emerald-700 dark:text-emerald-400"
                                >Kelas {{ foundStudent.rombel || '-' }}</strong
                            >
                            | Jenis Kelamin:
                            <strong class="text-foreground">{{
                                foundStudent.jk === 'P'
                                    ? 'Perempuan'
                                    : 'Laki-laki'
                            }}</strong>
                        </div>
                        <div class="text-[11px] text-muted-foreground">
                            TTL: {{ foundStudent.tempatLahir || '-' }},
                            {{ foundStudent.tglLahir || '-' }}
                        </div>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        @click="printSummary"
                    >
                        <Printer class="size-3.5" />
                        Cetak Ringkas
                    </Button>
                </div>

                <div class="space-y-3 p-4 text-xs">
                    <div
                        class="grid grid-cols-1 gap-2.5 rounded-xl border border-border bg-card p-3.5 text-[11px] sm:grid-cols-2"
                    >
                        <div>
                            <span class="block text-muted-foreground"
                                >Asal RA / TK / PAUD</span
                            >
                            <strong>{{
                                foundStudent.asalSekolah || '-'
                            }}</strong>
                        </div>
                        <div>
                            <span class="block text-muted-foreground"
                                >Tanggal Masuk Madrasah</span
                            >
                            <strong>{{ foundStudent.tglMasuk || '-' }}</strong>
                        </div>
                        <div>
                            <span class="block text-muted-foreground"
                                >Nama Ayah / Ibu Kandung</span
                            >
                            <strong
                                >{{ foundStudent.ayahNama || '-' }} /
                                {{ foundStudent.ibuNama || '-' }}</strong
                            >
                        </div>
                        <div>
                            <span class="block text-muted-foreground"
                                >Status Bantuan Sosial (PIP)</span
                            >
                            <strong>{{
                                foundStudent.statusPip === 'Ya'
                                    ? `Penerima PIP (${foundStudent.noKip || '-'})`
                                    : 'Tidak Terdaftar PIP'
                            }}</strong>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="block text-muted-foreground"
                                >Alamat Tempat Tinggal</span
                            >
                            <strong>{{ fullAddress }}</strong>
                        </div>
                        <div
                            v-if="foundStudent.statusSiswa !== 'Aktif'"
                            class="border-t border-border pt-2 sm:col-span-2"
                        >
                            <span class="block font-bold text-purple-700"
                                >Riwayat Keluar / Nomor Seri Ijazah MI</span
                            >
                            <strong
                                >{{
                                    foundStudent.keluarDariMadrasah ||
                                    foundStudent.statusSiswa
                                }}
                                ({{ foundStudent.tglKeluar || '-' }}) | No.
                                Ijazah/Surat:
                                {{ foundStudent.noSeriIjazahMadrasah || '-' }} |
                                Tujuan:
                                {{ foundStudent.sekolahTujuan || '-' }}</strong
                            >
                        </div>
                    </div>

                    <div class="rounded-xl border border-border bg-card p-3.5">
                        <div
                            class="mb-2 flex items-center justify-between font-bold"
                        >
                            <span class="flex items-center gap-1.5">
                                <GraduationCap
                                    class="size-4 text-emerald-600"
                                />
                                Rekap Rata-Rata Nilai RDM MI (Smt 1 - 12)
                            </span>
                            <span
                                class="rounded border border-emerald-200 bg-emerald-50 px-2 py-0.5 font-mono text-emerald-800"
                            >
                                Rata-rata:
                                {{
                                    rdmSummary?.overall !== null &&
                                    rdmSummary?.overall !== undefined
                                        ? rdmSummary.overall.toFixed(2)
                                        : '-'
                                }}
                            </span>
                        </div>
                        <div
                            class="grid grid-cols-3 gap-1.5 text-center font-mono text-[11px] sm:grid-cols-6"
                        >
                            <div
                                v-for="smt in RDM_SEMESTERS"
                                :key="smt"
                                class="rounded-lg border border-border bg-muted/30 p-1.5"
                            >
                                <div class="text-[10px] text-muted-foreground">
                                    Smt {{ smt }}
                                </div>
                                <div class="font-bold">
                                    {{
                                        rdmSummary?.semesters?.[smt] !== null &&
                                        rdmSummary?.semesters?.[smt] !==
                                            undefined
                                            ? rdmSummary.semesters[
                                                  smt
                                              ]!.toFixed(1)
                                            : '-'
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
