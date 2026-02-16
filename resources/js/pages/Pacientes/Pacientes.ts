// resources/js/Pages/Pacientes/Pacientes.ts
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'
import type { PacienteForm, PacienteRow, PacientesPageProps } from './Pacientes.types'

export function usePacientesCrud(_props: PacientesPageProps) {
  const statusOptions = [
    { value: 'active', label: 'Activo' },
    { value: 'inactive', label: 'Inactivo' },
  ]

  const columns: CrudColumn<PacienteRow>[] = [
    { label: 'Paciente', class: 'min-w-[260px] font-medium', value: r => r.full_name },
    { label: 'Teléfono', class: 'min-w-[160px]', value: r => r.telefono ?? '—' },
    { label: 'Email', class: 'min-w-[220px]', value: r => r.email ?? '—' },
    { label: 'Estado', class: 'min-w-[120px]', value: r => (r.status === 'active' ? 'Activo' : 'Inactivo') },
  ]

  const defaults: PacienteForm = {
    status: 'active',
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    fecha_nacimiento: '',
    sexo: 'X',
    telefono: '',
    email: '',
    direccion: '',
    contacto_emergencia_nombre: '',
    contacto_emergencia_telefono: '',
    notas: '',
  }

  const fields: CrudField<PacienteForm>[] = [
    { key: 'status', label: 'Estatus', type: 'select', options: statusOptions },
    { key: 'nombres', label: 'Nombres', type: 'text' },
    { key: 'apellido_paterno', label: 'Apellido paterno', type: 'text' },
    { key: 'apellido_materno', label: 'Apellido materno', type: 'text' },
    { key: 'fecha_nacimiento', label: 'Fecha nacimiento', type: 'date' },
    { key: 'sexo', label: 'Sexo', type: 'select', options: [{ value: 'M', label: 'M' }, { value: 'F', label: 'F' }, { value: 'X', label: 'X' }] },
    { key: 'telefono', label: 'Teléfono', type: 'text' },
    { key: 'email', label: 'Email', type: 'text' },
    { key: 'direccion', label: 'Dirección', type: 'text' },
    { key: 'contacto_emergencia_nombre', label: 'Contacto emergencia', type: 'text' },
    { key: 'contacto_emergencia_telefono', label: 'Tel. emergencia', type: 'text' },
    { key: 'notas', label: 'Notas', type: 'textarea' },
  ]

  const toForm = (r: any): PacienteForm => ({
    status: r.status ?? 'active',
    nombres: r.nombres ?? '',
    apellido_paterno: r.apellido_paterno ?? '',
    apellido_materno: r.apellido_materno ?? '',
    fecha_nacimiento: r.fecha_nacimiento ?? '',
    sexo: r.sexo ?? 'X',
    telefono: r.telefono ?? '',
    email: r.email ?? '',
    direccion: r.direccion ?? '',
    contacto_emergencia_nombre: r.contacto_emergencia_nombre ?? '',
    contacto_emergencia_telefono: r.contacto_emergencia_telefono ?? '',
    notas: r.notas ?? '',
  })

  return { columns, fields, defaults, toForm, statusOptions }
}
