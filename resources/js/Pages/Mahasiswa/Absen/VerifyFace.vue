<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import axios from 'axios';
import { computed, onBeforeUnmount, ref } from 'vue';
import { Camera, CheckCircle2, RotateCcw, ScanFace, Square } from 'lucide-vue-next';

let faceapi = null;
let detectionModelsLoadingPromise = null;
let recognitionModelLoadingPromise = null;
let descriptorLoadingPromise = null;

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
    livenessChallenge: {
        type: Object,
        required: true,
    },
});

const videoRef = ref(null);
const stream = ref(null);
const detectionModelsLoaded = ref(false);
const recognitionModelLoaded = ref(false);
const cameraActive = ref(false);
const processing = ref(false);
const statusMessage = ref('');
const errorMessage = ref('');
const registeredDescriptor = ref(null);
const attemptsLeft = ref(props.attemptsRemaining);
const lastDistance = ref(null);
const currentLivenessIndex = ref(0);
const livenessCompletedAt = ref(null);
const livenessChecking = ref(false);
const livenessMessage = ref('');
const livenessDetail = ref('');
const livenessBaseline = ref(null);
const neutralSamples = ref([]);
const activeGesture = ref(null);
let livenessTimeoutId = null;
let livenessExpiryTimeoutId = null;

const secureCameraContext = computed(() => window.isSecureContext || ['localhost', '127.0.0.1'].includes(window.location.hostname));
const LIVENESS_CHECK_INTERVAL = 450;
const LIVENESS_CALIBRATION_SAMPLES = 3;
const TURN_DELTA_THRESHOLD = 0.1;
const livenessLabels = {
    mouth_open: 'Buka mulut',
    turn_left: 'Menoleh ke satu sisi',
};
const livenessSteps = computed(() => (props.livenessChallenge.steps ?? []).map((step) => ({
    key: step,
    label: livenessLabels[step] ?? step,
})));
const currentLivenessStep = computed(() => livenessSteps.value[currentLivenessIndex.value] ?? null);
const livenessPassed = computed(() => Boolean(livenessCompletedAt.value) && currentLivenessIndex.value >= livenessSteps.value.length);
const canVerify = computed(() => cameraActive.value && registeredDescriptor.value && livenessPassed.value && !processing.value && attemptsLeft.value > 0);

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

const loadDetectionModels = async () => {
    if (detectionModelsLoaded.value) {
        return;
    }

    if (detectionModelsLoadingPromise) {
        await detectionModelsLoadingPromise;
        return;
    }

    statusMessage.value = 'Memuat model deteksi wajah.';
    detectionModelsLoadingPromise = (async () => {
        const api = await getFaceApi();

        await Promise.all([
            api.nets.tinyFaceDetector.loadFromUri(props.faceConfig.modelPath),
            api.nets.faceLandmark68Net.loadFromUri(props.faceConfig.modelPath),
        ]);

        detectionModelsLoaded.value = true;
    })();

    try {
        await detectionModelsLoadingPromise;
    } finally {
        if (!detectionModelsLoaded.value) {
            detectionModelsLoadingPromise = null;
        }
    }
};

const loadRecognitionModel = async () => {
    if (recognitionModelLoaded.value) {
        return;
    }

    if (recognitionModelLoadingPromise) {
        await recognitionModelLoadingPromise;
        return;
    }

    recognitionModelLoadingPromise = (async () => {
        const api = await getFaceApi();
        await api.nets.faceRecognitionNet.loadFromUri(props.faceConfig.modelPath);
        recognitionModelLoaded.value = true;
    })();

    try {
        await recognitionModelLoadingPromise;
    } finally {
        if (!recognitionModelLoaded.value) {
            recognitionModelLoadingPromise = null;
        }
    }
};

const warmRecognitionModel = () => {
    const warm = () => loadRecognitionModel().catch(() => {});

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(warm, { timeout: 2500 });
        return;
    }

    window.setTimeout(warm, 800);
};

const loadRegisteredDescriptor = async () => {
    if (registeredDescriptor.value) {
        return;
    }

    if (descriptorLoadingPromise) {
        await descriptorLoadingPromise;
        return;
    }

    descriptorLoadingPromise = axios.get('/mahasiswa/face-descriptor')
        .then((response) => {
            registeredDescriptor.value = response.data.descriptor.map(Number);
        });

    try {
        await descriptorLoadingPromise;
    } finally {
        if (!registeredDescriptor.value) {
            descriptorLoadingPromise = null;
        }
    }
};

