<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import {
    ArrowRight,
    Award,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    FileCheck2,
    GraduationCap,
    LogIn,
    Presentation,
    ShieldCheck,
    Sparkles,
    Users,
} from "@lucide/vue";
import { ref } from "vue";

const props = defineProps<{
    demoLogin?: boolean;
}>();

const isLoggingInDemo = ref(false);

const handleDemoLogin = () => {
    router.post(
        "/demo-login",
        {},
        {
            onStart: () => {
                isLoggingInDemo.value = true;
            },
            onFinish: () => {
                isLoggingInDemo.value = false;
            },
        },
    );
};

const moduleGroups = [
    {
        title: "Profil & Legalitas",
        desc: "Identitas guru, kop madrasah, tanda tangan digital, dan kalibrasi stempel resmi.",
        items: [
            "Identitas Guru & Madrasah",
            "Logo, TTD & Stempel",
            "Sinkronisasi Dokumen",
        ],
        icon: FileCheck2,
    },
    {
        title: "Perencanaan Pembelajaran",
        desc: "Hitung hari dan jam efektif belajar secara presisi hingga jadwal mengajar mingguan.",
        items: [
            "Kaldik, HEB, JEB & RPE",
            "Jadwal Pelajaran",
            "Alokasi Beban Mengajar",
        ],
        icon: CalendarDays,
    },
    {
        title: "Perangkat Ajar Merdeka",
        desc: "Susun perangkat Kurikulum Merdeka Berbasis Panca Cinta yang langsung siap cetak A4/F4.",
        items: [
            "CP, TP & ATP",
            "Prota & Promes",
            "Modul Ajar, KKTP, LKPD & Jurnal",
        ],
        icon: BookOpen,
    },
    {
        title: "Kesiswaan & Asesmen",
        desc: "Kelola data siswa massal, presensi bulanan, bank soal, hingga analisis ketuntasan.",
        items: [
            "Master Data Siswa",
            "Presensi & Rekap Bulanan",
            "Bank Soal, Kisi-Kisi & Nilai",
        ],
        icon: ClipboardList,
    },
    {
        title: "Media & Panduan Digital",
        desc: "Presentasi slide pembelajaran interaktif di kelas lengkap dengan e-book panduan.",
        items: [
            "Slide Presentasi Interaktif",
            "Kuis Exit Ticket",
            "E-Book Panduan Guru",
        ],
        icon: Presentation,
    },
];

const roles = [
    {
        name: "Superadmin",
        badge: "Akses Penuh",
        desc: "Mengelola seluruh pengaturan madrasah, legalitas, manajemen akun guru, serta akses penuh ke semua modul perangkat ajar.",
        icon: ShieldCheck,
    },
    {
        name: "Guru Wali Kelas",
        badge: "Manajemen Kelas",
        desc: "Mengelola data siswa rombel binaan, presensi harian/bulanan, rekap nilai kelas, dan perangkat pembelajaran kelas.",
        icon: Users,
    },
    {
        name: "Guru Mata Pelajaran",
        badge: "Fokus Mapel",
        desc: "Menyusun CP/ATP, Prota/Promes, Modul Ajar, Bank Soal, serta penilaian lintas kelas sesuai mata pelajaran yang diampu.",
        icon: GraduationCap,
    },
];
</script>

