<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    Clock,
    Compass,
    Sparkles,
} from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/app/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { findModule, modules } from '@/lib/modules';

const props = defineProps<{
    slug: string;
}>();

const module = computed(() => findModule(props.slug));

defineOptions({
    layout: (props: { slug: string }) => {
        const current = findModule(props.slug);

        return {
            breadcrumbs: [
                { title: 'Beranda', href: '/beranda' },
                ...(current
                    ? [
                          {
                              title: current.groupTitle,
                              href: current.href,
                          },
                          { title: current.title, href: current.href },
                      ]
                    : [{ title: 'Modul', href: '/beranda' }]),
            ],
        };
    },
});

const related = computed(() => {
    if (!module.value) {
        return [];
    }

    return modules
        .filter(
            (item) =>
                item.groupId === module.value?.groupId &&
                item.slug !== module.value?.slug,
        )
        .slice(0, 4);
});

const siblingGroups = computed(() => {
    if (!module.value) {
        return [];
    }

    return modules
        .filter((item) => item.groupId !== module.value?.groupId && item.ready)
        .slice(0, 3);
});
</script>

<template>
    <Head :title="module?.title ?? 'Modul'" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <template v-if="module">
            <PageHeader
                :eyebrow="module.groupTitle"
                :title="module.title"
                :description="module.description"
                :icon="module.icon"
            >
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link href="/beranda">
                            <ArrowLeft />
                            Beranda
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-5 xl:grid-cols-[1.6fr_1fr]">
                <section
                    class="relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[0_1px_2px_rgba(7,63,36,0.04)] md:p-7"
                >
                    <div
                        class="pointer-events-none absolute -top-20 -right-16 size-56 rounded-full bg-[#107c41]/8 blur-3xl"
                    />
                    <div class="relative">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-[#c99700]/30 bg-[#c99700]/10 px-2.5 py-1 text-[9.5px] font-semibold tracking-[0.14em] text-[#8a6800] uppercase dark:text-[#e0b84a]"
                        >
                            <Clock class="size-3" />
                            Dalam Pengembangan
                        </span>

                        <div class="mt-5 flex items-start gap-4">
                            <div
                                class="grid size-14 shrink-0 place-items-center rounded-2xl bg-[linear-gradient(140deg,#107c41,#0b5a31)] text-white shadow-[0_12px_28px_-16px_rgba(16,124,65,0.9)]"
                            >
                                <component :is="module.icon" class="size-6" />
                            </div>
                            <div class="min-w-0">
                                <h2 class="font-serif text-lg leading-snug font-bold text-foreground md:text-xl">
                                    Modul ini sedang disiapkan dengan teliti
                                </h2>
                                <p class="mt-1.5 text-[12.5px] leading-relaxed text-muted-foreground">
                                    Kami menyusun
                                    <span class="font-semibold text-foreground">{{ module.title }}</span>
                                    agar benar-benar sesuai dengan kebutuhan administrasi
                                    guru madrasah — mulai dari format cetak A4/F4, penomoran,
                                    hingga integrasi identitas madrasah Anda.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="module.features.length"
                            class="mt-6 rounded-xl border border-[#107c41]/12 bg-[#107c41]/[0.025] p-4"
                        >
                            <p class="text-[10px] font-semibold tracking-[0.16em] text-[#a87c00] uppercase">
                                Rencana Fitur
                            </p>
                            <ul class="mt-2.5 grid gap-2 sm:grid-cols-2">
                                <li
                                    v-for="feature in module.features"
                                    :key="feature"
                                    class="flex items-start gap-2 text-[12px] leading-relaxed text-foreground/80"
                                >
                                    <span
                                        class="mt-0.5 grid size-4 shrink-0 place-items-center rounded-full bg-[#107c41]/10 text-[#107c41]"
                                    >
                                        <Check class="size-2.5" />
                                    </span>
                                    {{ feature }}
                                </li>
                            </ul>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center gap-2">
                            <Button as-child>
                                <Link href="/siswa">
                                    <Compass />
                                    Mulai dari Master Data Siswa
                                </Link>
                            </Button>
                            <Button variant="outline" as-child>
                                <Link href="/identitas">
                                    Lengkapi Identitas
                                </Link>
                            </Button>
                        </div>
                    </div>
                </section>

                <aside class="space-y-4">
                    <section class="rounded-2xl border border-border/80 bg-card p-4 md:p-5">
                        <div class="flex items-center gap-2">
                            <Sparkles class="size-4 text-[#c99700]" />
                            <h3 class="font-serif text-sm font-bold text-foreground">
                                Modul lain di kategori ini
                            </h3>
                        </div>
                        <div class="mt-3 space-y-1">
                            <Link
                                v-for="item in related"
                                :key="item.slug"
                                :href="item.href"
                                class="group flex items-center gap-2.5 rounded-lg px-2 py-2 transition hover:bg-[#107c41]/[0.04]"
                            >
                                <span
                                    class="grid size-7 shrink-0 place-items-center rounded-lg bg-[#107c41]/8 text-[#107c41]"
                                >
                                    <component :is="item.icon" class="size-3.5" />
                                </span>
                                <span class="min-w-0 flex-1 truncate text-[11.5px] text-foreground/80">
                                    {{ item.title }}
                                </span>
                                <ArrowRight
                                    class="size-3 shrink-0 text-muted-foreground/40 transition group-hover:translate-x-0.5 group-hover:text-[#107c41]"
                                />
                            </Link>
                            <p
                                v-if="related.length === 0"
                                class="px-2 py-3 text-[11.5px] text-muted-foreground"
                            >
                                Tidak ada modul lain di kategori ini.
                            </p>
                        </div>
                    </section>

                    <section
                        class="overflow-hidden rounded-2xl border border-[#073f24]/15 bg-[linear-gradient(150deg,#073f24,#0b5a31_65%,#107c41)] p-4 text-white md:p-5"
                    >
                        <p class="text-[9px] font-semibold tracking-[0.2em] text-[#e8c96b] uppercase">
                            Sudah tersedia
                        </p>
                        <p class="mt-1.5 font-serif text-base font-bold">
                            Modul siap pakai
                        </p>
                        <div class="mt-3 space-y-1.5">
                            <Link
                                v-for="item in siblingGroups"
                                :key="item.slug"
                                :href="item.href"
                                class="flex items-center gap-2 rounded-lg border border-white/10 bg-white/[0.06] px-2.5 py-2 text-[11.5px] transition hover:border-[#c99700]/40 hover:bg-white/[0.1]"
                            >
                                <component :is="item.icon" class="size-3.5 text-[#e8c96b]" />
                                <span class="min-w-0 flex-1 truncate">{{ item.title }}</span>
                                <ArrowRight class="size-3 shrink-0 text-white/40" />
                            </Link>
                        </div>
                    </section>
                </aside>
            </div>
        </template>

        <section
            v-else
            class="mx-auto flex max-w-lg flex-col items-center justify-center rounded-2xl border border-dashed border-border px-6 py-16 text-center"
        >
            <div class="grid size-14 place-items-center rounded-2xl bg-[#c99700]/10 text-[#a87c00]">
                <Compass class="size-6" />
            </div>
            <h1 class="mt-4 font-serif text-lg font-bold text-foreground">
                Modul tidak ditemukan
            </h1>
            <p class="mt-1 text-[12.5px] text-muted-foreground">
                Alamat yang Anda buka tidak tersedia. Silakan kembali ke beranda.
            </p>
            <Button class="mt-4" as-child>
                <Link href="/beranda">Kembali ke Beranda</Link>
            </Button>
        </section>
    </div>
</template>
