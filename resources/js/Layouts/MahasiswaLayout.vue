<script setup>
import RoleLayout from './RoleLayout.vue';
import { CalendarDays, History, LayoutDashboard, ScanFace, UserCircle } from 'lucide-vue-next';

const navigation = [
    { label: 'Dashboard', href: '/mahasiswa/dashboard', icon: LayoutDashboard },
    { label: 'Absen', href: '/mahasiswa/absen', icon: ScanFace },
    { label: 'Profil', href: '/mahasiswa/profil', icon: UserCircle },
    { label: 'Riwayat', href: '/mahasiswa/riwayat', icon: History },
    { label: 'Jadwal', href: '/mahasiswa/jadwal', icon: CalendarDays },
];
</script>

<template>
    <RoleLayout title="Mahasiswa" subtitle="Presensi dan riwayat kehadiran" :navigation="navigation">
        <slot />
    </RoleLayout>
</template>
