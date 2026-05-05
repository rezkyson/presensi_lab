<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { BookOpenCheck, Eye, EyeOff, LockKeyhole, LogIn, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
    identifier: '',
    password: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />

    <main class="min-h-screen bg-zinc-100 text-zinc-950">
        <section class="mx-auto grid min-h-screen w-full max-w-6xl items-center gap-10 px-5 py-8 sm:px-6 lg:grid-cols-[1fr_450px] lg:px-8">
            <div class="hidden lg:block">
                <div class="max-w-lg">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-md border border-emerald-200 bg-white text-emerald-700 shadow-sm">
                            <BookOpenCheck class="h-6 w-6" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">
                                Sistem Kehadiran Digital
                            </p>
                            <p class="text-sm text-zinc-500">Presensi laboratorium</p>
                        </div>
                    </div>

                    <div class="mt-16 border-l border-zinc-300 pl-8">
                        <h1 class="max-w-md text-4xl font-semibold leading-tight text-zinc-950">
                            Masuk untuk melanjutkan presensi.
                        </h1>
                        <p class="mt-4 max-w-sm text-base leading-7 text-zinc-600">
                            Akses akun admin, dosen, dan mahasiswa melalui satu halaman yang sama.
                        </p>
                    </div>

                    <div class="mt-16 grid max-w-md grid-cols-3 border-y border-zinc-200 text-sm">
                        <div class="py-4 pr-4">
                            <p class="font-semibold text-zinc-950">Admin</p>
                            <p class="mt-1 text-zinc-500">Data</p>
                        </div>
                        <div class="border-x border-zinc-200 px-4 py-4">
                            <p class="font-semibold text-zinc-950">Dosen</p>
                            <p class="mt-1 text-zinc-500">Sesi</p>
                        </div>
                        <div class="py-4 pl-4">
                            <p class="font-semibold text-zinc-950">Mahasiswa</p>
                            <p class="mt-1 text-zinc-500">Presensi</p>
                        </div>
                    </div>
                </div>
            </div>

            <section class="w-full">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex h-11 w-11 items-center justify-center rounded-md border border-emerald-200 bg-white text-emerald-700 shadow-sm">
                        <BookOpenCheck class="h-6 w-6" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">
                            Sistem Kehadiran Digital
                        </p>
                        <p class="text-sm text-zinc-500">Presensi laboratorium</p>
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
                    <div class="border-b border-zinc-200 px-6 py-6 sm:px-8">
                        <div class="hidden items-center gap-3 lg:flex">
                            <span class="flex h-10 w-10 items-center justify-center rounded-md bg-zinc-950 text-white">
                                <BookOpenCheck class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-zinc-950">Sistem Kehadiran Digital</p>
                                <p class="text-xs text-zinc-500">Presensi laboratorium</p>
                            </div>
                        </div>

                        <div class="mt-0 lg:mt-8">
                            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">
                                Login
                            </p>
                            <h1 class="mt-2 text-2xl font-semibold text-zinc-950">
                                Masuk ke akun
                            </h1>
                            <p class="mt-2 text-sm leading-6 text-zinc-600">
                                Gunakan email atau NIM yang sudah terdaftar.
                            </p>
                        </div>
                    </div>

                    <form class="space-y-5 px-6 py-6 sm:px-8 sm:py-8" @submit.prevent="submit">
                        <div>
                            <label for="identifier" class="block text-sm font-medium text-zinc-800">
                                Email atau NIM
                            </label>
                            <div
                                class="mt-2 flex items-center rounded-md border bg-white transition focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/15"
                                :class="form.errors.identifier ? 'border-rose-300' : 'border-zinc-300'"
                            >
                                <Mail class="ml-3 h-4 w-4 shrink-0 text-zinc-400" />
                                <input
                                    id="identifier"
                                    v-model="form.identifier"
                                    class="block min-h-11 w-full border-0 bg-transparent px-3 py-2 text-sm text-zinc-950 outline-none placeholder:text-zinc-400"
                                    type="text"
                                    autocomplete="username"
                                    placeholder="admin@kehadiran-digital.local"
                                    autofocus
                                >
                            </div>
                            <p v-if="form.errors.identifier" class="mt-2 text-sm font-medium text-rose-600">
                                {{ form.errors.identifier }}
                            </p>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-zinc-800">
                                Password
                            </label>
                            <div
                                class="mt-2 flex items-center rounded-md border bg-white transition focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/15"
                                :class="form.errors.password ? 'border-rose-300' : 'border-zinc-300'"
                            >
                                <LockKeyhole class="ml-3 h-4 w-4 shrink-0 text-zinc-400" />
                                <input
                                    id="password"
                                    v-model="form.password"
                                    class="block min-h-11 w-full border-0 bg-transparent px-3 py-2 text-sm text-zinc-950 outline-none placeholder:text-zinc-400"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    placeholder="Masukkan password"
                                >
                                <button
                                    class="mr-2 flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800"
                                    type="button"
                                    :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeOff v-if="showPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-2 text-sm font-medium text-rose-600">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <button
                            class="flex min-h-11 w-full items-center justify-center gap-2 rounded-md bg-zinc-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                            type="submit"
                            :disabled="form.processing"
                        >
                            <LogIn class="h-4 w-4" />
                            <span>{{ form.processing ? 'Memproses...' : 'Masuk' }}</span>
                        </button>
                    </form>
                </div>
            </section>
        </section>
    </main>
</template>
