import { ref } from 'vue';

const toasts = ref([]);
let nextToastId = 1;

const defaultTitles = {
    success: 'Berhasil',
    error: 'Terjadi masalah',
    warning: 'Perlu perhatian',
    info: 'Informasi',
};

const normalizeToast = (messageOrOptions, variant = 'info') => {
    if (typeof messageOrOptions === 'string') {
        return {
            message: messageOrOptions,
            title: defaultTitles[variant] ?? defaultTitles.info,
            variant,
            duration: 4500,
        };
    }

    return {
        title: messageOrOptions.title ?? defaultTitles[messageOrOptions.variant ?? variant] ?? defaultTitles.info,
        message: messageOrOptions.message ?? '',
        variant: messageOrOptions.variant ?? variant,
        duration: messageOrOptions.duration ?? 4500,
    };
};

export const useToast = () => {
    const remove = (id) => {
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    };

    const push = (messageOrOptions, variant = 'info') => {
        const toast = {
            id: nextToastId++,
            ...normalizeToast(messageOrOptions, variant),
        };

        if (!toast.message) {
            return null;
        }

        toasts.value = [toast, ...toasts.value].slice(0, 5);

        if (toast.duration > 0) {
            window.setTimeout(() => remove(toast.id), toast.duration);
        }

        return toast.id;
    };

    return {
        toasts,
        push,
        remove,
        success: (messageOrOptions) => push(messageOrOptions, 'success'),
        error: (messageOrOptions) => push(messageOrOptions, 'error'),
        warning: (messageOrOptions) => push(messageOrOptions, 'warning'),
        info: (messageOrOptions) => push(messageOrOptions, 'info'),
    };
};
