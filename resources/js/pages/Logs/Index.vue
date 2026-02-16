<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

type LogRow = {
  id: number
  level: 'info' | 'warning' | 'error' | 'audit'
  action: string
  entity_type?: string | null
  entity_id?: number | null
  message: string
  ip?: string | null
  created_at?: string | null
  actor_name?: string | null
}

type LogForm = {
  // normalmente no se crea/edita; lo dejo por compatibilidad
  level: string
  action: string
  entity_type: string
  entity_id: string | number
  message: string
  ip: string
  user_agent: string
}

const props = defineProps<{
  rows: LogRow[]
  page: any
  filters: any
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Logs', href: '/logs' }]

const levelOptions = [
  { value: 'info', label: 'Info' },
  { value: 'warning', label: 'Warning' },
  { value: 'error', label: 'Error' },
  { value: 'audit', label: 'Audit' },
]

const columns: CrudColumn<LogRow>[] = [
  { label: 'Fecha', value: r => r.created_at ?? '—', class: 'w-[170px]' },
  { label: 'Nivel', value: r => r.level, class: 'w-[110px]' },
  { label: 'Acción', value: r => r.action, class: 'w-[220px]' },
  { label: 'Entidad', value: r => `${r.entity_type ?? '—'} #${r.entity_id ?? '—'}`, class: 'w-[220px]' },
  { label: 'Mensaje', value: r => r.message },
]

const fields: CrudField<LogForm>[] = [
  { key: 'level', label: 'Nivel', type: 'select', options: levelOptions, span: 1, readonly: true },
  { key: 'action', label: 'Acción', type: 'text', span: 1, readonly: true },
  { key: 'entity_type', label: 'Entidad', type: 'text', span: 1, readonly: true },
  { key: 'entity_id', label: 'ID entidad', type: 'text', span: 1, readonly: true },
  { key: 'message', label: 'Mensaje', type: 'textarea', span: 2, readonly: true },
  { key: 'ip', label: 'IP', type: 'text', span: 1, readonly: true },
  { key: 'user_agent', label: 'User agent', type: 'text', span: 1, readonly: true },
]

const defaults: LogForm = {
  level: 'info',
  action: '',
  entity_type: '',
  entity_id: '',
  message: '',
  ip: '',
  user_agent: '',
}

const toForm = (row: LogRow): LogForm => ({
  level: row.level ?? 'info',
  action: row.action ?? '',
  entity_type: row.entity_type ?? '',
  entity_id: row.entity_id ?? '',
  message: row.message ?? '',
  ip: row.ip ?? '',
  user_agent: (row as any).user_agent ?? '',
})
</script>

<template>
  <Head title="Logs" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Logs"
      subtitle="Bitácora del sistema"
      route-base="logs"
      :rows="props.rows"
      :page="props.page"
      :filters="props.filters"
      :columns="columns"
      :fields="fields"
      :defaults="defaults"
      :to-form="toForm"
      :status-options="levelOptions"
      :can-create="false"
      :can-edit="false"
      :can-delete="false"
      modal-max-width-class="max-w-5xl"
    />
  </AppLayout>
</template>
