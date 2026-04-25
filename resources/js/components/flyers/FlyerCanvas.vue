<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { type FlyerElement } from '@/types';
import { CANVAS_STYLES } from './defaults';

const props = withDefaults(defineProps<{
    width: number;
    height: number;
    backgroundType: 'color' | 'image';
    backgroundColor: string | null;
    backgroundImageUrl?: string | null;
    elements: FlyerElement[];
    fieldValues?: Record<string, string>;
    elementOverrides?: Record<string, Partial<FlyerElement>>;
    assetUrls?: Record<string, string>;
    draggable?: boolean;
}>(), {
    draggable: false,
});

const emit = defineEmits<{
    (event: 'move-element', payload: { id: string; x: number; y: number }): void;
    (event: 'resize-element', payload: { id: string; width: number; height: number }): void;
    (event: 'select-element', payload: { id: string }): void;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const canvasBoxRef = ref<HTMLDivElement | null>(null);
const scale = computed(() => 520 / props.width);
const scaledWidth = computed(() => props.width * scale.value);
const scaledHeight = computed(() => props.height * scale.value);

type InteractionState = {
    id: string;
    mode: 'move' | 'resize';
    offsetX: number;
    offsetY: number;
};

const interactionState = ref<InteractionState | null>(null);
const selectedElementId = ref<string | null>(null);

function elementState(element: FlyerElement): FlyerElement {
    const override = props.elementOverrides?.[element.id] ?? {};

    return {
        ...element,
        ...override,
    };
}

function displayWidth(element: FlyerElement): number {
    if (element.width && element.width > 0) {
        return element.width;
    }

    if (element.type === 'image') {
        return 160;
    }

    return 260;
}

function displayHeight(element: FlyerElement): number {
    if (element.height && element.height > 0) {
        return element.height;
    }

    if (element.type === 'image') {
        return 160;
    }

    return Math.max(42, Math.round((element.font_size ?? 28) * 1.4));
}

const draggableElements = computed(() =>
    props.elements.map((rawElement) => {
        const element = elementState(rawElement);

        return {
            id: element.id,
            label: element.label,
            type: element.type,
            x: element.x,
            y: element.y,
            width: displayWidth(element),
            height: displayHeight(element),
        };
    }),
);

function clamp(value: number, min: number, max: number): number {
    return Math.min(Math.max(value, min), max);
}

function onDragStart(elementId: string, event: PointerEvent): void {
    if (!props.draggable || !canvasBoxRef.value) {
        return;
    }

    const targetElement = draggableElements.value.find((item) => item.id === elementId);

    if (!targetElement) {
        return;
    }

    const rect = canvasBoxRef.value.getBoundingClientRect();
    const pointerX = (event.clientX - rect.left) / scale.value;
    const pointerY = (event.clientY - rect.top) / scale.value;

    selectedElementId.value = elementId;
    emit('select-element', { id: elementId });

    interactionState.value = {
        id: elementId,
        mode: 'move',
        offsetX: pointerX - targetElement.x,
        offsetY: pointerY - targetElement.y,
    };
}

function onResizeStart(elementId: string, event: PointerEvent): void {
    if (!props.draggable || !canvasBoxRef.value) {
        return;
    }

    const targetElement = draggableElements.value.find((item) => item.id === elementId);

    if (!targetElement) {
        return;
    }

    selectedElementId.value = elementId;
    emit('select-element', { id: elementId });

    event.stopPropagation();

    interactionState.value = {
        id: elementId,
        mode: 'resize',
        offsetX: targetElement.x + targetElement.width,
        offsetY: targetElement.y + targetElement.height,
    };
}

function onDragMove(event: PointerEvent): void {
    if (!props.draggable || !canvasBoxRef.value || !interactionState.value) {
        return;
    }

    const targetElement = draggableElements.value.find((item) => item.id === interactionState.value?.id);

    if (!targetElement) {
        return;
    }

    const rect = canvasBoxRef.value.getBoundingClientRect();
    const pointerX = (event.clientX - rect.left) / scale.value;
    const pointerY = (event.clientY - rect.top) / scale.value;

    if (interactionState.value.mode === 'move') {
        const nextX = Math.round(clamp(pointerX - interactionState.value.offsetX, 0, Math.max(0, props.width - targetElement.width)));
        const nextY = Math.round(clamp(pointerY - interactionState.value.offsetY, 0, Math.max(0, props.height - targetElement.height)));

        emit('move-element', { id: interactionState.value.id, x: nextX, y: nextY });

        return;
    }

    const nextWidth = Math.round(clamp(pointerX - targetElement.x, 48, props.width - targetElement.x));
    const nextHeight = Math.round(clamp(pointerY - targetElement.y, 32, props.height - targetElement.y));

    emit('resize-element', { id: interactionState.value.id, width: nextWidth, height: nextHeight });
}

function onDragEnd(): void {
    interactionState.value = null;
}

function onCanvasMouseDown(): void {
    selectedElementId.value = null;
}

function resolveText(element: FlyerElement): string {
    if (element.key && props.fieldValues?.[element.key]) {
        return props.fieldValues[element.key];
    }

    return element.content ?? element.placeholder ?? element.label;
}

function resolveImage(element: FlyerElement): string | null {
    if (!element.key) {
        return null;
    }

    return props.assetUrls?.[element.key] ?? null;
}

function fontWeight(value: FlyerElement['font_weight']): string {
    if (value === 'bold') {
        return '700';
    }

    if (value === 'semibold') {
        return '600';
    }

    if (value === 'medium') {
        return '500';
    }

    return '400';
}

function textX(element: FlyerElement): number {
    const width = element.width ?? 0;

    if (element.alignment === 'center') {
        return element.x + width / 2;
    }

    if (element.alignment === 'right') {
        return element.x + width;
    }

    return element.x;
}

function textAlign(value: FlyerElement['alignment']): CanvasTextAlign {
    if (value === 'center' || value === 'right') {
        return value;
    }

    return 'left';
}

function loadImage(url: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = url;
    });
}

