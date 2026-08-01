<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    LayoutTemplate,
    LineChart,
    RefreshCw,
    Settings,
    UserPlus,
    Users,
} from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';
import { dashboard } from '@/routes';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Leads',
        href: '/leads',
        icon: UserPlus,
    },
    {
        title: 'Customers',
        href: '/customers',
        icon: Users,
    },
    {
        title: 'Templates',
        href: '/gold-poster/templates',
        icon: LayoutTemplate,
    },
    {
        title: 'Update gold rate',
        href: '/gold-poster/update',
        icon: RefreshCw,
    },
    {
        title: 'Rate history',
        href: '/gold-poster/rate-history',
        icon: LineChart,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Settings',
        href: '/settings/profile',
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Main" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
