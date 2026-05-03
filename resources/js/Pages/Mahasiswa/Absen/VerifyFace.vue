<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import axios from 'axios';
import { computed, onBeforeUnmount, ref } from 'vue';
import { Camera, CheckCircle2, RotateCcw, ScanFace, Square } from 'lucide-vue-next';

let faceapi = null;

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
    verificationExpiresAt: {
        type: String,
        required: true,
    },
    attemptsRemaining: {
        type: Number,
        required: true,
    },
    faceConfig: {
        type: Object,
        required: true,
    },
});

const videoRef = ref(null);
const stream = ref(null);
const modelsLoaded = ref(false);
const cameraActive = ref(false);
const processing = ref(false);
const statusMessage = ref('');
const errorMessage = ref('');
const registeredDescriptor = ref(null);
const attemptsLeft = ref(props.attemptsRemaining);
const lastDistance = ref(null);

const canVerify = computed(() => cameraActive.value && registeredDescriptor.value && !processing.value && attemptsLeft.value > 0);
const secureCameraContext = computed(() => window.isSecureContext || ['localhost', '127.0.0.1'].includes(window.location.hostname));

const getFaceApi = async () => {
    if (!faceapi) {
        faceapi = await import('face-api.js');
    }

    return faceapi;
};

const loadModels = async () => {
    if (modelsLoaded.value) {
        return;
    }

    statusMessage.value = 'Memuat model wajah.';
    const api = await getFaceApi();

    await Promise.all([
        api.nets.ssdMobilenetv1.loadFromUri(props.faceConfig.modelPath),
        api.nets.faceLandmark68Net.loadFromUri(props.faceConfig.modelPath),
        api.nets.faceRecognitionNet.loadFromUri(props.faceConfig.modelPath),
    ]);

    modelsLoaded.value = true;
};

const loadRegisteredDescriptor = async () => {
    if (registeredDescriptor.value) {
        return;
    }

    const response = await axios.get('/mahasiswa/face-descriptor');
    registeredDescriptor.value = response.data.descriptor.map(Number);
};

const startCamera = async () => {
    errorMessage.value = '';

    if (!secureCameraContext.value) {
        errorMessage.value = 'Akses kamera membutuhkan HTTPS atau localhost.';
        return;
    }

    try {
        await Promise.all([loadModels(), loadRegisteredDescriptor()]);

        if (!navigator.mediaDevices?.getUserMedia) {
            errorMessage.value = 'Kamera tidak tersedia di browser ini.';
            return;
        }

        stream.value = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 480 },
            },
            audio: false,
        });

        videoRef.value.srcObject = stream.value;
        await videoRef.value.play();
        cameraActive.value = true;
        statusMessage.value = 'Kamera aktif.';
    } catch (error) {
        if (!error.response && error.request) {
            errorMessage.value = 'Koneksi terputus saat memuat data wajah.';
        } else if (error?.name === 'NotAllowedError') {
            errorMessage.value = 'Izin kamera ditolak.';
        } else if (error?.name === 'NotFoundError') {
            errorMessage.value = 'Kamera tidak tersedia.';
        } else {
            errorMessage.value = 'Kamera, model wajah, atau descriptor tersimpan gagal dimuat.';
        }
    }
};

const stopCamera = () => {
    stream.value?.getTracks().forEach((track) => track.stop());
    stream.value = null;
    cameraActive.value = false;
};

