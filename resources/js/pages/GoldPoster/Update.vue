<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { BadgeCheck, Check, Download, ImageOff, Layers, MessageSquareText, Minus, Move, RefreshCw, Search, Trash2, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import ModalHost from '@/components/ModalHost.vue';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useModals } from '@/composables/useModals';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { DEFAULT_ICON_COLOR, ICON_COLORS, POSTER_ICON_GROUPS, posterIconDataUrl } from './posterIcons';
import { ensureFonts, layerSize, loadDocumentAssets, loadImage, renderDocument, type Layer, type PosterAssets, type PosterDocument, type StatusConfig } from './renderer';
import { buildStatusMessage, DEFAULT_STATUS_ICON_SET, STATUS_ICON_SETS } from './statusIcons';
import { useTemplateThumbnails } from './useTemplateThumbnails';

const modals = useModals();

interface TemplateSummary { id: number; name: string; type?: string | null }
interface RateData { date: string; price_22k_1g: number; price_22k_8g: number; price_18k_1g: number }
interface BackgroundOption { id: number; name: string; url: string; category: string | null }
interface TenantLike { name?: string | null; email?: string | null; phone?: string | null; address?: string | null; logo_light_url?: string | null; logo_dark_url?: string | null }

const props = defineProps<{
    tenant: TenantLike | null;
    templates: TemplateSummary[];
    latestRate: RateData | null;
    rates: RateData[];
    backgrounds?: BackgroundOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Update gold rate', href: '/gold-poster/update' }];

const canvasRef = ref<HTMLCanvasElement | null>(null);
const templateId = ref<number | null>(props.templates[0]?.id ?? null);
const doc = ref<PosterDocument | null>(null);
const assets = ref<PosterAssets>({ images: {}, bg: null });
const loading = ref(false);
const busy = ref(false);
const rateTrend = ref<{ direction: string; diff: number; has_previous: boolean } | null>(null);
const statusOverride = ref<string | null>(null);

function todayIso() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

const rate = reactive({
    // Always default to today (local date); prices only fill if a rate exists for the selected date.
    date: todayIso(),
    p22k1g: '',
    p22k8g: '',
    p18k1g: '',
});

// Fill the price fields from the saved rate for the given date, or clear them if none.
function applyRateForDate(date: string) {
    const found = (props.rates ?? []).find((r) => r.date === date);
    rate.p22k1g = found ? String(found.price_22k_1g) : '';
    rate.p22k8g = found ? String(found.price_22k_8g) : '';
    rate.p18k1g = found ? String(found.price_18k_1g) : '';
}

function num(v: string) { return Number(String(v).replace(/[^0-9]/g, '')) || 0; }
function money(v: string) { return `₹${num(v).toLocaleString('en-IN')}`; }
function displayDate(iso: string) {
    try { return new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); } catch { return iso; }
}
const DEFAULT_STATUS_CONFIG: StatusConfig = { mode: 'text', increaseText: 'Gold price up ₹{diff}/gram', decreaseText: 'Gold price down ₹{diff}/gram', icon: DEFAULT_STATUS_ICON_SET, order: 'icon-first' };

function statusMessage(t: { direction: string; diff: number; has_previous: boolean }): string {
    if (!t.has_previous || (t.direction !== 'up' && t.direction !== 'down')) return '';
    const config = doc.value?.statusConfig ?? DEFAULT_STATUS_CONFIG;

    return buildStatusMessage(config, t.direction as 'up' | 'down', t.diff);
}

// ── Price-change message ─────────────────────────────────────────────
// The design ships with its own wording, but the phrasing is the kind of thing that gets
// reworded per posting, so it is editable here. Edits apply to this poster only — the
// saved design, which other people may share, is never touched.
const statusConfig = reactive<StatusConfig>({ ...DEFAULT_STATUS_CONFIG });
/** Until the wording is touched, the design's own text is left exactly as it was. */
const statusEdited = ref(false);
const messageSheetOpen = ref(false);

/** The most recent saved rate before the selected date, used to preview the change locally. */
const previousRate = computed(() => (props.rates ?? [])
    .filter((r) => r.date < rate.date)
    .sort((a, b) => (a.date < b.date ? 1 : -1))[0] ?? null);

/** The server's verdict once a rate is saved; before that, the same sum done locally. */
const previewTrend = computed(() => {
    if (rateTrend.value) return rateTrend.value;
    const previous = previousRate.value;
    const current = num(rate.p22k1g);
    if (!previous || !current) return null;
    const diff = current - previous.price_22k_1g;

    return { direction: diff > 0 ? 'up' : diff < 0 ? 'down' : 'flat', diff: Math.abs(diff), has_previous: true };
});
/** True while the message on the canvas is illustrative rather than a real comparison. */
const statusIsSample = computed(() => !previewTrend.value);

function currentStatusText(): string {
    const t = previewTrend.value;
    if (t) {
        if (t.direction !== 'up' && t.direction !== 'down') return '';

        return buildStatusMessage(statusConfig, t.direction as 'up' | 'down', t.diff);
    }

    // Nothing to compare against yet — show the wording with a stand-in amount.
    return buildStatusMessage(statusConfig, 'up', 50);
}
const statusPreviewText = computed(() => {
    const t = previewTrend.value;
    if (t && t.direction !== 'up' && t.direction !== 'down') return 'Nothing is shown when the price is unchanged.';

    return currentStatusText();
});

function applyStatusConfig() {
    statusEdited.value = true;
    if (doc.value) doc.value.statusConfig = { ...statusConfig };
    statusOverride.value = currentStatusText();
    renderPreview();
}

/** Puts the design's own wording back. */
async function resetStatusConfig() {
    statusEdited.value = false;
    const base = templateId.value ? await fetchDocument(templateId.value) : null;
    Object.assign(statusConfig, DEFAULT_STATUS_CONFIG, base?.statusConfig ?? {});
    if (doc.value) doc.value.statusConfig = { ...statusConfig };
    statusOverride.value = rateTrend.value ? statusMessage(rateTrend.value) || null : null;
    renderPreview();
}

function buildFields(): Record<string, string> {
    const f = { ...(doc.value?.fields ?? {}) };
    if (rate.p22k1g) f['price_22k_1g'] = money(rate.p22k1g);
    if (rate.p22k8g) f['price_22k_8g'] = money(rate.p22k8g);
    if (rate.p18k1g) f['price_18k_1g'] = money(rate.p18k1g);
    f['date'] = displayDate(rate.date);
    if (statusOverride.value != null) f['status'] = statusOverride.value;
    // Brand contact details always reflect what's stored on the tenant's profile, not the template's saved copy.
    f['brand_name'] = props.tenant?.name ?? '';
    f['phone'] = props.tenant?.phone ?? '';
    f['email'] = props.tenant?.email ?? '';
    f['address'] = props.tenant?.address ?? '';
    return f;
}

