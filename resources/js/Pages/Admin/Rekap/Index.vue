<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Download, FileSpreadsheet, Search } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    records: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    options: {
        type: Object,
        required: true,
    },
});

const queryString = computed(() => new URLSearchParams(
    Object.fromEntries(Object.entries(props.filters).filter(([, value]) => value)),
).toString());

const exportUrl = (type) => `/admin/rekap/export/${type}${queryString.value ? `?${queryString.value}` : ''}`;
</script>

<template>
    <Head title="Rekap Presensi Admin" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="content-hero flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="eyebrow">Rekap Admin</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Laporan presensi</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Filter presensi berdasarkan kelas, mahasiswa, dosen, tanggal, mata kuliah, dan status.
                    </p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a
                        class="btn-secondary"
                        :href="exportUrl('pdf')"
                    >
                        <Download class="h-4 w-4" />
                        PDF
                    </a>
                    <a
                        class="btn-primary"
                        :href="exportUrl('excel')"
                    >
                        <FileSpreadsheet class="h-4 w-4" />
                        Excel
                    </a>
                </div>
            </header>

            <section class="grid gap-4 md:grid-cols-5">
                <article v-for="(value, key) in stats" :key="key" class="metric-tile">
                    <p class="text-sm font-medium capitalize text-zinc-600">{{ key.replace('_', ' ') }}</p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ value }}</p>
                </article>
            </section>

            <form class="ios-filter-bar" method="get" action="/admin/rekap">
                <div class="grid gap-4 md:grid-cols-4">
                    <label class="text-sm font-medium text-zinc-700">
                        Kelas
                        <select name="kelas_id" class="form-input mt-1 py-2">
                            <option value="">Semua kelas</option>
                            <option v-for="kelas in options.kelas" :key="kelas.id" :value="kelas.id" :selected="String(filters.kelas_id ?? '') === String(kelas.id)">
                                {{ kelas.nama_kelas }} - {{ kelas.prodi }}
                            </option>
                        </select>
                    </label>
                    <label class="text-sm font-medium text-zinc-700">
                        Mahasiswa
                        <select name="mahasiswa_id" class="form-input mt-1 py-2">
                            <option value="">Semua mahasiswa</option>
                            <option v-for="mahasiswa in options.mahasiswa" :key="mahasiswa.id" :value="mahasiswa.id" :selected="String(filters.mahasiswa_id ?? '') === String(mahasiswa.id)">
                                {{ mahasiswa.name }} - {{ mahasiswa.nim }}
                            </option>
                        </select>
                    </label>
                    <label class="text-sm font-medium text-zinc-700">
                        Dosen
                        <select name="dosen_id" class="form-input mt-1 py-2">
                            <option value="">Semua dosen</option>
                            <option v-for="dosen in options.dosen" :key="dosen.id" :value="dosen.id" :selected="String(filters.dosen_id ?? '') === String(dosen.id)">
                                {{ dosen.name }} - {{ dosen.nip }}
                            </option>
                        </select>
                    </label>
                    <label class="text-sm font-medium text-zinc-700">
                        Status
                        <select name="status" class="form-input mt-1 py-2">
                            <option value="">Semua status</option>
                            <option v-for="status in ['hadir', 'tidak_hadir', 'izin', 'sakit']" :key="status" :value="status" :selected="filters.status === status">
                                {{ status.replace('_', ' ') }}
                            </option>
                        </select>
                    </label>
                    <label class="text-sm font-medium text-zinc-700">
                        Mata kuliah
                        <input name="mata_kuliah" :value="filters.mata_kuliah" class="form-input mt-1 py-2" placeholder="Cari mata kuliah">
                    </label>
                    <label class="text-sm font-medium text-zinc-700">
                        Dari tanggal
                        <input name="date_from" type="date" :value="filters.date_from" class="form-input mt-1 py-2">
                    </label>
                    <label class="text-sm font-medium text-zinc-700">
                        Sampai tanggal
                        <input name="date_to" type="date" :value="filters.date_to" class="form-input mt-1 py-2">
                    </label>
                    <div class="flex items-end gap-2">
                        <button class="btn-dark w-full" type="submit">
                            <Search class="h-4 w-4" />
                            Filter
                        </button>
                        <Link class="btn-secondary" href="/admin/rekap">
                            Reset
                        </Link>
                    </div>
                </div>
            </form>

            <section class="table-shell">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs font-semibold text-zinc-500">
                            <tr>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Mahasiswa</th>
                                <th class="px-5 py-3">Kelas</th>
                                <th class="px-5 py-3">Dosen</th>
                                <th class="px-5 py-3">Mata Kuliah</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5">
                            <tr v-for="record in records.data" :key="record.id">
                                <td class="px-5 py-4">{{ record.tanggal }}</td>
                                <td class="px-5 py-4 font-semibold text-zinc-950">{{ record.mahasiswa }}<br><span class="font-normal text-zinc-500">{{ record.nim }}</span></td>
                                <td class="px-5 py-4">{{ record.kelas }}</td>
                                <td class="px-5 py-4">{{ record.dosen }}</td>
                                <td class="px-5 py-4">{{ record.mata_kuliah }}</td>
                                <td class="px-5 py-4 capitalize">{{ record.status.replace('_', ' ') }}</td>
                                <td class="px-5 py-4">{{ record.timestamp ?? '-' }}</td>
                            </tr>
                            <tr v-if="!records.data.length">
                                <td colspan="7" class="px-5 py-8 text-center text-zinc-500">Tidak ada data.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
