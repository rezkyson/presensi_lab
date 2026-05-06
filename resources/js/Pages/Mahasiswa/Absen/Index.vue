<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import axios from 'axios';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import { computed, onBeforeUnmount, ref } from 'vue';
import { AlertCircle, Camera, CameraOff, CheckCircle2, ScanLine, UserCircle } from 'lucide-vue-next';

const props = defineProps({
    faceRegistered: {
        type: Boolean,
        required: true,
    },
    activeSessions: {
        type: Array,
        default: () => [],
    },
    attendanceToday: {
        type: Array,
        default: () => [],
    },
});

const scannerId = 'mahasiswa-qr-reader';
const scanner = ref(null);
const scanning = ref(false);
const processing = ref(false);
const error = ref('');
const status = ref('');

const canScan = computed(() => props.faceRegistered && props.activeSessions.length > 0 && !processing.value);
const secureCameraContext = computed(() => window.isSecureContext || ['localhost', '127.0.0.1'].includes(window.location.hostname));
const scannerHelperText = computed(() => {
    if (error.value) {
        return 'Minta dosen menampilkan QR terbaru, lalu tekan Mulai untuk scan ulang.';
    }

    if (processing.value) {
        return 'QR sedang divalidasi.';
    }

    if (scanning.value) {
        return 'Arahkan kamera ke QR yang sedang aktif di layar dosen.';
    }

    return 'Tekan Mulai, lalu arahkan kamera ke QR yang sedang aktif.';
});

const qrbox = (viewfinderWidth, viewfinderHeight) => {
    const edge = Math.min(viewfinderWidth, viewfinderHeight);
    const size = Math.floor(edge * 0.72);

    return {
        width: Math.max(220, Math.min(size, 360)),
        height: Math.max(220, Math.min(size, 360)),
    };
};

const ensureScanner = () => {
    if (!scanner.value) {
        scanner.value = new Html5Qrcode(scannerId, {
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
            useBarCodeDetectorIfSupported: true,
            verbose: false,
        });
    }

    return scanner.value;
};

const startWithCamera = async (cameraConfig) => {
    await ensureScanner().start(
        cameraConfig,
        {
            fps: 10,
            qrbox,
            aspectRatio: 1,
            disableFlip: false,
        },
        onScanSuccess,
        () => {},
    );
};

const startScanner = async () => {
    if (!canScan.value || scanning.value) {
        return;
    }

    error.value = '';
    status.value = '';

    if (!secureCameraContext.value) {
        error.value = 'Akses kamera membutuhkan HTTPS atau localhost.';
        return;
    }

    if (!navigator.mediaDevices?.getUserMedia) {
        error.value = 'Kamera tidak tersedia di browser ini.';
        return;
    }

    try {
        await startWithCamera({ facingMode: 'environment' });
        scanning.value = true;
    } catch (cameraError) {
        try {
            const cameras = await Html5Qrcode.getCameras();
            const backCamera = cameras.find((camera) => /back|rear|environment/i.test(camera.label)) ?? cameras.at(-1);

            if (!backCamera) {
                throw cameraError;
            }

            await startWithCamera(backCamera.id);
            scanning.value = true;
        } catch (fallbackError) {
            if (fallbackError?.name === 'NotAllowedError') {
                error.value = 'Izin kamera ditolak.';
            } else if (fallbackError?.name === 'NotFoundError') {
                error.value = 'Kamera tidak tersedia.';
            } else {
                error.value = 'Kamera tidak tersedia atau tidak dapat dibuka.';
            }
        }
    }
};

const stopScanner = async () => {
    if (!scanner.value || !scanning.value) {
        return;
    }

    try {
        await scanner.value.stop();
        scanner.value.clear();
    } finally {
        scanning.value = false;
    }
};

const onScanSuccess = async (decodedText) => {
    if (processing.value) {
        return;
    }

    processing.value = true;
    status.value = 'QR terbaca. Memvalidasi...';
    error.value = '';
    await stopScanner();

    try {
        const response = await axios.post('/mahasiswa/absen/verifikasi-qr', {
            qr_payload: decodedText,
        });

        status.value = response.data.message ?? 'QR valid.';
        router.visit(response.data.next_url);
    } catch (verifyError) {
        const validationMessage = verifyError.response?.data?.errors?.qr_payload?.[0];
        const fallbackMessage = verifyError.request
            ? 'Koneksi terputus saat validasi QR.'
            : 'QR tidak dapat diverifikasi.';

        const message = validationMessage ?? verifyError.response?.data?.message ?? fallbackMessage;

        status.value = '';
        error.value = message.includes('kedaluwarsa') || message.includes('tidak valid')
            ? 'QR tidak valid atau sudah kedaluwarsa. Gunakan QR terbaru dari dosen.'
            : message;
        processing.value = false;
    }
};

