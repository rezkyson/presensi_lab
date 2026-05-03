<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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

const page = usePage();

const openSession = (schedule) => {
    router.post(`/dosen/jadwal/${schedule.id}/sesi`);
};
</script>

<template>
    <Head title="Sesi Absensi" />

    <DosenLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Sesi Absensi</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Buka sesi dan tampilkan QR</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Hari ini {{ todayName }}. Token QR otomatis diganti setiap 60 detik.
                    </p>
                </div>
            </header>

            <div
                v-if="page.props.flash?.success"
                class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            >
                {{ page.props.flash.success }}
            </div>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-zinc-950">Daftar jadwal dosen</h2>
                    <div v-if="schedules.length" class="mt-4 divide-y divide-zinc-200">
                        <div
                            v-for="schedule in schedules"
                            :key="schedule.id"
                            class="flex flex-col gap-4 py-4 first:pt-0 last:pb-0 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-zinc-950">{{ schedule.mata_kuliah }}</p>
                                    <span
                                        v-if="schedule.is_today"
                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800"
                                    >
                                        Hari ini
                                    </span>
                                    <span
                                        v-if="schedule.active_session_id"
                                        class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-800"
                                    >
                                        Aktif
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-zinc-600">
                                    {{ schedule.kelas?.nama_kelas }} &middot; {{ schedule.kelas?.prodi }}
                                </p>
                                <p class="mt-2 flex items-center gap-2 text-sm text-zinc-500">
                                    <Clock class="h-4 w-4" />
                                    {{ schedule.hari }}, {{ schedule.jam_mulai }}-{{ schedule.jam_selesai }} &middot; {{ schedule.ruangan }}
                                </p>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <Link
                                    v-if="schedule.active_session_id"
                                    class="inline-flex items-center justify-center gap-2 rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50"
                                    :href="`/dosen/sesi/${schedule.active_session_id}/qr`"
                                >
                                    <QrCode class="h-4 w-4" />
                                    Lihat QR
                                </Link>
                                <Link
                                    v-if="schedule.active_session_id"
                                    class="inline-flex items-center justify-center gap-2 rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50"
                                    :href="`/dosen/sesi/${schedule.active_session_id}/monitor`"
                                >
                                    <Activity class="h-4 w-4" />
                                    Monitor
                                </Link>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800"
                                    @click="openSession(schedule)"
                                >
                                    <Play class="h-4 w-4" />
                                    Buka sesi
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="mt-4 rounded-md border border-dashed border-zinc-300 p-6 text-sm text-zinc-500">
                        Belum ada jadwal yang diampu.
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
                    <div v-else class="mt-4 rounded-md border border-dashed border-zinc-300 p-6 text-sm text-zinc-500">
                        Belum ada sesi absensi aktif.
                    </div>
                </article>
            </section>
        </div>
    </DosenLayout>
</template>
