// resources/js/Pages/Logs/Logs.ts
import type { CrudColumn, CrudField, SelectOpt } from '@/components/fv/crud.types'
import type { LogForm, LogRow, LogsPageProps } from './Logs.types'

export function useLogsCrud(props: LogsPageProps) {
  const actors: SelectOpt[] = (props.lookups?.actors ?? []).map(a => ({ value: a.id, label: a.label }))

  const levelOptions = [
    { value: 'info', label: 'Info' },
    { value: 'warning', label: 'Warning' },
    { value: 'error', label: 'Error' },
    { value: 'audit', label: 'Audit' },
  ]

  const columns: CrudColumn<LogRow>[] = [
    { label: 'Fecha', class: 'min-w-[160px]', value: r => r.created_at ?? '—' },
    { label: 'Nivel', class: 'min-w-[120px]', value: r => r.level },
    { label: 'Actor', class: 'min-w-[180px]', value: r => r.actor_name ?? '—' },
    { label: 'Acción', class: 'min-w-[220px] font-medium', value: r => r.action },
    { label: 'Mensaje', class: 'min-w-[320px]', value: r => r.message },
  ]

  const defaults: LogForm = {
    level: 'info',
    actor_user_id: '',
    action: '',
    entity_type: '',
    entity_id: '',
    message: '',
  }

  const fields: CrudField<LogForm>[] = [
    { key: 'level', label: 'Nivel', type: 'select', options: levelOptions },
    { key: 'actor_user_id', label: 'Actor', type: 'select', options: actors },
    { key: 'action', label: 'Acción', type: 'text' },
    { key: 'entity_type', label: 'Entidad (tipo)', type: 'text' },
    { key: 'entity_id', label: 'Entidad (id)', type: 'number' },
    { key: 'message', label: 'Mensaje', type: 'textarea' },
  ]

  const toForm = (r: any): LogForm => ({
    level: r.level ?? 'info',
    actor_user_id: r.actor_user_id ?? '',
    action: r.action ?? '',
    entity_type: r.entity_type ?? '',
    entity_id: r.entity_id ?? '',
    message: r.message ?? '',
  })

  const extraFilters = [
    { key: 'level', label: 'Nivel', options: levelOptions },
  ]

  return { columns, fields, defaults, toForm, extraFilters }
}
