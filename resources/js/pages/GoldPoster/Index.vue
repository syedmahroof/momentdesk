<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    AlignCenter, AlignHorizontalJustifyCenter, AlignLeft, AlignRight,
    AlignVerticalJustifyCenter, AlignVerticalJustifyEnd, AlignVerticalJustifyStart, ArrowDown, ArrowUp, BadgeCheck,
    Bold, Cake, Check, Circle, ClipboardPaste, Coins, Copy, Download, GripVertical, ImageUp, Images, LayoutList, Magnet,
    Maximize, Minus, Move, MoveHorizontal, MoveVertical, Paintbrush, Redo2, RotateCcw, Ruler, Save, Search, Square,
    Trash2, Type, Undo2, X, ZoomIn, ZoomOut,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import ModalHost from '@/components/ModalHost.vue';
import { useModals } from '@/composables/useModals';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { DEFAULT_ICON_COLOR, ICON_COLORS, POSTER_ICON_GROUPS, posterIconDataUrl } from './posterIcons';
import { drawBackground, layoutText, TENANT_LOGO_IMAGE_ID, type VerticalAlign } from './renderer';
import { buildStatusMessage, DEFAULT_STATUS_ICON_SET, STATUS_ICON_SETS } from './statusIcons';
import { applyLayerStyle, copyLayerStyle, type CopiedStyle } from './styleClipboard';
import { useTemplateThumbnails } from './useTemplateThumbnails';

// Shown in the admin editor's logo slot when there's no real tenant to pull a logo from.
const DUMMY_LOGO_SRC = `data:image/svg+xml;utf8,${encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240"><circle cx="120" cy="120" r="118" fill="#52525b" stroke="#a1a1aa" stroke-width="3"/><text x="120" y="130" font-family="sans-serif" font-size="34" font-weight="700" fill="#e4e4e7" text-anchor="middle">LOGO</text></svg>')}`;

const modals = useModals();

interface TenantLike {
    name?: string | null; email?: string | null; phone?: string | null; address?: string | null;
    logo_light_url?: string | null; logo_dark_url?: string | null;
}
interface TemplateSummary { id: number; name: string; category?: string | null; type?: string | null; updated_at?: string | null; poster_category?: string | null; is_global?: boolean }
interface PosterCategoryOption { id: number; name: string }

interface PlaceholderOption { key: string; label: string; category: string }
interface BackgroundOption { id: number; name: string; url: string; category: string | null }
const props = defineProps<{
    tenant: TenantLike | null;
    templates: TemplateSummary[];
    open?: number | null;
    placeholders?: PlaceholderOption[];
    adminMode?: boolean;
    posterCategories?: PosterCategoryOption[];
    backgrounds?: BackgroundOption[];
}>();

const layout = props.adminMode ? AdminLayout : AppLayout;
const apiBase = props.adminMode ? '/admin/poster-templates' : '/gold-poster/templates';
const breadcrumbs: BreadcrumbItem[] = props.adminMode
    ? [{ title: 'Poster templates', href: '/admin/poster-templates' }]
    : [{ title: 'Templates', href: '/gold-poster' }];

const page = usePage();

/**
 * Placeholder brand used by the admin editor. The admin panel shares a session with the
 * tenant app, so `auth.tenant` can hold a real tenant — never read it here, or a tenant's
 * logo and contact details would be baked into a default template every tenant receives.
 */
const DUMMY_TENANT: TenantLike = {
    name: 'Your Brand',
    email: 'hello@yourbrand.com',
    phone: '+91 00000 00000',
    address: 'Your street, Your city',
};

const tenant = (props.adminMode
    ? DUMMY_TENANT
    : (props.tenant ?? (page.props.auth?.tenant as TenantLike | undefined) ?? {})) as TenantLike;
const posterCategoryId = ref<number | null>(null);

const canvasW = ref(1080);
const canvasH = ref(1920);
const HANDLE = 34;
const FONT_OPTIONS = ['Poppins', 'Montserrat', 'Oswald', 'Roboto', 'Bebas Neue', 'Anton', 'Playfair Display', 'Lora', 'Merriweather', 'Dancing Script', 'Great Vibes', 'Pacifico', 'Lobster', 'Caveat', 'Sacramento', 'Satisfy', 'Allura', 'Alex Brush', 'Tangerine', 'Kaushan Script', 'Parisienne', 'Cookie', 'Yellowtail', 'Pinyon Script'];
const FONT_CSS_URL = 'https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Anton&family=Bebas+Neue&family=Caveat:wght@400;500;600;700&family=Cookie&family=Dancing+Script:wght@400;500;600;700&family=Great+Vibes&family=Kaushan+Script&family=Lobster&family=Lora:wght@400;500;600;700&family=Merriweather:wght@300;400;700;900&family=Montserrat:wght@300;400;500;600;700;800;900&family=Oswald:wght@300;400;500;600;700&family=Pacifico&family=Parisienne&family=Pinyon+Script&family=Playfair+Display:wght@400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&family=Roboto:wght@300;400;500;700;900&family=Sacramento&family=Satisfy&family=Tangerine:wght@400;700&family=Yellowtail&display=swap';

type LayerType = 'text' | 'image' | 'rect' | 'ellipse' | 'line';
interface Layer {
    id: number; type: LayerType; x: number; y: number; rotation: number;
    // text
    text: string; fontFamily: string; fontSize: number; weight: number; color: string;
    align: CanvasTextAlign; shadow: boolean; letterSpacing: number; field: string;
    // optional text box: 0 means "hug the text", otherwise the text wraps and aligns inside it
    boxW: number; boxH: number; vAlign: VerticalAlign;
    // image
    imgId: number | null; w: number; h: number; naturalRatio: number;
    // shape
    opacity: number; radius: number; strokeW: number; strokeColor: string; curve: number; fillEnabled: boolean;
    radiusTL: number; radiusTR: number; radiusBR: number; radiusBL: number;
    // built-in icon / certification badge — kept so the layer can be recoloured later
    iconKey?: string; iconColor?: string;
}

let uid = 1;
const layers = ref<Layer[]>([]);
const selectedIds = ref<number[]>([]);
const bgColor = ref('#0d3b34');
const bgSrc = ref<string | null>(null);
const bgImage = ref<HTMLImageElement | null>(null);
// How the background photo is framed: 1 = fill the canvas, offsets pan from centre.
const bgScale = ref(1);
const bgOffsetX = ref(0);
const bgOffsetY = ref(0);
/** While on, dragging the canvas pans the background instead of moving layers. */
const bgAdjust = ref(false);
function resetBackgroundFraming() { bgScale.value = 1; bgOffsetX.value = 0; bgOffsetY.value = 0; render(); }
function nudgeBackground(dx: number, dy: number) { bgOffsetX.value += dx; bgOffsetY.value += dy; render(); }
const fieldValues = reactive<Record<string, string>>({});
const imgStore: Record<number, { img: HTMLImageElement; src: string }> = {};
const logoIds = reactive<{ light: number | null; dark: number | null }>({ light: null, dark: null });

const templateList = ref<TemplateSummary[]>([...props.templates]);
const currentTemplateId = ref<number | null>(null);
const currentName = ref('');
const currentCategory = ref<'gold_price' | 'poster'>('gold_price');
const currentType = ref('');
const currentIsGlobal = ref(false);
/** A default design opened by a tenant: editable on screen, but never saved over or deleted. */
const isReadOnlyTemplate = computed(() => currentIsGlobal.value && !props.adminMode);

interface StatusConfig { mode: 'text' | 'icon' | 'both'; increaseText: string; decreaseText: string; icon: string; order: 'icon-first' | 'text-first' }
function defaultStatusConfig(): StatusConfig {
    return { mode: 'text', increaseText: 'Gold price up ₹{diff}/gram', decreaseText: 'Gold price down ₹{diff}/gram', icon: DEFAULT_STATUS_ICON_SET, order: 'icon-first' };
}
const statusConfig = reactive<StatusConfig>(defaultStatusConfig());

const canvasRef = ref<HTMLCanvasElement | null>(null);
const started = ref(false);
const snapEnabled = ref(true);
const showGrid = ref(false);
const guideX = ref<number | null>(null);
const guideY = ref<number | null>(null);

// ── Canvas zoom ──────────────────────────────────────────────────────
// The canvas always renders at its full pixel size; only its on-screen size changes,
// so nothing about the exported poster depends on the zoom level.
const viewportRef = ref<HTMLElement | null>(null);
/** Screen scale at which the whole canvas fits the visible area — the zoom baseline. */
const fitScale = ref(1);
/** Zoom on top of the fit scale: 1 = fit to screen. */
const zoom = ref(1);
const viewScale = computed(() => fitScale.value * zoom.value);
const canvasStyle = computed(() => ({
    width: `${Math.round(canvasW.value * viewScale.value)}px`,
    height: `${Math.round(canvasH.value * viewScale.value)}px`,
}));
let viewportObserver: ResizeObserver | null = null;

function measureFit() {
    const el = viewportRef.value; if (!el) return;
    const s = getComputedStyle(el);
    const w = el.clientWidth - parseFloat(s.paddingLeft) - parseFloat(s.paddingRight);
    const h = el.clientHeight - parseFloat(s.paddingTop) - parseFloat(s.paddingBottom);
    if (w > 0 && h > 0) fitScale.value = Math.min(w / canvasW.value, h / canvasH.value);
}
function setZoom(z: number) { zoom.value = Math.min(8, Math.max(0.25, z)); }
function zoomBy(factor: number) { setZoom(zoom.value * factor); }
function zoomToFit() { zoom.value = 1; }
function onWheel(e: WheelEvent) {
    // Plain scrolling still pans the viewport; only pinch/ctrl-scroll zooms.
    if (!e.ctrlKey && !e.metaKey) return;
    e.preventDefault();
    zoomBy(e.deltaY < 0 ? 1.1 : 1 / 1.1);
}

// ── User-placed guides ───────────────────────────────────────────────
// Ruler guides the user drops on the canvas: layers snap to them, and they are saved
// with the template so a design's layout rules survive a reload.
const guidesV = ref<number[]>([]);
const guidesH = ref<number[]>([]);
const guideDrag = reactive({ axis: null as null | 'v' | 'h', index: -1 });
const guideCount = computed(() => guidesV.value.length + guidesH.value.length);

function addGuide(axis: 'v' | 'h') {
    if (axis === 'v') { guidesV.value = [...guidesV.value, Math.round(canvasW.value / 2)]; }
    else { guidesH.value = [...guidesH.value, Math.round(canvasH.value / 2)]; }
    render();
}
function clearGuides() { guidesV.value = []; guidesH.value = []; render(); }
/** Grab distance in canvas units that stays a constant size on screen at any zoom. */
function guideTolerance() { return 10 / Math.max(0.05, viewScale.value); }
function findGuide(p: { x: number; y: number }) {
    const t = guideTolerance();
    const v = guidesV.value.findIndex((g) => Math.abs(g - p.x) <= t);
    if (v >= 0) return { axis: 'v' as const, index: v };
    const h = guidesH.value.findIndex((g) => Math.abs(g - p.y) <= t);
    if (h >= 0) return { axis: 'h' as const, index: h };
    return null;
}
function moveGuide(p: { x: number; y: number }) {
    if (guideDrag.axis === 'v') { const a = [...guidesV.value]; a[guideDrag.index] = Math.round(p.x); guidesV.value = a; }
    else { const a = [...guidesH.value]; a[guideDrag.index] = Math.round(p.y); guidesH.value = a; }
    render();
}
/** Dragging a guide off the canvas removes it, the way design tools do. */
function dropGuide() {
    if (guideDrag.axis === 'v') {
        const x = guidesV.value[guideDrag.index];
        if (x < 0 || x > canvasW.value) guidesV.value = guidesV.value.filter((_, i) => i !== guideDrag.index);
    } else if (guideDrag.axis === 'h') {
        const y = guidesH.value[guideDrag.index];
        if (y < 0 || y > canvasH.value) guidesH.value = guidesH.value.filter((_, i) => i !== guideDrag.index);
    }
    guideDrag.axis = null; guideDrag.index = -1;
}

// Keep the canvas preview of the "status" field in sync with the configured wording/icon —
// otherwise picking "Icon only" appears to do nothing since the sample text never changes.
watch(statusConfig, () => { fieldValues.status = buildStatusMessage(statusConfig, 'up', 50); render(); }, { deep: true });

const selectedLayers = computed(() => layers.value.filter((l) => selectedIds.value.includes(l.id)));
const selected = computed(() => (selectedLayers.value.length === 1 ? selectedLayers.value[0] : null));
const fields = computed(() => {
    const out: string[] = []; const seen = new Set<string>();
    for (const l of layers.value) if (l.type === 'text' && l.field && !seen.has(l.field)) { seen.add(l.field); out.push(l.field); }
    return out;
});

function displayText(l: Layer): string {
    let t: unknown = l.text;
    if (l.field) { const v = fieldValues[l.field]; if (v !== undefined && v !== null && v !== '') t = v; }
    return t == null ? '' : String(t);
}

