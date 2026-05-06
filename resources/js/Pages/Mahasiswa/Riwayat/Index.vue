<script setup>
import { Head } from '@inertiajs/vue3';
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import { CalendarDays, CheckCircle2, History } from 'lucide-vue-next';

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
        <div class="mobile-app-surface space-y-5">
            <header class="content-hero">
                <p class="eyebrow">Riwayat</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Kehadiran pribadi</h1>
                <p class="mt-2 text-sm text-zinc-600">
                    Ringkasan persentase dihitung dari jumlah hadir dibanding total sesi per mata kuliah.
                </p>
            </header>

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="summary in summaries" :key="summary.mata_kuliah" class="metric-tile">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-zinc-950">{{ summary.mata_kuliah }}</p>
                            <p class="mt-1 text-xs font-medium text-apple-tertiary">
                                {{ summary.hadir }} hadir dari {{ summary.total_sesi }} sesi
                            </p>
                        </div>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-apple-blue-50 text-apple-blue">
                            <CheckCircle2 class="h-4 w-4" />
                        </span>
                    </div>
                    <div class="mt-5 flex items-end justify-between gap-3">
                        <p class="text-4xl font-semibold tracking-normal text-zinc-950">{{ summary.persentase }}%</p>
                        <div class="grid grid-cols-3 gap-1 text-center text-[11px] font-semibold">
                            <span class="rounded-md bg-sky-50 px-2 py-1 text-sky-800">Izin {{ summary.izin }}</span>
                            <span class="rounded-md bg-amber-50 px-2 py-1 text-amber-800">Sakit {{ summary.sakit }}</span>
                            <span class="rounded-md bg-rose-50 px-2 py-1 text-rose-800">Alpha {{ summary.tidak_hadir }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="ios-list">
                <div class="ios-list-row items-center">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-950 text-white">
                            <History class="h-4 w-4" />
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-zinc-950">Riwayat presensi</h2>
                            <p class="text-xs font-medium text-apple-tertiary">Semua catatan presensi pribadi</p>
                        </div>
                    </div>
                </div>

                <div v-if="history.data.length" class="divide-y divide-black/5 sm:hidden">
                    <article v-for="record in history.data" :key="record.id" class="px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-zinc-950">{{ record.mata_kuliah }}</p>
                                <p class="mt-1 text-xs font-medium text-apple-tertiary">{{ record.kelas }} &middot; {{ record.dosen }}</p>
                            </div>
                            <span class="ios-chip capitalize">{{ record.status.replace('_', ' ') }}</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between rounded-lg bg-zinc-100/80 px-3 py-2 text-xs font-semibold text-zinc-600">
                            <span class="flex items-center gap-1.5">
                                <CalendarDays class="h-3.5 w-3.5" />
                                {{ record.tanggal }}
                            </span>
                            <span>{{ record.timestamp ?? '-' }}</span>
                        </div>
                    </article>
                </div>

                <div v-else class="p-4">
                    <div class="empty-state text-center">Belum ada riwayat presensi.</div>
                </div>

                <div class="hidden overflow-x-auto sm:block">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs font-semibold text-zinc-500">
                            <tr>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Mata Kuliah</th>
                                <th class="px-5 py-3">Kelas</th>
                                <th class="px-5 py-3">Dosen</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5">
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
