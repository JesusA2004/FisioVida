// resources/js/Pages/Agenda/Agenda.types.ts
import type { PageMeta } from '@/components/fv/crud.types'

export type AppointmentRow = {
    id: number
    patient_persona_id: number
    therapist_user_id: number
    start_at: string
    end_at: string
    status: 'scheduled'|'confirmed'|'arrived'|'no_show'|'cancelled'|'done'
    notes: string | null
    patient_name: string
    therapist_name: string
}

export type AppointmentForm = {
    patient_persona_id: number | ''
    therapist_user_id: number | ''
    start_at: string
    end_at: string
    status: AppointmentRow['status']
    notes: string | null
}

export type AgendaPageProps = {
    rows: AppointmentRow[]
    page: PageMeta
    filters: { q?: string; status?: string }
    lookups: {
        patients: { id: number; label: string }[]
        therapists: { id: number; label: string }[]
    }
}
