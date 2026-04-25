<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Maximize2, Minimize2 } from 'lucide-vue-next';
import { ref } from 'vue';
import FlyerCanvas from '@/components/flyers/FlyerCanvas.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Flyer } from '@/types';

const props = defineProps<{
    flyer: Flyer;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Flyers', href: '/flyers' },
    { title: props.flyer.title },
];

const canvasRef = ref<InstanceType<typeof FlyerCanvas> | null>(null);
const fullscreen = ref(false);

const snap = props.flyer.template_snapshot;

async function downloadPng(): Promise<void> {
    await canvasRef.value?.download('png');
}

async function downloadJpg(): Promise<void> {
    await canvasRef.value?.download('jpg');
}

async function printFlyer(): Promise<void> {
    await canvasRef.value?.printCanvas();
}
</script>

<template>
    <Head :title="flyer.title" />

    <AppLayout v-if="!fullscreen" :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-foreground">{{ flyer.title }}</h1>
                    <p v-if="flyer.flyer_template" class="text-sm text-muted-foreground">From template: {{ flyer.flyer_template.title }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link href="/flyers" class="rounded-xl border border-border px-4 py-2 text-sm font-medium transition hover:bg-muted">All flyers</Link>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        @click="fullscreen = true"
                    >
                        <Maximize2 class="h-4 w-4" />
                        Full screen
                    </button>
                    <button
                        type="button"
                        class="rounded-xl border border-border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        @click="downloadPng"
                    >
                        Download PNG
                    </button>
                    <button
                        type="button"
                        class="rounded-xl border border-border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        @click="downloadJpg"
                    >
                        Download JPG
                    </button>
                    <button type="button" class="rounded-xl bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:bg-primary/90" @click="printFlyer">
                        Print
                    </button>
                </div>
            </div>

            <FlyerCanvas
                ref="canvasRef"
                :width="flyer.canvas_width"
                :height="flyer.canvas_height"
                :background-type="snap.background_type"
                :background-color="snap.background_color"
                :background-image-url="snap.background_image_url ?? null"
                :elements="snap.elements"
                :field-values="flyer.field_values"
                :element-overrides="flyer.element_overrides"
                :asset-urls="flyer.asset_urls"
            />
        </div>
    </AppLayout>

    <div
        v-else
        class="fixed inset-0 z-50 flex flex-col bg-background"
    >
        <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
            <h2 class="truncate text-sm font-semibold text-foreground">{{ flyer.title }}</h2>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <button type="button" class="rounded-lg border border-border px-3 py-1.5 text-xs font-medium" @click="downloadPng">PNG</button>
                <button type="button" class="rounded-lg border border-border px-3 py-1.5 text-xs font-medium" @click="downloadJpg">JPG</button>
                <button type="button" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground" @click="printFlyer">Print</button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg border border-border px-3 py-1.5 text-xs font-medium"
                    @click="fullscreen = false"
                >
                    <Minimize2 class="h-3.5 w-3.5" />
                    Exit
                </button>
            </div>
        </div>
        <div class="flex-1 overflow-auto p-6">
            <FlyerCanvas
                ref="canvasRef"
                :width="flyer.canvas_width"
                :height="flyer.canvas_height"
                :background-type="snap.background_type"
                :background-color="snap.background_color"
                :background-image-url="snap.background_image_url ?? null"
                :elements="snap.elements"
                :field-values="flyer.field_values"
                :element-overrides="flyer.element_overrides"
                :asset-urls="flyer.asset_urls"
            />
        </div>
    </div>
</template>
