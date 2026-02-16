// resources/js/Pages/Pacientes/Pacientes.types.ts
import type { PageMeta } from '@/components/fv/crud.types'

export type PacienteRow = {
    id: number
    full_name: string
    status: 'active'|'inactive'
    telefono: string | null
    email: string | null
    sexo: 'M'|'F'|'X'|null
    fecha_nacimiento: string | null
    direccion: string | null
    contacto_emergencia_nombre: string | null
    contacto_emergencia_telefono: string | null
    notas: string | null
}

export type PacienteForm = {
    status: PacienteRow['status']
    nombres: string
    apellido_paterno: string | null
    apellido_materno: string | null
    fecha_nacimiento: string | null
    sexo: PacienteRow['sexo']
    telefono: string | null
    email: string | null
    direccion: string | null
    contacto_emergencia_nombre: string | null
    contacto_emergencia_telefono: string | null
    notas: string | null
}

export type PacientesPageProps = {
    rows: PacienteRow[]
    page: PageMeta
    filters: { q?: string; status?: string }
}
