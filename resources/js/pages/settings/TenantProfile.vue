<script setup lang="ts">
import { Form, Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ImageUp, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type Tenant } from '@/types';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit, update } from '@/routes/tenant-profile';
import { send } from '@/routes/verification';

type TenantWithLogos = Tenant & {
    address?: string | null;
    logo_light_url?: string | null;
    logo_dark_url?: string | null;
};

type Props = {
    tenant: TenantWithLogos;
    mustVerifyEmail?: boolean;
    status?: string;
};
const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Tenant Profile', href: edit().url }];

const page = usePage();
const user = page.props.auth.user;

const form = useForm({
    name: props.tenant.name,
    email: props.tenant.email,
    phone: props.tenant.phone ?? '',
    address: props.tenant.address ?? '',
    logo_light: null as File | null,
    logo_dark: null as File | null,
    remove_logo_light: false as boolean,
    remove_logo_dark: false as boolean,
});

const lightPreview = ref<string | null>(props.tenant.logo_light_url ?? null);
const darkPreview = ref<string | null>(props.tenant.logo_dark_url ?? null);

function onLogoChange(variant: 'light' | 'dark', event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (variant === 'light') {
        form.logo_light = file;
        form.remove_logo_light = false;
        lightPreview.value = file ? URL.createObjectURL(file) : props.tenant.logo_light_url ?? null;
    } else {
        form.logo_dark = file;
        form.remove_logo_dark = false;
        darkPreview.value = file ? URL.createObjectURL(file) : props.tenant.logo_dark_url ?? null;
    }
}

function removeLogo(variant: 'light' | 'dark') {
    if (variant === 'light') {
        form.logo_light = null;
        form.remove_logo_light = true;
        lightPreview.value = null;
    } else {
        form.logo_dark = null;
        form.remove_logo_dark = true;
        darkPreview.value = null;
    }
}

function submit() {
    form.patch(update().url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.logo_light = null;
            form.logo_dark = null;
            form.remove_logo_light = false;
            form.remove_logo_dark = false;
        },
    });
}

const statusVariant = (status: Tenant['status']) => {
    if (status === 'active') return 'default';
    if (status === 'suspended') return 'destructive';
    return 'secondary';
};

const logoSlots = computed(() => [
    { variant: 'light' as const, label: 'Light mode logo', hint: 'Shown on light backgrounds', preview: lightPreview, swatch: 'bg-white', ring: 'ring-slate-200' },
    { variant: 'dark' as const, label: 'Dark mode logo', hint: 'Shown on dark backgrounds', preview: darkPreview, swatch: 'bg-slate-900', ring: 'ring-slate-700' },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Tenant Profile" />

        <h1 class="sr-only">Tenant Profile Settings</h1>

        <SettingsLayout>
            <!-- User profile information -->
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Profile information"
                    description="Update your name and email address"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" class="mt-1 block w-full" name="name" :default-value="user.name" required autocomplete="name" placeholder="Full name" />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input id="email" type="email" class="mt-1 block w-full" name="email" :default-value="user.email" required autocomplete="username" placeholder="Email address" />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link :href="send()" as="button" class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500">
                                Click here to resend the verification email.
                            </Link>
                        </p>
                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing" data-test="update-profile-button">Save</Button>
                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <Separator />

            <!-- Tenant / organization profile -->
            <div class="flex flex-col space-y-6">
                <!-- Header row with status badge -->
                <div class="flex items-start justify-between">
                    <Heading
                        variant="small"
                        title="Tenant profile"
                        description="Update your organization's details, contact info and branding"
                    />
                    <Badge :variant="statusVariant(tenant.status)" class="capitalize">
                        {{ tenant.status }}
                    </Badge>
                </div>

                <!-- Read-only info strip -->
                <div class="rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                    <p>
                        <span class="font-medium text-foreground">Slug:</span>
                        <code class="ml-2 rounded bg-muted px-1.5 py-0.5 font-mono text-xs">{{ tenant.slug }}</code>
                    </p>
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="tenant-name">Organization name</Label>
                        <Input id="tenant-name" v-model="form.name" class="mt-1 block w-full" required autocomplete="organization" placeholder="Acme Corp" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tenant-email">Billing / contact email</Label>
                        <Input id="tenant-email" v-model="form.email" type="email" class="mt-1 block w-full" required placeholder="admin@acme.com" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tenant-phone">Phone <span class="text-muted-foreground">(optional)</span></Label>
                        <Input id="tenant-phone" v-model="form.phone" type="tel" class="mt-1 block w-full" placeholder="+1 555 000 0000" />
                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tenant-address">Address <span class="text-muted-foreground">(optional)</span></Label>
                        <textarea
                            id="tenant-address"
                            v-model="form.address"
                            rows="3"
                            placeholder="123 Market Street, Suite 400&#10;San Francisco, CA 94103"
                            class="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground transition focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25"
                        />
                        <InputError class="mt-2" :message="form.errors.address" />
                    </div>

                    <!-- Logos -->
                    <div class="grid gap-2">
                        <Label>Brand logo</Label>
                        <p class="text-sm text-muted-foreground">Upload separate logos for light and dark backgrounds. PNG, JPG, SVG or WebP up to 2&nbsp;MB.</p>
                        <div class="mt-2 grid gap-4 sm:grid-cols-2">
                            <div
                                v-for="slot in logoSlots"
                                :key="slot.variant"
                                class="rounded-lg border border-border p-4"
                            >
                                <p class="text-sm font-medium text-foreground">{{ slot.label }}</p>
                                <p class="mb-3 text-xs text-muted-foreground">{{ slot.hint }}</p>

                                <div :class="['flex h-24 items-center justify-center overflow-hidden rounded-md ring-1', slot.swatch, slot.ring]">
                                    <img v-if="slot.preview.value" :src="slot.preview.value" alt="" class="max-h-16 max-w-[80%] object-contain" />
                                    <ImageUp v-else class="h-6 w-6 text-slate-400" />
                                </div>

                                <div class="mt-3 flex items-center gap-2">
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted">
                                        <ImageUp class="h-3.5 w-3.5" />
                                        {{ slot.preview.value ? 'Replace' : 'Upload' }}
                                        <input
                                            type="file"
                                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                            class="hidden"
                                            @change="onLogoChange(slot.variant, $event)"
                                        />
                                    </label>
                                    <button
                                        v-if="slot.preview.value"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                                        @click="removeLogo(slot.variant)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                        Remove
                                    </button>
                                </div>
                                <InputError class="mt-2" :message="form.errors[`logo_${slot.variant}`]" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">Save changes</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <Separator />

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
