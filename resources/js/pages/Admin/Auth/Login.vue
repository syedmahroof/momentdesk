<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/admin/login';

defineProps<{ status?: string }>();
</script>

<template>
    <Head title="Admin sign in" />

    <div class="relative flex min-h-svh items-center justify-center overflow-hidden bg-muted px-4 py-12">
        <div class="pointer-events-none absolute -top-40 left-1/2 h-96 w-[42rem] -translate-x-1/2 rounded-full bg-foreground/5 blur-3xl"></div>

        <div class="relative w-full max-w-[400px]">
            <div class="mb-8 flex justify-center">
                <div class="flex items-center gap-2.5">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-foreground text-background shadow-sm">
                        <ShieldCheck class="size-6" />
                    </div>
                    <span class="text-base font-bold tracking-tight text-foreground">Super Admin</span>
                </div>
            </div>

            <div class="rounded-lg border border-border bg-card px-8 py-8 shadow-sm">
                <div class="mb-6 text-center">
                    <h1 class="text-xl font-semibold text-foreground">Platform sign in</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Restricted to platform operators</p>
                </div>

                <div
                    v-if="status"
                    class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                >
                    {{ status }}
                </div>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="space-y-4"
                >
                    <div class="space-y-1.5">
                        <Label for="email" class="text-sm font-medium text-foreground">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="you@example.com"
                            class="h-11 rounded-lg border-input bg-background text-sm text-foreground transition placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/25"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="password" class="text-sm font-medium text-foreground">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="h-11 rounded-lg border-input bg-background text-sm text-foreground transition placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/25"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <Label for="remember" class="cursor-pointer text-sm font-normal text-muted-foreground">
                            Keep me signed in
                        </Label>
                    </div>

                    <Button
                        type="submit"
                        :tabindex="4"
                        :disabled="processing"
                        data-test="admin-login-button"
                        class="h-11 w-full rounded-lg text-sm font-semibold tracking-wide"
                    >
                        <Spinner v-if="processing" class="mr-2 size-4" />
                        Sign in
                    </Button>
                </Form>
            </div>

            <p class="mt-6 text-center text-xs text-muted-foreground">
                © {{ new Date().getFullYear() }} MomentDesk · Platform administration
            </p>
        </div>
    </div>
</template>
