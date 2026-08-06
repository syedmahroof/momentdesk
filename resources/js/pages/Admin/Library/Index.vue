<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowDown, ArrowUp, ImageUp, LayoutTemplate, Lock, Plus, Search, Tags, Trash2, Upload } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';

interface PosterCategoryItem {
    id: number;
    name: string;
    slug: string;
    order: number;
    templates_count: number;
    is_custom: boolean;
}

interface BackgroundCategoryItem {
    id: number;
    name: string;
    slug: string;
    backgrounds_count: number;
}

interface BackgroundItem {
    id: number;
    name: string;
    url: string;
    is_active: boolean;
    category_id: number | null;
    category: string | null;
}

const props = defineProps<{
    posterCategories: PosterCategoryItem[];
    backgroundCategories: BackgroundCategoryItem[];
    backgrounds: BackgroundItem[];
    activeCategory: number | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Library' },
];

const TABS = [
    { key: 'backgrounds', label: 'Backgrounds', icon: ImageUp },
    { key: 'background-categories', label: 'Image categories', icon: Tags },
    { key: 'poster-categories', label: 'Poster categories', icon: LayoutTemplate },
] as const;
type TabKey = (typeof TABS)[number]['key'];
const tab = ref<TabKey>('backgrounds');

const backgroundTotal = computed(() => props.backgroundCategories.reduce((sum, c) => sum + c.backgrounds_count, 0));

// ── Backgrounds ──────────────────────────────────────────────────────
const search = ref('');
const visibleBackgrounds = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.backgrounds;

    return props.backgrounds.filter((b) => `${b.name} ${b.category ?? ''}`.toLowerCase().includes(q));
});

