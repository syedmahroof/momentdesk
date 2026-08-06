// Renders saved poster templates to small preview images so every place that browses
// designs can show what they look like instead of just listing names.
//
// Documents are fetched once and cached per composable instance; each render reuses a
// single offscreen canvas rather than holding a full 1080x1920 buffer per template.

import axios from 'axios';
import { reactive, ref } from 'vue';
import { ensureFonts, loadDocumentAssets, renderDocument, TENANT_LOGO_IMAGE_ID, type PosterDocument } from './renderer';

/** Longest edge of a generated preview, in pixels — enough for a crisp small tile. */
const THUMBNAIL_WIDTH = 240;

interface Options {
    /** Where template documents are read from — the tenant or admin endpoint. */
    apiBase?: string;
    /** Injected into the reserved logo slot so previews show the real brand mark. */
    logoUrl?: string | null;
}

export function useTemplateThumbnails(options: Options = {}) {
    const apiBase = options.apiBase ?? '/gold-poster/templates';
    const thumbnails = reactive<Record<number, string>>({});
    const loading = ref(false);
    const documents: Record<number, PosterDocument> = {};

    async function fetchDocument(id: number): Promise<PosterDocument> {
        if (documents[id]) return documents[id];

        const { data } = await axios.get(`${apiBase}/${id}`);
        const document = data.document as PosterDocument;
        if (options.logoUrl) document.images = { ...(document.images ?? {}), [TENANT_LOGO_IMAGE_ID]: options.logoUrl };
        documents[id] = document;

        return document;
    }

    /** Renders previews for the given template ids, skipping any already rendered. */
    async function render(ids: number[]): Promise<void> {
        const pending = ids.filter((id) => !thumbnails[id]);
        if (!pending.length) return;

        loading.value = true;
        await ensureFonts();
        const canvas = document.createElement('canvas');
        // Previews are downscaled before export: a full 1080x1920 data URL is ~200 KB and
        // nothing on screen shows them larger than a couple of hundred pixels wide.
        const thumb = document.createElement('canvas');
        const thumbCtx = thumb.getContext('2d');

        for (const id of pending) {
            try {
                const doc = await fetchDocument(id);
                renderDocument(canvas, doc, await loadDocumentAssets(doc), { ...(doc.fields ?? {}) });

                if (!thumbCtx) continue;
                const scale = Math.min(1, THUMBNAIL_WIDTH / canvas.width);
                thumb.width = Math.round(canvas.width * scale);
                thumb.height = Math.round(canvas.height * scale);
                thumbCtx.imageSmoothingQuality = 'high';
                thumbCtx.drawImage(canvas, 0, 0, thumb.width, thumb.height);
                thumbnails[id] = thumb.toDataURL('image/jpeg', 0.72);
            } catch {
                // A template that won't render just keeps its name-only card.
            }
        }

        loading.value = false;
    }

    function forget(id: number): void {
        delete thumbnails[id];
        delete documents[id];
    }

    return { thumbnails, loading, render, forget, fetchDocument };
}