// ── Image loading ────────────────────────────────────────────────────
function loadImage(src: string): Promise<HTMLImageElement> {
    return new Promise((res, rej) => { const img = new Image(); img.onload = () => res(img); img.onerror = rej; img.src = src; });
}
async function loadLogo(url: string): Promise<HTMLImageElement> {
    if (!/\.svg(\?|$)/i.test(url)) return loadImage(url);
    try {
        const raw = await (await fetch(url)).text();
        const svg = new DOMParser().parseFromString(raw, 'image/svg+xml').documentElement;
        if (svg.nodeName.toLowerCase() === 'svg') {
            const vb = (svg.getAttribute('viewBox') ?? '').split(/[\s,]+/).map(Number);
            const ok = vb.length === 4 && vb.every((n) => Number.isFinite(n));
            const w = (svg.getAttribute('width') ?? '').replace('px', ''); const h = (svg.getAttribute('height') ?? '').replace('px', '');
            if (!w || w.includes('%')) svg.setAttribute('width', ok ? String(vb[2]) : '512');
            if (!h || h.includes('%')) svg.setAttribute('height', ok ? String(vb[3]) : '512');
        }
        return await loadImage(URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml' })));
    } catch { return loadImage(url); }
}
function toDataUrl(img: HTMLImageElement): string {
    const c = document.createElement('canvas');
    c.width = img.naturalWidth || img.width || 300; c.height = img.naturalHeight || img.height || 300;
    c.getContext('2d')!.drawImage(img, 0, 0, c.width, c.height);
    try { return c.toDataURL('image/png'); } catch { return ''; }
}
function registerImage(img: HTMLImageElement, src?: string): number {
    const id = uid++; imgStore[id] = { img, src: src ?? toDataUrl(img) }; return id;
}
function readFile(file: File): Promise<string> {
    return new Promise((res, rej) => { const r = new FileReader(); r.onload = () => res(r.result as string); r.onerror = rej; r.readAsDataURL(file); });
}

// ── Layer factory + add ──────────────────────────────────────────────
function makeLayer(p: Partial<Layer>): Layer {
    return {
        id: uid++, type: 'text', x: canvasW.value / 2, y: canvasH.value / 2, rotation: 0,
        text: 'New text', fontFamily: 'Poppins', fontSize: 48, weight: 600, color: '#ffffff',
        align: 'center', shadow: true, letterSpacing: 0, field: '',
        boxW: 0, boxH: 0, vAlign: 'middle',
        imgId: null, w: 0, h: 0, naturalRatio: 1,
        opacity: 1, radius: 0, strokeW: 0, strokeColor: '#ffffff', curve: 0, fillEnabled: true,
        radiusTL: 0, radiusTR: 0, radiusBR: 0, radiusBL: 0, ...p,
    };
}
function push(l: Layer) { started.value = true; layers.value.push(l); selectedIds.value = [l.id]; render(); }
function addText() { push(makeLayer({ text: 'New text' })); }
function addShape(type: LayerType) {
    if (type === 'rect') push(makeLayer({ type, w: 460, h: 240, color: '#d4af37', radiusTL: 16, radiusTR: 16, radiusBR: 16, radiusBL: 16 }));
    else if (type === 'ellipse') push(makeLayer({ type, w: 320, h: 320, color: '#d4af37' }));
    else push(makeLayer({ type: 'line', w: 480, h: 6, color: '#d4af37' }));
}
function addImageLayer(imgId: number, y = canvasH.value / 2) {
    const img = imgStore[imgId].img; const nat = (img.naturalWidth || 300) / (img.naturalHeight || 300);
    push(makeLayer({ type: 'image', imgId, w: 380, h: 380 / nat, naturalRatio: nat, y }));
}

// ── Icons & certification badges ─────────────────────────────────────
const iconPickerOpen = ref(false);
const iconGroup = ref(POSTER_ICON_GROUPS[0].key);
const iconColor = ref(DEFAULT_ICON_COLOR);
const iconGroupIcons = computed(() => POSTER_ICON_GROUPS.find((g) => g.key === iconGroup.value)?.icons ?? []);

function iconPreview(key: string): string {
    return posterIconDataUrl(key, iconColor.value) ?? '';
}

async function addIcon(key: string) {
    const src = posterIconDataUrl(key, iconColor.value);
    if (!src) return;
    const img = await loadImage(src);
    const nat = (img.naturalWidth || 120) / (img.naturalHeight || 120);
    // Badges read best around a third of the canvas width; square icons stay small.
    const w = nat > 1.4 ? 380 : 150;
    push(makeLayer({ type: 'image', imgId: registerImage(img, src), w, h: w / nat, naturalRatio: nat, iconKey: key, iconColor: iconColor.value }));
    iconPickerOpen.value = false;
}

/** Re-renders the selected icon in a new colour, keeping its position and size. */
async function recolorIcon(layer: Layer, color: string) {
    if (!layer.iconKey) return;
    const src = posterIconDataUrl(layer.iconKey, color);
    if (!src) return;
    const img = await loadImage(src);
    layer.imgId = registerImage(img, src);
    layer.iconColor = color;
    render();
}
async function onAddImage(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]; if (!file) return;
    const src = await readFile(file); const img = await loadImage(src); addImageLayer(registerImage(img, src));
    (e.target as HTMLInputElement).value = '';
}
function addLogo(which: 'light' | 'dark') { const id = logoIds[which]; if (id != null) addImageLayer(id, 270); }
async function onBackground(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]; if (!file) return;
    bgSrc.value = await readFile(file); bgImage.value = await loadImage(bgSrc.value);
    resetBackgroundFraming();
    (e.target as HTMLInputElement).value = '';
}
function removeBackground() { bgImage.value = null; bgSrc.value = null; bgAdjust.value = false; resetBackgroundFraming(); }

// ── Background library (admin-managed jewellery images) ──────────────
// Tenant-side only: the admin panel manages this library under Backgrounds and designs
// templates without picking one, so the picker never appears in admin mode.
const backgroundLibrary = computed<BackgroundOption[]>(() => (props.adminMode ? [] : props.backgrounds ?? []));
const libraryOpen = ref(false);
const librarySearch = ref('');
const libraryCategory = ref<string>('all');
const libraryCategories = computed(() => ['all', ...new Set(backgroundLibrary.value.map((b) => b.category ?? 'Uncategorized'))]);
const libraryItems = computed(() => {
    const q = librarySearch.value.trim().toLowerCase();
    return backgroundLibrary.value.filter((b) => {
        if (libraryCategory.value !== 'all' && (b.category ?? 'Uncategorized') !== libraryCategory.value) return false;
        if (!q) return true;
        return `${b.name} ${b.category ?? ''}`.toLowerCase().includes(q);
    });
});
watch(libraryOpen, (open) => { if (open) librarySearch.value = ''; });

async function applyLibraryBackground(background: BackgroundOption) {
    try {
        const img = await loadImage(background.url);
        bgSrc.value = background.url;
        bgImage.value = img;
        libraryOpen.value = false;
        resetBackgroundFraming();
    } catch {
        modals.alert('Could not load that background image.', { title: 'Load failed' });
    }
}

function deleteLayer(id: number) { layers.value = layers.value.filter((l) => l.id !== id); selectedIds.value = selectedIds.value.filter((s) => s !== id); render(); }
function duplicateLayer(l: Layer) { const c = { ...l, id: uid++, x: l.x + 40, y: l.y + 40 }; layers.value.push(c); selectedIds.value = [c.id]; render(); }
// ── Text boxes ───────────────────────────────────────────────────────
// Without a box a text layer hugs its own glyphs, so alignment has nothing to align
// against. Giving it a box makes left/centre/right and top/middle/bottom meaningful,
// and lets long text wrap instead of running off the canvas.
function toggleTextBox(l: Layer) {
    const ctx = getCtx(); if (!ctx) return;
    if (l.boxW) { l.boxW = 0; l.boxH = 0; }
    else {
        const s = contentSize(l, ctx);
        l.boxW = Math.min(canvasW.value, Math.round(s.w) + 60);
        l.boxH = Math.round(s.h) + 30;
    }
    render();
}
/** Stretches the box across the canvas with a margin, and centres it — the common case. */
function fitTextBoxToCanvas(l: Layer, margin = 60) {
    const ctx = getCtx(); if (!ctx) return;
    l.boxW = Math.max(60, canvasW.value - margin * 2);
    if (!l.boxH) l.boxH = Math.round(contentSize(l, ctx).h) + 30;
    l.x = canvasW.value / 2;
    render();
}
function setTextBox(l: Layer, axis: 'boxW' | 'boxH', value: string) {
    const n = Math.max(20, Math.round(Number(value) || 0));
    l[axis] = n;
    render();
}

function setImgW(v: string) { const l = selected.value; if (!l) return; const w = Math.max(20, Number(v) || 0); l.w = w; l.h = Math.max(20, Math.round(w / (l.naturalRatio || 1))); render(); }
function setImgH(v: string) { const l = selected.value; if (!l) return; const h = Math.max(20, Number(v) || 0); l.h = h; l.w = Math.max(20, Math.round(h * (l.naturalRatio || 1))); render(); }

const dragLayerId = ref<number | null>(null);
function onLayerDragStart(l: Layer) { dragLayerId.value = l.id; }
function onLayerDrop(target: Layer) {
    const from = layers.value.findIndex((x) => x.id === dragLayerId.value);
    const to = layers.value.findIndex((x) => x.id === target.id);
    dragLayerId.value = null;
    if (from < 0 || to < 0 || from === to) return;
    const [moved] = layers.value.splice(from, 1);
    layers.value.splice(to, 0, moved);
    render();
}

function reorder(l: Layer, dir: 1 | -1) { const i = layers.value.indexOf(l); const j = i + dir; if (j < 0 || j >= layers.value.length) return; layers.value.splice(i, 1); layers.value.splice(j, 0, l); render(); }

// ── Copy / paste style ───────────────────────────────────────────────
// The rules live in ./styleClipboard so they can be reasoned about on their own; this
// side only supplies the layers and redraws icons, which are rasterised images.
const copiedStyle = ref<CopiedStyle | null>(null);
const copiedStyleLabel = computed(() => (copiedStyle.value ? `${copiedStyle.value.family} style copied` : ''));
/** Short-lived note under the buttons: says what was copied, and what a paste actually did. */
const styleFeedback = ref('');
let styleFeedbackTimer: ReturnType<typeof setTimeout> | null = null;
function flashStyleFeedback(message: string) {
    styleFeedback.value = message;
    if (styleFeedbackTimer) clearTimeout(styleFeedbackTimer);
    styleFeedbackTimer = setTimeout(() => { styleFeedback.value = ''; styleFeedbackTimer = null; }, 3000);
}

function copyStyle() {
    // Falls back to the first of a multi-selection so the button is never a dead end.
    const l = selected.value ?? selectedLayers.value[0];
    if (!l) { flashStyleFeedback('Select a layer first.'); return; }
    copiedStyle.value = copyLayerStyle(l);
    flashStyleFeedback(`Copied this ${l.type}'s style.`);
}

async function applyStyleTo(targets: Layer[]) {
    if (!copiedStyle.value) { flashStyleFeedback('Copy a style first.'); return; }
    if (!targets.length) { flashStyleFeedback('Select the layers to paste onto.'); return; }
    let changed = 0;
    for (const l of targets) {
        const result = applyLayerStyle(copiedStyle.value, l);
        if (result.recolorIconTo) await recolorIcon(l, result.recolorIconTo);
        if (result.changed) changed++;
    }
    render();
    flashStyleFeedback(changed
        ? `Style applied to ${changed} layer${changed === 1 ? '' : 's'}.`
        : 'Those layers already match the copied style.');
}
function pasteStyle() { applyStyleTo(selectedLayers.value); }
function linkCorners() {
    const l = selected.value; if (!l) return;
    const r = l.radiusTL; l.radiusTR = r; l.radiusBR = r; l.radiusBL = r; render();
}
function defaultPlaceholder(): string {
    const list = props.placeholders ?? [];
    return (list.find((p) => p.category === currentCategory.value) ?? list[0])?.key ?? 'customer_name';
}
function toggleField(l: Layer) {
    if (l.field) { l.field = ''; } else {
        const key = defaultPlaceholder();
        l.field = key; if (fieldValues[key] === undefined) fieldValues[key] = l.text;
    }
    render();
}
function onFieldSelect(l: Layer) { if (l.field && fieldValues[l.field] === undefined) fieldValues[l.field] = l.text; render(); }
function phCatLabel(c: string): string { return c === 'gold_price' ? 'Gold price' : c === 'poster' ? 'Poster' : 'Common'; }
const placeholderGroups = computed(() => {
    const groups: Record<string, PlaceholderOption[]> = {};
    for (const p of props.placeholders ?? []) (groups[p.category] ??= []).push(p);
    const order = ['common', currentCategory.value, currentCategory.value === 'poster' ? 'gold_price' : 'poster'];
    return Object.entries(groups).sort((a, b) => (order.indexOf(a[0]) + 1 || 99) - (order.indexOf(b[0]) + 1 || 99));
});

// ── Geometry ─────────────────────────────────────────────────────────
function getCtx() { return canvasRef.value?.getContext('2d') ?? null; }
function contentSize(l: Layer, ctx: CanvasRenderingContext2D) {
    if (l.type === 'line') return { w: l.w, h: Math.max(l.h, Math.abs(l.curve) + l.h) };
    if (l.type !== 'text') return { w: l.w, h: l.h };
    const t = layoutText(ctx, l, displayText(l));
    return { w: t.w, h: t.h };
}
function toLocal(l: Layer, px: number, py: number) {
    const a = -(l.rotation || 0) * Math.PI / 180; const dx = px - l.x; const dy = py - l.y;
    return { x: dx * Math.cos(a) - dy * Math.sin(a), y: dx * Math.sin(a) + dy * Math.cos(a) };
}
function localToWorld(l: Layer, lx: number, ly: number) {
    const a = (l.rotation || 0) * Math.PI / 180;
    return { x: l.x + lx * Math.cos(a) - ly * Math.sin(a), y: l.y + lx * Math.sin(a) + ly * Math.cos(a) };
}
function roundRectCorners(ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, tl: number, tr: number, br: number, bl: number) {
    const m = Math.min(w, h) / 2; const c = (v: number) => Math.max(0, Math.min(v || 0, m));
    tl = c(tl); tr = c(tr); br = c(br); bl = c(bl);
    ctx.beginPath();
    ctx.moveTo(x + tl, y);
    ctx.lineTo(x + w - tr, y); ctx.arcTo(x + w, y, x + w, y + tr, tr);
    ctx.lineTo(x + w, y + h - br); ctx.arcTo(x + w, y + h, x + w - br, y + h, br);
    ctx.lineTo(x + bl, y + h); ctx.arcTo(x, y + h, x, y + h - bl, bl);
    ctx.lineTo(x, y + tl); ctx.arcTo(x, y, x + tl, y, tl);
    ctx.closePath();
}