const resetLiveness = () => {
    clearLivenessLoop();
    clearLivenessExpiry();
    currentLivenessIndex.value = 0;
    livenessCompletedAt.value = null;
    livenessChecking.value = false;
    livenessDetail.value = '';
    livenessBaseline.value = null;
    neutralSamples.value = [];
    activeGesture.value = null;
    livenessMessage.value = livenessSteps.value.length
        ? 'Hadapkan wajah lurus ke kamera untuk kalibrasi.'
        : '';
};

const clearLivenessLoop = () => {
    if (livenessTimeoutId) {
        window.clearTimeout(livenessTimeoutId);
        livenessTimeoutId = null;
    }
};

const clearLivenessExpiry = () => {
    if (livenessExpiryTimeoutId) {
        window.clearTimeout(livenessExpiryTimeoutId);
        livenessExpiryTimeoutId = null;
    }
};

const scheduleLivenessCheck = () => {
    clearLivenessLoop();
    livenessTimeoutId = window.setTimeout(runLivenessCheck, LIVENESS_CHECK_INTERVAL);
};

const distance = (left, right) => Math.hypot(left.x - right.x, left.y - right.y);

const averagePoint = (points) => {
    const total = points.reduce((carry, point) => ({
        x: carry.x + point.x,
        y: carry.y + point.y,
    }), { x: 0, y: 0 });

    return {
        x: total.x / points.length,
        y: total.y / points.length,
    };
};

const eyeAspectRatio = (eye) => {
    if (eye.length < 6) {
        return 1;
    }

    return (
        distance(eye[1], eye[5]) +
        distance(eye[2], eye[4])
    ) / (2 * distance(eye[0], eye[3]));
};

const headTurnOffset = (landmarks) => {
    const leftEyeCenter = averagePoint(landmarks.getLeftEye());
    const rightEyeCenter = averagePoint(landmarks.getRightEye());
    const nose = landmarks.getNose();
    const noseTip = nose[3] ?? nose[Math.floor(nose.length / 2)];
    const eyeCenter = averagePoint([leftEyeCenter, rightEyeCenter]);
    const eyeDistance = Math.max(distance(leftEyeCenter, rightEyeCenter), 1);

    return (noseTip.x - eyeCenter.x) / eyeDistance;
};

const mouthOpeningRatio = (mouth) => {
    if (mouth.length < 20) {
        return 0;
    }

    const width = Math.max(distance(mouth[12], mouth[16]), distance(mouth[0], mouth[6]), 1);

    return (
        distance(mouth[13], mouth[19]) +
        distance(mouth[14], mouth[18]) +
        distance(mouth[15], mouth[17])
    ) / (3 * width);
};

const readLivenessMetrics = (landmarks) => ({
    ear: (
        eyeAspectRatio(landmarks.getLeftEye()) +
        eyeAspectRatio(landmarks.getRightEye())
    ) / 2,
    mouth: mouthOpeningRatio(landmarks.getMouth()),
    turn: headTurnOffset(landmarks),
});

const averageMetrics = (samples) => samples.reduce((carry, sample) => ({
    ear: carry.ear + sample.ear / samples.length,
    mouth: carry.mouth + sample.mouth / samples.length,
    turn: carry.turn + sample.turn / samples.length,
}), { ear: 0, mouth: 0, turn: 0 });

const calibrateLiveness = (metrics) => {
    neutralSamples.value = [...neutralSamples.value, metrics].slice(-LIVENESS_CALIBRATION_SAMPLES);
    livenessDetail.value = `Kalibrasi ${neutralSamples.value.length}/${LIVENESS_CALIBRATION_SAMPLES}.`;

    if (neutralSamples.value.length < LIVENESS_CALIBRATION_SAMPLES) {
        livenessMessage.value = 'Hadapkan wajah lurus ke kamera.';
        return false;
    }

    livenessBaseline.value = averageMetrics(neutralSamples.value);
    livenessMessage.value = `Ikuti instruksi: ${currentLivenessStep.value?.label}.`;
    livenessDetail.value = '';

    return true;
};

const detectActiveGesture = (metrics) => {
    const baseline = livenessBaseline.value;
    const openMouthThreshold = Math.max(0.22, baseline.mouth * 2.2);
    const closedMouthThreshold = Math.max(0.12, baseline.mouth * 1.4);

    if (!activeGesture.value && metrics.mouth >= openMouthThreshold) {
        activeGesture.value = 'mouth';
        livenessMessage.value = 'Tutup mulut kembali.';
        return false;
    }

    if (activeGesture.value === 'mouth') {
        return metrics.mouth <= closedMouthThreshold;
    }

    livenessMessage.value = 'Buka mulut sebentar, lalu tutup kembali.';

    return false;
};

