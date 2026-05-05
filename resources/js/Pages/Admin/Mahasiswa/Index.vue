<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, RotateCcw, Search, Trash2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

const props = defineProps({
    mahasiswa: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    kelasOptions: {
        type: Array,
        default: () => [],
    },
    angkatanOptions: {
        type: Array,
        default: () => [],
    },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
    kelas_id: props.filters.kelas_id ?? '',
    angkatan: props.filters.angkatan ?? '',
    wajah: props.filters.wajah ?? '',
});
const filtering = ref(false);
const { confirm } = useConfirm();

const applyFilters = () => {
    filtering.value = true;

    router.get('/admin/mahasiswa', filterForm, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            filtering.value = false;
        },
    });
};

const clearFilters = () => {
    filterForm.search = '';
    filterForm.kelas_id = '';
    filterForm.angkatan = '';
    filterForm.wajah = '';
    applyFilters();
};

const destroyMahasiswa = async (mahasiswa) => {
    const confirmed = await confirm({
        title: 'Hapus mahasiswa?',
        message: `Data ${mahasiswa.name} akan dihapus dari sistem.`,
        confirmText: 'Hapus',
        variant: 'danger',
    });

    if (confirmed) {
        router.delete(`/admin/mahasiswa/${mahasiswa.id}`);
    }
};

const resetPassword = async (mahasiswa) => {
    const confirmed = await confirm({
        title: 'Reset password?',
        message: `Password ${mahasiswa.name} akan dikembalikan ke password default.`,
        confirmText: 'Reset password',
        variant: 'primary',
    });

    if (confirmed) {
        router.post(`/admin/mahasiswa/${mahasiswa.id}/reset-password`, {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Manajemen Mahasiswa" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Admin</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Manajemen mahasiswa</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Kelola akun, data akademik, kelas, dan status registrasi wajah mahasiswa.
                    </p>
                </div>
                <Link
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800"
                    href="/admin/mahasiswa/create"
                >
                    <Plus class="h-4 w-4" />
                    Tambah
                </Link>
            </header>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <form class="grid gap-3 lg:grid-cols-[1.4fr_1fr_0.8fr_0.8fr_auto_auto]" @submit.prevent="applyFilters">
                    <div>
                        <label class="sr-only" for="search">Cari mahasiswa</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                            <input
                                id="search"
                                v-model="filterForm.search"
                                class="w-full rounded-md border border-zinc-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                                placeholder="Cari nama, email, NIM, prodi"
                                type="search"
                            >
                        </div>
                    </div>

                    <select
                        v-model="filterForm.kelas_id"
                        aria-label="Filter kelas"
                        class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                    >
                        <option value="">Semua kelas</option>
                        <option v-for="kelas in kelasOptions" :key="kelas.id" :value="kelas.id">
                            {{ kelas.label }}
                        </option>
                    </select>

                    <select
                        v-model="filterForm.angkatan"
                        aria-label="Filter angkatan"
                        class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                    >
                        <option value="">Angkatan</option>
                        <option v-for="angkatan in angkatanOptions" :key="angkatan" :value="angkatan">
                            {{ angkatan }}
                        </option>
                    </select>

                    <select
                        v-model="filterForm.wajah"
                        aria-label="Filter status wajah"
                        class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                    >
                        <option value="">Status wajah</option>
                        <option value="terdaftar">Terdaftar</option>
                        <option value="belum">Belum</option>
                    </select>

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
                <div v-if="mahasiswa.data.length" class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Mahasiswa</th>
                                <th class="px-5 py-3 font-semibold">Akademik</th>
                                <th class="px-5 py-3 font-semibold">Kelas</th>
                                <th class="px-5 py-3 font-semibold">Wajah</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="item in mahasiswa.data" :key="item.id" class="align-top">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-zinc-950">{{ item.name }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ item.email }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">NIM {{ item.nim }}</p>
                                </td>
                                <td class="px-5 py-4 text-zinc-600">
                                    <p>{{ item.prodi }}</p>
                                    <p class="mt-1 text-xs">Angkatan {{ item.angkatan }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div v-if="item.kelas.length" class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="kelas in item.kelas"
                                            :key="kelas.id"
                                            class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700"
                                        >
                                            {{ kelas.nama_kelas }}
                                        </span>
                                    </div>
                                    <span v-else class="text-sm text-zinc-400">Belum ada kelas</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="item.wajah_terdaftar ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800'"
                                    >
                                        {{ item.wajah_terdaftar ? 'Terdaftar' : 'Belum' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            class="rounded-md border border-zinc-300 p-2 text-zinc-700 transition hover:bg-zinc-100"
                                            :href="`/admin/mahasiswa/${item.id}/edit`"
                                            title="Edit"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Link>
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
                                            @click="destroyMahasiswa(item)"
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
                    <p class="font-semibold text-zinc-800">Data mahasiswa tidak ditemukan.</p>
                    <p class="mt-1 text-sm text-zinc-500">Ubah filter atau tambahkan mahasiswa baru.</p>
                </div>

                <div class="flex flex-col gap-3 border-t border-zinc-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-zinc-500">
                        Menampilkan {{ mahasiswa.from ?? 0 }}-{{ mahasiswa.to ?? 0 }} dari {{ mahasiswa.total }} data
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in mahasiswa.links"
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