<template>
    <Head title="Perangkat Digital Guru Madrasah" />

    <div
        class="min-h-screen bg-slate-50 text-slate-800 selection:bg-emerald-200 selection:text-emerald-950"
    >
        <!-- Navbar -->
        <header
            class="sticky top-0 z-40 border-b border-emerald-900/10 bg-white/90 backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#064423] to-[#107c41] text-amber-300 shadow-sm ring-2 ring-amber-400/40"
                    >
                        <BookOpen class="h-5 w-5" />
                    </div>
                    <div>
                        <p
                            class="text-sm font-extrabold tracking-tight text-slate-900 sm:text-base"
                        >
                            Perangkat Digital Guru Madrasah
                        </p>
                        <p class="text-xs font-medium text-emerald-700">
                            Kurikulum Merdeka Berbasis Panca Cinta
                        </p>
                    </div>
                </div>

                <nav class="flex items-center gap-2.5">
                    <Link
                        href="/login"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#107c41] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0c5c30] focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:outline-none"
                    >
                        <LogIn class="h-4 w-4" />
                        <span>Masuk</span>
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section
            class="relative overflow-hidden bg-gradient-to-br from-[#064423] via-[#0d5c31] to-[#107c41] text-white"
        >
            <div
                class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-amber-400/15 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-24 left-1/4 h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl"
            />

            <div
                class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8"
            >
                <div class="grid items-center gap-12 lg:grid-cols-12">
                    <div class="lg:col-span-7">
                        <div class="mb-6 flex flex-wrap items-center gap-2.5">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3.5 py-1 text-xs font-semibold tracking-wide text-emerald-50 backdrop-blur-sm"
                            >
                                <Sparkles class="h-3.5 w-3.5 text-amber-300" />
                                Kurikulum Merdeka Madrasah Ibtidaiyah
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-amber-400 px-3 py-1 text-xs font-bold text-slate-950 shadow-sm"
                            >
                                <Award class="h-3.5 w-3.5" />
                                Siap Cetak A4 / F4
                            </span>
                        </div>

                        <h1
                            class="text-3xl font-extrabold tracking-tight text-white sm:text-5xl sm:leading-tight"
                        >
                            Satu Ruang Kerja untuk Seluruh
                            <span class="text-amber-300"
                                >Administrasi & Perangkat Ajar</span
                            >
                            Guru Madrasah
                        </h1>

                        <p
                            class="mt-5 max-w-2xl text-base leading-relaxed text-emerald-50/90 sm:text-lg"
                        >
                            Kelola Kaldik, Jadwal Pelajaran, CP/ATP, Prota,
                            Promes, Modul Ajar, Presensi, hingga Bank Soal dan
                            Analisis Nilai dalam satu sistem terintegrasi—rapi,
                            otomatis tersinkronisasi, dan siap disahkan dengan
                            kop, tanda tangan, serta stempel madrasah.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3.5">
                            <Link
                                href="/login"
                                class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-6 py-3.5 text-sm font-bold text-slate-950 shadow-lg shadow-black/20 transition hover:-translate-y-0.5 hover:bg-amber-300"
                            >
                                <LogIn class="h-4 w-4" />
                                <span>Masuk ke Aplikasi</span>
                                <ArrowRight class="h-4 w-4" />
                            </Link>

                            <button
                                v-if="props.demoLogin"
                                type="button"
                                :disabled="isLoggingInDemo"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-5 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-60"
                                @click="handleDemoLogin"
                            >
                                <Sparkles class="h-4 w-4 text-amber-300" />
                                <span>{{
                                    isLoggingInDemo
                                        ? "Membuka Demo..."
                                        : "Masuk Cepat (Mode Demo Lokal)"
                                }}</span>
                            </button>
                        </div>

                        <div
                            class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-xs font-medium text-emerald-100/90 sm:text-sm"
                        >
                            <span class="inline-flex items-center gap-1.5">
                                <CheckCircle2 class="h-4 w-4 text-amber-300" />
                                Berbasis Database Terpusat
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <CheckCircle2 class="h-4 w-4 text-amber-300" />
                                Akses Terpisah 3 Peran
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <CheckCircle2 class="h-4 w-4 text-amber-300" />
                                Format Resmi Standar Kemenag
                            </span>
                        </div>
                    </div>

                    <!-- Preview Card Sisi Kanan -->
                    <div class="lg:col-span-5">
                        <div
                            class="rounded-2xl border border-white/20 bg-white/10 p-6 shadow-2xl backdrop-blur-md"
                        >
                            <div
                                class="flex items-center justify-between border-b border-white/15 pb-4"
                            >
                                <div>
                                    <p
                                        class="text-xs font-semibold tracking-wider text-amber-300 uppercase"
                                    >
                                        Ekosistem Terpadu
                                    </p>
                                    <h2
                                        class="mt-0.5 text-lg font-bold text-white"
                                    >
                                        Alur Kerja Guru Digital
                                    </h2>
                                </div>
                                <span
                                    class="rounded-lg bg-emerald-950/60 px-2.5 py-1 text-xs font-semibold text-emerald-200"
                                >
                                    v2.0 Laravel
                                </span>
                            </div>

                            <ol class="mt-5 space-y-4">
                                <li class="flex items-start gap-3">
                                    <span
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-400 text-xs font-extrabold text-slate-950"
                                    >
                                        1
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-white">
                                            Lengkapi Identitas & Legalitas
                                        </p>
                                        <p class="text-xs text-emerald-100/80">
                                            Input data guru, madrasah, logo,
                                            tanda tangan, dan stempel sekali
                                            saja.
                                        </p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-400 text-xs font-extrabold text-slate-950"
                                    >
                                        2
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-white">
                                            Susun Perencanaan & Perangkat Ajar
                                        </p>
                                        <p class="text-xs text-emerald-100/80">
                                            Kaldik, Prota, Promes, dan Modul
                                            Ajar otomatis mewarisi data
                                            madrasah.
                                        </p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-400 text-xs font-extrabold text-slate-950"
                                    >
                                        3
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-white">
                                            Kelola Kelas, Asesmen & Cetak
                                        </p>
                                        <p class="text-xs text-emerald-100/80">
                                            Catat presensi, olah nilai, dan
                                            cetak seluruh berkas administrasi
                                            rapi.
                                        </p>
                                    </div>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ringkasan 5 Kategori Modul -->
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p
                    class="text-xs font-bold tracking-wider text-emerald-700 uppercase"
                >
                    Modul Lengkap
                </p>
                <h2
                    class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl"
                >
                    Dirancang Khusus untuk Kebutuhan Guru Madrasah
                </h2>
                <p class="mt-2 text-sm text-slate-600 sm:text-base">
                    Seluruh perangkat pembelajaran dikelompokkan sistematis agar
                    mudah dicari, diperbarui, dan dicetak kapan saja.
                </p>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="group in moduleGroups"
                    :key="group.title"
                    class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-[#107c41] ring-1 ring-emerald-200"
                    >
                        <component :is="group.icon" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-900">
                        {{ group.title }}
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                        {{ group.desc }}
                    </p>
                    <ul
                        class="mt-4 space-y-1.5 border-t border-slate-100 pt-4 text-xs font-medium text-slate-700"
                    >
                        <li
                            v-for="item in group.items"
                            :key="item"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-amber-500"
                            />
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- 3 Peran Pengguna -->
        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p
                        class="text-xs font-bold tracking-wider text-emerald-700 uppercase"
                    >
                        Hak Akses Terstruktur
                    </p>
                    <h2
                        class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl"
                    >
                        Tiga Peran Kerja dalam Satu Madrasah
                    </h2>
                </div>

                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    <div
                        v-for="role in roles"
                        :key="role.name"
                        class="rounded-2xl border border-slate-200 bg-slate-50/70 p-6 transition hover:border-emerald-300 hover:bg-emerald-50/30"
                    >
                        <div class="flex items-center justify-between">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#107c41] text-white shadow-sm"
                            >
                                <component :is="role.icon" class="h-5 w-5" />
                            </div>
                            <span
                                class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 ring-1 ring-amber-200"
                            >
                                {{ role.badge }}
                            </span>
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-slate-900">
                            {{ role.name }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            {{ role.desc }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-slate-900 text-slate-400">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 text-xs sm:flex-row sm:px-6 lg:px-8"
            >
                <p class="font-medium text-slate-300">
                    Perangkat Digital Guru Madrasah &mdash; Kurikulum Merdeka
                    Berbasis Panca Cinta
                </p>
                <Link
                    href="/login"
                    class="font-semibold text-amber-300 transition hover:text-amber-200"
                >
                    Masuk ke Dashboard &rarr;
                </Link>
            </div>
        </footer>
    </div>
</template>
