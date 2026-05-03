<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    dosen: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.dosen.name ?? '',
    email: props.dosen.email ?? '',
    nip: props.dosen.nip ?? '',
    bidang_studi: props.dosen.bidang_studi ?? '',
});

const submit = () => {
    form.put(`/admin/dosen/${props.dosen.id}`);
};
</script>

<template>
    <Head title="Edit Dosen" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <header>
                <p class="text-sm font-medium text-emerald-700">Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Edit dosen</h1>
                <p class="mt-2 text-sm text-zinc-600">
                    Perbarui data akun dan profil akademik dosen.
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
                        <label class="block text-sm font-medium text-zinc-800" for="nip">NIP</label>
                        <input id="nip" v-model="form.nip" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="text">
                        <p v-if="form.errors.nip" class="mt-1 text-sm text-red-600">{{ form.errors.nip }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-800" for="bidang_studi">Bidang studi</label>
                        <input id="bidang_studi" v-model="form.bidang_studi" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="text">
                        <p v-if="form.errors.bidang_studi" class="mt-1 text-sm text-red-600">{{ form.errors.bidang_studi }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link class="rounded-md border border-zinc-300 px-4 py-2 text-center text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100" href="/admin/dosen">
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
