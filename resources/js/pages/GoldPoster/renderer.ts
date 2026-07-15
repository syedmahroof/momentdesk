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
}

export interface PosterDocument {
    version?: number;
    canvas?: { w: number; h: number };
    bg?: { color: string; src: string | null };
    images?: Record<string, string>;
    layers: Layer[];
    fields?: Record<string, string>;
}

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

function contentSize(ctx: CanvasRenderingContext2D, l: Layer, fields: Fields) {
    if (l.type === 'line') return { w: l.w, h: Math.max(l.h, Math.abs(l.curve) + l.h) };
    if (l.type !== 'text') return { w: l.w, h: l.h };
    ctx.font = `${l.weight} ${l.fontSize}px "${l.fontFamily}"`;
    try { ctx.letterSpacing = `${l.letterSpacing}px`; } catch { /* unsupported */ }
    const lines = (displayText(l, fields) || ' ').split('\n');
    let w = 0; for (const ln of lines) w = Math.max(w, ctx.measureText(ln || ' ').width);
    try { ctx.letterSpacing = '0px'; } catch { /* unsupported */ }
    return { w, h: lines.length * l.fontSize * 1.25 };
}

function drawLayer(ctx: CanvasRenderingContext2D, l: Layer, getImage: (id: number) => HTMLImageElement | null, fields: Fields) {
    if (l.type === 'image') { const im = l.imgId != null ? getImage(l.imgId) : null; if (im) ctx.drawImage(im, -l.w / 2, -l.h / 2, l.w, l.h); return; }
    if (l.type === 'text') {
        ctx.save();
        if (l.shadow) { ctx.shadowColor = 'rgba(0,0,0,0.55)'; ctx.shadowBlur = 12; }
        ctx.font = `${l.weight} ${l.fontSize}px "${l.fontFamily}"`;
        ctx.fillStyle = l.color; ctx.textBaseline = 'middle';
        const s = contentSize(ctx, l, fields); const lineH = l.fontSize * 1.25; const lines = displayText(l, fields).split('\n');
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

export function renderDocument(canvas: HTMLCanvasElement, doc: PosterDocument, assets: PosterAssets, fields: Fields) {
    const ctx = canvas.getContext('2d'); if (!ctx) return;
    const W = doc.canvas?.w ?? 1080; const H = doc.canvas?.h ?? 1920;
    canvas.width = W; canvas.height = H;
    if (assets.bg) {
        const img = assets.bg; const sc = Math.max(W / img.width, H / img.height);
        ctx.drawImage(img, (W - img.width * sc) / 2, (H - img.height * sc) / 2, img.width * sc, img.height * sc);
    } else {
        ctx.fillStyle = doc.bg?.color ?? '#0d3b34'; ctx.fillRect(0, 0, W, H);
    }
    for (const l of doc.layers ?? []) {
        ctx.save(); ctx.translate(l.x, l.y); ctx.rotate((l.rotation || 0) * Math.PI / 180);
        drawLayer(ctx, l, (id) => assets.images[id] ?? null, fields);
        ctx.restore();
    }
}
