<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import FlyerCanvas from '@/components/flyers/FlyerCanvas.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { createDefaultElementOverrides, createDefaultFieldValues } from '@/components/flyers/defaults';
import { type BreadcrumbItem, type FlyerElement, type FlyerTemplate } from '@/types';

const props = defineProps<{
    templates: FlyerTemplate[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Flyers', href: '/flyers' },
    { title: 'Create' },
];

const firstTemplateId = props.templates[0]?.id ?? 0;

const form = useForm({
    title: 'New flyer',
    flyer_template_id: firstTemplateId,
    paper_size: 'a4' as FlyerTemplate['paper_size'],
    canvas_width: 1240,
    canvas_height: 1754,
    field_values: { ...createDefaultFieldValues() } as Record<string, string>,
    element_overrides: {} as Record<string, Partial<FlyerElement>>,
    logo_image: null as File | null,
    featured_image: null as File | null,
    background_image: null as File | null,
});

const logoObjectUrl = ref<string | null>(null);
const featuredObjectUrl = ref<string | null>(null);
const backgroundObjectUrl = ref<string | null>(null);
const canvasRef = ref<InstanceType<typeof FlyerCanvas> | null>(null);

function revokeIfNeeded(url: string | null): void {
    if (url) {
        URL.revokeObjectURL(url);
    }
}

function onLogoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.logo_image = file;
    revokeIfNeeded(logoObjectUrl.value);
    logoObjectUrl.value = file ? URL.createObjectURL(file) : null;
}

function onFeaturedChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.featured_image = file;
    revokeIfNeeded(featuredObjectUrl.value);
    featuredObjectUrl.value = file ? URL.createObjectURL(file) : null;
}

function onBackgroundChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.background_image = file;
    revokeIfNeeded(backgroundObjectUrl.value);
    backgroundObjectUrl.value = file ? URL.createObjectURL(file) : null;
}

function clearBackgroundImage(): void {
    form.background_image = null;
    revokeIfNeeded(backgroundObjectUrl.value);
    backgroundObjectUrl.value = null;
}

onUnmounted(() => {
    revokeIfNeeded(logoObjectUrl.value);
    revokeIfNeeded(featuredObjectUrl.value);
    revokeIfNeeded(backgroundObjectUrl.value);
});

const selectedTemplate = computed(() => props.templates.find((t) => t.id === form.flyer_template_id) ?? null);

watch(
    () => form.flyer_template_id,
    (id) => {
        if (!id) {
            return;
        }

        const template = props.templates.find((t) => t.id === id);

        if (!template) {
            return;
        }

        form.paper_size = template.paper_size;
        form.canvas_width = template.canvas_width;
        form.canvas_height = template.canvas_height;
        form.element_overrides = createDefaultElementOverrides(template.elements);
        form.field_values = { ...createDefaultFieldValues() };
        form.background_image = null;
        revokeIfNeeded(backgroundObjectUrl.value);
        backgroundObjectUrl.value = null;
    },
    { immediate: true },
);

const previewAssetUrls = computed(() => {
    const urls: Record<string, string> = {};

    if (logoObjectUrl.value) {
        urls.logo = logoObjectUrl.value;
    }

    if (featuredObjectUrl.value) {
        urls.featured_image = featuredObjectUrl.value;
    }

    return urls;
});

const previewBackgroundType = computed(() => {
    if (backgroundObjectUrl.value) {
        return 'image' as const;
    }

    return selectedTemplate.value?.background_type ?? 'color';
});

const previewBackgroundImageUrl = computed(() => {
    if (backgroundObjectUrl.value) {
        return backgroundObjectUrl.value;
    }

    return selectedTemplate.value?.background_image_url ?? null;
});

const previewBackgroundColor = computed(() => selectedTemplate.value?.background_color ?? '#ffffff');

function submit(): void {
    form.post('/flyers', {
        forceFormData: true,
        preserveScroll: true,
    });
}

const textKeys = ['date', 'title', 'price', 'message', 'product_name'] as const;
</script>

