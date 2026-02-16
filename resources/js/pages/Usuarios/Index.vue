<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

type UserRow = {
  id: number
  name: string
  email: string
  status: 'active' | 'blocked'
  is_super_admin: 0 | 1 | boolean

  mod_agenda: 0 | 1 | boolean
  mod_pacientes: 0 | 1 | boolean
  mod_sesiones: 0 | 1 | boolean
  mod_ejercicios: 0 | 1 | boolean
  mod_archivos: 0 | 1 | boolean
  mod_reportes: 0 | 1 | boolean
  mod_cobranza: 0 | 1 | boolean
  mod_config: 0 | 1 | boolean
}

type UserForm = {
  name: string
  email: string
  password: string
  status: 'active' | 'blocked'
  is_super_admin: boolean

  mod_agenda: boolean
  mod_pacientes: boolean
  mod_sesiones: boolean
  mod_ejercicios: boolean
  mod_archivos: boolean
  mod_reportes: boolean
  mod_cobranza: boolean
  mod_config: boolean
}

const props = defineProps<{
  rows: UserRow[]
  page: any
  filters: any
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Usuarios', href: '/usuarios' }]

const statusOptions = [
  { value: 'active', label: 'Activo' },
  { value: 'blocked', label: 'Bloqueado' },
]

const columns: CrudColumn<UserRow>[] = [
  { label: 'Nombre', value: r => r.name },
  { label: 'Email', value: r => r.email },
  { label: 'Estatus', value: r => r.status, class: 'w-[140px]' },
  { label: 'Super Admin', value: r => (Number(r.is_super_admin) ? 'Sí' : 'No'), class: 'w-[140px]' },
]

const fields: CrudField<UserForm>[] = [
  { key: 'name', label: 'Nombre', type: 'text', span: 1 },
  { key: 'email', label: 'Email', type: 'text', span: 1 },
  { key: 'password', label: 'Password', type: 'text', span: 2, hint: 'En editar: deja vacío si NO quieres cambiarla.' },
  { key: 'status', label: 'Estatus', type: 'select', options: statusOptions, span: 1 },
  { key: 'is_super_admin', label: 'Super Admin', type: 'toggle', span: 1, hint: 'Si está activo, controla permisos.', },
  { key: 'mod_agenda', label: 'Módulo Agenda', type: 'toggle', span: 1 },
  { key: 'mod_pacientes', label: 'Módulo Pacientes', type: 'toggle', span: 1 },
  { key: 'mod_sesiones', label: 'Módulo Sesiones', type: 'toggle', span: 1 },
  { key: 'mod_ejercicios', label: 'Módulo Ejercicios', type: 'toggle', span: 1 },
  { key: 'mod_archivos', label: 'Módulo Archivos', type: 'toggle', span: 1 },
  { key: 'mod_reportes', label: 'Módulo Reportes', type: 'toggle', span: 1 },
  { key: 'mod_cobranza', label: 'Módulo Cobranza', type: 'toggle', span: 1 },
  { key: 'mod_config', label: 'Módulo Config', type: 'toggle', span: 1 },
]

const defaults: UserForm = {
  name: '',
  email: '',
  password: '',
  status: 'active',
  is_super_admin: false,

  mod_agenda: true,
  mod_pacientes: true,
  mod_sesiones: true,
  mod_ejercicios: true,
  mod_archivos: true,
  mod_reportes: false,
  mod_cobranza: false,
  mod_config: false,
}

const toForm = (row: UserRow): UserForm => ({
  name: row.name ?? '',
  email: row.email ?? '',
  password: '',
  status: row.status ?? 'active',
  is_super_admin: Boolean(Number(row.is_super_admin)),

  mod_agenda: Boolean(Number(row.mod_agenda)),
  mod_pacientes: Boolean(Number(row.mod_pacientes)),
  mod_sesiones: Boolean(Number(row.mod_sesiones)),
  mod_ejercicios: Boolean(Number(row.mod_ejercicios)),
  mod_archivos: Boolean(Number(row.mod_archivos)),
  mod_reportes: Boolean(Number(row.mod_reportes)),
  mod_cobranza: Boolean(Number(row.mod_cobranza)),
  mod_config: Boolean(Number(row.mod_config)),
})
</script>

<template>
  <Head title="Usuarios" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Usuarios"
      subtitle="Accesos y permisos por módulo"
      create-label="Nuevo usuario"
      route-base="users"
      :rows="props.rows"
      :page="props.page"
      :filters="props.filters"
      :columns="columns"
      :fields="fields"
      :defaults="defaults"
      :to-form="toForm"
      :status-options="statusOptions"
      modal-max-width-class="max-w-5xl"
    />
  </AppLayout>
</template>
