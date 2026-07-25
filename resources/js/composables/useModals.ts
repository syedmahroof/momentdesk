import { reactive } from 'vue';

type ModalType = 'confirm' | 'prompt' | 'alert';

interface ModalState {
    open: boolean;
    type: ModalType;
    title: string;
    message: string;
    value: string;
    placeholder: string;
    confirmText: string;
    cancelText: string;
    danger: boolean;
    resolve: ((value: unknown) => void) | null;
}

const state = reactive<ModalState>({
    open: false,
    type: 'alert',
    title: '',
    message: '',
    value: '',
    placeholder: '',
    confirmText: 'OK',
    cancelText: 'Cancel',
    danger: false,
    resolve: null,
});

function settle(result: unknown) {
    const resolve = state.resolve;
    state.open = false;
    state.resolve = null;
    if (resolve) resolve(result);
}

/**
 * Promise-based replacements for window.confirm / prompt / alert,
 * rendered by a single <ModalHost /> on the page.
 */
export function useModals() {
    return {
        state,
        confirm(message: string, opts: { title?: string; confirmText?: string; danger?: boolean } = {}): Promise<boolean> {
            return new Promise((resolve) => {
                Object.assign(state, {
                    open: true, type: 'confirm', message,
                    title: opts.title ?? 'Please confirm',
                    confirmText: opts.confirmText ?? 'Confirm',
                    cancelText: 'Cancel',
                    danger: opts.danger ?? false,
                    resolve: resolve as (v: unknown) => void,
                });
            });
        },
        prompt(message: string, opts: { title?: string; default?: string; placeholder?: string; confirmText?: string } = {}): Promise<string | null> {
            return new Promise((resolve) => {
                Object.assign(state, {
                    open: true, type: 'prompt', message,
                    title: opts.title ?? message,
                    value: opts.default ?? '',
                    placeholder: opts.placeholder ?? '',
                    confirmText: opts.confirmText ?? 'Save',
                    cancelText: 'Cancel',
                    danger: false,
                    resolve: resolve as (v: unknown) => void,
                });
            });
        },
        alert(message: string, opts: { title?: string } = {}): Promise<void> {
            return new Promise((resolve) => {
                Object.assign(state, {
                    open: true, type: 'alert', message,
                    title: opts.title ?? '',
                    confirmText: 'OK',
                    danger: false,
                    resolve: resolve as (v: unknown) => void,
                });
            });
        },
        _confirm() { settle(state.type === 'prompt' ? state.value : true); },
        _cancel() { settle(state.type === 'prompt' ? null : false); },
    };
}
