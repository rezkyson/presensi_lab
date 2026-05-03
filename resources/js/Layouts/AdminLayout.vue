<script setup>
import RoleLayout from './RoleLayout.vue';
import { BarChart3, CalendarDays, GraduationCap, LayoutDashboard, Users } from 'lucide-vue-next';

const navigation = [
    { label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { label: 'Mahasiswa', href: '/admin/mahasiswa', icon: GraduationCap },
    { label: 'Dosen', href: '/admin/dosen', icon: Users },
    { label: 'Jadwal', href: '/admin/jadwal', icon: CalendarDays },
    { label: 'Rekap', href: '/admin/rekap', icon: BarChart3 },
];
</script>

<template>
    <RoleLayout title="Admin" subtitle="Kelola data akademik dan presensi" :navigation="navigation">
        <slot />
    </RoleLayout>
</template>
