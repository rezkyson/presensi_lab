<script setup>
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import GlobalConfirmModal from '@/Components/GlobalConfirmModal.vue';
import GlobalToast from '@/Components/GlobalToast.vue';
import { useToast } from '@/Composables/useToast';

const page = usePage();
const toast = useToast();

watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    ([successMessage, errorMessage]) => {
        if (successMessage) {
            toast.success(successMessage);
        }

        if (errorMessage) {
            toast.error(errorMessage);
        }
    },
    { immediate: true },
);
</script>

<template>
    <GlobalToast />
    <GlobalConfirmModal />
</template>