const detectHeadTurn = (metrics, step) => {
    const delta = metrics.turn - livenessBaseline.value.turn;
    const progress = Math.min(Math.round((Math.abs(delta) / TURN_DELTA_THRESHOLD) * 100), 100);

    if (Math.abs(delta) < TURN_DELTA_THRESHOLD) {
        livenessMessage.value = `Menoleh ke satu sisi sedikit lebih jauh (${progress}%).`;
        return false;
    }

    if (step === 'turn_left') {
        return true;
    }

    return true;
};

const livenessStepPassed = (step, metrics) => {
    if (step === 'mouth_open') {
        return detectActiveGesture(metrics);
    }

    if (step === 'turn_left') {
        return detectHeadTurn(metrics, step);
    }

    return false;
};

const completeLivenessStep = () => {
    currentLivenessIndex.value += 1;
    activeGesture.value = null;

    if (currentLivenessIndex.value >= livenessSteps.value.length) {
        livenessCompletedAt.value = new Date().toISOString();
        livenessDetail.value = '';
        livenessMessage.value = 'Liveness valid. Silakan verifikasi wajah.';
        clearLivenessLoop();
        livenessExpiryTimeoutId = window.setTimeout(() => {
            if (cameraActive.value) {
                livenessMessage.value = 'Liveness kedaluwarsa. Ulangi instruksi gerakan wajah.';
                startLivenessChallenge();
            }
        }, 30000);
        return;
    }

    livenessMessage.value = `Ikuti instruksi: ${currentLivenessStep.value?.label}.`;
};

const runLivenessCheck = async () => {
    if (!cameraActive.value || processing.value || livenessPassed.value || document.visibilityState === 'hidden') {
        if (cameraActive.value && !livenessPassed.value) {
            scheduleLivenessCheck();
        }

        return;
    }

    if (livenessChecking.value) {
        scheduleLivenessCheck();
        return;
    }

    livenessChecking.value = true;

    try {
        const api = await getFaceApi();
        const detection = await api
            .detectSingleFace(videoRef.value, createDetectorOptions(api))
            .withFaceLandmarks();

        if (!detection) {
            livenessMessage.value = 'Wajah tidak terdeteksi untuk liveness.';
            livenessDetail.value = '';
            return;
        }

        const metrics = readLivenessMetrics(detection.landmarks);

        if (!livenessBaseline.value && !calibrateLiveness(metrics)) {
            return;
        }

        livenessDetail.value = `Langkah ${currentLivenessIndex.value + 1} dari ${livenessSteps.value.length}.`;

        if (currentLivenessStep.value && livenessStepPassed(currentLivenessStep.value.key, metrics)) {
            completeLivenessStep();
            return;
        }
    } catch (error) {
        livenessMessage.value = 'Liveness gagal membaca gerakan wajah.';
        livenessDetail.value = '';
    } finally {
        livenessChecking.value = false;

        if (cameraActive.value && !livenessPassed.value) {
            scheduleLivenessCheck();
        }
    }
};

const startLivenessChallenge = () => {
    resetLiveness();
    scheduleLivenessCheck();
};

