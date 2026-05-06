<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import { CalendarDays, CheckCircle2, ScanLine, ShieldCheck } from 'lucide-vue-next';

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
        <div class="mobile-app-surface space-y-5">
            <header class="overflow-hidden rounded-lg bg-zinc-950 text-white shadow-apple-soft">
                <div class="px-5 py-6 sm:px-6">
                    <p class="text-sm font-semibold text-white/55">Dashboard Mahasiswa</p>
                    <h1 class="mt-2 text-4xl font-semibold leading-tight tracking-normal sm:text-5xl">
                        Presensi hari ini
                    </h1>
                    <p class="mt-3 text-sm text-white/65">
                        {{ todayName }} &middot; {{ todaySchedules.length }} jadwal
                    </p>
                </div>
                <div class="border-t border-white/10 bg-white/8 p-4">
                <Link
                    class="flex min-h-16 w-full items-center justify-between rounded-lg px-4 py-3 text-left text-sm font-semibold transition"
                    :class="availableAttendanceSession && faceRegistered ? 'bg-apple-blue text-white shadow-sm shadow-apple-blue/20 hover:bg-apple-blue-700' : 'cursor-not-allowed bg-white/12 text-white/45'"
                    :href="availableAttendanceSession && faceRegistered ? '/mahasiswa/absen' : '#'"
                    :aria-disabled="!(availableAttendanceSession && faceRegistered)"
                    @click="!(availableAttendanceSession && faceRegistered) && $event.preventDefault()"
                >
                    <span>
                        <span class="block text-xs opacity-70">Aksi utama</span>
                        <span class="mt-0.5 block text-lg">Scan QR</span>
                    </span>
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white/20">
                        <ScanLine class="h-5 w-5" />
                    </span>
                </Link>
                </div>
            </header>

            <section class="grid grid-cols-3 gap-3">
                <article class="metric-tile">
                    <ShieldCheck class="h-5 w-5" :class="faceRegistered ? 'text-emerald-600' : 'text-amber-600'" />
                    <p class="mt-3 text-xs font-semibold text-apple-tertiary">Wajah</p>
                    <p class="mt-1 text-sm font-semibold" :class="faceRegistered ? 'text-emerald-700' : 'text-amber-700'">
                        {{ faceRegistered ? 'Terdaftar' : 'Belum terdaftar' }}
                    </p>
                </article>
                <article class="metric-tile">
                    <CalendarDays class="h-5 w-5 text-apple-blue" />
                    <p class="mt-3 text-xs font-semibold text-apple-tertiary">Jadwal</p>
                    <p class="mt-1 text-3xl font-semibold text-zinc-950">{{ todaySchedules.length }}</p>
                </article>
                <article class="metric-tile">
                    <CheckCircle2 class="h-5 w-5 text-emerald-600" />
                    <p class="mt-3 text-xs font-semibold text-apple-tertiary">Tercatat</p>
                    <p class="mt-1 text-3xl font-semibold text-zinc-950">{{ attendanceToday.length }}</p>
                </article>
            </section>

            <section v-if="!faceRegistered" class="apple-subcard border-amber-100 bg-amber-50/90 p-5 text-amber-900">
                <h2 class="font-semibold">Daftarkan wajah terlebih dahulu</h2>
                <p class="mt-1 text-sm">
                    Presensi QR dan verifikasi wajah akan aktif setelah data wajah tersimpan.
                </p>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.3fr_1fr]">
                <article>
                    <h2 class="ios-section-title">Jadwal hari ini</h2>
                    <div v-if="todaySchedules.length" class="ios-list mt-3">
                        <div
                            v-for="schedule in todaySchedules"
                            :key="schedule.id"
                            class="ios-list-row"
                        >
                            <div class="min-w-0">
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
                            <p class="shrink-0 text-sm font-semibold text-zinc-700">
                                {{ schedule.jam_mulai }}<br>
                                <span class="font-normal text-apple-tertiary">{{ schedule.jam_selesai }}</span>
                            </p>
                        </div>
                    </div>
                    <div v-else class="empty-state mt-4">
                        Tidak ada jadwal kuliah hari ini.
                    </div>
                </article>

                <article>
                    <h2 class="ios-section-title">Status kehadiran</h2>
                    <div v-if="attendanceToday.length" class="ios-list mt-3">
                        <div
                            v-for="attendance in attendanceToday"
                            :key="attendance.id"
                            class="ios-list-row"
                        >
                            <div>
                                <p class="font-semibold text-zinc-950">{{ attendance.mata_kuliah }}</p>
                                <p class="mt-1 text-sm capitalize text-zinc-600">
                                    {{ attendance.status.replace('_', ' ') }}
                                </p>
                            </div>
                            <p class="text-sm font-medium text-apple-tertiary">{{ attendance.timestamp }}</p>
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
