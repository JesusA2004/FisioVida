// resources/js/Pages/Pagos/Pagos.types.ts
import type { PageMeta } from '@/components/fv/crud.types'

export type PaymentRow = {
    id: number
    provider: string
    provider_payment_id: string | null
    amount: string | number
    currency: string
    status: 'pending'|'paid'|'failed'|'refunded'
    paid_at: string | null
    reference: string | null
    notes: string | null
    created_at: string | null
}

export type PaymentForm = {
    provider: string
    provider_payment_id: string | null
    amount: string | number
    currency: string
    status: PaymentRow['status']
    paid_at: string | null
    reference: string | null
    notes: string | null
}

export type PagosPageProps = {
    rows: PaymentRow[]
    page: PageMeta
    filters: { q?: string; status?: string }
}
