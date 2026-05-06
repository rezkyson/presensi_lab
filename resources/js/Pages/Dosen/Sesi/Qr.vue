<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import DosenLayout from '@/Layouts/DosenLayout.vue';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Activity, ArrowLeft, RefreshCw, Square } from 'lucide-vue-next';

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
    qr: {
        type: Object,
        required: true,
    },
    refreshSeconds: {
        type: Number,
        default: 60,
    },
});

const qrData = ref(props.qr);
const countdown = ref(props.qr.expires_in ?? props.refreshSeconds);
const loading = ref(false);
const error = ref('');
let intervalId = null;

const percentage = computed(() => Math.max(0, Math.min(100, (countdown.value / props.refreshSeconds) * 100)));

const refreshQr = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await axios.get(`/dosen/sesi/${props.session.id}/qr-data`);
        qrData.value = response.data.qr;
        countdown.value = response.data.qr.expires_in ?? props.refreshSeconds;
    } catch (refreshError) {
        error.value = refreshError.response?.data?.message
            ?? (refreshError.request ? 'Koneksi terputus saat memperbarui token QR.' : 'Token QR gagal diperbarui.');
    } finally {
        loading.value = false;
    }
};

const closeSession = () => {
    router.delete(`/dosen/sesi/${props.session.id}`);
};

onMounted(() => {
    intervalId = window.setInterval(() => {
        countdown.value = Math.max(0, countdown.value - 1);

        if (countdown.value <= 0 && !loading.value) {
            refreshQr();
        }
    }, 1000);
});

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(intervalId);
    }
});
</script>

<template>
    <Head title="QR Sesi Absensi" />

    <DosenLayout>
        <div class="min-h-[calc(100vh-9rem)] space-y-5">
            <header class="content-hero flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <Link
                        class="inline-flex items-center gap-2 text-sm font-semibold text-white/65 transition hover:text-white"
                        href="/dosen/sesi"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Kembali
                    </Link>
                    <h1 class="mt-3 text-2xl font-semibold text-zinc-950">{{ session.mata_kuliah }}</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        {{ session.kelas?.nama_kelas }} &middot; {{ session.ruangan }} &middot;
                        {{ session.jam_mulai }}-{{ session.jam_selesai }}
                    </p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <Link
                        class="btn-secondary"
                        :href="`/dosen/sesi/${session.id}/monitor`"
                    >
                        <Activity class="h-4 w-4" />
                        Monitor
                    </Link>
                    <button
                        type="button"
                        class="btn-danger"
                        @click="closeSession"
                    >
                        <Square class="h-4 w-4" />
                        Tutup sesi
                    </button>
                </div>
            </header>

            <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <article class="apple-card flex min-h-[65vh] items-center justify-center p-3 sm:p-5">
                    <div class="w-full text-center">
                        <div class="mx-auto aspect-square w-full max-w-[min(78vh,720px)] rounded-lg bg-white p-3 shadow-inner sm:p-5">
                            <img
                                v-if="qrData.data_uri"
                                class="h-full w-full object-contain"
                                :src="qrData.data_uri"
                                alt="QR token sesi absensi"
                            />
                        </div>
                        <div class="mt-5 h-2 overflow-hidden rounded-full bg-zinc-200">
                            <div
                                class="h-full rounded-full bg-emerald-600 transition-all"
                                :style="{ width: `${percentage}%` }"
                            />
                        </div>
                        <p class="mt-3 text-sm font-semibold text-zinc-700 sm:text-base">
                            Token berganti dalam {{ countdown }} detik
                        </p>
                        <p v-if="error" class="status-error mt-3">{{ error }}</p>
                    </div>
                </article>

                <aside class="apple-card p-5">
                    <h2 class="text-base font-semibold text-zinc-950">Detail sesi</h2>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div>
                            <dt class="font-medium text-zinc-500">Kelas</dt>
                            <dd class="mt-1 font-semibold text-zinc-950">{{ session.kelas?.nama_kelas }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500">Program studi</dt>
                            <dd class="mt-1 font-semibold text-zinc-950">{{ session.kelas?.prodi }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500">Ruangan</dt>
                            <dd class="mt-1 font-semibold text-zinc-950">{{ session.ruangan }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500">Dibuka</dt>
                            <dd class="mt-1 font-semibold text-zinc-950">{{ session.dibuka_at }}</dd>
                        </div>
                    </dl>

                    <button
                        type="button"
                        class="btn-secondary mt-6 w-full"
                        :disabled="loading"
                        @click="refreshQr"
                    >
                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                        Refresh token
                    </button>

                    <p class="mt-4 break-all rounded-md bg-zinc-50 p-3 text-xs text-zinc-500">
                        {{ qrData.token }}
                    </p>
                </aside>
            </section>
        </div>
    </DosenLayout>
</template>
