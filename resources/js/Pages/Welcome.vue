<script setup>
defineProps({
    appName: {
        type: String,
        default: 'Sistem Kehadiran Digital',
    },
});
</script>

<template>
    <main class="min-h-screen bg-zinc-950 text-white">
        <section class="mx-auto flex min-h-screen max-w-5xl flex-col justify-center px-6 py-12">
            <p class="text-sm font-medium uppercase tracking-wide text-emerald-300">
                Sistem Kehadiran Digital
            </p>
            <h1 class="mt-4 max-w-3xl text-4xl font-semibold leading-tight sm:text-5xl">
                {{ appName }}
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-7 text-zinc-300">
                Fondasi Inertia.js dan Vue 3 sudah aktif. Tahap berikutnya adalah membangun database,
                role pengguna, dan alur presensi sesuai PRD.
            </p>
        </section>
    </main>
</template>
