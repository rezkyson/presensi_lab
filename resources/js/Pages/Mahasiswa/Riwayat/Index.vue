<script setup>
import { Head } from '@inertiajs/vue3';
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';

defineProps({
    history: {
        type: Object,
        required: true,
    },
    summaries: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Riwayat Kehadiran" />

    <MahasiswaLayout>
        <div class="space-y-6">
            <header>
                <p class="text-sm font-medium text-emerald-700">Riwayat</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Kehadiran pribadi</h1>
                <p class="mt-2 text-sm text-zinc-600">
                    Ringkasan persentase dihitung dari jumlah hadir dibanding total sesi per mata kuliah.
                </p>
            </header>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="summary in summaries" :key="summary.mata_kuliah" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="font-semibold text-zinc-950">{{ summary.mata_kuliah }}</p>
                    <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ summary.persentase }}%</p>
                    <p class="mt-1 text-sm text-zinc-600">
                        {{ summary.hadir }} hadir dari {{ summary.total_sesi }} sesi
                    </p>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="rounded-md bg-sky-50 p-2 text-sky-800">Izin {{ summary.izin }}</div>
                        <div class="rounded-md bg-amber-50 p-2 text-amber-800">Sakit {{ summary.sakit }}</div>
                        <div class="rounded-md bg-rose-50 p-2 text-rose-800">Alpha {{ summary.tidak_hadir }}</div>
                    </div>
                </article>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 p-5">
                    <h2 class="text-base font-semibold text-zinc-950">Riwayat presensi</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            <tr>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Mata Kuliah</th>
                                <th class="px-5 py-3">Kelas</th>
                                <th class="px-5 py-3">Dosen</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200">
                            <tr v-for="record in history.data" :key="record.id">
                                <td class="px-5 py-4">{{ record.tanggal }}</td>
                                <td class="px-5 py-4 font-semibold text-zinc-950">{{ record.mata_kuliah }}</td>
                                <td class="px-5 py-4">{{ record.kelas }}</td>
                                <td class="px-5 py-4">{{ record.dosen }}</td>
                                <td class="px-5 py-4 capitalize">{{ record.status.replace('_', ' ') }}</td>
                                <td class="px-5 py-4">{{ record.timestamp ?? '-' }}</td>
                            </tr>
                            <tr v-if="!history.data.length">
                                <td colspan="6" class="px-5 py-8 text-center text-zinc-500">Belum ada riwayat presensi.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </MahasiswaLayout>
</template>
