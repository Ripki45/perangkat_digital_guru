<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'sidebar' | 'light';
        compact?: boolean;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        variant: 'sidebar',
        compact: false,
        size: 'md',
    },
);
</script>

<template>
    <div class="flex min-w-0 items-center gap-3">
        <div
            class="relative grid shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#f2d583] via-[#dcae35] to-[#b98700] shadow-[0_8px_20px_-10px_rgba(201,151,0,0.9)] ring-1 ring-white/40 ring-inset"
            :class="{
                'size-9': size === 'sm',
                'size-10': size === 'md',
                'size-12': size === 'lg',
            }"
        >
            <svg
                viewBox="0 0 40 40"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                class="relative"
                :class="{
                    'size-6': size === 'sm',
                    'size-7': size === 'md',
                    'size-8': size === 'lg',
                }"
                aria-hidden="true"
            >
                <path
                    d="M20 5.5c-2 3.2-6.4 5-6.4 10.2 0 3.9 2.8 6.6 6.4 6.6s6.4-2.7 6.4-6.6C26.4 10.5 22 8.7 20 5.5Z"
                    fill="#073f24"
                />
                <path
                    d="M9 21.5c0-1.4 1.2-2.6 2.6-2.6h5.9c1 0 1.7-.5 2.5-1.3.8.8 1.5 1.3 2.5 1.3h5.9c1.4 0 2.6 1.2 2.6 2.6v1.4H9v-1.4Z"
                    fill="#073f24"
                />
                <path
                    d="M20 24.4c-3.6-2-8.4-2.3-11-1.6v8.4c3.2-.6 7.4 0 11 2 3.6-2 7.8-2.6 11-2v-8.4c-2.6-.7-7.4-.4-11 1.6Z"
                    fill="#fff9e8"
                />
                <path
                    d="M20 24.4v8.8M15 27.6c1.4-.1 2.9.2 4 .7M25 27.6c-1.4-.1-2.9.2-4 .7"
                    stroke="#073f24"
                    stroke-width="1"
                    stroke-linecap="round"
                    opacity="0.45"
                />
                <circle
                    cx="20"
                    cy="17.6"
                    r="1.3"
                    fill="#f2d583"
                    opacity="0.85"
                />
            </svg>
        </div>

        <div v-if="!compact" class="grid min-w-0 leading-tight">
            <span
                class="truncate font-serif text-[15px] font-bold tracking-tight"
                :class="
                    variant === 'sidebar'
                        ? 'text-white'
                        : 'text-[#073f24] dark:text-white'
                "
            >
                Perangkat Guru
            </span>
            <span
                class="truncate text-[9px] font-semibold tracking-[0.19em] uppercase"
                :class="
                    variant === 'sidebar' ? 'text-[#e8c96b]' : 'text-[#a87c00]'
                "
            >
                Madrasah Ibtidaiyah
            </span>
        </div>
    </div>
</template>