function filterBy(categoryId: number | null) {
    router.get(admin.library().url, categoryId ? { category: categoryId } : {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

const uploadOpen = ref(false);
const uploadForm = useForm<{ background_category_id: number | null; name: string; images: File[] }>({
    background_category_id: props.activeCategory ?? props.backgroundCategories[0]?.id ?? null,
    name: '',
    images: [],
});
const fileInput = ref<HTMLInputElement | null>(null);

function onFiles(event: Event) {
    uploadForm.images = Array.from((event.target as HTMLInputElement).files ?? []);
}

function submitUpload() {
    if (!uploadForm.images.length) return;
    uploadForm.post(admin.posterBackgrounds.store().url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            uploadForm.reset('name', 'images');
            if (fileInput.value) fileInput.value.value = '';
            uploadOpen.value = false;
        },
    });
}

function saveBackground(background: BackgroundItem, changes: Partial<BackgroundItem>) {
    const next = { ...background, ...changes };
    router.put(
        admin.posterBackgrounds.update(background.id).url,
        { name: next.name, background_category_id: next.category_id, is_active: next.is_active },
        { preserveScroll: true },
    );
}

function deleteBackground(background: BackgroundItem) {
    if (!confirm(`Delete "${background.name}"? The image file is removed permanently.`)) return;
    router.delete(admin.posterBackgrounds.destroy(background.id).url, { preserveScroll: true });
}

// ── Category drawer, shared by both category kinds ───────────────────
// Both lists are just named groups, so one drawer serves them: which kind is open decides
// where the form posts and whether reordering is offered.
type CategoryKind = 'background' | 'poster';
const drawerKind = ref<CategoryKind>('background');
const editingId = ref<number | null>(null);
const creating = ref(false);
const busyId = ref<number | null>(null);

const drawerOpen = computed({
    get: () => creating.value || editingId.value !== null,
    set: (value: boolean) => { if (!value) { creating.value = false; editingId.value = null; } },
});
const posterList = computed(() => props.posterCategories);
const activePoster = computed(() => posterList.value.find((c) => c.id === editingId.value) ?? null);
const activePosterIndex = computed(() => posterList.value.findIndex((c) => c.id === editingId.value));
const activeBackgroundCategory = computed(() => props.backgroundCategories.find((c) => c.id === editingId.value) ?? null);

const categoryForm = useForm({ name: '' });

function openCreate(kind: CategoryKind) {
    drawerKind.value = kind;
    editingId.value = null;
    categoryForm.defaults({ name: '' });
    categoryForm.reset();
    categoryForm.clearErrors();
    creating.value = true;
}

function openEdit(kind: CategoryKind, category: { id: number; name: string }) {
    drawerKind.value = kind;
    creating.value = false;
    categoryForm.defaults({ name: category.name });
    categoryForm.reset();
    categoryForm.clearErrors();
    editingId.value = category.id;
}

function submitCategory() {
    const poster = drawerKind.value === 'poster';
    const options = { preserveScroll: true, onSuccess: () => { creating.value = false; editingId.value = null; } };

    if (creating.value) {
        categoryForm.post(poster ? admin.posterCategories.store().url : admin.backgroundCategories.store().url, options);

        return;
    }
    if (editingId.value === null) return;
    categoryForm.put(
        poster ? admin.posterCategories.update(editingId.value).url : admin.backgroundCategories.update(editingId.value).url,
        options,
    );
}

async function movePosterCategory(category: PosterCategoryItem, direction: 'up' | 'down') {
    busyId.value = category.id;
    try {
        await axios.post(admin.posterCategories.reorder(category.id).url, { direction });
        router.reload({ only: ['posterCategories'] });
    } finally {
        busyId.value = null;
    }
}

function deletePosterCategory(category: PosterCategoryItem) {
    if (category.is_custom) return;
    if (!confirm(`Delete category "${category.name}"? Its designs will become uncategorized.`)) return;
    router.delete(admin.posterCategories.destroy(category.id).url, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
}

function deleteBackgroundCategory(category: BackgroundCategoryItem) {
    if (!confirm(`Delete category "${category.name}"? Its images stay, but become uncategorized.`)) return;
    router.delete(admin.backgroundCategories.destroy(category.id).url, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
}

// Deleting the filtered category leaves a filter pointing at nothing.
watch(() => props.backgroundCategories, (list) => {
    if (props.activeCategory && !list.some((c) => c.id === props.activeCategory)) filterBy(null);
});

const fieldClass = 'w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none';
</script>

<template>
    <Head title="Library — Admin" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-5 p-4 sm:p-6 lg:p-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-foreground">Library</h1>
                    <p class="max-w-xl text-sm text-muted-foreground">
                        Everything tenants choose from when designing a poster — background photography and the categories
                        that group both images and designs.
                    </p>
                </div>
                <button
                    v-if="tab === 'backgrounds'"
                    type="button"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90"
                    @click="uploadOpen = true"
                >
                    <Upload class="size-4" /> Upload images
                </button>
                <button
                    v-else
                    type="button"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90"
                    @click="openCreate(tab === 'poster-categories' ? 'poster' : 'background')"
                >
                    <Plus class="size-4" /> Add category
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex flex-wrap gap-1 border-b border-border">
                <button
                    v-for="t in TABS"
                    :key="t.key"
                    type="button"
                    :class="[
                        'inline-flex items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium transition sm:px-4',
                        tab === t.key ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground',
                    ]"
                    @click="tab = t.key"
                >
                    <component :is="t.icon" class="size-4" />
                    {{ t.label }}
                    <span class="text-xs text-muted-foreground">
                        {{ t.key === 'backgrounds' ? backgroundTotal : t.key === 'background-categories' ? backgroundCategories.length : posterCategories.length }}
                    </span>
                </button>
            </div>

            <!-- ── Backgrounds ── -->
            <template v-if="tab === 'backgrounds'">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative sm:w-72">
                        <Search class="pointer-events-none absolute left-3 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground" />
                        <input v-model="search" type="search" placeholder="Search backgrounds…" class="w-full rounded-lg border border-border bg-background py-2.5 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none" />
                    </div>
                    <p class="text-xs text-muted-foreground">Showing {{ visibleBackgrounds.length }} of {{ backgrounds.length }} in this view · {{ backgroundTotal }} total</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        :class="['rounded-full border px-3 py-1.5 text-xs font-medium transition', activeCategory === null ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']"
                        @click="filterBy(null)"
                    >
                        All · {{ backgroundTotal }}
                    </button>
                    <button
                        v-for="category in backgroundCategories"
                        :key="category.id"
                        type="button"
                        :class="['rounded-full border px-3 py-1.5 text-xs font-medium transition', activeCategory === category.id ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']"
                        @click="filterBy(category.id)"
                    >
                        {{ category.name }} · {{ category.backgrounds_count }}
                    </button>
                </div>

                <div v-if="visibleBackgrounds.length" class="grid grid-cols-3 gap-2.5 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-7 xl:grid-cols-8">
                    <div v-for="background in visibleBackgrounds" :key="background.id" class="overflow-hidden rounded-lg border border-border bg-card shadow-xs">
                        <div class="relative aspect-[9/16] bg-muted">
                            <img :src="background.url" :alt="background.name" class="h-full w-full object-cover" loading="lazy" />
                            <span v-if="!background.is_active" class="absolute left-1 top-1 rounded-full bg-background/90 px-1.5 py-0.5 text-[9px] font-medium text-muted-foreground">Hidden</span>
                        </div>
                        <div class="space-y-1 p-1.5">
                            <input
                                :value="background.name"
                                type="text"
                                class="w-full rounded border border-transparent bg-transparent px-1 py-0.5 text-[11px] font-medium text-foreground transition hover:border-border focus:border-primary focus:outline-none"
                                @change="saveBackground(background, { name: ($event.target as HTMLInputElement).value })"
                            />
                            <select
                                :value="background.category_id ?? ''"
                                class="w-full rounded border border-border bg-background px-1 py-0.5 text-[10px] text-muted-foreground focus:border-primary focus:outline-none"
                                @change="saveBackground(background, { category_id: ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null })"
                            >
                                <option value="">Uncategorized</option>
                                <option v-for="category in backgroundCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                            <div class="flex items-center justify-between">
                                <label class="flex cursor-pointer items-center gap-1 text-[10px] text-muted-foreground" title="Visible to tenants">
                                    <input
                                        type="checkbox"
                                        :checked="background.is_active"
                                        class="size-3 rounded border-border"
                                        @change="saveBackground(background, { is_active: ($event.target as HTMLInputElement).checked })"
                                    />
                                    Visible
                                </label>
                                <button class="rounded p-1 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" title="Delete" @click="deleteBackground(background)">
                                    <Trash2 class="size-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="search" class="rounded-xl border border-dashed border-border bg-card px-6 py-14 text-center">
                    <h3 class="text-base font-semibold text-foreground">Nothing matches “{{ search }}”</h3>
                    <button type="button" class="mt-2 text-sm font-medium text-primary hover:underline" @click="search = ''">Clear search</button>
                </div>

                <div v-else class="rounded-xl border border-dashed border-border bg-card px-6 py-14 text-center">
                    <h3 class="text-base font-semibold text-foreground">No background images yet</h3>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">Upload jewellery photography — tenants pick from these when designing a poster.</p>
                </div>
            </template>

            <!-- ── Image categories ── -->
            <template v-else-if="tab === 'background-categories'">
                <div v-if="backgroundCategories.length" class="divide-y divide-border overflow-hidden rounded-xl border border-border bg-card shadow-xs">
                    <div v-for="category in backgroundCategories" :key="category.id" class="flex items-center gap-3 px-3 py-3 transition hover:bg-muted/20 sm:px-4">
                        <button type="button" class="min-w-0 flex-1 truncate text-left text-sm font-medium text-foreground" @click="openEdit('background', category)">
                            {{ category.name }}
                        </button>
                        <button
                            type="button"
                            class="shrink-0 rounded-full bg-muted px-2.5 py-0.5 text-xs text-muted-foreground transition hover:text-foreground"
                            title="Show these images"
                            @click="tab = 'backgrounds'; filterBy(category.id)"
                        >
                            {{ category.backgrounds_count }} image{{ category.backgrounds_count === 1 ? '' : 's' }}
                        </button>
                    </div>
                </div>
                <div v-else class="rounded-xl border border-dashed border-border px-6 py-14 text-center">
                    <h3 class="font-semibold text-foreground">No image categories yet</h3>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">Group backgrounds by ornament — bangle, ring, chain — so tenants can find them quickly.</p>
                </div>
            </template>

            <!-- ── Poster categories ── -->
            <template v-else>
                <p class="-mt-2 text-sm text-muted-foreground">
                    The order here is the order tenants browse designs in. “Custom” is a system category for tenants' own designs.
                </p>
                <div v-if="posterCategories.length" class="divide-y divide-border overflow-hidden rounded-xl border border-border bg-card shadow-xs">
                    <div
                        v-for="(category, i) in posterCategories"
                        :key="category.id"
                        class="flex items-center gap-3 px-3 py-3 transition hover:bg-muted/20 sm:px-4"
                    >
                        <span class="w-5 shrink-0 text-center text-xs tabular-nums text-muted-foreground">{{ i + 1 }}</span>
                        <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-left" @click="openEdit('poster', category)">
                            <span class="truncate text-sm font-medium text-foreground">{{ category.name }}</span>
                            <Lock v-if="category.is_custom" class="size-3 shrink-0 text-muted-foreground" />
                        </button>
                        <span class="hidden shrink-0 items-center gap-1 text-xs text-muted-foreground sm:flex">
                            <LayoutTemplate class="size-3.5" /> {{ category.templates_count }}
                        </span>
                        <div class="flex shrink-0 items-center gap-0.5">
                            <button
                                class="rounded-lg p-2 text-muted-foreground transition hover:bg-muted disabled:opacity-30"
                                :disabled="i === 0 || busyId === category.id"
                                title="Move up"
                                @click="movePosterCategory(category, 'up')"
                            >
                                <ArrowUp class="size-3.5" />
                            </button>
                            <button
                                class="rounded-lg p-2 text-muted-foreground transition hover:bg-muted disabled:opacity-30"
                                :disabled="i === posterCategories.length - 1 || busyId === category.id"
                                title="Move down"
                                @click="movePosterCategory(category, 'down')"
                            >
                                <ArrowDown class="size-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-xl border border-dashed border-border px-6 py-14 text-center">
                    <h3 class="font-semibold text-foreground">No poster categories yet</h3>
                </div>
            </template>
        </div>

        <!-- Upload drawer -->
        <Sheet v-model:open="uploadOpen">
            <SheetContent side="right" class="w-full overflow-y-auto sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>Upload background images</SheetTitle>
                    <SheetDescription>JPG, PNG or WebP up to 8 MB each. Portrait 1080×1920 works best.</SheetDescription>
                </SheetHeader>
                <form class="space-y-4 px-4 pb-6" @submit.prevent="submitUpload">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Category</label>
                        <select v-model="uploadForm.background_category_id" :class="fieldClass">
                            <option :value="null">Uncategorized</option>
                            <option v-for="category in backgroundCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Name <span class="font-normal text-muted-foreground">(optional)</span></label>
                        <input v-model="uploadForm.name" type="text" placeholder="Defaults to the file name" :class="fieldClass" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Images</label>
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-foreground"
                            @change="onFiles"
                        />
                        <p v-if="uploadForm.errors.images" class="mt-1 text-xs text-destructive">{{ uploadForm.errors.images }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="uploadForm.processing || !uploadForm.images.length"
                        class="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-50"
                    >
                        <span v-if="uploadForm.progress">Uploading… {{ uploadForm.progress.percentage }}%</span>
                        <span v-else>Upload{{ uploadForm.images.length ? ` ${uploadForm.images.length} image${uploadForm.images.length === 1 ? '' : 's'}` : '' }}</span>
                    </button>
                </form>
            </SheetContent>
        </Sheet>

        <!-- Category drawer, for both kinds -->
        <Sheet v-model:open="drawerOpen">
            <SheetContent side="right" class="w-full overflow-y-auto sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>
                        {{ creating ? (drawerKind === 'poster' ? 'Add poster category' : 'Add image category') : (drawerKind === 'poster' ? activePoster?.name : activeBackgroundCategory?.name) }}
                    </SheetTitle>
                    <SheetDescription>
                        <template v-if="drawerKind === 'poster' && activePoster">
                            {{ activePoster.templates_count }} design{{ activePoster.templates_count === 1 ? '' : 's' }} · position {{ activePosterIndex + 1 }} of {{ posterCategories.length }}
                        </template>
                        <template v-else-if="drawerKind === 'background' && activeBackgroundCategory">
                            {{ activeBackgroundCategory.backgrounds_count }} image{{ activeBackgroundCategory.backgrounds_count === 1 ? '' : 's' }} in this category
                        </template>
                        <template v-else-if="drawerKind === 'poster'">Groups the default designs tenants browse.</template>
                        <template v-else>Groups background photos by ornament, like bangle or ring.</template>
                    </SheetDescription>
                </SheetHeader>

                <form class="space-y-4 px-4" @submit.prevent="submitCategory">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Name</label>
                        <input v-model="categoryForm.name" type="text" :placeholder="drawerKind === 'poster' ? 'e.g. Festival' : 'e.g. Pendant'" :class="fieldClass" />
                        <p v-if="categoryForm.errors.name" class="mt-1 text-xs text-destructive">{{ categoryForm.errors.name }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="categoryForm.processing || !categoryForm.name.trim()"
                        class="w-full rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ categoryForm.processing ? 'Saving…' : creating ? 'Add category' : 'Save changes' }}
                    </button>
                </form>

                <!-- Poster categories carry an order; image categories don't -->
                <div v-if="drawerKind === 'poster' && activePoster" class="px-4 pb-6">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Order</h3>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-border px-3 py-2 text-sm text-foreground transition hover:bg-muted disabled:opacity-40"
                            :disabled="activePosterIndex === 0 || busyId === activePoster.id"
                            @click="movePosterCategory(activePoster, 'up')"
                        >
                            <ArrowUp class="size-3.5" /> Move up
                        </button>
                        <button
                            type="button"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-border px-3 py-2 text-sm text-foreground transition hover:bg-muted disabled:opacity-40"
                            :disabled="activePosterIndex === posterCategories.length - 1 || busyId === activePoster.id"
                            @click="movePosterCategory(activePoster, 'down')"
                        >
                            <ArrowDown class="size-3.5" /> Move down
                        </button>
                    </div>

                    <template v-if="!activePoster.is_custom">
                        <button
                            type="button"
                            class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg border border-destructive/30 px-4 py-2.5 text-sm font-medium text-destructive transition hover:bg-destructive/10"
                            @click="deletePosterCategory(activePoster)"
                        >
                            <Trash2 class="size-4" /> Delete category
                        </button>
                        <p class="mt-2 text-center text-xs text-muted-foreground">Its designs stay, but become uncategorized.</p>
                    </template>
                    <p v-else class="mt-5 rounded-lg border border-border bg-muted/30 px-3 py-2.5 text-center text-xs text-muted-foreground">
                        This is a system category — it can't be deleted.
                    </p>
                </div>

                <div v-else-if="drawerKind === 'background' && activeBackgroundCategory" class="px-4 pb-6">
                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-destructive/30 px-4 py-2.5 text-sm font-medium text-destructive transition hover:bg-destructive/10"
                        @click="deleteBackgroundCategory(activeBackgroundCategory)"
                    >
                        <Trash2 class="size-4" /> Delete category
                    </button>
                    <p class="mt-2 text-center text-xs text-muted-foreground">Its images stay, but become uncategorized.</p>
                </div>
            </SheetContent>
        </Sheet>
    </AdminLayout>
</template>
