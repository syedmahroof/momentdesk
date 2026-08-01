// Built-in artwork for the poster editor: line icons and jewellery certification badges.
//
// Every entry renders to a standalone SVG string in the requested colour, which the editor
// turns into a data URL and drops in as a normal image layer. Keeping the colour out of the
// stored artwork means a layer can be recoloured later by re-rendering from `key` + `color`.

export interface PosterIcon {
    key: string;
    label: string;
    /** Artwork for the icon's viewBox, drawn in the colour picked at insert time. */
    body: (color: string) => string;
    /** Defaults to a 120x120 square; badges override it with a wider box. */
    viewBox?: string;
}

const BADGE_VIEW_BOX = '0 0 220 100';

export interface PosterIconGroup {
    key: string;
    label: string;
    icons: PosterIcon[];
}

const stroke = (color: string, width = 6): string =>
    `fill="none" stroke="${color}" stroke-width="${width}" stroke-linecap="round" stroke-linejoin="round"`;

/** Contact + storefront icons, sized for the strip along the bottom of a rate poster. */
const CONTACT_ICONS: PosterIcon[] = [
    {
        key: 'phone',
        label: 'Phone',
        body: (c) => `<path ${stroke(c)} d="M30 22h18l8 20-10 6a44 44 0 0 0 22 22l6-10 20 8v18c0 4-3 7-7 6C50 88 12 50 12 22c0-4 3-7 7-7z" transform="translate(4 4)"/>`,
    },
    {
        key: 'whatsapp',
        label: 'WhatsApp',
        body: (c) => `<path ${stroke(c, 5)} d="M20 100l6-20a38 38 0 1 1 14 14z"/><path ${stroke(c, 5)} d="M44 46c0 16 14 30 30 30 4 0 8-4 6-8l-8-4-5 5a30 30 0 0 1-16-16l5-5-4-8c-4-2-8 2-8 6z"/>`,
    },
    {
        key: 'location',
        label: 'Location',
        body: (c) => `<path ${stroke(c)} d="M60 108s34-34 34-58a34 34 0 1 0-68 0c0 24 34 58 34 58z"/><circle cx="60" cy="50" r="13" ${stroke(c)}/>`,
    },
    {
        key: 'mail',
        label: 'Email',
        body: (c) => `<rect x="14" y="30" width="92" height="60" rx="8" ${stroke(c)}/><path ${stroke(c)} d="M18 36l42 30 42-30"/>`,
    },
    {
        key: 'globe',
        label: 'Website',
        body: (c) => `<circle cx="60" cy="60" r="44" ${stroke(c)}/><path ${stroke(c)} d="M16 60h88M60 16c14 16 14 72 0 88M60 16c-14 16-14 72 0 88"/>`,
    },
    {
        key: 'clock',
        label: 'Timing',
        body: (c) => `<circle cx="60" cy="60" r="44" ${stroke(c)}/><path ${stroke(c)} d="M60 32v30l20 12"/>`,
    },
    {
        key: 'instagram',
        label: 'Instagram',
        body: (c) => `<rect x="18" y="18" width="84" height="84" rx="24" ${stroke(c)}/><circle cx="60" cy="60" r="20" ${stroke(c)}/><circle cx="86" cy="34" r="5" fill="${c}"/>`,
    },
    {
        key: 'facebook',
        label: 'Facebook',
        body: (c) => `<rect x="18" y="18" width="84" height="84" rx="20" ${stroke(c)}/><path ${stroke(c)} d="M76 40h-8a10 10 0 0 0-10 10v52M46 62h24"/>`,
    },
];

/** Jewellery motifs for headings and dividers. */
const JEWELLERY_ICONS: PosterIcon[] = [
    {
        key: 'ring',
        label: 'Ring',
        body: (c) => `<circle cx="60" cy="74" r="32" ${stroke(c)}/><path ${stroke(c)} d="M60 14l16 18-16 18-16-18z"/>`,
    },
    {
        key: 'necklace',
        label: 'Necklace',
        body: (c) => `<path ${stroke(c)} d="M16 24a52 52 0 0 0 88 0"/><path ${stroke(c)} d="M60 66l14 16-14 20-14-20z"/>`,
    },
    {
        key: 'bangle',
        label: 'Bangle',
        body: (c) => `<circle cx="60" cy="60" r="42" ${stroke(c)}/><circle cx="60" cy="60" r="28" ${stroke(c, 4)} stroke-dasharray="8 10"/>`,
    },
    {
        key: 'earring',
        label: 'Earring',
        body: (c) => `<circle cx="60" cy="30" r="12" ${stroke(c)}/><path ${stroke(c)} d="M60 42c-16 14-16 34 0 48 16-14 16-34 0-48z"/>`,
    },
    {
        key: 'diamond',
        label: 'Diamond',
        body: (c) => `<path ${stroke(c)} d="M28 44h64l-32 60z"/><path ${stroke(c)} d="M40 20h40l12 24H28z"/>`,
    },
    {
        key: 'crown',
        label: 'Crown',
        body: (c) => `<path ${stroke(c)} d="M20 88h80l8-52-26 18-22-32-22 32-26-18z"/>`,
    },
    {
        key: 'sparkle',
        label: 'Sparkle',
        body: (c) => `<path ${stroke(c)} d="M60 14l10 32 32 10-32 10-10 32-10-32-32-10 32-10z"/>`,
    },
    {
        key: 'coin',
        label: 'Coin',
        body: (c) => `<circle cx="60" cy="60" r="40" ${stroke(c)}/><path ${stroke(c)} d="M46 42h28M46 56h28M64 42a12 12 0 0 1 0 28H52l22 22"/>`,
    },
];