function renderPreview() {
    const canvas = canvasRef.value;
    if (!canvas || !doc.value) return;
    renderDocument(canvas, doc.value, assets.value, buildFields());
    if (adjustMode.value) drawSelection(canvas);
}

// ── Free positioning ─────────────────────────────────────────────────
// "Adjust layout" turns the preview into a light editing surface: drag any element to
// move it, drag its corner handle to resize. Changes live on this page's working copy,
// so the saved design — which other people may also be using — is never touched.
const adjustMode = ref(false);
const selectedLayerIds = ref<number[]>([]);
/** Tap adds to the selection instead of replacing it — the touch stand-in for shift-click. */
const multiSelect = ref(false);
const HANDLE = 34;

type DragState = {
    mode: 'move' | 'resize' | 'marquee';
    startX: number; startY: number;
    layer: Layer | null;
    baseW: number; baseH: number; baseFont: number;
    origins: { layer: Layer; x: number; y: number }[];
};
let drag: DragState | null = null;
const marquee = ref<{ x1: number; y1: number; x2: number; y2: number } | null>(null);

const selectedLayers = computed(() => (doc.value?.layers ?? []).filter((l) => selectedLayerIds.value.includes(l.id)));
const selectedLayer = computed(() => (selectedLayers.value.length === 1 ? selectedLayers.value[0] : null));

function selectLayer(id: number, additive: boolean) {
    if (!additive) { selectedLayerIds.value = [id]; return; }
    selectedLayerIds.value = selectedLayerIds.value.includes(id)
        ? selectedLayerIds.value.filter((s) => s !== id)
        : [...selectedLayerIds.value, id];
}

/** Canvas-space size of a layer as it is currently drawn. */
function boundsOf(layer: Layer): { w: number; h: number } {
    const ctx = canvasRef.value?.getContext('2d');
    if (!ctx) return { w: layer.w, h: layer.h };

    return layerSize(ctx, layer, buildFields());
}

/** Maps a pointer event to canvas coordinates, accounting for the on-screen scale. */
function toCanvasPoint(e: PointerEvent): { x: number; y: number } {
    const canvas = canvasRef.value!;
    const rect = canvas.getBoundingClientRect();

    return {
        x: (e.clientX - rect.left) * (canvas.width / rect.width),
        y: (e.clientY - rect.top) * (canvas.height / rect.height),
    };
}

/** Point-in-layer test in the layer's own (un-rotated) space. */
function hitLayer(layer: Layer, x: number, y: number, pad = 0): boolean {
    const { w, h } = boundsOf(layer);
    const rad = (-(layer.rotation || 0) * Math.PI) / 180;
    const dx = x - layer.x;
    const dy = y - layer.y;
    const lx = dx * Math.cos(rad) - dy * Math.sin(rad);
    const ly = dx * Math.sin(rad) + dy * Math.cos(rad);

    return Math.abs(lx) <= w / 2 + pad && Math.abs(ly) <= h / 2 + pad;
}

function handlePoint(layer: Layer): { x: number; y: number } {
    const { w, h } = boundsOf(layer);
    const rad = ((layer.rotation || 0) * Math.PI) / 180;
    const hx = w / 2;
    const hy = h / 2;

    return {
        x: layer.x + hx * Math.cos(rad) - hy * Math.sin(rad),
        y: layer.y + hx * Math.sin(rad) + hy * Math.cos(rad),
    };
}

