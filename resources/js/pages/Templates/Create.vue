<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2, Sparkles } from 'lucide-vue-next';
import axios from 'axios';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Templates', href: '/templates' },
    { title: 'New Template' },
];

const form = useForm({
    name: '',
    type: 'birthday' as const,
    channel: 'whatsapp' as const,
    subject: '',
    content: '',
    is_default: false,
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
        const response = await axios.post('/ai/improve-template', {
            content: form.content,
            tone: aiTone.value,
        });
        form.content = response.data.content;
    } catch {
        //
    } finally {
        aiLoading.value = false;
    }
}

function submit() {
    form.post('/templates');
}
</script>

<template>
    <Head title="New Template" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mx-auto max-w-2xl">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-foreground">New Template</h1>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <div class="grid gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Template Name *</label>
                                <input v-model="form.name" type="text" placeholder="e.g. Birthday WhatsApp" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Event Type</label>
                                    <select v-model="form.type" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none">
                                        <option value="birthday">🎂 Birthday</option>
                                        <option value="wedding">💍 Wedding</option>
                                        <option value="work">🏆 Work</option>
                                        <option value="custom">🌟 Custom</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Channel</label>
                                    <select v-model="form.channel" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none">
                                        <option value="whatsapp">💬 WhatsApp</option>
                                        <option value="email">📧 Email</option>
                                        <option value="sms">📱 SMS</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="form.channel === 'email'">
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Email Subject</label>
                                <input v-model="form.subject" type="text" placeholder="Happy Birthday, {{customer_name}}!" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none" />
                            </div>

                            <!-- Variables -->
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Available Variables</label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="v in variables"
                                        :key="v"
                                        type="button"
                                        class="rounded-lg border border-border bg-muted px-2.5 py-1 font-mono text-xs text-foreground transition hover:border-primary hover:bg-primary/5"
                                        @click="insertVariable(v)"
                                    >
                                        {{ v }}
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label class="text-sm font-medium text-foreground">Message Content *</label>
                                    <div class="flex items-center gap-2">
                                        <select v-model="aiTone" class="rounded-lg border border-violet-200 bg-violet-50 px-2 py-1 text-xs text-violet-700 focus:outline-none">
                                            <option value="friendly">friendly</option>
                                            <option value="formal">formal</option>
                                            <option value="warm">warm</option>
                                            <option value="professional">professional</option>
                                        </select>
                                        <button type="button" :disabled="aiLoading || !form.content" class="flex items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 transition hover:bg-violet-100 disabled:opacity-40" @click="aiImprove">
                                            <Loader2 v-if="aiLoading" class="h-3 w-3 animate-spin" />
                                            <Sparkles v-else class="h-3 w-3" />
                                            AI Improve
                                        </button>
                                    </div>
                                </div>
                                <textarea v-model="form.content" rows="5" placeholder="Write your template here..." class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                                <p v-if="form.errors.content" class="mt-1 text-xs text-destructive">{{ form.errors.content }}</p>
                            </div>

                            <!-- Preview -->
                            <div v-if="form.content" class="rounded-xl border border-border bg-muted/40 p-4">
                                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Preview</p>
                                <p class="whitespace-pre-wrap text-sm text-foreground">{{ previewContent }}</p>
                            </div>

                            <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground">
                                <input v-model="form.is_default" type="checkbox" class="h-4 w-4 rounded border-border" />
                                Set as default template for this event type &amp; channel
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <Link href="/templates" class="text-sm text-muted-foreground hover:text-foreground">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-70">
                            {{ form.processing ? 'Saving...' : 'Create Template' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
