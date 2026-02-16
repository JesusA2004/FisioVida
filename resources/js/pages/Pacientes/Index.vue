<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

type PatientRow = {
  id: number
  nombres: string
  apellido_paterno?: string | null
  apellido_materno?: string | null
  telefono?: string | null
  email?: string | null
  status: 'active' | 'inactive'
  sexo?: 'M' | 'F' | 'X' | null
  fecha_nacimiento?: string | null
}

type PatientForm = {
  tipo: 'paciente' | 'staff' | 'ambos'
  status: 'active' | 'inactive'
  nombres: string
  apellido_paterno: string
  apellido_materno: string
  fecha_nacimiento: string
  sexo: 'M' | 'F' | 'X' | ''
  telefono: string
  email: string
  direccion: string
  contacto_emergencia_nombre: string
  contacto_emergencia_telefono: string
  notas: string
}

const props = defineProps<{
  rows: PatientRow[]
  page: any
  filters: any
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pacientes', href: '/pacientes' }]

const columns: CrudColumn<PatientRow>[] = [
  { label: 'Nombre', value: r => `${r.apellido_paterno ?? ''} ${r.apellido_materno ?? ''} ${r.nombres}`.replace(/\s+/g, ' ').trim() },
  { label: 'Teléfono', value: r => r.telefono ?? '—', class: 'w-[160px]' },
  { label: 'Email', value: r => r.email ?? '—' },
  { label: 'Estatus', value: r => r.status, class: 'w-[140px]' },
]

const statusOptions = [
  { value: 'active', label: 'Activo' },
  { value: 'inactive', label: 'Inactivo' },
]

const tipoOptions = [
  { value: 'paciente', label: 'Paciente' },
  { value: 'staff', label: 'Staff' },
  { value: 'ambos', label: 'Ambos' },
]

const sexoOptions = [
  { value: 'M', label: 'M' },
  { value: 'F', label: 'F' },
  { value: 'X', label: 'X' },
]

const fields: CrudField<PatientForm>[] = [
  { key: 'tipo', label: 'Tipo', type: 'select', options: tipoOptions, span: 1 },
  { key: 'status', label: 'Estatus', type: 'select', options: statusOptions, span: 1 },
  { key: 'nombres', label: 'Nombres', type: 'text', placeholder: 'Ej. Juan Carlos', span: 1 },
  { key: 'apellido_paterno', label: 'Apellido paterno', type: 'text', span: 1 },
  { key: 'apellido_materno', label: 'Apellido materno', type: 'text', span: 1 },
  { key: 'fecha_nacimiento', label: 'Fecha nacimiento', type: 'date', span: 1 },
  { key: 'sexo', label: 'Sexo', type: 'select', options: sexoOptions, span: 1 },
  { key: 'telefono', label: 'Teléfono', type: 'text', span: 1 },
  { key: 'email', label: 'Email', type: 'text', span: 1 },
  { key: 'direccion', label: 'Dirección', type: 'text', span: 2 },
  { key: 'contacto_emergencia_nombre', label: 'Contacto emergencia (nombre)', type: 'text', span: 1 },
  { key: 'contacto_emergencia_telefono', label: 'Contacto emergencia (tel)', type: 'text', span: 1 },
  { key: 'notas', label: 'Notas', type: 'textarea', placeholder: 'Opcional...', span: 2 },
]

const defaults: PatientForm = {
  tipo: 'paciente',
  status: 'active',
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  fecha_nacimiento: '',
  sexo: '',
  telefono: '',
  email: '',
  direccion: '',
  contacto_emergencia_nombre: '',
  contacto_emergencia_telefono: '',
  notas: '',
}

const toForm = (row: PatientRow): PatientForm => ({
  tipo: (row as any).tipo ?? 'paciente',
  status: row.status ?? 'active',
  nombres: row.nombres ?? '',
  apellido_paterno: (row as any).apellido_paterno ?? '',
  apellido_materno: (row as any).apellido_materno ?? '',
  fecha_nacimiento: row.fecha_nacimiento ?? '',
  sexo: (row.sexo as any) ?? '',
  telefono: row.telefono ?? '',
  email: row.email ?? '',
  direccion: (row as any).direccion ?? '',
  contacto_emergencia_nombre: (row as any).contacto_emergencia_nombre ?? '',
  contacto_emergencia_telefono: (row as any).contacto_emergencia_telefono ?? '',
  notas: (row as any).notas ?? '',
})
</script>

<template>
  <Head title="Pacientes" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Pacientes"
      subtitle="Gestión de personas tipo paciente"
      create-label="Nuevo paciente"
      route-base="patients"
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
