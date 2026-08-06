// Shared, dependency-free poster renderer used by the editor and the daily-update page.
// Keep the drawing logic here in sync with the editor's canvas rendering.

export interface Layer {
    id: number;
    type: 'text' | 'image' | 'rect' | 'ellipse' | 'line';
    x: number; y: number; rotation: number;
    text: string; fontFamily: string; fontSize: number; weight: number; color: string;
    align: CanvasTextAlign; shadow: boolean; letterSpacing: number; field: string;
    imgId: number | null; w: number; h: number; naturalRatio: number;
    opacity: number; radius: number; strokeW: number; strokeColor: string; curve: number; fillEnabled: boolean;
    radiusTL: number; radiusTR: number; radiusBR: number; radiusBL: number;
    /**
     * Optional text box. When `boxW`/`boxH` are set the text wraps to that width and is
     * aligned inside the box (`align` horizontally, `vAlign` vertically); when they are 0
     * the layer hugs its own content, which is how every pre-box template behaves.
     */
    boxW?: number; boxH?: number; vAlign?: VerticalAlign;
}

export type VerticalAlign = 'top' | 'middle' | 'bottom';

export interface StatusConfig {
    mode: 'text' | 'icon' | 'both';
    increaseText: string;
    decreaseText: string;
    icon: string;
    order: 'icon-first' | 'text-first';
}

export interface PosterBackground {
    color: string;
    src: string | null;
    /** Zoom on top of the cover fit: 1 fills the canvas exactly. */
    scale?: number;
    /** Pan from centre, in canvas units. */
    offsetX?: number;
    offsetY?: number;
}

export interface PosterDocument {
    version?: number;
    canvas?: { w: number; h: number };
    bg?: PosterBackground;
    images?: Record<string, string>;
    layers: Layer[];
    fields?: Record<string, string>;
    statusConfig?: StatusConfig;
}

// Reserved image-layer id: templates bind a layer to this id to show the current tenant's
// logo automatically. Callers inject the real logo URL into `document.images` at load time.
export const TENANT_LOGO_IMAGE_ID = 900001;

export interface PosterAssets {
    images: Record<number, HTMLImageElement>;
    bg: HTMLImageElement | null;
}

type Fields = Record<string, string>;