// ── Rendering ────────────────────────────────────────────────────────
function drawLayer(ctx: CanvasRenderingContext2D, l: Layer) {
    if (l.type === 'image') {
        const im = l.imgId != null ? imgStore[l.imgId]?.img : null;
        if (im) {
            const scale = Math.min(l.w / im.width, l.h / im.height);
            const dw = im.width * scale;
            const dh = im.height * scale;
            ctx.drawImage(im, -dw / 2, -dh / 2, dw, dh);
        }
        return;
    }
    if (l.type === 'text') {
        ctx.save();
        if (l.shadow) { ctx.shadowColor = 'rgba(0,0,0,0.55)'; ctx.shadowBlur = 12; }
        ctx.font = `${l.weight} ${l.fontSize}px "${l.fontFamily}", sans-serif`;
        ctx.fillStyle = l.color; ctx.textBaseline = 'middle';
        const t = layoutText(ctx, l, displayText(l));
        // re-apply after layoutText (which resets letterSpacing while measuring)
        ctx.font = `${l.weight} ${l.fontSize}px "${l.fontFamily}", sans-serif`;
        try { ctx.letterSpacing = `${l.letterSpacing}px`; } catch { /* unsupported */ }
        ctx.textAlign = l.align;
        let ty = t.startY;
        for (const line of t.lines) { ctx.fillText(line, t.tx, ty); ty += t.lineH; }
        ctx.restore(); return;
    }
    ctx.save(); ctx.globalAlpha = l.opacity;
    if (l.type === 'line') {
        ctx.strokeStyle = l.color; ctx.lineWidth = Math.max(1, l.h); ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(-l.w / 2, 0);
        if (l.curve) ctx.quadraticCurveTo(0, -l.curve, l.w / 2, 0); else ctx.lineTo(l.w / 2, 0);
        ctx.stroke(); ctx.restore(); return;
    }
    if (l.type === 'ellipse') { ctx.beginPath(); ctx.ellipse(0, 0, l.w / 2, l.h / 2, 0, 0, Math.PI * 2); }
    else { roundRectCorners(ctx, -l.w / 2, -l.h / 2, l.w, l.h, l.radiusTL, l.radiusTR, l.radiusBR, l.radiusBL); }
    if (l.fillEnabled) { ctx.fillStyle = l.color; ctx.fill(); }
    if (l.strokeW > 0) { ctx.globalAlpha = 1; ctx.lineWidth = l.strokeW; ctx.strokeStyle = l.strokeColor; ctx.stroke(); }
    ctx.restore();
}

function render(showChrome = true) {
    const canvas = canvasRef.value; const ctx = canvas?.getContext('2d'); if (!canvas || !ctx) return;
    ctx.fillStyle = bgColor.value; ctx.fillRect(0, 0, canvasW.value, canvasH.value);
    if (bgImage.value) {
        drawBackground(ctx, bgImage.value, canvasW.value, canvasH.value, { color: bgColor.value, src: bgSrc.value, scale: bgScale.value, offsetX: bgOffsetX.value, offsetY: bgOffsetY.value });
    }

    for (const l of layers.value) {
        ctx.save(); ctx.translate(l.x, l.y); ctx.rotate((l.rotation || 0) * Math.PI / 180);
        drawLayer(ctx, l);
        if (showChrome && selectedIds.value.includes(l.id)) {
            const s = contentSize(l, ctx);
            ctx.strokeStyle = '#3b82f6'; ctx.lineWidth = 3; ctx.setLineDash([10, 8]);
            ctx.strokeRect(-s.w / 2 - 8, -s.h / 2 - 8, s.w + 16, s.h + 16); ctx.setLineDash([]);
            if (selectedIds.value.length === 1) {
                ctx.fillStyle = '#ffffff'; ctx.strokeStyle = '#3b82f6'; ctx.lineWidth = 3;
                ctx.beginPath(); ctx.arc(s.w / 2 + 8, s.h / 2 + 8, HANDLE / 2, 0, Math.PI * 2); ctx.fill(); ctx.stroke();
            }
        }
        ctx.restore();
    }

    if (showChrome) drawGuides(ctx);
}

// ── Pointer interaction ──────────────────────────────────────────────
const drag = reactive({ mode: null as null | 'move' | 'resize' | 'bg', startDist: 1, startFont: 0, startW: 0, startH: 0, items: [] as { l: Layer; x0: number; y0: number }[], px0: 0, py0: 0, bgX0: 0, bgY0: 0 });
function toCanvas(e: PointerEvent) { const r = canvasRef.value!.getBoundingClientRect(); return { x: (e.clientX - r.left) * (canvasW.value / r.width), y: (e.clientY - r.top) * (canvasH.value / r.height) }; }
function hitLayer(p: { x: number; y: number }, ctx: CanvasRenderingContext2D) {
    for (let i = layers.value.length - 1; i >= 0; i--) {
        const l = layers.value[i]; const s = contentSize(l, ctx); const loc = toLocal(l, p.x, p.y);
        if (Math.abs(loc.x) <= s.w / 2 + 8 && Math.abs(loc.y) <= s.h / 2 + 8) return l;
    }
    return null;
}
// Two fingers on the canvas pinch-zoom instead of dragging a layer.
const activePointers = new Map<number, { x: number; y: number }>();
const pinch = { dist: 0, zoom: 1 };
function pointerSpread() {
    const [a, b] = [...activePointers.values()];
    return Math.hypot(a.x - b.x, a.y - b.y);
}

function onPointerDown(e: PointerEvent) {
    const ctx = getCtx(); if (!ctx) return; const p = toCanvas(e);
    activePointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    if (activePointers.size === 2) {
        drag.mode = null; drag.items = []; guideDrag.axis = null;
        pinch.dist = Math.max(1, pointerSpread()); pinch.zoom = zoom.value;
        return;
    }
    if (activePointers.size > 2) return;
    // Repositioning the background takes over the whole canvas — layers stay put.
    if (bgAdjust.value && bgImage.value) {
        drag.mode = 'bg'; drag.px0 = p.x; drag.py0 = p.y; drag.bgX0 = bgOffsetX.value; drag.bgY0 = bgOffsetY.value;
        canvasRef.value!.setPointerCapture(e.pointerId); return;
    }
    if (selected.value) {
        const l = selected.value; const s = contentSize(l, ctx); const loc = toLocal(l, p.x, p.y);
        if (Math.hypot(loc.x - (s.w / 2 + 8), loc.y - (s.h / 2 + 8)) <= HANDLE) {
            drag.mode = 'resize'; drag.startDist = Math.max(20, Math.hypot(p.x - l.x, p.y - l.y));
            drag.startFont = l.fontSize; drag.startW = l.w; drag.startH = l.h;
            canvasRef.value!.setPointerCapture(e.pointerId); return;
        }
    }
    const g = findGuide(p);
    if (g) {
        guideDrag.axis = g.axis; guideDrag.index = g.index;
        canvasRef.value!.setPointerCapture(e.pointerId); return;
    }
    const hit = hitLayer(p, ctx);
    if (hit) {
        if (e.shiftKey) { selectedIds.value = selectedIds.value.includes(hit.id) ? selectedIds.value.filter((s) => s !== hit.id) : [...selectedIds.value, hit.id]; render(); return; }
        if (!selectedIds.value.includes(hit.id)) selectedIds.value = [hit.id];
        drag.mode = 'move'; drag.px0 = p.x; drag.py0 = p.y;
        drag.items = selectedLayers.value.map((l) => ({ l, x0: l.x, y0: l.y }));
        canvasRef.value!.setPointerCapture(e.pointerId); render(); return;
    }
    if (!e.shiftKey) selectedIds.value = []; render();
}
function onPointerMove(e: PointerEvent) {
    if (activePointers.has(e.pointerId)) activePointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    if (activePointers.size === 2) { setZoom(pinch.zoom * (pointerSpread() / pinch.dist)); return; }
    if (guideDrag.axis) { moveGuide(toCanvas(e)); return; }
    if (!drag.mode) return; const p = toCanvas(e);
    if (drag.mode === 'bg') { bgOffsetX.value = Math.round(drag.bgX0 + (p.x - drag.px0)); bgOffsetY.value = Math.round(drag.bgY0 + (p.y - drag.py0)); render(); return; }
    if (drag.mode === 'move') { const dx = p.x - drag.px0; const dy = p.y - drag.py0; for (const it of drag.items) { it.l.x = Math.round(it.x0 + dx); it.l.y = Math.round(it.y0 + dy); } const ctx = getCtx(); if (ctx) applySnap(ctx); }
    else if (selected.value) {
        const l = selected.value;
        if (l.type === 'text') {
            if (l.boxW) {
                // A boxed text layer resizes its box; the type size stays put.
                const loc = toLocal(l, p.x, p.y);
                l.boxW = Math.max(40, Math.round(Math.abs(loc.x) * 2));
                l.boxH = Math.max(Math.round(l.fontSize * 1.25), Math.round(Math.abs(loc.y) * 2));
            } else {
                const ratio = Math.hypot(p.x - l.x, p.y - l.y) / drag.startDist;
                l.fontSize = Math.min(600, Math.max(10, Math.round(drag.startFont * ratio)));
            }
        } else {
            const loc = toLocal(l, p.x, p.y);
            if (l.type === 'image') {
                // images resize proportionally (keep aspect ratio)
                const w = Math.max(20, Math.round(Math.abs(loc.x) * 2));
                l.w = w; l.h = Math.max(20, Math.round(w / (l.naturalRatio || 1)));
            } else {
                // shapes resize freely (W/H independent)
                l.w = Math.max(l.type === 'line' ? 10 : 20, Math.round(Math.abs(loc.x) * 2));
                l.h = Math.max(l.type === 'line' ? 2 : 20, Math.round(Math.abs(loc.y) * 2));
            }
        }
    }
    render();
}
function onPointerUp(e?: PointerEvent) {
    if (e) activePointers.delete(e.pointerId);
    if (guideDrag.axis) dropGuide();
    drag.mode = null; drag.items = []; guideX.value = null; guideY.value = null; render();
}

// ── Snapping + rulers ────────────────────────────────────────────────
function groupBox(ctx: CanvasRenderingContext2D) {
    const bs = selectedLayers.value.map((l) => { const s = contentSize(l, ctx); return { left: l.x - s.w / 2, right: l.x + s.w / 2, top: l.y - s.h / 2, bottom: l.y + s.h / 2 }; });
    const left = Math.min(...bs.map((b) => b.left)); const right = Math.max(...bs.map((b) => b.right));
    const top = Math.min(...bs.map((b) => b.top)); const bottom = Math.max(...bs.map((b) => b.bottom));
    return { left, right, top, bottom, cx: (left + right) / 2, cy: (top + bottom) / 2 };
}
function applySnap(ctx: CanvasRenderingContext2D) {
    guideX.value = null; guideY.value = null;
    if (!snapEnabled.value || !selectedLayers.value.length) return;
    const T = 8;
    const xT = [0, canvasW.value / 2, canvasW.value, ...guidesV.value];
    const yT = [0, canvasH.value / 2, canvasH.value, ...guidesH.value];
    for (const l of layers.value) { if (selectedIds.value.includes(l.id)) continue; const s = contentSize(l, ctx); xT.push(l.x - s.w / 2, l.x, l.x + s.w / 2); yT.push(l.y - s.h / 2, l.y, l.y + s.h / 2); }
    const b = groupBox(ctx);
    let dx = 0, dy = 0, bestX = T + 1, bestY = T + 1;
    for (const c of [b.left, b.cx, b.right]) for (const t of xT) { const d = Math.abs(c - t); if (d < bestX) { bestX = d; dx = t - c; guideX.value = t; } }
    for (const c of [b.top, b.cy, b.bottom]) for (const t of yT) { const d = Math.abs(c - t); if (d < bestY) { bestY = d; dy = t - c; guideY.value = t; } }
    if (bestX > T) { dx = 0; guideX.value = null; }
    if (bestY > T) { dy = 0; guideY.value = null; }
    if (dx || dy) for (const it of drag.items) { it.l.x = Math.round(it.l.x + dx); it.l.y = Math.round(it.l.y + dy); }
}
function drawGuides(ctx: CanvasRenderingContext2D) {
    if (showGrid.value) {
        ctx.save();
        const step = canvasW.value / 12;
        ctx.strokeStyle = 'rgba(255,255,255,0.08)'; ctx.lineWidth = 1;
        for (let x = step; x < canvasW.value; x += step) { ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, canvasH.value); ctx.stroke(); }
        for (let y = step; y < canvasH.value; y += step) { ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(canvasW.value, y); ctx.stroke(); }
        ctx.strokeStyle = 'rgba(212,175,55,0.35)'; ctx.setLineDash([12, 10]);
        ctx.beginPath(); ctx.moveTo(canvasW.value / 2, 0); ctx.lineTo(canvasW.value / 2, canvasH.value); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(0, canvasH.value / 2); ctx.lineTo(canvasW.value, canvasH.value / 2); ctx.stroke();
        ctx.setLineDash([]);
        ctx.strokeStyle = 'rgba(255,255,255,0.55)'; ctx.lineWidth = 2;
        for (let x = 0; x <= canvasW.value; x += step) { const c = Math.abs(x - canvasW.value / 2) < 1; ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, c ? 34 : 20); ctx.stroke(); }
        for (let y = 0; y <= canvasH.value; y += step) { const c = Math.abs(y - canvasH.value / 2) < 1; ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(c ? 34 : 20, y); ctx.stroke(); }
        ctx.restore();
    }
    if (guidesV.value.length || guidesH.value.length) {
        ctx.save();
        // Keep guides a constant thickness on screen however far the canvas is zoomed.
        ctx.strokeStyle = 'rgba(56,189,248,0.95)'; ctx.lineWidth = Math.max(1, 2 / Math.max(0.05, viewScale.value));
        for (const x of guidesV.value) { ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, canvasH.value); ctx.stroke(); }
        for (const y of guidesH.value) { ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(canvasW.value, y); ctx.stroke(); }
        ctx.restore();
    }
    if (guideX.value != null || guideY.value != null) {
        ctx.save(); ctx.strokeStyle = '#ec4899'; ctx.lineWidth = 2; ctx.setLineDash([9, 7]);
        if (guideX.value != null) { ctx.beginPath(); ctx.moveTo(guideX.value, 0); ctx.lineTo(guideX.value, canvasH.value); ctx.stroke(); }
        if (guideY.value != null) { ctx.beginPath(); ctx.moveTo(0, guideY.value); ctx.lineTo(canvasW.value, guideY.value); ctx.stroke(); }
        ctx.setLineDash([]); ctx.restore();
    }
}

