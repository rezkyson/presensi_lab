<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { BookOpen, CalendarDays, GraduationCap, UserRoundCheck } from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    attendanceToday: {
        type: Object,
        required: true,
    },
    recentSchedules: {
        type: Array,
        default: () => [],
    },
});

const statCards = [
    { label: 'Mahasiswa', value: props.stats.mahasiswa, icon: GraduationCap },
    { label: 'Dosen', value: props.stats.dosen, icon: UserRoundCheck },
    { label: 'Kelas', value: props.stats.kelas, icon: BookOpen },
    { label: 'Jadwal Aktif', value: props.stats.jadwalAktif, icon: CalendarDays },
];

const attendanceLabels = [
    { key: 'hadir', label: 'Hadir', tone: 'bg-emerald-100 text-emerald-800' },
    { key: 'tidak_hadir', label: 'Tidak hadir', tone: 'bg-zinc-200 text-zinc-800' },
    { key: 'izin', label: 'Izin', tone: 'bg-amber-100 text-amber-800' },
    { key: 'sakit', label: 'Sakit', tone: 'bg-sky-100 text-sky-800' },
];
</script>

<template>
    <Head title="Dashboard Admin" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="content-hero">
                <p class="eyebrow">Dashboard Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Ringkasan sistem</h1>
                <p class="mt-2 max-w-2xl text-sm text-zinc-600">
                    Pantau data utama dan aktivitas presensi hari ini.
                </p>
            </header>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in statCards"
                    :key="card.label"
                    class="metric-tile"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-zinc-600">{{ card.label }}</p>
                        <component :is="card.icon" class="h-5 w-5 text-apple-blue" />
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-zinc-950">{{ card.value }}</p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1fr_1.4fr]">
                <article class="ios-list p-5">
                    <h2 class="text-base font-semibold text-zinc-950">Presensi hari ini</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <div
                            v-for="item in attendanceLabels"
                            :key="item.key"
                            class="flex items-center justify-between rounded-lg bg-zinc-100/75 px-4 py-3"
                        >
                            <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', item.tone]">
                                {{ item.label }}
                            </span>
                            <span class="text-lg font-semibold text-zinc-950">
                                {{ attendanceToday[item.key] }}
                            </span>
                        </div>
                    </div>
                </article>

                <article class="table-shell p-5">
                    <h2 class="text-base font-semibold text-zinc-950">Jadwal terbaru</h2>
                    <div v-if="recentSchedules.length" class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="text-xs uppercase text-zinc-500">
                                <tr>
                                    <th class="py-3 pr-4 font-semibold">Mata kuliah</th>
                                    <th class="py-3 pr-4 font-semibold">Kelas</th>
                                    <th class="py-3 pr-4 font-semibold">Dosen</th>
                                    <th class="py-3 pr-4 font-semibold">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <tr v-for="schedule in recentSchedules" :key="schedule.id">
                                    <td class="py-3 pr-4 font-medium text-zinc-950">{{ schedule.mata_kuliah }}</td>
                                    <td class="py-3 pr-4 text-zinc-600">{{ schedule.kelas?.nama_kelas }}</td>
                                    <td class="py-3 pr-4 text-zinc-600">{{ schedule.dosen }}</td>
                                    <td class="py-3 pr-4 text-zinc-600">
                                        {{ schedule.hari }}, {{ schedule.jam_mulai }}-{{ schedule.jam_selesai }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="empty-state mt-4">
                        Belum ada jadwal yang tercatat.
                    </div>
                </article>
            </section>
        </div>
    </AdminLayout>
</template>
