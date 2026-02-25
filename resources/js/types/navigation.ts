import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export type BreadcrumbItem = {
    title: string;
    href?: string;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    color?: string;   // tailwind text-* color class e.g. 'text-violet-500'
    bgColor?: string; // tailwind bg-* color class e.g. 'bg-violet-500/10'
};
