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

    if (settings.primary_color) {
        root.style.setProperty('--primary', settings.primary_color);
        root.style.setProperty('--color-primary', settings.primary_color);
        root.style.setProperty('--ring', settings.primary_color);
        root.style.setProperty('--color-ring', settings.primary_color);
    }

    if (settings.primary_hover_color) {
        root.style.setProperty('--primary-hover', settings.primary_hover_color);
    }

    if (settings.primary_foreground_color) {
        root.style.setProperty('--primary-foreground', settings.primary_foreground_color);
        root.style.setProperty('--color-primary-foreground', settings.primary_foreground_color);
    }

    if (settings.app_background_color) {
        root.style.setProperty('--background', settings.app_background_color);
        root.style.setProperty('--color-background', settings.app_background_color);
    }

    if (settings.card_background_color) {
        root.style.setProperty('--card', settings.card_background_color);
        root.style.setProperty('--color-card', settings.card_background_color);
    }

    if (settings.sidebar_background_color) {
        root.style.setProperty('--sidebar-background', settings.sidebar_background_color);
        root.style.setProperty('--color-sidebar', settings.sidebar_background_color);
    }
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
