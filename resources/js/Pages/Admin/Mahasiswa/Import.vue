<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { FileUp, Upload } from 'lucide-vue-next';

defineProps({
    importResult: {
        type: Object,
        default: null,
    },
    format: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const form = useForm({
    file: null,
    default_password: 'password',
});

const submit = () => {
    form.post('/admin/mahasiswa/import', {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Import Mahasiswa" />

    <AdminLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Admin</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-950">Import mahasiswa</h1>
                    <p class="mt-2 text-sm text-zinc-600">
                        Upload CSV atau Excel dengan header yang sudah ditentukan.
                    </p>
                </div>
                <Link class="rounded-md border border-zinc-300 px-4 py-2.5 text-center text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50" href="/admin/mahasiswa">
                    Kembali
                </Link>
            </header>

            <div
                v-if="page.props.flash?.success"
                class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            >
                {{ page.props.flash.success }}
            </div>

            <section class="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
                <form class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div class="rounded-md border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center">
                        <FileUp class="mx-auto h-8 w-8 text-zinc-500" />
                        <label class="mt-4 block text-sm font-semibold text-zinc-900" for="file">File CSV/Excel</label>
                        <input
                            id="file"
                            class="mt-3 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm"
                            type="file"
                            accept=".csv,.txt,.xlsx,.xls"
                            @input="form.file = $event.target.files[0]"
                        >
                        <p v-if="form.errors.file" class="mt-2 text-sm text-red-600">{{ form.errors.file }}</p>
                    </div>

                    <label class="mt-5 block text-sm font-medium text-zinc-800" for="default_password">
                        Password default
                    </label>
                    <input
                        id="default_password"
                        v-model="form.default_password"
                        class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                        type="text"
                    >
                    <p v-if="form.errors.default_password" class="mt-2 text-sm text-red-600">{{ form.errors.default_password }}</p>

                    <button
                        class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60"
                        type="submit"
                        :disabled="form.processing"
                    >
                        <Upload class="h-4 w-4" />
                        Proses import
                    </button>
                </form>

                <aside class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-zinc-950">Format file</h2>
                    <p class="mt-2 text-sm text-zinc-600">
                        Baris pertama wajib berisi header berikut.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            v-for="item in format"
                            :key="item"
                            class="rounded-full bg-zinc-100 px-3 py-1 text-sm font-semibold text-zinc-700"
                        >
                            {{ item }}
                        </span>
                    </div>
                    <div class="mt-5 rounded-md bg-zinc-50 p-4 text-sm text-zinc-600">
                        <p class="font-semibold text-zinc-800">Contoh kelas</p>
                        <p class="mt-1">Isi `kelas` dengan nama kelas yang sudah ada, misalnya `IF-1A`. Untuk banyak kelas: `IF-1A, IF-1B`.</p>
                    </div>
                </aside>
            </section>

            <section v-if="importResult" class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-zinc-950">Ringkasan import</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <article class="rounded-md bg-emerald-50 p-4 text-emerald-900">
                        <p class="text-sm font-medium">Berhasil</p>
                        <p class="mt-2 text-3xl font-semibold">{{ importResult.success }}</p>
                    </article>
                    <article class="rounded-md bg-rose-50 p-4 text-rose-900">
                        <p class="text-sm font-medium">Gagal</p>
                        <p class="mt-2 text-3xl font-semibold">{{ importResult.failed }}</p>
                    </article>
                </div>

                <div v-if="importResult.failures?.length" class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Baris</th>
                                <th class="px-4 py-3">NIM</th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Error</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="failure in importResult.failures" :key="failure.row">
                                <td class="px-4 py-3 font-semibold text-zinc-950">{{ failure.row }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ failure.values.nim }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ failure.values.nama }}</td>
                                <td class="px-4 py-3 text-rose-700">{{ failure.errors.join(' ') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
