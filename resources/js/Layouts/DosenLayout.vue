<script setup>
import RoleLayout from './RoleLayout.vue';
import { BarChart3, CalendarCheck, LayoutDashboard, QrCode } from 'lucide-vue-next';

const navigation = [
    { label: 'Dashboard', href: '/dosen/dashboard', icon: LayoutDashboard },
    { label: 'Sesi Absensi', href: '/dosen/sesi', icon: QrCode },
    { label: 'Monitor', href: '/dosen/monitor', icon: CalendarCheck },
    { label: 'Rekap', href: '/dosen/rekap', icon: BarChart3 },
];
</script>

<template>
    <RoleLayout title="Dosen" subtitle="Kelola sesi dan pantau kehadiran" :navigation="navigation">
        <slot />
    </RoleLayout>
</template>
