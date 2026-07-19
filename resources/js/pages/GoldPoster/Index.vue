<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    AlignCenter, AlignHorizontalJustifyCenter, AlignLeft, AlignRight,
    AlignVerticalJustifyCenter, ArrowDown, ArrowUp, Bold, Cake, Circle, ClipboardPaste, Coins, Copy, Download,
    GripVertical, ImageUp, LayoutList, Magnet, Minus, Paintbrush, Ruler, Save, Square, Trash2, Type, X,
} from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ModalHost from '@/components/ModalHost.vue';
import { useModals } from '@/composables/useModals';
import { type BreadcrumbItem } from '@/types';

const modals = useModals();

interface TenantLike {
    email?: string | null; phone?: string | null; address?: string | null;
    logo_light_url?: string | null; logo_dark_url?: string | null;
}
interface TemplateSummary { id: number; name: string; category?: string | null; type?: string | null; updated_at?: string | null }

interface PlaceholderOption { key: string; label: string; category: string }
const props = defineProps<{ tenant: TenantLike | null; templates: TemplateSummary[]; open?: number | null; placeholders?: PlaceholderOption[] }>();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Templates', href: '/gold-poster' }];

const page = usePage();
const tenant = (props.tenant ?? (page.props.auth?.tenant as TenantLike | undefined) ?? {}) as TenantLike;

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
    // image
    imgId: number | null; w: number; h: number; naturalRatio: number;
    // shape
    opacity: number; radius: number; strokeW: number; strokeColor: string; curve: number; fillEnabled: boolean;
    radiusTL: number; radiusTR: number; radiusBR: number; radiusBL: number;
}

let uid = 1;
const layers = ref<Layer[]>([]);
const selectedIds = ref<number[]>([]);
const bgColor = ref('#0d3b34');
const bgSrc = ref<string | null>(null);
const bgImage = ref<HTMLImageElement | null>(null);
const fieldValues = reactive<Record<string, string>>({});
const imgStore: Record<number, { img: HTMLImageElement; src: string }> = {};
const logoIds = reactive<{ light: number | null; dark: number | null }>({ light: null, dark: null });

const templateList = ref<TemplateSummary[]>([...props.templates]);
const currentTemplateId = ref<number | null>(null);
const currentName = ref('');
const currentCategory = ref<'gold_price' | 'poster'>('gold_price');
const currentType = ref('');


const canvasRef = ref<HTMLCanvasElement | null>(null);
const started = ref(false);
const snapEnabled = ref(true);
const showGrid = ref(false);
const guideX = ref<number | null>(null);
const guideY = ref<number | null>(null);

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
async function onAddImage(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]; if (!file) return;
    const src = await readFile(file); const img = await loadImage(src); addImageLayer(registerImage(img, src));
    (e.target as HTMLInputElement).value = '';
}
function addLogo(which: 'light' | 'dark') { const id = logoIds[which]; if (id != null) addImageLayer(id, 270); }
async function onBackground(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]; if (!file) return;
    bgSrc.value = await readFile(file); bgImage.value = await loadImage(bgSrc.value); render();
    (e.target as HTMLInputElement).value = '';
}
function removeBackground() { bgImage.value = null; bgSrc.value = null; render(); }

