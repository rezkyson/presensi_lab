<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DosenLayout from '@/Layouts/DosenLayout.vue';
import { Download, FileSpreadsheet, Search } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    sessions: {
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

const exportUrl = (type) => `/dosen/rekap/export/${type}${queryString.value ? `?${queryString.value}` : ''}`;
</script>

<template>
    <Head title="Rekap Dosen" />

    <DosenLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Rekap Dosen</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Riwayat sesi presensi</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Lihat ringkasan kehadiran per sesi dan export laporan.
                    </p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a class="inline-flex items-center justify-center gap-2 rounded-md border border-zinc-300 px-4 py-2.5 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50" :href="exportUrl('pdf')">
                        <Download class="h-4 w-4" />
                        PDF
                    </a>
                    <a class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800" :href="exportUrl('excel')">
                        <FileSpreadsheet class="h-4 w-4" />
                        Excel
                    </a>
                </div>
            </header>

            <form class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm" method="get" action="/dosen/rekap">
                <div class="grid gap-4 md:grid-cols-4">
                    <label class="text-sm font-medium text-zinc-700">
                        Kelas
                        <select name="kelas_id" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                            <option value="">Semua kelas</option>
                            <option v-for="kelas in options.kelas" :key="kelas.id" :value="kelas.id" :selected="String(filters.kelas_id ?? '') === String(kelas.id)">
                                {{ kelas.nama_kelas }} - {{ kelas.prodi }}
                            </option>
                        </select>
                    </label>
                    <label class="text-sm font-medium text-zinc-700">
                        Mata kuliah
                        <input name="mata_kuliah" :value="filters.mata_kuliah" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                    </label>
                    <label class="text-sm font-medium text-zinc-700">
                        Dari tanggal
                        <input name="date_from" type="date" :value="filters.date_from" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                    </label>
                    <label class="text-sm font-medium text-zinc-700">
                        Sampai tanggal
                        <input name="date_to" type="date" :value="filters.date_to" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                    </label>
                </div>
                <div class="mt-4 flex gap-2">
                    <button class="inline-flex items-center justify-center gap-2 rounded-md bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white" type="submit">
                        <Search class="h-4 w-4" />
                        Filter
                    </button>
                    <Link class="rounded-md border border-zinc-300 px-4 py-2.5 text-sm font-semibold text-zinc-800" href="/dosen/rekap">
                        Reset
                    </Link>
                </div>
            </form>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="session in sessions.data" :key="session.id" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-zinc-950">{{ session.mata_kuliah }}</p>
                            <p class="mt-1 text-sm text-zinc-600">{{ session.kelas }} &middot; {{ session.ruangan }}</p>
                            <p class="mt-2 text-sm text-zinc-500">{{ session.tanggal }} &middot; {{ session.status }}</p>
                        </div>
                        <Link class="text-sm font-semibold text-emerald-700 hover:text-emerald-900" :href="`/dosen/sesi/${session.id}/monitor`">
                            Monitor
                        </Link>
                    </div>
                    <div class="mt-4 grid grid-cols-5 gap-2 text-center text-sm">
                        <div class="rounded-md bg-emerald-50 p-2 text-emerald-800">
                            <p class="font-semibold">{{ session.summary.hadir }}</p>
                            <p>Hadir</p>
                        </div>
                        <div class="rounded-md bg-rose-50 p-2 text-rose-800">
                            <p class="font-semibold">{{ session.summary.tidak_hadir }}</p>
                            <p>Alpha</p>
                        </div>
                        <div class="rounded-md bg-sky-50 p-2 text-sky-800">
                            <p class="font-semibold">{{ session.summary.izin }}</p>
                            <p>Izin</p>
                        </div>
                        <div class="rounded-md bg-amber-50 p-2 text-amber-800">
                            <p class="font-semibold">{{ session.summary.sakit }}</p>
                            <p>Sakit</p>
                        </div>
                        <div class="rounded-md bg-zinc-50 p-2 text-zinc-700">
                            <p class="font-semibold">{{ session.summary.total }}</p>
                            <p>Total</p>
                        </div>
                    </div>
                </article>
            </section>

            <div v-if="!sessions.data.length" class="rounded-lg border border-dashed border-zinc-300 bg-white p-8 text-center text-sm text-zinc-500">
                Belum ada sesi presensi.
            </div>
        </div>
    </DosenLayout>
</template>