// ── Alignment ────────────────────────────────────────────────────────
function align(kind: 'left' | 'hcenter' | 'right' | 'top' | 'vmiddle' | 'bottom') {
    const ctx = getCtx(); if (!ctx || !selectedLayers.value.length) return;
    const boxes = selectedLayers.value.map((l) => { const s = contentSize(l, ctx); return { l, hw: s.w / 2, hh: s.h / 2 }; });
    if (boxes.length === 1) {
        const b = boxes[0];
        if (kind === 'left') b.l.x = b.hw + 40; if (kind === 'hcenter') b.l.x = canvasW.value / 2; if (kind === 'right') b.l.x = canvasW.value - b.hw - 40;
        if (kind === 'top') b.l.y = b.hh + 40; if (kind === 'vmiddle') b.l.y = canvasH.value / 2; if (kind === 'bottom') b.l.y = canvasH.value - b.hh - 40;
    } else {
        const left = Math.min(...boxes.map((b) => b.l.x - b.hw)); const right = Math.max(...boxes.map((b) => b.l.x + b.hw));
        const top = Math.min(...boxes.map((b) => b.l.y - b.hh)); const bottom = Math.max(...boxes.map((b) => b.l.y + b.hh));
        for (const b of boxes) {
            if (kind === 'left') b.l.x = left + b.hw; if (kind === 'hcenter') b.l.x = (left + right) / 2; if (kind === 'right') b.l.x = right - b.hw;
            if (kind === 'top') b.l.y = top + b.hh; if (kind === 'vmiddle') b.l.y = (top + bottom) / 2; if (kind === 'bottom') b.l.y = bottom - b.hh;
        }
    }
    render();
}

// ── Export ───────────────────────────────────────────────────────────
function downloadPoster() {
    const canvas = canvasRef.value; if (!canvas) return; render(false);
    canvas.toBlob((blob) => { render(true); if (!blob) return; const u = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = u; a.download = `gold-rate-${new Date().toISOString().slice(0, 10)}.png`; a.click(); URL.revokeObjectURL(u); }, 'image/png');
}

// ── Templates ────────────────────────────────────────────────────────
function serialize() {
    const images: Record<number, string> = {};
    // The tenant-logo slot is always resolved dynamically at load time — never bake a specific logo into the saved document.
    for (const l of layers.value) if (l.type === 'image' && l.imgId != null && l.imgId !== TENANT_LOGO_IMAGE_ID && imgStore[l.imgId]) images[l.imgId] = imgStore[l.imgId].src;
    const fields: Record<string, string> = {};
    for (const [k, v] of Object.entries(fieldValues)) fields[k] = v == null ? '' : String(v);
    return {
        version: 1, canvas: { w: canvasW.value, h: canvasH.value }, bg: { color: bgColor.value, src: bgSrc.value, scale: bgScale.value, offsetX: bgOffsetX.value, offsetY: bgOffsetY.value },
        images, layers: layers.value.map((l) => ({ ...l })), fields, statusConfig: { ...statusConfig },
        // Editor-only chrome; the renderer ignores it.
        guides: { v: [...guidesV.value], h: [...guidesH.value] },
    };
}
async function deserialize(doc: ReturnType<typeof serialize>) {
    layers.value = []; selectedIds.value = [];
    Object.keys(fieldValues).forEach((k) => delete fieldValues[k]);
    canvasW.value = doc.canvas?.w ?? 1080;
    canvasH.value = doc.canvas?.h ?? 1920;
    bgColor.value = doc.bg?.color ?? '#0d3b34';
    bgSrc.value = doc.bg?.src ?? null;
    bgScale.value = doc.bg?.scale && doc.bg.scale > 0 ? doc.bg.scale : 1;
    bgOffsetX.value = doc.bg?.offsetX ?? 0;
    bgOffsetY.value = doc.bg?.offsetY ?? 0;
    Object.assign(statusConfig, defaultStatusConfig(), doc.statusConfig ?? {});
    const savedGuides = (doc as { guides?: { v?: number[]; h?: number[] } }).guides;
    guidesV.value = (savedGuides?.v ?? []).filter((n) => Number.isFinite(n));
    guidesH.value = (savedGuides?.h ?? []).filter((n) => Number.isFinite(n));
    bgImage.value = bgSrc.value ? await loadImage(bgSrc.value) : null;
    const map: Record<number, number> = {};
    for (const [oldIdStr, src] of Object.entries(doc.images ?? {})) {
        const oldId = Number(oldIdStr);
        try {
            const img = await loadImage(src as string);
            // Keep the reserved logo slot's id stable across load/save cycles instead of remapping it —
            // otherwise it drifts to an arbitrary local id and the dynamic logo injection stops finding it.
            if (oldId === TENANT_LOGO_IMAGE_ID) { imgStore[TENANT_LOGO_IMAGE_ID] = { img, src: src as string }; map[oldId] = TENANT_LOGO_IMAGE_ID; }
            else { map[oldId] = registerImage(img, src as string); }
        } catch { /* skip */ }
    }
    for (const [k, v] of Object.entries(doc.fields ?? {})) fieldValues[k] = v == null ? '' : String(v);
    layers.value = (doc.layers ?? []).map((l) => ({
        ...makeLayer({}), ...(l as Layer), id: uid++,
        // Never null out the reserved logo slot even if no logo image was injected this time — it should
        // stay bindable for the next load, whenever a real (or dummy) logo becomes available.
        imgId: l.imgId === TENANT_LOGO_IMAGE_ID ? TENANT_LOGO_IMAGE_ID : (l.imgId != null ? map[l.imgId] ?? null : null),
    }));
    // Override any stored "status" sample text — the preview always reflects the current wording/icon config.
    fieldValues.status = buildStatusMessage(statusConfig, 'up', 50);
    // Brand contact details always reflect the tenant's own profile, never whatever was stored when the template was saved.
    fieldValues.brand_name = tenant.name ?? '';
    fieldValues.phone = tenant.phone ?? '';
    fieldValues.email = tenant.email ?? '';
    fieldValues.address = tenant.address ?? '';
    render();
}
async function saveTemplate(asNew = false) {
    // Admin-provided default designs are read-only for tenants — editing one always saves a new copy,
    // so one tenant's changes can never reach another. Admins manage those designs directly.
    if (isReadOnlyTemplate.value) asNew = true;
    const name = await modals.prompt('Template name', { title: asNew ? 'Save as new template' : 'Save template', default: asNew ? '' : currentName.value, placeholder: 'e.g. Gold rate poster', confirmText: 'Save' });
    if (!name) return;
    try {
        const document = serialize();
        const category = currentCategory.value;
        const type = currentType.value || null;
        const posterCategoryPayload = props.adminMode ? { poster_category_id: posterCategoryId.value } : {};
        if (!asNew && currentTemplateId.value) {
            const { data } = await axios.put(`${apiBase}/${currentTemplateId.value}`, { name, category, type, document, ...posterCategoryPayload });
            currentName.value = data.name; forgetThumbnail(data.id); const i = templateList.value.findIndex((t) => t.id === data.id); if (i >= 0) templateList.value[i] = data;
        } else {
            const { data } = await axios.post(apiBase, { name, category, type, document, ...posterCategoryPayload });
            currentTemplateId.value = data.id; currentName.value = data.name; currentIsGlobal.value = false; templateList.value.unshift(data);
        }
    } catch { modals.alert('Could not save the template. The background image may be too large.', { title: 'Save failed' }); }
}
async function loadTemplate(id: number) {
    if (!id) return;
    try {
        const { data } = await axios.get(`${apiBase}/${id}`);
        const doc = data.document;
        // A layer bound to this reserved image id shows the tenant's own logo automatically.
        // The admin editor always uses the dummy placeholder — never a real tenant's logo.
        const logoUrl = props.adminMode ? DUMMY_LOGO_SRC : (tenant.logo_light_url ?? tenant.logo_dark_url ?? null);
        if (logoUrl) doc.images = { ...(doc.images ?? {}), [TENANT_LOGO_IMAGE_ID]: logoUrl };
        await deserialize(doc);
        currentTemplateId.value = data.id; currentName.value = data.name; currentCategory.value = data.category === 'poster' ? 'poster' : 'gold_price'; currentType.value = data.type ?? ''; currentIsGlobal.value = props.adminMode ? false : !!data.is_global; posterCategoryId.value = data.poster_category_id ?? null; started.value = true;
        resetHistory();
    }
    catch { modals.alert('Could not load that template.', { title: 'Load failed' }); }
}
async function deleteTemplate(id: number) {
    if (!(await modals.confirm('This template will be permanently removed.', { title: 'Delete template?', confirmText: 'Delete', danger: true }))) return;
    try { await axios.delete(`${apiBase}/${id}`); templateList.value = templateList.value.filter((t) => t.id !== id); forgetThumbnail(id); if (currentTemplateId.value === id) { currentTemplateId.value = null; currentName.value = ''; } }
    catch { modals.alert('Could not delete that template.', { title: 'Delete failed' }); }
}

// ── Prefill price placeholders from the latest saved rate ────────────
function formatMoney(n: number) { return `₹${Number(n).toLocaleString('en-IN')}`; }
async function fetchLatestRate() {
    try {
        const { data } = await axios.get('/gold-poster/rates/latest');
        if (data.rate) {
            fieldValues.price_22k_1g = formatMoney(data.rate.price_22k_1g);
            fieldValues.price_22k_8g = formatMoney(data.rate.price_22k_8g);
            fieldValues.price_18k_1g = formatMoney(data.rate.price_18k_1g);
            render();
        }
    } catch { /* ignore */ }
}

// ── Fonts + seed ─────────────────────────────────────────────────────
async function ensureFonts() {
    if (!document.getElementById('poster-fonts')) {
        const link = document.createElement('link'); link.id = 'poster-fonts'; link.rel = 'stylesheet';
        link.href = FONT_CSS_URL;
        document.head.appendChild(link);
    }
    try { await Promise.all(FONT_OPTIONS.flatMap((f) => [document.fonts.load(`400 40px "${f}"`), document.fonts.load(`700 40px "${f}"`)])); await document.fonts.ready; } catch { /* system fallback */ }
}
function addLogoTop() {
    if (logoIds.light == null && logoIds.dark == null) return;
    const id = logoIds.light ?? logoIds.dark!; const img = imgStore[id].img; const nat = (img.naturalWidth || 300) / (img.naturalHeight || 300);
    layers.value.push(makeLayer({ type: 'image', imgId: id, x: canvasW.value / 2, y: 270, w: 380, h: 380 / nat, naturalRatio: nat }));
}
function addContact() {
    const contact = [tenant.address, tenant.phone].filter(Boolean).join('  ·  ') || 'Your address · phone';
    layers.value.push(makeLayer({ text: contact, x: canvasW.value / 2, y: canvasH.value - 120, fontSize: 36, weight: 600 }));
}
const TENANT_FIELD_DEFAULTS: Record<string, () => string> = {
    brand_name: () => tenant.name ?? '',
    phone: () => tenant.phone ?? '',
    email: () => tenant.email ?? '',
    address: () => tenant.address ?? '',
};
function initFieldDefaults() {
    for (const l of layers.value) {
        if (!l.field || fieldValues[l.field] !== undefined) continue;
        if (l.field === 'status') { fieldValues[l.field] = buildStatusMessage(statusConfig, 'up', 50); continue; }
        fieldValues[l.field] = TENANT_FIELD_DEFAULTS[l.field]?.() ?? l.text;
    }
}

function seedGoldRate() {
    canvasW.value = 1080; canvasH.value = 1920;
    const cx = canvasW.value / 2;
    bgColor.value = '#0d3b34';
    addLogoTop();
    layers.value.push(makeLayer({ text: "TODAY'S GOLD RATE", x: cx, y: 540, fontSize: 76, weight: 800 }));
    layers.value.push(makeLayer({ text: '02 Apr 2026', x: cx, y: 620, fontSize: 46, weight: 700, color: '#d4af37', field: 'date' }));
    const priceRow = (y: number, karat: string, gram: string, key: string, price: string) => {
        layers.value.push(makeLayer({ text: karat, x: cx - 300, y, fontSize: 46, weight: 700 }));
        layers.value.push(makeLayer({ text: price, x: cx, y, fontSize: 60, weight: 800, color: '#d4af37', field: key }));
        layers.value.push(makeLayer({ text: gram, x: cx + 300, y, fontSize: 40, weight: 600 }));
    };
    priceRow(820, '22K', '1 GRAM', 'price_22k_1g', '₹13,880');
    priceRow(940, '22K', '8 GRAM', 'price_22k_8g', '₹1,11,040');
    priceRow(1060, '18K', '1 GRAM', 'price_18k_1g', '₹11,356');
    layers.value.push(makeLayer({ text: "Today's gold rate", x: cx, y: 1170, fontFamily: 'Poppins', fontSize: 44, weight: 600, color: '#d4af37', field: 'status' }));
    layers.value.push(makeLayer({ text: 'Let Your Style\nSpeak Uniquely', x: cx, y: 1300, fontFamily: 'Playfair Display', fontSize: 62, weight: 700, color: '#d4af37' }));
    addContact();
    initFieldDefaults();
}

function seedBirthday() {
    canvasW.value = 1080; canvasH.value = 1080; // square
    const cx = canvasW.value / 2;
    bgColor.value = '#241033';
    addLogoTop();
    layers.value.push(makeLayer({ text: 'Happy Birthday', x: cx, y: 470, fontFamily: 'Great Vibes', fontSize: 110, weight: 400, color: '#f6c453' }));
    layers.value.push(makeLayer({ text: 'Dear Customer', x: cx, y: 650, fontFamily: 'Poppins', fontSize: 54, weight: 600, color: '#ffffff', field: 'customer_name' }));
    layers.value.push(makeLayer({ text: 'Wishing you a wonderful day\nand a year full of joy & success!', x: cx, y: 810, fontFamily: 'Poppins', fontSize: 36, weight: 400, color: '#e5e7eb', field: 'message' }));
    addContact();
    initFieldDefaults();
}

