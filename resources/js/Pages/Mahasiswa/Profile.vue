<script setup>
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Camera, ScanFace, Save, Square } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

let faceapi = null;
let detectionIntervalId = null;
let detectionModelLoadingPromise = null;
let captureModelsLoadingPromise = null;

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    faceConfig: {
        type: Object,
        required: true,
    },
});

const videoRef = ref(null);
const canvasRef = ref(null);
const stream = ref(null);
const detectionModelLoaded = ref(false);
const captureModelsLoaded = ref(false);
const cameraActive = ref(false);
const statusMessage = ref('');
const previewImage = ref(null);
const samples = ref([]);
const faceCount = ref(null);
const detectingFace = ref(false);
const captureProcessing = ref(false);
const faceQuality = ref({
    canCapture: false,
    message: '',
    status: 'idle',
});

const form = useForm({
    image_base64: '',
    face_descriptor: [],
});

const registrationStatus = computed(() => props.profile.wajah_terdaftar ? 'Terdaftar' : 'Belum terdaftar');
const canSave = computed(() => form.image_base64 && form.face_descriptor.length === props.faceConfig.descriptorLength);
const hasCapturedFace = computed(() => Boolean(previewImage.value && canSave.value));
const secureCameraContext = computed(() => window.isSecureContext || ['localhost', '127.0.0.1'].includes(window.location.hostname));
const canCapture = computed(() => cameraActive.value && detectionModelLoaded.value && faceQuality.value.canCapture && !captureProcessing.value);
const qualityReady = computed(() => faceQuality.value.status === 'ready');

const captureButtonLabel = computed(() => {
    if (captureProcessing.value) {
        return 'Mengambil wajah...';
    }

    if (!cameraActive.value) {
        return hasCapturedFace.value ? 'Foto siap disimpan' : 'Nyalakan kamera dulu';
    }

    if (detectingFace.value && faceCount.value === null) {
        return 'Mencari wajah...';
    }

    if (hasCapturedFace.value && faceCount.value !== 1) {
        return 'Foto siap disimpan';
    }

    if (faceQuality.value.message && !faceQuality.value.canCapture) {
        return faceQuality.value.status === 'searching' ? 'Mencari wajah...' : faceQuality.value.message;
    }

    if (faceCount.value === 0) {
        return 'Wajah belum ditemukan';
    }

    if (faceCount.value > 1) {
        return 'Terdeteksi lebih dari satu wajah';
    }

    if (faceQuality.value.canCapture) {
        return hasCapturedFace.value ? 'Ambil ulang foto' : 'Ambil foto wajah';
    }

    return 'Mencari wajah...';
});

const cameraStatusText = computed(() => {
    if (statusMessage.value) {
        return statusMessage.value;
    }

    if (hasCapturedFace.value) {
        return 'Foto wajah sudah siap. Tekan Simpan wajah untuk menyelesaikan pendaftaran.';
    }

    if (!cameraActive.value) {
        return 'Nyalakan kamera, posisikan wajah di tengah frame, lalu ambil wajah saat tombol sudah aktif.';
    }

    if (faceQuality.value.message) {
        return faceQuality.value.message;
    }

    return 'Mencari wajah di kamera.';
});

const getFaceApi = async () => {
    if (!faceapi) {
        faceapi = await import('face-api.js');
    }

    return faceapi;
};

const createDetectorOptions = (api, inputSize = 224) => new api.TinyFaceDetectorOptions({
    inputSize,
    scoreThreshold: 0.5,
});

const loadDetectionModel = async () => {
    if (detectionModelLoaded.value) {
        return;
    }

    if (detectionModelLoadingPromise) {
        await detectionModelLoadingPromise;
        return;
    }

    statusMessage.value = 'Memuat model deteksi wajah.';
    detectionModelLoadingPromise = (async () => {
        const api = await getFaceApi();
        await api.nets.tinyFaceDetector.loadFromUri(props.faceConfig.modelPath);
        detectionModelLoaded.value = true;
    })();

    try {
        await detectionModelLoadingPromise;
    } finally {
        if (!detectionModelLoaded.value) {
            detectionModelLoadingPromise = null;
        }
    }
};

