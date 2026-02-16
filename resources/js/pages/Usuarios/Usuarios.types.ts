// resources/js/Pages/Usuarios/Usuarios.types.ts
import type { PageMeta } from '@/components/fv/crud.types'

export type UserRow = {
  id: number
  name: string
  email: string
  status: 'active'|'blocked'
  is_super_admin: boolean
  mods: {
    agenda: boolean
    pacientes: boolean
    sesiones: boolean
    ejercicios: boolean
    archivos: boolean
    reportes: boolean
    cobranza: boolean
    config: boolean
  }
  last_login_at: string | null
  last_login_ip: string | null
}

export type UserForm = {
  name: string
  email: string
  password: string | null
  status: UserRow['status']
  is_super_admin: boolean
  mod_agenda: boolean
  mod_pacientes: boolean
  mod_sesiones: boolean
  mod_ejercicios: boolean
  mod_archivos: boolean
  mod_reportes: boolean
  mod_cobranza: boolean
  mod_config: boolean
}

export type UsuariosPageProps = {
  rows: UserRow[]
  page: PageMeta
  filters: { q?: string; status?: string }
}
