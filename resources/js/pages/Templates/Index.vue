<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Template } from '@/types';

defineProps<{
    templates: Template[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Templates' },
];

const typeEmoji: Record<string, string> = {
    birthday: '🎂',
    wedding: '💍',
    work: '🏆',
    custom: '🌟',
};

const channelColor: Record<string, string> = {
    whatsapp: 'bg-green-50 text-green-700 border-green-200',
    email: 'bg-blue-50 text-blue-700 border-blue-200',
    sms: 'bg-purple-50 text-purple-700 border-purple-200',
};

function confirmDelete(template: Template) {
    if (confirm(`Delete template "${template.name}"?`)) {
        router.delete(`/templates/${template.id}`);
    }
}
</script>

<template>
    <Head title="Templates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-foreground">Message Templates</h1>
                    <p class="text-sm text-muted-foreground">Reusable wish templates for all channels.</p>
                </div>
                <Link
                    href="/templates/create"
                    class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    New Template
                </Link>
            </div>

            <div v-if="!templates.length" class="rounded-2xl border border-dashed border-border p-16 text-center">
                <span class="mb-3 text-4xl">📄</span>
                <h3 class="mb-1 font-semibold text-foreground">No templates yet</h3>
                <p class="mb-6 text-sm text-muted-foreground">Create reusable message templates to send faster.</p>
                <Link href="/templates/create" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground">
                    Create Template
                </Link>
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="template in templates"
                    :key="template.id"
                    class="group flex flex-col rounded-2xl border border-border bg-card p-5 shadow-sm transition hover:shadow-md"
                >
                    <div class="mb-3 flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ typeEmoji[template.type] ?? '📝' }}</span>
                            <div>
                                <p class="font-semibold text-foreground">{{ template.name }}</p>
                                <div class="mt-1 flex gap-1.5">
                                    <span :class="['rounded-full border px-2 py-0.5 text-xs font-medium', channelColor[template.channel]]">
                                        {{ template.channel }}
                                    </span>
                                    <span v-if="template.is_default" class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                                        default
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="mb-4 flex-1 line-clamp-3 text-sm text-muted-foreground">{{ template.content }}</p>

                    <div class="flex items-center justify-between">
                        <span class="text-xs capitalize text-muted-foreground">{{ template.type }}</span>
                        <div class="flex gap-2 opacity-0 transition group-hover:opacity-100">
                            <Link
                                :href="`/templates/${template.id}/edit`"
                                class="flex items-center gap-1 rounded-lg border border-border px-2.5 py-1.5 text-xs text-foreground transition hover:bg-muted"
                            >
                                <Pencil class="h-3.5 w-3.5" /> Edit
                            </Link>
                            <button
                                class="flex items-center gap-1 rounded-lg border border-destructive/30 px-2.5 py-1.5 text-xs text-destructive transition hover:bg-destructive/10"
                                @click="confirmDelete(template)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
