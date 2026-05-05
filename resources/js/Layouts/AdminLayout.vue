<script setup>
import RoleLayout from './RoleLayout.vue';
import { BarChart3, Building2, CalendarDays, GraduationCap, LayoutDashboard, School, Users } from 'lucide-vue-next';

const navigation = [
    { label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { label: 'Mahasiswa', href: '/admin/mahasiswa', icon: GraduationCap },
    { label: 'Dosen', href: '/admin/dosen', icon: Users },
    { label: 'Kelas', href: '/admin/kelas', icon: School },
    { label: 'Ruangan', href: '/admin/ruangan', icon: Building2 },
    { label: 'Jadwal', href: '/admin/jadwal', icon: CalendarDays },
    { label: 'Rekap', href: '/admin/rekap', icon: BarChart3 },
];
</script>

<template>
    <RoleLayout title="Admin" subtitle="Kelola data akademik dan presensi" :navigation="navigation">
        <slot />
    </RoleLayout>
</template>
