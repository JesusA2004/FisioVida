// resources/js/Pages/Logs/Logs.types.ts
import type { PageMeta } from '@/components/fv/crud.types'

export type LogRow = {
  id: number
  level: 'info'|'warning'|'error'|'audit'
  actor_user_id: number | null
  actor_name: string | null
  action: string
  entity_type: string | null
  entity_id: number | null
  message: string
  ip: string | null
  user_agent: string | null
  created_at: string | null
}

export type LogForm = {
  level: LogRow['level']
  actor_user_id: number | ''
  action: string
  entity_type: string | null
  entity_id: number | ''
  message: string
}

export type LogsPageProps = {
  rows: LogRow[]
  page: PageMeta
  filters: { q?: string; level?: string }
  lookups: { actors: { id: number; label: string }[] }
}
