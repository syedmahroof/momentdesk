<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
import FlyerCanvas from '@/components/flyers/FlyerCanvas.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    createDefaultFieldValues,
    placeholderOptions,
    randomElementId,
    digitalPostSizeOptions,
    getDesignerTip,
    DEFAULT_TEXT_ELEMENT,
    DEFAULT_IMAGE_ELEMENT,
    IMAGE_VALIDATION_HELP,
} from '@/components/flyers/defaults';
import { type BreadcrumbItem, type FlyerTemplate } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Flyer Templates', href: '/flyer-templates' },
    { title: 'Create' },
];

const form = useForm({
    title: '',
    category: 'custom' as FlyerTemplate['category'],
    paper_size: 'custom' as FlyerTemplate['paper_size'],
    canvas_width: digitalPostSizeOptions[0].width,
    canvas_height: digitalPostSizeOptions[0].height,
    background_type: 'color' as FlyerTemplate['background_type'],
    background_color: '#ffffff',
    background_image: null as File | null,
    remove_background_image: false,
    is_active: true,
    elements: [] as FlyerTemplate['elements'],
});

const backgroundPreviewUrl = ref<string | null>(null);
const elementImagePreviewUrls = ref<Record<string, string>>({});
const selectedPostSize = ref<(typeof digitalPostSizeOptions)[number]['value']>('instagram_post');

const previewFieldValues = computed(() => ({ ...createDefaultFieldValues('custom') }));

const templateCanvasBackgroundUrl = computed(() => {
    if (form.background_type !== 'image') {
        return null;
    }

    return backgroundPreviewUrl.value;
});

const previewAssetUrls = computed(() => {
    const urls: Record<string, string> = {};

    for (const el of form.elements) {
        if (el.type === 'image' && el.key && elementImagePreviewUrls.value[el.id]) {
            urls[el.key] = elementImagePreviewUrls.value[el.id] as string;
        }
    }

    return urls;
});

function revokeElementImagePreview(elementId: string): void {
    const url = elementImagePreviewUrls.value[elementId];

    if (url) {
        URL.revokeObjectURL(url);
    }

    const { [elementId]: _removed, ...rest } = elementImagePreviewUrls.value;
    elementImagePreviewUrls.value = rest;
}

function onElementImagePreview(elementId: string, event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    revokeElementImagePreview(elementId);

    if (file) {
        elementImagePreviewUrls.value = {
            ...elementImagePreviewUrls.value,
            [elementId]: URL.createObjectURL(file),
        };
    }
}

function applyPostSize(): void {
    const selected = digitalPostSizeOptions.find((option) => option.value === selectedPostSize.value);

    if (selected && selected.value !== 'custom') {
        form.canvas_width = selected.width;
        form.canvas_height = selected.height;
    }
}

watch(
    () => selectedPostSize.value,
    () => {
        applyPostSize();
    },
);

function addTextElement(): void {
    form.elements.push({
        ...JSON.parse(JSON.stringify(DEFAULT_TEXT_ELEMENT)),
        id: randomElementId(),
    });
}

function addImageElement(): void {
    form.elements.push({
        ...JSON.parse(JSON.stringify(DEFAULT_IMAGE_ELEMENT)),
        id: randomElementId(),
    });
}

function removeElement(index: number): void {
    const el = form.elements[index];

    if (el) {
        revokeElementImagePreview(el.id);
    }

    form.elements.splice(index, 1);
}

function moveElement(payload: { id: string; x: number; y: number }): void {
    const element = form.elements.find((item) => item.id === payload.id);

    if (!element) {
        return;
    }

    element.x = payload.x;
    element.y = payload.y;
}

function resizeElement(payload: { id: string; width: number; height: number }): void {
    const element = form.elements.find((item) => item.id === payload.id);

    if (!element) {
        return;
    }

    element.width = payload.width;
    element.height = payload.height;
}

function revokeAllElementImagePreviews(): void {
    Object.values(elementImagePreviewUrls.value).forEach((url) => URL.revokeObjectURL(url));
    elementImagePreviewUrls.value = {};
}

function onBackgroundFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.background_image = file;
    if (backgroundPreviewUrl.value) {
        URL.revokeObjectURL(backgroundPreviewUrl.value);
    }
    backgroundPreviewUrl.value = file ? URL.createObjectURL(file) : null;
}

onUnmounted(() => {
    if (backgroundPreviewUrl.value) {
        URL.revokeObjectURL(backgroundPreviewUrl.value);
    }

    revokeAllElementImagePreviews();
});

