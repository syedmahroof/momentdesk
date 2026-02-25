<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { redirect as socialRedirect } from '@/routes/social';
import { store } from '@/routes/register';
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Free forever — no credit card needed"
    >
        <Head title="Register" />

        <!-- ── Social sign-up ────────────────────────── -->
        <div class="space-y-2.5">
            <a :href="socialRedirect('google').url"
               class="flex h-11 w-full items-center justify-center gap-3 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Sign up with Google
            </a>

            <a :href="socialRedirect('facebook').url"
               class="flex h-11 w-full items-center justify-center gap-3 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/>
                </svg>
                Sign up with Facebook
            </a>
        </div>

        <!-- ── Divider ────────────────────────────────── -->
        <div class="my-5 flex items-center gap-3">
            <div class="h-px flex-1 bg-gray-100"></div>
            <span class="text-xs text-gray-400">or</span>
            <div class="h-px flex-1 bg-gray-100"></div>
        </div>

        <!-- ── Registration form ─────────────────────── -->
        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <div class="space-y-1.5">
                <Label for="name" class="text-sm font-medium text-gray-700">Full name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Jane Smith"
                    class="h-11 rounded-xl border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder:text-gray-400 transition focus:border-violet-500 focus:bg-white focus:ring-2 focus:ring-violet-500/20"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="space-y-1.5">
                <Label for="email" class="text-sm font-medium text-gray-700">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="jane@example.com"
                    class="h-11 rounded-xl border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder:text-gray-400 transition focus:border-violet-500 focus:bg-white focus:ring-2 focus:ring-violet-500/20"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="space-y-1.5">
                <Label for="password" class="text-sm font-medium text-gray-700">Password</Label>
                <Input
                    id="password"
                    type="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Min. 8 characters"
                    class="h-11 rounded-xl border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder:text-gray-400 transition focus:border-violet-500 focus:bg-white focus:ring-2 focus:ring-violet-500/20"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="space-y-1.5">
                <Label for="password_confirmation" class="text-sm font-medium text-gray-700">Confirm password</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Repeat password"
                    class="h-11 rounded-xl border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder:text-gray-400 transition focus:border-violet-500 focus:bg-white focus:ring-2 focus:ring-violet-500/20"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="h-11 w-full rounded-xl bg-violet-600 text-sm font-semibold tracking-wide hover:bg-violet-700"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                Create account
            </Button>

            <p class="pt-1 text-center text-sm text-gray-500">
                Already have an account?
                <TextLink :href="login()" class="font-semibold text-violet-600 hover:underline" :tabindex="6">
                    Sign in
                </TextLink>
            </p>
        </Form>
    </AuthBase>
</template>