<template>
    <Head title="Create Flyer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-foreground">Create flyer</h1>
                    <p class="text-sm text-muted-foreground">Edit content on the left — preview updates instantly on the right.</p>
                </div>
                <Link href="/flyers" class="rounded-xl border border-border px-4 py-2 text-sm font-medium transition hover:bg-muted">Back</Link>
            </div>

            <div v-if="!templates.length" class="rounded-2xl border border-dashed border-border p-12 text-center">
                <p class="mb-4 text-sm text-muted-foreground">You need at least one active flyer template.</p>
                <Link href="/flyer-templates/create" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground">Create template</Link>
            </div>

            <form v-else class="grid gap-8 lg:grid-cols-2 lg:items-start" @submit.prevent="submit">
                <div class="space-y-6">
                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Flyer</h2>
                        <div class="grid gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Template *</label>
                                <select
                                    v-model.number="form.flyer_template_id"
                                    class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm"
                                    required
                                >
                                    <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.title }}</option>
                                </select>
                                <p v-if="form.errors.flyer_template_id" class="mt-1 text-xs text-destructive">{{ form.errors.flyer_template_id }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Flyer title *</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm"
                                />
                                <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Content</h2>
                        <div class="grid gap-3">
                            <div v-for="key in textKeys" :key="key">
                                <label class="mb-1 block text-xs font-medium capitalize text-muted-foreground">{{ key.replace('_', ' ') }}</label>
                                <textarea
                                    v-model="form.field_values[key]"
                                    :rows="key === 'message' ? 3 : 1"
                                    class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Background</h2>
                        <p class="mb-3 text-xs text-muted-foreground">
                            Optional: use your own full-page background image for this flyer. If you skip this, the template’s background (color or image) is used.
                        </p>
                        <div class="grid gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Background image</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="text-sm text-muted-foreground file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:text-primary-foreground"
                                    @change="onBackgroundChange"
                                />
                                <p v-if="form.errors.background_image" class="mt-1 text-xs text-destructive">
                                    {{ form.errors.background_image }}
                                </p>
                            </div>
                            <div v-if="backgroundObjectUrl" class="flex flex-wrap items-center gap-3">
                                <img
                                    :src="backgroundObjectUrl"
                                    alt=""
                                    class="h-20 max-w-full rounded-lg border border-border object-cover"
                                />
                                <button
                                    type="button"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                                    @click="clearBackgroundImage"
                                >
                                    Remove — use template background
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Images</h2>
                        <div class="grid gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Logo</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="text-sm text-muted-foreground file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:text-primary-foreground"
                                    @change="onLogoChange"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Featured image</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="text-sm text-muted-foreground file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:text-primary-foreground"
                                    @change="onFeaturedChange"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedTemplate" class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Layout overrides</h2>
                        <p class="mb-4 text-xs text-muted-foreground">Adjust position, size, and text styling per element (optional).</p>
                        <div class="max-h-80 space-y-4 overflow-y-auto pr-1">
                            <div
                                v-for="el in selectedTemplate.elements.filter((e) => e.type === 'text')"
                                :key="el.id"
                                class="rounded-xl border border-border bg-muted/30 p-3 dark:bg-muted/10"
                            >
                                <p class="mb-2 text-xs font-medium text-foreground">{{ el.label }}</p>
                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    <div>
                                        <label class="text-[10px] text-muted-foreground">X</label>
                                        <input
                                            v-model.number="form.element_overrides[el.id]!.x"
                                            type="number"
                                            class="w-full rounded-lg border border-border bg-background px-2 py-1 text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-muted-foreground">Y</label>
                                        <input
                                            v-model.number="form.element_overrides[el.id]!.y"
                                            type="number"
                                            class="w-full rounded-lg border border-border bg-background px-2 py-1 text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-muted-foreground">Size</label>
                                        <input
                                            v-model.number="form.element_overrides[el.id]!.font_size"
                                            type="number"
                                            class="w-full rounded-lg border border-border bg-background px-2 py-1 text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-muted-foreground">Color</label>
                                        <input v-model="form.element_overrides[el.id]!.color" type="color" class="h-8 w-full rounded border border-border" />
                                    </div>
                                    <div class="col-span-2">
                                        <label class="text-[10px] text-muted-foreground">Align</label>
                                        <select v-model="form.element_overrides[el.id]!.alignment" class="w-full rounded-lg border border-border bg-background px-2 py-1 text-xs">
                                            <option value="left">Left</option>
                                            <option value="center">Center</option>
                                            <option value="right">Right</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button
                            type="submit"
                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            Generate flyer
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-border px-5 py-2.5 text-sm font-medium transition hover:bg-muted"
                            @click="canvasRef?.download('png')"
                        >
                            Download PNG (preview)
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-border px-5 py-2.5 text-sm font-medium transition hover:bg-muted"
                            @click="canvasRef?.download('jpg')"
                        >
                            Download JPG (preview)
                        </button>
                    </div>
                </div>

                <div class="lg:sticky lg:top-6">
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-foreground">Live preview</h2>
                        <span class="text-xs text-muted-foreground">WYSIWYG canvas</span>
                    </div>
                    <FlyerCanvas
                        v-if="selectedTemplate"
                        ref="canvasRef"
                        :width="form.canvas_width"
                        :height="form.canvas_height"
                        :background-type="previewBackgroundType"
                        :background-color="previewBackgroundColor"
                        :background-image-url="previewBackgroundImageUrl"
                        :elements="selectedTemplate.elements"
                        :field-values="form.field_values"
                        :element-overrides="form.element_overrides"
                        :asset-urls="previewAssetUrls"
                    />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
