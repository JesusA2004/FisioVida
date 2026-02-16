// resources/js/Pages/Archivos/Archivos.ts
import type { CrudColumn, CrudField, SelectOpt } from '@/components/fv/crud.types'
import type { ArchivosPageProps, FileForm, FileRow } from './Archivos.types'

const fmtSize = (n?: number | null) => {
  if (!n) return '—'
  const kb = n / 1024
  if (kb < 1024) return `${kb.toFixed(1)} KB`
  return `${(kb / 1024).toFixed(1)} MB`
}

export function useArchivosCrud(props: ArchivosPageProps) {
  const patients: SelectOpt[] = (props.lookups?.patients ?? []).map(p => ({ value: p.id, label: p.label }))
  const sessions: SelectOpt[] = (props.lookups?.sessions ?? []).map(s => ({ value: s.id, label: s.label }))

  const columns: CrudColumn<FileRow>[] = [
    { label: 'Fecha', class: 'min-w-[160px]', value: r => r.created_at },
    { label: 'Paciente', class: 'min-w-[240px]', value: r => r.patient_name ?? '—' },
    { label: 'Sesión', class: 'min-w-[140px]', value: r => r.session_date ?? '—' },
    { label: 'Archivo', class: 'min-w-[260px] font-medium', value: r => r.original_name },
    { label: 'Tamaño', class: 'min-w-[120px]', value: r => fmtSize(r.size_bytes) },
  ]

  const defaults: FileForm = {
    patient_persona_id: '',
    session_id: '',
    file: null,
  }

  const fields: CrudField<FileForm>[] = [
    { key: 'patient_persona_id', label: 'Paciente', type: 'select', options: patients },
    { key: 'session_id', label: 'Sesión', type: 'select', options: sessions },
    { key: 'file', label: 'Archivo', type: 'file', accept: '.pdf,.png,.jpg,.jpeg,.doc,.docx', hint: 'Máx 10MB' },
  ]

  const toForm = (_r: any): FileForm => ({ ...defaults })

  return { columns, fields, defaults, toForm }
}
