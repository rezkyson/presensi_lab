<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { BookOpenCheck, LogOut, Menu, X } from 'lucide-vue-next';
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
    mobileNavigation: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const mobileOpen = ref(false);
const user = computed(() => page.props.auth?.user);
const userInitials = computed(() => (user.value?.name ?? 'U')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join(''));
const isActive = (href) => page.url.startsWith(href);

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="apple-page">
        <div class="mx-auto flex min-h-screen w-full max-w-[1480px] flex-col lg:grid lg:h-dvh lg:min-h-0 lg:grid-cols-[18rem_minmax(0,1fr)] lg:items-stretch lg:gap-6 lg:overflow-hidden lg:p-4">
            <aside class="hidden min-h-0 w-full lg:block">
                <div class="apple-glass flex h-full min-h-0 flex-col overflow-hidden">
                    <div class="shrink-0 px-4 py-4">
                        <div class="rounded-lg bg-zinc-950 p-4 text-white shadow-apple-soft">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-white text-zinc-950">
                                    <BookOpenCheck class="h-5 w-5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">Sistem Kehadiran Digital</p>
                                    <p class="text-xs font-medium text-white/60">Presensi laboratorium</p>
                                </div>
                            </div>
                            <div class="mt-5">
                                <p class="text-xs font-semibold text-white/50">Workspace</p>
                                <h1 class="mt-1 text-2xl font-semibold tracking-normal">
                                    {{ title }}
                                </h1>
                                <p v-if="subtitle" class="mt-2 text-sm leading-6 text-white/65">
                                    {{ subtitle }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <nav class="min-h-0 flex-1 space-y-1.5 overflow-y-auto px-3 pb-3 pr-2 [scrollbar-width:thin]">
                        <Link
                            v-for="item in navigation"
                            :key="item.href"
                            :href="item.href"
                            class="touch-target flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold transition"
                            :class="isActive(item.href) ? 'bg-white text-apple-blue shadow-sm' : 'text-zinc-600 hover:bg-white/70 hover:text-zinc-950'"
                        >
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                :class="isActive(item.href) ? 'bg-apple-blue text-white' : 'bg-zinc-100 text-zinc-500'"
                            >
                                <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
                            </span>
                            <span>{{ item.label }}</span>
                        </Link>
                    </nav>

                    <div class="shrink-0 border-t border-black/5 p-4">
                        <button
                            class="mb-3 flex w-full items-center gap-3 rounded-lg bg-white/80 p-3 text-left shadow-sm transition hover:bg-white"
                            type="button"
                            aria-label="Keluar dari akun"
                            @click="logout"
                        >
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-apple-blue text-sm font-semibold text-white shadow-sm">
                                {{ userInitials }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-zinc-950">
                                    {{ user?.name }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-apple-tertiary">
                                    {{ user?.email }} &middot; Keluar
                                </p>
                            </div>
                        </button>
                        <button
                            class="btn-secondary w-full"
                            type="button"
                            @click="logout"
                        >
                            <LogOut class="h-4 w-4" />
                            Keluar
                        </button>
                    </div>
                </div>
            </aside>

            <div class="min-w-0 lg:flex lg:h-full lg:min-h-0 lg:flex-col lg:overflow-hidden">
            <div class="hidden shrink-0 pb-4 lg:block">
                <div class="apple-glass flex items-center justify-between px-4 py-3">
                    <div>
                        <p class="text-xs font-semibold text-apple-tertiary">Sistem Kehadiran Digital</p>
                        <p class="text-sm font-semibold text-apple-label">{{ title }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-right transition hover:bg-white/70"
                            type="button"
                            aria-label="Keluar dari akun"
                            @click="logout"
                        >
                            <div>
                            <p class="text-sm font-semibold text-zinc-950">{{ user?.name }}</p>
                                <p class="text-xs text-apple-tertiary">{{ user?.email }} &middot; Keluar</p>
                            </div>
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-950 text-sm font-semibold text-white">
                                {{ userInitials }}
                            </span>
                        </button>
                        <button
                            class="icon-button"
                            type="button"
                            aria-label="Keluar"
                            title="Keluar"
                            @click="logout"
                        >
                            <LogOut class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <header class="border-b border-black/5 bg-white/85 px-4 py-3 shadow-sm backdrop-blur-2xl lg:hidden">
                <div class="flex items-center justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-950 text-white shadow-sm">
                            <BookOpenCheck class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-apple-label">
                                Sistem Kehadiran Digital
                            </p>
                            <p class="truncate text-xs font-medium text-apple-secondary">
                                {{ title }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            class="icon-button"
                            type="button"
                            aria-label="Keluar"
                            title="Keluar"
                            @click="logout"
                        >
                            <LogOut class="h-5 w-5" />
                        </button>
                        <button
                            class="icon-button"
                            type="button"
                            aria-label="Buka navigasi"
                            @click="mobileOpen = !mobileOpen"
                        >
                            <X v-if="mobileOpen" class="h-5 w-5" />
                            <Menu v-else class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <nav v-if="mobileOpen" class="mt-4 space-y-1.5 rounded-lg border border-black/5 bg-white p-2 shadow-apple-soft">
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        class="touch-target flex items-center gap-3 rounded-md px-3 py-2 text-sm font-semibold"
                        :class="isActive(item.href) ? 'bg-apple-blue-50 text-apple-blue' : 'text-zinc-700'"
                        @click="mobileOpen = false"
                    >
                        <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
                        <span>{{ item.label }}</span>
                    </Link>
                    <button
                        class="touch-target flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-semibold text-zinc-700"
                        type="button"
                        @click="logout"
                    >
                        <LogOut class="h-4 w-4" />
                        Keluar
                    </button>
                </nav>
            </header>

            <main
                class="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:px-6 lg:pb-8 lg:pt-2 lg:[scrollbar-width:thin]"
                :class="mobileNavigation ? 'pb-32 lg:pb-8' : ''"
            >
                <slot />
            </main>

            <nav
                v-if="mobileNavigation"
                class="ios-fixed-bottom-nav fixed inset-x-0 z-40 mx-auto grid w-[calc(100%-2rem)] max-w-md grid-cols-5 gap-1 rounded-lg border border-black/5 bg-white/85 p-2 shadow-apple-card backdrop-blur-2xl lg:hidden"
            >
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg px-1 text-[11px] font-semibold transition"
                        :class="isActive(item.href) ? 'bg-apple-blue-50 text-apple-blue' : 'text-zinc-500 hover:text-zinc-950'"
                    >
                        <component :is="item.icon" v-if="item.icon" class="h-5 w-5" />
                        <span class="truncate">{{ item.label }}</span>
                    </Link>
            </nav>
            </div>
        </div>
    </div>
</template>