function submit(): void {
    form.post('/flyer-templates', {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Create Flyer Template" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mx-auto max-w-7xl">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-foreground">Create flyer template</h1>
                        <p class="text-sm text-muted-foreground">
                            Define background, placeholders, and layout — the live preview updates as you edit.
                        </p>
                    </div>
                    <Link
                        href="/flyer-templates"
                        class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                    >
                        Back
                    </Link>
                </div>

                <form class="grid gap-8 lg:grid-cols-2 lg:items-start" @submit.prevent="submit">
                    <div class="min-w-0 space-y-6">
                        <!-- Basics Section -->
                        <div class="overflow-hidden rounded-[24px] border border-border bg-card shadow-sm transition-all hover:shadow-md">
                            <div class="border-b border-border bg-muted/30 px-6 py-4">
                                <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary/10 text-primary">1</span>
                                    Basics
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="grid gap-6">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-semibold text-foreground">Template Title</label>
                                        <input
                                            v-model="form.title"
                                            type="text"
                                            required
                                            class="w-full rounded-lg border border-border bg-background px-4 py-3 text-sm transition-all focus:border-primary focus:ring-4 focus:ring-primary/10 focus:outline-none"
                                            placeholder="e.g. Daily Gold Rate (Modern Blue)"
                                        />
                                        <p class="mt-1.5 text-xs text-muted-foreground">Give your template a clear, descriptive name.</p>
                                        <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                                    </div>
                                    <div>
                                            <label class="mb-1.5 block text-sm font-semibold text-foreground">Post Size</label>
                                            <select
                                                v-model="selectedPostSize"
                                                class="w-full rounded-lg border border-border bg-background px-4 py-3 text-sm focus:outline-none focus:ring-4 focus:ring-primary/10 transition-all cursor-pointer"
                                            >
                                                <option v-for="opt in digitalPostSizeOptions" :key="opt.value" :value="opt.value">
                                                    {{ opt.label }}
                                                </option>
                                            </select>
                                    </div>
                                    <div v-if="selectedPostSize === 'custom'" class="grid gap-4 rounded-lg bg-muted/20 p-4 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground uppercase">Width (px)</label>
                                            <input
                                                v-model.number="form.canvas_width"
                                                type="number"
                                                min="400"
                                                max="3000"
                                                class="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground uppercase">Height (px)</label>
                                            <input
                                                v-model.number="form.canvas_height"
                                                type="number"
                                                min="400"
                                                max="3000"
                                                class="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm"
                                            />
                                        </div>
                                    </div>
                                    <label class="flex cursor-pointer items-center gap-3 text-sm text-foreground group">
                                        <div class="relative flex h-5 w-5 items-center justify-center rounded border border-border bg-background transition-all group-hover:border-primary">
                                            <input v-model="form.is_active" type="checkbox" class="peer absolute h-full w-full opacity-0 cursor-pointer" />
                                            <div class="h-3 w-3 rounded-sm bg-primary opacity-0 transition-opacity peer-checked:opacity-100"></div>
                                        </div>
                                        <span class="select-none font-medium">Activate template for use</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Background Section -->
                        <div class="overflow-hidden rounded-[24px] border border-border bg-card shadow-sm transition-all hover:shadow-md">
                            <div class="border-b border-border bg-muted/30 px-6 py-4">
                                <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary/10 text-primary">2</span>
                                    Visual Design
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="grid gap-6">
                                    <div>
                                        <label class="mb-3 block text-sm font-semibold text-foreground">Background Type</label>
                                        <div class="grid grid-cols-2 gap-4">
                                            <button
                                                type="button"
                                                class="flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-all"
                                                :class="form.background_type === 'color' ? 'border-primary bg-primary/5 text-primary' : 'border-border bg-background hover:bg-muted/50'"
                                                @click="form.background_type = 'color'"
                                            >
                                                <div class="h-8 w-8 rounded-full border border-border bg-white shadow-sm" :style="{ backgroundColor: form.background_color }"></div>
                                                <span class="text-xs font-semibold uppercase tracking-wider">Solid Color</span>
                                            </button>
                                            <button
                                                type="button"
                                                class="flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-all"
                                                :class="form.background_type === 'image' ? 'border-primary bg-primary/5 text-primary' : 'border-border bg-background hover:bg-muted/50'"
                                                @click="form.background_type = 'image'"
                                            >
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted shadow-sm">
                                                    <Plus class="h-4 w-4" />
                                                </div>
                                                <span class="text-xs font-semibold uppercase tracking-wider">Image / Art</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="form.background_type === 'color'" class="rounded-lg bg-muted/20 p-5">
                                        <label class="mb-2 block text-xs font-bold uppercase text-muted-foreground">Pick Canvas Color</label>
                                        <div class="flex items-center gap-4">
                                            <div class="relative h-12 w-12 overflow-hidden rounded-lg border border-border shadow-inner">
                                                <input v-model="form.background_color" type="color" class="absolute -inset-2 h-16 w-16 cursor-pointer" />
                                            </div>
                                            <input
                                                v-model="form.background_color"
                                                type="text"
                                                class="flex-1 rounded-lg border border-border bg-background px-4 py-2.5 font-mono text-sm uppercase"
                                            />
                                        </div>
                                    </div>

                                    <div v-else class="rounded-lg border-2 border-dashed border-border bg-muted/5 p-6 text-center">
                                        <div v-if="!backgroundPreviewUrl">
                                            <label class="relative cursor-pointer">
                                                <div class="mb-3 flex justify-center">
                                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                        <Plus class="h-6 w-6" />
                                                    </div>
                                                </div>
                                                <span class="text-sm font-semibold text-foreground">Click to upload background artwork</span>
                                                <p class="mt-1 text-xs text-muted-foreground">{{ IMAGE_VALIDATION_HELP }}</p>
                                                <input type="file" accept="image/*" class="sr-only" @change="onBackgroundFile" />
                                            </label>
                                        </div>
                                        <div v-else class="relative overflow-hidden rounded-lg border border-border">
                                            <img :src="backgroundPreviewUrl" alt="" class="max-h-48 w-full object-cover" />
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity hover:opacity-100">
                                                <label class="cursor-pointer rounded-lg bg-white px-4 py-2 text-xs font-bold text-black shadow-lg hover:bg-neutral-100">
                                                    Change Image
                                                    <input type="file" accept="image/*" class="sr-only" @change="onBackgroundFile" />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Elements Section -->
                        <div class="overflow-hidden rounded-[24px] border border-border bg-card shadow-sm transition-all hover:shadow-md">
                            <div class="border-b border-border bg-muted/30 px-6 py-4 flex items-center justify-between">
                                <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary/10 text-primary">3</span>
                                    Layout Elements
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="mb-6 flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-border bg-background py-3 text-sm font-semibold transition hover:border-primary hover:text-primary"
                                        @click="addTextElement"
                                    >
                                        <Plus class="h-4 w-4" />
                                        Add Text
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-border bg-background py-3 text-sm font-semibold transition hover:border-primary hover:text-primary"
                                        @click="addImageElement"
                                    >
                                        <Plus class="h-4 w-4" />
                                        Add Image Slot
                                    </button>
                                </div>

                                <div v-if="form.elements.length === 0" class="flex flex-col items-center justify-center rounded-3xl bg-muted/10 py-12 text-center decoration-dashed">
                                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted/20 text-muted-foreground/30">
                                        <Plus class="h-8 w-8" />
                                    </div>
                                    <p class="text-sm font-medium text-muted-foreground">No elements yet. Start adding text or image slots.</p>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="(element, index) in form.elements"
                                        :key="element.id"
                                        class="group relative rounded-lg border border-border bg-muted/20 p-5 transition-all hover:border-primary/30 hover:bg-background"
                                    >
                                        <div class="mb-4 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold text-white shadow-sm"
                                                    :class="element.type === 'image' ? 'bg-indigo-500' : 'bg-primary'"
                                                >
                                                    {{ element.type[0].toUpperCase() }}
                                                </div>
                                                <div class="min-w-0">
                                                    <input
                                                        v-model="element.label"
                                                        class="w-full bg-transparent text-sm font-bold text-foreground focus:outline-none"
                                                        placeholder="Label this element..."
                                                    />
                                                    <p v-if="element.key" class="text-[10px] text-muted-foreground">
                                                        Preview: <span class="font-mono text-primary">{{ previewFieldValues[element.key] || '---' }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="rounded-full p-2 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                                                @click="removeElement(index)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold uppercase text-muted-foreground">Placeholder / Link</label>
                                                <select v-model="element.key" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/10">
                                                    <option :value="null">None (Static Element)</option>
                                                    <option v-for="opt in placeholderOptions" :key="opt.value" :value="opt.value">
                                                        {{ opt.label }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold uppercase text-muted-foreground">Default View (Internal)</label>
                                                <input
                                                    v-model="element.content"
                                                    type="text"
                                                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/10"
                                                    placeholder="Preset text value..."
                                                />
                                            </div>
                                        </div>

                                        <div class="mt-4 grid gap-3 grid-cols-2 md:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold uppercase text-muted-foreground">X Pos</label>
                                                <input v-model.number="element.x" type="number" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs" />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold uppercase text-muted-foreground">Y Pos</label>
                                                <input v-model.number="element.y" type="number" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs" />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold uppercase text-muted-foreground">Width</label>
                                                <input v-model.number="element.width" type="number" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs" />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold uppercase text-muted-foreground">Height</label>
                                                <input v-model.number="element.height" type="number" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs" />
                                            </div>
                                        </div>

                                        <div v-if="element.type === 'text'" class="mt-4 grid gap-3 grid-cols-2 md:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4 border-t border-border pt-4">
                                            <div class="col-span-2">
                                                <label class="mb-1 block text-[10px] font-bold uppercase text-muted-foreground">Font Size (px)</label>
                                                <input
                                                    v-model.number="element.font_size"
                                                    type="number"
                                                    min="8"
                                                    max="200"
                                                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-xs"
                                                />
                                            </div>
                                            <div class="col-span-2">
                                                <label class="mb-1 block text-[10px] font-bold uppercase text-muted-foreground">Text Color</label>
                                                <div class="flex items-center gap-2">
                                                    <input v-model="element.color" type="color" class="h-8 w-8 cursor-pointer rounded border border-border" />
                                                    <input v-model="element.color" type="text" class="flex-1 rounded-lg border border-border bg-background px-2 py-2 text-[10px] font-mono uppercase" />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold uppercase text-muted-foreground">Align</label>
                                                <select v-model="element.alignment" class="w-full rounded-lg border border-border bg-background px-2 py-2 text-xs">
                                                    <option value="left">Left</option>
                                                    <option value="center">Center</option>
                                                    <option value="right">Right</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold uppercase text-muted-foreground">Weight</label>
                                                <select v-model="element.font_weight" class="w-full rounded-lg border border-border bg-background px-2 py-2 text-xs">
                                                    <option value="normal">Reg</option>
                                                    <option value="medium">Med</option>
                                                    <option value="semibold">Semi</option>
                                                    <option value="bold">Bold</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div v-if="element.type === 'image'" class="mt-4 border-t border-border pt-4">
                                            <label class="mb-2 block text-[10px] font-bold uppercase text-muted-foreground">Preview Graphic (Template Designer Only)</label>
                                            <div class="flex items-center gap-4">
                                                <label class="cursor-pointer rounded-lg border border-border bg-background px-4 py-2 text-xs font-semibold hover:bg-muted">
                                                    Upload Sample
                                                    <input type="file" accept="image/*" class="sr-only" @change="onElementImagePreview(element.id, $event)" />
                                                </label>
                                                <p class="text-[10px] text-muted-foreground italic">Sample image to help you position this slot on the canvas.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-end gap-4 py-4">
                            <Link href="/flyer-templates" class="rounded-lg border border-border px-8 py-3 text-sm font-bold transition hover:bg-muted">Discard</Link>
                            <button
                                type="submit"
                                class="rounded-lg bg-primary px-8 py-3 text-sm font-bold text-primary-foreground shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] hover:shadow-xl active:scale-[0.98] disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Publish Template
                            </button>
                        </div>
                    </div>

                    <div class="min-w-0 lg:sticky lg:top-6 lg:pb-12">
                        <div class="mb-4 flex items-center justify-between px-2">
                            <div>
                                <h2 class="text-sm font-bold uppercase tracking-wider text-foreground">Canvas Preview</h2>
                                <p class="text-[11px] text-muted-foreground">Drag labels on canvas to position elements</p>
                            </div>
                            <div class="flex gap-2">
                                <span class="rounded-lg bg-green-500/10 px-2 py-1 text-[10px] font-bold text-green-600 uppercase tracking-tighter">Live Sync</span>
                            </div>
                        </div>
                        <div class="relative overflow-visible">
                            <FlyerCanvas
                                :width="form.canvas_width"
                                :height="form.canvas_height"
                                :background-type="form.background_type"
                                :background-color="form.background_color"
                                :background-image-url="templateCanvasBackgroundUrl"
                                :elements="form.elements"
                                :field-values="previewFieldValues"
                                :asset-urls="previewAssetUrls"
                                draggable
                                @move-element="moveElement"
                                @resize-element="resizeElement"
                            />
                            <!-- Decoration -->
                            <div class="pointer-events-none absolute -bottom-12 -left-12 -z-10 h-64 w-64 rounded-full bg-primary/5 blur-3xl"></div>
                            <div class="pointer-events-none absolute -top-12 -right-12 -z-10 h-64 w-64 rounded-full bg-indigo-500/5 blur-3xl"></div>
                        </div>
                        
                        <div class="mt-8 rounded-3xl bg-neutral-900 p-6 text-white shadow-2xl">
                            <h3 class="mb-2 text-xs font-bold uppercase tracking-widest text-neutral-400">Designer Tip</h3>
                            <p class="text-sm leading-relaxed text-neutral-300">
                                {{ getDesignerTip('custom') }}
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
