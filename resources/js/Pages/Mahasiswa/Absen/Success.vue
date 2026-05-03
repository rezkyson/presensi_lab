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
        <div class="mx-auto max-w-3xl space-y-6">
            <section class="rounded-lg border border-emerald-200 bg-emerald-50 p-6 text-emerald-950">
                <div class="flex items-start gap-4">
                    <div class="rounded-md bg-emerald-700 p-3 text-white">
                        <CheckCircle2 class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-emerald-700">Presensi berhasil</p>
                        <h1 class="mt-1 text-2xl font-semibold">{{ result.session?.mata_kuliah }}</h1>
                        <p class="mt-2 text-sm text-emerald-800">
                            Wajah cocok dengan distance {{ result.distance }}.
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-zinc-500">Kelas</dt>
                        <dd class="mt-1 font-semibold text-zinc-950">{{ result.session?.kelas?.nama_kelas }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500">Ruangan</dt>
                        <dd class="mt-1 font-semibold text-zinc-950">{{ result.session?.ruangan }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500">Dosen</dt>
                        <dd class="mt-1 font-semibold text-zinc-950">{{ result.session?.dosen }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500">Jam</dt>
                        <dd class="mt-1 font-semibold text-zinc-950">{{ result.session?.jam_mulai }}-{{ result.session?.jam_selesai }}</dd>
                    </div>
                </dl>

                <Link
                    class="mt-6 inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800"
                    href="/mahasiswa/dashboard"
                >
                    <History class="h-4 w-4" />
                    Kembali ke dashboard
                </Link>
            </section>
        </div>
    </MahasiswaLayout>
</template>
