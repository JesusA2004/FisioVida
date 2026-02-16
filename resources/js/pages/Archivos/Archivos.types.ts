// resources/js/Pages/Archivos/Archivos.types.ts
import type { PageMeta } from '@/components/fv/crud.types'

export type FileRow = {
  id: number
  patient_persona_id: number | null
  patient_name: string | null
  session_id: number | null
  session_date: string | null
  original_name: string
  mime: string | null
  size_bytes: number | null
  created_at: string
  url: string | null
}

export type FileForm = {
  patient_persona_id: number | ''
  session_id: number | ''
  file: File | null
}

export type ArchivosPageProps = {
  rows: FileRow[]
  page: PageMeta
  filters: { q?: string }
  lookups: {
    patients: { id: number; label: string }[]
    sessions: { id: number; label: string }[]
  }
}
