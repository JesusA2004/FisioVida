<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

type ExerciseRow = {
  id: number
  name: string
  is_active: 0 | 1 | boolean
  video_url?: string | null
  description?: string | null
}

type ExerciseForm = {
  name: string
  description: string
  video_url: string
  is_active: boolean
}

const props = defineProps<{
  rows: ExerciseRow[]
  page: any
  filters: any
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ejercicios', href: '/ejercicios' }]

const columns: CrudColumn<ExerciseRow>[] = [
  { label: 'Nombre', value: r => r.name },
  { label: 'Video', value: r => r.video_url ?? '—' },
  { label: 'Activo', value: r => (Number(r.is_active) ? 'Sí' : 'No'), class: 'w-[120px]' },
]

const fields: CrudField<ExerciseForm>[] = [
  { key: 'name', label: 'Nombre', type: 'text', span: 2 },
  { key: 'video_url', label: 'Video URL', type: 'text', span: 2, placeholder: 'https://...' },
  { key: 'description', label: 'Descripción', type: 'textarea', span: 2 },
  { key: 'is_active', label: 'Estatus', type: 'toggle', span: 2, hint: 'Controla visibilidad en catálogo.' },
]

const defaults: ExerciseForm = {
  name: '',
  description: '',
  video_url: '',
  is_active: true,
}

const toForm = (row: ExerciseRow): ExerciseForm => ({
  name: row.name ?? '',
  description: row.description ?? '',
  video_url: row.video_url ?? '',
  is_active: Boolean(Number(row.is_active)),
})
</script>

<template>
  <Head title="Ejercicios" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Ejercicios"
      subtitle="Catálogo de ejercicios"
      create-label="Nuevo ejercicio"
      route-base="exercises"
      :rows="props.rows"
      :page="props.page"
      :filters="props.filters"
      :columns="columns"
      :fields="fields"
      :defaults="defaults"
      :to-form="toForm"
      modal-max-width-class="max-w-4xl"
    />
  </AppLayout>
</template>