onBeforeUnmount(() => {
    stopScanner();
});
</script>

<template>
    <Head title="Scan QR" />

    <MahasiswaLayout>
        <div class="mobile-app-surface space-y-5">
            <header class="rounded-lg bg-zinc-950 p-5 text-white shadow-apple-soft">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-white/55">Presensi</p>
                        <h1 class="mt-2 text-4xl font-semibold tracking-normal">Scan QR</h1>
                        <p class="mt-3 text-sm text-white/65">
                            {{ activeSessions.length }} sesi tersedia hari ini.
                        </p>
                    </div>
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-white/12">
                        <ScanLine class="h-6 w-6" />
                    </span>
                </div>
                <Link
                    v-if="!faceRegistered"
                    class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-center text-sm font-semibold text-zinc-950 shadow-sm transition hover:bg-white/90"
                    href="/mahasiswa/profil"
                >
                    <UserCircle class="h-4 w-4" />
                    Daftarkan wajah
                </Link>
            </header>

            <section v-if="!faceRegistered" class="apple-subcard border-amber-100 bg-amber-50/90 p-5 text-amber-900">
                <h2 class="font-semibold">Wajah belum terdaftar</h2>
                <p class="mt-1 text-sm">
                    Presensi QR aktif setelah profil wajah tersimpan.
                </p>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.2fr_1fr]">
                <article class="apple-glass p-3">
                    <div class="camera-stage">
                        <div class="border-b border-white/10 px-4 py-3">
                            <p class="text-xs font-semibold text-white/50">Scanner kamera</p>
                            <p class="mt-1 text-sm font-semibold text-white">Arahkan ke QR dosen</p>
                        </div>
                        <div
                            :id="scannerId"
                            class="qr-scanner-frame aspect-square w-full"
                            :aria-busy="processing || scanning"
                        />
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                class="btn-primary"
                                :disabled="!canScan || scanning"
                                @click="startScanner"
                            >
                                <Camera class="h-4 w-4" />
                                Mulai
                            </button>
                            <button
                                type="button"
                                class="btn-secondary"
                                :disabled="!scanning"
                                @click="stopScanner"
                            >
                                <CameraOff class="h-4 w-4" />
                                Stop
                            </button>
                    </div>

                    <div class="mt-4 space-y-2">
                        <p v-if="status" class="flex items-center gap-2 text-sm font-semibold text-emerald-700">
                            <CheckCircle2 class="h-4 w-4" />
                            {{ status }}
                        </p>
                        <div
                            v-if="error"
                            class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800"
                        >
                            <div class="flex gap-2">
                                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                                <div>
                                    <p>{{ error }}</p>
                                    <p class="mt-1 text-rose-700">{{ scannerHelperText }}</p>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-zinc-500">
                            {{ scannerHelperText }}
                        </p>
                    </div>
                </article>

                <div class="space-y-6">
                    <article>
                        <h2 class="ios-section-title">Sesi aktif</h2>
                        <div v-if="activeSessions.length" class="ios-list mt-3">
                            <div
                                v-for="session in activeSessions"
                                :key="session.id"
                                class="ios-list-row bg-emerald-50/70"
                            >
                                <div>
                                    <p class="font-semibold text-emerald-950">{{ session.mata_kuliah }}</p>
                                    <p class="mt-1 text-sm text-emerald-800">
                                        {{ session.kelas?.nama_kelas }} &middot; {{ session.ruangan }}
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-emerald-700">
                                        {{ session.dosen }}
                                    </p>
                                </div>
                                <p class="text-right text-sm font-semibold text-emerald-900">
                                    {{ session.jam_mulai }}<br>
                                    <span class="font-normal text-emerald-700">{{ session.jam_selesai }}</span>
                                </p>
                            </div>
                        </div>
                        <div v-else class="empty-state mt-4">
                            Belum ada sesi yang bisa diikuti.
                        </div>
                    </article>

                    <article>
                        <h2 class="ios-section-title">Presensi hari ini</h2>
                        <div v-if="attendanceToday.length" class="ios-list mt-3">
                            <div
                                v-for="attendance in attendanceToday"
                                :key="attendance.id"
                                class="ios-list-row"
                            >
                                <div>
                                    <p class="font-semibold text-zinc-950">{{ attendance.mata_kuliah }}</p>
                                    <p class="mt-1 text-sm capitalize text-zinc-600">
                                        {{ attendance.status.replace('_', ' ') }}
                                    </p>
                                </div>
                                <p class="text-sm text-apple-tertiary">{{ attendance.timestamp }}</p>
                            </div>
                        </div>
                        <div v-else class="empty-state mt-4">
                            Belum ada presensi tercatat.
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </MahasiswaLayout>
</template>
