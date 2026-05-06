<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import { CheckCircle2, History } from 'lucide-vue-next';

defineProps({
    result: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Presensi Berhasil" />

    <MahasiswaLayout>
        <div class="mobile-app-surface mx-auto max-w-md space-y-5">
            <section class="primary-action-card">
                <div class="flex items-start gap-4">
                    <div class="rounded-lg bg-emerald-500 p-3 text-white">
                        <CheckCircle2 class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white/55">Presensi berhasil</p>
                        <h1 class="mt-1 text-2xl font-semibold">{{ result.session?.mata_kuliah }}</h1>
                        <p class="mt-2 text-sm text-white/65">
                            Wajah cocok dengan distance {{ result.distance }}.
                        </p>
                    </div>
                </div>
            </section>

            <section class="ios-list">
                <dl class="divide-y divide-black/5 text-sm">
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <dt class="font-medium text-apple-secondary">Kelas</dt>
                        <dd class="text-right font-semibold text-zinc-950">{{ result.session?.kelas?.nama_kelas }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <dt class="font-medium text-apple-secondary">Ruangan</dt>
                        <dd class="text-right font-semibold text-zinc-950">{{ result.session?.ruangan }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <dt class="font-medium text-apple-secondary">Dosen</dt>
                        <dd class="text-right font-semibold text-zinc-950">{{ result.session?.dosen }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <dt class="font-medium text-apple-secondary">Jam</dt>
                        <dd class="text-right font-semibold text-zinc-950">{{ result.session?.jam_mulai }}-{{ result.session?.jam_selesai }}</dd>
                    </div>
                </dl>

                <Link
                    class="btn-primary mx-4 mb-4 mt-2 w-[calc(100%-2rem)]"
                    href="/mahasiswa/dashboard"
                >
                    <History class="h-4 w-4" />
                    Kembali ke dashboard
                </Link>
            </section>
        </div>
    </MahasiswaLayout>
</template>
