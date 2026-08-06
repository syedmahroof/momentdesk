<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Coins, LayoutTemplate, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import ModalHost from '@/components/ModalHost.vue';
import { useModals } from '@/composables/useModals';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { useTemplateThumbnails } from './useTemplateThumbnails';

interface TemplateItem { id: number; name: string; category?: string | null; type?: string | null; updated_at?: string | null; poster_category?: string | null; is_global?: boolean }

const props = defineProps<{ templates: TemplateItem[] }>();
const modals = useModals();

const { thumbnails, render: renderThumbnails, forget } = useTemplateThumbnails();
onMounted(() => renderThumbnails(props.templates.map((t) => t.id)));

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Templates', href: '/gold-poster/templates' }];

const items = ref<TemplateItem[]>([...props.templates]);

const categoryMeta: Record<string, { label: string; icon: typeof Coins }> = {
    gold_price: { label: 'Gold price', icon: Coins },
    poster: { label: 'Posters', icon: LayoutTemplate },
};
function metaFor(cat?: string | null) { return categoryMeta[cat === 'poster' ? 'poster' : 'gold_price']; }

const grouped = computed(() => {
    const groups: Record<string, TemplateItem[]> = {};
    for (const t of items.value) { const c = t.category === 'poster' ? 'poster' : 'gold_price'; (groups[c] ??= []).push(t); }
    const order = ['gold_price', 'poster'];
    return Object.entries(groups).sort((a, b) => (order.indexOf(a[0]) + 1 || 99) - (order.indexOf(b[0]) + 1 || 99));
});

const activeCategory = ref<string>(grouped.value[0]?.[0] ?? 'gold_price');
const activeList = computed(() => grouped.value.find(([cat]) => cat === activeCategory.value)?.[1] ?? []);

const DESIGN_ORDER = ['Modern', 'Minimal', 'Advanced'];
const designTabs = computed(() => {
    const groups: Record<string, TemplateItem[]> = {};
    for (const t of activeList.value) { const key = t.poster_category || 'Custom'; (groups[key] ??= []).push(t); }
    return Object.entries(groups).sort((a, b) => {
        if (a[0] === 'Custom') return 1;
        if (b[0] === 'Custom') return -1;
        const ai = DESIGN_ORDER.indexOf(a[0]);
        const bi = DESIGN_ORDER.indexOf(b[0]);
        return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi);
    });
});
const activeDesign = ref<string | null>(null);
watch(activeCategory, () => { activeDesign.value = null; });

const search = ref('');
const visibleList = computed(() => {
    const base = activeCategory.value === 'gold_price' && activeDesign.value
        ? designTabs.value.find(([name]) => name === activeDesign.value)?.[1] ?? []
        : activeList.value;
    const q = search.value.trim().toLowerCase();
    if (!q) return base;

    return base.filter((t) => `${t.name} ${t.type ?? ''} ${t.poster_category ?? ''}`.toLowerCase().includes(q));
});

function openTemplate(id: number) { router.visit(`/gold-poster?template=${id}`); }

async function deleteTemplate(t: TemplateItem) {
    if (!(await modals.confirm(`“${t.name}” will be permanently removed.`, { title: 'Delete template?', confirmText: 'Delete', danger: true }))) return;
    try { await axios.delete(`/gold-poster/templates/${t.id}`); items.value = items.value.filter((x) => x.id !== t.id); forget(t.id); }
    catch { modals.alert('Could not delete that template.', { title: 'Delete failed' }); }
}

