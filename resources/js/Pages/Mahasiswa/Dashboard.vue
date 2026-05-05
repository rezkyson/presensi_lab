<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import { ScanLine } from 'lucide-vue-next';

defineProps({
    todayName: {
        type: String,
        required: true,
    },
    faceRegistered: {
        type: Boolean,
        required: true,
    },
    todaySchedules: {
        type: Array,
        default: () => [],
    },
    attendanceToday: {
        type: Array,
        default: () => [],
    },
    availableAttendanceSession: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Dashboard Mahasiswa" />

    <MahasiswaLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Dashboard Mahasiswa</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Presensi hari ini</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Hari ini {{ todayName }}.
                    </p>
                </div>
                <Link
                    class="touch-target inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold transition"
                    :class="availableAttendanceSession && faceRegistered ? 'bg-emerald-700 text-white hover:bg-emerald-800' : 'cursor-not-allowed bg-zinc-300 text-zinc-600'"
                    :href="availableAttendanceSession && faceRegistered ? '/mahasiswa/absen' : '#'"
                    :aria-disabled="!(availableAttendanceSession && faceRegistered)"
                    @click="!(availableAttendanceSession && faceRegistered) && $event.preventDefault()"
                >
                    <ScanLine class="h-4 w-4" />
                    Scan QR
                </Link>
            </header>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-600">Status wajah</p>
                    <p class="mt-3 text-lg font-semibold" :class="faceRegistered ? 'text-emerald-700' : 'text-amber-700'">
                        {{ faceRegistered ? 'Terdaftar' : 'Belum terdaftar' }}
                    </p>
                </article>
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-600">Jadwal hari ini</p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ todaySchedules.length }}</p>
                </article>
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-600">Presensi tercatat</p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ attendanceToday.length }}</p>
                </article>
            </section>

            <section v-if="!faceRegistered" class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-900">
                <h2 class="font-semibold">Daftarkan wajah terlebih dahulu</h2>
                <p class="mt-1 text-sm">
                    Presensi QR dan verifikasi wajah akan aktif setelah data wajah tersimpan.
                </p>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-zinc-950">Jadwal hari ini</h2>
                    <div v-if="todaySchedules.length" class="mt-4 space-y-3">
                        <div
                            v-for="schedule in todaySchedules"
                            :key="schedule.id"
                            class="rounded-md border border-zinc-200 p-4"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-zinc-950">{{ schedule.mata_kuliah }}</p>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="{
                                                'bg-emerald-100 text-emerald-800': schedule.schedule_status === 'ongoing',
                                                'bg-amber-100 text-amber-800': schedule.schedule_status === 'upcoming',
                                                'bg-zinc-100 text-zinc-700': schedule.schedule_status === 'ended',
                                                'bg-rose-100 text-rose-800': schedule.schedule_status === 'unavailable',
                                            }"
                                        >
                                            {{ schedule.schedule_status_label }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-zinc-600">
                                        {{ schedule.dosen }} &middot; {{ schedule.ruangan }}
                                    </p>
                                    <p
                                        v-if="schedule.schedule_status_description"
                                        class="mt-2 text-sm font-medium"
                                        :class="schedule.schedule_status === 'ended' ? 'text-zinc-600' : 'text-amber-700'"
                                    >
                                        {{ schedule.schedule_status_description }}
                                    </p>
                                </div>
                                <p class="text-sm font-medium text-zinc-700">
                                    {{ schedule.jam_mulai }}-{{ schedule.jam_selesai }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="empty-state mt-4">
                        Tidak ada jadwal kuliah hari ini.
                    </div>
                </article>

                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-zinc-950">Status kehadiran</h2>
                    <div v-if="attendanceToday.length" class="mt-4 space-y-3">
                        <div
                            v-for="attendance in attendanceToday"
                            :key="attendance.id"
                            class="rounded-md border border-zinc-200 p-4"
                        >
                            <p class="font-semibold text-zinc-950">{{ attendance.mata_kuliah }}</p>
                            <p class="mt-1 text-sm capitalize text-zinc-600">
                                {{ attendance.status.replace('_', ' ') }} &middot; {{ attendance.timestamp }}
                            </p>
                        </div>
                    </div>
                    <div v-else class="empty-state mt-4">
                        Belum ada presensi yang tercatat hari ini.
                    </div>
                </article>
            </section>
        </div>
    </MahasiswaLayout>
</template>
