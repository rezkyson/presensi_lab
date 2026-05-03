<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    identifier: '',
    password: '',
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />

    <main class="flex min-h-screen items-center justify-center bg-zinc-950 px-6 py-12 text-zinc-950">
        <section class="w-full max-w-md rounded-lg bg-white p-8 shadow-xl shadow-black/20">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">
                    SiHadir
                </p>
                <h1 class="mt-2 text-2xl font-semibold text-zinc-950">
                    Masuk ke akun
                </h1>
            </div>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <div>
                    <label for="identifier" class="block text-sm font-medium text-zinc-800">
                        Email atau NIM
                    </label>
                    <input
                        id="identifier"
                        v-model="form.identifier"
                        class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                        type="text"
                        autocomplete="username"
                        autofocus
                    >
                    <p v-if="form.errors.identifier" class="mt-2 text-sm text-red-600">
                        {{ form.errors.identifier }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-zinc-800">
                        Password
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                        type="password"
                        autocomplete="current-password"
                    >
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <button
                    class="w-full rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60"
                    type="submit"
                    :disabled="form.processing"
                >
                    Masuk
                </button>
            </form>
        </section>
    </main>
</template>
