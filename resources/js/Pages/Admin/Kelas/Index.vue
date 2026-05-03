<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

const props = defineProps({
    kelas: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterOptions: {
        type: Object,
        default: () => ({ prodi: [], semester: [], tahun_akademik: [] }),
    },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
    prodi: props.filters.prodi ?? '',
    semester: props.filters.semester ?? '',
    tahun_akademik: props.filters.tahun_akademik ?? '',
});
const filtering = ref(false);

const applyFilters = () => {
    filtering.value = true;

    router.get('/admin/kelas', filterForm, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            filtering.value = false;
        },
    });
};

const clearFilters = () => {
    filterForm.search = '';
    filterForm.prodi = '';
    filterForm.semester = '';
    filterForm.tahun_akademik = '';
    applyFilters();
};

const destroyKelas = (kelas) => {
    if (confirm(`Hapus kelas ${kelas.nama_kelas}?`)) {
        router.delete(`/admin/kelas/${kelas.id}`);
    }
};
</script>

<template>
    <Head title="Manajemen Kelas" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Admin</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Manajemen kelas</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Kelola kelas, peserta mahasiswa, dan dosen pengampu.
                    </p>
                </div>
                <Link
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800"
                    href="/admin/kelas/create"
                >
                    <Plus class="h-4 w-4" />
                    Tambah
                </Link>
            </header>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <form class="grid gap-3 lg:grid-cols-[1.4fr_1fr_0.7fr_1fr_auto_auto]" @submit.prevent="applyFilters">
                    <div>
                        <label class="sr-only" for="search">Cari kelas</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                            <input
                                id="search"
                                v-model="filterForm.search"
                                class="w-full rounded-md border border-zinc-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                                placeholder="Cari nama kelas, prodi, tahun"
                                type="search"
                            >
                        </div>
                    </div>

                    <select v-model="filterForm.prodi" aria-label="Filter prodi" class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                        <option value="">Semua prodi</option>
                        <option v-for="prodi in filterOptions.prodi" :key="prodi" :value="prodi">
                            {{ prodi }}
                        </option>
                    </select>

                    <select v-model="filterForm.semester" aria-label="Filter semester" class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                        <option value="">Semester</option>
                        <option v-for="semester in filterOptions.semester" :key="semester" :value="semester">
                            {{ semester }}
                        </option>
                    </select>

                    <select v-model="filterForm.tahun_akademik" aria-label="Filter tahun akademik" class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                        <option value="">Tahun akademik</option>
                        <option v-for="tahun in filterOptions.tahun_akademik" :key="tahun" :value="tahun">
                            {{ tahun }}
                        </option>
                    </select>

                    <button class="touch-target rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-wait disabled:opacity-70" type="submit" :disabled="filtering">
                        {{ filtering ? 'Memuat' : 'Filter' }}
                    </button>
                    <button class="touch-target rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100 disabled:cursor-wait disabled:opacity-70" type="button" :disabled="filtering" @click="clearFilters">
                        Reset
                    </button>
                </form>
            </section>

            <section class="relative rounded-lg border border-zinc-200 bg-white shadow-sm" :aria-busy="filtering">
                <div v-if="filtering" class="absolute inset-0 z-10 flex items-start justify-center rounded-lg bg-white/70 pt-12 text-sm font-semibold text-zinc-700 backdrop-blur-[1px]">
                    Memuat data...
                </div>
                <div v-if="kelas.data.length" class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Kelas</th>
                                <th class="px-5 py-3 font-semibold">Prodi</th>
                                <th class="px-5 py-3 font-semibold">Semester</th>
                                <th class="px-5 py-3 font-semibold">Tahun</th>
                                <th class="px-5 py-3 font-semibold">Isi</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="item in kelas.data" :key="item.id">
                                <td class="px-5 py-4 font-semibold text-zinc-950">{{ item.nama_kelas }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.prodi }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.semester }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.tahun_akademik }}</td>
                                <td class="px-5 py-4 text-zinc-600">
                                    {{ item.mahasiswa_count ?? 0 }} mhs · {{ item.dosen_count ?? 0 }} dosen · {{ item.jadwal_count ?? 0 }} jadwal
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Link class="rounded-md border border-zinc-300 p-2 text-zinc-700 transition hover:bg-zinc-100" :href="`/admin/kelas/${item.id}`" title="Detail">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                        <Link class="rounded-md border border-zinc-300 p-2 text-zinc-700 transition hover:bg-zinc-100" :href="`/admin/kelas/${item.id}/edit`" title="Edit">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button class="rounded-md border border-red-200 p-2 text-red-700 transition hover:bg-red-50" title="Hapus" type="button" @click="destroyKelas(item)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-10 text-center">
                    <p class="font-semibold text-zinc-800">Data kelas tidak ditemukan.</p>
                    <p class="mt-1 text-sm text-zinc-500">Ubah filter atau tambahkan kelas baru.</p>
                </div>

                <div class="flex flex-col gap-3 border-t border-zinc-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-zinc-500">
                        Menampilkan {{ kelas.from ?? 0 }}-{{ kelas.to ?? 0 }} dari {{ kelas.total }} data
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in kelas.links"
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
