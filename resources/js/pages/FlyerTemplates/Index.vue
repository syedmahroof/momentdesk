<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type FlyerTemplate } from '@/types';

defineProps<{
    flyerTemplates: FlyerTemplate[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Flyer Templates' },
];

import { getCategoryLabel } from '@/components/flyers/defaults';

function confirmDelete(flyerTemplate: FlyerTemplate): void {
    if (confirm(`Delete "${flyerTemplate.title}"?`)) {
        router.delete(`/flyer-templates/${flyerTemplate.id}`);
    }
}
</script>

<template>
    <Head title="Flyer Templates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-foreground">Flyer Templates</h1>
                    <p class="text-sm text-muted-foreground">Build reusable layouts for rates, wishes, and product promos.</p>
                </div>
                <Link
                    href="/flyer-templates/create"
                    class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    New Template
                </Link>
            </div>

            <div v-if="!flyerTemplates.length" class="rounded-lg border border-dashed border-border p-16 text-center">
                <h3 class="mb-2 text-base font-semibold text-foreground">No flyer templates yet</h3>
                <p class="mb-6 text-sm text-muted-foreground">Create your first flyer layout to start generating ready-to-print designs.</p>
                <Link href="/flyer-templates/create" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground">
                    Create Template
                </Link>
            </div>

            <div v-else class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="flyerTemplate in flyerTemplates"
                    :key="flyerTemplate.id"
                    class="group rounded-lg border border-border bg-card p-5 shadow-sm transition hover:shadow-md"
                >
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-foreground">{{ flyerTemplate.title }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ getCategoryLabel(flyerTemplate.category) }}</p>
                        </div>
                        <span class="rounded-full border border-border bg-muted px-2.5 py-1 text-[11px] uppercase tracking-wide text-muted-foreground">
                            {{ flyerTemplate.paper_size }}
                        </span>
                    </div>

                    <div class="mb-4 rounded-lg border border-border/70 bg-muted/40 p-3">
                        <div class="mb-2 flex items-center justify-between text-xs text-muted-foreground">
                            <span>{{ flyerTemplate.canvas_width }} x {{ flyerTemplate.canvas_height }}</span>
                            <span>{{ flyerTemplate.elements.length }} elements</span>
                        </div>
                        <div
                            class="h-36 rounded-lg border border-border/70"
                            :style="{
                                backgroundColor: flyerTemplate.background_type === 'color' ? (flyerTemplate.background_color ?? '#ffffff') : '#ffffff',
                                backgroundImage: flyerTemplate.background_image_url ? `url(${flyerTemplate.background_image_url})` : undefined,
                                backgroundSize: 'cover',
                                backgroundPosition: 'center',
                            }"
                        />
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-xs text-muted-foreground">{{ flyerTemplate.created_at ?? 'Recently updated' }}</span>
                        <div class="flex gap-2 opacity-0 transition group-hover:opacity-100">
                            <Link
                                :href="`/flyer-templates/${flyerTemplate.id}/edit`"
                                class="flex items-center gap-1 rounded-lg border border-border px-2.5 py-1.5 text-xs text-foreground transition hover:bg-muted"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                                Edit
                            </Link>
                            <button
                                class="flex items-center gap-1 rounded-lg border border-destructive/30 px-2.5 py-1.5 text-xs text-destructive transition hover:bg-destructive/10"
                                @click="confirmDelete(flyerTemplate)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
