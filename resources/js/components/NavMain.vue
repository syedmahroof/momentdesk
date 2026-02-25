<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { type NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    label?: string;
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel v-if="label">{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    class="group/nav h-9"
                >
                    <Link :href="item.href">
                        <!-- Colored icon container -->
                        <span
                            v-if="item.icon"
                            :class="[
                                'flex h-6 w-6 shrink-0 items-center justify-center rounded-md transition-transform duration-150 group-hover/nav:scale-110',
                                item.bgColor ?? 'bg-muted',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :class="['size-3.5', item.color ?? 'text-muted-foreground']"
                            />
                        </span>
                        <span class="font-medium">{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
