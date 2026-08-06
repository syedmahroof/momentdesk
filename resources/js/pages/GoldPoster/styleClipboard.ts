// The editor's style clipboard, kept free of Vue so it can be reasoned about and
// exercised on its own.
//
// Layers fall into three style families. Copying and pasting within a family carries the
// whole look; pasting across families carries the colour, so a palette can be pushed
// through a design made of text, shapes and icons in one go.

export type StyleFamily = 'text' | 'image' | 'shape';
export type StyleableType = 'text' | 'image' | 'rect' | 'ellipse' | 'line';

/** The parts of a layer this module needs; editors pass their own richer layer type. */
export interface StyleableLayer {
    type: StyleableType;
    color: string;
    /** Set on built-in icons and certification badges, which are pre-rasterised images. */
    iconKey?: string;
    iconColor?: string;
    /** Images keep their aspect ratio when a copied width is applied. */
    naturalRatio?: number;
}

export interface CopiedStyle {
    family: StyleFamily;
    props: Record<string, unknown>;
}

/**
 * What each family carries. Size and rotation are included: a pasted layer is expected to
 * come out looking like the one it was copied from, not just recoloured. For text, size is
 * the font size and the text box; images take a width and keep their own aspect ratio.
 */
export const STYLE_KEYS: Record<StyleFamily, string[]> = {
    text: ['fontFamily', 'fontSize', 'weight', 'color', 'align', 'vAlign', 'shadow', 'letterSpacing', 'boxW', 'boxH', 'rotation'],
    image: ['opacity', 'iconColor', 'w', 'h', 'rotation'],
    shape: ['color', 'opacity', 'strokeW', 'strokeColor', 'fillEnabled', 'radiusTL', 'radiusTR', 'radiusBR', 'radiusBL', 'curve', 'w', 'h', 'rotation'],
};

export function styleFamily(type: StyleableType): StyleFamily {
    return type === 'text' ? 'text' : type === 'image' ? 'image' : 'shape';
}

export function copyLayerStyle(layer: StyleableLayer): CopiedStyle {
    const family = styleFamily(layer.type);
    const source = layer as unknown as Record<string, unknown>;
    const props: Record<string, unknown> = {};
    for (const key of STYLE_KEYS[family]) {
        const value = source[key];
        if (value !== undefined) props[key] = value;
    }

    return { family, props };
}

export interface ApplyResult {
    /** True when the layer took any part of the copied style. */
    changed: boolean;
    /** Set when an icon needs re-rasterising in this colour; the caller does the redraw. */
    recolorIconTo: string | null;
}

/**
 * Applies a copied style to one layer, mutating it in place.
 *
 * Same family: every style property transfers. Different family: only the colour does,
 * because a font size means nothing to a rectangle.
 */
export function applyLayerStyle(copied: CopiedStyle, layer: StyleableLayer): ApplyResult {
    const target = layer as unknown as Record<string, unknown>;
    const family = styleFamily(layer.type);

    if (family === copied.family) {
        let changed = false;
        let recolorIconTo: string | null = null;
        for (const [key, value] of Object.entries(copied.props)) {
            // Icon colour is not a plain property — it needs the image redrawn.
            if (key === 'iconColor') {
                if (layer.iconKey && typeof value === 'string' && value !== layer.iconColor) { recolorIconTo = value; changed = true; }
                continue;
            }
            // An image takes the copied width but keeps its own proportions, so pasting a
            // wide badge's style onto a square icon doesn't squash it.
            if (layer.type === 'image' && key === 'h') continue;
            if (layer.type === 'image' && key === 'w' && typeof value === 'number') {
                const height = Math.max(1, Math.round(value / (layer.naturalRatio || 1)));
                if (target.w !== value || target.h !== height) { target.w = value; target.h = height; changed = true; }
                continue;
            }
            if (target[key] !== value) { target[key] = value; changed = true; }
        }

        return { changed, recolorIconTo };
    }

    const sourceColor = (copied.props.color ?? copied.props.iconColor) as string | undefined;
    if (!sourceColor) return { changed: false, recolorIconTo: null };

    if (layer.type === 'image') {
        if (!layer.iconKey || layer.iconColor === sourceColor) return { changed: false, recolorIconTo: null };

        return { changed: true, recolorIconTo: sourceColor };
    }
    if (layer.color === sourceColor) return { changed: false, recolorIconTo: null };
    layer.color = sourceColor;

    return { changed: true, recolorIconTo: null };
}
