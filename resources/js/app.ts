import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme, resolveTheme } from './composables/useAppearance';
import type { Appearance } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'FisioVida';

type Settings = Record<string, string | null> | undefined;

// Tokens que solo aplican en light mode (configurados por el usuario)
const LIGHT_BACKGROUND_PROPS = [
    '--background',
    '--color-background',
    '--card',
    '--color-card',
    '--sidebar-background',
    '--sidebar',
    '--color-sidebar',
] as const;

function applyPrimaryColors(settings: Record<string, string | null>): void {
    const root = document.documentElement;

    if (settings.primary_color) {
        root.style.setProperty('--primary', settings.primary_color);
        root.style.setProperty('--color-primary', settings.primary_color);
        root.style.setProperty('--ring', settings.primary_color);
        root.style.setProperty('--color-ring', settings.primary_color);
        root.style.setProperty('--sidebar-primary', settings.primary_color);
        root.style.setProperty('--sidebar-ring', settings.primary_color);
    }

    if (settings.primary_hover_color) {
        root.style.setProperty('--primary-hover', settings.primary_hover_color);
        root.style.setProperty('--color-primary-hover', settings.primary_hover_color);
    }

    if (settings.primary_foreground_color) {
        root.style.setProperty('--primary-foreground', settings.primary_foreground_color);
        root.style.setProperty('--color-primary-foreground', settings.primary_foreground_color);
        root.style.setProperty('--sidebar-primary-foreground', settings.primary_foreground_color);
    }
}

// En light mode se aplican los colores configurados por el usuario para fondos
function applyLightThemeColors(settings: Record<string, string | null>): void {
    const root = document.documentElement;

    applyPrimaryColors(settings);

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
        root.style.setProperty('--sidebar', settings.sidebar_background_color);
        root.style.setProperty('--color-sidebar', settings.sidebar_background_color);
    }
}

// En dark mode se eliminan los overrides de fondos del usuario para que
// el bloque .dark del CSS aplique su paleta premium sin ser pisada
function applyDarkThemeColors(settings: Record<string, string | null>): void {
    const root = document.documentElement;

    LIGHT_BACKGROUND_PROPS.forEach((prop) => root.style.removeProperty(prop));

    // Solo se respeta el color primario del usuario como acento
    applyPrimaryColors(settings);
}

function applyThemeColors(settings: Settings, resolvedTheme: 'light' | 'dark'): void {
    if (!settings) return;

    if (resolvedTheme === 'dark') {
        applyDarkThemeColors(settings);
    } else {
        applyLightThemeColors(settings);
    }
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const settings = (props as any).initialPage?.props?.appSettings as Settings;

        let adminDefault: 'dark' | 'light' | undefined;
        if (settings?.dark_mode_enabled === '1') adminDefault = 'dark';
        else if (settings?.dark_mode_enabled === '0') adminDefault = 'light';

        // 1. Determinar tema resuelto antes de tocar el DOM
        const savedAppearance = (typeof window !== 'undefined'
            ? localStorage.getItem('appearance')
            : null) as Appearance | null;
        const effectiveAppearance: Appearance = savedAppearance ?? adminDefault ?? 'system';
        const resolvedTheme = resolveTheme(effectiveAppearance);

        // 2. Aplicar clase .dark primero
        document.documentElement.classList.toggle('dark', resolvedTheme === 'dark');

        // 3. Aplicar tokens según tema resuelto (sin flash de colores claros)
        applyThemeColors(settings, resolvedTheme);

        // 4. Escuchar cambios futuros de tema (usuario cambia modo o OS cambia)
        window.addEventListener('appearance:changed', (event: Event) => {
            const { theme } = (event as CustomEvent<{ theme: 'light' | 'dark' }>).detail;
            applyThemeColors(settings, theme);
        });

        // 5. Registrar listener del media query del sistema (llama updateTheme internamente)
        initializeTheme(adminDefault);

        // 6. Montar Vue
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: 'var(--primary)',
    },
});
