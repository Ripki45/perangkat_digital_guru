<script setup lang="ts">
import { LoaderCircle, Trash } from '@lucide/vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string;
        confirmLabel?: string;
        processing?: boolean;
    }>(),
    {
        description: '',
        confirmLabel: 'Hapus',
        processing: false,
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="gap-2">
                <div
                    class="mb-1 grid size-11 place-items-center rounded-xl bg-destructive/10 text-destructive ring-1 ring-destructive/15"
                >
                    <Trash class="size-5" />
                </div>
                <DialogTitle class="text-left text-lg">{{ title }}</DialogTitle>
                <DialogDescription class="text-left">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="mt-2 gap-2">
                <Button
                    variant="outline"
                    type="button"
                    :disabled="processing"
                    @click="emit('update:open', false)"
                >
                    Batal
                </Button>
                <Button
                    variant="destructive"
                    type="button"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    <LoaderCircle v-if="processing" class="animate-spin" />
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
