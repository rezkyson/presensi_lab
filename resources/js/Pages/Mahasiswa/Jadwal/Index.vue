<script setup>
import { Head } from '@inertiajs/vue3';
import MahasiswaLayout from '@/Layouts/MahasiswaLayout.vue';
import { CalendarDays, Clock, MapPin, UserRound } from 'lucide-vue-next';

defineProps({
    todayName: {
        type: String,
        required: true,
    },
    schedules: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Jadwal Kuliah" />

    <MahasiswaLayout>
        <div class="mobile-app-surface space-y-5">
            <header class="content-hero">
                <p class="eyebrow">Mahasiswa</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Jadwal kuliah</h1>
                <p class="mt-2 text-sm text-zinc-600">
                    Hari ini {{ todayName }}.
                </p>
            </header>

            <section class="ios-list">
                <div class="ios-list-row items-center">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-950 text-white">
                            <CalendarDays class="h-4 w-4" />
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-zinc-950">Daftar jadwal</h2>
                            <p class="text-xs font-medium text-apple-tertiary">Hari ini {{ todayName }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="schedules.length" class="divide-y divide-black/5">
                    <article
                        v-for="schedule in schedules"
                        :key="schedule.id"
                        class="px-4 py-4 transition"
                        :class="schedule.is_today ? 'bg-emerald-50/80' : 'bg-white/40'"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-zinc-950">{{ schedule.mata_kuliah }}</p>
                                <p class="mt-1 text-sm text-zinc-600">
                                    {{ schedule.kelas?.nama_kelas }} &middot; {{ schedule.kelas?.prodi }}
                                </p>
                            </div>
                            <span
                                class="ios-chip shrink-0"
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

                        <div class="mt-4 grid gap-2 text-sm text-zinc-600 sm:grid-cols-3">
                            <p class="flex items-center gap-2 rounded-lg bg-zinc-100/75 px-3 py-2">
                                <Clock class="h-4 w-4" />
                                {{ schedule.hari }}, {{ schedule.jam_mulai }}-{{ schedule.jam_selesai }}
                            </p>
                            <p class="flex items-center gap-2 rounded-lg bg-zinc-100/75 px-3 py-2">
                                <MapPin class="h-4 w-4" />
                                {{ schedule.ruangan }}
                            </p>
                            <p class="flex items-center gap-2 rounded-lg bg-zinc-100/75 px-3 py-2">
                                <UserRound class="h-4 w-4" />
                                {{ schedule.dosen ?? '-' }}
                            </p>
                            <p
                                v-if="schedule.schedule_status_description"
                                class="font-medium sm:col-span-3"
                                :class="schedule.schedule_status === 'ended' ? 'text-zinc-600' : 'text-amber-700'"
                            >
                                {{ schedule.schedule_status_description }}
                            </p>
                        </div>
                    </article>
                </div>

                <div v-else class="p-4">
                    <div class="empty-state text-center">Belum ada jadwal kuliah yang terdaftar.</div>
                </div>
            </section>
        </div>
    </MahasiswaLayout>
</template>
