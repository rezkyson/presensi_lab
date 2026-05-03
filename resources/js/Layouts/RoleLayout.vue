<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Menu, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    navigation: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const mobileOpen = ref(false);
const user = computed(() => page.props.auth?.user);

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-zinc-100 text-zinc-950">
        <aside class="fixed inset-y-0 left-0 hidden w-72 border-r border-zinc-200 bg-white lg:flex lg:flex-col">
            <div class="border-b border-zinc-200 px-6 py-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">
                    SiHadir
                </p>
                <h1 class="mt-2 text-xl font-semibold">
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="mt-1 text-sm text-zinc-500">
                    {{ subtitle }}
                </p>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                <Link
                    v-for="item in navigation"
                    :key="item.href"
                    :href="item.href"
                    class="touch-target flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-zinc-950 focus-visible:bg-emerald-50 focus-visible:text-emerald-800"
                    :class="{ 'bg-emerald-50 text-emerald-800': $page.url.startsWith(item.href) }"
                >
                    <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
                    <span>{{ item.label }}</span>
                </Link>
            </nav>

            <div class="border-t border-zinc-200 p-4">
                <div class="mb-4">
                    <p class="text-sm font-semibold text-zinc-900">
                        {{ user?.name }}
                    </p>
                    <p class="mt-0.5 truncate text-xs text-zinc-500">
                        {{ user?.email }}
                    </p>
                </div>
                <button
                    class="touch-target flex w-full items-center justify-center gap-2 rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-100"
                    type="button"
                    @click="logout"
                >
                    <LogOut class="h-4 w-4" />
                    Keluar
                </button>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-20 border-b border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur lg:hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                            SiHadir
                        </p>
                        <p class="text-sm font-semibold">
                            {{ title }}
                        </p>
                    </div>
                    <button
                        class="touch-target rounded-md border border-zinc-300 p-2 text-zinc-800"
                        type="button"
                        aria-label="Buka navigasi"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <X v-if="mobileOpen" class="h-5 w-5" />
                        <Menu v-else class="h-5 w-5" />
                    </button>
                </div>

                <nav v-if="mobileOpen" class="mt-4 space-y-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        class="touch-target flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-zinc-700"
                        :class="{ 'bg-emerald-50 text-emerald-800': $page.url.startsWith(item.href) }"
                        @click="mobileOpen = false"
                    >
                        <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
                        <span>{{ item.label }}</span>
                    </Link>
                    <button
                        class="touch-target flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-zinc-700"
                        type="button"
                        @click="logout"
                    >
                        <LogOut class="h-4 w-4" />
                        Keluar
                    </button>
                </nav>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <slot />
            </main>
        </div>
    </div>
</template>
