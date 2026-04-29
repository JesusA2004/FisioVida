<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    CalendarDays,
    Users,
    ClipboardList,
    Dumbbell,
    FileText,
    BarChart3,
    CreditCard,
    Settings,
    ShieldCheck,
    ListTodo,
    KeyRound,
} from 'lucide-vue-next';

import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();

const isSuperAdmin = computed(() =>
    Boolean((page.props as any).auth?.is_super_admin),
);
const permissions = computed<string[]>(
    () => ((page.props as any).auth?.permissions ?? []) as string[],
);
const enabledModules = computed<Record<string, boolean>>(
    () => ((page.props as any).enabledModules ?? {}) as Record<string, boolean>,
);

const moduleEnabled = (module: string) =>
    enabledModules.value[module] !== false;
const can = (permission: string) =>
    isSuperAdmin.value || permissions.value.includes(permission);
const canAccess = (module: string, permission: string) =>
    moduleEnabled(module) && can(permission);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (canAccess('dashboard', 'dashboard.view'))
        items.push({
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        });
    if (canAccess('agenda', 'appointments.view'))
        items.push({ title: 'Agenda', href: '/citas', icon: CalendarDays });
    if (canAccess('pacientes', 'patients.view'))
        items.push({ title: 'Pacientes', href: '/pacientes', icon: Users });
    if (canAccess('sesiones', 'sessions.view'))
        items.push({
            title: 'Sesiones',
            href: '/sesiones',
            icon: ClipboardList,
        });
    if (canAccess('ejercicios', 'exercises.view'))
        items.push({
            title: 'Ejercicios',
            href: '/ejercicios',
            icon: Dumbbell,
        });
    if (canAccess('archivos', 'files.view'))
        items.push({ title: 'Archivos', href: '/archivos', icon: FileText });
    if (canAccess('pagos', 'payments.view'))
        items.push({ title: 'Cobranza', href: '/pagos', icon: CreditCard });
    if (canAccess('reportes', 'reports.view'))
        items.push({ title: 'Reportes', href: '/reportes', icon: BarChart3 });
    if (canAccess('actividades', 'activities.view'))
        items.push({
            title: 'Actividades',
            href: '/actividades',
            icon: ListTodo,
        });
    if (canAccess('roles', 'roles.view'))
        items.push({ title: 'Roles', href: '/roles', icon: ShieldCheck });
    if (canAccess('permisos', 'permissions.view'))
        items.push({ title: 'Permisos', href: '/permisos', icon: KeyRound });
    if (canAccess('usuarios', 'users.view'))
        items.push({ title: 'Usuarios', href: '/usuarios', icon: Users });
    if (canAccess('logs', 'logs.view'))
        items.push({ title: 'Bitácora', href: '/logs', icon: BarChart3 });
    if (canAccess('configuracion', 'settings.view'))
        items.push({
            title: 'Configuración',
            href: '/configuracion',
            icon: Settings,
        });

    return items;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="hover:bg-white hover:text-sidebar-accent-foreground data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground"
                    >
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
