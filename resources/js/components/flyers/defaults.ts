import { type FlyerElement } from '@/types';

/**
 * Stable unique ids for flyer elements. `crypto.randomUUID` is undefined in some browsers
 * on non-secure origins (http://) and in older environments.
 */
export function randomElementId(): string {
    const c = globalThis.crypto;

    if (typeof c?.randomUUID === 'function') {
        return c.randomUUID();
    }

    if (typeof c?.getRandomValues === 'function') {
        const bytes = new Uint8Array(16);
        c.getRandomValues(bytes);
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;
        const hex = [...bytes].map((b) => b.toString(16).padStart(2, '0')).join('');

        return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
    }

    return `el-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 11)}`;
}

export const flyerCategoryOptions = [
    { value: 'daily_gold_rate', label: 'Daily Gold Rate' },
    { value: 'daily_wishes', label: 'Daily Wishes' },
    { value: 'birthday_card', label: 'Birthday Card' },
    { value: 'work_anniversary', label: 'Work Anniversary' },
    { value: 'product_rate', label: 'Product Rate' },
    { value: 'custom', label: 'Custom' },
] as const;

export const CANVAS_STYLES = {
    DEFAULT_BG: '#ffffff',
    PLACEHOLDER_BG: '#f3f4f6',
    PLACEHOLDER_STROKE: '#9ca3af',
    PLACEHOLDER_TEXT: '#6b7280',
    MISSING_IMAGE_BG: '#e5e7eb',
    DEFAULT_TEXT_COLOR: '#111827',
    DEFAULT_FONT_FAMILY: 'sans-serif',
} as const;

export const IMAGE_VALIDATION_HELP = 'PNG, JPG or WebP (max 4MB)';

export const placeholderOptions = [
    { value: 'date', label: '{{date}}' },
    { value: 'title', label: '{{title}}' },
    { value: 'price', label: '{{price}}' },
    { value: 'message', label: '{{message}}' },
    { value: 'product_name', label: '{{product_name}}' },
    { value: 'celebrant_name', label: '{{celebrant_name}}' },
    { value: 'age', label: '{{age}}' },
    { value: 'years_of_service', label: '{{years_of_service}}' },
    { value: 'logo', label: 'Logo image' },
    { value: 'featured_image', label: 'Featured image' },
] as const;

export const PRESETS: Record<string, FlyerElement[]> = {
    daily_gold_rate: [
        {
            id: randomElementId(),
            type: 'text',
            key: 'date',
            label: 'Date',
            content: null,
            placeholder: '{{date}}',
            x: 90,
            y: 120,
            width: 1060,
            height: null,
            font_size: 44,
            color: '#111827',
            alignment: 'center',
            font_weight: 'medium',
        },
        {
            id: randomElementId(),
            type: 'text',
            key: 'title',
            label: 'Headline',
            content: null,
            placeholder: '{{title}}',
            x: 90,
            y: 260,
            width: 1060,
            height: null,
            font_size: 78,
            color: '#111827',
            alignment: 'center',
            font_weight: 'bold',
        },
        {
            id: randomElementId(),
            type: 'text',
            key: 'product_name',
            label: 'Product name',
            content: null,
            placeholder: '{{product_name}}',
            x: 90,
            y: 380,
            width: 1060,
            height: null,
            font_size: 36,
            color: '#4b5563',
            alignment: 'center',
            font_weight: 'medium',
        },
        {
            id: randomElementId(),
            type: 'text',
            key: 'price',
            label: 'Price',
            content: null,
            placeholder: '{{price}}',
            x: 90,
            y: 500,
            width: 1060,
            height: null,
            font_size: 120,
            color: '#dc2626',
            alignment: 'center',
            font_weight: 'bold',
        },
        {
            id: randomElementId(),
            type: 'image',
            key: 'logo',
            label: 'Logo',
            content: null,
            placeholder: null,
            x: 80,
            y: 60,
            width: 140,
            height: 140,
            font_size: null,
            color: null,
            alignment: null,
            font_weight: null,
        },
        {
            id: randomElementId(),
            type: 'image',
            key: 'featured_image',
            label: 'Featured Image',
            content: null,
            placeholder: null,
            x: 370,
            y: 1020,
            width: 500,
            height: 500,
            font_size: null,
            color: null,
            alignment: null,
            font_weight: null,
        },
    ],
    birthday_card: [
        {
            id: randomElementId(),
            type: 'image',
            key: 'logo',
            label: 'Logo',
            content: null,
            placeholder: null,
            x: 80,
            y: 60,
            width: 140,
            height: 140,
            font_size: null,
            color: null,
            alignment: null,
            font_weight: null,
        },
        {
            id: randomElementId(),
            type: 'text',
            key: 'celebrant_name',
            label: 'Name',
            content: null,
            placeholder: '{{celebrant_name}}',
            x: 90,
            y: 300,
            width: 1060,
            height: null,
            font_size: 72,
            color: '#be185d',
            alignment: 'center',
            font_weight: 'bold',
        },
        {
            id: randomElementId(),
            type: 'text',
            key: 'title',
            label: 'Wishes',
            content: 'Happy Birthday!',
            placeholder: '{{title}}',
            x: 90,
            y: 420,
            width: 1060,
            height: null,
            font_size: 48,
            color: '#db2777',
            alignment: 'center',
            font_weight: 'bold',
        },
        {
            id: randomElementId(),
            type: 'text',
            key: 'age',
            label: 'Age',
            content: null,
            placeholder: 'Turning {{age}}',
            x: 90,
            y: 520,
            width: 1060,
            height: null,
            font_size: 32,
            color: '#9d174d',
            alignment: 'center',
            font_weight: 'medium',
        },
        {
            id: randomElementId(),
            type: 'image',
            key: 'featured_image',
            label: 'Celebrant Photo',
            content: null,
            placeholder: null,
            x: 320,
            y: 650,
            width: 600,
            height: 600,
            font_size: null,
            color: null,
            alignment: null,
            font_weight: null,
        },
    ],
    work_anniversary: [
        {
            id: randomElementId(),
            type: 'image',
            key: 'logo',
            label: 'Logo',
            content: null,
            placeholder: null,
            x: 80,
            y: 60,
            width: 140,
            height: 140,
            font_size: null,
            color: null,
            alignment: null,
            font_weight: null,
        },
        {
            id: randomElementId(),
            type: 'text',
            key: 'celebrant_name',
            label: 'Employee Name',
            content: null,
            placeholder: '{{celebrant_name}}',
            x: 90,
            y: 350,
            width: 1060,
            height: null,
            font_size: 64,
            color: '#1e3a8a',
            alignment: 'center',
            font_weight: 'bold',
        },
        {
            id: randomElementId(),
            type: 'text',
            key: 'years_of_service',
            label: 'Years',
            content: null,
            placeholder: '{{years_of_service}} Years of Excellence',
            x: 90,
            y: 460,
            width: 1060,
            height: null,
            font_size: 42,
            color: '#1d4ed8',
            alignment: 'center',
            font_weight: 'semibold',
        },
        {
            id: randomElementId(),
            type: 'text',
            key: 'message',
            label: 'Thank you message',
            content: 'Thank you for your hard work and dedication.',
            placeholder: '{{message}}',
            x: 120,
            y: 800,
            width: 1000,
            height: null,
            font_size: 28,
            color: '#374151',
            alignment: 'center',
            font_weight: 'normal',
        },
    ],
};

