<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nama: '',
    keterangan: '',
});

const submit = () => {
    form.post('/admin/ruangan');
};
</script>

<template>
    <Head title="Tambah Ruangan" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <header>
                <p class="text-sm font-medium text-emerald-700">Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Tambah ruangan</h1>
                <p class="mt-2 text-sm text-zinc-600">Tambahkan lab yang dapat dipilih saat membuat jadwal.</p>
            </header>

            <form class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="nama">Nama ruangan</label>
                        <input id="nama" v-model="form.nama" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" placeholder="Contoh: LAB 1" type="text">
                        <p v-if="form.errors.nama" class="mt-1 text-sm text-red-600">{{ form.errors.nama }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="keterangan">Keterangan</label>
                        <textarea id="keterangan" v-model="form.keterangan" class="mt-2 min-h-24 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" placeholder="Opsional"></textarea>
                        <p v-if="form.errors.keterangan" class="mt-1 text-sm text-red-600">{{ form.errors.keterangan }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link class="rounded-md border border-zinc-300 px-4 py-2 text-center text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100" href="/admin/ruangan">Batal</Link>
                    <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60" type="submit" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
