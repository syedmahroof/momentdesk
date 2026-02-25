<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tenants', href: '/admin/tenants' },
    { title: 'New Tenant' },
];

const form = useForm({
    name: '',
    email: '',
    phone: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
});

function submit() {
    form.post('/admin/tenants');
}
</script>

<template>
    <Head title="New Tenant" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mx-auto max-w-xl">
                <h1 class="mb-6 text-xl font-bold text-foreground">Create New Tenant</h1>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Organization</h2>
                        <div class="grid gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Organization Name *</label>
                                <input v-model="form.name" type="text" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Email *</label>
                                    <input v-model="form.email" type="email" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none" />
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-foreground">Phone</label>
                                    <input v-model="form.phone" type="tel" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Admin User</h2>
                        <div class="grid gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Admin Name *</label>
                                <input v-model="form.admin_name" type="text" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none" />
                                <p v-if="form.errors.admin_name" class="mt-1 text-xs text-destructive">{{ form.errors.admin_name }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Admin Email *</label>
                                <input v-model="form.admin_email" type="email" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none" />
                                <p v-if="form.errors.admin_email" class="mt-1 text-xs text-destructive">{{ form.errors.admin_email }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Password *</label>
                                <input v-model="form.admin_password" type="password" class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm focus:border-primary focus:outline-none" />
                                <p v-if="form.errors.admin_password" class="mt-1 text-xs text-destructive">{{ form.errors.admin_password }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <Link href="/admin/tenants" class="text-sm text-muted-foreground hover:text-foreground">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-70">
                            {{ form.processing ? 'Creating...' : 'Create Tenant' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
