<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customers', href: '/customers' },
    { title: 'Add Customer' },
];

const form = useForm({
    name: '',
    phone: '',
    email: '',
    whatsapp_number: '',
    notes: '',
    dates: [] as {
        type: string;
        title: string;
        date: string;
        reminder_days_before: number;
        active: boolean;
        auto_send: boolean;
    }[],
});

const dateTypes = [
    { value: 'birthday', label: '🎂 Birthday' },
    { value: 'wedding', label: '💍 Wedding Anniversary' },
    { value: 'work', label: '🏆 Work Anniversary' },
    { value: 'custom', label: '🌟 Custom Event' },
];

function addDate() {
    form.dates.push({
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
    form.post('/customers');
}
</script>

<template>
    <Head title="Add Customer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mx-auto max-w-2xl">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-foreground">Add Customer</h1>
                    <p class="text-sm text-muted-foreground">Add a new customer and their important dates.</p>
                </div>

                <form @submit.prevent="submit">
                    <!-- Customer Info -->
                    <div class="mb-6 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Customer Information</h2>

                        <div class="grid gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Full Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g. Alice Johnson"
                                    class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder-muted-foreground transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Phone</label>
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="+1 234 567 8900"
                                        class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Email</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="alice@example.com"
                                        class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">WhatsApp Number</label>
                                <input
                                    v-model="form.whatsapp_number"
                                    type="tel"
                                    placeholder="+1 234 567 8900"
                                    class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                                <p class="mt-1 text-xs text-muted-foreground">Include country code (e.g. +1, +44, +92)</p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Notes</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    placeholder="Any additional notes..."
                                    class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Important Dates -->
                    <div class="mb-6 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Important Dates</h2>
                            <button
                                type="button"
                                class="flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-sm text-foreground transition hover:bg-muted"
                                @click="addDate"
                            >
                                <Plus class="h-4 w-4" />
                                Add Date
                            </button>
                        </div>

                        <div v-if="!form.dates.length" class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                            No dates added yet. Click "Add Date" to add a birthday, anniversary, or custom event.
                        </div>

                        <div class="flex flex-col gap-4">
                            <div
                                v-for="(date, index) in form.dates"
                                :key="index"
                                class="relative rounded-xl border border-border p-4"
                            >
                                <button
                                    type="button"
                                    class="absolute right-3 top-3 rounded-lg p-1 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                                    @click="removeDate(index)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>

                                <div class="grid gap-3">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Event Type</label>
                                            <select
                                                v-model="date.type"
                                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none"
                                            >
                                                <option v-for="t in dateTypes" :key="t.value" :value="t.value">
                                                    {{ t.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Date</label>
                                            <input
                                                v-model="date.date"
                                                type="date"
                                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none"
                                            />
                                        </div>
                                    </div>

                                    <div v-if="date.type === 'custom'">
                                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Custom Title</label>
                                        <input
                                            v-model="date.title"
                                            type="text"
                                            placeholder="e.g. Graduation Day"
                                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none"
                                        />
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Remind Before (days)</label>
                                            <input
                                                v-model.number="date.reminder_days_before"
                                                type="number"
                                                min="0"
                                                max="30"
                                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none"
                                            />
                                        </div>
                                        <div class="flex items-end gap-4">
                                            <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground">
                                                <input v-model="date.active" type="checkbox" class="h-4 w-4 rounded border-border" />
                                                Active
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground">
                                                <input v-model="date.auto_send" type="checkbox" class="h-4 w-4 rounded border-border" />
                                                Auto Send
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between">
                        <Link href="/customers" class="text-sm text-muted-foreground hover:text-foreground">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-70"
                        >
                            {{ form.processing ? 'Saving...' : 'Add Customer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
