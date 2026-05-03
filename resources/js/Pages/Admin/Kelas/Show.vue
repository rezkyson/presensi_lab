<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';

const props = defineProps({
    kelas: {
        type: Object,
        required: true,
    },
    mahasiswaOptions: {
        type: Array,
        default: () => [],
    },
    dosenOptions: {
        type: Array,
        default: () => [],
    },
});

const mahasiswaForm = useForm({
    mahasiswa_id: '',
});

const dosenForm = useForm({
    dosen_id: '',
    mata_kuliah: '',
});

const attachMahasiswa = () => {
    mahasiswaForm.post(`/admin/kelas/${props.kelas.id}/mahasiswa`, {
        preserveScroll: true,
        onSuccess: () => mahasiswaForm.reset(),
    });
};

const detachMahasiswa = (mahasiswa) => {
    if (confirm(`Hapus ${mahasiswa.name} dari kelas ini?`)) {
        router.delete(`/admin/kelas/${props.kelas.id}/mahasiswa/${mahasiswa.id}`, { preserveScroll: true });
    }
};

const attachDosen = () => {
    dosenForm.post(`/admin/kelas/${props.kelas.id}/dosen`, {
        preserveScroll: true,
        onSuccess: () => dosenForm.reset(),
    });
};

const detachDosen = (dosen) => {
    if (confirm(`Hapus pengampu ${dosen.name} untuk ${dosen.mata_kuliah}?`)) {
        router.delete(`/admin/kelas/${props.kelas.id}/dosen/${dosen.pivot_id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="`Detail ${kelas.nama_kelas}`" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Detail kelas</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">{{ kelas.nama_kelas }}</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        {{ kelas.prodi }} · Semester {{ kelas.semester }} · {{ kelas.tahun_akademik }}
                    </p>
                </div>
                <Link
                    class="inline-flex items-center justify-center rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100"
                    href="/admin/kelas"
                >
                    Kembali
                </Link>
            </header>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-600">Mahasiswa</p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ kelas.mahasiswa_count ?? kelas.mahasiswa.length }}</p>
                </article>
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-600">Dosen pengampu</p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ kelas.dosen_count ?? kelas.dosen.length }}</p>
                </article>
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-600">Jadwal</p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ kelas.jadwal_count ?? 0 }}</p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-zinc-950">Peserta mahasiswa</h2>
                    <form class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]" @submit.prevent="attachMahasiswa">
                        <select
                            v-model="mahasiswaForm.mahasiswa_id"
                            class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                        >
                            <option value="">Pilih mahasiswa</option>
                            <option v-for="mahasiswa in mahasiswaOptions" :key="mahasiswa.id" :value="mahasiswa.id">
                                {{ mahasiswa.label }}
                            </option>
                        </select>
                        <button
                            class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60"
                            type="submit"
                            :disabled="mahasiswaForm.processing"
                        >
                            Tambah
                        </button>
                        <p v-if="mahasiswaForm.errors.mahasiswa_id" class="text-sm text-red-600 sm:col-span-2">
                            {{ mahasiswaForm.errors.mahasiswa_id }}
                        </p>
                    </form>

                    <div v-if="kelas.mahasiswa.length" class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500">
                                <tr>
                                    <th class="py-3 pr-4 font-semibold">Mahasiswa</th>
                                    <th class="py-3 pr-4 font-semibold">Prodi</th>
                                    <th class="py-3 text-right font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <tr v-for="mahasiswa in kelas.mahasiswa" :key="mahasiswa.id">
                                    <td class="py-3 pr-4">
                                        <p class="font-semibold text-zinc-950">{{ mahasiswa.name }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ mahasiswa.nim }}</p>
                                    </td>
                                    <td class="py-3 pr-4 text-zinc-600">{{ mahasiswa.prodi }}</td>
                                    <td class="py-3 text-right">
                                        <button class="rounded-md border border-red-200 p-2 text-red-700 transition hover:bg-red-50" type="button" @click="detachMahasiswa(mahasiswa)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="mt-5 rounded-md border border-dashed border-zinc-300 p-6 text-sm text-zinc-500">
                        Belum ada mahasiswa di kelas ini.
                    </div>
                </article>

                <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-zinc-950">Dosen pengampu</h2>
                    <form class="mt-4 grid gap-3" @submit.prevent="attachDosen">
                        <select
                            v-model="dosenForm.dosen_id"
                            class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                        >
                            <option value="">Pilih dosen</option>
                            <option v-for="dosen in dosenOptions" :key="dosen.id" :value="dosen.id">
                                {{ dosen.label }}
                            </option>
                        </select>
                        <input
                            v-model="dosenForm.mata_kuliah"
                            class="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                            placeholder="Mata kuliah"
                            type="text"
                        >
                        <button
                            class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60"
                            type="submit"
                            :disabled="dosenForm.processing"
                        >
                            Tambah pengampu
                        </button>
                        <p v-if="dosenForm.errors.dosen_id" class="text-sm text-red-600">{{ dosenForm.errors.dosen_id }}</p>
                        <p v-if="dosenForm.errors.mata_kuliah" class="text-sm text-red-600">{{ dosenForm.errors.mata_kuliah }}</p>
                    </form>

                    <div v-if="kelas.dosen.length" class="mt-5 space-y-3">
                        <div
                            v-for="dosen in kelas.dosen"
                            :key="dosen.pivot_id"
                            class="flex items-start justify-between gap-4 rounded-md border border-zinc-200 p-4"
                        >
                            <div>
                                <p class="font-semibold text-zinc-950">{{ dosen.name }}</p>
                                <p class="mt-1 text-sm text-zinc-600">{{ dosen.mata_kuliah }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ dosen.nip }}</p>
                            </div>
                            <button class="rounded-md border border-red-200 p-2 text-red-700 transition hover:bg-red-50" type="button" @click="detachDosen(dosen)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <div v-else class="mt-5 rounded-md border border-dashed border-zinc-300 p-6 text-sm text-zinc-500">
                        Belum ada dosen pengampu untuk kelas ini.
                    </div>
                </article>
            </section>
        </div>
    </AdminLayout>
</template>