async function newTemplate(preset: 'gold_rate' | 'birthday' | 'blank') {
    if (layers.value.length && !(await modals.confirm('Any unsaved changes will be lost.', { title: 'Start a new template?', confirmText: 'Discard & start' }))) return;
    layers.value = []; selectedIds.value = [];
    Object.keys(fieldValues).forEach((k) => delete fieldValues[k]);
    bgImage.value = null; bgSrc.value = null; bgColor.value = '#0d3b34';
    bgScale.value = 1; bgOffsetX.value = 0; bgOffsetY.value = 0; bgAdjust.value = false;
    guidesV.value = []; guidesH.value = [];
    currentTemplateId.value = null; currentName.value = ''; currentIsGlobal.value = false;
    Object.assign(statusConfig, defaultStatusConfig());
    started.value = true;
    if (preset === 'gold_rate') { currentCategory.value = 'gold_price'; currentType.value = 'Gold rate'; seedGoldRate(); }
    else if (preset === 'birthday') { currentCategory.value = 'poster'; currentType.value = 'Birthday'; seedBirthday(); }
    else { currentCategory.value = 'poster'; currentType.value = ''; canvasW.value = 1080; canvasH.value = 1920; addLogoTop(); addContact(); initFieldDefaults(); }
    render();
    resetHistory();
}
function onNewSelect(e: Event) {
    const el = e.target as HTMLSelectElement; const v = el.value; el.value = '';
    if (v) newTemplate(v as 'gold_rate' | 'birthday' | 'blank');
}

// ── Undo / redo ──────────────────────────────────────────────────────
// A snapshot is JSON of everything the user can change, minus the image blobs: imgStore is
// append-only, so layers keep resolving the same image ids across an undo. The background
// image is held by reference for the same reason — re-encoding it per step would be huge.
interface HistoryEntry { json: string; bgSrc: string | null; bgImage: HTMLImageElement | null }
const HISTORY_LIMIT = 60;
const history = ref<HistoryEntry[]>([]);
const historyIndex = ref(-1);
const canUndo = computed(() => historyIndex.value > 0);
const canRedo = computed(() => historyIndex.value < history.value.length - 1);
let restoring = false;
let historyTimer: ReturnType<typeof setTimeout> | null = null;

function snapshot(): HistoryEntry {
    return {
        json: JSON.stringify({
            layers: layers.value, fields: fieldValues, status: statusConfig,
            guidesV: guidesV.value, guidesH: guidesH.value,
            bgColor: bgColor.value, w: canvasW.value, h: canvasH.value,
            bgScale: bgScale.value, bgOffsetX: bgOffsetX.value, bgOffsetY: bgOffsetY.value,
        }),
        bgSrc: bgSrc.value, bgImage: bgImage.value,
    };
}
/** Starts the history over from the current state — used whenever a template is loaded or created. */
function resetHistory() {
    if (historyTimer) { clearTimeout(historyTimer); historyTimer = null; }
    history.value = [snapshot()];
    historyIndex.value = 0;
}
function commitHistory() {
    const entry = snapshot();
    const current = history.value[historyIndex.value];
    if (current && current.json === entry.json && current.bgSrc === entry.bgSrc) return;
    const kept = [...history.value.slice(0, historyIndex.value + 1), entry];
    history.value = kept.length > HISTORY_LIMIT ? kept.slice(kept.length - HISTORY_LIMIT) : kept;
    historyIndex.value = history.value.length - 1;
}
/** Coalesces a burst of changes — a drag, a run of keystrokes — into a single undo step. */
function scheduleHistory() {
    if (restoring) return;
    if (historyTimer) clearTimeout(historyTimer);
    historyTimer = setTimeout(() => { historyTimer = null; commitHistory(); }, 400);
}
async function restoreHistory(entry: HistoryEntry) {
    restoring = true;
    const state = JSON.parse(entry.json);
    layers.value = state.layers as Layer[];
    canvasW.value = state.w; canvasH.value = state.h; bgColor.value = state.bgColor;
    guidesV.value = state.guidesV ?? []; guidesH.value = state.guidesH ?? [];
    bgSrc.value = entry.bgSrc; bgImage.value = entry.bgImage;
    bgScale.value = state.bgScale ?? 1; bgOffsetX.value = state.bgOffsetX ?? 0; bgOffsetY.value = state.bgOffsetY ?? 0;
    Object.keys(fieldValues).forEach((k) => delete fieldValues[k]);
    Object.assign(fieldValues, state.fields);
    Object.assign(statusConfig, state.status);
    // A layer that only existed in the undone state can't stay selected.
    const ids = new Set(layers.value.map((l) => l.id));
    selectedIds.value = selectedIds.value.filter((id) => ids.has(id));
    await nextTick();
    restoring = false;
    render();
}
function undo() {
    // Fold any change still waiting on the debounce into history first, so one undo
    // doesn't silently skip the edit the user just made.
    if (historyTimer) { clearTimeout(historyTimer); historyTimer = null; commitHistory(); }
    if (!canUndo.value) return;
    historyIndex.value--;
    restoreHistory(history.value[historyIndex.value]);
}
function redo() {
    if (!canRedo.value) return;
    historyIndex.value++;
    restoreHistory(history.value[historyIndex.value]);
}
function onKeydown(e: KeyboardEvent) {
    if (!e.metaKey && !e.ctrlKey) return;
    const el = e.target as HTMLElement | null;
    // Inputs keep their own native undo.
    if (el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable)) return;
    const key = e.key.toLowerCase();
    if (key === 'z') { e.preventDefault(); if (e.shiftKey) { redo(); } else { undo(); } }
    else if (key === 'y') { e.preventDefault(); redo(); }
}
watch(
    [layers, fieldValues, statusConfig, guidesV, guidesH, bgColor, bgSrc, canvasW, canvasH, bgScale, bgOffsetX, bgOffsetY],
    () => scheduleHistory(),
    { deep: true },
);

onMounted(async () => {
    await ensureFonts();
    if (props.adminMode) {
        // Bind the admin's logo layers to the reserved slot: the dummy is only ever a
        // positioning placeholder, and each tenant's own logo fills it when they open the design.
        try {
            const dummy = await loadImage(DUMMY_LOGO_SRC);
            imgStore[TENANT_LOGO_IMAGE_ID] = { img: dummy, src: DUMMY_LOGO_SRC };
            logoIds.light = TENANT_LOGO_IMAGE_ID;
        } catch { /* ignore */ }
    } else {
        if (tenant.logo_light_url) { try { logoIds.light = registerImage(await loadLogo(tenant.logo_light_url)); } catch { /* ignore */ } }
        if (tenant.logo_dark_url) { try { logoIds.dark = registerImage(await loadLogo(tenant.logo_dark_url)); } catch { /* ignore */ } }
    }
    // Start empty — the user chooses a preset or loads a template (or opens one via ?template=).
    if (props.open) await loadTemplate(props.open);
    measureFit();
    if (viewportRef.value && typeof ResizeObserver !== 'undefined') {
        viewportObserver = new ResizeObserver(() => measureFit());
        viewportObserver.observe(viewportRef.value);
    }
    render();
    window.addEventListener('keydown', onKeydown);
    document.fonts.addEventListener('loadingdone', () => render());
    // Prefill prices before the history baseline, so the first undo doesn't wipe them.
    if (!props.adminMode) await fetchLatestRate();
    resetHistory();
});
watch([layers, bgColor, fieldValues], () => render(), { deep: true });
// Render AFTER the canvas element's width/height attributes update (they reset the
// canvas buffer), otherwise switching size leaves the canvas blank.
watch([canvasW, canvasH], () => { measureFit(); render(); }, { flush: 'post' });
onBeforeUnmount(() => {
    viewportObserver?.disconnect(); viewportObserver = null;
    if (historyTimer) { clearTimeout(historyTimer); historyTimer = null; }
    if (styleFeedbackTimer) { clearTimeout(styleFeedbackTimer); styleFeedbackTimer = null; }
    window.removeEventListener('keydown', onKeydown);
});

function setCanvasSize(w: number, h: number) {
    canvasW.value = Math.max(200, Math.min(4000, Math.round(w) || 1080));
    canvasH.value = Math.max(200, Math.min(4000, Math.round(h) || 1080));
}

const inputClass = 'w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground transition focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25';
const shapeIcon = { rect: Square, ellipse: Circle, line: Minus, image: ImageUp, text: Type } as Record<LayerType, typeof Type>;
function layerLabel(l: Layer): string { if (l.type !== 'text') return l.type[0].toUpperCase() + l.type.slice(1); return (l.field ? `{${l.field}} ` : '') + displayText(l).replace(/\n/g, ' ').slice(0, 20); }

function categoryLabel(c: string): string { return c === 'poster' ? 'Poster' : 'Gold price'; }

// Browsing shows rendered previews, so a design is picked by look rather than by name.
const { thumbnails: browseThumbnails, render: renderBrowseThumbnails, forget: forgetThumbnail } = useTemplateThumbnails({
    apiBase,
    logoUrl: props.adminMode ? DUMMY_LOGO_SRC : (tenant.logo_light_url ?? tenant.logo_dark_url ?? null),
});
const browseOpen = ref(false);
const browseSearch = ref('');
const browseCategory = ref<'all' | 'gold_price' | 'poster'>('all');
const browseResults = computed(() => {
    const q = browseSearch.value.trim().toLowerCase();
    return templateList.value.filter((t) => {
        const category = t.category === 'poster' ? 'poster' : 'gold_price';
        if (browseCategory.value !== 'all' && category !== browseCategory.value) return false;
        if (!q) return true;
        return `${t.name} ${t.type ?? ''} ${t.poster_category ?? ''}`.toLowerCase().includes(q);
    });
});
watch(browseOpen, (open) => {
    if (!open) return;
    browseSearch.value = '';
    renderBrowseThumbnails(templateList.value.map((t) => t.id));
});
async function openFromBrowse(id: number) { browseOpen.value = false; await loadTemplate(id); }

// ── Mobile panel tabs ─────────────────────────────────────────────────
const mobilePanel = ref<'tools' | 'canvas' | 'props'>('canvas');
// A hidden panel measures zero wide, so re-fit once the canvas is back on screen.
watch(mobilePanel, (panel) => { if (panel === 'canvas') requestAnimationFrame(measureFit); });
</script>

