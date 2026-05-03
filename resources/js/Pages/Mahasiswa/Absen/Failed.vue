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
        <div class="mx-auto max-w-3xl space-y-6">
            <section class="rounded-lg border border-rose-200 bg-rose-50 p-6 text-rose-950">
                <div class="flex items-start gap-4">
                    <div class="rounded-md bg-rose-700 p-3 text-white">
                        <XCircle class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-rose-700">Presensi gagal</p>
                        <h1 class="mt-1 text-2xl font-semibold">{{ result.message }}</h1>
                        <p v-if="result.distance !== undefined" class="mt-2 text-sm text-rose-800">
                            Distance terakhir {{ result.distance }}.
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-zinc-950">{{ result.session?.mata_kuliah }}</h2>
                <p class="mt-2 text-sm text-zinc-600">
                    {{ result.session?.kelas?.nama_kelas }} &middot; {{ result.session?.ruangan }}
                </p>

                <Link
                    class="mt-6 inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800"
                    href="/mahasiswa/absen"
                >
                    <RotateCcw class="h-4 w-4" />
                    Scan ulang
                </Link>
            </section>
        </div>
    </MahasiswaLayout>
</template>
