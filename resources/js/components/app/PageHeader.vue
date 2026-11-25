<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: LucideIcon;
        eyebrow?: string;
    }>(),
    {
        description: '',
        eyebrow: '',
    },
);
</script>

<template>
    <div
        class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
    >
        <div class="flex min-w-0 items-start gap-3">
            <div
                v-if="icon"
                class="mt-0.5 grid size-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/15"
            >
                <component :is="icon" class="size-5" />
            </div>
            <div>
                <p
                    v-if="eyebrow"
                    class="mb-1 text-[10.5px] font-semibold tracking-[0.2em] text-[#a87c00] uppercase"
                >
                    {{ eyebrow }}
                </p>
                <h1
                    class="text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                >
                    {{ title }}
                </h1>
                <p
                    v-if="description"
                    class="mt-1 max-w-2xl text-sm leading-relaxed text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
        </div>
        <div
            v-if="$slots.actions"
            class="flex shrink-0 flex-wrap items-center gap-2"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
