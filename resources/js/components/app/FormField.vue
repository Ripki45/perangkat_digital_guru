<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        for?: string;
        hint?: string;
        error?: string;
        required?: boolean;
    }>(),
    {
        hint: '',
        error: '',
        required: false,
    },
);
</script>

<template>
    <div class="grid gap-1.5">
        <label
            :for="$props.for"
            class="text-[12.5px] font-semibold text-foreground/85"
        >
            {{ label }}
            <span v-if="required" class="text-[#c99700]">*</span>
        </label>
        <slot />
        <p v-if="hint && !error" class="text-[11px] text-muted-foreground">
            {{ hint }}
        </p>
        <p v-if="error" class="text-[11.5px] font-medium text-destructive">
            {{ error }}
        </p>
    </div>
</template>
