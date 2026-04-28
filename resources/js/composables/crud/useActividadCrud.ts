import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

export type ActivityRow = {
  id: number
  title: string
  description?: string | null
  responsible_name?: string | null
  priority: 'low' | 'medium' | 'high' | 'urgent'
  status: 'pending' | 'in_progress' | 'on_hold' | 'completed' | 'cancelled' | 'overdue'
  due_date?: string | null
}

export type ActivityForm = {
  title: string
  description: string
  priority: ActivityRow['priority']
  status: ActivityRow['status']
  due_date: string
}

export const useActividadCrud = () => {
  const columns: CrudColumn<ActivityRow>[] = [
    { label: 'Título', value: r => r.title },
    { label: 'Responsable', value: r => r.responsible_name ?? 'Sin asignar' },
    { label: 'Prioridad', value: r => r.priority, class: 'w-[120px]' },
    { label: 'Estado', value: r => r.status, class: 'w-[140px]' },
    { label: 'Vence', value: r => r.due_date ?? '—', class: 'w-[160px]' },
  ]

  const statusOptions = [
    { value: 'pending', label: 'Pendiente' },
    { value: 'in_progress', label: 'En proceso' },
    { value: 'on_hold', label: 'En espera' },
    { value: 'completed', label: 'Completada' },
    { value: 'cancelled', label: 'Cancelada' },
    { value: 'overdue', label: 'Vencida' },
  ]

  const priorityOptions = [
    { value: 'low', label: 'Baja' },
    { value: 'medium', label: 'Media' },
    { value: 'high', label: 'Alta' },
    { value: 'urgent', label: 'Urgente' },
  ]

  const fields: CrudField<ActivityForm>[] = [
    { key: 'title', label: 'Título', type: 'text', span: 2 },
    { key: 'priority', label: 'Prioridad', type: 'select', options: priorityOptions, span: 1 },
    { key: 'status', label: 'Estado', type: 'select', options: statusOptions, span: 1 },
    { key: 'due_date', label: 'Fecha límite', type: 'datetime', span: 1 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2 },
  ]

  const defaults: ActivityForm = {
    title: '',
    description: '',
    priority: 'medium',
    status: 'pending',
    due_date: '',
  }

  const toForm = (row: ActivityRow): ActivityForm => ({
    title: row.title ?? '',
    description: row.description ?? '',
    priority: row.priority ?? 'medium',
    status: row.status ?? 'pending',
    due_date: row.due_date?.slice(0, 16) ?? '',
  })

  return { columns, fields, defaults, toForm, statusOptions, priorityOptions }
}
