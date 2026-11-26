<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    BadgeCheck,
    BookOpen,
    CalendarRange,
    Check,
    ChevronRight,
    LayoutGrid,
    Sparkles,
    Stamp,
    Upload,
    UserCheck,
    Users,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import MadrasahBrand from '@/components/app/MadrasahBrand.vue';
import StatCard from '@/components/app/StatCard.vue';
import { useInitials } from '@/composables/useInitials';
import { classLabel, formatDate, greeting, todayLabel } from '@/lib/format';
import {
    canAccessModule,
    getQuickModulesForRole,
    moduleGroups,
    modules,
    pancaCinta,
} from '@/lib/modules';
import type {
    BerandaStats,
    ClassDistributionItem,
    DocumentCheckItem,
    RecentStudent,
    TeacherProfile,
} from '@/types';

const props = defineProps<{
    profile: Partial<TeacherProfile> | null;
    stats: BerandaStats;
    classDistribution: ClassDistributionItem[];
    recentStudents: RecentStudent[];
    documentTypes: DocumentCheckItem[];
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [{ title: 'Beranda Mukodimah', href: '/beranda' }],
    }),
});

const page = usePage();
const user = page.props.auth.user;
const { getInitials } = useInitials();

const teacherName = computed(() => props.profile?.nama_guru || user.name);
const shortName = computed(() => teacherName.value.split(',')[0]);
const madrasahName = computed(
    () => props.profile?.nama_madrasah || 'Madrasah Ibtidaiyah',
);
const academicYear = computed(() => props.profile?.tahun_ajaran || '2025/2026');
const semester = computed(() => props.profile?.semester || 'Ganjil');

const completeness = computed(() => props.stats.profile_completeness ?? 0);
const circumference = 2 * Math.PI * 42;
const dashOffset = computed(
    () => circumference - (completeness.value / 100) * circumference,
);

const maxClassTotal = computed(() =>
    Math.max(...props.classDistribution.map((item) => item.total), 1),
);

const uploadedDocuments = computed(
    () => props.documentTypes.filter((document) => document.uploaded).length,
);

const greetingText = computed(() => greeting());

const initials = computed(() => getInitials(teacherName.value));

const userRole = computed(() => user.role ?? null);
const isSuperadmin = computed(
    () => !userRole.value || userRole.value === 'superadmin',
);
const canManageSiswa = computed(
    () =>
        !userRole.value ||
        userRole.value === 'superadmin' ||
        userRole.value === 'wali_kelas',
);
const visibleQuickModules = computed(() =>
    getQuickModulesForRole(userRole.value),
);
const accessibleModuleCount = computed(
    () => modules.filter((m) => canAccessModule(m, userRole.value)).length,
);
</script>