function relativeTime(iso?: string | null): string {
    if (!iso) return '';
    const diff = (Date.now() - new Date(iso).getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return new Date(iso).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head title="Templates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground">Templates</h1>
                    <p class="text-sm text-muted-foreground">{{ items.length }} saved template{{ items.length === 1 ? '' : 's' }} across all categories.</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 sm:w-64 sm:flex-none">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                        <input v-model="search" type="search" placeholder="Search designs…" class="w-full rounded-md border border-input bg-background py-2 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25" />
                    </div>
                    <Link href="/gold-poster" class="inline-flex shrink-0 items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90">
                        <Plus class="h-4 w-4" /> <span class="hidden sm:inline">New template</span>
                    </Link>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="!items.length" class="rounded-lg border border-dashed border-border bg-card px-6 py-16 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-muted"><Coins class="h-6 w-6 text-muted-foreground" /></div>
                <h3 class="text-base font-semibold text-foreground">No templates yet</h3>
                <p class="mx-auto mt-1 mb-6 max-w-sm text-sm text-muted-foreground">Create your first poster design and save it as a template.</p>
                <Link href="/gold-poster" class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90"><Plus class="h-4 w-4" /> New template</Link>
            </div>

            <template v-else>
                <!-- Category tabs -->
                <div class="flex flex-wrap gap-1 border-b border-border">
                    <button
                        v-for="[cat, list] in grouped"
                        :key="cat"
                        type="button"
                        :class="[
                            'inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition',
                            activeCategory === cat ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground',
                        ]"
                        @click="activeCategory = cat"
                    >
                        <component :is="metaFor(cat).icon" class="h-4 w-4" />
                        {{ metaFor(cat).label }}
                        <span class="text-xs text-muted-foreground">{{ list.length }}</span>
                    </button>
                </div>

                <!-- Design sub-tabs (gold price only) -->
                <div v-if="activeCategory === 'gold_price' && designTabs.length > 1" class="-mt-2 flex flex-wrap gap-2">
                    <button
                        type="button"
                        :class="[
                            'rounded-full px-3 py-1.5 text-xs font-medium transition',
                            activeDesign === null ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/70',
                        ]"
                        @click="activeDesign = null"
                    >
                        All <span class="opacity-70">· {{ activeList.length }}</span>
                    </button>
                    <button
                        v-for="[designName, designList] in designTabs"
                        :key="designName"
                        type="button"
                        :class="[
                            'rounded-full px-3 py-1.5 text-xs font-medium transition',
                            activeDesign === designName ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/70',
                        ]"
                        @click="activeDesign = designName"
                    >
                        {{ designName }} <span class="opacity-70">· {{ designList.length }}</span>
                    </button>
                </div>

                <!-- Cards -->
                <div v-if="visibleList.length" class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-7 xl:grid-cols-8">
                    <div
                        v-for="t in visibleList"
                        :key="t.id"
                        class="group flex flex-col overflow-hidden rounded-lg border border-border bg-card shadow-xs transition hover:border-primary/30 hover:shadow-sm"
                    >
                        <button class="relative block aspect-[9/16] w-full bg-muted" :title="t.name" @click="openTemplate(t.id)">
                            <img v-if="thumbnails[t.id]" :src="thumbnails[t.id]" :alt="t.name" class="h-full w-full object-cover" />
                            <span v-else class="flex h-full w-full animate-pulse items-center justify-center bg-muted">
                                <component :is="metaFor(t.category).icon" class="h-6 w-6 text-muted-foreground" />
                            </span>
                            <span v-if="t.is_global" class="absolute left-2 top-2 rounded-full bg-background/90 px-2 py-0.5 text-[10px] font-medium text-muted-foreground">Default</span>
                        </button>
                        <div class="flex flex-1 flex-col p-2">
                            <button class="block truncate text-left text-xs font-medium text-foreground transition hover:text-primary" @click="openTemplate(t.id)">{{ t.name }}</button>
                            <p class="truncate text-[10px] text-muted-foreground">{{ relativeTime(t.updated_at) }}</p>
                            <div class="mt-1.5 flex items-center justify-between">
                                <button class="inline-flex items-center gap-1 text-[10px] font-medium text-primary transition hover:opacity-80" @click="openTemplate(t.id)"><Pencil class="h-3 w-3" /> Open</button>
                                <button v-if="!t.is_global" class="rounded p-1 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" title="Delete" @click="deleteTemplate(t)"><Trash2 class="h-3.5 w-3.5" /></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="py-12 text-center">
                    <p class="text-sm text-muted-foreground">{{ search ? `No designs match “${search}”.` : 'No templates in this category yet.' }}</p>
                    <button v-if="search" type="button" class="mt-2 text-xs font-medium text-primary hover:underline" @click="search = ''">Clear search</button>
                </div>
            </template>
        </div>

        <ModalHost />
    </AppLayout>
</template>