const loadCaptureModels = async () => {
    if (captureModelsLoaded.value) {
        return;
    }

    if (captureModelsLoadingPromise) {
        await captureModelsLoadingPromise;
        return;
    }

    captureModelsLoadingPromise = (async () => {
        const api = await getFaceApi();

        await Promise.all([
            api.nets.faceLandmark68TinyNet.loadFromUri(props.faceConfig.modelPath),
            api.nets.faceRecognitionNet.loadFromUri(props.faceConfig.modelPath),
        ]);

        captureModelsLoaded.value = true;
    })();

    try {
        await captureModelsLoadingPromise;
    } finally {
        if (!captureModelsLoaded.value) {
            captureModelsLoadingPromise = null;
        }
    }
};

const warmCaptureModels = () => {
    const warm = () => loadCaptureModels().catch(() => {});

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(warm, { timeout: 2500 });
        return;
    }

    window.setTimeout(warm, 800);
};

const startCamera = async () => {
    if (!secureCameraContext.value) {
        statusMessage.value = 'Akses kamera membutuhkan HTTPS atau localhost.';
        return;
    }

    const detectionModelPromise = loadDetectionModel();
    detectionModelPromise.catch(() => {});
    statusMessage.value = 'Mengaktifkan kamera.';

    try {
        if (!navigator.mediaDevices?.getUserMedia) {
            statusMessage.value = 'Kamera tidak tersedia di browser ini.';
            return;
        }

        stream.value = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'user',
                width: { ideal: 480 },
                height: { ideal: 480 },
                aspectRatio: { ideal: 1 },
                frameRate: { ideal: 15, max: 24 },
            },
            audio: false,
        });

        videoRef.value.srcObject = stream.value;
        await videoRef.value.play();
        cameraActive.value = true;
        statusMessage.value = detectionModelLoaded.value ? '' : 'Kamera aktif. Memuat model deteksi wajah.';
    } catch (error) {
        if (error?.name === 'NotAllowedError') {
            statusMessage.value = 'Izin kamera ditolak.';
        } else if (error?.name === 'NotFoundError') {
            statusMessage.value = 'Kamera tidak tersedia.';
        } else {
            statusMessage.value = 'Kamera gagal dibuka.';
        }

        return;
    }

    try {
        await detectionModelPromise;

        if (!cameraActive.value) {
            return;
        }

        statusMessage.value = '';
        startFaceDetection();
        warmCaptureModels();
    } catch (error) {
        statusMessage.value = 'Model deteksi wajah gagal dimuat. Coba nyalakan ulang kamera.';
    }
};

const stopCamera = () => {
    stopFaceDetection();
    videoRef.value?.pause();
    if (videoRef.value) {
        videoRef.value.srcObject = null;
    }
    stream.value?.getTracks().forEach((track) => track.stop());
    stream.value = null;
    cameraActive.value = false;
    faceCount.value = null;
    detectingFace.value = false;
    faceQuality.value = {
        canCapture: false,
        message: '',
        status: 'idle',
    };
};

const faceBox = (detection) => detection?.detection?.box ?? detection?.box;

const qualityResult = (status, message, canCapture = false) => ({
    canCapture,
    message,
    status,
});

