<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

defineProps<Props>();
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList>
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem :class="[index < breadcrumbs.length - 2 ? 'hidden sm:inline-flex' : '']">
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage class="max-w-[120px] truncate sm:max-w-none block" :title="item.title">
                            {{ item.title }}
                        </BreadcrumbPage>
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <Link :href="item.href ?? '#'" class="max-w-[100px] truncate sm:max-w-none inline-block align-bottom">
                                {{ item.title }}
                            </Link>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" :class="[index < breadcrumbs.length - 2 ? 'hidden sm:inline-flex' : '']" />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
