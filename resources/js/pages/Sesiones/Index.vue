<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

type SessionRow = {
  id: number
  session_date: string
  patient_name: string
  therapist_name: string
  pain_scale?: number | null
  notes?: string | null
  appointment_id?: number | null
}

type SessionForm = {
  appointment_id: string | number
  patient_persona_id: string | number
  therapist_user_id: string | number
  session_date: string
  subjective: string
  objective: string
  assessment: string
  plan: string
  pain_scale: string | number
  notes: string
}

const props = defineProps<{
  rows: SessionRow[]
  page: any
  filters: any

  patients: { value: number; label: string }[]
  therapists: { value: number; label: string }[]
  appointments?: { value: number; label: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Sesiones', href: '/sesiones' }]

const columns: CrudColumn<SessionRow>[] = [
  { label: 'Fecha', value: r => r.session_date, class: 'w-[140px]' },
  { label: 'Paciente', value: r => r.patient_name },
  { label: 'Terapeuta', value: r => r.therapist_name },
  { label: 'Dolor', value: r => (r.pain_scale ?? '—'), class: 'w-[90px]' },
  { label: 'Notas', value: r => r.notes ?? '—' },
]

const fields: CrudField<SessionForm>[] = [
  { key: 'patient_persona_id', label: 'Paciente', type: 'select', options: props.patients, span: 1 },
  { key: 'therapist_user_id', label: 'Terapeuta', type: 'select', options: props.therapists, span: 1 },
  { key: 'appointment_id', label: 'Cita (opcional)', type: 'select', options: props.appointments ?? [], span: 2 },
  { key: 'session_date', label: 'Fecha sesión', type: 'date', span: 1 },
  { key: 'pain_scale', label: 'Escala dolor (0-10)', type: 'number', span: 1, hint: 'Valídalo en backend (0–10).' },
  { key: 'subjective', label: 'S - Subjetivo', type: 'textarea', span: 2 },
  { key: 'objective', label: 'O - Objetivo', type: 'textarea', span: 2 },
  { key: 'assessment', label: 'A - Evaluación', type: 'textarea', span: 2 },
  { key: 'plan', label: 'P - Plan', type: 'textarea', span: 2 },
  { key: 'notes', label: 'Notas', type: 'textarea', span: 2 },
]

const defaults: SessionForm = {
  appointment_id: '',
  patient_persona_id: '',
  therapist_user_id: '',
  session_date: '',
  subjective: '',
  objective: '',
  assessment: '',
  plan: '',
  pain_scale: '',
  notes: '',
}

const toForm = (row: SessionRow): SessionForm => ({
  appointment_id: (row as any).appointment_id ?? '',
  patient_persona_id: (row as any).patient_persona_id ?? '',
  therapist_user_id: (row as any).therapist_user_id ?? '',
  session_date: row.session_date ?? '',
  subjective: (row as any).subjective ?? '',
  objective: (row as any).objective ?? '',
  assessment: (row as any).assessment ?? '',
  plan: (row as any).plan ?? '',
  pain_scale: (row as any).pain_scale ?? '',
  notes: row.notes ?? '',
})
</script>

<template>
  <Head title="Sesiones" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Sesiones"
      subtitle="Registro clínico (SOAP)"
      create-label="Nueva sesión"
      route-base="sessions"
      :rows="props.rows"
      :page="props.page"
      :filters="props.filters"
      :columns="columns"
      :fields="fields"
      :defaults="defaults"
      :to-form="toForm"
      modal-max-width-class="max-w-5xl"
    />
  </AppLayout>
</template>