async function draw(): Promise<void> {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    canvas.width = props.width;
    canvas.height = props.height;

    context.clearRect(0, 0, props.width, props.height);
    context.fillStyle = props.backgroundColor || CANVAS_STYLES.DEFAULT_BG;
    context.fillRect(0, 0, props.width, props.height);

    if (props.backgroundType === 'image' && props.backgroundImageUrl) {
        try {
            const background = await loadImage(props.backgroundImageUrl);
            context.drawImage(background, 0, 0, props.width, props.height);
        } catch {
            //
        }
    }

    for (const originalElement of props.elements) {
        const element = elementState(originalElement);

        if (element.type === 'image') {
            const source = resolveImage(element);

            if (source && element.width && element.height) {
                try {
                    const image = await loadImage(source);
                    context.drawImage(image, element.x, element.y, element.width, element.height);
                } catch {
                    context.fillStyle = CANVAS_STYLES.MISSING_IMAGE_BG;
                    context.fillRect(element.x, element.y, element.width, element.height);
                }
            } else if (element.width && element.height) {
                context.fillStyle = CANVAS_STYLES.PLACEHOLDER_BG;
                context.fillRect(element.x, element.y, element.width, element.height);
                context.strokeStyle = CANVAS_STYLES.PLACEHOLDER_STROKE;
                context.strokeRect(element.x, element.y, element.width, element.height);
                context.fillStyle = CANVAS_STYLES.PLACEHOLDER_TEXT;
                context.font = `500 24px ${CANVAS_STYLES.DEFAULT_FONT_FAMILY}`;
                context.textAlign = 'center';
                context.fillText(element.label, element.x + element.width / 2, element.y + element.height / 2);
            }

            continue;
        }

        context.fillStyle = element.color ?? CANVAS_STYLES.DEFAULT_TEXT_COLOR;
        context.font = `${fontWeight(element.font_weight)} ${element.font_size ?? 32}px ${CANVAS_STYLES.DEFAULT_FONT_FAMILY}`;
        context.textAlign = textAlign(element.alignment);
        context.textBaseline = 'top';

        const lines = resolveText(element).split('\n');
        const lineHeight = Math.round((element.font_size ?? 32) * 1.3);

        lines.forEach((line, index) => {
            context.fillText(line, textX(element), element.y + lineHeight * index, element.width ?? undefined);
        });
    }
}

