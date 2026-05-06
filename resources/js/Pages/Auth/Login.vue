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

    <main class="apple-page min-h-dvh">
        <section class="mx-auto grid min-h-dvh w-full max-w-6xl items-center gap-8 px-5 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_420px] lg:px-8">
            <div class="hidden lg:block">
                <div class="max-w-xl">
                    <span class="flex h-16 w-16 items-center justify-center rounded-lg bg-zinc-950 text-white shadow-apple-soft">
                        <BookOpenCheck class="h-8 w-8" />
                    </span>
                    <p class="mt-8 text-sm font-semibold text-apple-blue">Sistem Kehadiran Digital</p>
                    <h1 class="mt-3 text-6xl font-semibold leading-tight tracking-normal text-apple-label">
                        Presensi laboratorium.
                    </h1>
                    <p class="mt-5 max-w-md text-base leading-7 text-apple-secondary">
                        Masuk ke akun untuk melanjutkan.
                    </p>
                </div>
            </div>

            <section class="w-full justify-self-center">
                <div class="apple-glass overflow-hidden p-2">
                    <div class="rounded-lg bg-white/85 px-5 py-6 sm:px-7 sm:py-7">
                        <div class="flex items-center gap-3">
                            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-zinc-950 text-white shadow-sm">
                                <BookOpenCheck class="h-6 w-6" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-zinc-950">Sistem Kehadiran Digital</p>
                                <p class="text-xs font-medium text-apple-tertiary">Presensi laboratorium</p>
                            </div>
                        </div>

                        <div class="mt-8">
                            <p class="text-sm font-semibold text-apple-blue">Login</p>
                            <h1 class="mt-2 text-4xl font-semibold tracking-normal text-zinc-950">
                                Masuk
                            </h1>
                            <p class="mt-3 text-sm leading-6 text-apple-secondary">
                                Gunakan email atau NIM yang terdaftar.
                            </p>
                        </div>
                    </div>

                    <form class="space-y-5 px-5 py-5 sm:px-7 sm:py-7" @submit.prevent="submit">
                        <div>
                            <label for="identifier" class="form-label">
                                Email atau NIM
                            </label>
                            <div
                                class="mt-2 flex items-center rounded-md border bg-white/90 shadow-sm transition focus-within:border-apple-blue focus-within:ring-4 focus-within:ring-apple-blue/15"
                                :class="form.errors.identifier ? 'border-rose-300' : 'border-zinc-300/80'"
                            >
                                <Mail class="ml-3 h-4 w-4 shrink-0 text-zinc-400" />
                                <input
                                    id="identifier"
                                    v-model="form.identifier"
                                    class="block min-h-11 w-full border-0 bg-transparent px-3 py-2 text-sm text-zinc-950 outline-none placeholder:text-zinc-400"
                                    type="text"
                                    autocomplete="username"
                                    placeholder="Email atau NIM"
                                    autofocus
                                >
                            </div>
                            <p v-if="form.errors.identifier" class="form-error mt-2">
                                {{ form.errors.identifier }}
                            </p>
                        </div>

                        <div>
                            <label for="password" class="form-label">
                                Password
                            </label>
                            <div
                                class="mt-2 flex items-center rounded-md border bg-white/90 shadow-sm transition focus-within:border-apple-blue focus-within:ring-4 focus-within:ring-apple-blue/15"
                                :class="form.errors.password ? 'border-rose-300' : 'border-zinc-300/80'"
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
                            <p v-if="form.errors.password" class="form-error mt-2">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <button
                            class="btn-primary mt-2 w-full"
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
