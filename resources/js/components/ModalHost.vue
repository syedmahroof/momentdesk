<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { useModals } from '@/composables/useModals';

const m = useModals();
const inputRef = ref<HTMLInputElement | null>(null);

watch(
    () => m.state.open,
    (open) => {
        if (open && m.state.type === 'prompt') {
            nextTick(() => { inputRef.value?.focus(); inputRef.value?.select(); });
        }
    },
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div v-if="m.state.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-[1px]" @click="m.state.type !== 'alert' && m._cancel()"></div>

                <div class="relative w-full max-w-sm rounded-lg border border-border bg-card p-5 shadow-xl" role="dialog" aria-modal="true" @keydown.esc="m.state.type !== 'alert' && m._cancel()">
                    <h3 v-if="m.state.title" class="text-base font-semibold text-foreground">{{ m.state.title }}</h3>
                    <p v-if="m.state.message && m.state.message !== m.state.title" class="mt-1 text-sm text-muted-foreground">{{ m.state.message }}</p>

                    <input
                        v-if="m.state.type === 'prompt'"
                        ref="inputRef"
                        v-model="m.state.value"
                        :placeholder="m.state.placeholder"
                        type="text"
                        class="mt-3 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/25"
                        @keydown.enter="m._confirm()"
                    />

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            v-if="m.state.type !== 'alert'"
                            type="button"
                            class="rounded-md border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                            @click="m._cancel()"
                        >
                            {{ m.state.cancelText }}
                        </button>
                        <button
                            type="button"
                            :class="['rounded-md px-4 py-2 text-sm font-medium text-white shadow-xs transition', m.state.danger ? 'bg-destructive hover:bg-destructive/90' : 'bg-primary text-primary-foreground hover:bg-primary/90']"
                            @click="m._confirm()"
                        >
                            {{ m.state.confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
