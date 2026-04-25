<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Image,
    LayoutGrid,
    MessageSquareHeart,
    Palette,
    PenSquare,
    Printer,
    Settings,
    Shield,
    Users,
} from 'lucide-vue-next';
import NavFlyers from '@/components/NavFlyers.vue';
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
        title: 'Message templates',
        href: '/templates',
        icon: MessageSquareHeart,
        color: 'text-emerald-500',
        bgColor: 'bg-emerald-500/10 dark:bg-emerald-500/15',
    },
];

const flyerNavItems: NavItem[] = [
    {
        title: 'My flyers',
        href: '/flyers',
        icon: Image,
        color: 'text-fuchsia-500',
        bgColor: 'bg-fuchsia-500/10 dark:bg-fuchsia-500/15',
    },
    {
        title: 'Flyer templates',
        href: '/flyer-templates',
        icon: Palette,
        color: 'text-indigo-500',
        bgColor: 'bg-indigo-500/10 dark:bg-indigo-500/15',
    },
    {
        title: 'Create flyer',
        href: '/flyers/create',
        icon: PenSquare,
        color: 'text-rose-500',
        bgColor: 'bg-rose-500/10 dark:bg-rose-500/15',
    },
    {
        title: 'Preview & print',
        href: '/preview-print',
        icon: Printer,
        color: 'text-amber-500',
        bgColor: 'bg-amber-500/10 dark:bg-amber-500/15',
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
            <NavFlyers :items="flyerNavItems" />
            <NavMain v-if="isSuperAdmin" :items="adminNavItems" label="Admin" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
