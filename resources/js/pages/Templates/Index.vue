<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Award, Braces, Cake, FileText, Heart, Pencil, Plus, Star, Trash2 } from 'lucide-vue-next';
import { ref, type Component } from 'vue';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Template } from '@/types';

defineProps<{
    templates: Template[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Templates' },
];

const typeIcon: Record<string, Component> = {
    birthday: Cake,
    wedding: Heart,
    work: Award,
    custom: Star,
};

const typeIconColor: Record<string, string> = {
    birthday: 'text-rose-500',
    wedding: 'text-violet-500',
    work: 'text-amber-500',
    custom: 'text-sky-500',
};

const channelColor: Record<string, string> = {
    whatsapp: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
    email: 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20',
    sms: 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/20',
};

function confirmDelete(template: Template) {
    if (confirm(`Delete template "${template.name}"?`)) {
        router.delete(`/templates/${template.id}`);
    }
}

// ── Add / edit template drawer ───────────────────────────────────────
const drawerOpen = ref(false);
const mode = ref<'add' | 'edit'>('add');
const editingId = ref<number | null>(null);
const contentRef = ref<HTMLTextAreaElement | null>(null);

const placeholders = [
    { token: '{{customer_name}}', label: 'Customer name' },
    { token: '{{event_name}}', label: 'Event name' },
    { token: '{{years}}', label: 'Years' },
    { token: '{{ordinal_years}}', label: 'Ordinal years' },
];

const contentPlaceholder = 'Write your message… e.g. Happy {{event_name}}, {{customer_name}}!';
const nameToken = '{{customer_name}}';

const form = useForm({
    name: '',
    type: 'birthday',
    channel: 'whatsapp',
    subject: '',
    content: '',
    is_default: false,
});

function openAdd() {
    mode.value = 'add';
    editingId.value = null;
    form.reset();
    form.clearErrors();
    drawerOpen.value = true;
}

function openEdit(template: Template) {
    mode.value = 'edit';
    editingId.value = template.id;
    form.clearErrors();
    form.defaults({
        name: template.name,
        type: template.type,
        channel: template.channel,
        subject: template.subject ?? '',
        content: template.content,
        is_default: Boolean(template.is_default),
    });
    form.reset();
    drawerOpen.value = true;
}

function insertPlaceholder(token: string) {
    const el = contentRef.value;
    if (!el) {
        form.content += token;
        return;
    }
    const start = el.selectionStart ?? form.content.length;
    const end = el.selectionEnd ?? form.content.length;
    form.content = form.content.slice(0, start) + token + form.content.slice(end);
    requestAnimationFrame(() => {
        el.focus();
        const caret = start + token.length;
        el.setSelectionRange(caret, caret);
    });
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            drawerOpen.value = false;
            form.reset();
        },
    };
    if (mode.value === 'edit' && editingId.value) {
        form.put(`/templates/${editingId.value}`, options);
    } else {
        form.post('/templates', options);
    }
}

const inputClass =
    'w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground transition focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25';
</script>

