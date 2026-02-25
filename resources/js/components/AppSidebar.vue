<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    LayoutGrid,
    MessageSquareHeart,
    Settings,
    Shield,
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

const page = usePage<{ auth: { user: { role: string } } }>();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        color: 'text-violet-500',
        bgColor: 'bg-violet-500/10 dark:bg-violet-500/15',
    },
    {
        title: 'Customers',
        href: '/customers',
        icon: Users,
        color: 'text-sky-500',
        bgColor: 'bg-sky-500/10 dark:bg-sky-500/15',
    },
    {
        title: 'Templates',
        href: '/templates',
        icon: MessageSquareHeart,
        color: 'text-rose-500',
        bgColor: 'bg-rose-500/10 dark:bg-rose-500/15',
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Tenants',
        href: '/admin/tenants',
        icon: Shield,
        color: 'text-amber-500',
        bgColor: 'bg-amber-500/10 dark:bg-amber-500/15',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Settings',
        href: '/settings/profile',
        icon: Settings,
        color: 'text-slate-500',
        bgColor: 'bg-slate-500/10 dark:bg-slate-500/15',
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs',
        icon: BookOpen,
        color: 'text-emerald-500',
        bgColor: 'bg-emerald-500/10 dark:bg-emerald-500/15',
    },
];

const isSuperAdmin = page.props.auth?.user?.role === 'super_admin';
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
            <NavMain v-if="isSuperAdmin" :items="adminNavItems" label="Admin" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
