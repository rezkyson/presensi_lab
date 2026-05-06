<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    kelasOptions: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    nim: '',
    prodi: '',
    angkatan: new Date().getFullYear(),
    kelas_ids: [],
});

const submit = () => {
    form.post('/admin/mahasiswa');
};
</script>

<template>
    <Head title="Tambah Mahasiswa" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <header class="content-hero">
                <p class="eyebrow">Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Tambah mahasiswa</h1>
                <p class="mt-2 text-sm text-zinc-600">
                    Akun mahasiswa akan dibuat otomatis dengan role mahasiswa.
                </p>
            </header>

            <form class="apple-card p-6" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="form-label" for="name">Nama</label>
                        <input id="name" v-model="form.name" class="form-input mt-2" type="text">
                        <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="form-label" for="email">Email</label>
                        <input id="email" v-model="form.email" class="form-input mt-2" type="email">
                        <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="form-label" for="password">Password awal</label>
                        <input id="password" v-model="form.password" class="form-input mt-2" placeholder="Default: password" type="password">
                        <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="form-label" for="nim">NIM</label>
                        <input id="nim" v-model="form.nim" class="form-input mt-2" type="text">
                        <p v-if="form.errors.nim" class="form-error">{{ form.errors.nim }}</p>
                    </div>

                    <div>
                        <label class="form-label" for="angkatan">Angkatan</label>
                        <input id="angkatan" v-model="form.angkatan" class="form-input mt-2" type="number">
                        <p v-if="form.errors.angkatan" class="form-error">{{ form.errors.angkatan }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label" for="prodi">Prodi</label>
                        <input id="prodi" v-model="form.prodi" class="form-input mt-2" type="text">
                        <p v-if="form.errors.prodi" class="form-error">{{ form.errors.prodi }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label" for="kelas">Kelas</label>
                        <select id="kelas" v-model="form.kelas_ids" class="form-input mt-2 min-h-32" multiple>
                            <option v-for="kelas in kelasOptions" :key="kelas.id" :value="kelas.id">
                                {{ kelas.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.kelas_ids" class="form-error">{{ form.errors.kelas_ids }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link class="btn-secondary py-2 text-center" href="/admin/mahasiswa">
                        Batal
                    </Link>
                    <button class="btn-primary py-2 disabled:opacity-60" type="submit" :disabled="form.processing">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
