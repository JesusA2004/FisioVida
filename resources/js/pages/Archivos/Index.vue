<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

type FileRow = {
  id: number
  patient_name?: string | null
  session_id?: number | null
  original_name: string
  mime?: string | null
  size_bytes?: number | null
  created_at?: string | null
}

type FileForm = {
  patient_persona_id: string | number
  session_id: string | number
  file: File | null
}

const props = defineProps<{
  rows: FileRow[]
  page: any
  filters: any
  patients: { value: number; label: string }[]
  sessions?: { value: number; label: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Archivos', href: '/archivos' }]

const fmtSize = (n?: number | null) => {
  const v = Number(n ?? 0)
  if (!v) return '—'
  const kb = v / 1024
  if (kb < 1024) return `${kb.toFixed(0)} KB`
  return `${(kb / 1024).toFixed(1)} MB`
}

const columns: CrudColumn<FileRow>[] = [
  { label: 'Archivo', value: r => r.original_name },
  { label: 'Paciente', value: r => r.patient_name ?? '—' },
  { label: 'Sesión', value: r => (r.session_id ?? '—'), class: 'w-[110px]' },
  { label: 'Tipo', value: r => r.mime ?? '—', class: 'w-[170px]' },
  { label: 'Tamaño', value: r => fmtSize(r.size_bytes), class: 'w-[110px]' },
  { label: 'Fecha', value: r => r.created_at ?? '—', class: 'w-[160px]' },
]

const fields: CrudField<FileForm>[] = [
  { key: 'patient_persona_id', label: 'Paciente (opcional)', type: 'select', options: props.patients, span: 2 },
  { key: 'session_id', label: 'Sesión (opcional)', type: 'select', options: props.sessions ?? [], span: 2 },
  { key: 'file', label: 'Archivo', type: 'file', accept: '*/*', span: 2, hint: 'Sube el documento (estudio/consentimiento/etc).' },
]

const defaults: FileForm = {
  patient_persona_id: '',
  session_id: '',
  file: null,
}

const toForm = (row: FileRow): FileForm => ({
  patient_persona_id: (row as any).patient_persona_id ?? '',
  session_id: (row as any).session_id ?? '',
  file: null, // no rehidratar archivo
})
</script>

<template>
  <Head title="Archivos" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Archivos"
      subtitle="Documentos clínicos y evidencias"
      create-label="Subir archivo"
      route-base="files"
      :rows="props.rows"
      :page="props.page"
      :filters="props.filters"
      :columns="columns"
      :fields="fields"
      :defaults="defaults"
      :to-form="toForm"
      :force-form-data="true"
      modal-max-width-class="max-w-4xl"
    />
  </AppLayout>
</template>