export function loadImage(src: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

export async function loadDocumentAssets(doc: PosterDocument): Promise<PosterAssets> {
    const images: Record<number, HTMLImageElement> = {};
    for (const [id, src] of Object.entries(doc.images ?? {})) {
        try { images[Number(id)] = await loadImage(src); } catch { /* skip */ }
    }
    let bg: HTMLImageElement | null = null;
    if (doc.bg?.src) { try { bg = await loadImage(doc.bg.src); } catch { /* skip */ } }
    return { images, bg };
}

export async function ensureFonts(families = ['Poppins', 'Montserrat', 'Oswald', 'Roboto', 'Bebas Neue', 'Anton', 'Playfair Display', 'Lora', 'Merriweather', 'Dancing Script', 'Great Vibes', 'Pacifico', 'Lobster', 'Caveat', 'Sacramento', 'Satisfy', 'Allura', 'Alex Brush', 'Tangerine', 'Kaushan Script', 'Parisienne', 'Cookie', 'Yellowtail', 'Pinyon Script']) {
    if (!document.getElementById('poster-fonts')) {
        const link = document.createElement('link');
        link.id = 'poster-fonts';
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Anton&family=Bebas+Neue&family=Caveat:wght@400;500;600;700&family=Cookie&family=Dancing+Script:wght@400;500;600;700&family=Great+Vibes&family=Kaushan+Script&family=Lobster&family=Lora:wght@400;500;600;700&family=Merriweather:wght@300;400;700;900&family=Montserrat:wght@300;400;500;600;700;800;900&family=Oswald:wght@300;400;500;600;700&family=Pacifico&family=Parisienne&family=Pinyon+Script&family=Playfair+Display:wght@400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&family=Roboto:wght@300;400;500;700;900&family=Sacramento&family=Satisfy&family=Tangerine:wght@400;700&family=Yellowtail&display=swap';
        document.head.appendChild(link);
    }
    try {
        await Promise.all(families.flatMap((f) => [document.fonts.load(`400 40px "${f}"`), document.fonts.load(`700 40px "${f}"`)]));
        await document.fonts.ready;
    } catch { /* system fallback */ }
}

export function displayText(l: Layer, fields: Fields): string {
    let t: unknown = l.text;
    if (l.field) { const v = fields[l.field]; if (v !== undefined && v !== null && v !== '') t = v; }
    return t == null ? '' : String(t);
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

/** Greedy word wrap to `maxWidth`, honouring the text's own line breaks. */
function wrapLines(ctx: CanvasRenderingContext2D, text: string, maxWidth: number): string[] {
    const out: string[] = [];
    for (const paragraph of text.split('\n')) {
        const words = paragraph.split(' ');
        let line = '';
        for (const word of words) {
            const next = line ? `${line} ${word}` : word;
            // A single word wider than the box stays on its own line rather than being cut up.
            if (line && ctx.measureText(next).width > maxWidth) { out.push(line); line = word; }
            else { line = next; }
        }
        out.push(line);
    }
    return out;
}

export interface TextLayout {
    lines: string[];
    /** Size of the layer's box — the fixed box if set, otherwise the text's own size. */
    w: number; h: number;
    lineH: number;
    /** Baseline y of the first line and the x the lines are drawn from, both box-relative. */
    startY: number; tx: number;
}

/**
 * Lays out a text layer inside its box. Shared with the editor so what a user positions
 * on screen is exactly what renders here.
 */
export function layoutText(ctx: CanvasRenderingContext2D, l: Layer, text: string): TextLayout {
    ctx.font = `${l.weight} ${l.fontSize}px "${l.fontFamily}", sans-serif`;
    try { ctx.letterSpacing = `${l.letterSpacing}px`; } catch { /* unsupported */ }
    const boxW = l.boxW && l.boxW > 0 ? l.boxW : 0;
    const lines = boxW ? wrapLines(ctx, text || ' ', boxW) : (text || ' ').split('\n');
    let contentW = 0;
    for (const ln of lines) contentW = Math.max(contentW, ctx.measureText(ln || ' ').width);
    try { ctx.letterSpacing = '0px'; } catch { /* unsupported */ }

    const lineH = l.fontSize * 1.25;
    const textH = lines.length * lineH;
    const w = boxW || contentW;
    const h = l.boxH && l.boxH > 0 ? l.boxH : textH;
    const vAlign = l.vAlign ?? 'middle';
    const startY = (vAlign === 'top' ? -h / 2 : vAlign === 'bottom' ? h / 2 - textH : -textH / 2) + lineH / 2;
    const tx = l.align === 'left' ? -w / 2 : l.align === 'right' ? w / 2 : 0;

    return { lines, w, h, lineH, startY, tx };
}

/**
 * The drawn size of a layer, in canvas units. Exported so callers that let users
 * drag or resize a layer can hit-test and outline it exactly where it renders.
 */
export function layerSize(ctx: CanvasRenderingContext2D, l: Layer, fields: Fields): { w: number; h: number } {
    return contentSize(ctx, l, fields);
}

function contentSize(ctx: CanvasRenderingContext2D, l: Layer, fields: Fields) {
    if (l.type === 'line') return { w: l.w, h: Math.max(l.h, Math.abs(l.curve) + l.h) };
    if (l.type !== 'text') return { w: l.w, h: l.h };
    const t = layoutText(ctx, l, displayText(l, fields));
    return { w: t.w, h: t.h };
}

function drawLayer(ctx: CanvasRenderingContext2D, l: Layer, getImage: (id: number) => HTMLImageElement | null, fields: Fields) {
    if (l.type === 'image') {
        const im = l.imgId != null ? getImage(l.imgId) : null;
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
        const t = layoutText(ctx, l, displayText(l, fields));
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

/**
 * Draws a background image scaled to cover the canvas, then adjusted by the document's own
 * zoom and pan so a photo can be framed deliberately rather than always centre-cropped.
 */
export function drawBackground(ctx: CanvasRenderingContext2D, img: HTMLImageElement, W: number, H: number, bg?: PosterBackground) {
    const cover = Math.max(W / img.width, H / img.height);
    const scale = cover * (bg?.scale && bg.scale > 0 ? bg.scale : 1);
    const dw = img.width * scale;
    const dh = img.height * scale;
    ctx.drawImage(img, (W - dw) / 2 + (bg?.offsetX ?? 0), (H - dh) / 2 + (bg?.offsetY ?? 0), dw, dh);
}

export function renderDocument(canvas: HTMLCanvasElement, doc: PosterDocument, assets: PosterAssets, fields: Fields) {
    const ctx = canvas.getContext('2d'); if (!ctx) return;
    const W = doc.canvas?.w ?? 1080; const H = doc.canvas?.h ?? 1920;
    canvas.width = W; canvas.height = H;
    if (assets.bg) {
        // A zoomed-in background can leave gaps at the edges; the colour shows through there.
        ctx.fillStyle = doc.bg?.color ?? '#0d3b34'; ctx.fillRect(0, 0, W, H);
        drawBackground(ctx, assets.bg, W, H, doc.bg);
    } else {
        ctx.fillStyle = doc.bg?.color ?? '#0d3b34'; ctx.fillRect(0, 0, W, H);
    }
    for (const l of doc.layers ?? []) {
        ctx.save(); ctx.translate(l.x, l.y); ctx.rotate((l.rotation || 0) * Math.PI / 180);
        drawLayer(ctx, l, (id) => assets.images[id] ?? null, fields);
        ctx.restore();
    }
}
