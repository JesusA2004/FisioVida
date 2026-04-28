import { computed, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { swalConfirm, swalErr, swalToast } from '@/lib/swal'

export type PagoStatus = 'pending' | 'paid' | 'failed' | 'refunded'

export type PagoRow = {
  id: number
  provider: string
  provider_payment_id?: string | null
  amount: number
  currency: string
  status: PagoStatus
  paid_at?: string | null
  reference?: string | null
  notes?: string | null
}

export const usePagoCrud = (filters: { q?: string; status?: string }) => {
  const page = usePage()
  const isOpen = ref(false)
  const editingId = ref<number | null>(null)

  const form = useForm({
    provider: '',
    provider_payment_id: '',
    amount: '' as number | '',
    currency: 'MXN',
    status: 'pending' as PagoStatus,
    paid_at: '',
    reference: '',
    notes: '',
  })

  const permissions = computed<string[]>(() => ((page.props as any).auth?.permissions ?? []) as string[])
  const isSuperAdmin = computed<boolean>(() => Boolean((page.props as any).auth?.user?.is_super_admin))
  const enabledModules = computed<Record<string, boolean>>(() => ((page.props as any).enabledModules ?? {}) as Record<string, boolean>)

  const can = (permission: string) => isSuperAdmin.value || permissions.value.includes(permission)
  const moduleEnabled = computed(() => enabledModules.value.cobranza !== false)

  const applyFilters = (extra: Record<string, string | number>) => {
    router.get(route('pagos.index'), { ...filters, ...extra }, { preserveState: true, replace: true, preserveScroll: true })
  }

  const openCreate = () => {
    editingId.value = null
    form.reset()
    form.clearErrors()
    form.currency = 'MXN'
    form.status = 'pending'
    isOpen.value = true
  }

  const openEdit = (row: PagoRow) => {
    editingId.value = row.id
    form.provider = row.provider
    form.provider_payment_id = row.provider_payment_id ?? ''
    form.amount = row.amount
    form.currency = row.currency ?? 'MXN'
    form.status = row.status
    form.paid_at = row.paid_at?.slice(0, 16) ?? ''
    form.reference = row.reference ?? ''
    form.notes = row.notes ?? ''
    isOpen.value = true
  }

  const closeModal = () => {
    isOpen.value = false
    form.clearErrors()
  }

  const submit = async () => {
    const ok = await swalConfirm(editingId.value ? '¿Actualizar pago?' : '¿Crear pago?', 'Verifica monto, moneda y estatus.', editingId.value ? 'Sí, actualizar' : 'Sí, crear')
    if (!ok) return

    const options = {
      preserveScroll: true,
      onSuccess: () => {
        swalToast(editingId.value ? 'Pago actualizado' : 'Pago creado', 'success')
        closeModal()
      },
      onError: () => swalErr('No se pudo guardar', 'Revisa monto y proveedor.'),
    }

    if (editingId.value) {
      form.put(route('pagos.update', editingId.value), options)
      return
    }

    form.post(route('pagos.store'), options)
  }

  const setStatus = async (row: PagoRow, status: PagoStatus) => {
    const label = status === 'paid' ? 'marcar como pagado' : status === 'refunded' ? 'marcar como reembolsado' : 'marcar como cancelado/fallido'
    const ok = await swalConfirm(`¿Deseas ${label}?`, row.reference || `Pago #${row.id}`, 'Sí, confirmar')
    if (!ok) return

    router.put(route('pagos.update', row.id), {
      provider: row.provider,
      provider_payment_id: row.provider_payment_id ?? '',
      amount: row.amount,
      currency: row.currency,
      status,
      paid_at: status === 'paid' ? new Date().toISOString().slice(0, 19).replace('T', ' ') : row.paid_at,
      reference: row.reference ?? '',
      notes: row.notes ?? '',
    }, {
      preserveScroll: true,
      onSuccess: () => swalToast('Estado de pago actualizado', 'success'),
      onError: () => swalErr('No se pudo actualizar el estado'),
    })
  }

  const destroyPago = async (row: PagoRow) => {
    const ok = await swalConfirm('¿Eliminar pago?', row.reference || row.provider, 'Sí, eliminar')
    if (!ok) return

    router.delete(route('pagos.destroy', row.id), {
      preserveScroll: true,
      onSuccess: () => swalToast('Pago eliminado', 'success'),
    })
  }

  return { form, isOpen, editingId, can, moduleEnabled, applyFilters, openCreate, openEdit, closeModal, submit, setStatus, destroyPago }
}
