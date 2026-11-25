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
import { moduleGroups } from '@/lib/modules';

const page = usePage();
const { state } = useSidebar();
const { getInitials } = useInitials();

const user = page.props.auth.user;
const profile = computed(() => page.props.teacherProfile);

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
        class="[&_[data-sidebar=sidebar]]:bg-[linear-gradient(180deg,#073f24_0%,#062f1c_100%)]"
    >
        <SidebarHeader
            class="gap-3 border-b border-white/10 px-3 pt-4 pb-3"
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
                                variant="sidebar"
                                :compact="state === 'collapsed'"
                            />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <Link
                href="/identitas"
                class="group/profile flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/[0.055] p-2.5 transition-colors hover:border-[#c99700]/35 hover:bg-white/[0.09] group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:border-transparent group-data-[collapsible=icon]:bg-transparent group-data-[collapsible=icon]:p-0"
                title="Buka identitas guru"
            >
                <Avatar
                    class="size-9 shrink-0 overflow-hidden rounded-lg ring-2 ring-[#c99700]/60 ring-offset-2 ring-offset-[#073f24]"
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
                    <p class="truncate text-[12px] font-semibold text-white">
                        {{ teacherName }}
                    </p>
                    <p class="mt-0.5 truncate text-[10px] text-[#e8c96b]">
                        {{ madrasahName }}
                    </p>
                    <p class="mt-0.5 truncate text-[9.5px] text-white/45">
                        Tahun {{ academicYear }} · {{ semester }}
                    </p>
                </div>
                <ChevronRight
                    class="size-3.5 shrink-0 text-white/35 transition-transform group-hover/profile:translate-x-0.5 group-data-[collapsible=icon]:hidden"
                />
            </Link>
        </SidebarHeader>

        <SidebarContent>
            <SidebarNav :groups="moduleGroups" />
        </SidebarContent>

        <SidebarFooter
            class="gap-2 border-t border-white/10 px-3 pt-3 pb-2"
        >
            <div
                class="rounded-xl border border-[#c99700]/25 bg-gradient-to-br from-[#c99700]/15 to-transparent p-3 group-data-[collapsible=icon]:hidden"
            >
                <div
                    class="flex items-center gap-1.5 text-[9px] font-semibold tracking-[0.16em] text-[#e8c96b] uppercase"
                >
                    <Sparkles class="size-3" />
                    Kurikulum Merdeka · Panca Cinta
                </div>
                <p
                    class="mt-1.5 line-clamp-3 font-serif text-[11px] leading-relaxed text-white/60 italic"
                >
                    “{{ motto }}”
                </p>
            </div>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
