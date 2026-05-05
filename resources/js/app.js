import './bootstrap';

import '../css/app.css';
import GlobalFeedback from '@/Components/GlobalFeedback.vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';

createInertiaApp({
    title: (title) => (title ? `${title} - Sistem Kehadiran Digital` : 'Sistem Kehadiran Digital'),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => [h(App, props), h(GlobalFeedback)] })
            .use(plugin)
            .mount(el);
    },
});