const sampleFaceQuality = (video, box) => {
    const canvas = document.createElement('canvas');
    const size = 72;
    const paddingX = box.width * 0.12;
    const paddingY = box.height * 0.12;
    const sourceX = Math.max(0, box.x - paddingX);
    const sourceY = Math.max(0, box.y - paddingY);
    const sourceWidth = Math.min(video.videoWidth - sourceX, box.width + paddingX * 2);
    const sourceHeight = Math.min(video.videoHeight - sourceY, box.height + paddingY * 2);

    canvas.width = size;
    canvas.height = size;
    const context = canvas.getContext('2d', { willReadFrequently: true });
    context.drawImage(video, sourceX, sourceY, sourceWidth, sourceHeight, 0, 0, size, size);

    const data = context.getImageData(0, 0, size, size).data;
    const grayscale = new Array(size * size);
    let luminanceTotal = 0;

    for (let index = 0, pixel = 0; index < data.length; index += 4, pixel += 1) {
        const luminance = (0.299 * data[index]) + (0.587 * data[index + 1]) + (0.114 * data[index + 2]);
        grayscale[pixel] = luminance;
        luminanceTotal += luminance;
    }

    const averageLuminance = luminanceTotal / grayscale.length;
    let sharpnessTotal = 0;
    let sharpnessCount = 0;

    for (let y = 1; y < size - 1; y += 1) {
        for (let x = 1; x < size - 1; x += 1) {
            const center = grayscale[(y * size) + x] * 4;
            const contrast = Math.abs(
                center
                - grayscale[(y * size) + x - 1]
                - grayscale[(y * size) + x + 1]
                - grayscale[((y - 1) * size) + x]
                - grayscale[((y + 1) * size) + x],
            );

            sharpnessTotal += contrast;
            sharpnessCount += 1;
        }
    }

    return {
        brightness: averageLuminance,
        sharpness: sharpnessTotal / sharpnessCount,
    };
};

const evaluateFaceQuality = (detections) => {
    const video = videoRef.value;
    faceCount.value = detections.length;

    if (!video || detections.length === 0) {
        return qualityResult('no_face', 'Wajah belum ditemukan. Posisikan wajah di tengah kamera.');
    }

    if (detections.length > 1) {
        return qualityResult('multiple_faces', 'Terdeteksi lebih dari satu wajah. Pastikan hanya wajah kamu yang terlihat.');
    }

    const box = faceBox(detections[0]);

    if (!box || !video.videoWidth || !video.videoHeight) {
        return qualityResult('searching', 'Mencari wajah di kamera.');
    }

    const widthRatio = box.width / video.videoWidth;
    const heightRatio = box.height / video.videoHeight;

    if (widthRatio < 0.18 || heightRatio < 0.24) {
        return qualityResult('too_small', 'Wajah terlalu jauh. Dekatkan wajah ke kamera.');
    }

    const faceCenterX = box.x + (box.width / 2);
    const faceCenterY = box.y + (box.height / 2);
    const centerOffsetX = Math.abs(faceCenterX - (video.videoWidth / 2)) / video.videoWidth;
    const centerOffsetY = Math.abs(faceCenterY - (video.videoHeight / 2)) / video.videoHeight;

    if (centerOffsetX > 0.22 || centerOffsetY > 0.24) {
        return qualityResult('off_center', 'Posisikan wajah di tengah frame.');
    }

    const quality = sampleFaceQuality(video, box);

    if (quality.brightness < 58) {
        return qualityResult('too_dark', 'Wajah terlalu gelap. Cari tempat dengan cahaya lebih terang.');
    }

    if (quality.sharpness < 4.5) {
        return qualityResult('blurry', 'Gambar kurang jelas. Tahan kamera tetap stabil.');
    }

    return qualityResult('ready', 'Wajah terlihat jelas. Kamu bisa mengambil foto wajah.', true);
};

const detectFacePresence = async () => {
    if (!cameraActive.value || !videoRef.value || !detectionModelLoaded.value || detectingFace.value || captureProcessing.value || document.visibilityState === 'hidden') {
        return;
    }

    detectingFace.value = true;

    try {
        const api = await getFaceApi();
        const detections = await api.detectAllFaces(
            videoRef.value,
            createDetectorOptions(api),
        );

        faceQuality.value = evaluateFaceQuality(detections);
    } catch {
        statusMessage.value = 'Deteksi wajah gagal. Coba nyalakan ulang kamera.';
    } finally {
        detectingFace.value = false;
    }
};

