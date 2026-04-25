<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, Image } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { type NavItem } from '@/types';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

function pathnameFromPageUrl(url: string): string {
    try {
        return new URL(url, typeof window !== 'undefined' ? window.location.origin : 'http://localhost').pathname;
    } catch {
        return url.split('?')[0] ?? url;
    }
}

function isFlyerSectionPath(pathname: string): boolean {
    return (
        pathname === '/flyers' ||
        pathname.startsWith('/flyers/') ||
        pathname.startsWith('/flyer-templates') ||
        pathname.startsWith('/preview-print')
    );
}

const flyersOpen = ref(isFlyerSectionPath(pathnameFromPageUrl(page.url)));

watch(
    () => page.url,
    (url) => {
        if (isFlyerSectionPath(pathnameFromPageUrl(url))) {
            flyersOpen.value = true;
        }
    },
);

const parentActive = computed(() => {
    if (props.items.some((item) => isSubItemActive(item))) {
        return true;
    }

    return isFlyerSectionPath(pathnameFromPageUrl(page.url));
});

function isSubItemActive(item: NavItem): boolean {
    if (isCurrentUrl(item.href)) {
        return true;
    }

    const path = pathnameFromPageUrl(page.url);
    const target = toUrl(item.href);

    if (target === '/flyers') {
        return path === '/flyers' || /^\/flyers\/\d+$/.test(path);
    }

    if (target === '/flyer-templates') {
        return path === '/flyer-templates' || path.startsWith('/flyer-templates/');
    }

    return false;
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarMenu>
            <SidebarMenuItem>
                <Collapsible v-model:open="flyersOpen" class="w-full">
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton
                            type="button"
                            tooltip="Flyers"
                            :is-active="parentActive"
                            class="group/trigger w-full"
                        >
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-fuchsia-500/10 transition-transform duration-150 group-hover/trigger:scale-110 dark:bg-fuchsia-500/15"
                            >
                                <Image class="size-3.5 text-fuchsia-500" />
                            </span>
                            <span class="font-medium">Flyers</span>
                            <ChevronRight
                                :class="[
                                    'ml-auto size-4 shrink-0 text-muted-foreground transition-transform duration-200',
                                    flyersOpen && 'rotate-90',
                                ]"
                            />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem v-for="item in items" :key="item.title">
                                <SidebarMenuSubButton as-child :is-active="isSubItemActive(item)" size="md">
                                    <Link :href="item.href" class="flex min-w-0 items-center gap-2">
                                        <span
                                            v-if="item.icon"
                                            :class="[
                                                'flex h-5 w-5 shrink-0 items-center justify-center rounded-md',
                                                item.bgColor ?? 'bg-muted',
                                            ]"
                                        >
                                            <component
                                                :is="item.icon"
                                                :class="['size-3', item.color ?? 'text-muted-foreground']"
                                            />
                                        </span>
                                        <span class="truncate font-medium">{{ item.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </Collapsible>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
