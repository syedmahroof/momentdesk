<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2, Sparkles } from 'lucide-vue-next';
import axios from 'axios';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Template } from '@/types';

const props = defineProps<{ template: Template }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Templates', href: '/templates' },
    { title: 'Edit Template' },
];

const form = useForm({
    name: props.template.name,
    type: props.template.type,
    channel: props.template.channel,
    subject: props.template.subject ?? '',
    content: props.template.content,
    is_default: props.template.is_default,
});

const aiLoading = ref(false);
const aiTone = ref('friendly');
const variables = ['{{customer_name}}', '{{event_name}}', '{{years}}', '{{ordinal_years}}'];

const previewContent = computed(() =>
    form.content
        .replace('{{customer_name}}', 'Alice Johnson')
        .replace('{{event_name}}', 'Birthday')
        .replace('{{years}}', '32')
        .replace('{{ordinal_years}}', '32nd')
);

function insertVariable(v: string) {
    form.content += v;
}

async function aiImprove() {
    if (!form.content) return;
    aiLoading.value = true;
    try {
        const response = await axios.post('/ai/improve-template', { content: form.content, tone: aiTone.value });
        form.content = response.data.content;
    } catch { /* */ } finally { aiLoading.value = false; }
}

function submit() {
    form.put(`/templates/${props.template.id}`);
}
</script>

<template>
    <Head title="Edit Template" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mx-auto max-w-2xl">
                <h1 class="mb-6 text-xl font-bold text-foreground">Edit Template</h1>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <div class="grid gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Template Name *</label>
                                <input v-model="form.name" type="text" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Event Type</label>
                                    <select v-model="form.type" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:outline-none">
                                        <option value="birthday">🎂 Birthday</option>
                                        <option value="wedding">💍 Wedding</option>
                                        <option value="work">🏆 Work</option>
                                        <option value="custom">🌟 Custom</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Channel</label>
                                    <select v-model="form.channel" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:outline-none">
                                        <option value="whatsapp">💬 WhatsApp</option>
                                        <option value="email">📧 Email</option>
                                        <option value="sms">📱 SMS</option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="form.channel === 'email'">
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Subject</label>
                                <input v-model="form.subject" type="text" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:outline-none" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Variables</label>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="v in variables" :key="v" type="button" class="rounded-lg border border-border bg-muted px-2.5 py-1 font-mono text-xs transition hover:border-primary hover:bg-primary/5" @click="insertVariable(v)">{{ v }}</button>
                                </div>
                            </div>
                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label class="text-sm font-medium text-foreground">Content *</label>
                                    <button type="button" :disabled="aiLoading" class="flex items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 transition hover:bg-violet-100 disabled:opacity-40" @click="aiImprove">
                                        <Loader2 v-if="aiLoading" class="h-3 w-3 animate-spin" /><Sparkles v-else class="h-3 w-3" /> AI Improve
                                    </button>
                                </div>
                                <textarea v-model="form.content" rows="5" class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                                <p v-if="form.errors.content" class="mt-1 text-xs text-destructive">{{ form.errors.content }}</p>
                            </div>
                            <div v-if="form.content" class="rounded-xl border border-border bg-muted/40 p-4">
                                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Preview</p>
                                <p class="whitespace-pre-wrap text-sm text-foreground">{{ previewContent }}</p>
                            </div>
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground">
                                <input v-model="form.is_default" type="checkbox" class="h-4 w-4 rounded" />
                                Set as default for this type &amp; channel
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <Link href="/templates" class="text-sm text-muted-foreground hover:text-foreground">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-70">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
