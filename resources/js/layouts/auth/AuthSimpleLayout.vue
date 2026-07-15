<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-svh items-center justify-center overflow-hidden bg-muted px-4 py-12">
        <!-- Ambient brand glow -->
        <div class="pointer-events-none absolute -top-40 left-1/2 h-96 w-[42rem] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl"></div>

        <div class="relative w-full max-w-[400px]">
            <!-- Logo -->
            <div class="mb-8 flex justify-center">
                <Link :href="home()" class="flex items-center gap-2.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-card shadow-sm ring-1 ring-border">
                        <svg viewBox="0 0 40 40" class="h-6 w-6 text-primary" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M 6 32 L 6 10 Q 6 8 8 8 Q 10 8 11 10 L 20 24 L 29 10 Q 30 8 32 8 Q 34 8 34 10 L 34 32"
                                  stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M 11 18 C 11 14 16 12 20 17 C 24 12 29 14 29 18 C 29 22 25 25 20 30 C 15 25 11 22 11 18 Z"
                                  fill="currentColor"/>
                        </svg>
                    </div>
                    <span class="text-base font-bold tracking-tight text-foreground">
                        Moment<span class="text-primary">Desk</span>
                    </span>
                </Link>
            </div>

            <!-- Card -->
            <div class="rounded-lg border border-border bg-card px-8 py-8 shadow-sm">
                <!-- Heading -->
                <div class="mb-6 text-center">
                    <h1 class="text-xl font-semibold text-foreground">{{ title }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
                </div>

                <slot />
            </div>

            <!-- Footer -->
            <p class="mt-6 text-center text-xs text-muted-foreground">
                © {{ new Date().getFullYear() }} MomentDesk · All rights reserved
            </p>
        </div>
    </div>
</template>
