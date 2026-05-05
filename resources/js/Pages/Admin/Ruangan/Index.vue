<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, RotateCcw, Search, Trash2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

const props = defineProps({
    ruangan: {
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

    router.get('/admin/ruangan', filterForm, {
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

const destroyRuangan = async (ruangan) => {
    if (ruangan.jadwal_count > 0) {
        return;
    }

    const confirmed = await confirm({
        title: 'Hapus ruangan?',
        message: `Ruangan ${ruangan.nama} akan dihapus dari pilihan jadwal.`,
        confirmText: 'Hapus',
        variant: 'danger',
    });

    if (confirmed) {
        router.delete(`/admin/ruangan/${ruangan.id}`);
    }
};
</script>

<template>
    <Head title="Manajemen Ruangan" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Admin</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Manajemen ruangan</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Kelola daftar lab yang bisa dipilih saat membuat jadwal.
                    </p>
                </div>
                <Link
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800"
                    href="/admin/ruangan/create"
                >
                    <Plus class="h-4 w-4" />
                    Tambah
                </Link>
            </header>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <form class="grid gap-3 sm:grid-cols-[1fr_auto_auto]" @submit.prevent="applyFilters">
                    <div>
                        <label class="sr-only" for="search">Cari ruangan</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                            <input
                                id="search"
                                v-model="filterForm.search"
                                class="w-full rounded-md border border-zinc-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                                placeholder="Cari nama atau keterangan"
                                type="search"
                            >
                        </div>
                    </div>
                    <button
                        class="touch-target rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-wait disabled:opacity-70"
                        type="submit"
                        :disabled="filtering"
                    >
                        {{ filtering ? 'Memuat' : 'Filter' }}
                    </button>
                    <button
                        class="touch-target inline-flex items-center justify-center gap-2 rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100 disabled:cursor-wait disabled:opacity-70"
                        type="button"
                        :disabled="filtering"
                        @click="clearFilters"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Reset
                    </button>
                </form>
            </section>

            <section class="relative rounded-lg border border-zinc-200 bg-white shadow-sm" :aria-busy="filtering">
                <div v-if="filtering" class="absolute inset-0 z-10 flex items-start justify-center rounded-lg bg-white/70 pt-12 text-sm font-semibold text-zinc-700 backdrop-blur-[1px]">
                    Memuat data...
                </div>
                <div v-if="ruangan.data.length" class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Ruangan</th>
                                <th class="px-5 py-3 font-semibold">Keterangan</th>
                                <th class="px-5 py-3 font-semibold">Jadwal</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="item in ruangan.data" :key="item.id" class="align-top">
                                <td class="px-5 py-4 font-semibold text-zinc-950">{{ item.nama }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.keterangan || '-' }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.jadwal_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            class="rounded-md border border-zinc-300 p-2 text-zinc-700 transition hover:bg-zinc-100"
                                            :href="`/admin/ruangan/${item.id}/edit`"
                                            title="Edit"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button
                                            class="rounded-md border p-2 transition disabled:cursor-not-allowed disabled:border-zinc-200 disabled:text-zinc-400 enabled:border-red-200 enabled:text-red-700 enabled:hover:bg-red-50"
                                            :title="item.jadwal_count > 0 ? 'Tidak bisa dihapus karena masih digunakan jadwal' : 'Hapus'"
                                            type="button"
                                            :disabled="item.jadwal_count > 0"
                                            @click="destroyRuangan(item)"
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
                    <p class="font-semibold text-zinc-800">Data ruangan tidak ditemukan.</p>
                    <p class="mt-1 text-sm text-zinc-500">Ubah filter atau tambahkan ruangan baru.</p>
                </div>

                <div class="flex flex-col gap-3 border-t border-zinc-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-zinc-500">
                        Menampilkan {{ ruangan.from ?? 0 }}-{{ ruangan.to ?? 0 }} dari {{ ruangan.total }} data
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in ruangan.links"
                            :key="link.label"
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="[
                                link.active ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-zinc-300 text-zinc-700',
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
