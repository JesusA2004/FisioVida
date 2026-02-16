// resources/js/Pages/Usuarios/Usuarios.ts
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'
import type { UserForm, UserRow, UsuariosPageProps } from './Usuarios.types'

export function useUsuariosCrud(_props: UsuariosPageProps) {
  const statusOptions = [
    { value: 'active', label: 'Activo' },
    { value: 'blocked', label: 'Bloqueado' },
  ]

  const columns: CrudColumn<UserRow>[] = [
    { label: 'Usuario', class: 'min-w-[220px] font-medium', value: r => r.name },
    { label: 'Email', class: 'min-w-[240px]', value: r => r.email },
    { label: 'Estado', class: 'min-w-[140px]', value: r => (r.status === 'active' ? 'Activo' : 'Bloqueado') },
    { label: 'Rol', class: 'min-w-[140px]', value: r => (r.is_super_admin ? 'Super Admin' : 'Usuario') },
  ]

  const defaults: UserForm = {
    name: '',
    email: '',
    password: '',
    status: 'active',
    is_super_admin: false,
    mod_agenda: true,
    mod_pacientes: true,
    mod_sesiones: true,
    mod_ejercicios: true,
    mod_archivos: true,
    mod_reportes: false,
    mod_cobranza: false,
    mod_config: false,
  }

  const fields: CrudField<UserForm>[] = [
    { key: 'name', label: 'Nombre', type: 'text' },
    { key: 'email', label: 'Email', type: 'text' },
    { key: 'password', label: 'Password (solo si cambia)', type: 'text' },
    { key: 'status', label: 'Estatus', type: 'select', options: statusOptions },

    { key: 'is_super_admin', label: 'Super Admin', type: 'toggle' },
    { key: 'mod_agenda', label: 'Agenda', type: 'toggle' },
    { key: 'mod_pacientes', label: 'Pacientes', type: 'toggle' },
    { key: 'mod_sesiones', label: 'Sesiones', type: 'toggle' },
    { key: 'mod_ejercicios', label: 'Ejercicios', type: 'toggle' },
    { key: 'mod_archivos', label: 'Archivos', type: 'toggle' },
    { key: 'mod_reportes', label: 'Reportes', type: 'toggle' },
    { key: 'mod_cobranza', label: 'Cobranza', type: 'toggle' },
    { key: 'mod_config', label: 'Configuración', type: 'toggle' },
  ]

  const toForm = (r: any): UserForm => ({
    name: r.name ?? '',
    email: r.email ?? '',
    password: '',
    status: r.status ?? 'active',
    is_super_admin: Boolean(r.is_super_admin),
    mod_agenda: Boolean(r.mods?.agenda),
    mod_pacientes: Boolean(r.mods?.pacientes),
    mod_sesiones: Boolean(r.mods?.sesiones),
    mod_ejercicios: Boolean(r.mods?.ejercicios),
    mod_archivos: Boolean(r.mods?.archivos),
    mod_reportes: Boolean(r.mods?.reportes),
    mod_cobranza: Boolean(r.mods?.cobranza),
    mod_config: Boolean(r.mods?.config),
  })

  return { columns, fields, defaults, toForm, statusOptions }
}