function deleteLayer(id: number) { layers.value = layers.value.filter((l) => l.id !== id); selectedIds.value = selectedIds.value.filter((s) => s !== id); render(); }
function duplicateLayer(l: Layer) { const c = { ...l, id: uid++, x: l.x + 40, y: l.y + 40 }; layers.value.push(c); selectedIds.value = [c.id]; render(); }
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
const copiedStyle = ref<Partial<Layer> | null>(null);
function copyStyle() {
    const l = selected.value; if (!l || l.type !== 'text') return;
    copiedStyle.value = { fontFamily: l.fontFamily, fontSize: l.fontSize, weight: l.weight, color: l.color, align: l.align, shadow: l.shadow, letterSpacing: l.letterSpacing };
}
function pasteStyle() {
    if (!copiedStyle.value) return;
    for (const l of selectedLayers.value) if (l.type === 'text') Object.assign(l, copiedStyle.value);
    render();
}
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
    ctx.font = `${l.weight} ${l.fontSize}px "${l.fontFamily}"`;
    try { ctx.letterSpacing = `${l.letterSpacing}px`; } catch { /* unsupported */ }
    const lines = (displayText(l) || ' ').split('\n');
    let w = 0; for (const ln of lines) w = Math.max(w, ctx.measureText(ln || ' ').width);
    try { ctx.letterSpacing = '0px'; } catch { /* unsupported */ }
    return { w, h: lines.length * l.fontSize * 1.25 };
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
    if (l.type === 'image') { const im = l.imgId != null ? imgStore[l.imgId]?.img : null; if (im) ctx.drawImage(im, -l.w / 2, -l.h / 2, l.w, l.h); return; }
    if (l.type === 'text') {
        ctx.save();
        if (l.shadow) { ctx.shadowColor = 'rgba(0,0,0,0.55)'; ctx.shadowBlur = 12; }
        ctx.font = `${l.weight} ${l.fontSize}px "${l.fontFamily}"`;
        ctx.fillStyle = l.color; ctx.textBaseline = 'middle';
        const s = contentSize(l, ctx); const lineH = l.fontSize * 1.25; const lines = displayText(l).split('\n');
        // re-apply after contentSize (which resets letterSpacing while measuring)
        ctx.font = `${l.weight} ${l.fontSize}px "${l.fontFamily}"`;
        try { ctx.letterSpacing = `${l.letterSpacing}px`; } catch { /* unsupported */ }
        let ty = -s.h / 2 + lineH / 2;
        for (const line of lines) { ctx.textAlign = l.align; const tx = l.align === 'left' ? -s.w / 2 : l.align === 'right' ? s.w / 2 : 0; ctx.fillText(line, tx, ty); ty += lineH; }
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
    if (bgImage.value) {
        const img = bgImage.value; const sc = Math.max(canvasW.value / img.width, canvasH.value / img.height);
        ctx.drawImage(img, (canvasW.value - img.width * sc) / 2, (canvasH.value - img.height * sc) / 2, img.width * sc, img.height * sc);
    } else { ctx.fillStyle = bgColor.value; ctx.fillRect(0, 0, canvasW.value, canvasH.value); }

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
const drag = reactive({ mode: null as null | 'move' | 'resize', startDist: 1, startFont: 0, startW: 0, startH: 0, items: [] as { l: Layer; x0: number; y0: number }[], px0: 0, py0: 0 });
function toCanvas(e: PointerEvent) { const r = canvasRef.value!.getBoundingClientRect(); return { x: (e.clientX - r.left) * (canvasW.value / r.width), y: (e.clientY - r.top) * (canvasH.value / r.height) }; }
function hitLayer(p: { x: number; y: number }, ctx: CanvasRenderingContext2D) {
    for (let i = layers.value.length - 1; i >= 0; i--) {
        const l = layers.value[i]; const s = contentSize(l, ctx); const loc = toLocal(l, p.x, p.y);
        if (Math.abs(loc.x) <= s.w / 2 + 8 && Math.abs(loc.y) <= s.h / 2 + 8) return l;
    }
    return null;
}
function onPointerDown(e: PointerEvent) {
    const ctx = getCtx(); if (!ctx) return; const p = toCanvas(e);
    if (selected.value) {
        const l = selected.value; const s = contentSize(l, ctx); const loc = toLocal(l, p.x, p.y);
        if (Math.hypot(loc.x - (s.w / 2 + 8), loc.y - (s.h / 2 + 8)) <= HANDLE) {
            drag.mode = 'resize'; drag.startDist = Math.max(20, Math.hypot(p.x - l.x, p.y - l.y));
            drag.startFont = l.fontSize; drag.startW = l.w; drag.startH = l.h;
            canvasRef.value!.setPointerCapture(e.pointerId); return;
        }
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
    if (!drag.mode) return; const p = toCanvas(e);
    if (drag.mode === 'move') { const dx = p.x - drag.px0; const dy = p.y - drag.py0; for (const it of drag.items) { it.l.x = Math.round(it.x0 + dx); it.l.y = Math.round(it.y0 + dy); } const ctx = getCtx(); if (ctx) applySnap(ctx); }
    else if (selected.value) {
        const l = selected.value;
        if (l.type === 'text') {
            const ratio = Math.hypot(p.x - l.x, p.y - l.y) / drag.startDist;
            l.fontSize = Math.min(600, Math.max(10, Math.round(drag.startFont * ratio)));
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
function onPointerUp() { drag.mode = null; drag.items = []; guideX.value = null; guideY.value = null; render(); }

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
    const xT = [0, canvasW.value / 2, canvasW.value]; const yT = [0, canvasH.value / 2, canvasH.value];
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
    for (const l of layers.value) if (l.type === 'image' && l.imgId != null && imgStore[l.imgId]) images[l.imgId] = imgStore[l.imgId].src;
    const fields: Record<string, string> = {};
    for (const [k, v] of Object.entries(fieldValues)) fields[k] = v == null ? '' : String(v);
    return { version: 1, canvas: { w: canvasW.value, h: canvasH.value }, bg: { color: bgColor.value, src: bgSrc.value }, images, layers: layers.value.map((l) => ({ ...l })), fields };
}
async function deserialize(doc: ReturnType<typeof serialize>) {
    layers.value = []; selectedIds.value = [];
    Object.keys(fieldValues).forEach((k) => delete fieldValues[k]);
    canvasW.value = doc.canvas?.w ?? 1080;
    canvasH.value = doc.canvas?.h ?? 1920;
    bgColor.value = doc.bg?.color ?? '#0d3b34';
    bgSrc.value = doc.bg?.src ?? null;
    bgImage.value = bgSrc.value ? await loadImage(bgSrc.value) : null;
    const map: Record<number, number> = {};
    for (const [oldId, src] of Object.entries(doc.images ?? {})) { try { const img = await loadImage(src as string); map[Number(oldId)] = registerImage(img, src as string); } catch { /* skip */ } }
    for (const [k, v] of Object.entries(doc.fields ?? {})) fieldValues[k] = v == null ? '' : String(v);
    layers.value = (doc.layers ?? []).map((l) => ({ ...makeLayer({}), ...(l as Layer), id: uid++, imgId: l.imgId != null ? map[l.imgId] ?? null : null }));
    render();
}
async function saveTemplate(asNew = false) {
    const name = await modals.prompt('Template name', { title: asNew ? 'Save as new template' : 'Save template', default: asNew ? '' : currentName.value, placeholder: 'e.g. Gold rate poster', confirmText: 'Save' });
    if (!name) return;
    try {
        const document = serialize();
        const category = currentCategory.value;
        const type = currentType.value || null;
        if (!asNew && currentTemplateId.value) {
            const { data } = await axios.put(`/gold-poster/templates/${currentTemplateId.value}`, { name, category, type, document });
            currentName.value = data.name; const i = templateList.value.findIndex((t) => t.id === data.id); if (i >= 0) templateList.value[i] = data;
        } else {
            const { data } = await axios.post('/gold-poster/templates', { name, category, type, document });
            currentTemplateId.value = data.id; currentName.value = data.name; templateList.value.unshift(data);
        }
    } catch { modals.alert('Could not save the template. The background image may be too large.', { title: 'Save failed' }); }
}
async function loadTemplate(id: number) {
    if (!id) return;
    try { const { data } = await axios.get(`/gold-poster/templates/${id}`); await deserialize(data.document); currentTemplateId.value = data.id; currentName.value = data.name; currentCategory.value = data.category === 'poster' ? 'poster' : 'gold_price'; currentType.value = data.type ?? ''; started.value = true; }
    catch { modals.alert('Could not load that template.', { title: 'Load failed' }); }
}
async function deleteTemplate(id: number) {
    if (!(await modals.confirm('This template will be permanently removed.', { title: 'Delete template?', confirmText: 'Delete', danger: true }))) return;
    try { await axios.delete(`/gold-poster/templates/${id}`); templateList.value = templateList.value.filter((t) => t.id !== id); if (currentTemplateId.value === id) { currentTemplateId.value = null; currentName.value = ''; } }
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
function initFieldDefaults() { for (const l of layers.value) if (l.field && fieldValues[l.field] === undefined) fieldValues[l.field] = l.text; }

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
    currentTemplateId.value = null; currentName.value = '';
    started.value = true;
    if (preset === 'gold_rate') { currentCategory.value = 'gold_price'; currentType.value = 'Gold rate'; seedGoldRate(); }
    else if (preset === 'birthday') { currentCategory.value = 'poster'; currentType.value = 'Birthday'; seedBirthday(); }
    else { currentCategory.value = 'poster'; currentType.value = ''; canvasW.value = 1080; canvasH.value = 1920; addLogoTop(); addContact(); initFieldDefaults(); }
    render();
}
function onNewSelect(e: Event) {
    const el = e.target as HTMLSelectElement; const v = el.value; el.value = '';
    if (v) newTemplate(v as 'gold_rate' | 'birthday' | 'blank');
}

onMounted(async () => {
    await ensureFonts();
    if (tenant.logo_light_url) { try { logoIds.light = registerImage(await loadLogo(tenant.logo_light_url)); } catch { /* ignore */ } }
    if (tenant.logo_dark_url) { try { logoIds.dark = registerImage(await loadLogo(tenant.logo_dark_url)); } catch { /* ignore */ } }
    // Start empty — the user chooses a preset or loads a template (or opens one via ?template=).
    if (props.open) await loadTemplate(props.open);
    render();
    document.fonts.addEventListener('loadingdone', () => render());
    fetchLatestRate();
});
watch([layers, bgColor, fieldValues], () => render(), { deep: true });
// Render AFTER the canvas element's width/height attributes update (they reset the
// canvas buffer), otherwise switching size leaves the canvas blank.
watch([canvasW, canvasH], () => render(), { flush: 'post' });

function setCanvasSize(w: number, h: number) {
    canvasW.value = Math.max(200, Math.min(4000, Math.round(w) || 1080));
    canvasH.value = Math.max(200, Math.min(4000, Math.round(h) || 1080));
}

const inputClass = 'w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground transition focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25';
const shapeIcon = { rect: Square, ellipse: Circle, line: Minus, image: ImageUp, text: Type } as Record<LayerType, typeof Type>;
function layerLabel(l: Layer): string { if (l.type !== 'text') return l.type[0].toUpperCase() + l.type.slice(1); return (l.field ? `{${l.field}} ` : '') + displayText(l).replace(/\n/g, ' ').slice(0, 20); }

function categoryLabel(c: string): string { return c === 'poster' ? 'Poster' : 'Gold price'; }

const browseOpen = ref(false);
async function openFromBrowse(id: number) { browseOpen.value = false; await loadTemplate(id); }

// ── Mobile panel tabs ─────────────────────────────────────────────────
const mobilePanel = ref<'tools' | 'canvas' | 'props'>('canvas');
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
                </div>
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <select class="h-8 rounded-md border border-input bg-background px-2 text-xs text-foreground sm:h-9 sm:text-sm" @change="onNewSelect">
                        <option value="">New…</option>
                        <option value="gold_rate">Gold rate</option>
                        <option value="birthday">Birthday poster</option>
                        <option value="blank">Blank poster</option>
                    </select>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-md border border-border px-2 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted sm:px-3 sm:py-2 sm:text-sm" @click="browseOpen = true"><LayoutList class="h-4 w-4" /> <span class="hidden sm:inline">Browse</span></button>
                    <button v-if="currentTemplateId" type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive sm:p-2" title="Delete template" @click="deleteTemplate(currentTemplateId)"><Trash2 class="h-4 w-4" /></button>
                    <button type="button" class="hidden items-center gap-1.5 rounded-md border border-border px-2 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted sm:inline-flex sm:px-3 sm:py-2 sm:text-sm" @click="saveTemplate(true)">Save as new</button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-md border border-border px-2 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted sm:px-3 sm:py-2 sm:text-sm" @click="saveTemplate(false)"><Save class="h-4 w-4" /> <span class="hidden sm:inline">Save</span></button>
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
                            <label class="flex cursor-pointer flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted"><ImageUp class="h-4 w-4" /> Image<input type="file" accept="image/*" class="hidden" @change="onAddImage" /></label>
                            <label class="flex cursor-pointer flex-col items-center gap-1 rounded-md border border-border py-2.5 text-center text-[11px] font-medium text-foreground transition hover:bg-muted"><ImageUp class="h-4 w-4" /> {{ bgImage ? 'Replace bg' : 'Bg image' }}<input type="file" accept="image/*" class="hidden" @change="onBackground" /></label>
                            <button v-if="logoIds.light != null" type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted" @click="addLogo('light')"><ImageUp class="h-4 w-4" /> Light logo</button>
                            <button v-if="logoIds.dark != null" type="button" class="flex flex-col items-center gap-1 rounded-md border border-border py-2.5 text-[11px] font-medium text-foreground transition hover:bg-muted" @click="addLogo('dark')"><ImageUp class="h-4 w-4" /> Dark logo</button>
                        </div>
                    </div>

                    <div class="border-t border-border pt-3">
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Background</div>
                        <div v-if="!bgImage" class="flex items-center justify-between gap-2">
                            <span class="text-xs text-muted-foreground">Color</span>
                            <input v-model="bgColor" type="color" class="h-8 w-12 cursor-pointer rounded border border-border bg-background" />
                        </div>
                        <button v-else type="button" class="inline-flex w-full items-center justify-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" @click="removeBackground"><Trash2 class="h-3.5 w-3.5" /> Remove background</button>
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
                </aside>

                <!-- CENTER: canvas -->
                <main :class="['min-w-0 flex-col bg-muted/40 md:flex md:flex-1', mobilePanel === 'canvas' ? 'flex flex-1' : 'hidden']">
                    <div v-if="selectedLayers.length" class="flex flex-wrap items-center gap-2 border-b border-border bg-card/70 px-3 py-1.5">
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
                    </div>
                    <div class="relative flex flex-1 items-center justify-center overflow-auto p-6">
                        <canvas v-show="started" ref="canvasRef" :width="canvasW" :height="canvasH" class="block max-h-full max-w-full touch-none select-none rounded shadow-lg ring-1 ring-black/10" @pointerdown="onPointerDown" @pointermove="onPointerMove" @pointerup="onPointerUp" @pointercancel="onPointerUp" />

                        <!-- Empty state — choose a template type or load one -->
                        <div v-if="!started" class="w-full max-w-md rounded-lg border border-dashed border-border bg-card p-8 text-center shadow-xs">
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
                                <div class="rounded-md border border-border p-3">
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-foreground"><input type="checkbox" :checked="!!selected.field" class="h-4 w-4 rounded border-border accent-primary" @change="toggleField(selected)" /> Dynamic field (placeholder)</label>
                                    <select v-if="selected.field" v-model="selected.field" :class="[inputClass, 'mt-2']" @change="onFieldSelect(selected)">
                                        <optgroup v-for="[cat, opts] in placeholderGroups" :key="cat" :label="phCatLabel(cat)">
                                            <option v-for="p in opts" :key="p.key" :value="p.key">{{ p.label }}</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2 border-t border-border pt-3">
                                    <button type="button" class="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted" title="Copy this text's style" @click="copyStyle"><Paintbrush class="h-3.5 w-3.5" /> Copy style</button>
                                    <button type="button" :disabled="!copiedStyle" class="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted disabled:opacity-40" title="Apply the copied style" @click="pasteStyle"><ClipboardPaste class="h-3.5 w-3.5" /> Paste style</button>
                                    <span v-if="copiedStyle" class="text-xs text-muted-foreground">style copied</span>
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
                        <button v-if="selectedLayers.length > 1 && copiedStyle" type="button" class="mt-2 inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted" @click="pasteStyle"><ClipboardPaste class="h-3.5 w-3.5" /> Paste style to selected</button>
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

                    <!-- Data fields -->
                    <div v-if="fields.length" class="p-4">
                        <h2 class="mb-1 text-sm font-semibold text-foreground">Data fields</h2>
                        <p class="mb-3 text-xs text-muted-foreground">Fill the {placeholders}. Enter daily rates from <Link href="/gold-poster/update" class="text-primary hover:underline">Update gold rate</Link>.</p>
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

        <!-- Browse all templates (table) -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0" leave-active-class="transition duration-100 ease-in" leave-to-class="opacity-0">
                <div v-if="browseOpen" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-[1px]" @click="browseOpen = false"></div>
                    <div class="relative flex max-h-[80vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg border border-border bg-card shadow-xl">
                        <div class="flex items-center justify-between border-b border-border px-5 py-3">
                            <h3 class="text-sm font-semibold text-foreground">Templates <span class="font-normal text-muted-foreground">· {{ templateList.length }}</span></h3>
                            <button type="button" class="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="browseOpen = false"><X class="h-4 w-4" /></button>
                        </div>
                        <div class="overflow-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-border bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                        <th class="px-5 py-2.5 font-medium">Name</th>
                                        <th class="px-5 py-2.5 font-medium">Category</th>
                                        <th class="px-5 py-2.5 font-medium">Type</th>
                                        <th class="px-5 py-2.5 font-medium">Edited</th>
                                        <th class="px-5 py-2.5 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr v-for="t in templateList" :key="t.id" :class="['transition hover:bg-muted/40', currentTemplateId === t.id ? 'bg-primary/5' : '']">
                                        <td class="px-5 py-2.5"><button class="text-left font-medium text-foreground transition hover:text-primary" @click="openFromBrowse(t.id)">{{ t.name }}</button></td>
                                        <td class="px-5 py-2.5"><span class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">{{ categoryLabel(t.category || 'gold_price') }}</span></td>
                                        <td class="px-5 py-2.5 text-muted-foreground">{{ t.type || '—' }}</td>
                                        <td class="px-5 py-2.5 text-muted-foreground">{{ relativeTime(t.updated_at) }}</td>
                                        <td class="px-5 py-2.5">
                                            <div class="flex items-center justify-end gap-1">
                                                <button class="rounded-md px-2.5 py-1 text-xs font-medium text-primary transition hover:bg-primary/10" @click="openFromBrowse(t.id)">Open</button>
                                                <button class="rounded-md p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" title="Delete" @click="deleteTemplate(t.id)"><Trash2 class="h-4 w-4" /></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!templateList.length"><td colspan="5" class="px-5 py-12 text-center text-sm text-muted-foreground">No templates saved yet.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <ModalHost />
    </AppLayout>
</template>
