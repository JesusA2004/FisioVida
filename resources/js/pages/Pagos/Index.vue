<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import type { CrudColumn, CrudField } from '@/components/fv/crud.types'

type PaymentRow = {
  id: number
  provider: string
  amount: string | number
  currency: string
  status: 'pending' | 'paid' | 'failed' | 'refunded'
  paid_at?: string | null
  reference?: string | null
  notes?: string | null
}

type PaymentForm = {
  provider: string
  provider_payment_id: string
  amount: string | number
  currency: string
  status: 'pending' | 'paid' | 'failed' | 'refunded'
  paid_at: string
  reference: string
  notes: string
}

const props = defineProps<{
  rows: PaymentRow[]
  page: any
  filters: any
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Cobranza', href: '/pagos' }]

const money = (v: any) => {
  const n = Number(v ?? 0)
  return n.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
}

const statusOptions = [
  { value: 'pending', label: 'Pendiente' },
  { value: 'paid', label: 'Pagado' },
  { value: 'failed', label: 'Fallido' },
  { value: 'refunded', label: 'Reembolsado' },
]

const columns: CrudColumn<PaymentRow>[] = [
  { label: 'Monto', value: r => money(r.amount), class: 'w-[160px]' },
  { label: 'Estatus', value: r => r.status, class: 'w-[140px]' },
  { label: 'Proveedor', value: r => r.provider, class: 'w-[170px]' },
  { label: 'Pagado', value: r => r.paid_at ?? '—', class: 'w-[170px]' },
  { label: 'Referencia', value: r => r.reference ?? '—' },
]

const fields: CrudField<PaymentForm>[] = [
  { key: 'provider', label: 'Proveedor', type: 'text', span: 1, placeholder: 'stripe / mercadopago...' },
  { key: 'provider_payment_id', label: 'ID proveedor', type: 'text', span: 1 },
  { key: 'amount', label: 'Monto', type: 'number', span: 1 },
  { key: 'currency', label: 'Moneda', type: 'text', span: 1, placeholder: 'MXN' },
  { key: 'status', label: 'Estatus', type: 'select', options: statusOptions, span: 1 },
  { key: 'paid_at', label: 'Fecha pago', type: 'datetime', span: 1 },
  { key: 'reference', label: 'Referencia', type: 'text', span: 2 },
  { key: 'notes', label: 'Notas', type: 'textarea', span: 2 },
]

const defaults: PaymentForm = {
  provider: '',
  provider_payment_id: '',
  amount: '',
  currency: 'MXN',
  status: 'pending',
  paid_at: '',
  reference: '',
  notes: '',
}

const toForm = (row: PaymentRow): PaymentForm => ({
  provider: row.provider ?? '',
  provider_payment_id: (row as any).provider_payment_id ?? '',
  amount: row.amount ?? '',
  currency: row.currency ?? 'MXN',
  status: row.status ?? 'pending',
  paid_at: row.paid_at ?? '',
  reference: row.reference ?? '',
  notes: row.notes ?? '',
})
</script>

<template>
  <Head title="Pagos" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Pagos"
      subtitle="Control de cobranza"
      create-label="Nuevo pago"
      route-base="payments"
      :rows="props.rows"
      :page="props.page"
      :filters="props.filters"
      :columns="columns"
      :fields="fields"
      :defaults="defaults"
      :to-form="toForm"
      :status-options="statusOptions"
      modal-max-width-class="max-w-4xl"
    />
  </AppLayout>
</template>