async function exportImage(type: 'image/png' | 'image/jpeg'): Promise<string | null> {
    await nextTick();
    await draw();

    return canvasRef.value?.toDataURL(type) ?? null;
}

async function download(format: 'png' | 'jpg'): Promise<void> {
    const mimeType = format === 'png' ? 'image/png' : 'image/jpeg';
    const dataUrl = await exportImage(mimeType);

    if (!dataUrl) {
        return;
    }

    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = `flyer.${format}`;
    link.click();
}

async function printCanvas(): Promise<void> {
    const dataUrl = await exportImage('image/png');

    if (!dataUrl) {
        return;
    }

    const printWindow = window.open('', '_blank', 'noopener,noreferrer');

    if (!printWindow) {
        return;
    }

    printWindow.document.write(`
        <html>
            <head>
                <title>Print Flyer</title>
                <style>
                    @page { margin: 12mm; }
                    body { margin: 0; display: grid; place-items: center; background: white; }
                    img { width: 100%; max-width: 210mm; height: auto; }
                </style>
            </head>
            <body>
                <img src="${dataUrl}" alt="Flyer preview" />
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}

defineExpose({
    download,
    printCanvas,
    exportImage,
});

watch(
    () => JSON.stringify({
        width: props.width,
        height: props.height,
        backgroundType: props.backgroundType,
        backgroundColor: props.backgroundColor,
        backgroundImageUrl: props.backgroundImageUrl,
        elements: props.elements,
        fieldValues: props.fieldValues,
        elementOverrides: props.elementOverrides,
        assetUrls: props.assetUrls,
    }),
    async () => {
        await nextTick();
        await draw();
    },
    { immediate: true },
);

onMounted(async () => {
    await draw();

    window.addEventListener('pointermove', onDragMove);
    window.addEventListener('pointerup', onDragEnd);
});

onUnmounted(() => {
    window.removeEventListener('pointermove', onDragMove);
    window.removeEventListener('pointerup', onDragEnd);
});

watch(
    () => props.elements.map((element) => element.id),
    (ids) => {
        if (selectedElementId.value && !ids.includes(selectedElementId.value)) {
            selectedElementId.value = null;
        }
    },
);
</script>

<template>
    <div class="rounded-[28px] border border-border bg-white p-4 shadow-sm">
        <div class="overflow-auto rounded-[20px] bg-neutral-100 p-4 dark:bg-neutral-900">
            <div
                ref="canvasBoxRef"
                class="relative mx-auto"
                :style="{
                    width: `${scaledWidth}px`,
                    height: `${scaledHeight}px`,
                }"
            >
                <canvas
                    ref="canvasRef"
                    :style="{
                        width: `${scaledWidth}px`,
                        height: `${scaledHeight}px`,
                    }"
                    class="rounded-2xl bg-white shadow-lg"
                    @pointerdown="onCanvasMouseDown"
                />

                <div v-if="draggable" class="pointer-events-none absolute inset-0 rounded-2xl border border-dashed border-primary/40" />
                <div
                    v-for="element in draggableElements"
                    :key="element.id"
                    class="absolute z-10 rounded-md border bg-primary/10 text-[10px] font-medium backdrop-blur-[1px]"
                    :style="{
                        left: `${element.x * scale}px`,
                        top: `${element.y * scale}px`,
                        width: `${Math.max(element.width * scale, 24)}px`,
                        height: `${Math.max(element.height * scale, 24)}px`,
                        cursor: draggable ? 'grab' : 'default',
                    }"
                    :class="[
                        draggable ? 'pointer-events-auto' : 'pointer-events-none',
                        selectedElementId === element.id ? 'border-primary text-primary ring-2 ring-primary/30' : 'border-primary/70 text-primary/90',
                    ]"
                    @pointerdown.prevent="onDragStart(element.id, $event)"
                >
                    <div class="pointer-events-none absolute left-1 top-1 rounded bg-background/80 px-1 py-0.5 text-[9px] leading-none">
                        {{ element.label }}
                    </div>
                    <button
                        v-if="draggable"
                        type="button"
                        class="absolute -bottom-1 -right-1 h-3 w-3 rounded-sm border border-background bg-primary"
                        @pointerdown.prevent="onResizeStart(element.id, $event)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
