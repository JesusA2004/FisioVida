import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

export type RolRow = {
  id: number
  name: string
  slug: string
  description?: string | null
  status: 'active' | 'inactive'
}

export type RolForm = {
  name: string
  slug: string
  description: string
  status: 'active' | 'inactive'
}

export const useRolCrud = () => {
  const columns: CrudColumn<RolRow>[] = [
    { label: 'Nombre', value: r => r.name },
    { label: 'Slug', value: r => r.slug },
    { label: 'Descripción', value: r => r.description ?? '—' },
    { label: 'Estado', value: r => r.status, class: 'w-[140px]' },
  ]

  const statusOptions = [
    { value: 'active', label: 'Activo' },
    { value: 'inactive', label: 'Inactivo' },
  ]

  const fields: CrudField<RolForm>[] = [
    { key: 'name', label: 'Nombre', type: 'text', span: 1 },
    { key: 'slug', label: 'Slug', type: 'text', span: 1 },
    { key: 'status', label: 'Estado', type: 'select', options: statusOptions, span: 1 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2 },
  ]

  const defaults: RolForm = {
    name: '',
    slug: '',
    description: '',
    status: 'active',
  }

  const toForm = (row: RolRow): RolForm => ({
    name: row.name ?? '',
    slug: row.slug ?? '',
    description: row.description ?? '',
    status: row.status ?? 'active',
  })

  return { columns, fields, defaults, toForm, statusOptions }
}
