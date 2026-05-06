<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    options: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    kelas_id: '',
    dosen_id: '',
    mata_kuliah: '',
    hari: 'Senin',
    jam_mulai: '08:00',
    jam_selesai: '09:40',
    ruangan: '',
});

const hasErrors = computed(() => Object.keys(form.errors).length > 0);

const submit = () => {
    form.post('/admin/jadwal');
};
</script>

<template>
    <Head title="Tambah Jadwal" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <header class="content-hero">
                <p class="eyebrow">Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Tambah jadwal</h1>
                <p class="mt-2 text-sm text-zinc-600">Sistem akan menolak jadwal yang bentrok.</p>
            </header>

            <form class="apple-card p-6" @submit.prevent="submit">
                <div
                    v-if="hasErrors"
                    class="mb-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800"
                >
                    Jadwal belum bisa disimpan. Periksa data yang bentrok atau belum valid.
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="kelas">Kelas</label>
                        <select id="kelas" v-model="form.kelas_id" class="form-input mt-2">
                            <option value="">Pilih kelas</option>
                            <option v-for="kelas in options.kelas" :key="kelas.id" :value="kelas.id">{{ kelas.label }}</option>
                        </select>
                        <p v-if="form.errors.kelas_id" class="form-error">{{ form.errors.kelas_id }}</p>
                    </div>
                    <div>
                        <label class="form-label" for="dosen">Dosen</label>
                        <select id="dosen" v-model="form.dosen_id" class="form-input mt-2">
                            <option value="">Pilih dosen</option>
                            <option v-for="dosen in options.dosen" :key="dosen.id" :value="dosen.id">{{ dosen.label }}</option>
                        </select>
                        <p v-if="form.errors.dosen_id" class="form-error">{{ form.errors.dosen_id }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label" for="mata_kuliah">Mata kuliah</label>
                        <input id="mata_kuliah" v-model="form.mata_kuliah" class="form-input mt-2" type="text">
                        <p v-if="form.errors.mata_kuliah" class="form-error">{{ form.errors.mata_kuliah }}</p>
                    </div>
                    <div>
                        <label class="form-label" for="hari">Hari</label>
                        <select id="hari" v-model="form.hari" class="form-input mt-2">
                            <option v-for="day in options.days" :key="day" :value="day">{{ day }}</option>
                        </select>
                        <p v-if="form.errors.hari" class="form-error">{{ form.errors.hari }}</p>
                    </div>
                    <div>
                        <label class="form-label" for="ruangan">Ruangan</label>
                        <select id="ruangan" v-model="form.ruangan" class="form-input mt-2">
                            <option value="">Pilih ruangan</option>
                            <option v-for="ruangan in options.ruangan" :key="ruangan.id" :value="ruangan.nama">{{ ruangan.label }}</option>
                        </select>
                        <p v-if="form.errors.ruangan" class="form-error">{{ form.errors.ruangan }}</p>
                    </div>
                    <div>
                        <label class="form-label" for="jam_mulai">Jam mulai</label>
                        <input id="jam_mulai" v-model="form.jam_mulai" class="form-input mt-2" type="time">
                        <p v-if="form.errors.jam_mulai" class="form-error">{{ form.errors.jam_mulai }}</p>
                    </div>
                    <div>
                        <label class="form-label" for="jam_selesai">Jam selesai</label>
                        <input id="jam_selesai" v-model="form.jam_selesai" class="form-input mt-2" type="time">
                        <p v-if="form.errors.jam_selesai" class="form-error">{{ form.errors.jam_selesai }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link class="btn-secondary py-2 text-center" href="/admin/jadwal">Batal</Link>
                    <button class="btn-primary py-2 disabled:opacity-60" type="submit" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