const startFaceDetection = () => {
    stopFaceDetection();
    faceCount.value = null;
    faceQuality.value = qualityResult('searching', 'Mencari wajah di kamera.');
    detectFacePresence();
    detectionIntervalId = window.setInterval(detectFacePresence, 1400);
};

const stopFaceDetection = () => {
    if (detectionIntervalId) {
        window.clearInterval(detectionIntervalId);
        detectionIntervalId = null;
    }
};

const captureFace = async () => {
    if (!videoRef.value || !detectionModelLoaded.value) {
        statusMessage.value = 'Model deteksi wajah belum siap.';
        return;
    }

    if (!canCapture.value) {
        return;
    }

    captureProcessing.value = true;
    statusMessage.value = captureModelsLoaded.value ? 'Mendeteksi wajah.' : 'Menyiapkan model pengenal wajah.';

    try {
        await loadCaptureModels();
        statusMessage.value = 'Mendeteksi wajah.';

        const api = await getFaceApi();
        const detections = await api
            .detectAllFaces(videoRef.value, createDetectorOptions(api, 320))
            .withFaceLandmarks(true)
            .withFaceDescriptors();

        faceCount.value = detections.length;
        const quality = evaluateFaceQuality(detections);
        faceQuality.value = quality;

        if (!quality.canCapture) {
            statusMessage.value = quality.message;
            return;
        }

        if (detections.length === 0) {
            statusMessage.value = 'Wajah tidak terdeteksi. Coba dekatkan wajah ke kamera.';
            return;
        }

        if (detections.length > 1) {
            statusMessage.value = 'Terdeteksi lebih dari satu wajah. Pastikan hanya wajah kamu yang terlihat.';
            return;
        }

        const canvas = canvasRef.value;
        const sourceSize = Math.min(videoRef.value.videoWidth, videoRef.value.videoHeight);
        const sourceX = (videoRef.value.videoWidth - sourceSize) / 2;
        const sourceY = (videoRef.value.videoHeight - sourceSize) / 2;
        const targetSize = Math.min(sourceSize, 640);
        canvas.width = targetSize;
        canvas.height = targetSize;
        canvas.getContext('2d').drawImage(
            videoRef.value,
            sourceX,
            sourceY,
            sourceSize,
            sourceSize,
            0,
            0,
            targetSize,
            targetSize,
        );

        const image = canvas.toDataURL('image/jpeg', 0.82);
        const descriptor = Array.from(detections[0].descriptor);

        samples.value = [...samples.value, descriptor].slice(-3);
        form.image_base64 = image;
        form.face_descriptor = averageDescriptor(samples.value);
        previewImage.value = image;
        statusMessage.value = 'Foto wajah sudah siap. Tekan Simpan wajah untuk menyelesaikan pendaftaran.';
    } catch {
        statusMessage.value = 'Model pengenal wajah gagal dimuat atau wajah gagal diproses. Coba nyalakan ulang kamera.';
    } finally {
        captureProcessing.value = false;
    }
};

const averageDescriptor = (items) => {
    const length = props.faceConfig.descriptorLength;

    return Array.from({ length }, (_, index) => {
        const total = items.reduce((sum, item) => sum + Number(item[index] ?? 0), 0);
        return total / items.length;
    });
};

const submit = () => {
    form.post('/mahasiswa/profil/wajah', {
        preserveScroll: true,
        onSuccess: () => {
            statusMessage.value = 'Data wajah berhasil disimpan.';
        },
    });
};

onBeforeUnmount(() => {
    stopCamera();
});
</script>

