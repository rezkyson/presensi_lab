<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DosenLayout from '@/Layouts/DosenLayout.vue';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { ArrowLeft, CheckCircle2, Clock, HeartPulse, RefreshCw, UserCheck, UserX } from 'lucide-vue-next';

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
    attendance: {
        type: Object,
        required: true,
    },
    pollingMs: {
        type: Number,
        default: 5000,
    },
    broadcastingEnabled: {
        type: Boolean,
        default: false,
    },
});

const attendanceState = ref(props.attendance);
const loading = ref(false);
const error = ref('');
const echoChannel = ref(null);
let intervalId = null;

const participants = computed(() => attendanceState.value.participants ?? []);
const summary = computed(() => attendanceState.value.summary ?? {});

const statusClasses = {
    hadir: 'bg-emerald-100 text-emerald-800',
    belum_hadir: 'bg-zinc-100 text-zinc-700',
    tidak_hadir: 'bg-rose-100 text-rose-800',
    izin: 'bg-sky-100 text-sky-800',
    sakit: 'bg-amber-100 text-amber-800',
};

const statusLabel = (status) => (status ?? 'belum_hadir').replace('_', ' ');

const refreshAttendance = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await axios.get(`/dosen/sesi/${props.session.id}/kehadiran`);
        attendanceState.value = response.data.attendance;
    } catch (refreshError) {
        error.value = refreshError.response?.data?.message ?? 'Data monitor gagal diperbarui.';
    } finally {
        loading.value = false;
    }
};

const mergeBroadcastPresence = (payload) => {
    const presensi = payload.presensi;

    if (!presensi) {
        return;
    }

    attendanceState.value = {
        ...attendanceState.value,
        participants: participants.value.map((participant) => (
            participant.mahasiswa_id === presensi.mahasiswa_id
                ? {
                    ...participant,
                    status: presensi.status,
                    timestamp: presensi.timestamp,
                    metode: presensi.metode,
                }
                : participant
        )),
        last_updated_at: new Date().toISOString(),
    };

    const nextSummary = { ...summary.value };
    nextSummary.hadir = attendanceState.value.participants.filter((item) => item.status === 'hadir').length;
    nextSummary.belum_hadir = attendanceState.value.participants.filter((item) => item.status === 'belum_hadir').length;
    nextSummary.tidak_hadir = attendanceState.value.participants.filter((item) => item.status === 'tidak_hadir').length;
    nextSummary.izin = attendanceState.value.participants.filter((item) => item.status === 'izin').length;
    nextSummary.sakit = attendanceState.value.participants.filter((item) => item.status === 'sakit').length;
    attendanceState.value.summary = nextSummary;
};

onMounted(() => {
    intervalId = window.setInterval(refreshAttendance, props.pollingMs);

    if (window.Echo) {
        echoChannel.value = window.Echo.private(`sesi.${props.session.id}`)
            .listen('.mahasiswa.hadir', mergeBroadcastPresence);
    }
});

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(intervalId);
    }

    if (window.Echo) {
        window.Echo.leave(`private-sesi.${props.session.id}`);
    }
});
</script>

<template>
    <Head title="Monitor Presensi" />

    <DosenLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <Link
                        class="inline-flex items-center gap-2 text-sm font-semibold text-zinc-600 hover:text-zinc-950"
                        href="/dosen/monitor"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Kembali
                    </Link>
                    <p class="mt-4 text-sm font-medium text-emerald-700">Monitor Presensi</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">{{ session.mata_kuliah }}</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        {{ session.kelas?.nama_kelas }} &middot; {{ session.ruangan }} &middot;
                        {{ session.jam_mulai }}-{{ session.jam_selesai }}
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-md border border-zinc-300 px-4 py-2.5 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="loading"
                    @click="refreshAttendance"
                >
                    <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                    Refresh
                </button>
            </header>

            <section class="grid gap-4 md:grid-cols-5">
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-600">Peserta</p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ summary.total ?? 0 }}</p>
                </article>
                <article class="rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                    <p class="flex items-center gap-2 text-sm font-medium text-emerald-800">
                        <UserCheck class="h-4 w-4" />
                        Hadir
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-emerald-950">{{ summary.hadir ?? 0 }}</p>
                </article>
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="flex items-center gap-2 text-sm font-medium text-zinc-600">
                        <Clock class="h-4 w-4" />
                        Belum
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ summary.belum_hadir ?? 0 }}</p>
                </article>
                <article class="rounded-lg border border-sky-200 bg-sky-50 p-5">
                    <p class="flex items-center gap-2 text-sm font-medium text-sky-800">
                        <CheckCircle2 class="h-4 w-4" />
                        Izin
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-sky-950">{{ summary.izin ?? 0 }}</p>
                </article>
                <article class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                    <p class="flex items-center gap-2 text-sm font-medium text-amber-800">
                        <HeartPulse class="h-4 w-4" />
                        Sakit
                    </p>
                    <p class="mt-3 text-3xl font-semibold text-amber-950">{{ summary.sakit ?? 0 }}</p>
                </article>
            </section>

            <p v-if="error" class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                {{ error }}
            </p>

            <section class="rounded-lg border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 p-5">
                    <h2 class="text-base font-semibold text-zinc-950">Daftar peserta</h2>
                    <p class="mt-1 text-sm text-zinc-500">
                        Terakhir diperbarui {{ attendanceState.last_updated_at }}.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            <tr>
                                <th class="px-5 py-3">Mahasiswa</th>
                                <th class="px-5 py-3">NIM</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Waktu</th>
                                <th class="px-5 py-3">Metode</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white">
                            <tr v-for="participant in participants" :key="participant.mahasiswa_id">
                                <td class="px-5 py-4 font-semibold text-zinc-950">{{ participant.nama }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ participant.nim }}</td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold capitalize"
                                        :class="statusClasses[participant.status] ?? statusClasses.belum_hadir"
                                    >
                                        {{ statusLabel(participant.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-zinc-600">{{ participant.timestamp ?? '-' }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ participant.metode ?? '-' }}</td>
                            </tr>
                            <tr v-if="!participants.length">
                                <td colspan="5" class="px-5 py-8 text-center text-zinc-500">
                                    Belum ada peserta pada kelas ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-2 text-sm text-zinc-600 sm:flex-row sm:items-center sm:justify-between">
                    <p>Polling aktif setiap {{ pollingMs / 1000 }} detik.</p>
                    <p>{{ broadcastingEnabled ? 'Broadcast siap dipakai jika Echo tersambung.' : 'Broadcast belum dikonfigurasi; polling tetap aktif.' }}</p>
                </div>
            </section>
        </div>
    </DosenLayout>
</template>
