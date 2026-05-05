<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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

const submit = () => {
    form.post('/admin/jadwal');
};
</script>

<template>
    <Head title="Tambah Jadwal" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <header>
                <p class="text-sm font-medium text-emerald-700">Admin</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Tambah jadwal</h1>
                <p class="mt-2 text-sm text-zinc-600">Sistem akan menolak jadwal yang bentrok.</p>
            </header>

            <form class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="kelas">Kelas</label>
                        <select id="kelas" v-model="form.kelas_id" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            <option value="">Pilih kelas</option>
                            <option v-for="kelas in options.kelas" :key="kelas.id" :value="kelas.id">{{ kelas.label }}</option>
                        </select>
                        <p v-if="form.errors.kelas_id" class="mt-1 text-sm text-red-600">{{ form.errors.kelas_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="dosen">Dosen</label>
                        <select id="dosen" v-model="form.dosen_id" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            <option value="">Pilih dosen</option>
                            <option v-for="dosen in options.dosen" :key="dosen.id" :value="dosen.id">{{ dosen.label }}</option>
                        </select>
                        <p v-if="form.errors.dosen_id" class="mt-1 text-sm text-red-600">{{ form.errors.dosen_id }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-800" for="mata_kuliah">Mata kuliah</label>
                        <input id="mata_kuliah" v-model="form.mata_kuliah" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="text">
                        <p v-if="form.errors.mata_kuliah" class="mt-1 text-sm text-red-600">{{ form.errors.mata_kuliah }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="hari">Hari</label>
                        <select id="hari" v-model="form.hari" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            <option v-for="day in options.days" :key="day" :value="day">{{ day }}</option>
                        </select>
                        <p v-if="form.errors.hari" class="mt-1 text-sm text-red-600">{{ form.errors.hari }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="ruangan">Ruangan</label>
                        <select id="ruangan" v-model="form.ruangan" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                            <option value="">Pilih ruangan</option>
                            <option v-for="ruangan in options.ruangan" :key="ruangan.id" :value="ruangan.nama">{{ ruangan.label }}</option>
                        </select>
                        <p v-if="form.errors.ruangan" class="mt-1 text-sm text-red-600">{{ form.errors.ruangan }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="jam_mulai">Jam mulai</label>
                        <input id="jam_mulai" v-model="form.jam_mulai" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="time">
                        <p v-if="form.errors.jam_mulai" class="mt-1 text-sm text-red-600">{{ form.errors.jam_mulai }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-800" for="jam_selesai">Jam selesai</label>
                        <input id="jam_selesai" v-model="form.jam_selesai" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20" type="time">
                        <p v-if="form.errors.jam_selesai" class="mt-1 text-sm text-red-600">{{ form.errors.jam_selesai }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link class="rounded-md border border-zinc-300 px-4 py-2 text-center text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100" href="/admin/jadwal">Batal</Link>
                    <button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60" type="submit" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
