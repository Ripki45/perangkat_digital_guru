<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { computed } from 'vue';

type Tone = 'emerald' | 'gold' | 'sky' | 'rose' | 'violet';

const props = withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        hint?: string;
        icon: LucideIcon;
        tone?: Tone;
    }>(),
    {
        hint: '',
        tone: 'emerald',
    },
);

const tones: Record<Tone, { tile: string; ring: string; bar: string }> = {
    emerald: {
        tile: 'bg-[#107c41]/10 text-[#107c41]',
        ring: 'ring-[#107c41]/15',
        bar: 'from-[#107c41] to-[#2f9e6a]',
    },
    gold: {
        tile: 'bg-[#c99700]/12 text-[#a87c00]',
        ring: 'ring-[#c99700]/20',
        bar: 'from-[#c99700] to-[#e0b84a]',
    },
    sky: {
        tile: 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
        ring: 'ring-sky-500/15',
        bar: 'from-sky-500 to-cyan-400',
    },
    rose: {
        tile: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
        ring: 'ring-rose-500/15',
        bar: 'from-rose-500 to-pink-400',
    },
    violet: {
        tile: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
        ring: 'ring-violet-500/15',
        bar: 'from-violet-500 to-purple-400',
    },
};

const palette = computed(() => tones[props.tone]);
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-4 shadow-[0_1px_2px_rgba(7,63,36,0.04)] transition-shadow hover:shadow-[0_12px_30px_-18px_rgba(7,63,36,0.35)]"
    >
        <div
            class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r opacity-70"
            :class="palette.bar"
        />
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p
                    class="text-[10.5px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                >
                    {{ label }}
                </p>
                <p
                    class="mt-2 font-serif text-[26px] leading-none font-bold text-foreground"
                >
                    {{ value }}
                </p>
                <p
                    v-if="hint"
                    class="mt-2 truncate text-[11.5px] text-muted-foreground"
                >
                    {{ hint }}
                </p>
            </div>
            <div
                class="grid size-10 shrink-0 place-items-center rounded-xl ring-1"
                :class="[palette.tile, palette.ring]"
            >
                <component :is="icon" class="size-[18px]" />
            </div>
        </div>
    </div>
</template>
