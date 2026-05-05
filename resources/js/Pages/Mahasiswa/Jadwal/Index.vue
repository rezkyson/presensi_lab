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
        <div class="space-y-6">
            <header>
                <p class="text-sm font-medium text-emerald-700">Mahasiswa</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Jadwal kuliah</h1>
                <p class="mt-2 text-sm text-zinc-600">
                    Hari ini {{ todayName }}.
                </p>
            </header>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2">
                    <CalendarDays class="h-5 w-5 text-emerald-700" />
                    <h2 class="text-base font-semibold text-zinc-950">Daftar jadwal</h2>
                </div>

                <div v-if="schedules.length" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="schedule in schedules"
                        :key="schedule.id"
                        class="rounded-md border border-zinc-200 p-4"
                        :class="schedule.is_today ? 'border-emerald-200 bg-emerald-50' : 'bg-white'"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-zinc-950">{{ schedule.mata_kuliah }}</p>
                                <p class="mt-1 text-sm text-zinc-600">
                                    {{ schedule.kelas?.nama_kelas }} &middot; {{ schedule.kelas?.prodi }}
                                </p>
                            </div>
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

                        <div class="mt-4 space-y-2 text-sm text-zinc-600">
                            <p class="flex items-center gap-2">
                                <Clock class="h-4 w-4" />
                                {{ schedule.hari }}, {{ schedule.jam_mulai }}-{{ schedule.jam_selesai }}
                            </p>
                            <p class="flex items-center gap-2">
                                <MapPin class="h-4 w-4" />
                                {{ schedule.ruangan }}
                            </p>
                            <p class="flex items-center gap-2">
                                <UserRound class="h-4 w-4" />
                                {{ schedule.dosen ?? '-' }}
                            </p>
                            <p
                                v-if="schedule.schedule_status_description"
                                class="font-medium"
                                :class="schedule.schedule_status === 'ended' ? 'text-zinc-600' : 'text-amber-700'"
                            >
                                {{ schedule.schedule_status_description }}
                            </p>
                        </div>
                    </article>
                </div>

                <div v-else class="empty-state mt-4">
                    Belum ada jadwal kuliah yang terdaftar.
                </div>
            </section>
        </div>
    </MahasiswaLayout>
</template>
