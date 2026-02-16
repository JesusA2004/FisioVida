// resources/js/Pages/Ejercicios/Ejercicios.ts
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'
import type { EjerciciosPageProps, ExerciseForm, ExerciseRow } from './Ejercicios.types'

export function useEjerciciosCrud(_props: EjerciciosPageProps) {
  const columns: CrudColumn<ExerciseRow>[] = [
    { label: 'Ejercicio', class: 'min-w-[260px] font-medium', value: r => r.name },
    { label: 'Activo', class: 'min-w-[120px]', value: r => (r.is_active ? 'Sí' : 'No') },
    { label: 'Video', class: 'min-w-[260px]', value: r => r.video_url ?? '—' },
  ]

  const defaults: ExerciseForm = {
    name: '',
    description: '',
    video_url: '',
    is_active: true,
  }

  const fields: CrudField<ExerciseForm>[] = [
    { key: 'name', label: 'Nombre', type: 'text' },
    { key: 'video_url', label: 'Video URL', type: 'text' },
    { key: 'is_active', label: 'Activo', type: 'toggle' },
    { key: 'description', label: 'Descripción', type: 'textarea' },
  ]

  const toForm = (r: any): ExerciseForm => ({
    name: r.name ?? '',
    description: r.description ?? '',
    video_url: r.video_url ?? '',
    is_active: Boolean(r.is_active),
  })

  const extraFilters = [
    { key: 'active', label: 'Activo', options: [{ value: '1', label: 'Sí' }, { value: '0', label: 'No' }] },
  ]

  return { columns, fields, defaults, toForm, extraFilters }
}
