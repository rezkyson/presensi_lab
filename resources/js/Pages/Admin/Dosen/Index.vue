<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, RotateCcw, Search, Trash2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

const props = defineProps({
    dosen: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
});
const filtering = ref(false);
const { confirm } = useConfirm();

const applyFilters = () => {
    filtering.value = true;

    router.get('/admin/dosen', filterForm, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            filtering.value = false;
        },
    });
};

const clearFilters = () => {
    filterForm.search = '';
    applyFilters();
};

const destroyDosen = async (dosen) => {
    const confirmed = await confirm({
        title: 'Hapus dosen?',
        message: `Data ${dosen.name} akan dihapus dari sistem.`,
        confirmText: 'Hapus',
        variant: 'danger',
    });

    if (confirmed) {
        router.delete(`/admin/dosen/${dosen.id}`);
    }
};

const resetPassword = async (dosen) => {
    const confirmed = await confirm({
        title: 'Reset password?',
        message: `Password ${dosen.name} akan dikembalikan ke password default.`,
        confirmText: 'Reset password',
        variant: 'primary',
    });

    if (confirmed) {
        router.post(`/admin/dosen/${dosen.id}/reset-password`, {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Manajemen Dosen" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="content-hero flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">Admin</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Manajemen dosen</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Kelola akun dosen, NIP, dan bidang studi.
                    </p>
                </div>
                <Link
                    class="btn-primary"
                    href="/admin/dosen/create"
                >
                    <Plus class="h-4 w-4" />
                    Tambah
                </Link>
            </header>

            <section class="ios-filter-bar">
                <form class="grid gap-3 sm:grid-cols-[1fr_auto_auto]" @submit.prevent="applyFilters">
                    <div>
                        <label class="sr-only" for="search">Cari dosen</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                            <input
                                id="search"
                                v-model="filterForm.search"
                                class="form-input py-2 pl-9 pr-3"
                                placeholder="Cari nama, email, NIP, bidang studi"
                                type="search"
                            >
                        </div>
                    </div>
                    <button
                        class="btn-dark py-2"
                        type="submit"
                        :disabled="filtering"
                    >
                        {{ filtering ? 'Memuat' : 'Filter' }}
                    </button>
                    <button
                        class="btn-secondary py-2 disabled:cursor-wait disabled:opacity-70"
                        type="button"
                        :disabled="filtering"
                        @click="clearFilters"
                    >
                        Reset
                    </button>
                </form>
            </section>

            <section class="relative table-shell" :aria-busy="filtering">
                <div v-if="filtering" class="absolute inset-0 z-10 flex items-start justify-center rounded-lg bg-white/70 pt-12 text-sm font-semibold text-zinc-700 backdrop-blur-[1px]">
                    Memuat data...
                </div>
                <div v-if="dosen.data.length" class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Dosen</th>
                                <th class="px-5 py-3 font-semibold">NIP</th>
                                <th class="px-5 py-3 font-semibold">Bidang studi</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="item in dosen.data" :key="item.id" class="align-top">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-zinc-950">{{ item.name }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ item.email }}</p>
                                </td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.nip }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.bidang_studi || '-' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            class="ios-action"
                                            :href="`/admin/dosen/${item.id}/edit`"
                                            title="Edit"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button
                                            class="ios-action"
                                            title="Reset password"
                                            type="button"
                                            @click="resetPassword(item)"
                                        >
                                            <RotateCcw class="h-4 w-4" />
                                        </button>
                                        <button
                                            class="ios-danger-action"
                                            title="Hapus"
                                            type="button"
                                            @click="destroyDosen(item)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-10 text-center">
                    <p class="font-semibold text-zinc-800">Data dosen tidak ditemukan.</p>
                    <p class="mt-1 text-sm text-zinc-500">Ubah filter atau tambahkan dosen baru.</p>
                </div>

                <div class="ios-pagination">
                    <p class="text-sm text-zinc-500">
                        Menampilkan {{ dosen.from ?? 0 }}-{{ dosen.to ?? 0 }} dari {{ dosen.total }} data
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in dosen.links"
                            :key="link.label"
                            class="rounded-md px-3 py-1.5 text-sm font-semibold transition"
                            :class="[
                                link.active ? 'border-apple-blue bg-apple-blue text-white' : 'border-zinc-300 text-zinc-700',
                                !link.url ? 'pointer-events-none opacity-40' : 'hover:bg-zinc-100',
                            ]"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
