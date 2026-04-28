<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
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

const page = usePage()

const isSuperAdmin = computed(() => Boolean((page.props as any).auth?.is_super_admin))
const permissions = computed<string[]>(() => ((page.props as any).auth?.permissions ?? []) as string[])

const can = (permission: string) => isSuperAdmin.value || permissions.value.includes(permission)

const mainNavItems = computed<NavItem[]>(() => {
  const items: NavItem[] = []

  if (can('dashboard.view')) items.push({ title: 'Dashboard', href: '/dashboard', icon: LayoutGrid })
  if (can('appointments.view')) items.push({ title: 'Agenda', href: '/citas', icon: CalendarDays })
  if (can('patients.view')) items.push({ title: 'Pacientes', href: '/pacientes', icon: Users })
  if (can('sessions.view')) items.push({ title: 'Sesiones', href: '/sesiones', icon: ClipboardList })
  if (can('exercises.view')) items.push({ title: 'Ejercicios', href: '/ejercicios', icon: Dumbbell })
  if (can('files.view')) items.push({ title: 'Archivos', href: '/archivos', icon: FileText })
  if (can('payments.view')) items.push({ title: 'Cobranza', href: '/pagos', icon: CreditCard })
  if (can('activities.view')) items.push({ title: 'Actividades', href: '/actividades', icon: ListTodo })
  if (can('roles.view')) items.push({ title: 'Roles', href: '/roles', icon: ShieldCheck })
  if (can('permissions.view')) items.push({ title: 'Permisos', href: '/permisos', icon: KeyRound })
  if (can('users.view')) items.push({ title: 'Usuarios', href: '/usuarios', icon: Users })
  if (can('logs.view')) items.push({ title: 'Logs', href: '/logs', icon: BarChart3 })
  if (can('settings.view')) items.push({ title: 'Configuración', href: '/settings/profile', icon: Settings })

  return items
})

const footerNavItems: NavItem[] = []
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
