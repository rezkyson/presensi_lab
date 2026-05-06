<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import DosenLayout from '@/Layouts/DosenLayout.vue';
import { Activity, Clock, ExternalLink, Play, QrCode } from 'lucide-vue-next';

defineProps({
    todayName: {
        type: String,
        required: true,
    },
    schedules: {
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
    <Head title="Sesi Absensi" />

    <DosenLayout>
        <div class="space-y-6">
            <header class="content-hero flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">Sesi Absensi</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Buka sesi dan tampilkan QR</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Hari ini {{ todayName }}. Token QR otomatis diganti setiap 60 detik.
                    </p>
                </div>
            </header>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
                <article class="apple-card p-5">
                    <h2 class="text-base font-semibold text-zinc-950">Daftar jadwal dosen</h2>
                    <div v-if="schedules.length" class="mt-4 divide-y divide-black/5">
                        <div
                            v-for="schedule in schedules"
                            :key="schedule.id"
                            class="flex flex-col gap-4 py-4 first:pt-0 last:pb-0 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-zinc-950">{{ schedule.mata_kuliah }}</p>
                                    <span
                                        class="ios-chip"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800': schedule.schedule_status === 'ongoing' && !schedule.completed_session_id,
                                            'bg-amber-100 text-amber-800': schedule.schedule_status === 'upcoming' && !schedule.completed_session_id,
                                            'bg-zinc-100 text-zinc-700': schedule.schedule_status === 'ended' || schedule.completed_session_id,
                                            'bg-rose-100 text-rose-800': schedule.schedule_status === 'unavailable',
                                        }"
                                    >
                                        {{ schedule.schedule_status_label }}
                                    </span>
                                    <span
                                        v-if="schedule.active_session_id"
                                        class="ios-chip bg-sky-100 text-sky-800"
                                    >
                                        Aktif
                                    </span>
                                    <span
                                        v-if="schedule.completed_session_id"
                                        class="ios-chip"
                                    >
                                        Selesai
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-zinc-600">
                                    {{ schedule.kelas?.nama_kelas }} &middot; {{ schedule.kelas?.prodi }}
                                </p>
                                <p class="mt-2 flex items-center gap-2 text-sm text-zinc-500">
                                    <Clock class="h-4 w-4" />
                                    {{ schedule.hari }}, {{ schedule.jam_mulai }}-{{ schedule.jam_selesai }} &middot; {{ schedule.ruangan }}
                                </p>
                                <p
                                    v-if="schedule.completed_session_id && schedule.closed_at"
                                    class="mt-2 text-sm font-medium text-zinc-600"
                                >
                                    Ditutup {{ schedule.closed_at }}
                                </p>
                                <p
                                    v-if="!schedule.active_session_id && !schedule.can_open_session && schedule.unavailable_reason"
                                    class="mt-2 text-sm font-medium"
                                    :class="schedule.schedule_status === 'ended' || schedule.completed_session_id ? 'text-zinc-600' : 'text-amber-700'"
                                >
                                    {{ schedule.unavailable_reason }}
                                </p>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <Link
                                    v-if="schedule.active_session_id"
                                    class="btn-secondary"
                                    :href="`/dosen/sesi/${schedule.active_session_id}/qr`"
                                >
                                    <QrCode class="h-4 w-4" />
                                    Lihat QR
                                </Link>
                                <Link
                                    v-if="schedule.active_session_id"
                                    class="btn-secondary"
                                    :href="`/dosen/sesi/${schedule.active_session_id}/monitor`"
                                >
                                    <Activity class="h-4 w-4" />
                                    Monitor
                                </Link>
                                <button
                                    v-if="!schedule.active_session_id"
                                    type="button"
                                    class="inline-flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-500 enabled:bg-apple-blue enabled:text-white enabled:hover:bg-apple-blue-700"
                                    :disabled="!schedule.can_open_session"
                                    @click="openSession(schedule)"
                                >
                                    <Play class="h-4 w-4" />
                                    {{ schedule.completed_session_id ? 'Sesi selesai' : (schedule.can_open_session ? 'Buka sesi' : schedule.schedule_status_label) }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="empty-state mt-4">
                        Belum ada jadwal yang diampu.
                    </div>
                </article>

                <article class="apple-card p-5">
                    <h2 class="text-base font-semibold text-zinc-950">Sesi aktif</h2>
                    <div v-if="activeSessions.length" class="mt-4 space-y-3">
                        <div
                            v-for="session in activeSessions"
                            :key="session.id"
                            class="apple-subcard border-emerald-100 bg-emerald-50/90 p-4"
                        >
                            <p class="font-semibold text-emerald-950">{{ session.mata_kuliah }}</p>
                            <p class="mt-1 text-sm text-emerald-800">
                                {{ session.kelas?.nama_kelas }} &middot; {{ session.ruangan }}
                            </p>
                            <p class="mt-1 text-xs font-medium text-emerald-700">
                                Dibuka {{ session.dibuka_at }}
                            </p>
                            <Link
                                class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-950"
                                :href="`/dosen/sesi/${session.id}/qr`"
                            >
                                <ExternalLink class="h-4 w-4" />
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
        </div>
    </DosenLayout>
</template>
