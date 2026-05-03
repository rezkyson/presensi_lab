<script setup>
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Camera, Save, Square } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

let faceapi = null;

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
const modelsLoaded = ref(false);
const cameraActive = ref(false);
const statusMessage = ref('');
const previewImage = ref(null);
const samples = ref([]);

const form = useForm({
    image_base64: '',
    face_descriptor: [],
});

const registrationStatus = computed(() => props.profile.wajah_terdaftar ? 'Terdaftar' : 'Belum terdaftar');
const canSave = computed(() => form.image_base64 && form.face_descriptor.length === props.faceConfig.descriptorLength);
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

const startCamera = async () => {
    if (!secureCameraContext.value) {
        statusMessage.value = 'Akses kamera membutuhkan HTTPS atau localhost.';
        return;
    }

    try {
        await loadModels();

        if (!navigator.mediaDevices?.getUserMedia) {
            statusMessage.value = 'Kamera tidak tersedia di browser ini.';
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
        if (error?.name === 'NotAllowedError') {
            statusMessage.value = 'Izin kamera ditolak.';
        } else if (error?.name === 'NotFoundError') {
            statusMessage.value = 'Kamera tidak tersedia.';
        } else {
            statusMessage.value = 'Kamera atau model wajah gagal dimuat.';
        }
    }
};

const stopCamera = () => {
    stream.value?.getTracks().forEach((track) => track.stop());
    stream.value = null;
    cameraActive.value = false;
};

const captureFace = async () => {
    if (!videoRef.value || !modelsLoaded.value) {
        statusMessage.value = 'Model wajah belum siap.';
        return;
    }

    statusMessage.value = 'Mendeteksi wajah.';
    const api = await getFaceApi();

    const detections = await api
        .detectAllFaces(videoRef.value, new api.SsdMobilenetv1Options({ minConfidence: 0.5 }))
        .withFaceLandmarks()
        .withFaceDescriptors();

    if (detections.length === 0) {
        statusMessage.value = 'Wajah tidak terdeteksi.';
        return;
    }

    if (detections.length > 1) {
        statusMessage.value = 'Terdeteksi lebih dari satu wajah.';
        return;
    }

    const canvas = canvasRef.value;
    canvas.width = videoRef.value.videoWidth;
    canvas.height = videoRef.value.videoHeight;
    canvas.getContext('2d').drawImage(videoRef.value, 0, 0, canvas.width, canvas.height);

    const image = canvas.toDataURL('image/jpeg', 0.9);
    const descriptor = Array.from(detections[0].descriptor);

    samples.value = [...samples.value, descriptor].slice(-3);
    form.image_base64 = image;
    form.face_descriptor = averageDescriptor(samples.value);
    previewImage.value = image;
    statusMessage.value = `Wajah tersimpan sementara (${samples.value.length}/3).`;
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
        <div class="space-y-6">
            <header>
                <p class="text-sm font-medium text-emerald-700">Profil</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">{{ profile.name }}</h1>
                <p class="mt-2 text-sm text-zinc-600">{{ profile.nim }} · {{ profile.prodi }} · Angkatan {{ profile.angkatan }}</p>
            </header>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-600">Email</p>
                    <p class="mt-3 font-semibold text-zinc-950">{{ profile.email }}</p>
                </article>
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-600">Status wajah</p>
                    <p class="mt-3 font-semibold" :class="profile.wajah_terdaftar ? 'text-emerald-700' : 'text-amber-700'">
                        {{ registrationStatus }}
                    </p>
                </article>
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-600">Terakhir diperbarui</p>
                    <p class="mt-3 font-semibold text-zinc-950">{{ profile.face_registered_at ?? '-' }}</p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_0.9fr]">
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-base font-semibold text-zinc-950">Daftarkan wajah</h2>
                        <div class="flex gap-2">
                            <button
                                class="inline-flex items-center justify-center gap-2 rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100"
                                type="button"
                                @click="cameraActive ? stopCamera() : startCamera()"
                            >
                                <Square v-if="cameraActive" class="h-4 w-4" />
                                <Camera v-else class="h-4 w-4" />
                                {{ cameraActive ? 'Stop' : 'Kamera' }}
                            </button>
                            <button
                                class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800 disabled:opacity-60"
                                type="button"
                                :disabled="!cameraActive"
                                @click="captureFace"
                            >
                                Ambil
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-lg bg-zinc-950">
                        <video ref="videoRef" class="aspect-video w-full object-cover" muted playsinline />
                        <canvas ref="canvasRef" class="hidden" />
                    </div>

                    <p class="mt-3 text-sm text-zinc-600">{{ statusMessage || 'Kamera belum aktif.' }}</p>
                    <p v-if="form.errors.image_base64" class="mt-2 text-sm text-red-600">{{ form.errors.image_base64 }}</p>
                    <p v-if="form.errors.face_descriptor" class="mt-2 text-sm text-red-600">{{ form.errors.face_descriptor }}</p>
                </article>

                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-zinc-950">Preview</h2>
                    <div class="mt-5 overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100">
                        <img v-if="previewImage" :src="previewImage" alt="Preview wajah" class="aspect-video w-full object-cover">
                        <div v-else class="flex aspect-video items-center justify-center text-sm text-zinc-500">
                            Belum ada preview.
                        </div>
                    </div>

                    <button
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60"
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
