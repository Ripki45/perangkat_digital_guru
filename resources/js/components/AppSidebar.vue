<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import MadrasahBrand from '@/components/app/MadrasahBrand.vue';
import SidebarNav from '@/components/app/SidebarNav.vue';
import NavUser from '@/components/NavUser.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import { berandaNavItem, moduleGroups } from '@/lib/modules';

const page = usePage();
const { state } = useSidebar();
const { getInitials } = useInitials();

const user = page.props.auth.user;
const profile = computed(() => page.props.teacherProfile);

const visibleGroups = computed(() => {
    const role = user?.role ?? null;
    return moduleGroups
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) => !item.roles || (role && item.roles.includes(role)),
            ),
        }))
        .filter((group) => group.items.length > 0);
});

const teacherName = computed(() => profile.value?.nama_guru || user.name);
const madrasahName = computed(
    () => profile.value?.nama_madrasah || 'Madrasah Ibtidaiyah',
);
const motto = computed(
    () =>
        profile.value?.motto ||
        'Mendidik dengan hati, menumbuhkan cinta ilmu dan akhlak mulia.',
);
const academicYear = computed(() => profile.value?.tahun_ajaran || '2025/2026');
const semester = computed(() => profile.value?.semester || 'Ganjil');
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        class="[&_[data-sidebar=sidebar]]:border-r [&_[data-sidebar=sidebar]]:border-slate-200 [&_[data-sidebar=sidebar]]:bg-slate-50/70 dark:[&_[data-sidebar=sidebar]]:border-white/10 dark:[&_[data-sidebar=sidebar]]:bg-[#062214]"
    >
        <SidebarHeader
            class="gap-3 border-b border-slate-200/80 px-3 pt-4 pb-3 dark:border-white/10"
        >
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="h-auto justify-start rounded-xl p-1 hover:bg-transparent data-[state=open]:bg-transparent"
                    >
                        <Link href="/beranda" class="flex items-center gap-3">
                            <MadrasahBrand
                                variant="light"
                                :compact="state === 'collapsed'"
                            />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <Link
                href="/identitas"
                class="group/profile flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white p-2.5 shadow-2xs transition-colors group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:border-transparent group-data-[collapsible=icon]:bg-transparent group-data-[collapsible=icon]:p-0 hover:border-emerald-300 hover:bg-emerald-50/40 dark:border-white/10 dark:bg-white/[0.055] dark:hover:border-[#c99700]/35 dark:hover:bg-white/[0.09]"
                title="Buka identitas guru"
            >
                <Avatar
                    class="size-9 shrink-0 overflow-hidden rounded-lg ring-2 ring-[#c99700]/60 ring-offset-2 ring-offset-white dark:ring-offset-[#073f24]"
                >
                    <AvatarFallback
                        class="rounded-lg bg-gradient-to-br from-[#c99700] to-[#a87c00] text-[11px] font-bold text-white"
                    >
                        {{ getInitials(teacherName) }}
                    </AvatarFallback>
                </Avatar>
                <div
                    class="min-w-0 flex-1 leading-tight group-data-[collapsible=icon]:hidden"
                >
                    <p
                        class="truncate text-[12px] font-semibold text-slate-900 dark:text-white"
                    >
                        {{ teacherName }}
                    </p>
                    <p
                        class="mt-0.5 truncate text-[10px] font-medium text-[#107c41] dark:text-[#e8c96b]"
                    >
                        {{ madrasahName }}
                    </p>
                    <p
                        class="mt-0.5 truncate text-[9.5px] text-slate-500 dark:text-white/45"
                    >
                        Tahun {{ academicYear }} · {{ semester }}
                    </p>
                </div>
                <ChevronRight
                    class="size-3.5 shrink-0 text-slate-400 transition-transform group-hover/profile:translate-x-0.5 group-data-[collapsible=icon]:hidden dark:text-white/35"
                />
            </Link>
        </SidebarHeader>

        <SidebarContent>
            <SidebarNav :home-item="berandaNavItem" :groups="visibleGroups" />
        </SidebarContent>

        <SidebarFooter
            class="gap-2 border-t border-slate-200/80 px-3 pt-3 pb-2 dark:border-white/10"
        >
            <div
                class="rounded-xl border border-[#c99700]/30 bg-amber-50/60 p-3 group-data-[collapsible=icon]:hidden dark:border-[#c99700]/25 dark:bg-gradient-to-br dark:from-[#c99700]/15 dark:to-transparent"
            >
                <div
                    class="flex items-center gap-1.5 text-[9px] font-semibold tracking-[0.16em] text-[#9a7200] uppercase dark:text-[#e8c96b]"
                >
                    <Sparkles class="size-3" />
                    Kurikulum Merdeka · Panca Cinta
                </div>
                <p
                    class="mt-1.5 line-clamp-3 font-serif text-[11px] leading-relaxed text-slate-600 italic dark:text-white/60"
                >
                    “{{ motto }}”
                </p>
            </div>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
