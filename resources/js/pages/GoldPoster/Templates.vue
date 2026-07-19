<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Coins, LayoutTemplate, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ModalHost from '@/components/ModalHost.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useModals } from '@/composables/useModals';
import { type BreadcrumbItem } from '@/types';

interface TemplateItem { id: number; name: string; category?: string | null; type?: string | null; updated_at?: string | null }

const props = defineProps<{ templates: TemplateItem[] }>();
const modals = useModals();

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

function openTemplate(id: number) { router.visit(`/gold-poster?template=${id}`); }

async function deleteTemplate(t: TemplateItem) {
    if (!(await modals.confirm(`“${t.name}” will be permanently removed.`, { title: 'Delete template?', confirmText: 'Delete', danger: true }))) return;
    try { await axios.delete(`/gold-poster/templates/${t.id}`); items.value = items.value.filter((x) => x.id !== t.id); }
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
                <Link href="/gold-poster" class="inline-flex shrink-0 items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90">
                    <Plus class="h-4 w-4" /> New template
                </Link>
            </div>

            <!-- Empty -->
            <div v-if="!items.length" class="rounded-lg border border-dashed border-border bg-card px-6 py-16 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-muted"><Coins class="h-6 w-6 text-muted-foreground" /></div>
                <h3 class="text-base font-semibold text-foreground">No templates yet</h3>
                <p class="mx-auto mt-1 mb-6 max-w-sm text-sm text-muted-foreground">Create your first poster design and save it as a template.</p>
                <Link href="/gold-poster" class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90"><Plus class="h-4 w-4" /> New template</Link>
            </div>

            <!-- Grouped -->
            <div v-for="[cat, list] in grouped" :key="cat" class="space-y-3">
                <div class="flex items-center gap-2">
                    <component :is="metaFor(cat).icon" class="h-4 w-4 text-muted-foreground" />
                    <h2 class="text-sm font-semibold text-foreground">{{ metaFor(cat).label }}</h2>
                    <span class="text-xs text-muted-foreground">· {{ list.length }}</span>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div
                        v-for="t in list"
                        :key="t.id"
                        class="group flex flex-col rounded-lg border border-border bg-card p-4 shadow-xs transition hover:border-primary/30 hover:shadow-sm"
                    >
                        <div class="mb-3 flex items-start justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary"><component :is="metaFor(t.category).icon" class="h-4.5 w-4.5" /></span>
                                <div class="min-w-0">
                                    <button class="block truncate text-left text-sm font-medium text-foreground transition hover:text-primary" @click="openTemplate(t.id)">{{ t.name }}</button>
                                    <p class="truncate text-xs text-muted-foreground">{{ t.type || metaFor(t.category).label }} · edited {{ relativeTime(t.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-auto flex items-center justify-between border-t border-border pt-3">
                            <button class="inline-flex items-center gap-1.5 text-xs font-medium text-primary transition hover:opacity-80" @click="openTemplate(t.id)"><Pencil class="h-3.5 w-3.5" /> Open</button>
                            <button class="rounded-md p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" title="Delete" @click="deleteTemplate(t)"><Trash2 class="h-4 w-4" /></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ModalHost />
    </AppLayout>
</template>