<template>
    <Head title="Beranda Mukodimah" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <!-- Hero -->
        <section
            class="relative overflow-hidden rounded-3xl border border-[#073f24]/20 bg-[linear-gradient(120deg,#062f1c_0%,#0b5a31_58%,#107c41_100%)] p-5 text-white shadow-[0_18px_45px_-28px_rgba(7,63,36,0.8)] md:p-7"
        >
            <div
                class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-[#c99700]/20 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-28 left-1/3 size-72 rounded-full bg-[#3dbb7a]/15 blur-3xl"
            />
            <div
                class="paper-grid pointer-events-none absolute inset-0 opacity-[0.07]"
            />

            <div
                class="relative grid gap-6 lg:grid-cols-[1.6fr_1fr] lg:items-center"
            >
                <div>
                    <div
                        class="mb-3 inline-flex items-center gap-2 rounded-full border border-[#e8c96b]/30 bg-white/8 px-3 py-1 text-[10px] font-semibold tracking-[0.15em] text-[#f0d585] uppercase backdrop-blur"
                    >
                        <Sparkles class="size-3" />
                        Kurikulum Merdeka · Berbasis Panca Cinta
                    </div>
                    <p class="text-[12px] text-white/65">
                        {{ todayLabel() }}
                    </p>
                    <h1
                        class="text-shadow-hero mt-1 font-serif text-[26px] leading-tight font-bold tracking-tight sm:text-3xl"
                    >
                        {{ greetingText }},<br class="sm:hidden" />
                        {{ shortName }}
                    </h1>
                    <p
                        class="mt-2 max-w-xl text-[13px] leading-relaxed text-white/70"
                    >
                        Selamat datang di ruang kerja digital Anda di
                        <span class="font-semibold text-[#f0d585]">{{
                            madrasahName
                        }}</span
                        >. Kelola perangkat ajar, administrasi, dan asesmen
                        dalam satu tempat.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/8 px-3 py-1 text-[11px] text-white/85"
                        >
                            <CalendarRange class="size-3.5 text-[#e8c96b]" />
                            TA {{ academicYear }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/8 px-3 py-1 text-[11px] text-white/85"
                        >
                            <span class="size-1.5 rounded-full bg-[#e8c96b]" />
                            Semester {{ semester }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/8 px-3 py-1 text-[11px] text-white/85"
                        >
                            <UsersRound class="size-3.5 text-[#e8c96b]" />
                            {{ stats.classes }} kelas aktif
                        </span>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <Link
                            v-if="canManageSiswa"
                            href="/siswa"
                            class="inline-flex h-9 items-center gap-2 rounded-lg bg-[#c99700] px-4 text-[12.5px] font-semibold text-[#1b1403] shadow-[0_10px_24px_-12px_rgba(201,151,0,0.9)] transition hover:bg-[#dcae35]"
                        >
                            <Users class="size-4" />
                            Kelola Data Siswa
                            <ArrowRight class="size-3.5" />
                        </Link>
                        <Link
                            v-else
                            href="/rdm"
                            class="inline-flex h-9 items-center gap-2 rounded-lg bg-[#c99700] px-4 text-[12.5px] font-semibold text-[#1b1403] shadow-[0_10px_24px_-12px_rgba(201,151,0,0.9)] transition hover:bg-[#dcae35]"
                        >
                            <BookOpen class="size-4" />
                            Nilai RDM MI (13 Mapel)
                            <ArrowRight class="size-3.5" />
                        </Link>
                        <Link
                            v-if="canManageSiswa"
                            href="/rekap-emis"
                            class="inline-flex h-9 items-center gap-2 rounded-lg border border-white/20 bg-white/8 px-4 text-[12.5px] font-medium text-white/90 backdrop-blur transition hover:bg-white/15"
                        >
                            Rekap & Cetak Massal
                        </Link>
                        <Link
                            v-if="isSuperadmin"
                            href="/gtk"
                            class="inline-flex h-9 items-center gap-2 rounded-lg border border-white/20 bg-white/8 px-4 text-[12.5px] font-medium text-white/90 backdrop-blur transition hover:bg-white/15"
                        >
                            Data GTK ({{ stats.gurus ?? 0 }})
                        </Link>
                        <Link
                            href="/cek-nisn"
                            class="inline-flex h-9 items-center gap-2 rounded-lg border border-white/20 bg-white/8 px-4 text-[12.5px] font-medium text-white/90 backdrop-blur transition hover:bg-white/15"
                        >
                            Cek NISN
                        </Link>
                        <Link
                            href="/identitas"
                            class="inline-flex h-9 items-center gap-2 rounded-lg border border-white/20 bg-white/8 px-4 text-[12.5px] font-medium text-white/90 backdrop-blur transition hover:bg-white/15"
                        >
                            Lengkapi Identitas
                        </Link>
                    </div>
                </div>

                <!-- Profile completeness card -->
                <div class="relative">
                    <div
                        class="rounded-2xl border border-white/15 bg-white/[0.065] p-4 backdrop-blur-md"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="relative grid size-24 shrink-0 place-items-center"
                            >
                                <svg
                                    viewBox="0 0 100 100"
                                    class="absolute inset-0 size-full -rotate-90"
                                >
                                    <circle
                                        cx="50"
                                        cy="50"
                                        r="42"
                                        fill="none"
                                        stroke="rgba(255,255,255,0.14)"
                                        stroke-width="7"
                                    />
                                    <circle
                                        cx="50"
                                        cy="50"
                                        r="42"
                                        fill="none"
                                        stroke="#e8c96b"
                                        stroke-width="7"
                                        stroke-linecap="round"
                                        :stroke-dasharray="circumference"
                                        :stroke-dashoffset="dashOffset"
                                        class="transition-all duration-700"
                                    />
                                </svg>
                                <div class="text-center">
                                    <p
                                        class="font-serif text-xl leading-none font-bold text-white"
                                    >
                                        {{ completeness }}%
                                    </p>
                                    <p
                                        class="mt-0.5 text-[8px] tracking-[0.1em] text-white/55 uppercase"
                                    >
                                        Lengkap
                                    </p>
                                </div>
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="text-[10px] tracking-[0.16em] text-white/50 uppercase"
                                >
                                    Kartu Identitas
                                </p>
                                <p
                                    class="mt-1 truncate text-sm font-semibold text-white"
                                >
                                    {{ teacherName }}
                                </p>
                                <p class="truncate text-[11px] text-[#e8c96b]">
                                    NIP {{ profile?.nip || 'belum diisi' }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-[10.5px] text-white/55"
                                >
                                    {{ profile?.jabatan || 'Guru Madrasah' }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="mt-3 flex items-center justify-between gap-2 border-t border-white/10 pt-3"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="grid size-7 place-items-center rounded-full bg-[#c99700]/20 text-[10px] font-bold text-[#f0d585]"
                                >
                                    {{ initials }}
                                </div>
                                <span class="text-[10px] text-white/50">
                                    Profil tersimpan otomatis pada dokumen
                                </span>
                            </div>
                            <Link
                                href="/identitas"
                                class="inline-flex items-center gap-1 text-[10.5px] font-semibold text-[#f0d585] hover:text-white"
                            >
                                Edit
                                <ChevronRight class="size-3" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                label="Total Siswa"
                :value="stats.students"
                :hint="`${stats.male} laki-laki · ${stats.female} perempuan`"
                :icon="Users"
                tone="emerald"
            />
            <StatCard
                label="Kelas Aktif"
                :value="stats.classes"
                hint="Rombongan belajar terdata"
                :icon="LayoutGrid"
                tone="sky"
            />
            <StatCard
                label="Kelengkapan Profil"
                :value="`${stats.profile_completeness}%`"
                hint="Identitas guru & madrasah"
                :icon="BadgeCheck"
                tone="gold"
            />
            <StatCard
                label="Legalitas Dokumen"
                :value="`${stats.documents}/${stats.documents_required}`"
                :hint="
                    stats.documents === stats.documents_required
                        ? 'Semua dokumen lengkap'
                        : 'Lengkapi berkas legalitas'
                "
                :icon="Stamp"
                tone="violet"
            />
        </section>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">
                <!-- Quick access -->
                <section
                    class="rounded-2xl border border-border/80 bg-card p-4 shadow-[0_1px_2px_rgba(7,63,36,0.04)] md:p-5"
                >
                    <div class="mb-4 flex items-end justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-[0.18em] text-[#a87c00] uppercase"
                            >
                                Akses Cepat
                            </p>
                            <h2
                                class="mt-1 font-serif text-lg font-bold text-foreground"
                            >
                                Pintasan Modul
                            </h2>
                        </div>
                        <span
                            class="hidden text-[11px] text-muted-foreground sm:block"
                        >
                            {{ accessibleModuleCount }} modul dalam
                            {{ moduleGroups.length }} kategori
                        </span>
                    </div>

                    <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="module in visibleQuickModules"
                            :key="module.slug"
                            :href="module.href"
                            class="group flex items-start gap-3 rounded-xl border border-border/70 bg-background/40 p-3 transition hover:border-[#107c41]/30 hover:bg-[#107c41]/[0.035]"
                        >
                            <div
                                class="grid size-9 shrink-0 place-items-center rounded-lg bg-[#107c41]/8 text-[#107c41] ring-1 ring-[#107c41]/10 transition group-hover:bg-[#107c41] group-hover:text-white"
                            >
                                <component :is="module.icon" class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <p
                                        class="truncate text-[12.5px] font-semibold text-foreground"
                                    >
                                        {{ module.title }}
                                    </p>
                                    <span
                                        v-if="!module.ready"
                                        class="shrink-0 rounded-full bg-[#c99700]/12 px-1.5 py-0.5 text-[8px] font-semibold text-[#8a6800] uppercase"
                                    >
                                        Segera
                                    </span>
                                </div>
                                <p
                                    class="mt-0.5 line-clamp-1 text-[10.5px] text-muted-foreground"
                                >
                                    {{ module.description }}
                                </p>
                            </div>
                            <ArrowUpRight
                                class="mt-1 size-3.5 shrink-0 text-muted-foreground/50 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-[#107c41]"
                            />
                        </Link>
                    </div>
                </section>

                <!-- Class distribution -->
                <section
                    class="rounded-2xl border border-border/80 bg-card p-4 shadow-[0_1px_2px_rgba(7,63,36,0.04)] md:p-5"
                >
                    <div class="mb-4 flex items-end justify-between gap-3">
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-[0.18em] text-[#a87c00] uppercase"
                            >
                                Sebaran
                            </p>
                            <h2
                                class="mt-1 font-serif text-lg font-bold text-foreground"
                            >
                                Siswa per Kelas
                            </h2>
                        </div>
                        <Link
                            v-if="canManageSiswa"
                            href="/siswa"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#107c41] hover:underline"
                        >
                            Lihat semua
                            <ArrowRight class="size-3" />
                        </Link>
                    </div>

                    <div
                        v-if="classDistribution.length"
                        class="grid grid-cols-2 gap-x-5 gap-y-3 sm:grid-cols-3"
                    >
                        <div
                            v-for="item in classDistribution"
                            :key="item.kelas"
                            class="min-w-0"
                        >
                            <div
                                class="mb-1 flex items-center justify-between gap-2"
                            >
                                <span
                                    class="truncate text-[11.5px] font-medium text-foreground/80"
                                >
                                    {{ classLabel(item.kelas) }}
                                </span>
                                <span
                                    class="text-[10.5px] font-semibold text-muted-foreground"
                                >
                                    {{ item.total }}
                                </span>
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-[#107c41]/8"
                            >
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-[#107c41] to-[#3dbb7a]"
                                    :style="{
                                        width: `${Math.max((item.total / maxClassTotal) * 100, 6)}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="rounded-xl border border-dashed border-border py-8 text-center text-sm text-muted-foreground"
                    >
                        Belum ada data siswa. Tambahkan siswa pertama Anda.
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <!-- Legalitas checklist -->
                <section
                    class="rounded-2xl border border-border/80 bg-card p-4 shadow-[0_1px_2px_rgba(7,63,36,0.04)] md:p-5"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-[0.18em] text-[#a87c00] uppercase"
                            >
                                Berkas Resmi
                            </p>
                            <h2
                                class="mt-1 font-serif text-lg font-bold text-foreground"
                            >
                                Legalitas
                            </h2>
                        </div>
                        <div
                            class="rounded-full bg-[#107c41]/8 px-2.5 py-1 text-[10.5px] font-semibold text-[#107c41]"
                        >
                            {{ uploadedDocuments }}/{{ documentTypes.length }}
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <component
                            :is="isSuperadmin ? Link : 'div'"
                            v-for="document in documentTypes"
                            :key="document.type"
                            v-bind="isSuperadmin ? { href: '/legalitas' } : {}"
                            class="flex items-center gap-2.5 rounded-lg px-2 py-2 transition"
                            :class="
                                isSuperadmin ? 'hover:bg-[#107c41]/[0.04]' : ''
                            "
                        >
                            <span
                                class="grid size-7 shrink-0 place-items-center rounded-full"
                                :class="
                                    document.uploaded
                                        ? 'bg-[#107c41]/10 text-[#107c41]'
                                        : 'bg-[#c99700]/10 text-[#a87c00]'
                                "
                            >
                                <Check
                                    v-if="document.uploaded"
                                    class="size-3.5"
                                />
                                <Upload v-else class="size-3.5" />
                            </span>
                            <span
                                class="min-w-0 flex-1 truncate text-[11.5px] text-foreground/80"
                            >
                                {{ document.label }}
                            </span>
                            <span
                                class="text-[9.5px] font-semibold uppercase"
                                :class="
                                    document.uploaded
                                        ? 'text-[#107c41]'
                                        : 'text-[#a87c00]'
                                "
                            >
                                {{
                                    document.uploaded
                                        ? 'Ada'
                                        : isSuperadmin
                                          ? 'Unggah'
                                          : 'Belum'
                                }}
                            </span>
                        </component>
                    </div>
                </section>

                <!-- Recent students -->
                <section
                    class="rounded-2xl border border-border/80 bg-card p-4 shadow-[0_1px_2px_rgba(7,63,36,0.04)] md:p-5"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-[0.18em] text-[#a87c00] uppercase"
                            >
                                Terbaru
                            </p>
                            <h2
                                class="mt-1 font-serif text-lg font-bold text-foreground"
                            >
                                Siswa Baru
                            </h2>
                        </div>
                        <UserCheck class="size-4 text-[#107c41]/60" />
                    </div>

                    <div v-if="recentStudents.length" class="space-y-1">
                        <div
                            v-for="student in recentStudents"
                            :key="student.id"
                            class="flex items-center gap-2.5 rounded-lg px-1 py-2"
                        >
                            <div
                                class="grid size-8 shrink-0 place-items-center rounded-full text-[10px] font-bold"
                                :class="
                                    student.jenis_kelamin === 'P'
                                        ? 'bg-rose-500/10 text-rose-600 dark:text-rose-300'
                                        : 'bg-sky-500/10 text-sky-700 dark:text-sky-300'
                                "
                            >
                                {{ getInitials(student.nama) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-[11.5px] font-medium text-foreground/85"
                                >
                                    {{ student.nama }}
                                </p>
                                <p class="text-[10px] text-muted-foreground">
                                    NISN {{ student.nisn || '—' }}
                                </p>
                            </div>
                            <span
                                class="rounded-md bg-muted px-1.5 py-0.5 text-[9.5px] font-medium text-muted-foreground"
                            >
                                {{ student.kelas }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="py-5 text-center text-[11.5px] text-muted-foreground"
                    >
                        Belum ada data siswa.
                    </div>

                    <Link
                        :href="canManageSiswa ? '/siswa' : '/cek-nisn'"
                        class="mt-2 flex items-center justify-center gap-1 rounded-lg border border-[#107c41]/15 py-2 text-[11px] font-semibold text-[#107c41] transition hover:bg-[#107c41]/5"
                    >
                        {{
                            canManageSiswa
                                ? 'Buka Master Data Siswa'
                                : 'Cek Data Siswa (NISN)'
                        }}
                        <ArrowRight class="size-3" />
                    </Link>
                </section>
            </aside>
        </div>

        <!-- Panca Cinta -->
        <section
            class="relative overflow-hidden rounded-2xl border border-[#c99700]/20 bg-[linear-gradient(100deg,#fbf5e2,#fffdf6_55%,#f6efe0)] p-4 md:p-5 dark:border-[#c99700]/15 dark:bg-[linear-gradient(100deg,#171304,#0f1d13)]"
        >
            <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <p
                        class="text-[10px] font-semibold tracking-[0.18em] text-[#a87c00] uppercase"
                    >
                        Fondasi Karakter
                    </p>
                    <h2
                        class="mt-1 font-serif text-lg font-bold text-[#073f24] dark:text-[#f3d583]"
                    >
                        Lima Nilai Panca Cinta
                    </h2>
                </div>
                <BookOpen class="size-5 text-[#c99700]/60" />
            </div>
            <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-5">
                <div
                    v-for="(value, index) in pancaCinta"
                    :key="value.title"
                    class="rounded-xl border border-[#c99700]/15 bg-white/65 p-3 backdrop-blur-sm dark:bg-white/[0.03]"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="grid size-5 place-items-center rounded-full bg-[#c99700] text-[9.5px] font-bold text-white"
                        >
                            {{ index + 1 }}
                        </span>
                        <p
                            class="text-[11px] font-bold text-[#073f24] dark:text-[#f3d583]"
                        >
                            {{ value.title }}
                        </p>
                    </div>
                    <p
                        class="mt-1.5 text-[10px] leading-relaxed text-[#5c6d61] dark:text-muted-foreground"
                    >
                        {{ value.description }}
                    </p>
                </div>
            </div>
        </section>

        <p class="pb-2 text-center text-[10px] text-muted-foreground">
            Diperbarui {{ formatDate(new Date().toISOString()) }} · Perangkat
            Digital Guru Madrasah
        </p>
    </div>
</template>