<template>
    <Head title="Templates" />

    <component :is="layout" :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100dvh-4rem)] min-h-0 flex-col">
            <!-- Top action bar -->
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border bg-card px-3 py-2 sm:px-4">
                <div class="flex min-w-0 items-center gap-2">
                    <h1 class="text-sm font-semibold text-foreground">Templates</h1>
                    <span class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">{{ categoryLabel(currentCategory) }}</span>
                    <input
                        v-if="started && currentCategory === 'poster'"
                        v-model="currentType"
                        type="text"
                        placeholder="Type e.g. Birthday"
                        class="h-7 w-28 rounded-md border border-input bg-background px-2 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/25 sm:w-40"
                    />
                    <span class="hidden truncate text-xs text-muted-foreground sm:inline">· {{ currentName || 'Unsaved' }}</span>
                    <span v-if="isReadOnlyTemplate" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary" title="Default design — edit freely, then keep your changes with “Save as new”">Default design</span>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <div class="inline-flex overflow-hidden rounded-md border border-border">
                        <button type="button" :disabled="!canUndo" class="p-1.5 text-foreground transition hover:bg-muted disabled:opacity-40 sm:p-2" title="Undo (⌘/Ctrl+Z)" @click="undo"><Undo2 class="h-4 w-4" /></button>
                        <button type="button" :disabled="!canRedo" class="border-l border-border p-1.5 text-foreground transition hover:bg-muted disabled:opacity-40 sm:p-2" title="Redo (⇧⌘/Ctrl+Y)" @click="redo"><Redo2 class="h-4 w-4" /></button>
                    </div>
                    <select class="h-8 rounded-md border border-input bg-background px-2 text-xs text-foreground sm:h-9 sm:text-sm" @change="onNewSelect">
                        <option value="">New…</option>
                        <option value="gold_rate">Gold rate</option>
                        <option value="birthday">Birthday poster</option>
                        <option value="blank">Blank poster</option>
                    </select>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-md border border-border px-2 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted sm:px-3 sm:py-2 sm:text-sm" @click="browseOpen = true"><LayoutList class="h-4 w-4" /> <span class="hidden sm:inline">Browse</span></button>
                    <button v-if="currentTemplateId && !isReadOnlyTemplate" type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive sm:p-2" title="Delete template" @click="deleteTemplate(currentTemplateId)"><Trash2 class="h-4 w-4" /></button>
                    <button
                        v-if="isReadOnlyTemplate"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md border border-border px-2 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted sm:px-3 sm:py-2 sm:text-sm"
                        title="Default designs stay untouched — this saves your edits as your own template"
                        @click="saveTemplate(true)"
                    >
                        <Save class="h-4 w-4" /> <span class="hidden sm:inline">Save as new</span>
                    </button>
                    <template v-else>
                        <button type="button" class="hidden items-center gap-1.5 rounded-md border border-border px-2 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted sm:inline-flex sm:px-3 sm:py-2 sm:text-sm" @click="saveTemplate(true)">Save as new</button>
                        <button type="button" class="inline-flex items-center gap-1.5 rounded-md border border-border px-2 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted sm:px-3 sm:py-2 sm:text-sm" @click="saveTemplate(false)"><Save class="h-4 w-4" /> <span class="hidden sm:inline">Save</span></button>
                    </template>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-md bg-primary px-2 py-1.5 text-xs font-medium text-primary-foreground shadow-xs transition hover:bg-primary/90 sm:px-3 sm:py-2 sm:text-sm" @click="downloadPoster"><Download class="h-4 w-4" /> <span class="hidden sm:inline">Download</span></button>
                </div>
            </div>

            <!-- Mobile panel tabs -->
            <div class="flex border-b border-border bg-card md:hidden">
                <button
                    type="button"
                    :class="['flex flex-1 items-center justify-center gap-1.5 py-2 text-xs font-medium transition', mobilePanel === 'tools' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground']"
                    @click="mobilePanel = 'tools'"
                >
                    <Type class="h-3.5 w-3.5" /> Tools
                </button>
                <button
                    type="button"
                    :class="['flex flex-1 items-center justify-center gap-1.5 py-2 text-xs font-medium transition', mobilePanel === 'canvas' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground']"
                    @click="mobilePanel = 'canvas'"
                >
                    <Square class="h-3.5 w-3.5" /> Canvas
                </button>
                <button
                    type="button"
                    :class="['flex flex-1 items-center justify-center gap-1.5 py-2 text-xs font-medium transition', mobilePanel === 'props' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground']"
                    @click="mobilePanel = 'props'"
                >
                    <Paintbrush class="h-3.5 w-3.5" /> Properties
                </button>
            </div>

            <div class="flex min-h-0 flex-1">
                <!-- LEFT: toolbox -->
                <aside :class="['shrink-0 flex-col gap-4 overflow-y-auto border-r border-border bg-card p-3 md:flex md:w-48', mobilePanel === 'tools' ? 'flex w-full' : 'hidden']">
                    <div>
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Add</div>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted" @click="addText"><Type class="h-4 w-4" /> Text</button>
                            <button type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted" @click="addShape('rect')"><Square class="h-4 w-4" /> Rect</button>
                            <button type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted" @click="addShape('ellipse')"><Circle class="h-4 w-4" /> Ellipse</button>
                            <button type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted" @click="addShape('line')"><Minus class="h-4 w-4" /> Line</button>
                            <button type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted" title="Icons & certification badges (916, BIS, 22K…)" @click="iconPickerOpen = true"><BadgeCheck class="h-4 w-4" /> Icon</button>
                            <label class="flex cursor-pointer flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted"><ImageUp class="h-4 w-4" /> Image<input type="file" accept="image/*" class="hidden" @change="onAddImage" /></label>
                            <label class="flex cursor-pointer flex-col items-center gap-1 rounded-md border border-border py-2.5 text-center text-[11px] font-medium text-foreground transition hover:bg-muted"><ImageUp class="h-4 w-4" /> {{ bgImage ? 'Replace bg' : 'Bg image' }}<input type="file" accept="image/*" class="hidden" @change="onBackground" /></label>
                            <button v-if="logoIds.light != null" type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-center text-[11px] font-medium text-foreground transition hover:bg-muted" :title="props.adminMode ? 'Placeholder that becomes each tenant’s own logo' : undefined" @click="addLogo('light')"><ImageUp class="h-4 w-4" /> {{ props.adminMode ? 'Logo slot' : 'Light logo' }}</button>
                            <button v-if="logoIds.dark != null" type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted" @click="addLogo('dark')"><ImageUp class="h-4 w-4" /> Dark logo</button>
                        </div>
                    </div>

                    <div class="border-t border-border pt-3">
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Background</div>
                        <button
                            v-if="backgroundLibrary.length"
                            type="button"
                            class="mb-2 inline-flex w-full items-center justify-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                            @click="libraryOpen = true"
                        >
                            <Images class="h-3.5 w-3.5" /> Background library
                        </button>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs text-muted-foreground">{{ bgImage ? 'Behind image' : 'Color' }}</span>
                            <input v-model="bgColor" type="color" class="h-8 w-12 cursor-pointer rounded border border-border bg-background" />
                        </div>

                        <!-- Framing the background photo -->
                        <template v-if="bgImage">
                            <div class="mt-3 border-t border-border pt-3">
                                <label class="mb-1.5 flex items-center justify-between text-[11px] font-medium text-muted-foreground">
                                    <span>Zoom</span>
                                    <span class="tabular-nums text-foreground">{{ Math.round(bgScale * 100) }}%</span>
                                </label>
                                <input v-model.number="bgScale" type="range" min="0.5" max="4" step="0.01" class="w-full accent-primary" @input="render()" />
                                <button
                                    type="button"
                                    :class="['mt-2 inline-flex w-full items-center justify-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium transition', bgAdjust ? 'border-primary bg-primary/10 text-primary' : 'border-border text-foreground hover:bg-muted']"
                                    title="Drag anywhere on the canvas to move the photo"
                                    @click="bgAdjust = !bgAdjust; render()"
                                >
                                    <Move class="h-3.5 w-3.5" /> {{ bgAdjust ? 'Done repositioning' : 'Reposition' }}
                                </button>
                                <div class="mt-2 flex items-center justify-center gap-1">
                                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs text-foreground transition hover:bg-muted" aria-label="Move background left" @click="nudgeBackground(-20, 0)">←</button>
                                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs text-foreground transition hover:bg-muted" aria-label="Move background up" @click="nudgeBackground(0, -20)">↑</button>
                                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs text-foreground transition hover:bg-muted" aria-label="Move background down" @click="nudgeBackground(0, 20)">↓</button>
                                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs text-foreground transition hover:bg-muted" aria-label="Move background right" @click="nudgeBackground(20, 0)">→</button>
                                    <button type="button" class="ml-1 rounded-md border border-border p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" title="Reset zoom and position" @click="resetBackgroundFraming"><RotateCcw class="h-3.5 w-3.5" /></button>
                                </div>
                            </div>
                            <button type="button" class="mt-2 inline-flex w-full items-center justify-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" @click="removeBackground"><Trash2 class="h-3.5 w-3.5" /> Remove background</button>
                        </template>
                    </div>

                    <div class="border-t border-border pt-3">
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Canvas size</div>
                        <div class="grid grid-cols-3 gap-1.5">
                            <button type="button" :class="['rounded-md border py-1.5 text-[11px] font-medium transition', canvasW === 1080 && canvasH === 1920 ? 'border-primary bg-primary/10 text-primary' : 'border-border text-foreground hover:bg-muted']" @click="setCanvasSize(1080, 1920)">Portrait</button>
                            <button type="button" :class="['rounded-md border py-1.5 text-[11px] font-medium transition', canvasW === 1080 && canvasH === 1080 ? 'border-primary bg-primary/10 text-primary' : 'border-border text-foreground hover:bg-muted']" @click="setCanvasSize(1080, 1080)">Square</button>
                            <button type="button" :class="['rounded-md border py-1.5 text-[11px] font-medium transition', canvasW === 1920 && canvasH === 1080 ? 'border-primary bg-primary/10 text-primary' : 'border-border text-foreground hover:bg-muted']" @click="setCanvasSize(1920, 1080)">Landscape</button>
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-1.5 text-[11px] text-muted-foreground">W<input :value="canvasW" type="number" min="200" max="4000" class="w-full rounded-md border border-input bg-background px-2 py-1 text-xs text-foreground focus:outline-none" @change="setCanvasSize(Number(($event.target as HTMLInputElement).value), canvasH)" /></label>
                            <label class="flex items-center gap-1.5 text-[11px] text-muted-foreground">H<input :value="canvasH" type="number" min="200" max="4000" class="w-full rounded-md border border-input bg-background px-2 py-1 text-xs text-foreground focus:outline-none" @change="setCanvasSize(canvasW, Number(($event.target as HTMLInputElement).value))" /></label>
                        </div>
                    </div>

                    <div class="border-t border-border pt-3">
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">View</div>
                        <div class="flex flex-col gap-1.5">
                            <button type="button" :class="['inline-flex items-center gap-1.5 rounded-md border px-3 py-2 text-sm font-medium transition', snapEnabled ? 'border-primary bg-primary/10 text-primary' : 'border-border text-foreground hover:bg-muted']" @click="snapEnabled = !snapEnabled"><Magnet class="h-4 w-4" /> Snap</button>
                            <button type="button" :class="['inline-flex items-center gap-1.5 rounded-md border px-3 py-2 text-sm font-medium transition', showGrid ? 'border-primary bg-primary/10 text-primary' : 'border-border text-foreground hover:bg-muted']" @click="showGrid = !showGrid; render()"><Ruler class="h-4 w-4" /> Rulers</button>
                        </div>
                    </div>

                    <div class="border-t border-border pt-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Guides</span>
                            <span v-if="guideCount" class="text-[11px] text-muted-foreground">{{ guideCount }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted" @click="addGuide('v')"><MoveVertical class="h-4 w-4" /> Vertical</button>
                            <button type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted" @click="addGuide('h')"><MoveHorizontal class="h-4 w-4" /> Horizontal</button>
                        </div>
                        <button v-if="guideCount" type="button" class="mt-1.5 inline-flex w-full items-center justify-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" @click="clearGuides"><Trash2 class="h-3.5 w-3.5" /> Clear guides</button>
                        <p class="mt-1.5 text-[11px] leading-snug text-muted-foreground">Drag a guide to move it, or drag it off the canvas to remove it. Layers snap to guides.</p>
                    </div>

                    <div class="border-t border-border pt-3">
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Zoom</div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" class="flex-1 rounded-md border border-border py-1.5 text-foreground transition hover:bg-muted" title="Zoom out" @click="zoomBy(1 / 1.25)"><ZoomOut class="mx-auto h-4 w-4" /></button>
                            <span class="min-w-12 text-center text-xs font-medium tabular-nums text-foreground">{{ Math.round(viewScale * 100) }}%</span>
                            <button type="button" class="flex-1 rounded-md border border-border py-1.5 text-foreground transition hover:bg-muted" title="Zoom in" @click="zoomBy(1.25)"><ZoomIn class="mx-auto h-4 w-4" /></button>
                        </div>
                        <button type="button" class="mt-1.5 inline-flex w-full items-center justify-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted" @click="zoomToFit"><Maximize class="h-3.5 w-3.5" /> Fit to screen</button>
                        <p class="mt-1.5 text-[11px] leading-snug text-muted-foreground">Pinch, or hold ⌘/Ctrl and scroll, to zoom on the canvas.</p>
                    </div>
                </aside>

                <!-- CENTER: canvas -->
                <main :class="['min-w-0 flex-col bg-muted/40 md:flex md:flex-1', mobilePanel === 'canvas' ? 'flex flex-1' : 'hidden']">
                    <div v-if="bgAdjust" class="flex flex-wrap items-center gap-2 border-b border-primary/30 bg-primary/10 px-3 py-1.5">
                        <Move class="h-3.5 w-3.5 text-primary" />
                        <span class="text-xs font-medium text-primary">Drag the canvas to move the background photo.</span>
                        <button type="button" class="ml-auto rounded-md border border-primary bg-background px-2.5 py-1 text-xs font-medium text-primary transition hover:bg-primary/10" @click="bgAdjust = false; render()">Done</button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 border-b border-border bg-card/70 px-3 py-1.5">
                        <template v-if="selectedLayers.length">
                            <span class="text-xs text-muted-foreground">{{ selectedLayers.length > 1 ? `Align ${selectedLayers.length} layers` : 'Align to canvas' }}</span>
                            <div class="inline-flex overflow-hidden rounded-md border border-border">
                                <button type="button" class="p-1.5 text-foreground transition hover:bg-muted" title="Left" @click="align('left')"><AlignLeft class="h-4 w-4" /></button>
                                <button type="button" class="p-1.5 text-foreground transition hover:bg-muted" title="Center H" @click="align('hcenter')"><AlignHorizontalJustifyCenter class="h-4 w-4" /></button>
                                <button type="button" class="p-1.5 text-foreground transition hover:bg-muted" title="Right" @click="align('right')"><AlignRight class="h-4 w-4" /></button>
                            </div>
                            <div class="inline-flex overflow-hidden rounded-md border border-border">
                                <button type="button" class="p-1.5 text-foreground transition hover:bg-muted" title="Top" @click="align('top')"><AlignLeft class="h-4 w-4 rotate-90" /></button>
                                <button type="button" class="p-1.5 text-foreground transition hover:bg-muted" title="Middle V" @click="align('vmiddle')"><AlignVerticalJustifyCenter class="h-4 w-4" /></button>
                                <button type="button" class="p-1.5 text-foreground transition hover:bg-muted" title="Bottom" @click="align('bottom')"><AlignRight class="h-4 w-4 rotate-90" /></button>
                            </div>
                        </template>

                        <!-- Zoom + guides -->
                        <div class="ml-auto flex items-center gap-1.5">
                            <div class="inline-flex overflow-hidden rounded-md border border-border">
                                <button type="button" class="p-1.5 text-foreground transition hover:bg-muted" title="Add a vertical guide" @click="addGuide('v')"><MoveVertical class="h-4 w-4" /></button>
                                <button type="button" class="p-1.5 text-foreground transition hover:bg-muted" title="Add a horizontal guide" @click="addGuide('h')"><MoveHorizontal class="h-4 w-4" /></button>
                                <button type="button" :disabled="!guideCount" class="p-1.5 text-foreground transition hover:bg-muted disabled:opacity-40" :title="`Clear ${guideCount} guide${guideCount === 1 ? '' : 's'}`" @click="clearGuides"><Trash2 class="h-4 w-4" /></button>
                            </div>
                            <div class="inline-flex items-center overflow-hidden rounded-md border border-border">
                                <button type="button" class="p-1.5 text-foreground transition hover:bg-muted" title="Zoom out" @click="zoomBy(1 / 1.25)"><ZoomOut class="h-4 w-4" /></button>
                                <button type="button" class="min-w-14 px-1 text-xs font-medium tabular-nums text-foreground transition hover:bg-muted" title="Reset to fit" @click="zoomToFit">{{ Math.round(viewScale * 100) }}%</button>
                                <button type="button" class="p-1.5 text-foreground transition hover:bg-muted" title="Zoom in" @click="zoomBy(1.25)"><ZoomIn class="h-4 w-4" /></button>
                                <button type="button" class="border-l border-border p-1.5 text-foreground transition hover:bg-muted" title="Fit to screen" @click="zoomToFit"><Maximize class="h-4 w-4" /></button>
                            </div>
                        </div>
                    </div>
                    <div ref="viewportRef" class="relative flex flex-1 overflow-auto p-6" @wheel="onWheel">
                        <canvas
                            v-show="started"
                            ref="canvasRef"
                            :width="canvasW"
                            :height="canvasH"
                            :style="canvasStyle"
                                        :class="['m-auto block shrink-0 touch-none select-none rounded shadow-lg ring-1 ring-black/10', bgAdjust ? 'cursor-move' : '']"
                            @pointerdown="onPointerDown"
                            @pointermove="onPointerMove"
                            @pointerup="onPointerUp"
                            @pointercancel="onPointerUp"
                        />

                        <!-- Empty state — choose a template type or load one -->
                        <div v-if="!started" class="m-auto w-full max-w-md rounded-lg border border-dashed border-border bg-card p-8 text-center shadow-xs">
                            <h2 class="text-base font-semibold text-foreground">Start a new template</h2>
                            <p class="mx-auto mt-1 mb-5 max-w-xs text-sm text-muted-foreground">Pick a starting point, or load a saved template from the top bar.</p>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" class="flex flex-col items-center gap-1.5 rounded-md border border-border p-3 text-xs font-medium text-foreground transition hover:border-primary/40 hover:bg-primary/5" @click="newTemplate('gold_rate')"><Coins class="h-5 w-5 text-primary" /> Gold rate</button>
                                <button type="button" class="flex flex-col items-center gap-1.5 rounded-md border border-border p-3 text-xs font-medium text-foreground transition hover:border-primary/40 hover:bg-primary/5" @click="newTemplate('birthday')"><Cake class="h-5 w-5 text-primary" /> Birthday</button>
                                <button type="button" class="flex flex-col items-center gap-1.5 rounded-md border border-border p-3 text-xs font-medium text-foreground transition hover:border-primary/40 hover:bg-primary/5" @click="newTemplate('blank')"><Square class="h-5 w-5 text-primary" /> Blank</button>
                            </div>
                        </div>
                    </div>
                </main>

                <!-- RIGHT: properties + layers + data -->
                <aside :class="['shrink-0 flex-col overflow-y-auto border-l border-border bg-card md:flex md:w-80', mobilePanel === 'props' ? 'flex w-full' : 'hidden']">
                    <!-- Properties -->
                    <div v-if="selected" class="border-b border-border p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold capitalize text-foreground">{{ selected.type }} properties</h2>
                            <div class="flex items-center gap-1">
                                <button class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground" title="Duplicate" @click="duplicateLayer(selected)"><Copy class="h-4 w-4" /></button>
                                <button class="rounded-md p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" title="Delete" @click="deleteLayer(selected.id)"><Trash2 class="h-4 w-4" /></button>
                            </div>
                        </div>

                        <!-- Style clipboard — works for text, shapes and icons alike -->
                        <div class="mb-3 flex flex-wrap items-center gap-1.5 rounded-md border border-border bg-muted/30 p-2">
                            <button type="button" class="inline-flex items-center gap-1.5 rounded-md border border-border bg-background px-2.5 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted" :title="`Copy this ${selected.type}'s style`" @click="copyStyle"><Paintbrush class="h-3.5 w-3.5" /> Copy style</button>
                            <button type="button" :disabled="!copiedStyle" class="inline-flex items-center gap-1.5 rounded-md border border-border bg-background px-2.5 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted disabled:opacity-40" title="Apply the copied style to the selection" @click="pasteStyle"><ClipboardPaste class="h-3.5 w-3.5" /> Paste</button>
                            <span v-if="styleFeedback" class="w-full text-[11px] font-medium text-primary">{{ styleFeedback }}</span>
                            <span v-else-if="copiedStyle" class="w-full text-[11px] text-muted-foreground">{{ copiedStyleLabel }} — select a layer and hit Paste. Onto another kind of layer, the colour carries across.</span>
                            <span v-else class="w-full text-[11px] text-muted-foreground">Copy this layer's look — colours, size and rotation — then paste it onto another layer.</span>
                        </div>

                        <template v-if="selected.type === 'text'">
                            <div class="space-y-3">
                                <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Text</label><textarea v-model="selected.text" rows="2" :class="inputClass" /></div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Font</label><select v-model="selected.fontFamily" :class="inputClass"><option v-for="f in FONT_OPTIONS" :key="f" :value="f">{{ f }}</option></select></div>
                                    <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Size</label><input v-model.number="selected.fontSize" type="number" min="10" max="600" :class="inputClass" /></div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Weight {{ selected.weight }}</label><input v-model.number="selected.weight" type="range" min="100" max="900" step="100" class="w-full accent-primary" /></div>
                                    <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Letter spacing {{ selected.letterSpacing }}</label><input v-model.number="selected.letterSpacing" type="range" min="-5" max="40" step="0.5" class="w-full accent-primary" /></div>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex items-center gap-2"><label class="text-xs font-medium text-muted-foreground">Color</label><input v-model="selected.color" type="color" class="h-9 w-10 cursor-pointer rounded border border-border bg-background" /></div>
                                    <button type="button" :class="['inline-flex items-center gap-1.5 rounded-md border px-2.5 py-2 text-sm transition', selected.weight >= 700 ? 'border-primary bg-primary/10 text-primary' : 'border-border text-foreground hover:bg-muted']" @click="selected.weight = selected.weight >= 700 ? 400 : 700"><Bold class="h-4 w-4" /></button>
                                    <div class="inline-flex overflow-hidden rounded-md border border-border">
                                        <button type="button" :class="['p-2', selected.align === 'left' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="selected.align = 'left'"><AlignLeft class="h-4 w-4" /></button>
                                        <button type="button" :class="['p-2', selected.align === 'center' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="selected.align = 'center'"><AlignCenter class="h-4 w-4" /></button>
                                        <button type="button" :class="['p-2', selected.align === 'right' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="selected.align = 'right'"><AlignRight class="h-4 w-4" /></button>
                                    </div>
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground"><input v-model="selected.shadow" type="checkbox" class="h-4 w-4 rounded border-border accent-primary" /> Shadow</label>
                                </div>

                                <!-- Text box: gives left/centre/right and top/middle/bottom something to align against -->
                                <div class="rounded-md border border-border p-3">
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground">
                                        <input type="checkbox" :checked="!!selected.boxW" class="h-4 w-4 rounded border-border accent-primary" @change="toggleTextBox(selected)" />
                                        Text box (wrap &amp; align inside)
                                    </label>
                                    <template v-if="selected.boxW">
                                        <div class="mt-3 grid grid-cols-2 gap-3">
                                            <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Box width</label><input :value="Math.round(selected.boxW)" type="number" min="20" max="4000" :class="inputClass" @input="setTextBox(selected, 'boxW', ($event.target as HTMLInputElement).value)" /></div>
                                            <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Box height</label><input :value="Math.round(selected.boxH)" type="number" min="20" max="4000" :class="inputClass" @input="setTextBox(selected, 'boxH', ($event.target as HTMLInputElement).value)" /></div>
                                        </div>
                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-medium text-muted-foreground">Vertical</span>
                                            <div class="inline-flex overflow-hidden rounded-md border border-border">
                                                <button type="button" :class="['p-2', selected.vAlign === 'top' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" title="Top" @click="selected.vAlign = 'top'; render()"><AlignVerticalJustifyStart class="h-4 w-4" /></button>
                                                <button type="button" :class="['p-2', (selected.vAlign ?? 'middle') === 'middle' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" title="Middle" @click="selected.vAlign = 'middle'; render()"><AlignVerticalJustifyCenter class="h-4 w-4" /></button>
                                                <button type="button" :class="['p-2', selected.vAlign === 'bottom' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" title="Bottom" @click="selected.vAlign = 'bottom'; render()"><AlignVerticalJustifyEnd class="h-4 w-4" /></button>
                                            </div>
                                            <button type="button" class="rounded-md border border-border px-2.5 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted" title="Stretch the box across the canvas and centre it" @click="fitTextBoxToCanvas(selected)">Full width</button>
                                        </div>
                                        <p class="mt-2 text-[11px] leading-snug text-muted-foreground">Text wraps to the box width. Dragging the resize handle now resizes the box instead of the type size.</p>
                                    </template>
                                    <p v-else class="mt-2 text-[11px] leading-snug text-muted-foreground">Off, the layer hugs its text, so left/centre/right only affects multi-line text.</p>
                                </div>
                                <div class="rounded-md border border-border p-3">
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground"><input type="checkbox" :checked="!!selected.field" class="h-4 w-4 rounded border-border accent-primary" @change="toggleField(selected)" /> Dynamic field (placeholder)</label>
                                    <select v-if="selected.field" v-model="selected.field" :class="[inputClass, 'mt-2']" @change="onFieldSelect(selected)">
                                        <optgroup v-for="[cat, opts] in placeholderGroups" :key="cat" :label="phCatLabel(cat)">
                                            <option v-for="p in opts" :key="p.key" :value="p.key">{{ p.label }}</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div v-if="selected.field === 'status'" class="rounded-md border border-border p-3">
                                    <label class="mb-2 block text-xs font-medium text-muted-foreground">Price change display</label>
                                    <div class="mb-3 inline-flex overflow-hidden rounded-md border border-border">
                                        <button type="button" :class="['px-3 py-1.5 text-xs font-medium transition', statusConfig.mode === 'text' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="statusConfig.mode = 'text'">Wording</button>
                                        <button type="button" :class="['px-3 py-1.5 text-xs font-medium transition', statusConfig.mode === 'icon' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="statusConfig.mode = 'icon'">Icon only</button>
                                        <button type="button" :class="['px-3 py-1.5 text-xs font-medium transition', statusConfig.mode === 'both' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="statusConfig.mode = 'both'">Icon + wording</button>
                                    </div>
                                    <template v-if="statusConfig.mode === 'text' || statusConfig.mode === 'both'">
                                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Price increased</label>
                                        <input v-model="statusConfig.increaseText" :class="[inputClass, 'mb-2']" placeholder="Gold price up ₹{diff}/gram" />
                                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Price decreased</label>
                                        <input v-model="statusConfig.decreaseText" :class="[inputClass, 'mb-2']" placeholder="Gold price down ₹{diff}/gram" />
                                        <p class="mb-2 text-[11px] text-muted-foreground">Use <code>{diff}</code> for the price change amount.</p>
                                    </template>
                                    <template v-if="statusConfig.mode === 'icon' || statusConfig.mode === 'both'">
                                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Icon</label>
                                        <div class="mb-2 grid grid-cols-3 gap-2">
                                            <button
                                                v-for="set in STATUS_ICON_SETS"
                                                :key="set.key"
                                                type="button"
                                                :class="['flex flex-col items-center gap-1 rounded-md border px-2 py-2 text-xs transition', statusConfig.icon === set.key ? 'border-primary bg-primary/10 text-primary' : 'border-border text-foreground hover:bg-muted']"
                                                @click="statusConfig.icon = set.key"
                                            >
                                                <span class="flex items-center gap-1.5 text-base leading-none"><span>{{ set.up }}</span><span>{{ set.down }}</span></span>
                                                {{ set.label }}
                                            </button>
                                        </div>
                                    </template>
                                    <template v-if="statusConfig.mode === 'both'">
                                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Order</label>
                                        <div class="mb-2 inline-flex overflow-hidden rounded-md border border-border">
                                            <button type="button" :class="['px-3 py-1.5 text-xs font-medium transition', statusConfig.order === 'icon-first' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="statusConfig.order = 'icon-first'">Icon → text</button>
                                            <button type="button" :class="['px-3 py-1.5 text-xs font-medium transition', statusConfig.order === 'text-first' ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted']" @click="statusConfig.order = 'text-first'">Text → icon</button>
                                        </div>
                                    </template>
                                    <p class="text-[11px] text-muted-foreground">Nothing is shown when the price hasn't changed.</p>
                                </div>
                            </div>
                        </template>

                        <template v-else-if="selected.type === 'image'">
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Width</label><input :value="Math.round(selected.w)" type="number" min="20" max="1080" :class="inputClass" @input="setImgW(($event.target as HTMLInputElement).value)" /></div>
                                    <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Height</label><input :value="Math.round(selected.h)" type="number" min="20" max="1920" :class="inputClass" @input="setImgH(($event.target as HTMLInputElement).value)" /></div>
                                </div>
                                <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Rotation {{ selected.rotation }}°</label><input v-model.number="selected.rotation" type="range" min="-180" max="180" class="w-full accent-primary" /></div>
                                <div v-if="selected.iconKey">
                                    <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Icon colour</label>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <button
                                            v-for="c in ICON_COLORS"
                                            :key="c"
                                            type="button"
                                            :class="['h-6 w-6 rounded-full border-2 transition', selected.iconColor === c ? 'border-primary' : 'border-border']"
                                            :style="{ backgroundColor: c }"
                                            :title="c"
                                            @click="recolorIcon(selected, c)"
                                        ></button>
                                    </div>
                                </div>
                                <p class="text-xs text-muted-foreground">Drag to move · drag the blue handle to resize (keeps aspect ratio).</p>
                            </div>
                        </template>

                        <template v-else>
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex items-center gap-2"><label class="text-xs font-medium text-muted-foreground">{{ selected.type === 'line' ? 'Color' : 'Fill' }}</label><input v-model="selected.color" type="color" class="h-9 w-10 cursor-pointer rounded border border-border bg-background" /></div>
                                    <label v-if="selected.type !== 'line'" class="flex cursor-pointer items-center gap-2 text-sm text-foreground"><input v-model="selected.fillEnabled" type="checkbox" class="h-4 w-4 rounded border-border accent-primary" /> Fill</label>
                                    <div v-if="selected.type !== 'line'" class="flex items-center gap-2"><label class="text-xs font-medium text-muted-foreground">Stroke</label><input v-model="selected.strokeColor" type="color" class="h-9 w-10 cursor-pointer rounded border border-border bg-background" /></div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">{{ selected.type === 'line' ? 'Length' : 'Width' }}</label><input v-model.number="selected.w" type="number" min="2" max="1080" :class="inputClass" /></div>
                                    <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">{{ selected.type === 'line' ? 'Thickness' : 'Height' }}</label><input v-model.number="selected.h" type="number" min="2" max="1920" :class="inputClass" /></div>
                                </div>
                                <div v-if="selected.type === 'line'"><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Curve {{ selected.curve }}</label><input v-model.number="selected.curve" type="range" min="-400" max="400" class="w-full accent-primary" /></div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Opacity {{ selected.opacity }}</label><input v-model.number="selected.opacity" type="range" min="0" max="1" step="0.05" class="w-full accent-primary" /></div>
                                    <div v-if="selected.type !== 'line'"><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Stroke width</label><input v-model.number="selected.strokeW" type="number" min="0" max="60" :class="inputClass" /></div>
                                </div>
                                <div v-if="selected.type === 'rect'">
                                    <div class="mb-1.5 flex items-center justify-between">
                                        <label class="text-xs font-medium text-muted-foreground">Corner radius (per corner)</label>
                                        <button type="button" class="rounded border border-border px-2 py-0.5 text-xs text-foreground hover:bg-muted" @click="linkCorners">All = top-left</button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="flex items-center gap-1.5 text-xs text-muted-foreground">TL<input v-model.number="selected.radiusTL" type="number" min="0" max="600" :class="[inputClass, 'py-1.5']" /></label>
                                        <label class="flex items-center gap-1.5 text-xs text-muted-foreground">TR<input v-model.number="selected.radiusTR" type="number" min="0" max="600" :class="[inputClass, 'py-1.5']" /></label>
                                        <label class="flex items-center gap-1.5 text-xs text-muted-foreground">BL<input v-model.number="selected.radiusBL" type="number" min="0" max="600" :class="[inputClass, 'py-1.5']" /></label>
                                        <label class="flex items-center gap-1.5 text-xs text-muted-foreground">BR<input v-model.number="selected.radiusBR" type="number" min="0" max="600" :class="[inputClass, 'py-1.5']" /></label>
                                    </div>
                                </div>
                                <div><label class="mb-1.5 block text-xs font-medium text-muted-foreground">Rotation {{ selected.rotation }}°</label><input v-model.number="selected.rotation" type="range" min="-180" max="180" class="w-full accent-primary" /></div>
                            </div>
                        </template>
                    </div>
                    <div v-else class="border-b border-border p-4 text-center text-sm text-muted-foreground">
                        <p>{{ selectedLayers.length > 1 ? `${selectedLayers.length} layers selected.` : 'Select a layer to edit it. Shift-click for several.' }}</p>
                        <div v-if="selectedLayers.length > 1 || copiedStyle" class="mt-2 flex flex-wrap items-center justify-center gap-1.5">
                            <button v-if="selectedLayers.length > 1" type="button" class="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted" title="Copy the style of the first selected layer" @click="copyStyle"><Paintbrush class="h-3.5 w-3.5" /> Copy style</button>
                            <button v-if="selectedLayers.length > 1 && copiedStyle" type="button" class="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted" @click="pasteStyle"><ClipboardPaste class="h-3.5 w-3.5" /> Paste to selected</button>
                        </div>
                        <p v-if="styleFeedback" class="mt-2 text-[11px] font-medium text-primary">{{ styleFeedback }}</p>
                    </div>

                    <!-- Layers -->
                    <div v-if="layers.length" class="border-b border-border p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-foreground">Layers</h2>
                            <span class="text-[11px] text-muted-foreground">drag to reorder</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <div
                                v-for="l in [...layers].reverse()"
                                :key="l.id"
                                draggable="true"
                                :class="['flex items-center gap-1.5 rounded-md border px-2 py-1.5 text-sm transition', selectedIds.includes(l.id) ? 'border-primary bg-primary/5' : 'border-transparent hover:bg-muted', dragLayerId === l.id ? 'opacity-50' : '']"
                                @dragstart="onLayerDragStart(l)"
                                @dragover.prevent
                                @drop="onLayerDrop(l)"
                                @dragend="dragLayerId = null"
                            >
                                <GripVertical class="h-3.5 w-3.5 shrink-0 cursor-grab text-muted-foreground" />
                                <button class="flex min-w-0 flex-1 items-center gap-2 text-left" @click="selectedIds = [l.id]; render()"><component :is="shapeIcon[l.type]" class="h-3.5 w-3.5 shrink-0 text-muted-foreground" /><span class="truncate text-foreground">{{ layerLabel(l) }}</span></button>
                                <button class="rounded p-1 text-muted-foreground hover:text-foreground" title="Forward" @click="reorder(l, 1)"><ArrowUp class="h-3.5 w-3.5" /></button>
                                <button class="rounded p-1 text-muted-foreground hover:text-foreground" title="Backward" @click="reorder(l, -1)"><ArrowDown class="h-3.5 w-3.5" /></button>
                                <button class="rounded p-1 text-muted-foreground hover:bg-destructive/10 hover:text-destructive" title="Delete" @click="deleteLayer(l.id)"><Trash2 class="h-3.5 w-3.5" /></button>
                            </div>
                        </div>
                    </div>

                    <!-- Category (admin only) -->
                    <div v-if="props.adminMode" class="border-b border-border p-4">
                        <h2 class="mb-1.5 text-sm font-semibold text-foreground">Category</h2>
                        <p class="mb-3 text-xs text-muted-foreground">Which category tenants will see this design under.</p>
                        <select v-model="posterCategoryId" :class="inputClass">
                            <option :value="null">— Uncategorized —</option>
                            <option v-for="c in props.posterCategories ?? []" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <!-- Data fields -->
                    <div v-if="fields.length" class="p-4">
                        <h2 class="mb-1 text-sm font-semibold text-foreground">Data fields</h2>
                        <p v-if="props.adminMode" class="mb-3 text-xs text-muted-foreground">Fill the {placeholders}. Prices/date shown here are just sample preview values.</p>
                        <p v-else class="mb-3 text-xs text-muted-foreground">Fill the {placeholders}. Enter daily rates from <Link href="/gold-poster/update" class="text-primary hover:underline">Update gold rate</Link>.</p>
                        <div class="flex flex-col gap-3">
                            <div v-for="key in fields" :key="key">
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">{{ key }}</label>
                                <input v-model="fieldValues[key]" type="text" :class="inputClass" />
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <!-- Icons & certification badges -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0" leave-active-class="transition duration-100 ease-in" leave-to-class="opacity-0">
                <div v-if="iconPickerOpen" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-[1px]" @click="iconPickerOpen = false"></div>
                    <div class="relative flex max-h-[82vh] w-full max-w-2xl flex-col overflow-hidden rounded-lg border border-border bg-card shadow-xl">
                        <div class="flex items-center justify-between border-b border-border px-5 py-3">
                            <h3 class="text-sm font-semibold text-foreground">Icons &amp; certifications</h3>
                            <button type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="iconPickerOpen = false"><X class="h-4 w-4" /></button>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="group in POSTER_ICON_GROUPS"
                                    :key="group.key"
                                    type="button"
                                    :class="['rounded-full border px-3 py-1 text-xs font-medium transition', iconGroup === group.key ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']"
                                    @click="iconGroup = group.key"
                                >
                                    {{ group.label }}
                                </button>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[11px] text-muted-foreground">Colour</span>
                                <button
                                    v-for="c in ICON_COLORS"
                                    :key="c"
                                    type="button"
                                    :class="['h-6 w-6 rounded-full border-2 transition', iconColor === c ? 'border-primary' : 'border-border']"
                                    :style="{ backgroundColor: c }"
                                    :title="c"
                                    @click="iconColor = c"
                                ></button>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3 overflow-auto p-5 sm:grid-cols-4">
                            <button
                                v-for="icon in iconGroupIcons"
                                :key="icon.key"
                                type="button"
                                class="flex flex-col items-center gap-2 rounded-md border border-border p-3 transition hover:border-primary hover:bg-muted/40"
                                @click="addIcon(icon.key)"
                            >
                                <span class="flex h-16 w-full items-center justify-center rounded bg-[#1b1b1f] p-2">
                                    <img :src="iconPreview(icon.key)" :alt="icon.label" class="max-h-full max-w-full" />
                                </span>
                                <span class="text-[11px] font-medium text-foreground">{{ icon.label }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Background library -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0" leave-active-class="transition duration-100 ease-in" leave-to-class="opacity-0">
                <div v-if="libraryOpen" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-[1px]" @click="libraryOpen = false"></div>
                    <div class="relative flex max-h-[82vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg border border-border bg-card shadow-xl">
                        <div class="flex items-center justify-between border-b border-border px-5 py-3">
                            <h3 class="text-sm font-semibold text-foreground">Background library <span class="font-normal text-muted-foreground">· {{ libraryItems.length }} of {{ backgroundLibrary.length }}</span></h3>
                            <button type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="libraryOpen = false"><X class="h-4 w-4" /></button>
                        </div>
                        <div class="space-y-3 border-b border-border px-5 py-3">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                                <input v-model="librarySearch" type="search" placeholder="Search backgrounds…" class="w-full rounded-md border border-input bg-background py-2 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25" />
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="cat in libraryCategories"
                                    :key="cat"
                                    type="button"
                                    :class="['rounded-full border px-3 py-1 text-xs font-medium capitalize transition', libraryCategory === cat ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']"
                                    @click="libraryCategory = cat"
                                >
                                    {{ cat }}
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-2.5 overflow-auto p-5 sm:grid-cols-6 lg:grid-cols-8">
                            <button
                                v-if="bgImage"
                                type="button"
                                class="flex aspect-[9/16] flex-col items-center justify-center gap-1 rounded-md border border-dashed border-border px-1 text-center text-[10px] font-medium text-muted-foreground transition hover:border-destructive hover:text-destructive"
                                @click="removeBackground(); libraryOpen = false"
                            >
                                <Trash2 class="h-3.5 w-3.5" /> None
                            </button>
                            <button
                                v-for="bg in libraryItems"
                                :key="bg.id"
                                type="button"
                                :class="['group relative overflow-hidden rounded-md border-2 text-left transition', bgSrc === bg.url ? 'border-primary' : 'border-border hover:border-primary/60']"
                                :title="bg.name"
                                @click="applyLibraryBackground(bg)"
                            >
                                <img :src="bg.url" :alt="bg.name" class="aspect-[9/16] w-full object-cover" loading="lazy" />
                                <span class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/75 to-transparent px-1.5 pb-1 pt-4 text-[10px] font-medium text-white">{{ bg.name }}</span>
                                <span v-if="bgSrc === bg.url" class="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-primary-foreground"><Check class="h-2.5 w-2.5" /></span>
                            </button>
                            <div v-if="!libraryItems.length" class="col-span-full py-12 text-center">
                                <p class="text-sm text-muted-foreground">No backgrounds match “{{ librarySearch }}”.</p>
                                <button v-if="librarySearch" type="button" class="mt-2 text-xs font-medium text-primary hover:underline" @click="librarySearch = ''">Clear search</button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Browse all templates (previews) -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0" leave-active-class="transition duration-100 ease-in" leave-to-class="opacity-0">
                <div v-if="browseOpen" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-[1px]" @click="browseOpen = false"></div>
                    <div class="relative flex max-h-[80vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg border border-border bg-card shadow-xl">
                        <div class="flex items-center justify-between border-b border-border px-5 py-3">
                            <h3 class="text-sm font-semibold text-foreground">Templates <span class="font-normal text-muted-foreground">· {{ browseResults.length }} of {{ templateList.length }}</span></h3>
                            <button type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="browseOpen = false"><X class="h-4 w-4" /></button>
                        </div>
                        <div class="space-y-3 border-b border-border px-5 py-3">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                                <input v-model="browseSearch" type="search" placeholder="Search designs by name or type…" class="w-full rounded-md border border-input bg-background py-2 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25" />
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" :class="['rounded-full border px-3 py-1 text-xs font-medium transition', browseCategory === 'all' ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']" @click="browseCategory = 'all'">All</button>
                                <button type="button" :class="['rounded-full border px-3 py-1 text-xs font-medium transition', browseCategory === 'gold_price' ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']" @click="browseCategory = 'gold_price'">Gold price</button>
                                <button type="button" :class="['rounded-full border px-3 py-1 text-xs font-medium transition', browseCategory === 'poster' ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-muted']" @click="browseCategory = 'poster'">Posters</button>
                            </div>
                        </div>
                        <div class="overflow-auto p-5">
                            <div v-if="browseResults.length" class="grid grid-cols-3 gap-2.5 sm:grid-cols-5 lg:grid-cols-8">
                                <div
                                    v-for="t in browseResults"
                                    :key="t.id"
                                    :class="['overflow-hidden rounded-md border-2 transition', currentTemplateId === t.id ? 'border-primary' : 'border-border hover:border-primary/50']"
                                >
                                    <button class="relative block aspect-[9/16] w-full bg-muted" :title="t.name" @click="openFromBrowse(t.id)">
                                        <img v-if="browseThumbnails[t.id]" :src="browseThumbnails[t.id]" :alt="t.name" class="h-full w-full object-cover" />
                                        <span v-else class="flex h-full w-full animate-pulse items-center justify-center bg-muted px-2 text-center text-[10px] text-muted-foreground">{{ t.name }}</span>
                                        <span v-if="!props.adminMode && t.is_global" class="absolute left-1.5 top-1.5 rounded-full bg-background/90 px-1.5 py-0.5 text-[9px] font-medium text-muted-foreground">Default</span>
                                    </button>
                                    <div class="flex items-center justify-between gap-1 px-2 py-1.5">
                                        <button class="min-w-0 flex-1 truncate text-left text-[11px] font-medium text-foreground transition hover:text-primary" @click="openFromBrowse(t.id)">{{ t.name }}</button>
                                        <button
                                            v-if="props.adminMode || !t.is_global"
                                            class="shrink-0 rounded p-1 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                                            title="Delete"
                                            @click="deleteTemplate(t.id)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-12 text-center">
                                <p class="text-sm text-muted-foreground">{{ templateList.length ? 'No designs match your search.' : 'No templates saved yet.' }}</p>
                                <button v-if="templateList.length && (browseSearch || browseCategory !== 'all')" type="button" class="mt-2 text-xs font-medium text-primary hover:underline" @click="browseSearch = ''; browseCategory = 'all'">Clear filters</button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <ModalHost />
    </component>
</template>
