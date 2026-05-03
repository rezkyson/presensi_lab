<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    mahasiswa: {
        type: Object,
        required: true,
    },
    kelasOptions: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.mahasiswa.name ?? '',
    email: props.mahasiswa.email ?? '',
    nim: props.mahasiswa.nim ?? '',
    prodi: props.mahasiswa.prodi ?? '',
    angkatan: props.mahasiswa.angkatan ?? '',
    kelas_ids: props.mahasiswa.kelas_ids ?? [],
});

const submit = () => {
    form.put(`/admin/mahasiswa/${props.mahasiswa.id}`);
};
</script>

<template>
    <Head title="Edit Mahasiswa" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <header>
                <p class="text-sm font-medium text-emerald-700">Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Edit mahasiswa</h1>
                <p class="mt-2 text-sm text-zinc-600">
                    Perbarui data akun dan akademik mahasiswa.
                </p>
            </header>

            <form class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-800" for="name">Nama</label>
                        <input id="name" v-model="form.name" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="text">
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="email">Email</label>
                        <input id="email" v-model="form.email" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="email">
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="nim">NIM</label>
                        <input id="nim" v-model="form.nim" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="text">
                        <p v-if="form.errors.nim" class="mt-1 text-sm text-red-600">{{ form.errors.nim }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="angkatan">Angkatan</label>
                        <input id="angkatan" v-model="form.angkatan" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="number">
                        <p v-if="form.errors.angkatan" class="mt-1 text-sm text-red-600">{{ form.errors.angkatan }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="prodi">Prodi</label>
                        <input id="prodi" v-model="form.prodi" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="text">
                        <p v-if="form.errors.prodi" class="mt-1 text-sm text-red-600">{{ form.errors.prodi }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-800" for="kelas">Kelas</label>
                        <select id="kelas" v-model="form.kelas_ids" class="mt-2 min-h-32 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" multiple>
                            <option v-for="kelas in kelasOptions" :key="kelas.id" :value="kelas.id">
                                {{ kelas.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.kelas_ids" class="mt-1 text-sm text-red-600">{{ form.errors.kelas_ids }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link class="rounded-md border border-zinc-300 px-4 py-2 text-center text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100" href="/admin/mahasiswa">
                        Batal
                    </Link>
                    <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60" type="submit" :disabled="form.processing">
                        Simpan perubahan
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
