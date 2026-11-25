<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: LucideIcon;
    }>(),
    {
        description: '',
    },
);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-[#107c41]/25 bg-[#107c41]/[0.015] px-6 py-12 text-center"
    >
        <div
            v-if="icon"
            class="mb-4 grid size-14 place-items-center rounded-2xl bg-[#107c41]/8 text-[#107c41] ring-1 ring-[#107c41]/15"
        >
            <component :is="icon" class="size-6" />
        </div>
        <h3 class="font-serif text-base font-bold text-foreground">
            {{ title }}
        </h3>
        <p
            v-if="description"
            class="mt-1.5 max-w-md text-sm leading-relaxed text-muted-foreground"
        >
            {{ description }}
        </p>
        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>
