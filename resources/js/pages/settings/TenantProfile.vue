<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type Tenant } from '@/types';
import { edit, update } from '@/routes/tenant-profile';

type Props = { tenant: Tenant };
const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Tenant Profile', href: edit().url },
];

const form = useForm({
    name:  props.tenant.name,
    email: props.tenant.email,
    phone: props.tenant.phone ?? '',
});

function submit() {
    form.patch(update().url);
}

const statusVariant = (status: Tenant['status']) => {
    if (status === 'active') return 'default';
    if (status === 'suspended') return 'destructive';
    return 'secondary';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Tenant Profile" />

        <h1 class="sr-only">Tenant Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <!-- Header row with status badge -->
                <div class="flex items-start justify-between">
                    <Heading
                        variant="small"
                        title="Tenant profile"
                        description="Update your organization's name, email and phone number"
                    />
                    <Badge :variant="statusVariant(tenant.status)" class="capitalize">
                        {{ tenant.status }}
                    </Badge>
                </div>

                <!-- Read-only info strip -->
                <div class="rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                    <p>
                        <span class="font-medium text-foreground">Slug:</span>
                        <code class="ml-2 rounded bg-muted px-1.5 py-0.5 text-xs font-mono">{{ tenant.slug }}</code>
                    </p>
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="tenant-name">Organization name</Label>
                        <Input
                            id="tenant-name"
                            v-model="form.name"
                            class="mt-1 block w-full"
                            required
                            autocomplete="organization"
                            placeholder="Acme Corp"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tenant-email">Billing / contact email</Label>
                        <Input
                            id="tenant-email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                            placeholder="admin@acme.com"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tenant-phone">
                            Phone
                            <span class="text-muted-foreground">(optional)</span>
                        </Label>
                        <Input
                            id="tenant-phone"
                            v-model="form.phone"
                            type="tel"
                            class="mt-1 block w-full"
                            placeholder="+1 555 000 0000"
                        />
                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            Save changes
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="form.recentlySuccessful"
                                class="text-sm text-neutral-600 dark:text-neutral-400"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