const verifyFace = async () => {
    if (!canVerify.value) {
        return;
    }

    processing.value = true;
    errorMessage.value = '';
    statusMessage.value = 'Mendeteksi wajah.';

    try {
        const api = await getFaceApi();
        const detections = await api
            .detectAllFaces(videoRef.value, new api.SsdMobilenetv1Options({ minConfidence: 0.5 }))
            .withFaceLandmarks()
            .withFaceDescriptors();

        if (detections.length === 0) {
            errorMessage.value = 'Wajah tidak terdeteksi.';
            return;
        }

        if (detections.length > 1) {
            errorMessage.value = 'Terdeteksi lebih dari satu wajah.';
            return;
        }

        const descriptor = Array.from(detections[0].descriptor);
        const distance = api.euclideanDistance(registeredDescriptor.value, descriptor);
        lastDistance.value = Number(distance.toFixed(4));
        statusMessage.value = `Jarak wajah ${lastDistance.value}.`;

        const response = await axios.post('/mahasiswa/absen/verifikasi-wajah', {
            face_descriptor: descriptor,
            client_distance: distance,
        });

        await stopCamera();
        router.visit(response.data.next_url);
    } catch (error) {
        const response = error.response;
        const validationMessage = response?.data?.errors?.face_descriptor?.[0];
        errorMessage.value = validationMessage
            ?? response?.data?.message
            ?? (error.request ? 'Koneksi terputus saat verifikasi wajah.' : 'Verifikasi wajah gagal.');

        if (response?.data?.distance !== undefined) {
            lastDistance.value = response.data.distance;
        }

        if (response?.data?.attempts_remaining !== undefined) {
            attemptsLeft.value = response.data.attempts_remaining;
        }

        if (response?.data?.next_url && attemptsLeft.value <= 0) {
            await stopCamera();
            router.visit(response.data.next_url);
        }
    } finally {
        processing.value = false;
    }
};

onBeforeUnmount(() => {
    stopCamera();
});
</script>

<template>
    <Head title="Verifikasi Wajah" />

    <MahasiswaLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Verifikasi Wajah</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">{{ session.mata_kuliah }}</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        QR valid sampai {{ verificationExpiresAt }}.
                    </p>
                </div>
                <Link
                    class="inline-flex items-center justify-center gap-2 rounded-md border border-zinc-300 px-4 py-2.5 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50"
                    href="/mahasiswa/absen"
                >
                    <RotateCcw class="h-4 w-4" />
                    Scan ulang
                </Link>
            </header>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_0.9fr]">
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-zinc-950">Kamera depan</h2>
                            <p class="mt-1 text-sm text-zinc-600">
                                Sisa percobaan {{ attemptsLeft }} dari {{ faceConfig.maxAttempts }}.
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50"
                                @click="cameraActive ? stopCamera() : startCamera()"
                            >
                                <Square v-if="cameraActive" class="h-4 w-4" />
                                <Camera v-else class="h-4 w-4" />
                                {{ cameraActive ? 'Stop' : 'Kamera' }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-zinc-300 disabled:text-zinc-600"
                                :disabled="!canVerify"
                                @click="verifyFace"
                            >
                                <ScanFace class="h-4 w-4" />
                                {{ processing ? 'Memeriksa' : 'Verifikasi' }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-lg bg-zinc-950">
                        <video ref="videoRef" class="aspect-video w-full object-cover" muted playsinline />
                    </div>

                    <div class="mt-4 space-y-2 text-sm">
                        <p class="font-medium text-zinc-600">{{ statusMessage || 'Aktifkan kamera untuk mulai verifikasi.' }}</p>
                        <p v-if="lastDistance !== null" class="font-semibold" :class="lastDistance <= faceConfig.threshold ? 'text-emerald-700' : 'text-amber-700'">
                            Distance: {{ lastDistance }} / threshold {{ faceConfig.threshold }}
                        </p>
                        <p v-if="errorMessage" class="font-semibold text-rose-700">{{ errorMessage }}</p>
                    </div>
                </article>

                <aside class="space-y-6">
                    <section class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-emerald-950">
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="mt-0.5 h-5 w-5 text-emerald-700" />
                            <div>
                                <h2 class="font-semibold">QR sudah valid</h2>
                                <p class="mt-1 text-sm text-emerald-800">
                                    Langkah terakhir adalah mencocokkan wajah dengan data profil.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                        <h2 class="text-base font-semibold text-zinc-950">Detail sesi</h2>
                        <dl class="mt-4 space-y-4 text-sm">
                            <div>
                                <dt class="font-medium text-zinc-500">Kelas</dt>
                                <dd class="mt-1 font-semibold text-zinc-950">{{ session.kelas?.nama_kelas }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500">Dosen</dt>
                                <dd class="mt-1 font-semibold text-zinc-950">{{ session.dosen }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500">Jam</dt>
                                <dd class="mt-1 font-semibold text-zinc-950">{{ session.jam_mulai }}-{{ session.jam_selesai }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500">Ruangan</dt>
                                <dd class="mt-1 font-semibold text-zinc-950">{{ session.ruangan }}</dd>
                            </div>
                        </dl>
                    </section>
                </aside>
            </section>
        </div>
    </MahasiswaLayout>
</template>
