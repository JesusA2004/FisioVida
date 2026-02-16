// resources/js/Pages/Agenda/Agenda.ts
import type { CrudColumn, CrudField, SelectOpt } from '@/components/fv/crud.types'
import type { AgendaPageProps, AppointmentForm, AppointmentRow } from './Agenda.types'

export function useAgendaCrud(props: AgendaPageProps) {
  const patientOpts: SelectOpt[] = (props.lookups?.patients ?? []).map(p => ({ value: p.id, label: p.label }))
  const therapistOpts: SelectOpt[] = (props.lookups?.therapists ?? []).map(t => ({ value: t.id, label: t.label }))

  const statusOptions = [
    { value: 'scheduled', label: 'Programada' },
    { value: 'confirmed', label: 'Confirmada' },
    { value: 'arrived', label: 'Llegó' },
    { value: 'no_show', label: 'No asistió' },
    { value: 'cancelled', label: 'Cancelada' },
    { value: 'done', label: 'Completada' },
  ]

  const columns: CrudColumn<AppointmentRow>[] = [
    { label: 'Fecha / hora', class: 'min-w-[210px]', value: r => `${r.start_at} → ${r.end_at}` },
    { label: 'Paciente', class: 'min-w-[220px]', value: r => r.patient_name },
    { label: 'Terapeuta', class: 'min-w-[180px]', value: r => r.therapist_name },
    { label: 'Estado', class: 'min-w-[140px]', value: r => statusOptions.find(s => s.value === r.status)?.label ?? r.status },
  ]

  const defaults: AppointmentForm = {
    patient_persona_id: '',
    therapist_user_id: '',
    start_at: '',
    end_at: '',
    status: 'scheduled',
    notes: '',
  }

  const fields: CrudField<AppointmentForm>[] = [
    { key: 'patient_persona_id', label: 'Paciente', type: 'select', options: patientOpts },
    { key: 'therapist_user_id', label: 'Terapeuta', type: 'select', options: therapistOpts },
    { key: 'start_at', label: 'Inicio', type: 'datetime' },
    { key: 'end_at', label: 'Fin', type: 'datetime' },
    { key: 'status', label: 'Estatus', type: 'select', options: statusOptions.map(s => ({ value: s.value, label: s.label })) },
    { key: 'notes', label: 'Notas', type: 'textarea', placeholder: 'Opcional (máx. 255)' },
  ]

  const toForm = (r: AppointmentRow): AppointmentForm => ({
    patient_persona_id: r.patient_persona_id,
    therapist_user_id: r.therapist_user_id,
    start_at: r.start_at?.slice(0, 16) ?? '',
    end_at: r.end_at?.slice(0, 16) ?? '',
    status: r.status,
    notes: r.notes ?? '',
  })

  return { columns, fields, defaults, toForm, statusOptions }
}
