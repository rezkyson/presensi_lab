import { computed, ref } from 'vue';

const STORAGE_KEY = 'sihadir.camera_profile';
const PROFILE_ORDER = ['low', 'balanced', 'high'];

const PROFILES = {
    low: {
        width: 320,
        height: 320,
        detectorInputSize: 160,
        detectionIntervalMs: 1800,
        livenessIntervalMs: 700,
        qualitySampleSize: 56,
        captureSize: 480,
        jpegQuality: 0.76,
        slowDetectionMs: 1300,
    },
    balanced: {
        width: 480,
        height: 480,
        detectorInputSize: 224,
        detectionIntervalMs: 1400,
        livenessIntervalMs: 500,
        qualitySampleSize: 72,
        captureSize: 640,
        jpegQuality: 0.82,
        slowDetectionMs: 1000,
    },
    high: {
        width: 640,
        height: 640,
        detectorInputSize: 320,
        detectionIntervalMs: 1000,
        livenessIntervalMs: 380,
        qualitySampleSize: 96,
        captureSize: 720,
        jpegQuality: 0.86,
        slowDetectionMs: 850,
    },
};

const safeLocalStorage = () => {
    try {
        return window.localStorage;
    } catch {
        return null;
    }
};

const savedProfile = () => {
    const value = safeLocalStorage()?.getItem(STORAGE_KEY);

    return PROFILE_ORDER.includes(value) ? value : null;
};

const inferProfile = () => {
    const memory = Number(navigator.deviceMemory ?? 0);
    const cores = Number(navigator.hardwareConcurrency ?? 0);

    if ((memory > 0 && memory <= 2) || (cores > 0 && cores <= 4)) {
        return 'low';
    }

    if ((memory >= 6 && cores >= 6) || cores >= 8) {
        return 'high';
    }

    return 'balanced';
};

const persistProfile = (profileName) => {
    safeLocalStorage()?.setItem(STORAGE_KEY, profileName);
};

const lowerProfile = (profileName) => {
    const index = PROFILE_ORDER.indexOf(profileName);

    return PROFILE_ORDER[Math.max(0, index - 1)] ?? 'low';
};

const constraintsFor = (profile) => [
    {
        video: {
            facingMode: 'user',
            width: { ideal: profile.width },
            height: { ideal: profile.height },
        },
        audio: false,
    },
    {
        video: { facingMode: 'user' },
        audio: false,
    },
    {
        video: true,
        audio: false,
    },
];

export function useAdaptiveCamera() {
    const profileName = ref(savedProfile() ?? inferProfile());
    const slowSamples = ref(0);
    const profile = computed(() => PROFILES[profileName.value] ?? PROFILES.balanced);

    const downgrade = () => {
        const nextProfile = lowerProfile(profileName.value);

        if (nextProfile === profileName.value) {
            return false;
        }

        profileName.value = nextProfile;
        persistProfile(nextProfile);
        slowSamples.value = 0;

        return true;
    };

    const recordDetection = (durationMs) => {
        if (durationMs > profile.value.slowDetectionMs) {
            slowSamples.value += 1;
        } else {
            slowSamples.value = 0;
        }

        if (slowSamples.value >= 2) {
            return downgrade();
        }

        return false;
    };

    const openCameraStream = async () => {
        let lastError = null;
        let currentProfileName = profileName.value;

        while (true) {
            for (const constraints of constraintsFor(PROFILES[currentProfileName] ?? PROFILES.low)) {
                try {
                    profileName.value = currentProfileName;
                    persistProfile(currentProfileName);

                    return await navigator.mediaDevices.getUserMedia(constraints);
                } catch (error) {
                    lastError = error;

                    if (['NotAllowedError', 'SecurityError'].includes(error?.name)) {
                        throw error;
                    }
                }
            }

            const nextProfile = lowerProfile(currentProfileName);

            if (nextProfile === currentProfileName) {
                throw lastError;
            }

            currentProfileName = nextProfile;
        }
    };

    const cameraErrorMessage = (error) => {
        if (error?.name === 'NotAllowedError') {
            return 'Izin kamera ditolak.';
        }

        if (error?.name === 'NotFoundError' || error?.name === 'DevicesNotFoundError') {
            return 'Kamera tidak tersedia.';
        }

        if (error?.name === 'NotReadableError' || error?.name === 'TrackStartError') {
            return 'Kamera sedang dipakai aplikasi lain. Tutup kamera/aplikasi lain lalu coba lagi.';
        }

        if (error?.name === 'OverconstrainedError' || error?.name === 'ConstraintNotSatisfiedError') {
            return 'Kamera tidak mendukung pengaturan yang diminta. Coba muat ulang halaman.';
        }

        return 'Kamera gagal dibuka. Coba muat ulang halaman atau tutup aplikasi lain yang memakai kamera.';
    };

    return {
        profile,
        profileName,
        cameraErrorMessage,
        downgrade,
        openCameraStream,
        recordDetection,
    };
}
