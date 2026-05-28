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
    Zap,
    HeartHandshake,
    Inbox,
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
const authRoles = computed<{ id: number; name: string; slug: string }[]>(
    () => ((page.props as any).auth?.roles ?? []) as { id: number; name: string; slug: string }[],
);

const moduleEnabled = (module: string) =>
    enabledModules.value[module] !== false;
const can = (permission: string) =>
    isSuperAdmin.value || permissions.value.includes(permission);
const canAccess = (module: string, permission: string) =>
    moduleEnabled(module) && can(permission);

// Verificar si el usuario es paciente puro (solo tiene patient_portal.view sin permisos admin)
const isPatientOnly = computed(() => {
    if (isSuperAdmin.value) return false;
    if (!permissions.value.includes('patient_portal.view')) return false;
    const adminPerms = [
        'patients.view', 'sessions.view', 'appointments.view',
        'exercises.view', 'files.view', 'payments.view',
        'users.view', 'roles.view', 'settings.view', 'reports.view',
    ];
    return !adminPerms.some(p => permissions.value.includes(p));
});

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    // Paciente puro: mostrar solo Mi Portal
    if (isPatientOnly.value) {
        items.push({ title: 'Mi Portal', href: '/mi-portal', icon: HeartHandshake });
        return items;
    }

    if (canAccess('dashboard', 'dashboard.view'))
        items.push({
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        });

    // Mi Portal: también visible si tiene patient_portal.view y permisos adicionales
    if (can('patient_portal.view'))
        items.push({ title: 'Mi Portal', href: '/mi-portal', icon: HeartHandshake });

    // Mi Jornada: solo para terapeutas con permiso de citas (no superadmin)
    if (can('appointments.view') && !isSuperAdmin.value) {
        const isTherapistRole = authRoles.value?.some((r: any) =>
            ['terapeuta', 'therapist', 'fisioterapeuta'].includes((r.slug ?? '').toLowerCase())
        );
        if (isTherapistRole) {
            items.push({ title: 'Mi Jornada', href: '/mi-jornada', icon: Zap });
        }
    }

    if (canAccess('agenda', 'appointments.view'))
        items.push({ title: 'Agenda', href: '/citas', icon: CalendarDays });
    if (can('appointment_requests.view'))
        items.push({ title: 'Solicitudes', href: '/solicitudes-cita', icon: Inbox });
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
                        class="hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground"
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
