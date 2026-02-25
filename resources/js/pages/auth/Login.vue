<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { redirect as socialRedirect } from '@/routes/social';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Sign in"
        description="Enter your details to access your account"
    >
        <Head title="Log in" />

        <!-- Status message -->
        <div v-if="status"
             class="mb-5 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            {{ status }}
        </div>

        <!-- ── Social buttons ───────────────────────── -->
        <div class="space-y-2.5">
            <a :href="socialRedirect('google').url"
               class="flex h-11 w-full items-center justify-center gap-3 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continue with Google
            </a>

            <a :href="socialRedirect('facebook').url"
               class="flex h-11 w-full items-center justify-center gap-3 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/>
                </svg>
                Continue with Facebook
            </a>
        </div>

        <!-- ── Divider ───────────────────────────────── -->
        <div class="my-5 flex items-center gap-3">
            <div class="h-px flex-1 bg-gray-100"></div>
            <span class="text-xs text-gray-400">or</span>
            <div class="h-px flex-1 bg-gray-100"></div>
        </div>

        <!-- ── Email / password form ─────────────────── -->
        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <!-- Email -->
            <div class="space-y-1.5">
                <Label for="email" class="text-sm font-medium text-gray-700">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="you@example.com"
                    class="h-11 rounded-xl border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder:text-gray-400 transition focus:border-violet-500 focus:bg-white focus:ring-2 focus:ring-violet-500/20"
                />
                <InputError :message="errors.email" />
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-sm font-medium text-gray-700">Password</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        :tabindex="5"
                        class="text-xs text-violet-600 hover:underline"
                    >
                        Forgot password?
                    </TextLink>
                </div>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="h-11 rounded-xl border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder:text-gray-400 transition focus:border-violet-500 focus:bg-white focus:ring-2 focus:ring-violet-500/20"
                />
                <InputError :message="errors.password" />
            </div>

            <!-- Remember me -->
            <div class="flex items-center gap-2">
                <Checkbox id="remember" name="remember" :tabindex="3" />
                <Label for="remember" class="cursor-pointer text-sm font-normal text-gray-600">
                    Keep me signed in
                </Label>
            </div>

            <!-- Submit -->
            <Button
                type="submit"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
                class="h-11 w-full rounded-xl bg-violet-600 text-sm font-semibold tracking-wide hover:bg-violet-700"
            >
                <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                Sign in
            </Button>

            <!-- Register link -->
            <p v-if="canRegister" class="pt-1 text-center text-sm text-gray-500">
                No account yet?
                <TextLink :href="register()" :tabindex="6" class="font-semibold text-violet-600 hover:underline">
                    Sign up free
                </TextLink>
            </p>
        </Form>
    </AuthBase>
</template>
