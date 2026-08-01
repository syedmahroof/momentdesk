<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlarmClock,
    ArrowRight,
    Building2,
    CalendarHeart,
    LayoutDashboard,
    MessagesSquare,
    Sparkles,
} from 'lucide-vue-next';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{ canRegister: boolean }>(),
    { canRegister: true },
);

const features = [
    { icon: CalendarHeart, title: 'Date tracking', desc: 'Store birthdays, anniversaries, work milestones and any custom date per customer.' },
    { icon: MessagesSquare, title: 'Multi-channel', desc: 'Send personalised wishes via WhatsApp or email — the channel they use most.' },
    { icon: Sparkles, title: 'AI-written messages', desc: 'Let AI craft heartfelt, unique messages so every wish feels personal, never templated.' },
    { icon: AlarmClock, title: 'Auto reminders', desc: 'Set reminders days ahead. Never scramble at the last minute again.' },
    { icon: LayoutDashboard, title: 'Clear dashboard', desc: "See today's events, upcoming occasions, and delivery stats at a glance." },
    { icon: Building2, title: 'Multi-tenant', desc: 'Built for agencies — manage multiple client businesses from one super-admin account.' },
];

const steps = [
    { title: 'Add your customers', desc: 'Import or add customers with their special dates — birthdays, anniversaries, milestones.' },
    { title: 'Write the message', desc: 'Compose a wish for the occasion, or let AI draft and polish it in seconds.' },
    { title: 'Send and delight', desc: 'Send on the right day through WhatsApp or email. Customers feel remembered.' },
];

const stats = [
    { value: '98%', label: 'WhatsApp open rate' },
    { value: '3×', label: 'Higher customer retention' },
    { value: '0 hrs', label: 'Manual work required' },
];
</script>

<template>
    <Head title="MomentDesk — Never Miss a Moment">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-white font-[Inter,sans-serif] text-slate-900 antialiased">
        <!-- ───── NAV ───── -->
        <nav class="sticky top-0 z-50 border-b border-slate-100 bg-white/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                <Link href="/" class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                        <svg viewBox="0 0 40 40" class="h-5 w-5 text-blue-600" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M 6 32 L 6 10 Q 6 8 8 8 Q 10 8 11 10 L 20 24 L 29 10 Q 30 8 32 8 Q 34 8 34 10 L 34 32"
                                  stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M 11 18 C 11 14 16 12 20 17 C 24 12 29 14 29 18 C 29 22 25 25 20 30 C 15 25 11 22 11 18 Z"
                                  fill="currentColor"/>
                        </svg>
                    </div>
                    <span class="text-sm font-bold tracking-tight text-slate-900">
                        Moment<span class="text-blue-600">Desk</span>
                    </span>
                </Link>

                <div class="hidden items-center gap-8 text-sm font-medium text-slate-500 md:flex">
                    <a href="#features" class="transition hover:text-slate-900">Features</a>
                    <a href="#how" class="transition hover:text-slate-900">How it works</a>
                    <a href="#pricing" class="transition hover:text-slate-900">Pricing</a>
                </div>

                <div class="flex items-center gap-3">
                    <Link v-if="$page.props.auth.user" :href="dashboard()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Dashboard
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                    <template v-else>
                        <Link :href="login()"
                            class="text-sm font-medium text-slate-600 transition hover:text-slate-900">
                            Sign in
                        </Link>
                        <Link v-if="canRegister" :href="register()"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            Get started free
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- ───── HERO ───── -->
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 -top-32 mx-auto h-96 max-w-4xl rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="relative mx-auto max-w-5xl px-6 pb-24 pt-24 text-center">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3.5 py-1 text-xs font-semibold text-blue-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    Customer Moments Platform
                </div>

                <h1 class="mb-6 text-5xl font-extrabold leading-[1.05] tracking-tight text-slate-900 md:text-6xl">
                    Turn every customer<br/>
                    moment into <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">magic</span>
                </h1>

                <p class="mx-auto mb-9 max-w-xl text-lg leading-relaxed text-slate-500">
                    Track birthdays, anniversaries &amp; milestones. Send personalised wishes via WhatsApp or email — automatically.
                </p>

                <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link v-if="!$page.props.auth.user && canRegister" :href="register()"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-7 py-3 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700">
                        Start for free
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link v-else-if="$page.props.auth.user" :href="dashboard()"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-7 py-3 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700">
                        Go to Dashboard
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                    <a href="#how"
                        class="rounded-xl border border-slate-200 bg-white px-7 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        See how it works
                    </a>
                </div>

                <p class="mt-6 text-xs text-slate-400">No credit card required · Free to get started</p>

                <!-- Stats -->
                <div class="mx-auto mt-16 grid max-w-2xl grid-cols-3 gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200">
                    <div v-for="stat in stats" :key="stat.label" class="bg-white px-6 py-7 text-center">
                        <div class="text-3xl font-extrabold text-blue-600">{{ stat.value }}</div>
                        <div class="mt-1 text-xs font-medium text-slate-400">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ───── FEATURES ───── -->
        <section id="features" class="bg-slate-50 px-6 py-24">
            <div class="mx-auto max-w-6xl">
                <div class="mb-14 text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Everything in one place</h2>
                    <p class="mt-3 text-slate-500">All the tools you need to never miss a customer moment.</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="feat in features" :key="feat.title"
                        class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
                        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                            <component :is="feat.icon" class="h-5 w-5" />
                        </div>
                        <h3 class="mb-1.5 font-semibold text-slate-900">{{ feat.title }}</h3>
                        <p class="text-sm leading-relaxed text-slate-500">{{ feat.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ───── HOW IT WORKS ───── -->
        <section id="how" class="px-6 py-24">
            <div class="mx-auto max-w-3xl">
                <div class="mb-14 text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Three steps to wow your customers</h2>
                    <p class="mt-3 text-slate-500">Up and running in under 5 minutes.</p>
                </div>

                <div class="space-y-4">
                    <div v-for="(step, i) in steps" :key="step.title"
                        class="flex gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm shadow-blue-600/20">
                            {{ i + 1 }}
                        </div>
                        <div>
                            <h3 class="mb-1 font-semibold text-slate-900">{{ step.title }}</h3>
                            <p class="text-sm leading-relaxed text-slate-500">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ───── CTA ───── -->
        <section id="pricing" class="bg-slate-50 px-6 py-24">
            <div class="relative mx-auto max-w-2xl overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-700 p-12 text-center shadow-xl shadow-blue-600/20">
                <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
                <h2 class="mb-3 text-2xl font-bold text-white">Start sending moments today</h2>
                <p class="mb-8 text-sm text-blue-100">
                    Free to get started. No credit card. No lock-in.<br/>Set up in under 5 minutes.
                </p>
                <Link v-if="!$page.props.auth.user && canRegister" :href="register()"
                    class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50">
                    Create your free account
                    <ArrowRight class="h-4 w-4" />
                </Link>
                <Link v-else-if="$page.props.auth.user" :href="dashboard()"
                    class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50">
                    Go to Dashboard
                    <ArrowRight class="h-4 w-4" />
                </Link>
            </div>
        </section>

        <!-- ───── FOOTER ───── -->
        <footer class="border-t border-slate-100 px-6 py-8">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 text-xs text-slate-400 sm:flex-row">
                <span>© {{ new Date().getFullYear() }} MomentDesk · All rights reserved.</span>
                <span class="font-semibold text-slate-900">Moment<span class="text-blue-600">Desk</span></span>
            </div>
        </footer>
    </div>
</template>
