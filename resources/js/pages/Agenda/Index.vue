<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

type AppointmentRow = {
  id: number
  start_at: string
  end_at: string
  status: string
  patient_name: string
  therapist_name: string
  notes?: string | null
}

type AppointmentForm = {
  patient_persona_id: string | number
  therapist_user_id: string | number
  start_at: string
  end_at: string
  status: string
  notes: string
}

const props = defineProps<{
  rows: AppointmentRow[]
  page: any
  filters: any

  patients: { value: number; label: string }[]
  therapists: { value: number; label: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Agenda', href: '/citas' }]

const columns: CrudColumn<AppointmentRow>[] = [
  { label: 'Fecha / hora', value: r => `${r.start_at} → ${r.end_at}` },
  { label: 'Paciente', value: r => r.patient_name },
  { label: 'Terapeuta', value: r => r.therapist_name },
  { label: 'Estado', value: r => r.status, class: 'w-[160px]' },
]

const statusOptions = [
  { value: 'scheduled', label: 'Programada' },
  { value: 'confirmed', label: 'Confirmada' },
  { value: 'arrived', label: 'Llegó' },
  { value: 'no_show', label: 'No asistió' },
  { value: 'cancelled', label: 'Cancelada' },
  { value: 'done', label: 'Finalizada' },
]

const fields: CrudField<AppointmentForm>[] = [
  { key: 'patient_persona_id', label: 'Paciente', type: 'select', options: props.patients, span: 1 },
  { key: 'therapist_user_id', label: 'Terapeuta', type: 'select', options: props.therapists, span: 1 },
  { key: 'start_at', label: 'Inicio', type: 'datetime', span: 1 },
  { key: 'end_at', label: 'Fin', type: 'datetime', span: 1 },
  { key: 'status', label: 'Estado', type: 'select', options: statusOptions, span: 1 },
  { key: 'notes', label: 'Notas', type: 'textarea', placeholder: 'Opcional...', span: 2 },
]

const defaults: AppointmentForm = {
  patient_persona_id: '',
  therapist_user_id: '',
  start_at: '',
  end_at: '',
  status: 'scheduled',
  notes: '',
}

const toForm = (row: AppointmentRow): AppointmentForm => ({
  patient_persona_id: (row as any).patient_persona_id ?? '',
  therapist_user_id: (row as any).therapist_user_id ?? '',
  start_at: (row as any).start_at ?? '',
  end_at: (row as any).end_at ?? '',
  status: row.status ?? 'scheduled',
  notes: row.notes ?? '',
})
</script>

<template>
  <Head title="Agenda" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Citas"
      subtitle="Agenda de citas programadas"
      create-label="Nueva cita"
      route-base="appointments"
      :rows="props.rows"
      :page="props.page"
      :filters="props.filters"
      :columns="columns"
      :fields="fields"
      :defaults="defaults"
      :to-form="toForm"
      :searchable="true"
      :status-options="statusOptions"
      modal-max-width-class="max-w-4xl"
    />
  </AppLayout>
</template>
