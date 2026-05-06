<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    ruangan: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    nama: props.ruangan.nama ?? '',
    keterangan: props.ruangan.keterangan ?? '',
});

const submit = () => {
    form.put(`/admin/ruangan/${props.ruangan.id}`);
};
</script>

<template>
    <Head title="Edit Ruangan" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <header class="content-hero">
                <p class="eyebrow">Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Edit ruangan</h1>
                <p class="mt-2 text-sm text-zinc-600">Perbarui nama, keterangan, dan status ruangan.</p>
            </header>

            <form class="apple-card p-6" @submit.prevent="submit">
                <div class="space-y-5">
                    <div>
                        <label class="form-label" for="nama">Nama ruangan</label>
                        <input id="nama" v-model="form.nama" class="form-input mt-2" type="text">
                        <p v-if="form.errors.nama" class="form-error">{{ form.errors.nama }}</p>
                    </div>

                    <div>
                        <label class="form-label" for="keterangan">Keterangan</label>
                        <textarea id="keterangan" v-model="form.keterangan" class="form-input mt-2 min-h-24"></textarea>
                        <p v-if="form.errors.keterangan" class="form-error">{{ form.errors.keterangan }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link class="btn-secondary py-2 text-center" href="/admin/ruangan">Batal</Link>
                    <button class="btn-primary py-2 disabled:opacity-60" type="submit" :disabled="form.processing">Simpan perubahan</button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
