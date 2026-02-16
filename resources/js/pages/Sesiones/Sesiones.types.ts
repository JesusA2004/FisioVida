// resources/js/Pages/Sesiones/Sesiones.types.ts
import type { PageMeta } from '@/components/fv/crud.types'

export type SessionRow = {
    id: number
    appointment_id: number | null
    patient_persona_id: number
    therapist_user_id: number
    session_date: string
    pain_scale: number | null
    notes: string | null
    patient_name: string
    therapist_name: string
    subjective: string | null
    objective: string | null
    assessment: string | null
    plan: string | null
}

export type SessionForm = {
    appointment_id: number | ''
    patient_persona_id: number | ''
    therapist_user_id: number | ''
    session_date: string
    pain_scale: number | ''
    subjective: string | null
    objective: string | null
    assessment: string | null
    plan: string | null
    notes: string | null
}

export type SesionesPageProps = {
    rows: SessionRow[]
    page: PageMeta
    filters: { q?: string }
    lookups: {
        patients: { id: number; label: string }[]
        therapists: { id: number; label: string }[]
        appointments: { id: number; label: string }[]
    }
}
