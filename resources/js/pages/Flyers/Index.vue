<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Plus, Printer, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Flyer } from '@/types';

defineProps<{
    flyers: Flyer[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Flyers' },
];

function confirmDelete(flyer: Flyer): void {
    if (confirm(`Delete "${flyer.title}"?`)) {
        router.delete(`/flyers/${flyer.id}`);
    }
}
</script>

<template>
    <Head title="Flyers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-foreground">Flyers</h1>
                    <p class="text-sm text-muted-foreground">Generated designs you can preview, print, or export as PNG or JPG.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/flyers/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Create flyer
                    </Link>
                    <Link
                        href="/preview-print"
                        class="inline-flex items-center gap-2 rounded-xl border border-border px-4 py-2.5 text-sm font-medium text-foreground transition hover:bg-muted"
                    >
                        <Printer class="h-4 w-4" />
                        Preview &amp; print
                    </Link>
                </div>
            </div>

            <div v-if="!flyers.length" class="rounded-2xl border border-dashed border-border p-16 text-center">
                <h3 class="mb-2 text-base font-semibold text-foreground">No flyers yet</h3>
                <p class="mb-6 text-sm text-muted-foreground">Create a flyer from a template to see it here.</p>
                <div class="flex flex-wrap justify-center gap-3">
                    <Link href="/flyers/create" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground">Create flyer</Link>
                    <Link href="/flyer-templates/create" class="rounded-xl border border-border px-5 py-2.5 text-sm font-medium">New template</Link>
                </div>
            </div>

            <div v-else class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="flyer in flyers"
                    :key="flyer.id"
                    class="group rounded-2xl border border-border bg-card p-5 shadow-sm transition hover:shadow-md"
                >
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-foreground">{{ flyer.title }}</p>
                            <p v-if="flyer.flyer_template" class="mt-1 text-xs text-muted-foreground">{{ flyer.flyer_template.title }}</p>
                        </div>
                        <span class="shrink-0 rounded-full border border-border bg-muted px-2.5 py-1 text-[11px] uppercase tracking-wide text-muted-foreground">
                            {{ flyer.paper_size }}
                        </span>
                    </div>
                    <p class="mb-4 text-xs text-muted-foreground">{{ flyer.created_at }}</p>
                    <div class="flex flex-wrap gap-2 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
                        <Link
                            :href="`/flyers/${flyer.id}`"
                            class="inline-flex items-center gap-1 rounded-lg border border-border px-3 py-1.5 text-xs font-medium transition hover:bg-muted"
                        >
                            <Eye class="h-3.5 w-3.5" />
                            Open
                        </Link>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-destructive/30 px-3 py-1.5 text-xs text-destructive transition hover:bg-destructive/10"
                            @click="confirmDelete(flyer)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
