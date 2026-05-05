<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { AlertTriangle, CheckCircle2, Info, Trash2, X } from 'lucide-vue-next';
import { useConfirm } from '@/Composables/useConfirm';

const { confirmState, accept, cancel } = useConfirm();
const confirmButton = ref(null);

const icons = {
    danger: Trash2,
    primary: CheckCircle2,
    neutral: Info,
};

const iconClasses = {
    danger: 'bg-rose-100 text-rose-700',
    primary: 'bg-emerald-100 text-emerald-700',
    neutral: 'bg-zinc-100 text-zinc-700',
};

const confirmButtonClasses = {
    danger: 'bg-rose-700 text-white hover:bg-rose-800 focus:ring-rose-700/30',
    primary: 'bg-emerald-700 text-white hover:bg-emerald-800 focus:ring-emerald-700/30',
    neutral: 'bg-zinc-900 text-white hover:bg-zinc-800 focus:ring-zinc-900/30',
};

const modalIcon = computed(() => icons[confirmState.variant] ?? AlertTriangle);

const handleKeydown = (event) => {
    if (event.key === 'Escape' && confirmState.open) {
        cancel();
    }
};

watch(
    () => confirmState.open,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';

        if (open) {
            nextTick(() => confirmButton.value?.focus());
        }
    },
);

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-120 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="confirmState.open"
                class="fixed inset-0 z-[80] flex items-center justify-center bg-zinc-950/45 px-4 py-6 backdrop-blur-sm"
                @click.self="cancel"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="translate-y-2 scale-95 opacity-0"
                    enter-to-class="translate-y-0 scale-100 opacity-100"
                    leave-active-class="transition duration-120 ease-in"
                    leave-from-class="translate-y-0 scale-100 opacity-100"
                    leave-to-class="translate-y-2 scale-95 opacity-0"
                >
                    <section
                        class="w-full max-w-md rounded-lg border border-zinc-200 bg-white p-5 shadow-2xl shadow-zinc-950/20"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="global-confirm-title"
                        aria-describedby="global-confirm-message"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                :class="iconClasses[confirmState.variant] ?? iconClasses.neutral"
                            >
                                <component :is="modalIcon" class="h-5 w-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <h2 id="global-confirm-title" class="text-base font-semibold text-zinc-950">
                                        {{ confirmState.title }}
                                    </h2>
                                    <button
                                        class="rounded-md p-1 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-400/40"
                                        type="button"
                                        aria-label="Tutup modal"
                                        @click="cancel"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                                <p id="global-confirm-message" class="mt-2 text-sm leading-6 text-zinc-600">
                                    {{ confirmState.message }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <button
                                class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-400/30"
                                type="button"
                                @click="cancel"
                            >
                                {{ confirmState.cancelText }}
                            </button>
                            <button
                                ref="confirmButton"
                                class="rounded-md px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2"
                                :class="confirmButtonClasses[confirmState.variant] ?? confirmButtonClasses.neutral"
                                type="button"
                                @click="accept"
                            >
                                {{ confirmState.confirmText }}
                            </button>
                        </div>
                    </section>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
