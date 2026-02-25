<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{ canRegister: boolean }>(),
    { canRegister: true },
);
</script>

<template>
    <Head title="MomentDesk — Never Miss a Moment">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-white font-[Inter,sans-serif] text-gray-900 antialiased">

        <!-- ───── NAV ───── -->
        <nav class="sticky top-0 z-50 border-b border-gray-100 bg-white/95 backdrop-blur-sm">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-black/8">
                        <svg viewBox="0 0 40 40" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="lg-nav" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                                    <stop offset="0%" stop-color="#a78bfa"/>
                                    <stop offset="100%" stop-color="#4f46e5"/>
                                </linearGradient>
                            </defs>
                            <path d="M 6 32 L 6 10 Q 6 8 8 8 Q 10 8 11 10 L 20 24 L 29 10 Q 30 8 32 8 Q 34 8 34 10 L 34 32"
                                  stroke="url(#lg-nav)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M 11 18 C 11 14 16 12 20 17 C 24 12 29 14 29 18 C 29 22 25 25 20 30 C 15 25 11 22 11 18 Z"
                                  fill="url(#lg-nav)"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold tracking-tight text-gray-900">
                        Moment<span class="text-violet-600">Desk</span>
                    </span>
                </Link>

                <!-- Links -->
                <div class="hidden items-center gap-7 text-sm text-gray-500 md:flex">
                    <a href="#features" class="transition hover:text-gray-900">Features</a>
                    <a href="#how" class="transition hover:text-gray-900">How it works</a>
                    <a href="#pricing" class="transition hover:text-gray-900">Pricing</a>
                </div>

                <!-- Auth -->
                <div class="flex items-center gap-3">
                    <Link v-if="$page.props.auth.user" :href="dashboard()"
                        class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-700">
                        Dashboard →
                    </Link>
                    <template v-else>
                        <Link :href="login()"
                            class="text-sm font-medium text-gray-600 transition hover:text-gray-900">
                            Sign in
                        </Link>
                        <Link v-if="canRegister" :href="register()"
                            class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-700">
                            Get started free
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- ───── HERO ───── -->
        <section class="mx-auto max-w-5xl px-6 pb-20 pt-24 text-center">
            <!-- Badge -->
            <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-violet-100 bg-violet-50 px-3.5 py-1 text-xs font-semibold text-violet-700">
                <span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>
                Customer Moments Platform
            </div>

            <!-- Headline -->
            <h1 class="mb-5 text-5xl font-extrabold leading-tight tracking-tight text-gray-900 md:text-6xl">
                Turn every customer<br/>moment into magic
            </h1>

            <p class="mx-auto mb-8 max-w-xl text-lg leading-relaxed text-gray-500">
                Track birthdays, anniversaries & milestones. Send personalised wishes via WhatsApp, email, or SMS — automatically.
            </p>

            <!-- CTA row -->
            <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                <Link v-if="!$page.props.auth.user && canRegister" :href="register()"
                    class="rounded-xl bg-violet-600 px-7 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                    Start for free
                </Link>
                <Link v-else-if="$page.props.auth.user" :href="dashboard()"
                    class="rounded-xl bg-violet-600 px-7 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                    Go to Dashboard →
                </Link>
                <a href="#how"
                    class="rounded-xl border border-gray-200 bg-white px-7 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    See how it works
                </a>
            </div>

            <p class="mt-5 text-xs text-gray-400">No credit card required · Free to get started</p>

            <!-- Stats -->
            <div class="mx-auto mt-16 grid max-w-2xl grid-cols-3 gap-px overflow-hidden rounded-2xl border border-gray-100 bg-gray-100">
                <div v-for="stat in [
                    { value: '98%',  label: 'WhatsApp open rate'      },
                    { value: '3×',   label: 'Higher customer retention' },
                    { value: '0 hrs', label: 'Manual work required'   },
                ]" :key="stat.label" class="bg-white px-6 py-7 text-center">
                    <div class="text-3xl font-extrabold text-violet-600">{{ stat.value }}</div>
                    <div class="mt-1 text-xs text-gray-400">{{ stat.label }}</div>
                </div>
            </div>
        </section>

        <!-- ───── FEATURES ───── -->
        <section id="features" class="bg-[#f8f8fb] px-6 py-20">
            <div class="mx-auto max-w-5xl">
                <div class="mb-12 text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">Everything in one place</h2>
                    <p class="mt-2 text-gray-400">All the tools you need to never miss a customer moment.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="feat in features" :key="feat.title"
                        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="mb-4 text-2xl">{{ feat.emoji }}</div>
                        <h3 class="mb-1.5 font-semibold text-gray-900">{{ feat.title }}</h3>
                        <p class="text-sm leading-relaxed text-gray-400">{{ feat.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ───── HOW IT WORKS ───── -->
        <section id="how" class="px-6 py-20">
            <div class="mx-auto max-w-3xl">
                <div class="mb-12 text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">Three steps to wow your customers</h2>
                    <p class="mt-2 text-gray-400">Up and running in under 5 minutes.</p>
                </div>

                <div class="space-y-4">
                    <div v-for="(step, i) in steps" :key="step.title"
                        class="flex gap-5 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-600 text-sm font-bold text-white">
                            {{ i + 1 }}
                        </div>
                        <div>
                            <h3 class="mb-1 font-semibold text-gray-900">{{ step.title }}</h3>
                            <p class="text-sm leading-relaxed text-gray-400">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ───── CTA ───── -->
        <section id="pricing" class="bg-[#f8f8fb] px-6 py-20">
            <div class="mx-auto max-w-2xl rounded-2xl border border-gray-100 bg-white p-12 text-center shadow-sm">
                <div class="mb-3 text-4xl">💌</div>
                <h2 class="mb-3 text-2xl font-bold text-gray-900">Start sending moments today</h2>
                <p class="mb-7 text-sm text-gray-400">
                    Free to get started. No credit card. No lock-in.<br/>Set up in under 5 minutes.
                </p>
                <Link v-if="!$page.props.auth.user && canRegister" :href="register()"
                    class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-violet-700">
                    Create your free account →
                </Link>
                <Link v-else-if="$page.props.auth.user" :href="dashboard()"
                    class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-violet-700">
                    Go to Dashboard →
                </Link>
            </div>
        </section>

        <!-- ───── FOOTER ───── -->
        <footer class="border-t border-gray-100 px-6 py-8">
            <div class="mx-auto flex max-w-5xl items-center justify-between text-xs text-gray-400">
                <span>© {{ new Date().getFullYear() }} MomentDesk · All rights reserved.</span>
                <span class="font-semibold text-gray-900">Moment<span class="text-violet-600">Desk</span></span>
            </div>
        </footer>
    </div>
</template>

<script lang="ts">
const features = [
    { emoji: '🎂', title: 'Date tracking',       desc: 'Store birthdays, anniversaries, work milestones and any custom date per customer.' },
    { emoji: '💬', title: 'Multi-channel',        desc: 'Send personalised wishes via WhatsApp, email, or SMS — the channel they use most.' },
    { emoji: '🤖', title: 'AI-written messages',  desc: 'Let AI craft heartfelt, unique messages so every wish feels personal, never templated.' },
    { emoji: '⏰', title: 'Auto reminders',       desc: 'Set reminders days ahead. Never scramble at the last minute again.' },
    { emoji: '📊', title: 'Clear dashboard',      desc: "See today's events, upcoming occasions, and delivery stats at a glance." },
    { emoji: '🏢', title: 'Multi-tenant',         desc: 'Built for agencies — manage multiple client businesses from one super-admin account.' },
];

const steps = [
    { title: 'Add your customers',       desc: 'Import or add customers with their special dates — birthdays, anniversaries, milestones.' },
    { title: 'Set up message templates', desc: 'Create personalised templates per occasion and channel. AI helps you write them in seconds.' },
    { title: 'Sit back and delight',     desc: 'MomentDesk sends wishes automatically on the right day and right channel. Customers feel remembered.' },
];
</script>
