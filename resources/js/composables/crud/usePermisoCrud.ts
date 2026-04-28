import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

export type PermissionRow = {
  id: number
  name: string
  slug: string
  module: string
  description?: string | null
  status: 'active' | 'inactive'
}

export type PermissionForm = {
  name: string
  slug: string
  module: string
  description: string
  status: 'active' | 'inactive'
}

export const usePermisoCrud = () => {
  const columns: CrudColumn<PermissionRow>[] = [
    { label: 'Nombre', value: r => r.name },
    { label: 'Slug', value: r => r.slug },
    { label: 'Módulo', value: r => r.module, class: 'w-[140px]' },
    { label: 'Estado', value: r => r.status, class: 'w-[120px]' },
  ]

  const statusOptions = [
    { value: 'active', label: 'Activo' },
    { value: 'inactive', label: 'Inactivo' },
  ]

  const fields: CrudField<PermissionForm>[] = [
    { key: 'name', label: 'Nombre', type: 'text', span: 1 },
    { key: 'slug', label: 'Slug', type: 'text', span: 1 },
    { key: 'module', label: 'Módulo', type: 'text', span: 1 },
    { key: 'status', label: 'Estado', type: 'select', options: statusOptions, span: 1 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2 },
  ]

  const defaults: PermissionForm = {
    name: '',
    slug: '',
    module: '',
    description: '',
    status: 'active',
  }

  const toForm = (row: PermissionRow): PermissionForm => ({
    name: row.name ?? '',
    slug: row.slug ?? '',
    module: row.module ?? '',
    description: row.description ?? '',
    status: row.status ?? 'active',
  })

  return { columns, statusOptions, fields, defaults, toForm }
}
