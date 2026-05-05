<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

const props = defineProps({
    jadwal: {
        type: Object,
        required: true,
    },
    weeklySchedules: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    options: {
        type: Object,
        required: true,
    },
});

const filterForm = reactive({
    kelas_id: props.filters.kelas_id ?? '',
    dosen_id: props.filters.dosen_id ?? '',
    hari: props.filters.hari ?? '',
    tahun_akademik: props.filters.tahun_akademik ?? '',
});
const filtering = ref(false);
const { confirm } = useConfirm();

const applyFilters = () => {
    filtering.value = true;

    router.get('/admin/jadwal', filterForm, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            filtering.value = false;
        },
    });
};

const clearFilters = () => {
    filterForm.kelas_id = '';
    filterForm.dosen_id = '';
    filterForm.hari = '';
    filterForm.tahun_akademik = '';
    applyFilters();
};

const destroyJadwal = async (jadwal) => {
    const confirmed = await confirm({
        title: 'Hapus jadwal?',
        message: `Jadwal ${jadwal.mata_kuliah} akan dihapus.`,
        confirmText: 'Hapus',
        variant: 'danger',
    });

    if (confirmed) {
        router.delete(`/admin/jadwal/${jadwal.id}`);
    }
};
</script>

<template>
    <Head title="Manajemen Jadwal" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Admin</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Manajemen jadwal</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Kelola jadwal kuliah dan cegah bentrok dosen, kelas, atau ruangan.
                    </p>
                </div>
                <Link
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800"
                    href="/admin/jadwal/create"
                >
                    <Plus class="h-4 w-4" />
                    Tambah
                </Link>
            </header>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <form class="grid gap-3 lg:grid-cols-[1fr_1fr_0.8fr_1fr_auto_auto]" @submit.prevent="applyFilters">
                    <select v-model="filterForm.kelas_id" aria-label="Filter kelas" class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                        <option value="">Semua kelas</option>
                        <option v-for="kelas in options.kelas" :key="kelas.id" :value="kelas.id">
                            {{ kelas.label }}
                        </option>
                    </select>

                    <select v-model="filterForm.dosen_id" aria-label="Filter dosen" class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                        <option value="">Semua dosen</option>
                        <option v-for="dosen in options.dosen" :key="dosen.id" :value="dosen.id">
                            {{ dosen.label }}
                        </option>
                    </select>

                    <select v-model="filterForm.hari" aria-label="Filter hari" class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                        <option value="">Semua hari</option>
                        <option v-for="day in options.days" :key="day" :value="day">
                            {{ day }}
                        </option>
                    </select>

                    <select v-model="filterForm.tahun_akademik" aria-label="Filter tahun akademik" class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                        <option value="">Tahun akademik</option>
                        <option v-for="tahun in options.tahun_akademik" :key="tahun" :value="tahun">
                            {{ tahun }}
                        </option>
                    </select>

                    <button class="touch-target inline-flex items-center justify-center gap-2 rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-wait disabled:opacity-70" type="submit" :disabled="filtering">
                        <Search class="h-4 w-4" />
                        {{ filtering ? 'Memuat' : 'Filter' }}
                    </button>
                    <button class="touch-target rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100 disabled:cursor-wait disabled:opacity-70" type="button" :disabled="filtering" @click="clearFilters">
                        Reset
                    </button>
                </form>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-zinc-950">Kalender mingguan</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-7">
                    <article v-for="day in options.days" :key="day" class="rounded-md border border-zinc-200 p-3">
                        <h3 class="text-sm font-semibold text-zinc-950">{{ day }}</h3>
                        <div v-if="weeklySchedules[day]?.length" class="mt-3 space-y-2">
                            <div v-for="item in weeklySchedules[day]" :key="item.id" class="rounded-md bg-zinc-100 p-3">
                                <p class="text-xs font-semibold text-zinc-950">{{ item.jam_mulai }}-{{ item.jam_selesai }}</p>
                                <p class="mt-1 text-sm font-medium text-zinc-900">{{ item.mata_kuliah }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ item.kelas?.nama_kelas }} · {{ item.ruangan }}</p>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-zinc-400">Kosong</p>
                    </article>
                </div>
            </section>

            <section class="relative rounded-lg border border-zinc-200 bg-white shadow-sm" :aria-busy="filtering">
                <div v-if="filtering" class="absolute inset-0 z-10 flex items-start justify-center rounded-lg bg-white/70 pt-12 text-sm font-semibold text-zinc-700 backdrop-blur-[1px]">
                    Memuat data...
                </div>
                <div v-if="jadwal.data.length" class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Mata kuliah</th>
                                <th class="px-5 py-3 font-semibold">Kelas</th>
                                <th class="px-5 py-3 font-semibold">Dosen</th>
                                <th class="px-5 py-3 font-semibold">Waktu</th>
                                <th class="px-5 py-3 font-semibold">Ruangan</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="item in jadwal.data" :key="item.id">
                                <td class="px-5 py-4 font-semibold text-zinc-950">{{ item.mata_kuliah }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.kelas?.nama_kelas }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.dosen?.name }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.hari }}, {{ item.jam_mulai }}-{{ item.jam_selesai }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ item.ruangan }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Link class="rounded-md border border-zinc-300 p-2 text-zinc-700 transition hover:bg-zinc-100" :href="`/admin/jadwal/${item.id}/edit`" title="Edit">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button class="rounded-md border border-red-200 p-2 text-red-700 transition hover:bg-red-50" title="Hapus" type="button" @click="destroyJadwal(item)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-10 text-center">
                    <p class="font-semibold text-zinc-800">Data jadwal tidak ditemukan.</p>
                    <p class="mt-1 text-sm text-zinc-500">Ubah filter atau tambahkan jadwal baru.</p>
                </div>

                <div class="flex flex-col gap-3 border-t border-zinc-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-zinc-500">
                        Menampilkan {{ jadwal.from ?? 0 }}-{{ jadwal.to ?? 0 }} dari {{ jadwal.total }} data
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in jadwal.links"
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
