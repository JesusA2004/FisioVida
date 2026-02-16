// resources/js/Pages/Pagos/Pagos.ts
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'
import type { PagosPageProps, PaymentForm, PaymentRow } from './Pagos.types'

const money = (v: any) => {
  const n = Number(v ?? 0)
  return n.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
}

export function usePagosCrud(_props: PagosPageProps) {
  const statusOptions = [
    { value: 'pending', label: 'Pendiente' },
    { value: 'paid', label: 'Pagado' },
    { value: 'failed', label: 'Fallido' },
    { value: 'refunded', label: 'Reembolsado' },
  ]

  const columns: CrudColumn<PaymentRow>[] = [
    { label: 'Fecha', class: 'min-w-[160px]', value: r => r.created_at ?? '—' },
    { label: 'Proveedor', class: 'min-w-[140px] font-medium', value: r => r.provider },
    { label: 'Monto', class: 'min-w-[140px]', value: r => money(r.amount) },
    { label: 'Estado', class: 'min-w-[140px]', value: r => statusOptions.find(s => s.value === r.status)?.label ?? r.status },
    { label: 'Referencia', class: 'min-w-[200px]', value: r => r.reference ?? '—' },
  ]

  const defaults: PaymentForm = {
    provider: 'stripe',
    provider_payment_id: '',
    amount: '',
    currency: 'MXN',
    status: 'pending',
    paid_at: '',
    reference: '',
    notes: '',
  }

  const fields: CrudField<PaymentForm>[] = [
    { key: 'provider', label: 'Proveedor', type: 'text' },
    { key: 'provider_payment_id', label: 'Provider Payment ID', type: 'text' },
    { key: 'amount', label: 'Monto', type: 'number' },
    { key: 'currency', label: 'Moneda', type: 'text' },
    { key: 'status', label: 'Estatus', type: 'select', options: statusOptions },
    { key: 'paid_at', label: 'Pagado en', type: 'datetime' },
    { key: 'reference', label: 'Referencia', type: 'text' },
    { key: 'notes', label: 'Notas', type: 'textarea' },
  ]

  const toForm = (r: any): PaymentForm => ({
    provider: r.provider ?? '',
    provider_payment_id: r.provider_payment_id ?? '',
    amount: r.amount ?? '',
    currency: r.currency ?? 'MXN',
    status: r.status ?? 'pending',
    paid_at: r.paid_at ?? '',
    reference: r.reference ?? '',
    notes: r.notes ?? '',
  })

  return { columns, fields, defaults, toForm, statusOptions }
}
