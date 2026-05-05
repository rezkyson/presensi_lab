<script setup>
import { computed } from 'vue';
import { AlertTriangle, CheckCircle2, Info, X, XCircle } from 'lucide-vue-next';
import { useToast } from '@/Composables/useToast';

const { toasts, remove } = useToast();

const icons = {
    success: CheckCircle2,
    error: XCircle,
    warning: AlertTriangle,
    info: Info,
};

const styles = {
    success: 'border-emerald-200 bg-emerald-50 text-emerald-950',
    error: 'border-rose-200 bg-rose-50 text-rose-950',
    warning: 'border-amber-200 bg-amber-50 text-amber-950',
    info: 'border-sky-200 bg-sky-50 text-sky-950',
};

const iconStyles = {
    success: 'text-emerald-700',
    error: 'text-rose-700',
    warning: 'text-amber-700',
    info: 'text-sky-700',
};

const visibleToasts = computed(() => toasts.value);
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed right-4 top-4 z-[70] flex w-[calc(100vw-2rem)] max-w-sm flex-col gap-3 sm:right-5 sm:top-5"
            aria-live="polite"
            aria-atomic="true"
        >
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-2 opacity-0"
                move-class="transition duration-200"
            >
                <div
                    v-for="toast in visibleToasts"
                    :key="toast.id"
                    class="pointer-events-auto flex gap-3 rounded-lg border p-4 shadow-lg shadow-zinc-950/10"
                    :class="styles[toast.variant] ?? styles.info"
                    role="status"
                >
                    <component
                        :is="icons[toast.variant] ?? icons.info"
                        class="mt-0.5 h-5 w-5 shrink-0"
                        :class="iconStyles[toast.variant] ?? iconStyles.info"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold">
                            {{ toast.title }}
                        </p>
                        <p class="mt-1 break-words text-sm opacity-80">
                            {{ toast.message }}
                        </p>
                    </div>
                    <button
                        class="shrink-0 rounded-md p-1 text-current opacity-60 transition hover:bg-white/60 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-current/25"
                        type="button"
                        aria-label="Tutup notifikasi"
                        @click="remove(toast.id)"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