<template>
    <Head title="Templates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground">Message templates</h1>
                    <p class="text-sm text-muted-foreground">Reusable wish templates for every channel.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                    @click="openAdd"
                >
                    <Plus class="h-4 w-4" />
                    New template
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-border bg-card shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="px-5 py-3 font-medium">Template</th>
                                <th class="px-5 py-3 font-medium">Channel</th>
                                <th class="px-5 py-3 font-medium">Preview</th>
                                <th class="px-5 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="template in templates"
                                :key="template.id"
                                class="group transition hover:bg-muted/40"
                            >
                                <!-- Template -->
                                <td class="px-5 py-3 align-top">
                                    <div class="flex items-center gap-2.5">
                                        <component :is="typeIcon[template.type] ?? FileText" :class="['h-5 w-5 shrink-0', typeIconColor[template.type]]" />
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-foreground">{{ template.name }}</span>
                                                <span
                                                    v-if="template.is_default"
                                                    class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20"
                                                >
                                                    Default
                                                </span>
                                            </div>
                                            <p class="text-xs capitalize text-muted-foreground">{{ template.type }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Channel -->
                                <td class="px-5 py-3 align-top">
                                    <span :class="['inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1 ring-inset', channelColor[template.channel]]">
                                        {{ template.channel }}
                                    </span>
                                </td>

                                <!-- Preview -->
                                <td class="px-5 py-3 align-top">
                                    <p class="line-clamp-2 max-w-md text-xs text-muted-foreground">{{ template.content }}</p>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-3 align-top">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium text-primary transition hover:bg-primary/10"
                                            @click="openEdit(template)"
                                        >
                                            <Pencil class="h-3.5 w-3.5" /> Edit
                                        </button>
                                        <button
                                            class="rounded-md p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                                            aria-label="Delete template"
                                            @click="confirmDelete(template)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!templates.length">
                                <td colspan="4" class="px-5 py-16 text-center">
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                                        <FileText class="h-6 w-6 text-muted-foreground" />
                                    </div>
                                    <p class="text-sm font-medium text-foreground">No templates found</p>
                                    <p class="mt-0.5 text-sm text-muted-foreground">Create a reusable message template to send wishes faster.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── Add template drawer ── -->
        <Sheet v-model:open="drawerOpen">
            <SheetContent class="flex w-full flex-col gap-0 p-0 sm:max-w-lg">
                <SheetHeader class="border-b border-border px-6 py-4 text-left">
                    <SheetTitle>{{ mode === 'edit' ? 'Edit template' : 'New template' }}</SheetTitle>
                    <SheetDescription>Create a reusable message for a channel and occasion.</SheetDescription>
                </SheetHeader>

                <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
                    <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Name *</label>
                            <input v-model="form.name" type="text" placeholder="e.g. Birthday — warm" :class="inputClass" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Occasion</label>
                                <select v-model="form.type" :class="inputClass">
                                    <option value="birthday">Birthday</option>
                                    <option value="wedding">Anniversary</option>
                                    <option value="work">Work milestone</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Channel</label>
                                <select v-model="form.channel" :class="inputClass">
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="form.channel === 'email'">
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Subject</label>
                            <input v-model="form.subject" type="text" placeholder="Happy birthday!" :class="inputClass" />
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label class="block text-sm font-medium text-foreground">Message *</label>
                            </div>

                            <!-- Placeholder picker -->
                            <div class="mb-2 flex flex-wrap items-center gap-1.5">
                                <span class="text-xs text-muted-foreground">Insert:</span>
                                <button
                                    v-for="ph in placeholders"
                                    :key="ph.token"
                                    type="button"
                                    :title="`Insert ${ph.token}`"
                                    class="inline-flex items-center gap-1 rounded-md border border-border bg-muted/40 px-2 py-1 text-xs font-medium text-foreground transition hover:border-primary/40 hover:bg-primary/10 hover:text-primary"
                                    @click="insertPlaceholder(ph.token)"
                                >
                                    <Braces class="h-3 w-3" />
                                    {{ ph.label }}
                                </button>
                            </div>

                            <textarea
                                ref="contentRef"
                                v-model="form.content"
                                rows="6"
                                :placeholder="contentPlaceholder"
                                :class="inputClass"
                            />
                            <p v-if="form.errors.content" class="mt-1 text-xs text-destructive">{{ form.errors.content }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Placeholders like <code class="rounded bg-muted px-1 py-0.5">{{ nameToken }}</code> are replaced when the wish is sent.
                            </p>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground">
                            <input v-model="form.is_default" type="checkbox" class="h-4 w-4 rounded border-border accent-primary" />
                            Set as default for this occasion &amp; channel
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-border px-6 py-4">
                        <SheetClose
                            class="rounded-md border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                        >
                            Cancel
                        </SheetClose>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Saving…' : mode === 'edit' ? 'Save changes' : 'Create template' }}
                        </button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>
    </AppLayout>
</template>
