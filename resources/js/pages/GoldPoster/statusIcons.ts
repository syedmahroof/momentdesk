// Icon choices for "icon only" price-change display. Add more entries here as needed —
// each one just needs an up/down glyph pair that canvas fillText can render directly.
export interface StatusIconSet {
    key: string;
    label: string;
    up: string;
    down: string;
}

export const STATUS_ICON_SETS: StatusIconSet[] = [
    { key: 'triangle', label: 'Triangle', up: '▲', down: '▼' },
    { key: 'arrow', label: 'Arrow', up: '↑', down: '↓' },
    { key: 'double-arrow', label: 'Double arrow', up: '⇑', down: '⇓' },
    { key: 'chevron', label: 'Chevron', up: '⌃', down: '⌄' },
    { key: 'circled-arrow', label: 'Circled arrow', up: '⬆', down: '⬇' },
];

export const DEFAULT_STATUS_ICON_SET = STATUS_ICON_SETS[0].key;

export function getStatusIconGlyph(setKey: string | undefined, direction: 'up' | 'down'): string {
    const set = STATUS_ICON_SETS.find((s) => s.key === setKey) ?? STATUS_ICON_SETS[0];
    return direction === 'up' ? set.up : set.down;
}

export interface StatusConfigLike {
    mode: 'text' | 'icon' | 'both';
    increaseText: string;
    decreaseText: string;
    icon: string;
    order: 'icon-first' | 'text-first';
}

export function buildStatusMessage(config: StatusConfigLike, direction: 'up' | 'down', diff: number): string {
    const icon = getStatusIconGlyph(config.icon, direction);
    const text = (direction === 'up' ? config.increaseText : config.decreaseText).replace('{diff}', String(diff));

    if (config.mode === 'icon') return icon;
    if (config.mode === 'text') return text;
    return config.order === 'text-first' ? `${text} ${icon}` : `${icon} ${text}`;
}
