<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { ModuleGroup } from '@/lib/modules';

const props = defineProps<{
    groups: ModuleGroup[];
}>();

const { state } = useSidebar();
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const activeGroupId = computed(
    () =>
        props.groups.find((group) =>
            group.items.some((item) => isCurrentOrParentUrl(item.href)),
        )?.id ?? null,
);

const openGroupId = ref<string | null>(null);

const syncOpenGroup = () => {
    if (activeGroupId.value) {
        openGroupId.value = activeGroupId.value;
    }
};

syncOpenGroup();

watch(activeGroupId, (groupId) => {
    if (groupId) {
        openGroupId.value = groupId;
    }
});

const toggleGroup = (groupId: string, open: boolean) => {
    openGroupId.value = open ? groupId : null;
};

const isGroupActive = (group: ModuleGroup) =>
    group.id === activeGroupId.value;

const activeItemHref = (group: ModuleGroup) =>
    group.items.find((item) => isCurrentOrParentUrl(item.href))?.href ??
    group.items[0]?.href ??
    '/beranda';

const isItemActive = (href: string) => isCurrentUrl(href);
</script>

<template>
    <!-- Expanded accordion navigation -->
    <SidebarGroup
        v-if="state !== 'collapsed'"
        class="sidebar-scroll min-h-0 flex-1 gap-1 overflow-y-auto px-2 py-1"
    >
        <Collapsible
            v-for="group in groups"
            :key="group.id"
            :open="openGroupId === group.id"
            @update:open="(open) => toggleGroup(group.id, open)"
        >
            <CollapsibleTrigger as-child>
                <SidebarMenuButton
                    class="h-9 rounded-lg px-2.5 text-white/85 transition-colors hover:bg-white/10 hover:text-white data-[state=open]:bg-white/10 data-[state=open]:text-white"
                >
                    <component
                        :is="group.icon"
                        class="size-4 shrink-0 text-[#e8c96b]"
                    />
                    <span class="text-[12.5px] font-semibold tracking-wide">
                        {{ group.title }}
                    </span>
                    <ChevronDown
                        class="ml-auto size-3.5 shrink-0 text-white/50 transition-transform duration-200"
                        :class="
                            openGroupId === group.id ? 'rotate-180' : ''
                        "
                    />
                </SidebarMenuButton>
            </CollapsibleTrigger>

            <CollapsibleContent class="overflow-hidden">
                <SidebarMenuSub class="border-white/15 py-1">
                    <SidebarMenuSubItem
                        v-for="item in group.items"
                        :key="item.slug"
                    >
                        <SidebarMenuSubButton
                            as-child
                            size="md"
                            :is-active="isItemActive(item.href)"
                            class="h-8 rounded-md text-white/70 hover:bg-white/10 hover:text-white data-[active=true]:bg-[#c99700]/18 data-[active=true]:font-semibold data-[active=true]:text-[#f7e6b0] data-[active=true]:shadow-[inset_2px_0_0_#c99700] [&>svg]:text-current"
                        >
                            <Link :href="item.href" class="flex w-full items-center gap-2">
                                <component
                                    :is="item.icon"
                                    class="size-3.5 shrink-0 opacity-80"
                                />
                                <span class="truncate">{{ item.title }}</span>
                                <span
                                    v-if="!item.ready"
                                    class="ml-auto rounded-full border border-white/15 px-1.5 py-0.5 text-[8px] font-semibold tracking-wider text-white/40 uppercase"
                                >
                                    Segera
                                </span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                </SidebarMenuSub>
            </CollapsibleContent>
        </Collapsible>
    </SidebarGroup>

    <!-- Collapsed icon navigation -->
    <SidebarGroup v-else class="px-2 py-1">
        <SidebarMenu class="gap-1">
            <SidebarMenuItem v-for="group in groups" :key="group.id">
                <SidebarMenuButton
                    as-child
                    :tooltip="group.title"
                    :is-active="isGroupActive(group)"
                    class="h-9 rounded-lg text-white/80 hover:bg-white/10 hover:text-white data-[active=true]:bg-[#c99700]/18 data-[active=true]:text-[#f7e6b0]"
                >
                    <Link :href="activeItemHref(group)" class="flex items-center gap-2">
                        <component
                            :is="group.icon"
                            class="size-4 shrink-0 text-[#e8c96b]"
                        />
                        <span>{{ group.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
