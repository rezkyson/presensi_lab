<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nama_kelas: '',
    prodi: '',
    semester: 1,
    tahun_akademik: '2025/2026',
});

const submit = () => {
    form.post('/admin/kelas');
};
</script>

<template>
    <Head title="Tambah Kelas" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <header>
                <p class="text-sm font-medium text-emerald-700">Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Tambah kelas</h1>
                <p class="mt-2 text-sm text-zinc-600">Buat kelas sebelum mengatur peserta dan dosen pengampu.</p>
            </header>

            <form class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="nama_kelas">Nama kelas</label>
                        <input id="nama_kelas" v-model="form.nama_kelas" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="text">
                        <p v-if="form.errors.nama_kelas" class="mt-1 text-sm text-red-600">{{ form.errors.nama_kelas }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="prodi">Prodi</label>
                        <input id="prodi" v-model="form.prodi" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="text">
                        <p v-if="form.errors.prodi" class="mt-1 text-sm text-red-600">{{ form.errors.prodi }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="semester">Semester</label>
                        <input id="semester" v-model="form.semester" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="number">
                        <p v-if="form.errors.semester" class="mt-1 text-sm text-red-600">{{ form.errors.semester }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="tahun_akademik">Tahun akademik</label>
                        <input id="tahun_akademik" v-model="form.tahun_akademik" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="text">
                        <p v-if="form.errors.tahun_akademik" class="mt-1 text-sm text-red-600">{{ form.errors.tahun_akademik }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link class="rounded-md border border-zinc-300 px-4 py-2 text-center text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100" href="/admin/kelas">Batal</Link>
                    <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60" type="submit" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
