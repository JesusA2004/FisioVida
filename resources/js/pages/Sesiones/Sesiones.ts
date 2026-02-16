// resources/js/Pages/Sesiones/Sesiones.ts
import type { CrudColumn, CrudField, SelectOpt } from '@/components/fv/crud.types'
import type { SesionesPageProps, SessionForm, SessionRow } from './Sesiones.types'

export function useSesionesCrud(props: SesionesPageProps) {
  const patients: SelectOpt[] = (props.lookups?.patients ?? []).map(p => ({ value: p.id, label: p.label }))
  const therapists: SelectOpt[] = (props.lookups?.therapists ?? []).map(t => ({ value: t.id, label: t.label }))
  const appointments: SelectOpt[] = (props.lookups?.appointments ?? []).map(a => ({ value: a.id, label: a.label }))

  const columns: CrudColumn<SessionRow>[] = [
    { label: 'Fecha', class: 'min-w-[140px]', value: r => r.session_date },
    { label: 'Paciente', class: 'min-w-[240px] font-medium', value: r => r.patient_name },
    { label: 'Terapeuta', class: 'min-w-[180px]', value: r => r.therapist_name },
    { label: 'Dolor', class: 'min-w-[90px]', value: r => (r.pain_scale ?? '—').toString() },
  ]

  const defaults: SessionForm = {
    appointment_id: '',
    patient_persona_id: '',
    therapist_user_id: '',
    session_date: '',
    pain_scale: '',
    subjective: '',
    objective: '',
    assessment: '',
    plan: '',
    notes: '',
  }

  const fields: CrudField<SessionForm>[] = [
    { key: 'appointment_id', label: 'Cita (opcional)', type: 'select', options: appointments },
    { key: 'patient_persona_id', label: 'Paciente', type: 'select', options: patients },
    { key: 'therapist_user_id', label: 'Terapeuta', type: 'select', options: therapists },
    { key: 'session_date', label: 'Fecha sesión', type: 'date' },
    { key: 'pain_scale', label: 'Dolor (0-10)', type: 'number' },
    { key: 'subjective', label: 'Subjective', type: 'textarea' },
    { key: 'objective', label: 'Objective', type: 'textarea' },
    { key: 'assessment', label: 'Assessment', type: 'textarea' },
    { key: 'plan', label: 'Plan', type: 'textarea' },
    { key: 'notes', label: 'Notas', type: 'textarea' },
  ]

  const toForm = (r: any): SessionForm => ({
    appointment_id: r.appointment_id ?? '',
    patient_persona_id: r.patient_persona_id ?? '',
    therapist_user_id: r.therapist_user_id ?? '',
    session_date: r.session_date ?? '',
    pain_scale: r.pain_scale ?? '',
    subjective: r.subjective ?? '',
    objective: r.objective ?? '',
    assessment: r.assessment ?? '',
    plan: r.plan ?? '',
    notes: r.notes ?? '',
  })

  return { columns, fields, defaults, toForm }
}
