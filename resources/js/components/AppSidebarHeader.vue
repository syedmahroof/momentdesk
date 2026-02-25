<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { appearance, updateAppearance } = useAppearance();

const appearanceCycle = {
    light:  { next: 'dark'   as const, icon: Sun,     label: 'Switch to Dark'   },
    dark:   { next: 'system' as const, icon: Moon,    label: 'Switch to System' },
    system: { next: 'light'  as const, icon: Monitor, label: 'Switch to Light'  },
} as const;

const currentMode = computed(() => appearanceCycle[appearance.value] ?? appearanceCycle['system']);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <!-- Left: sidebar trigger + breadcrumbs -->
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Right: appearance toggle -->
        <div class="ml-auto flex items-center">
            <TooltipProvider :delay-duration="0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="group h-8 w-8 cursor-pointer"
                            @click="updateAppearance(currentMode.next)"
                        >
                            <component
                                :is="currentMode.icon"
                                class="size-4 opacity-70 transition-all group-hover:opacity-100"
                            />
                            <span class="sr-only">{{ currentMode.label }}</span>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p>{{ currentMode.label }}</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>
    </header>
</template>
