<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, Download, ImagePlus, Search } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import ModalHost from '@/components/ModalHost.vue';
import { useModals } from '@/composables/useModals';
import AppLayout from '@/layouts/AppLayout.vue';
import { ensureFonts, loadDocumentAssets, renderDocument, type PosterAssets, type PosterDocument } from '@/pages/GoldPoster/renderer';
import { useTemplateThumbnails } from '@/pages/GoldPoster/useTemplateThumbnails';
import { type BreadcrumbItem } from '@/types';

interface TemplateSummary { id: number; name: string; type?: string | null }
interface CustomerLite { id: number; name: string; phone?: string | null; email?: string | null }

const props = defineProps<{ customer: CustomerLite; templates: TemplateSummary[] }>();
const modals = useModals();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Customers', href: '/customers' },
    { title: props.customer.name, href: `/customers/${props.customer.id}` },
    { title: 'Poster' },
];

const canvasRef = ref<HTMLCanvasElement | null>(null);
const templateId = ref<number | null>(props.templates[0]?.id ?? null);
const doc = ref<PosterDocument | null>(null);
const assets = ref<PosterAssets>({ images: {}, bg: null });
const loading = ref(false);
const fieldValues = reactive<Record<string, string>>({});

const fieldKeys = computed(() => {
    const keys: string[] = []; const seen = new Set<string>();
    for (const l of doc.value?.layers ?? []) if (l.type === 'text' && l.field && !seen.has(l.field)) { seen.add(l.field); keys.push(l.field); }
    return keys;
});
function labelFor(key: string) { return key.replace(/_/g, ' '); }

function renderPreview() { if (canvasRef.value && doc.value) renderDocument(canvasRef.value, doc.value, assets.value, { ...fieldValues }); }

async function loadTemplate(id: number | null) {
    if (!id) { doc.value = null; return; }
    loading.value = true;
    try {
        const { data } = await axios.get(`/gold-poster/templates/${id}`);
        doc.value = data.document as PosterDocument;
        assets.value = await loadDocumentAssets(doc.value);
        Object.keys(fieldValues).forEach((k) => delete fieldValues[k]);
        for (const [k, v] of Object.entries(doc.value.fields ?? {})) fieldValues[k] = v == null ? '' : String(v);
        // Fill the customer's own details.
        fieldValues['customer_name'] = props.customer.name;
        renderPreview();
    } catch { modals.alert('Could not load that template.', { title: 'Load failed' }); } finally { loading.value = false; }
}

function download() {
    const canvas = canvasRef.value; if (!canvas || !doc.value) { modals.alert('Pick a template first.'); return; }
    renderPreview();
    canvas.toBlob((blob) => {
        if (!blob) return;
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = `${props.customer.name.replace(/\s+/g, '-').toLowerCase()}-poster.png`; a.click();
        URL.revokeObjectURL(url);
    }, 'image/png');
}

watch(fieldValues, () => renderPreview(), { deep: true });
const { thumbnails, loading: thumbnailsLoading, render: renderThumbnails } = useTemplateThumbnails();

const designSearch = ref('');
const visibleTemplates = computed(() => {
    const q = designSearch.value.trim().toLowerCase();
    if (!q) return props.templates;

    return props.templates.filter((t) => `${t.name} ${t.type ?? ''}`.toLowerCase().includes(q));
});

onMounted(async () => {
    await ensureFonts();
    await loadTemplate(templateId.value);
    await renderThumbnails(props.templates.map((t) => t.id));
    document.fonts.addEventListener('loadingdone', () => renderPreview());
});

const inputClass = 'w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground transition focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25';
</script>

<template>
    <Head :title="`Poster · ${customer.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-6 lg:p-8">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-foreground">Poster for {{ customer.name }}</h1>
                <p class="text-sm text-muted-foreground">Pick a poster design, personalise the details, and download it.</p>
            </div>

            <!-- No poster templates -->
            <div v-if="!templates.length" class="rounded-lg border border-dashed border-border bg-card px-6 py-14 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-muted"><ImagePlus class="h-6 w-6 text-muted-foreground" /></div>
                <p class="text-sm font-medium text-foreground">No poster designs yet</p>
                <p class="mx-auto mt-1 mb-5 max-w-sm text-sm text-muted-foreground">Create a poster template (e.g. a birthday design) first, then come back to personalise it.</p>
                <Link href="/gold-poster" class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90">Open the editor</Link>
            </div>

            <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,420px)]">
                <!-- Controls -->
                <div class="space-y-5">
                    <section class="rounded-lg border border-border bg-card p-4 shadow-xs sm:p-5">
                        <div class="mb-3 flex items-center gap-2">
                            <h2 class="text-sm font-medium text-foreground">Design</h2>
                            <span v-if="thumbnailsLoading" class="text-xs text-muted-foreground">rendering previews…</span>
                        </div>
                        <div v-if="templates.length > 6" class="relative mb-3">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                            <input v-model="designSearch" type="search" placeholder="Search designs…" :class="[inputClass, 'pl-9']" />
                        </div>
                        <div class="grid grid-cols-4 gap-2.5 sm:grid-cols-6 lg:grid-cols-7">
                            <button
                                v-for="t in visibleTemplates"
                                :key="t.id"
                                type="button"
                                :class="['relative overflow-hidden rounded-md border-2 transition', templateId === t.id ? 'border-primary' : 'border-border hover:border-primary/50']"
                                :title="t.name"
                                @click="templateId = t.id; loadTemplate(t.id)"
                            >
                                <span class="block aspect-[9/16] w-full bg-muted">
                                    <img v-if="thumbnails[t.id]" :src="thumbnails[t.id]" :alt="t.name" class="h-full w-full object-cover" />
                                    <span v-else class="flex h-full w-full animate-pulse items-center justify-center px-1 text-center text-[10px] text-muted-foreground">{{ t.name }}</span>
                                </span>
                                <span v-if="templateId === t.id" class="absolute right-1.5 top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <Check class="h-3 w-3" />
                                </span>
                            </button>
                            <div v-if="!visibleTemplates.length" class="col-span-full py-8 text-center">
                                <p class="text-sm text-muted-foreground">No designs match “{{ designSearch }}”.</p>
                                <button type="button" class="mt-1 text-xs font-medium text-primary hover:underline" @click="designSearch = ''">Clear search</button>
                            </div>
                        </div>
                    </section>

                    <section v-if="fieldKeys.length" class="rounded-lg border border-border bg-card p-5 shadow-xs">
                        <h2 class="mb-3 text-sm font-semibold text-foreground">Details</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div v-for="key in fieldKeys" :key="key" :class="key === 'message' ? 'sm:col-span-2' : ''">
                                <label class="mb-1.5 block text-xs font-medium capitalize text-muted-foreground">{{ labelFor(key) }}</label>
                                <textarea v-if="key === 'message'" v-model="fieldValues[key]" rows="2" :class="inputClass" />
                                <input v-else v-model="fieldValues[key]" type="text" :class="inputClass" />
                            </div>
                        </div>
                    </section>

                    <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-3 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90" @click="download">
                        <Download class="h-4 w-4" /> Download poster
                    </button>
                </div>

                <!-- Preview -->
                <div class="lg:sticky lg:top-6 lg:self-start">
                    <div class="rounded-lg border border-border bg-card p-4 shadow-xs">
                        <div class="relative overflow-hidden rounded-md border border-border bg-muted flex items-center justify-center">
                            <canvas ref="canvasRef" class="block h-auto max-w-full max-h-[500px] object-contain mx-auto" />
                            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-background/60 text-sm text-muted-foreground">Loading…</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ModalHost />
    </AppLayout>
</template>