<template>
    <Head title="Profil Mahasiswa" />

    <MahasiswaLayout>
        <div class="mobile-app-surface space-y-5">
            <header class="content-hero">
                <p class="eyebrow">Profil</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">{{ profile.name }}</h1>
                <p class="mt-2 text-sm text-zinc-600">{{ profile.nim }} &middot; {{ profile.prodi }} &middot; Angkatan {{ profile.angkatan }}</p>
            </header>

            <section class="ios-list">
                <div class="ios-list-row">
                    <p class="text-sm font-medium text-apple-secondary">Email</p>
                    <p class="max-w-[62%] truncate text-right text-sm font-semibold text-zinc-950">{{ profile.email }}</p>
                </div>
                <div class="ios-list-row">
                    <p class="text-sm font-medium text-apple-secondary">Status wajah</p>
                    <p class="text-sm font-semibold" :class="profile.wajah_terdaftar ? 'text-emerald-700' : 'text-amber-700'">
                        {{ registrationStatus }}
                    </p>
                </div>
                <div class="ios-list-row">
                    <p class="text-sm font-medium text-apple-secondary">Terakhir diperbarui</p>
                    <p class="text-right text-sm font-semibold text-zinc-950">{{ profile.face_registered_at ?? '-' }}</p>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_0.9fr]">
                <article class="apple-card p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-zinc-950">Daftarkan wajah</h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                Gunakan pencahayaan cukup dan pastikan hanya satu wajah terlihat.
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                class="btn-secondary px-3 py-2"
                                type="button"
                                @click="cameraActive ? stopCamera() : startCamera()"
                            >
                                <Square v-if="cameraActive" class="h-4 w-4" />
                                <Camera v-else class="h-4 w-4" />
                                {{ cameraActive ? 'Matikan kamera' : 'Nyalakan kamera' }}
                            </button>
                            <button
                                class="btn-dark px-3 py-2"
                                type="button"
                                :disabled="!canCapture"
                                @click="captureFace"
                            >
                                <ScanFace class="h-4 w-4" />
                                {{ captureButtonLabel }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-lg bg-zinc-950">
                        <video ref="videoRef" class="aspect-square w-full object-cover" autoplay muted playsinline />
                        <canvas ref="canvasRef" class="hidden" />
                    </div>

                    <div
                        class="mt-3 rounded-md border px-4 py-3 text-sm font-medium"
                        :class="qualityReady ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-zinc-200 bg-zinc-50 text-zinc-700'"
                    >
                        {{ cameraStatusText }}
                    </div>
                    <p v-if="form.errors.image_base64" class="mt-2 text-sm text-red-600">{{ form.errors.image_base64 }}</p>
                    <p v-if="form.errors.face_descriptor" class="mt-2 text-sm text-red-600">{{ form.errors.face_descriptor }}</p>
                </article>

                <article class="apple-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-zinc-950">Preview</h2>
                            <p class="mt-1 text-sm text-zinc-500">
                                Pastikan foto wajah terlihat jelas sebelum disimpan.
                            </p>
                        </div>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="hasCapturedFace ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600'"
                        >
                            {{ hasCapturedFace ? 'Siap disimpan' : 'Belum ada foto' }}
                        </span>
                    </div>
                    <div class="mt-5 overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100">
                        <img v-if="previewImage" :src="previewImage" alt="Preview wajah" class="aspect-square w-full object-cover">
                        <div v-else class="flex aspect-square items-center justify-center text-sm text-zinc-500">
                            Belum ada preview.
                        </div>
                    </div>
                    <p class="mt-3 text-sm font-medium" :class="hasCapturedFace ? 'text-emerald-700' : 'text-zinc-500'">
                        {{ hasCapturedFace ? 'Foto sudah siap. Simpan untuk mengaktifkan presensi wajah.' : 'Ambil foto wajah terlebih dahulu.' }}
                    </p>

                    <button
                        class="btn-primary mt-5 w-full"
                        type="button"
                        :disabled="!canSave || form.processing"
                        @click="submit"
                    >
                        <Save class="h-4 w-4" />
                        Simpan wajah
                    </button>
                </article>
            </section>
        </div>
    </MahasiswaLayout>
</template>
