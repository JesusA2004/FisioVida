<script setup lang="ts">
    import { Link } from '@inertiajs/vue3'
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
    } from 'lucide-vue-next'

    import NavFooter from '@/components/NavFooter.vue'
    import NavMain from '@/components/NavMain.vue'
    import NavUser from '@/components/NavUser.vue'

    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar'

    import { type NavItem } from '@/types'
    import AppLogo from './AppLogo.vue'
    import { dashboard } from '@/routes'

    /**
     * Main nav (módulos del sistema).
     * Por ahora: href = '#'
     * Cuando existan rutas, reemplazas '#'
     */
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },

        // mod_agenda
        {
            title: 'Agenda',
            href: '#',
            icon: CalendarDays,
        },

        // mod_pacientes
        {
            title: 'Pacientes',
            href: '#',
            icon: Users,
        },

        // mod_sesiones
        {
            title: 'Sesiones',
            href: '#',
            icon: ClipboardList,
        },

        // mod_ejercicios
        {
            title: 'Ejercicios',
            href: '#',
            icon: Dumbbell,
        },

        // mod_archivos
        {
            title: 'Archivos',
            href: '#',
            icon: FileText,
        },

        // mod_reportes
        {
            title: 'Reportes',
            href: '#',
            icon: BarChart3,
        },

        // mod_cobranza
        {
            title: 'Cobranza',
            href: '#',
            icon: CreditCard,
        },

        // mod_config
        {
            title: 'Configuración',
            href: '#',
            icon: Settings,
        },
    ]

    /**
     * Footer nav: vacío por ahora (quitamos GitHub/Docs),
     * pero dejamos el componente para futuro.
     */
    const footerNavItems: NavItem[] = [
    // Ejemplo futuro (cuando exista link real):
    // { title: 'Soporte', href: 'https://tusitio.com/soporte', icon: LifeBuoy },
    ]
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
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
