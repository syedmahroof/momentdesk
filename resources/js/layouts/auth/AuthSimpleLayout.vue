<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh items-center justify-center bg-[#f6f7fb] px-4 py-12">
        <div class="w-full max-w-[400px]">

            <!-- Logo -->
            <div class="mb-8 flex justify-center">
                <Link :href="home()" class="flex items-center gap-2.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-black/8">
                        <svg viewBox="0 0 40 40" class="h-6 w-6" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="lg-auth" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                                    <stop offset="0%" stop-color="#a78bfa"/>
                                    <stop offset="100%" stop-color="#4f46e5"/>
                                </linearGradient>
                            </defs>
                            <path d="M 6 32 L 6 10 Q 6 8 8 8 Q 10 8 11 10 L 20 24 L 29 10 Q 30 8 32 8 Q 34 8 34 10 L 34 32"
                                  stroke="url(#lg-auth)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M 11 18 C 11 14 16 12 20 17 C 24 12 29 14 29 18 C 29 22 25 25 20 30 C 15 25 11 22 11 18 Z"
                                  fill="url(#lg-auth)"/>
                        </svg>
                    </div>
                    <span class="text-base font-bold tracking-tight text-gray-900">
                        Moment<span class="text-violet-600">Desk</span>
                    </span>
                </Link>
            </div>

            <!-- Card -->
            <div class="rounded-2xl bg-white px-8 py-8 shadow-sm ring-1 ring-black/5">
                <!-- Heading -->
                <div class="mb-6 text-center">
                    <h1 class="text-xl font-semibold text-gray-900">{{ title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ description }}</p>
                </div>

                <slot />
            </div>

            <!-- Footer -->
            <p class="mt-6 text-center text-xs text-gray-400">
                © {{ new Date().getFullYear() }} MomentDesk · All rights reserved
            </p>
        </div>
    </div>
</template>
