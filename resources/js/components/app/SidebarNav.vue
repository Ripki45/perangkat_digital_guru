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
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { ModuleGroup, ModuleItem } from '@/lib/modules';

const props = defineProps<{
    homeItem?: ModuleItem;
    groups: ModuleGroup[];
}>();

const { state } = useSidebar();
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const isItemMatch = (href: string) =>
    isCurrentUrl(href) || (href === '/mapel-rombel' && isCurrentUrl('/rombel'));

const activeGroupId = computed(
    () =>
        props.groups.find((group) =>
            group.items.some(
                (item) =>
                    isCurrentOrParentUrl(item.href) ||
                    (item.href === '/mapel-rombel' &&
                        isCurrentOrParentUrl('/rombel')),
            ),
        )?.id ?? 'biodata',
);

const openGroupId = ref<string | null>(activeGroupId.value);

watch(activeGroupId, (groupId) => {
    if (groupId) {
        openGroupId.value = groupId;
    }
});

const toggleGroup = (groupId: string, open: boolean) => {
    openGroupId.value = open ? groupId : null;
};

const isGroupActive = (group: ModuleGroup) => group.id === activeGroupId.value;

const activeItemHref = (group: ModuleGroup) =>
    group.items.find((item) => isCurrentOrParentUrl(item.href))?.href ??
    group.items[0]?.href ??
    '/beranda';
</script>

<template>
    <!-- Expanded carded accordion navigation (matches BIODATA design) -->
    <SidebarGroup
        v-if="state !== 'collapsed'"
        class="sidebar-scroll min-h-0 flex-1 gap-2.5 overflow-y-auto px-2.5 py-2"
    >
        <!-- Standalone Beranda & Mukodimah pill card -->
        <Link
            v-if="homeItem"
            :href="homeItem.href"
            class="flex items-center gap-2.5 rounded-xl border px-3 py-2.5 text-[13px] font-bold transition-colors"
            :class="
                isItemMatch(homeItem.href)
                    ? 'border-[#107c41] bg-[#107c41] text-white shadow-xs'
                    : 'border-slate-200 bg-white text-slate-800 shadow-2xs hover:border-emerald-300 hover:bg-emerald-50/40 dark:border-white/10 dark:bg-white/[0.06] dark:text-white dark:hover:bg-white/10'
            "
        >
            <component
                :is="homeItem.icon"
                class="size-4 shrink-0"
                :class="
                    isItemMatch(homeItem.href)
                        ? 'text-white'
                        : 'text-slate-600 dark:text-[#e8c96b]'
                "
            />
            <span class="truncate">{{ homeItem.title }}</span>
        </Link>

        <!-- Collapsible category groups (BIODATA, etc.) -->
        <Collapsible
            v-for="group in groups"
            :key="group.id"
            :open="openGroupId === group.id"
            @update:open="(open) => toggleGroup(group.id, open)"
        >
            <CollapsibleTrigger as-child>
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left text-[12.5px] font-bold tracking-wide transition-colors"
                    :class="
                        openGroupId === group.id || isGroupActive(group)
                            ? 'border-l-4 border-emerald-300 border-l-[#107c41] bg-emerald-50/80 text-[#0b5a31] dark:border-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-200'
                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/[0.05] dark:text-white/85 dark:hover:bg-white/10'
                    "
                >
                    <component
                        :is="group.icon"
                        class="size-4 shrink-0"
                        :class="
                            openGroupId === group.id || isGroupActive(group)
                                ? 'text-amber-500'
                                : 'text-slate-500 dark:text-[#e8c96b]'
                        "
                    />
                    <span class="truncate">{{ group.title }}</span>
                    <ChevronDown
                        class="ml-auto size-4 shrink-0 transition-transform duration-200"
                        :class="[
                            openGroupId === group.id ? 'rotate-180' : '',
                            openGroupId === group.id || isGroupActive(group)
                                ? 'text-[#107c41] dark:text-emerald-300'
                                : 'text-slate-400',
                        ]"
                    />
                </button>
            </CollapsibleTrigger>

            <CollapsibleContent class="overflow-hidden">
                <div
                    class="mt-1.5 ml-3.5 space-y-1 border-l-2 border-[#107c41] py-0.5 pl-3"
                >
                    <Link
                        v-for="item in group.items"
                        :key="item.slug"
                        :href="item.href"
                        class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-[12.5px] transition-colors"
                        :class="
                            isItemMatch(item.href)
                                ? 'bg-[#107c41] font-semibold text-white shadow-xs'
                                : 'font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white'
                        "
                    >
                        <component
                            :is="item.icon"
                            class="size-4 shrink-0"
                            :class="
                                isItemMatch(item.href)
                                    ? 'text-white'
                                    : 'text-slate-500 dark:text-white/60'
                            "
                        />
                        <span class="truncate">{{ item.title }}</span>
                        <span
                            v-if="!item.ready"
                            class="ml-auto rounded-full border px-1.5 py-0.5 text-[8px] font-semibold tracking-wider uppercase"
                            :class="
                                isItemMatch(item.href)
                                    ? 'border-white/30 text-white/80'
                                    : 'border-slate-300 text-slate-400 dark:border-white/15 dark:text-white/40'
                            "
                        >
                            Segera
                        </span>
                    </Link>
                </div>
            </CollapsibleContent>
        </Collapsible>
    </SidebarGroup>

    <!-- Collapsed icon navigation -->
    <SidebarGroup v-else class="px-2 py-1">
        <SidebarMenu class="gap-1">
            <SidebarMenuItem v-if="homeItem">
                <SidebarMenuButton
                    as-child
                    :tooltip="homeItem.title"
                    :is-active="isItemMatch(homeItem.href)"
                    class="h-9 rounded-lg text-slate-700 hover:bg-emerald-50 data-[active=true]:bg-[#107c41] data-[active=true]:text-white dark:text-white/85"
                >
                    <Link :href="homeItem.href" class="flex items-center gap-2">
                        <component
                            :is="homeItem.icon"
                            class="size-4 shrink-0"
                        />
                        <span>{{ homeItem.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
            <SidebarMenuItem v-for="group in groups" :key="group.id">
                <SidebarMenuButton
                    as-child
                    :tooltip="group.title"
                    :is-active="isGroupActive(group)"
                    class="h-9 rounded-lg text-slate-700 hover:bg-emerald-50 data-[active=true]:bg-[#107c41] data-[active=true]:text-white dark:text-white/85"
                >
                    <Link
                        :href="activeItemHref(group)"
                        class="flex items-center gap-2"
                    >
                        <component :is="group.icon" class="size-4 shrink-0" />
                        <span>{{ group.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