const startCamera = async () => {
    errorMessage.value = '';

    if (!secureCameraContext.value) {
        errorMessage.value = 'Akses kamera membutuhkan HTTPS atau localhost.';
        return;
    }

    const detectionModelsPromise = loadDetectionModels();
    const descriptorPromise = loadRegisteredDescriptor();
    detectionModelsPromise.catch(() => {});
    descriptorPromise.catch(() => {});
    statusMessage.value = 'Mengaktifkan kamera.';

    try {
        if (!navigator.mediaDevices?.getUserMedia) {
            errorMessage.value = 'Kamera tidak tersedia di browser ini.';
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
        statusMessage.value = detectionModelsLoaded.value && registeredDescriptor.value
            ? 'Kamera aktif. Selesaikan liveness terlebih dahulu.'
            : 'Kamera aktif. Memuat data wajah.';
    } catch (error) {
        if (error?.name === 'NotAllowedError') {
            errorMessage.value = 'Izin kamera ditolak.';
        } else if (error?.name === 'NotFoundError') {
            errorMessage.value = 'Kamera tidak tersedia.';
        } else {
            errorMessage.value = 'Kamera gagal dibuka.';
        }

        return;
    }

    try {
        await Promise.all([detectionModelsPromise, descriptorPromise]);

        if (!cameraActive.value) {
            return;
        }

        statusMessage.value = 'Kamera aktif. Selesaikan liveness terlebih dahulu.';
        startLivenessChallenge();
        warmRecognitionModel();
    } catch (error) {
        errorMessage.value = (!error.response && error.request)
            ? 'Koneksi terputus saat memuat data wajah.'
            : 'Model deteksi wajah atau descriptor tersimpan gagal dimuat.';
    }
};

const stopCamera = () => {
    clearLivenessLoop();
    videoRef.value?.pause();
    if (videoRef.value) {
        videoRef.value.srcObject = null;
    }
    stream.value?.getTracks().forEach((track) => track.stop());
    stream.value = null;
    cameraActive.value = false;
    resetLiveness();
};

const verifyFace = async () => {
    if (!canVerify.value) {
        if (!livenessPassed.value) {
            errorMessage.value = 'Selesaikan liveness detection terlebih dahulu.';
        }

        return;
    }

    processing.value = true;
    errorMessage.value = '';
    statusMessage.value = recognitionModelLoaded.value ? 'Mendeteksi wajah.' : 'Menyiapkan model pengenal wajah.';

    try {
        await loadRecognitionModel();
        statusMessage.value = 'Mendeteksi wajah.';

        const api = await getFaceApi();
        const detections = await api
            .detectAllFaces(videoRef.value, createDetectorOptions(api, 320))
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
            liveness: {
                challenge_id: props.livenessChallenge.id,
                steps: props.livenessChallenge.steps,
                completed_at: livenessCompletedAt.value,
            },
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

            if (attemptsLeft.value > 0) {
                startLivenessChallenge();
            }
        }

        if (response?.status === 422 && response?.data?.message?.includes('Liveness')) {
            startLivenessChallenge();
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
            <header class="content-hero flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">Verifikasi Wajah</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">{{ session.mata_kuliah }}</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        QR valid sampai {{ verificationExpiresAt }}.
                    </p>
                </div>
                <Link
                    class="btn-secondary"
                    href="/mahasiswa/absen"
                >
                    <RotateCcw class="h-4 w-4" />
                    Scan ulang
                </Link>
            </header>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_0.9fr]">
                <article class="apple-card p-5">
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
                                class="btn-secondary px-3 py-2"
                                @click="cameraActive ? stopCamera() : startCamera()"
                            >
                                <Square v-if="cameraActive" class="h-4 w-4" />
                                <Camera v-else class="h-4 w-4" />
                                {{ cameraActive ? 'Matikan kamera' : 'Nyalakan kamera' }}
                            </button>
                            <button
                                type="button"
                                class="btn-primary px-3 py-2"
                                :disabled="!canVerify"
                                @click="verifyFace"
                            >
                                <ScanFace class="h-4 w-4" />
                                {{ processing ? 'Memeriksa' : 'Verifikasi' }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-lg bg-zinc-950">
                        <video ref="videoRef" class="aspect-square w-full object-cover" autoplay muted playsinline />
                    </div>

                    <div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-950">Liveness detection</h3>
                                <p class="mt-1 text-sm text-zinc-600">
                                    {{ livenessMessage || 'Aktifkan kamera, lalu ikuti instruksi gerakan wajah.' }}
                                </p>
                                <p v-if="livenessDetail" class="mt-1 text-xs font-medium text-zinc-500">
                                    {{ livenessDetail }}
                                </p>
                            </div>
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="livenessPassed ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                            >
                                {{ livenessPassed ? 'Valid' : 'Belum valid' }}
                            </span>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            <div
                                v-for="(step, index) in livenessSteps"
                                :key="step.key"
                                class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
                                :class="index < currentLivenessIndex ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-zinc-200 bg-white text-zinc-600'"
                            >
                                <CheckCircle2
                                    class="h-4 w-4"
                                    :class="index < currentLivenessIndex ? 'text-emerald-700' : 'text-zinc-300'"
                                />
                                <span class="font-medium">{{ step.label }}</span>
                            </div>
                        </div>
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
                    <section class="apple-subcard border-emerald-100 bg-emerald-50/90 p-5 text-emerald-950">
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

                    <section class="apple-card p-5">
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
