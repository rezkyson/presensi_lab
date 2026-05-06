<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import { RotateCcw, XCircle } from 'lucide-vue-next';

defineProps({
    result: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Presensi Gagal" />

    <MahasiswaLayout>
        <div class="mobile-app-surface mx-auto max-w-md space-y-5">
            <section class="primary-action-card">
                <div class="flex items-start gap-4">
                    <div class="rounded-lg bg-rose-600 p-3 text-white">
                        <XCircle class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white/55">Presensi gagal</p>
                        <h1 class="mt-1 text-2xl font-semibold">{{ result.message }}</h1>
                        <p v-if="result.distance !== undefined" class="mt-2 text-sm text-white/65">
                            Distance terakhir {{ result.distance }}.
                        </p>
                    </div>
                </div>
            </section>

            <section class="ios-list">
                <div class="ios-list-row">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-950">{{ result.session?.mata_kuliah }}</h2>
                        <p class="mt-1 text-sm text-zinc-600">
                            {{ result.session?.kelas?.nama_kelas }} &middot; {{ result.session?.ruangan }}
                        </p>
                    </div>
                </div>

                <Link
                    class="btn-primary mx-4 mb-4 mt-2 w-[calc(100%-2rem)]"
                    href="/mahasiswa/absen"
                >
                    <RotateCcw class="h-4 w-4" />
                    Scan ulang
                </Link>
            </section>
        </div>
    </MahasiswaLayout>
</template>
