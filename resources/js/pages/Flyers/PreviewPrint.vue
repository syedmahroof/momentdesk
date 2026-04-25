<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Plus } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Flyer } from '@/types';

defineProps<{
    flyers: Flyer[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Preview & Print' },
];
</script>

<template>
    <Head title="Preview & Print" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-foreground">Preview &amp; print</h1>
                    <p class="text-sm text-muted-foreground">Open a flyer to print or download PNG / JPG from the detail page.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/flyers/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Create flyer
                    </Link>
                    <Link href="/flyers" class="rounded-xl border border-border px-4 py-2.5 text-sm font-medium transition hover:bg-muted">All flyers</Link>
                </div>
            </div>

            <div v-if="!flyers.length" class="rounded-2xl border border-dashed border-border p-12 text-center text-sm text-muted-foreground">
                No flyers yet. Create one first.
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="flyer in flyers"
                    :key="flyer.id"
                    :href="`/flyers/${flyer.id}`"
                    class="group flex flex-col rounded-2xl border border-border bg-card p-5 shadow-sm transition hover:border-primary/30 hover:shadow-md"
                >
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <p class="font-medium text-foreground group-hover:text-primary">{{ flyer.title }}</p>
                        <ExternalLink class="h-4 w-4 shrink-0 text-muted-foreground opacity-0 transition group-hover:opacity-100" />
                    </div>
                    <p v-if="flyer.flyer_template" class="text-xs text-muted-foreground">{{ flyer.flyer_template.title }}</p>
                    <p class="mt-3 text-xs text-muted-foreground">{{ flyer.created_at }}</p>
                    <span class="mt-4 text-xs font-medium text-primary">Open for print / PNG / JPG →</span>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
