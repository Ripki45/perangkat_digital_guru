<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CalendarRange, Printer } from '@lucide/vue';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const profile = computed(() => page.props.teacherProfile);
const academicYear = computed(
    () => profile.value?.tahun_ajaran || '2025/2026',
);
const semester = computed(() => profile.value?.semester || 'Ganjil');
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-[#073f24]/10 bg-[#f4f7f4]/85 px-4 backdrop-blur-md transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-14 md:px-6 dark:border-border dark:bg-background/85"
    >
        <div class="flex min-w-0 items-center gap-3">
            <SidebarTrigger
                class="-ml-1 text-[#073f24]/70 hover:text-[#073f24] dark:text-foreground/70 dark:hover:text-foreground"
            />
            <div class="hidden h-5 w-px bg-[#073f24]/15 sm:block dark:bg-border" />
            <div class="min-w-0 truncate">
                <Breadcrumbs
                    v-if="breadcrumbs && breadcrumbs.length > 0"
                    :breadcrumbs="breadcrumbs"
                />
                <span
                    v-else
                    class="text-sm font-medium text-muted-foreground"
                >
                    Perangkat Digital Guru Madrasah
                </span>
            </div>
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-2">
            <div
                class="hidden items-center gap-2 rounded-full border border-[#107c41]/15 bg-white/70 px-3 py-1.5 text-[11px] text-[#073f24] shadow-xs md:flex dark:border-border dark:bg-card dark:text-foreground"
            >
                <CalendarRange class="size-3.5 text-[#107c41]" />
                <span class="font-semibold">TA {{ academicYear }}</span>
                <span class="size-1 rounded-full bg-[#c99700]" />
                <span class="text-muted-foreground">Semester {{ semester }}</span>
            </div>
            <div
                class="hidden items-center gap-1.5 rounded-full border border-[#c99700]/25 bg-[#c99700]/8 px-3 py-1.5 text-[10.5px] font-medium text-[#8a6800] xl:flex dark:text-[#e0b84a]"
                title="Dokumen dirancang untuk dicetak pada kertas A4 atau F4"
            >
                <Printer class="size-3.5" />
                Siap Cetak A4/F4
            </div>
        </div>
    </header>
</template>