export const digitalPostSizeOptions = [
    { value: 'instagram_post', label: 'Instagram Post (1080 × 1080)', width: 1080, height: 1080 },
    { value: 'whatsapp_status', label: 'WhatsApp Status (1080 × 1920)', width: 1080, height: 1920 },
    { value: 'facebook_post', label: 'Facebook Post (1200 × 630)', width: 1200, height: 630 },
    { value: 'custom', label: 'Custom Size', width: 1080, height: 1080 },
] as const;

export const DEFAULT_TEXT_ELEMENT: Partial<FlyerElement> = {
    type: 'text',
    key: 'message',
    label: 'New Text Block',
    content: null,
    placeholder: '{{message}}',
    x: 100,
    y: 100,
    width: 800,
    height: null,
    font_size: 32,
    color: '#111827',
    alignment: 'left',
    font_weight: 'normal',
};

export const DEFAULT_IMAGE_ELEMENT: Partial<FlyerElement> = {
    type: 'image',
    key: 'logo',
    label: 'New Image Slot',
    content: null,
    placeholder: null,
    x: 100,
    y: 100,
    width: 200,
    height: 200,
};

export function getDesignerTip(category: string): string {
    const tips: Record<string, string> = {
        daily_gold_rate: 'Ensure the price text is large and high-contrast. Use center alignment for the product name to maintain a balanced, professional look.',
        birthday_card: 'Use playful colors and leave plenty of room for the celebrant photo. Script-style fonts work well for the name placeholder.',
        work_anniversary: 'Keep the design clean and corporate. The "Years of Service" should be bold and prominent to celebrate the milestone.',
        product_rate: 'Group product names and rates clearly. Using a light background helps information-heavy flyers stay readable.',
    };

    return tips[category] || 'Maintain clear hierarchy and use meaningful labels for your placeholders. High-quality background images lead to better engagement.';
}

export function createDefaultElements(category: string = 'daily_gold_rate'): FlyerElement[] {
    const elements = PRESETS[category] || PRESETS.daily_gold_rate;

    return JSON.parse(JSON.stringify(elements)).map((el: FlyerElement) => ({
        ...el,
        id: randomElementId(),
    }));
}

export function createDefaultFieldValues(category: string = 'daily_gold_rate'): Record<string, string> {
    const common = {
        date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }),
        logo: '/assets/logo-placeholder.png',
    };

    if (category === 'birthday_card') {
        return {
            ...common,
            title: 'Happy Birthday!',
            celebrant_name: 'John Doe',
            age: '28',
            message: 'Wishing you a day filled with happiness and a year filled with joy.',
        };
    }

    if (category === 'work_anniversary') {
        return {
            ...common,
            title: 'Work Anniversary',
            celebrant_name: 'Jane Smith',
            years_of_service: '5',
            message: 'Congratulations on this milestone! Thank you for your dedication.',
        };
    }

    if (category === 'daily_gold_rate') {
        return {
            ...common,
            title: 'Daily Gold Rate',
            price: '₹ 7,550',
            product_name: '22K Gold (per gram)',
            message: 'Rates are subject to market fluctuations.',
        };
    }

    return {
        ...common,
        title: 'Sample Template',
        price: '₹ 0.00',
        message: 'Lorem ipsum dolor sit amet...',
        product_name: 'Sample Product',
        celebrant_name: 'John Doe',
        age: '25',
        years_of_service: '5',
    };
}

export function getCategoryLabel(category: string): string {
    return flyerCategoryOptions.find((opt) => opt.value === category)?.label || 'Custom';
}

export function createDefaultElementOverrides(elements: FlyerElement[]): Record<string, Partial<FlyerElement>> {
    return Object.fromEntries(
        elements.map((element) => [
            element.id,
            {
                x: element.x,
                y: element.y,
                font_size: element.font_size,
                color: element.color,
                alignment: element.alignment,
                font_weight: element.font_weight,
            },
        ]),
    );
}
