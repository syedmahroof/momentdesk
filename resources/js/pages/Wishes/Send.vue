<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Loader2, Sparkles, ExternalLink } from 'lucide-vue-next';
import axios from 'axios';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Customer, type CustomerDate, type Template } from '@/types';

const props = defineProps<{
    customer: Customer;
    date: CustomerDate;
    templates: Template[];
}>();

const page = usePage<{ flash: { success?: string; whatsapp_link?: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Customers', href: '/customers' },
    { title: props.customer.name, href: `/customers/${props.customer.id}` },
    { title: 'Send Wish' },
];

const form = useForm({
    customer_id: props.customer.id,
    customer_date_id: props.date.id,
    channel: 'whatsapp' as 'whatsapp' | 'email' | 'sms',
    template_id: null as number | null,
    message: '',
});

const aiLoading = ref(false);
const aiTone = ref<'friendly' | 'formal' | 'professional' | 'warm'>('friendly');

const channelOptions = [
    { value: 'whatsapp', label: '💬 WhatsApp', color: 'border-green-300 bg-green-50 text-green-700' },
    { value: 'email', label: '📧 Email', color: 'border-blue-300 bg-blue-50 text-blue-700' },
    { value: 'sms', label: '📱 SMS', color: 'border-purple-300 bg-purple-50 text-purple-700' },
];

const toneOptions = ['friendly', 'formal', 'professional', 'warm'] as const;

const filteredTemplates = computed(() =>
    props.templates.filter((t) => t.channel === form.channel)
);

function applyTemplate(template: Template) {
    form.template_id = template.id;
    form.message = template.content
        .replace('{{customer_name}}', props.customer.name)
        .replace('{{event_name}}', props.date.display_title)
        .replace('{{years}}', String(props.date.years))
        .replace('{{ordinal_years}}', props.date.ordinal_years);
}

async function generateWithAI() {
    aiLoading.value = true;
    try {
        const response = await axios.post('/ai/generate-wish', {
            customer_id: props.customer.id,
            customer_date_id: props.date.id,
            tone: aiTone.value,
        });
        form.message = response.data.message;
    } catch {
        // silently fail — user can retry
    } finally {
        aiLoading.value = false;
    }
}

async function improveWithAI() {
    if (!form.message) return;
    aiLoading.value = true;
    try {
        const response = await axios.post('/ai/improve-template', {
            content: form.message,
            tone: aiTone.value,
        });
        form.message = response.data.content;
    } catch {
        // silently fail
    } finally {
        aiLoading.value = false;
    }
}

function submit() {
    form.post(`/customers/${props.customer.id}/dates/${props.date.id}/send`);
}

const eventTypeEmoji: Record<string, string> = {
    birthday: '🎂',
    wedding: '💍',
    work: '🏆',
    custom: '🌟',
};
</script>

<template>
    <Head title="Send Wish" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mx-auto max-w-2xl space-y-6">
                <!-- Event info -->
                <div class="rounded-2xl border border-border bg-gradient-to-r from-primary/5 to-violet-500/5 p-5">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">{{ eventTypeEmoji[date.type] ?? '🌟' }}</span>
                        <div>
                            <p class="font-semibold text-foreground">{{ customer.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ date.display_title }}
                                <span v-if="date.years > 0"> · {{ date.ordinal_years }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Success / WhatsApp link -->
                <div v-if="page.props.flash?.success" class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <p class="text-sm font-medium text-green-700">{{ page.props.flash.success }}</p>
                    <a
                        v-if="page.props.flash?.whatsapp_link"
                        :href="page.props.flash.whatsapp_link"
                        target="_blank"
                        class="mt-2 flex items-center gap-1.5 text-sm font-medium text-green-700 underline"
                    >
                        Open in WhatsApp
                        <ExternalLink class="h-3.5 w-3.5" />
                    </a>
                </div>

                <!-- Channel selection -->
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-muted-foreground">Channel</h2>
                    <div class="flex gap-3">
                        <button
                            v-for="opt in channelOptions"
                            :key="opt.value"
                            type="button"
                            :class="[
                                'flex-1 rounded-xl border py-2.5 text-sm font-medium transition',
                                form.channel === opt.value ? opt.color + ' ring-2 ring-offset-1 ring-current' : 'border-border text-muted-foreground hover:bg-muted',
                            ]"
                            @click="form.channel = opt.value as typeof form.channel; form.template_id = null"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <!-- Templates -->
                <div v-if="filteredTemplates.length" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-muted-foreground">Templates</h2>
                    <div class="flex flex-col gap-2">
                        <button
                            v-for="template in filteredTemplates"
                            :key="template.id"
                            type="button"
                            :class="[
                                'rounded-xl border p-3 text-left text-sm transition',
                                form.template_id === template.id
                                    ? 'border-primary bg-primary/5 text-foreground'
                                    : 'border-border text-muted-foreground hover:border-primary/40 hover:bg-muted',
                            ]"
                            @click="applyTemplate(template)"
                        >
                            <p class="font-medium text-foreground">{{ template.name }}</p>
                            <p class="mt-0.5 line-clamp-2 text-xs">{{ template.content }}</p>
                        </button>
                    </div>
                </div>

                <!-- AI Generation -->
                <div class="rounded-2xl border border-violet-200 bg-violet-50/50 p-5 shadow-sm">
                    <div class="mb-3 flex items-center gap-2">
                        <Sparkles class="h-4 w-4 text-violet-600" />
                        <h2 class="text-sm font-semibold text-violet-700">AI Assistant</h2>
                    </div>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <button
                            v-for="tone in toneOptions"
                            :key="tone"
                            type="button"
                            :class="[
                                'rounded-lg px-3 py-1.5 text-xs font-medium capitalize transition',
                                aiTone === tone
                                    ? 'bg-violet-600 text-white'
                                    : 'border border-violet-200 text-violet-600 hover:bg-violet-100',
                            ]"
                            @click="aiTone = tone"
                        >
                            {{ tone }}
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            :disabled="aiLoading"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-violet-700 disabled:opacity-70"
                            @click="generateWithAI"
                        >
                            <Loader2 v-if="aiLoading" class="h-4 w-4 animate-spin" />
                            <Sparkles v-else class="h-4 w-4" />
                            Generate
                        </button>
                        <button
                            type="button"
                            :disabled="aiLoading || !form.message"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-violet-200 px-4 py-2.5 text-sm font-medium text-violet-600 transition hover:bg-violet-100 disabled:opacity-40"
                            @click="improveWithAI"
                        >
                            Improve
                        </button>
                    </div>
                </div>

                <!-- Message compose -->
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-muted-foreground">Message</h2>
                    <textarea
                        v-model="form.message"
                        rows="5"
                        placeholder="Type your wish here, or use a template or AI to generate one..."
                        class="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    <p v-if="form.errors.message" class="mt-1 text-xs text-destructive">{{ form.errors.message }}</p>
                    <p class="mt-1 text-right text-xs text-muted-foreground">{{ form.message.length }} chars</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <Link :href="`/customers/${customer.id}`" class="text-sm text-muted-foreground hover:text-foreground">Cancel</Link>
                    <button
                        type="button"
                        :disabled="form.processing || !form.message"
                        class="flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-70"
                        @click="submit"
                    >
                        {{ form.processing ? 'Sending...' : 'Send Wish' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
