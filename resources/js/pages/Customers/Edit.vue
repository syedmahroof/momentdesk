<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Customer, type CustomerDate } from '@/types';

const props = defineProps<{
    customer: Customer & { dates: CustomerDate[] };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customers', href: '/customers' },
    { title: props.customer.name, href: `/customers/${props.customer.id}` },
    { title: 'Edit' },
];

const form = useForm({
    name: props.customer.name,
    phone: props.customer.phone ?? '',
    email: props.customer.email ?? '',
    whatsapp_number: props.customer.whatsapp_number ?? '',
    notes: props.customer.notes ?? '',
    dates: props.customer.dates?.map((d) => ({
        id: d.id,
        type: d.type,
        title: d.title ?? '',
        date: d.date,
        reminder_days_before: d.reminder_days_before,
        active: d.active,
        auto_send: d.auto_send,
    })) ?? [],
});

const dateTypes = [
    { value: 'birthday', label: '🎂 Birthday' },
    { value: 'wedding', label: '💍 Wedding Anniversary' },
    { value: 'work', label: '🏆 Work Anniversary' },
    { value: 'custom', label: '🌟 Custom Event' },
];

function addDate() {
    form.dates.push({
        id: undefined,
        type: 'birthday',
        title: '',
        date: '',
        reminder_days_before: 1,
        active: true,
        auto_send: false,
    });
}

function removeDate(index: number) {
    form.dates.splice(index, 1);
}

function submit() {
    form.put(`/customers/${props.customer.id}`);
}
</script>

<template>
    <Head :title="`Edit — ${customer.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mx-auto max-w-2xl">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-foreground">Edit Customer</h1>
                </div>

                <form @submit.prevent="submit">
                    <div class="mb-6 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Customer Information</h2>
                        <div class="grid gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Full Name *</label>
                                <input v-model="form.name" type="text" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Phone</label>
                                    <input v-model="form.phone" type="tel" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Email</label>
                                    <input v-model="form.email" type="email" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none" />
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">WhatsApp Number</label>
                                <input v-model="form.whatsapp_number" type="tel" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Notes</label>
                                <textarea v-model="form.notes" rows="3" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none" />
                            </div>
                        </div>
                    </div>

                    <div class="mb-6 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Important Dates</h2>
                            <button type="button" class="flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-sm text-foreground transition hover:bg-muted" @click="addDate">
                                <Plus class="h-4 w-4" /> Add Date
                            </button>
                        </div>

                        <div v-if="!form.dates.length" class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                            No dates added.
                        </div>

                        <div class="flex flex-col gap-4">
                            <div v-for="(date, index) in form.dates" :key="index" class="relative rounded-xl border border-border p-4">
                                <button type="button" class="absolute right-3 top-3 rounded-lg p-1 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" @click="removeDate(index)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                                <div class="grid gap-3">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Event Type</label>
                                            <select v-model="date.type" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none">
                                                <option v-for="t in dateTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Date</label>
                                            <input v-model="date.date" type="date" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none" />
                                        </div>
                                    </div>
                                    <div v-if="date.type === 'custom'">
                                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Custom Title</label>
                                        <input v-model="date.title" type="text" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none" />
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Remind Before (days)</label>
                                            <input v-model.number="date.reminder_days_before" type="number" min="0" max="30" class="w-24 rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none" />
                                        </div>
                                        <div class="flex items-end gap-4">
                                            <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground">
                                                <input v-model="date.active" type="checkbox" class="h-4 w-4 rounded border-border" /> Active
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground">
                                                <input v-model="date.auto_send" type="checkbox" class="h-4 w-4 rounded border-border" /> Auto Send
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <Link :href="`/customers/${customer.id}`" class="text-sm text-muted-foreground hover:text-foreground">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-70">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
