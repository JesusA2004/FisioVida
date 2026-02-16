// resources/js/Pages/Ejercicios/Ejercicios.types.ts
import type { PageMeta } from '@/components/fv/crud.types'

export type ExerciseRow = {
  id: number
  name: string
  description: string | null
  video_url: string | null
  is_active: boolean | 0 | 1
}

export type ExerciseForm = {
  name: string
  description: string | null
  video_url: string | null
  is_active: boolean
}

export type EjerciciosPageProps = {
  rows: ExerciseRow[]
  page: PageMeta
  filters: { q?: string; active?: string }
}
