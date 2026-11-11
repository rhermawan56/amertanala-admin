import { http } from '@inertiajs/core';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';
import '../css/app.css';

http.setClient({ xsrfCookieName: 'AMERTANALA-XSRF' });
const appName = import.meta.env.VITE_APP_NAME || 'Amertanala Studio';
createInertiaApp({
    title: (title) => title + ' · ' + appName,
    resolve: (name) => resolvePageComponent('./pages/' + name + '.vue', import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: '#67e8f9' },
});
document.documentElement.classList.add('dark');
