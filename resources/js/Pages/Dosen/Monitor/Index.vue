<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DosenLayout from '@/Layouts/DosenLayout.vue';
import { Activity, Clock, ExternalLink } from 'lucide-vue-next';

defineProps({
    sessions: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Monitor Presensi" />

    <DosenLayout>
        <div class="space-y-6">
            <header class="content-hero">
                <p class="eyebrow">Monitor</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Pantau sesi presensi</h1>
                <p class="mt-2 text-sm text-zinc-600">
                    Pilih sesi untuk melihat status kehadiran peserta kelas.
                </p>
            </header>

            <section class="apple-card p-5">
                <h2 class="text-base font-semibold text-zinc-950">Sesi terbaru</h2>
                <div v-if="sessions.length" class="mt-4 divide-y divide-black/5">
                    <div
                        v-for="session in sessions"
                        :key="session.id"
                        class="flex flex-col gap-4 py-4 first:pt-0 last:pb-0 md:flex-row md:items-center md:justify-between"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-zinc-950">{{ session.mata_kuliah }}</p>
                                <span
                                    class="ios-chip"
                                    :class="session.status === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-700'"
                                >
                                    {{ session.status }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-zinc-600">
                                {{ session.kelas?.nama_kelas }} &middot; {{ session.ruangan }}
                            </p>
                            <p class="mt-2 flex items-center gap-2 text-sm text-zinc-500">
                                <Clock class="h-4 w-4" />
                                {{ session.tanggal }} &middot; {{ session.jam_mulai }}-{{ session.jam_selesai }}
                            </p>
                        </div>
                        <Link
                            class="btn-primary"
                            :href="`/dosen/sesi/${session.id}/monitor`"
                        >
                            <ExternalLink class="h-4 w-4" />
                            Buka monitor
                        </Link>
                    </div>
                </div>
                <div v-else class="empty-state mt-4">
                    Belum ada sesi presensi.
                </div>
            </section>

            <section class="apple-subcard border-sky-100 bg-sky-50/90 p-5 text-sky-950">
                <div class="flex gap-3">
                    <Activity class="mt-0.5 h-5 w-5 text-sky-700" />
                    <p class="text-sm text-sky-800">
                        Monitor memperbarui data otomatis dengan polling. Jika broadcast aktif, pembaruan hadir juga dapat diterima lebih cepat.
                    </p>
                </div>
            </section>
        </div>
    </DosenLayout>
</template>
