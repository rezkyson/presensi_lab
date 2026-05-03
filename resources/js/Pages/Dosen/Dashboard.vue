<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import DosenLayout from '@/Layouts/DosenLayout.vue';
import { Activity, CalendarPlus, MonitorUp, QrCode } from 'lucide-vue-next';

defineProps({
    todayName: {
        type: String,
        required: true,
    },
    todaySchedules: {
        type: Array,
        default: () => [],
    },
    upcomingSchedules: {
        type: Array,
        default: () => [],
    },
    activeSessions: {
        type: Array,
        default: () => [],
    },
});

const openSession = (schedule) => {
    router.post(`/dosen/jadwal/${schedule.id}/sesi`);
};
</script>

<template>
    <Head title="Dashboard Dosen" />

    <DosenLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Dashboard Dosen</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Jadwal mengajar</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Hari ini {{ todayName }}.
                    </p>
                </div>
                <Link
                    class="touch-target inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800"
                    href="/dosen/sesi"
                >
                    <CalendarPlus class="h-4 w-4" />
                    Kelola sesi
                </Link>
            </header>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-zinc-950">Jadwal hari ini</h2>
                    <div v-if="todaySchedules.length" class="mt-4 space-y-3">
                        <div
                            v-for="schedule in todaySchedules"
                            :key="schedule.id"
                            class="rounded-md border border-zinc-200 p-4"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-semibold text-zinc-950">{{ schedule.mata_kuliah }}</p>
                                    <p class="mt-1 text-sm text-zinc-600">
                                        {{ schedule.kelas?.nama_kelas }} &middot; {{ schedule.kelas?.prodi }}
                                    </p>
                                </div>
                                <p class="text-sm font-medium text-zinc-700">
                                    {{ schedule.jam_mulai }}-{{ schedule.jam_selesai }}
                                </p>
                            </div>
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-zinc-500">{{ schedule.ruangan }}</p>
                                <button
                                    type="button"
                                    class="touch-target inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800"
                                    @click="openSession(schedule)"
                                >
                                    <QrCode class="h-4 w-4" />
                                    Buka QR
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="empty-state mt-4">
                        Tidak ada jadwal mengajar hari ini.
                    </div>
                </article>

                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-zinc-950">Sesi aktif</h2>
                    <div v-if="activeSessions.length" class="mt-4 space-y-3">
                        <div
                            v-for="session in activeSessions"
                            :key="session.id"
                            class="rounded-md border border-emerald-200 bg-emerald-50 p-4"
                        >
                            <p class="font-semibold text-emerald-950">{{ session.mata_kuliah }}</p>
                            <p class="mt-1 text-sm text-emerald-800">
                                {{ session.kelas }} &middot; Dibuka {{ session.dibuka_at }}
                            </p>
                            <Link
                                class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-950"
                                :href="`/dosen/sesi/${session.id}/qr`"
                            >
                                <MonitorUp class="h-4 w-4" />
                                Tampilkan QR
                            </Link>
                            <Link
                                class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-950"
                                :href="`/dosen/sesi/${session.id}/monitor`"
                            >
                                <Activity class="h-4 w-4" />
                                Monitor
                            </Link>
                        </div>
                    </div>
                    <div v-else class="empty-state mt-4">
                        Belum ada sesi absensi aktif.
                    </div>
                </article>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-zinc-950">Jadwal mendatang</h2>
                <div v-if="upcomingSchedules.length" class="mt-4 grid gap-3 md:grid-cols-2">
                    <div
                        v-for="schedule in upcomingSchedules"
                        :key="schedule.id"
                        class="rounded-md border border-zinc-200 p-4"
                    >
                        <p class="font-semibold text-zinc-950">{{ schedule.mata_kuliah }}</p>
                        <p class="mt-1 text-sm text-zinc-600">
                            {{ schedule.hari }}, {{ schedule.jam_mulai }}-{{ schedule.jam_selesai }}
                        </p>
                        <p class="mt-2 text-sm text-zinc-500">
                            {{ schedule.kelas?.nama_kelas }} &middot; {{ schedule.ruangan }}
                        </p>
                    </div>
                </div>
                <div v-else class="empty-state mt-4">
                    Belum ada jadwal mendatang.
                </div>
            </section>
        </div>
    </DosenLayout>
</template>