/**
 * Hallmark and purity badges. Text is baked into the artwork with a system sans-serif so it
 * rasterises identically everywhere — no webfont is available inside an SVG image.
 */
const badge = (label: string, sub: string | null, color: string): string => {
    const main = sub
        ? `<text x="110" y="50" font-family="Arial, Helvetica, sans-serif" font-size="38" font-weight="700" fill="${color}" text-anchor="middle">${label}</text>
           <text x="110" y="76" font-family="Arial, Helvetica, sans-serif" font-size="15" font-weight="600" letter-spacing="2" fill="${color}" text-anchor="middle">${sub}</text>`
        : `<text x="110" y="64" font-family="Arial, Helvetica, sans-serif" font-size="42" font-weight="700" fill="${color}" text-anchor="middle">${label}</text>`;

    return `<rect x="6" y="6" width="208" height="88" rx="16" ${stroke(color, 4)}/>${main}`;
};

const CERTIFICATION_ICONS: PosterIcon[] = [
    { key: 'cert-916', label: '916 Hallmark', viewBox: BADGE_VIEW_BOX, body: (c) => badge('916', 'HALLMARK', c) },
    { key: 'cert-bis', label: 'BIS Hallmark', viewBox: BADGE_VIEW_BOX, body: (c) => badge('BIS', 'HALLMARKED', c) },
    { key: 'cert-22k', label: '22K Gold', viewBox: BADGE_VIEW_BOX, body: (c) => badge('22K', 'GOLD', c) },
    { key: 'cert-18k', label: '18K Gold', viewBox: BADGE_VIEW_BOX, body: (c) => badge('18K', 'GOLD', c) },
    { key: 'cert-24k', label: '24K Gold', viewBox: BADGE_VIEW_BOX, body: (c) => badge('24K', 'GOLD', c) },
    { key: 'cert-kdm', label: '916 KDM', viewBox: BADGE_VIEW_BOX, body: (c) => badge('KDM', '916 PURITY', c) },
    { key: 'cert-925', label: '925 Silver', viewBox: BADGE_VIEW_BOX, body: (c) => badge('925', 'SILVER', c) },
    { key: 'cert-huid', label: 'HUID', viewBox: BADGE_VIEW_BOX, body: (c) => badge('HUID', 'CERTIFIED', c) },
    {
        key: 'cert-hallmark-seal',
        label: 'Hallmark seal',
        body: (c) => `<circle cx="60" cy="60" r="46" ${stroke(c)}/><circle cx="60" cy="60" r="36" ${stroke(c, 3)}/>
            <text x="60" y="58" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="700" fill="${c}" text-anchor="middle">916</text>
            <text x="60" y="76" font-family="Arial, Helvetica, sans-serif" font-size="10" font-weight="600" letter-spacing="1" fill="${c}" text-anchor="middle">HALLMARK</text>`,
    },
    {
        key: 'cert-purity-assured',
        label: 'Purity assured',
        viewBox: BADGE_VIEW_BOX,
        body: (c) => `<rect x="6" y="6" width="208" height="88" rx="16" ${stroke(c, 4)}/>
            <path ${stroke(c, 5)} d="M44 50l10 12 20-24" transform="translate(-14 4)"/>
            <text x="136" y="45" font-family="Arial, Helvetica, sans-serif" font-size="22" font-weight="700" fill="${c}" text-anchor="middle">PURITY</text>
            <text x="136" y="72" font-family="Arial, Helvetica, sans-serif" font-size="18" font-weight="600" letter-spacing="1" fill="${c}" text-anchor="middle">ASSURED</text>`,
    },
];

export const POSTER_ICON_GROUPS: PosterIconGroup[] = [
    { key: 'certification', label: 'Certifications', icons: CERTIFICATION_ICONS },
    { key: 'contact', label: 'Contact', icons: CONTACT_ICONS },
    { key: 'jewellery', label: 'Jewellery', icons: JEWELLERY_ICONS },
];

export const ICON_COLORS = ['#e8c56a', '#ffffff', '#111111', '#c9992f', '#8b1d3f', '#0d3b34'];

export const DEFAULT_ICON_COLOR = ICON_COLORS[0];

export function findPosterIcon(key: string): PosterIcon | null {
    for (const group of POSTER_ICON_GROUPS) {
        const icon = group.icons.find((i) => i.key === key);
        if (icon) return icon;
    }

    return null;
}

/** Renders an icon to a data URL the canvas can draw straight away. */
export function posterIconDataUrl(key: string, color: string): string | null {
    const icon = findPosterIcon(key);
    if (!icon) return null;

    const viewBox = icon.viewBox ?? '0 0 120 120';
    const [, , width, height] = viewBox.split(' ').map(Number);
    // Rasterise at 4x so a badge scaled up on a 1080-wide canvas still has crisp edges.
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width * 4}" height="${height * 4}" viewBox="${viewBox}">${icon.body(color)}</svg>`;

    return `data:image/svg+xml;utf8,${encodeURIComponent(svg)}`;
}
