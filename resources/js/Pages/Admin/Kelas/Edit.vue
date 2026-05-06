<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    kelas: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    nama_kelas: props.kelas.nama_kelas ?? '',
    prodi: props.kelas.prodi ?? '',
    semester: props.kelas.semester ?? '',
    tahun_akademik: props.kelas.tahun_akademik ?? '',
});

const submit = () => {
    form.put(`/admin/kelas/${props.kelas.id}`);
};
</script>

<template>
    <Head title="Edit Kelas" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <header class="content-hero">
                <p class="eyebrow">Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Edit kelas</h1>
                <p class="mt-2 text-sm text-zinc-600">Perbarui identitas kelas.</p>
            </header>

            <form class="apple-card p-6" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="nama_kelas">Nama kelas</label>
                        <input id="nama_kelas" v-model="form.nama_kelas" class="form-input mt-2" type="text">
                        <p v-if="form.errors.nama_kelas" class="form-error">{{ form.errors.nama_kelas }}</p>
                    </div>
                    <div>
                        <label class="form-label" for="prodi">Prodi</label>
                        <select id="prodi" v-model="form.prodi" class="form-input mt-2">
                            <option value="Teknik Informatika">Teknik Informatika</option>
                            <option value="Sistem Informasi">Sistem Informasi</option>
                        </select>
                        <p v-if="form.errors.prodi" class="form-error">{{ form.errors.prodi }}</p>
                    </div>
                    <div>
                        <label class="form-label" for="semester">Semester</label>
                        <input id="semester" v-model="form.semester" class="form-input mt-2" type="number">
                        <p v-if="form.errors.semester" class="form-error">{{ form.errors.semester }}</p>
                    </div>
                    <div>
                        <label class="form-label" for="tahun_akademik">Tahun akademik</label>
                        <input id="tahun_akademik" v-model="form.tahun_akademik" class="form-input mt-2" type="text">
                        <p v-if="form.errors.tahun_akademik" class="form-error">{{ form.errors.tahun_akademik }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link class="btn-secondary py-2 text-center" href="/admin/kelas">Batal</Link>
                    <button class="btn-primary py-2 disabled:opacity-60" type="submit" :disabled="form.processing">Simpan perubahan</button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