function drawSelection(canvas: HTMLCanvasElement) {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    for (const layer of selectedLayers.value) {
        const { w, h } = boundsOf(layer);
        ctx.save();
        ctx.translate(layer.x, layer.y);
        ctx.rotate(((layer.rotation || 0) * Math.PI) / 180);
        ctx.strokeStyle = '#3b82f6';
        ctx.lineWidth = 4;
        ctx.setLineDash([14, 10]);
        ctx.strokeRect(-w / 2, -h / 2, w, h);
        ctx.setLineDash([]);
        // Only a single selection gets a drag-to-resize handle; groups resize with the buttons.
        if (selectedLayers.value.length === 1) {
            ctx.fillStyle = '#3b82f6';
            ctx.beginPath();
            ctx.arc(w / 2, h / 2, HANDLE / 2, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.restore();
    }

    if (marquee.value) {
        const m = marquee.value;
        ctx.save();
        ctx.strokeStyle = '#3b82f6';
        ctx.fillStyle = 'rgba(59,130,246,0.15)';
        ctx.lineWidth = 3;
        ctx.setLineDash([10, 8]);
        ctx.fillRect(m.x1, m.y1, m.x2 - m.x1, m.y2 - m.y1);
        ctx.strokeRect(m.x1, m.y1, m.x2 - m.x1, m.y2 - m.y1);
        ctx.restore();
    }
}

function onPointerDown(e: PointerEvent) {
    if (!adjustMode.value || !doc.value) return;
    const { x, y } = toCanvasPoint(e);
    const additive = multiSelect.value || e.shiftKey || e.ctrlKey || e.metaKey;

    // The resize handle of a single selection wins over anything underneath it.
    const current = selectedLayer.value;
    if (current && !additive) {
        const handle = handlePoint(current);
        // Generous radius: on a 1080-wide canvas shown ~380px wide, 34 units is only ~12 screen px.
        if (Math.hypot(x - handle.x, y - handle.y) <= HANDLE * 2) {
            const { w, h } = boundsOf(current);
            drag = { mode: 'resize', startX: x, startY: y, layer: current, baseW: w, baseH: h, baseFont: current.fontSize, origins: [] };
            (e.target as HTMLElement).setPointerCapture(e.pointerId);
            e.preventDefault();

            return;
        }
    }

    const hit = [...(doc.value.layers ?? [])].reverse().find((l) => hitLayer(l, x, y, 8));

    if (!hit) {
        // Empty space: drag out a marquee to select everything inside it.
        selectedLayerIds.value = additive ? selectedLayerIds.value : [];
        drag = { mode: 'marquee', startX: x, startY: y, layer: null, baseW: 0, baseH: 0, baseFont: 0, origins: [] };
        marquee.value = { x1: x, y1: y, x2: x, y2: y };
        (e.target as HTMLElement).setPointerCapture(e.pointerId);
        e.preventDefault();
        renderPreview();

        return;
    }

    // Dragging an already-selected layer moves the whole selection with it.
    if (!selectedLayerIds.value.includes(hit.id)) selectLayer(hit.id, additive);
    else if (additive) selectLayer(hit.id, true);

    const origins = selectedLayers.value.map((l) => ({ layer: l, x: l.x, y: l.y }));
    if (origins.length) {
        drag = { mode: 'move', startX: x, startY: y, layer: hit, baseW: 0, baseH: 0, baseFont: hit.fontSize, origins };
        (e.target as HTMLElement).setPointerCapture(e.pointerId);
        e.preventDefault();
    }
    renderPreview();
}

function onPointerMove(e: PointerEvent) {
    if (!drag) return;
    const { x, y } = toCanvasPoint(e);

    if (drag.mode === 'marquee') {
        marquee.value = { x1: Math.min(drag.startX, x), y1: Math.min(drag.startY, y), x2: Math.max(drag.startX, x), y2: Math.max(drag.startY, y) };
        renderPreview();

        return;
    }

    const layer = drag.layer!;

    if (drag.mode === 'move') {
        // Every selected layer shifts by the same delta, keeping their relative positions.
        const dx = Math.round(x - drag.startX);
        const dy = Math.round(y - drag.startY);
        for (const origin of drag.origins) {
            origin.layer.x = origin.x + dx;
            origin.layer.y = origin.y + dy;
        }
    } else {
        // Scale against the size captured at drag start, so dragging doesn't compound.
        const scale = Math.max(0.1, (x - layer.x) / Math.max(1, drag.baseW / 2));
        if (layer.type === 'text') {
            layer.fontSize = Math.max(10, Math.round(drag.baseFont * scale));
        } else {
            layer.w = Math.max(20, Math.round(drag.baseW * scale));
            layer.h = Math.max(20, Math.round(drag.baseH * scale));
        }
    }
    renderPreview();
}

function onPointerUp() {
    if (drag?.mode === 'marquee' && marquee.value && doc.value) {
        const m = marquee.value;
        const inside = (doc.value.layers ?? []).filter((l) => {
            const { w, h } = boundsOf(l);

            // Overlap, not containment — a partly covered layer still counts as picked.
            return l.x + w / 2 >= m.x1 && l.x - w / 2 <= m.x2 && l.y + h / 2 >= m.y1 && l.y - h / 2 <= m.y2;
        }).map((l) => l.id);
        selectedLayerIds.value = [...new Set([...selectedLayerIds.value, ...inside])];
    }
    marquee.value = null;
    drag = null;
    renderPreview();
}

function selectAllLayers() {
    selectedLayerIds.value = (doc.value?.layers ?? []).map((l) => l.id);
    renderPreview();
}

function clearSelection() {
    selectedLayerIds.value = [];
    renderPreview();
}

function resetLayout() {
    selectedLayerIds.value = [];
    applyExtras();
}

function nudgeSelected(dx: number, dy: number) {
    for (const layer of selectedLayers.value) {
        layer.x += dx;
        layer.y += dy;
    }
    renderPreview();
}

/** Removes everything selected from this poster. "Reset layout" brings it all back. */
function removeSelected() {
    const layers = selectedLayers.value;
    if (!layers.length || !doc.value) return;

    const ids = layers.map((l) => l.id);
    const removedIconKeys = ids.map((id) => iconLayerKeys[id]).filter(Boolean);
    selectedLayerIds.value = [];

    doc.value.layers = (doc.value.layers ?? []).filter((l) => !ids.includes(l.id));

    if (removedIconKeys.length) {
        // Untick them in step 3 and re-lay the remaining icons so the row stays centred.
        selectedIcons.value = selectedIcons.value.filter((k) => !removedIconKeys.includes(k));
        applyExtras();

        return;
    }

    renderPreview();
}

function resizeSelected(factor: number) {
    for (const layer of selectedLayers.value) {
        if (layer.type === 'text') layer.fontSize = Math.max(10, Math.round(layer.fontSize * factor));
        else { layer.w = Math.max(20, Math.round(layer.w * factor)); layer.h = Math.max(20, Math.round(layer.h * factor)); }
    }
    renderPreview();
}

watch(adjustMode, () => { selectedLayerIds.value = []; multiSelect.value = false; renderPreview(); });

// ── Step 1 · design ──────────────────────────────────────────────────
// Designs are chosen from rendered previews rather than a list of names, so the
// picker shows what the poster actually looks like before prices are filled in.
const { thumbnails, loading: thumbnailsLoading, render: buildThumbnails, fetchDocument } = useTemplateThumbnails({
    logoUrl: props.tenant?.logo_light_url ?? props.tenant?.logo_dark_url ?? null,
});

const designSheetOpen = ref(false);
const selectedTemplate = computed(() => props.templates.find((t) => t.id === templateId.value) ?? null);

async function selectTemplate(id: number) {
    templateId.value = id;
    designSheetOpen.value = false;
    await loadTemplate(id);
}

async function loadTemplate(id: number | null) {
    if (!id) { doc.value = null; return; }
    loading.value = true; statusOverride.value = null; rateTrend.value = null; statusEdited.value = false;
    try {
        // fetchDocument caches the pristine document; every change here works on a copy.
        await fetchDocument(id);
        await applyExtras();
    } catch { modals.alert('Could not load that template.', { title: 'Load failed' }); } finally { loading.value = false; }
}

// ── Step 2 · background (optional) ───────────────────────────────────
const backgroundLibrary = computed<BackgroundOption[]>(() => props.backgrounds ?? []);
const backgroundId = ref<number | null>(null);
// Framing for the chosen background photo: 1 = fill, offsets pan from centre.
const bgScale = ref(1);
const bgOffsetX = ref(0);
const bgOffsetY = ref(0);
/** Writes the current framing onto the live document and repaints — no refetch needed. */
function applyBackgroundFraming() {
    if (!doc.value?.bg) return;
    doc.value.bg = { ...doc.value.bg, scale: bgScale.value, offsetX: bgOffsetX.value, offsetY: bgOffsetY.value };
    renderPreview();
}
function nudgeBackground(dx: number, dy: number) { bgOffsetX.value += dx; bgOffsetY.value += dy; applyBackgroundFraming(); }
function resetBackgroundFraming() { bgScale.value = 1; bgOffsetX.value = 0; bgOffsetY.value = 0; applyBackgroundFraming(); }
const designSearch = ref('');
const visibleTemplates = computed(() => {
    const q = designSearch.value.trim().toLowerCase();
    if (!q) return props.templates;

    return props.templates.filter((t) => `${t.name} ${t.type ?? ''}`.toLowerCase().includes(q));
});
const backgroundSearch = ref('');
const backgroundCategory = ref('all');
const backgroundCategories = computed(() => ['all', ...new Set(backgroundLibrary.value.map((b) => b.category ?? 'Uncategorized'))]);
const visibleBackgrounds = computed(() => {
    const q = backgroundSearch.value.trim().toLowerCase();

    return backgroundLibrary.value.filter((b) => {
        if (backgroundCategory.value !== 'all' && (b.category ?? 'Uncategorized') !== backgroundCategory.value) return false;
        if (!q) return true;

        return `${b.name} ${b.category ?? ''}`.toLowerCase().includes(q);
    });
});

const backgroundSheetOpen = ref(false);
const selectedBackground = computed(() => backgroundLibrary.value.find((b) => b.id === backgroundId.value) ?? null);

async function chooseBackground(id: number | null) {
    backgroundId.value = id;
    bgScale.value = 1; bgOffsetX.value = 0; bgOffsetY.value = 0;
    backgroundSheetOpen.value = false;
    await applyExtras();
}

// ── Step 3 · icons (optional) ────────────────────────────────────────
const ICON_ROW_POSITIONS = [
    { key: 'top', label: 'Top', factor: 0.16 },
    { key: 'middle', label: 'Middle', factor: 0.5 },
    { key: 'bottom', label: 'Above contact', factor: 0.84 },
] as const;

const iconSheetOpen = ref(false);
const iconGroup = ref(POSTER_ICON_GROUPS[0].key);
const iconColor = ref(DEFAULT_ICON_COLOR);
const iconPosition = ref<(typeof ICON_ROW_POSITIONS)[number]['key']>('bottom');
const selectedIcons = ref<string[]>([]);
const iconGroupIcons = computed(() => POSTER_ICON_GROUPS.find((g) => g.key === iconGroup.value)?.icons ?? []);

function iconPreview(key: string): string { return posterIconDataUrl(key, iconColor.value) ?? ''; }

async function toggleIcon(key: string) {
    selectedIcons.value = selectedIcons.value.includes(key)
        ? selectedIcons.value.filter((k) => k !== key)
        : [...selectedIcons.value, key];
    await applyExtras();
}

/** Layer id → icon key, so deleting an icon on the canvas also unticks it in step 3. */
const iconLayerKeys = reactive<Record<number, string>>({});

/** Lays the chosen icons out as one centred row, scaled to fit the canvas width. */
async function iconLayers(width: number, height: number): Promise<{ layers: Layer[]; images: Record<number, HTMLImageElement> }> {
    const layers: Layer[] = [];
    const images: Record<number, HTMLImageElement> = {};
    if (!selectedIcons.value.length) return { layers, images };

    const y = height * (ICON_ROW_POSITIONS.find((p) => p.key === iconPosition.value)?.factor ?? 0.84);
    const gap = 32;
    const loaded = await Promise.all(selectedIcons.value.map(async (key) => {
        const src = posterIconDataUrl(key, iconColor.value);
        if (!src) return null;
        const img = await loadImage(src);

        return { key, img, ratio: (img.naturalWidth || 120) / (img.naturalHeight || 120) };
    }));
    const items = loaded.filter(Boolean) as { key: string; img: HTMLImageElement; ratio: number }[];
    if (!items.length) return { layers, images };

    // One height for the row, shrunk until the whole row fits inside the canvas margins.
    let h = 150;
    const rowWidth = (rowHeight: number) => items.reduce((sum, i) => sum + rowHeight * i.ratio, 0) + gap * (items.length - 1);
    const maxWidth = width - 120;
    if (rowWidth(h) > maxWidth) h = h * (maxWidth / rowWidth(h));

    let x = (width - rowWidth(h)) / 2;
    // Ids sit far above anything the editor assigns so they can't collide with saved images.
    let imgId = 950001;
    Object.keys(iconLayerKeys).forEach((k) => delete iconLayerKeys[Number(k)]);
    for (const item of items) {
        const w = h * item.ratio;
        images[imgId] = item.img;
        iconLayerKeys[imgId] = item.key;
        layers.push({
            id: imgId, type: 'image', x: x + w / 2, y, rotation: 0,
            text: '', fontFamily: 'Poppins', fontSize: 48, weight: 600, color: '#ffffff',
            align: 'center', shadow: false, letterSpacing: 0, field: '',
            imgId, w, h, naturalRatio: item.ratio,
            opacity: 1, radius: 0, strokeW: 0, strokeColor: '#ffffff', curve: 0, fillEnabled: true,
            radiusTL: 0, radiusTR: 0, radiusBR: 0, radiusBL: 0,
        });
        x += w + gap;
        imgId++;
    }

    return { layers, images };
}

/** Re-applies the optional background and icon choices on top of the chosen design. */
async function applyExtras() {
    if (!templateId.value) { doc.value = null; return; }
    const base = await fetchDocument(templateId.value);

    // A fresh copy drops any layout tweaks, so the selection can't point at a stale layer.
    selectedLayerIds.value = [];
    const next = JSON.parse(JSON.stringify(base)) as PosterDocument;
    const background = backgroundLibrary.value.find((b) => b.id === backgroundId.value);
    if (background) {
        next.bg = { color: next.bg?.color ?? '#0d3b34', src: background.url, scale: bgScale.value, offsetX: bgOffsetX.value, offsetY: bgOffsetY.value };
    }

    const width = next.canvas?.w ?? 1080;
    const height = next.canvas?.h ?? 1920;
    const { layers, images } = await iconLayers(width, height);
    next.layers = [...(next.layers ?? []), ...layers];

    // applyExtras rebuilds from the pristine design, so re-apply any reworded message.
    if (statusEdited.value) { next.statusConfig = { ...statusConfig }; }
    else { Object.assign(statusConfig, DEFAULT_STATUS_CONFIG, next.statusConfig ?? {}); }

    doc.value = next;
    const loadedAssets = await loadDocumentAssets(next);
    assets.value = { bg: loadedAssets.bg, images: { ...loadedAssets.images, ...images } };
    renderPreview();
}

// ── Step 4 · prices ──────────────────────────────────────────────────
async function updateRate() {
    if (!doc.value) { modals.alert('Pick a design first.'); return; }
    if (!rate.p22k1g || !rate.p22k8g || !rate.p18k1g) { modals.alert('Enter all three prices first.'); return; }
    busy.value = true;
    try {
        const { data } = await axios.post('/gold-poster/rates', {
            date: rate.date, price_22k_1g: num(rate.p22k1g), price_22k_8g: num(rate.p22k8g), price_18k_1g: num(rate.p18k1g),
        });
        rateTrend.value = data.trend;
        // Fall back to the template's own status text when there's no message (e.g. first-ever rate).
        statusOverride.value = statusMessage(data.trend) || null;
        renderPreview();
    } catch { modals.alert('Could not save the rate.', { title: 'Save failed' }); } finally { busy.value = false; }
}

function downloadPoster() {
    if (!doc.value) { modals.alert('Pick a design first.'); return; }
    const canvas = canvasRef.value; if (!canvas) return;
    // Draw without the selection outline so it never ends up in the downloaded file.
    renderDocument(canvas, doc.value, assets.value, buildFields());
    canvas.toBlob((blob) => {
        if (!blob) return;
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = `gold-rate-${rate.date}.png`; a.click();
        URL.revokeObjectURL(url);
    }, 'image/png');
}

const trendText = () => {
    const t = rateTrend.value; if (!t) return '';
    if (!t.has_previous) return '';
    return statusMessage(t) || 'No change vs previous';
};

watch(rate, () => { if (statusEdited.value) statusOverride.value = currentStatusText(); renderPreview(); });
watch(() => rate.date, (d) => applyRateForDate(d));

onMounted(async () => {
    applyRateForDate(rate.date);
    await ensureFonts();
    await loadTemplate(templateId.value);
    await buildThumbnails(props.templates.map((t) => t.id));
    document.fonts.addEventListener('loadingdone', () => renderPreview());
});

const inputClass = 'w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground transition focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25';
const stepClass = 'flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-[11px] font-semibold text-primary-foreground';
// Touch-sized square control used by the move/resize pads.
const ctrlClass = 'flex h-9 w-9 items-center justify-center rounded-md border border-border text-sm text-foreground transition hover:bg-muted active:bg-muted';
</script>

<template>
    <Head title="Update gold rate" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground">Update gold rate</h1>
                    <p class="text-sm text-muted-foreground">Pick a design, set today's prices, and download the poster.</p>
                </div>
                <Link href="/gold-poster/rate-history" class="text-sm font-medium text-primary transition hover:opacity-80">Rate history →</Link>
            </div>

            <!-- No templates -->
            <div v-if="!templates.length" class="rounded-lg border border-dashed border-border bg-card px-6 py-14 text-center">
                <p class="text-sm font-medium text-foreground">No saved poster designs yet</p>
                <p class="mx-auto mt-1 mb-5 max-w-sm text-sm text-muted-foreground">Design a poster and save it as a template first — then come here to update prices daily.</p>
                <Link href="/gold-poster" class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90">Open the poster editor</Link>
            </div>

            <div v-else class="grid gap-4 sm:gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,380px)]">
                <!-- Steps -->
                <div class="space-y-4 sm:space-y-5">
                    <!-- 1 · Design — the grid lives in a drawer so the page stays short -->
                    <section class="rounded-lg border border-border bg-card p-3.5 shadow-xs sm:p-5">
                        <div class="mb-3 flex items-center gap-2">
                            <span :class="stepClass">1</span>
                            <h2 class="text-sm font-semibold text-foreground">Design</h2>
                            <span v-if="thumbnailsLoading" class="text-xs text-muted-foreground">rendering previews…</span>
                        </div>
                        <button type="button" class="flex w-full items-center gap-3 rounded-md border border-border p-2 text-left transition hover:bg-muted/50" @click="designSheetOpen = true">
                            <span class="h-16 w-10 shrink-0 overflow-hidden rounded bg-muted">
                                <img v-if="templateId && thumbnails[templateId]" :src="thumbnails[templateId]" alt="" class="h-full w-full object-cover" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-foreground">{{ selectedTemplate?.name ?? 'Pick a design' }}</span>
                                <span class="block text-xs text-muted-foreground">{{ templates.length }} design{{ templates.length === 1 ? '' : 's' }} available</span>
                            </span>
                            <span class="shrink-0 rounded-md border border-border px-3 py-2 text-xs font-medium text-foreground">Change</span>
                        </button>
                    </section>

                    <!-- 2 · Background (optional) -->
                    <section v-if="backgroundLibrary.length" class="rounded-lg border border-border bg-card p-3.5 shadow-xs sm:p-5">
                        <div class="mb-3 flex items-center gap-2">
                            <span :class="stepClass">2</span>
                            <h2 class="text-sm font-semibold text-foreground">Background</h2>
                            <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">Optional</span>
                        </div>
                        <button type="button" class="flex w-full items-center gap-3 rounded-md border border-border p-2 text-left transition hover:bg-muted/50" @click="backgroundSheetOpen = true">
                            <span class="flex h-16 w-10 shrink-0 items-center justify-center overflow-hidden rounded bg-muted">
                                <img v-if="selectedBackground" :src="selectedBackground.url" alt="" class="h-full w-full object-cover" />
                                <ImageOff v-else class="h-4 w-4 text-muted-foreground" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-foreground">{{ selectedBackground?.name ?? "Design's own background" }}</span>
                                <span class="block text-xs text-muted-foreground">{{ backgroundLibrary.length }} image{{ backgroundLibrary.length === 1 ? '' : 's' }} available</span>
                            </span>
                            <span class="shrink-0 rounded-md border border-border px-3 py-2 text-xs font-medium text-foreground">Choose</span>
                        </button>

                        <!-- Framing, so a photo can be zoomed and shifted rather than always centre-cropped -->
                        <div v-if="selectedBackground" class="mt-3 rounded-md border border-border bg-muted/30 p-3">
                            <label class="mb-1.5 flex items-center justify-between text-xs font-medium text-muted-foreground">
                                <span>Zoom</span>
                                <span class="tabular-nums text-foreground">{{ Math.round(bgScale * 100) }}%</span>
                            </label>
                            <input v-model.number="bgScale" type="range" min="0.5" max="4" step="0.01" class="w-full accent-primary" @input="applyBackgroundFraming" />
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span class="text-xs text-muted-foreground">Position</span>
                                <button type="button" :class="ctrlClass" aria-label="Move background left" @click="nudgeBackground(-30, 0)">←</button>
                                <button type="button" :class="ctrlClass" aria-label="Move background right" @click="nudgeBackground(30, 0)">→</button>
                                <button type="button" :class="ctrlClass" aria-label="Move background up" @click="nudgeBackground(0, -30)">↑</button>
                                <button type="button" :class="ctrlClass" aria-label="Move background down" @click="nudgeBackground(0, 30)">↓</button>
                                <button type="button" class="ml-auto min-h-9 px-2 text-xs text-muted-foreground underline-offset-2 hover:underline" @click="resetBackgroundFraming">Reset</button>
                            </div>
                        </div>
                    </section>

                    <!-- 3 · Icons (optional) -->
                    <section class="rounded-lg border border-border bg-card p-3.5 shadow-xs sm:p-5">
                        <div class="mb-1 flex items-center gap-2">
                            <span :class="stepClass">3</span>
                            <h2 class="text-sm font-semibold text-foreground">Icons &amp; certifications</h2>
                            <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">Optional</span>
                        </div>
                        <button type="button" class="flex w-full items-center gap-3 rounded-md border border-border p-2 text-left transition hover:bg-muted/50" @click="iconSheetOpen = true">
                            <span class="flex h-16 w-10 shrink-0 items-center justify-center overflow-hidden rounded bg-[#1b1b1f] p-1">
                                <img v-if="selectedIcons.length" :src="iconPreview(selectedIcons[0])" alt="" class="max-h-full max-w-full" />
                                <BadgeCheck v-else class="h-4 w-4 text-muted-foreground" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-foreground">
                                    {{ selectedIcons.length ? `${selectedIcons.length} selected` : 'No icons added' }}
                                </span>
                                <span class="block text-xs text-muted-foreground">Hallmark badges like 916 or BIS, or contact icons</span>
                            </span>
                            <span class="shrink-0 rounded-md border border-border px-3 py-2 text-xs font-medium text-foreground">Choose</span>
                        </button>

                        <div v-if="selectedIcons.length" class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="text-xs text-muted-foreground">Position</span>
                            <button
                                v-for="pos in ICON_ROW_POSITIONS"
                                :key="pos.key"
                                type="button"
                                :class="['rounded-md border px-2.5 py-1 text-xs font-medium transition', iconPosition === pos.key ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']"
                                @click="iconPosition = pos.key; applyExtras()"
                            >
                                {{ pos.label }}
                            </button>
                            <button type="button" class="ml-auto text-xs text-muted-foreground underline-offset-2 hover:underline" @click="selectedIcons = []; applyExtras()">Clear</button>
                        </div>
                    </section>

                    <!-- 4 · Prices -->
                    <section class="rounded-lg border border-border bg-card p-3.5 shadow-xs sm:p-5">
                        <div class="mb-4 flex items-center gap-2">
                            <span :class="stepClass">4</span>
                            <h2 class="text-sm font-semibold text-foreground">Today's prices</h2>
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Date</label><input v-model="rate.date" type="date" :class="inputClass" /></div>
                            <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">22K · 1g</label><input v-model="rate.p22k1g" inputmode="numeric" placeholder="13880" :class="inputClass" /></div>
                            <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">22K · 8g</label><input v-model="rate.p22k8g" inputmode="numeric" placeholder="111040" :class="inputClass" /></div>
                            <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">18K · 1g</label><input v-model="rate.p18k1g" inputmode="numeric" placeholder="11356" :class="inputClass" /></div>
                        </div>
                        <p class="mt-3 text-xs text-muted-foreground">The price-change line updates from the 1g 22K change against the previous saved date.</p>

                        <!-- Price-change wording -->
                        <button type="button" class="mt-3 flex w-full items-center gap-3 rounded-md border border-border p-2 text-left transition hover:bg-muted/50" @click="messageSheetOpen = true">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-muted">
                                <MessageSquareText class="h-4 w-4 text-muted-foreground" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-foreground">{{ statusPreviewText || 'No change message' }}</span>
                                <span class="block text-xs text-muted-foreground">
                                    Price change message{{ statusEdited ? ' · reworded' : '' }}{{ statusIsSample ? ' · sample amount' : '' }}
                                </span>
                            </span>
                            <span class="shrink-0 rounded-md border border-border px-3 py-2 text-xs font-medium text-foreground">Change</span>
                        </button>
                        <div v-if="rateTrend" class="mt-3">
                            <span :class="['inline-flex items-center gap-1 text-sm font-medium', rateTrend.direction === 'up' ? 'text-emerald-600 dark:text-emerald-400' : rateTrend.direction === 'down' ? 'text-rose-600 dark:text-rose-400' : 'text-muted-foreground']">
                                <component :is="rateTrend.direction === 'up' ? TrendingUp : rateTrend.direction === 'down' ? TrendingDown : Minus" class="h-4 w-4" />
                                {{ trendText() }}
                            </span>
                        </div>
                        <button type="button" :disabled="busy || loading" class="mt-4 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-3 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-60" @click="updateRate">
                            <RefreshCw class="h-4 w-4" /> {{ busy ? 'Saving…' : 'Save today\'s rate' }}
                        </button>
                    </section>
                </div>

                <!-- Preview — first on mobile so the poster stays in view while the steps scroll -->
                <div class="order-first lg:order-none lg:sticky lg:top-6 lg:self-start">
                    <div class="rounded-lg border border-border bg-card p-3 shadow-xs sm:p-4">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-foreground">Preview</h2>
                            <button
                                type="button"
                                :class="['inline-flex min-h-9 items-center gap-1.5 rounded-md border px-3 py-2 text-xs font-medium transition', adjustMode ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']"
                                @click="adjustMode = !adjustMode"
                            >
                                <Move class="h-3.5 w-3.5" /> {{ adjustMode ? 'Done' : 'Adjust layout' }}
                            </button>
                        </div>
                        <div class="relative flex items-center justify-center overflow-hidden rounded-md border border-border bg-muted">
                            <canvas
                                ref="canvasRef"
                                :class="['mx-auto block h-auto max-h-[48vh] w-full object-contain sm:max-h-[520px]', adjustMode ? 'cursor-move touch-none' : '']"
                                @pointerdown="onPointerDown"
                                @pointermove="onPointerMove"
                                @pointerup="onPointerUp"
                                @pointercancel="onPointerUp"
                            />
                            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-background/60 text-sm text-muted-foreground">Loading…</div>
                        </div>

                        <!-- Free positioning controls -->
                        <div v-if="adjustMode" class="mt-3 rounded-md border border-border bg-muted/30 p-3">
                            <p class="text-xs text-muted-foreground">
                                Tap an element to select it, drag to move, drag the blue dot to resize. Drag on empty space to lasso several at once.
                            </p>

                            <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                <button
                                    type="button"
                                    :class="['inline-flex min-h-9 items-center gap-1.5 rounded-md border px-3 py-2 text-xs font-medium transition', multiSelect ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']"
                                    title="Tap several elements one after another"
                                    @click="multiSelect = !multiSelect"
                                >
                                    <Layers class="h-3.5 w-3.5" /> Multi-select
                                </button>
                                <button type="button" class="min-h-9 rounded-md border border-border px-3 py-2 text-xs font-medium text-muted-foreground transition hover:bg-muted" @click="selectAllLayers">Select all</button>
                                <button v-if="selectedLayers.length" type="button" class="min-h-9 rounded-md border border-border px-3 py-2 text-xs font-medium text-muted-foreground transition hover:bg-muted" @click="clearSelection">Deselect</button>
                            </div>

                            <div v-if="selectedLayers.length" class="mt-3 space-y-2.5">
                                <p class="truncate text-xs font-medium text-foreground">
                                    <template v-if="selectedLayer">
                                        Selected: {{ selectedLayer.type === 'text' ? (selectedLayer.field || selectedLayer.text || 'Text') : selectedLayer.type }}
                                    </template>
                                    <template v-else>{{ selectedLayers.length }} elements selected — moved and resized together</template>
                                </p>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs text-muted-foreground">Move</span>
                                    <button type="button" :class="ctrlClass" aria-label="Move left" @click="nudgeSelected(-10, 0)">←</button>
                                    <button type="button" :class="ctrlClass" aria-label="Move right" @click="nudgeSelected(10, 0)">→</button>
                                    <button type="button" :class="ctrlClass" aria-label="Move up" @click="nudgeSelected(0, -10)">↑</button>
                                    <button type="button" :class="ctrlClass" aria-label="Move down" @click="nudgeSelected(0, 10)">↓</button>
                                    <span class="ml-2 text-xs text-muted-foreground">Size</span>
                                    <button type="button" :class="ctrlClass" aria-label="Smaller" @click="resizeSelected(0.9)">−</button>
                                    <button type="button" :class="ctrlClass" aria-label="Bigger" @click="resizeSelected(1.1)">+</button>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex min-h-9 items-center gap-1.5 rounded-md border border-border px-3 py-2 text-xs font-medium text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                                        @click="removeSelected"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" /> Remove{{ selectedLayers.length > 1 ? ` (${selectedLayers.length})` : '' }}
                                    </button>
                                    <button type="button" class="min-h-9 px-2 text-xs text-muted-foreground underline-offset-2 hover:underline" @click="resetLayout">Reset layout</button>
                                </div>
                            </div>
                            <button v-else type="button" class="mt-2 min-h-9 text-xs text-muted-foreground underline-offset-2 hover:underline" @click="resetLayout">Reset layout</button>
                        </div>

                        <button type="button" :disabled="loading" class="mt-3 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-3 text-sm font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 disabled:opacity-60" @click="downloadPoster">
                            <Download class="h-4 w-4" /> Download poster
                        </button>
                        <p class="mt-2 text-center text-xs text-muted-foreground">Layout changes apply to this download only — the saved design stays as it is.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Icon drawer -->
        <Sheet v-model:open="iconSheetOpen">
            <SheetContent side="bottom" class="max-h-[85vh] overflow-y-auto">
                <SheetHeader>
                    <SheetTitle>Icons &amp; certifications</SheetTitle>
                    <SheetDescription>Tap to add or remove. Selected icons are placed as one centred row.</SheetDescription>
                </SheetHeader>
                <div class="flex flex-wrap items-center justify-between gap-3 px-4">
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="group in POSTER_ICON_GROUPS"
                            :key="group.key"
                            type="button"
                            :class="['rounded-full border px-3 py-1.5 text-xs font-medium transition', iconGroup === group.key ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']"
                            @click="iconGroup = group.key"
                        >
                            {{ group.label }}
                        </button>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="c in ICON_COLORS"
                            :key="c"
                            type="button"
                            :class="['h-6 w-6 rounded-full border-2 transition', iconColor === c ? 'border-primary' : 'border-border']"
                            :style="{ backgroundColor: c }"
                            :title="c"
                            @click="iconColor = c; applyExtras()"
                        ></button>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 px-4 pb-6 sm:grid-cols-5 lg:grid-cols-8">
                    <button
                        v-for="icon in iconGroupIcons"
                        :key="icon.key"
                        type="button"
                        :class="['flex flex-col items-center gap-1 rounded-md border-2 p-2 transition', selectedIcons.includes(icon.key) ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50']"
                        @click="toggleIcon(icon.key)"
                    >
                        <span class="flex h-10 w-full items-center justify-center rounded bg-[#1b1b1f] p-1">
                            <img :src="iconPreview(icon.key)" :alt="icon.label" class="max-h-full max-w-full" />
                        </span>
                        <span class="truncate text-[10px] text-foreground">{{ icon.label }}</span>
                    </button>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Design drawer -->
        <Sheet v-model:open="designSheetOpen">
            <SheetContent side="bottom" class="max-h-[85vh] overflow-y-auto">
                <SheetHeader>
                    <SheetTitle>Choose a design</SheetTitle>
                    <SheetDescription>Tap a design to use it for today's poster.</SheetDescription>
                </SheetHeader>
                <div class="px-4 pb-3">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                        <input v-model="designSearch" type="search" placeholder="Search designs…" class="w-full rounded-md border border-input bg-background py-2.5 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25" />
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2.5 px-4 pb-6 sm:grid-cols-6 lg:grid-cols-10">
                    <button
                        v-for="t in visibleTemplates"
                        :key="t.id"
                        type="button"
                        :class="['relative overflow-hidden rounded-md border-2 transition', templateId === t.id ? 'border-primary' : 'border-border hover:border-primary/50']"
                        :title="t.name"
                        @click="selectTemplate(t.id)"
                    >
                        <span class="block aspect-[9/16] w-full bg-muted">
                            <img v-if="thumbnails[t.id]" :src="thumbnails[t.id]" :alt="t.name" class="h-full w-full object-cover" />
                            <span v-else class="flex h-full w-full animate-pulse items-center justify-center px-1 text-center text-[10px] text-muted-foreground">{{ t.name }}</span>
                        </span>
                        <span class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/75 to-transparent px-1 pb-0.5 pt-3 text-[10px] font-medium text-white">{{ t.name }}</span>
                        <span v-if="templateId === t.id" class="absolute right-1.5 top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-primary-foreground">
                            <Check class="h-3 w-3" />
                        </span>
                    </button>
                    <div v-if="!visibleTemplates.length" class="col-span-full py-10 text-center">
                        <p class="text-sm text-muted-foreground">No designs match “{{ designSearch }}”.</p>
                        <button type="button" class="mt-2 text-xs font-medium text-primary hover:underline" @click="designSearch = ''">Clear search</button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Background drawer -->
        <Sheet v-model:open="backgroundSheetOpen">
            <SheetContent side="bottom" class="max-h-[85vh] overflow-y-auto">
                <SheetHeader>
                    <SheetTitle>Background image</SheetTitle>
                    <SheetDescription>Keep the design's own background, or swap in a jewellery photo.</SheetDescription>
                </SheetHeader>
                <div class="px-4 pb-3">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                        <input v-model="backgroundSearch" type="search" placeholder="Search backgrounds…" class="w-full rounded-md border border-input bg-background py-2.5 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25" />
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5 px-4">
                    <button
                        v-for="cat in backgroundCategories"
                        :key="cat"
                        type="button"
                        :class="['rounded-full border px-3 py-1.5 text-xs font-medium capitalize transition', backgroundCategory === cat ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']"
                        @click="backgroundCategory = cat"
                    >
                        {{ cat }}
                    </button>
                </div>
                <div class="grid grid-cols-4 gap-2.5 px-4 pb-6 sm:grid-cols-6 lg:grid-cols-10">
                    <button
                        type="button"
                        :class="['flex aspect-[9/16] flex-col items-center justify-center gap-1 rounded-md border-2 px-1 text-center text-[10px] font-medium transition', backgroundId === null ? 'border-primary text-primary' : 'border-border text-muted-foreground hover:border-primary/50']"
                        @click="chooseBackground(null)"
                    >
                        <ImageOff class="h-4 w-4" /> Design's own
                    </button>
                    <button
                        v-for="bg in visibleBackgrounds"
                        :key="bg.id"
                        type="button"
                        :class="['relative overflow-hidden rounded-md border-2 transition', backgroundId === bg.id ? 'border-primary' : 'border-border hover:border-primary/50']"
                        :title="bg.name"
                        @click="chooseBackground(bg.id)"
                    >
                        <img :src="bg.url" :alt="bg.name" class="aspect-[9/16] w-full object-cover" loading="lazy" />
                        <span class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/75 to-transparent px-1 pb-0.5 pt-3 text-[10px] font-medium text-white">{{ bg.name }}</span>
                        <span v-if="backgroundId === bg.id" class="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-primary-foreground"><Check class="h-2.5 w-2.5" /></span>
                    </button>
                    <div v-if="!visibleBackgrounds.length" class="col-span-full py-10 text-center">
                        <p class="text-sm text-muted-foreground">No backgrounds match “{{ backgroundSearch }}”.</p>
                        <button type="button" class="mt-2 text-xs font-medium text-primary hover:underline" @click="backgroundSearch = ''">Clear search</button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Price-change message drawer -->
        <Sheet v-model:open="messageSheetOpen">
            <SheetContent side="bottom" class="max-h-[85vh] overflow-y-auto">
                <SheetHeader>
                    <SheetTitle>Price change message</SheetTitle>
                    <SheetDescription>How the rise or fall is worded on this poster. The saved design keeps its own wording.</SheetDescription>
                </SheetHeader>

                <div class="space-y-4 px-4 pb-6">
                    <div class="rounded-md border border-border bg-muted/30 p-3">
                        <p class="text-xs font-medium text-muted-foreground">Preview</p>
                        <p class="mt-1 text-base font-semibold text-foreground">{{ statusPreviewText || '—' }}</p>
                        <p v-if="statusIsSample" class="mt-1 text-xs text-muted-foreground">
                            Sample amount — the real change appears once today's price is entered.
                        </p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Show as</label>
                        <div class="inline-flex flex-wrap overflow-hidden rounded-md border border-border">
                            <button type="button" :class="['px-3 py-2 text-xs font-medium transition', statusConfig.mode === 'text' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="statusConfig.mode = 'text'; applyStatusConfig()">Wording</button>
                            <button type="button" :class="['border-l border-border px-3 py-2 text-xs font-medium transition', statusConfig.mode === 'icon' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="statusConfig.mode = 'icon'; applyStatusConfig()">Icon only</button>
                            <button type="button" :class="['border-l border-border px-3 py-2 text-xs font-medium transition', statusConfig.mode === 'both' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="statusConfig.mode = 'both'; applyStatusConfig()">Icon + wording</button>
                        </div>
                    </div>

                    <template v-if="statusConfig.mode !== 'icon'">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-muted-foreground">When the price goes up</label>
                            <input v-model="statusConfig.increaseText" :class="inputClass" placeholder="Gold price up ₹{diff}/gram" @input="applyStatusConfig" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-muted-foreground">When the price goes down</label>
                            <input v-model="statusConfig.decreaseText" :class="inputClass" placeholder="Gold price down ₹{diff}/gram" @input="applyStatusConfig" />
                        </div>
                        <p class="text-xs text-muted-foreground">Write <code>{diff}</code> where the amount of the change should appear.</p>
                    </template>

                    <div v-if="statusConfig.mode !== 'text'">
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Icon</label>
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                            <button
                                v-for="set in STATUS_ICON_SETS"
                                :key="set.key"
                                type="button"
                                :class="['flex flex-col items-center gap-1 rounded-md border px-2 py-2 text-xs transition', statusConfig.icon === set.key ? 'border-primary bg-primary/10 text-primary' : 'border-border text-foreground hover:bg-muted']"
                                @click="statusConfig.icon = set.key; applyStatusConfig()"
                            >
                                <span class="flex items-center gap-1.5 text-base leading-none"><span>{{ set.up }}</span><span>{{ set.down }}</span></span>
                                {{ set.label }}
                            </button>
                        </div>
                    </div>

                    <div v-if="statusConfig.mode === 'both'">
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Order</label>
                        <div class="inline-flex overflow-hidden rounded-md border border-border">
                            <button type="button" :class="['px-3 py-2 text-xs font-medium transition', statusConfig.order === 'icon-first' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="statusConfig.order = 'icon-first'; applyStatusConfig()">Icon → text</button>
                            <button type="button" :class="['border-l border-border px-3 py-2 text-xs font-medium transition', statusConfig.order === 'text-first' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="statusConfig.order = 'text-first'; applyStatusConfig()">Text → icon</button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 border-t border-border pt-3">
                        <p class="text-xs text-muted-foreground">Nothing is shown when the price hasn't changed.</p>
                        <button v-if="statusEdited" type="button" class="min-h-9 shrink-0 px-2 text-xs text-muted-foreground underline-offset-2 hover:underline" @click="resetStatusConfig">Use the design's wording</button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

        <ModalHost />
    </AppLayout>
</template>
