import { reactive } from 'vue';

const defaultConfirm = {
    open: false,
    title: 'Konfirmasi aksi',
    message: 'Lanjutkan aksi ini?',
    confirmText: 'Lanjutkan',
    cancelText: 'Batal',
    variant: 'danger',
    resolver: null,
};

const confirmState = reactive({ ...defaultConfirm });

const resetConfirm = () => {
    Object.assign(confirmState, {
        ...defaultConfirm,
        resolver: null,
    });
};

const settle = (value) => {
    const resolver = confirmState.resolver;

    resetConfirm();

    if (resolver) {
        resolver(value);
    }
};

const normalizeOptions = (options) => {
    if (typeof options === 'string') {
        return { message: options };
    }

    return options ?? {};
};

export const useConfirm = () => {
    const confirm = (options) => new Promise((resolve) => {
        if (confirmState.resolver) {
            confirmState.resolver(false);
        }

        Object.assign(confirmState, {
            ...defaultConfirm,
            ...normalizeOptions(options),
            open: true,
            resolver: resolve,
        });
    });

    return {
        confirmState,
        confirm,
        accept: () => settle(true),
        cancel: () => settle(false),
    };
};
