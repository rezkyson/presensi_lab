<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, RotateCcw, Search, Trash2, UserCheck, UserX } from 'lucide-vue-next';
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

const destroyDosen = (dosen) => {
    if (confirm(`Hapus dosen ${dosen.name}?`)) {
        router.delete(`/admin/dosen/${dosen.id}`);
    }
};

const toggleActive = (dosen) => {
    router.patch(`/admin/dosen/${dosen.id}/toggle-active`, {}, { preserveScroll: true });
};

const resetPassword = (dosen) => {
    if (confirm(`Reset password ${dosen.name} ke password default?`)) {
        router.post(`/admin/dosen/${dosen.id}/reset-password`, {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Manajemen Dosen" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Admin</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Manajemen dosen</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Kelola akun dosen, NIP, bidang studi, dan status akses.
                    </p>
                </div>
                <Link
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800"
                    href="/admin/dosen/create"
                >
                    <Plus class="h-4 w-4" />
                    Tambah
                </Link>
            </header>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <form class="grid gap-3 sm:grid-cols-[1fr_auto_auto]" @submit.prevent="applyFilters">
                    <div>
                        <label class="sr-only" for="search">Cari dosen</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                            <input
                                id="search"
                                v-model="filterForm.search"
                                class="w-full rounded-md border border-zinc-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                                placeholder="Cari nama, email, NIP, bidang studi"
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
                        class="touch-target rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100 disabled:cursor-wait disabled:opacity-70"
                        type="button"
                        :disabled="filtering"
                        @click="clearFilters"
                    >
                        Reset
                    </button>
                </form>
            </section>

            <section class="relative rounded-lg border border-zinc-200 bg-white shadow-sm" :aria-busy="filtering">
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
                                <th class="px-5 py-3 font-semibold">Status</th>
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
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="item.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700'"
                                    >
                                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            class="rounded-md border border-zinc-300 p-2 text-zinc-700 transition hover:bg-zinc-100"
                                            :href="`/admin/dosen/${item.id}/edit`"
                                            title="Edit"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button
                                            class="rounded-md border border-zinc-300 p-2 text-zinc-700 transition hover:bg-zinc-100"
                                            type="button"
                                            :title="item.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                            @click="toggleActive(item)"
                                        >
                                            <UserX v-if="item.is_active" class="h-4 w-4" />
                                            <UserCheck v-else class="h-4 w-4" />
                                        </button>
                                        <button
                                            class="rounded-md border border-zinc-300 p-2 text-zinc-700 transition hover:bg-zinc-100"
                                            title="Reset password"
                                            type="button"
                                            @click="resetPassword(item)"
                                        >
                                            <RotateCcw class="h-4 w-4" />
                                        </button>
                                        <button
                                            class="rounded-md border border-red-200 p-2 text-red-700 transition hover:bg-red-50"
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

                <div class="flex flex-col gap-3 border-t border-zinc-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-zinc-500">
                        Menampilkan {{ dosen.from ?? 0 }}-{{ dosen.to ?? 0 }} dari {{ dosen.total }} data
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in dosen.links"
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
