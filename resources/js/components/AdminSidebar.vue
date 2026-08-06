<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, Images, LayoutDashboard, LayoutTemplate, ShieldCheck } from 'lucide-vue-next';
import AdminNavUser from '@/components/AdminNavUser.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import admin from '@/routes/admin';
import { type NavItem } from '@/types';

const adminNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: admin.dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Tenants',
        href: admin.tenants.index(),
        icon: Building2,
    },
    {
        title: 'Poster templates',
        href: admin.posterTemplates.index(),
        icon: LayoutTemplate,
    },
    {
        title: 'Library',
        href: admin.library(),
        icon: Images,
    },
];

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="admin.dashboard()">
                            <div
                                class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-md bg-foreground text-background"
                            >
                                <ShieldCheck class="size-5" />
                            </div>
                            <div class="ml-1 grid flex-1 text-left">
                                <span class="truncate text-sm font-semibold leading-tight">Super Admin</span>
                                <span class="truncate text-xs text-muted-foreground">Platform control</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="adminNavItems" label="Platform" />
        </SidebarContent>

        <SidebarFooter>
            <AdminNavUser />
        </SidebarFooter>
    </Sidebar>
</template>
