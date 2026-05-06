<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';
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
const { confirm } = useConfirm();

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

const destroyKelas = async (kelas) => {
    const confirmed = await confirm({
        title: 'Hapus kelas?',
        message: `Data kelas ${kelas.nama_kelas} akan dihapus.`,
        confirmText: 'Hapus',
        variant: 'danger',
    });

    if (confirmed) {
        router.delete(`/admin/kelas/${kelas.id}`);
    }
};
</script>

<template>
    <Head title="Manajemen Kelas" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="content-hero flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">Admin</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Manajemen kelas</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Kelola kelas, peserta mahasiswa, dan dosen pengampu.
                    </p>
                </div>
                <Link
                    class="btn-primary"
                    href="/admin/kelas/create"
                >
                    <Plus class="h-4 w-4" />
                    Tambah Kelas
                </Link>
            </header>

            <section class="ios-filter-bar">
                <form class="grid gap-3 lg:grid-cols-[1.4fr_1fr_0.7fr_1fr_auto_auto]" @submit.prevent="applyFilters">
                    <div>
                        <label class="sr-only" for="search">Cari kelas</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                            <input
                                id="search"
                                v-model="filterForm.search"
                                class="form-input py-2 pl-9 pr-3"
                                placeholder="Cari nama kelas, prodi, tahun"
                                type="search"
                            >
                        </div>
                    </div>

                    <select v-model="filterForm.prodi" aria-label="Filter prodi" class="form-input">
                        <option value="">Semua prodi</option>
                        <option v-for="prodi in filterOptions.prodi" :key="prodi" :value="prodi">
                            {{ prodi }}
                        </option>
                    </select>

                    <select v-model="filterForm.semester" aria-label="Filter semester" class="form-input">
                        <option value="">Semester</option>
                        <option v-for="semester in filterOptions.semester" :key="semester" :value="semester">
                            {{ semester }}
                        </option>
                    </select>

                    <select v-model="filterForm.tahun_akademik" aria-label="Filter tahun akademik" class="form-input">
                        <option value="">Tahun akademik</option>
                        <option v-for="tahun in filterOptions.tahun_akademik" :key="tahun" :value="tahun">
                            {{ tahun }}
                        </option>
                    </select>

                    <button class="btn-dark py-2" type="submit" :disabled="filtering">
                        {{ filtering ? 'Memuat' : 'Filter' }}
                    </button>
                    <button class="btn-secondary py-2 disabled:cursor-wait disabled:opacity-70" type="button" :disabled="filtering" @click="clearFilters">
                        Reset
                    </button>
                </form>
            </section>

            <section class="relative table-shell" :aria-busy="filtering">
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
                                    {{ item.mahasiswa_count ?? 0 }} mhs &middot; {{ item.dosen_count ?? 0 }} dosen &middot; {{ item.jadwal_count ?? 0 }} jadwal
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Link class="ios-action" :href="`/admin/kelas/${item.id}`" title="Detail">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                        <Link class="ios-action" :href="`/admin/kelas/${item.id}/edit`" title="Edit">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button class="ios-danger-action" title="Hapus" type="button" @click="destroyKelas(item)">
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

                <div class="ios-pagination">
                    <p class="text-sm text-zinc-500">
                        Menampilkan {{ kelas.from ?? 0 }}-{{ kelas.to ?? 0 }} dari {{ kelas.total }} data
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in kelas.links"
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
