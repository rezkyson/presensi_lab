<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    nip: '',
    bidang_studi: '',
});

const submit = () => {
    form.post('/admin/dosen');
};
</script>

<template>
    <Head title="Tambah Dosen" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <header class="content-hero">
                <p class="eyebrow">Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Tambah dosen</h1>
                <p class="mt-2 text-sm text-zinc-600">
                    Akun dosen akan dibuat otomatis dengan role dosen.
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
                        <label class="form-label" for="nip">NIP</label>
                        <input id="nip" v-model="form.nip" class="form-input mt-2" type="text">
                        <p v-if="form.errors.nip" class="form-error">{{ form.errors.nip }}</p>
                    </div>

                    <div>
                        <label class="form-label" for="bidang_studi">Bidang studi</label>
                        <input id="bidang_studi" v-model="form.bidang_studi" class="form-input mt-2" type="text">
                        <p v-if="form.errors.bidang_studi" class="form-error">{{ form.errors.bidang_studi }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link class="btn-secondary py-2 text-center" href="/admin/dosen">
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
