import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const applyThemeColors = (settings: Record<string, string | null> | undefined) => {
    if (!settings) return;

    const root = document.documentElement;
    if (settings.primary_color) { root.style.setProperty('--color-primary', settings.primary_color); root.style.setProperty('--primary', settings.primary_color); }
    if (settings.secondary_color) { root.style.setProperty('--color-secondary', settings.secondary_color); root.style.setProperty('--secondary', settings.secondary_color); }
    if (settings.accent_color) { root.style.setProperty('--color-accent', settings.accent_color); root.style.setProperty('--accent', settings.accent_color); }
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        applyThemeColors((props as any).initialPage?.props?.appSettings);

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
