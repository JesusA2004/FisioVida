import { computed, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { swalConfirm, swalErr, swalToast } from '@/lib/swal'

export type PagoStatus = 'pending' | 'paid' | 'cancelled'
export type PaymentMethod = 'efectivo' | 'transferencia' | 'tarjeta_terminal' | 'deposito' | 'otro'

export type PagoRow = {
    id: number
    patient_persona_id?: number | null
    patient_name?: string | null
    concept?: string | null
    payment_method?: PaymentMethod | null
    amount: number
    currency: string
    status: PagoStatus
    paid_at?: string | null
    reference?: string | null
    notes?: string | null
    created_by?: number | null
    created_by_name?: string | null
    cancelled_at?: string | null
    cancelled_by?: number | null
    created_at?: string | null
    updated_at?: string | null
}

export const usePagoCrud = (filters: {
    q?: string
    status?: string
    payment_method?: string
    patient_persona_id?: string | number
    date_from?: string
    date_to?: string
}) => {
    const page = usePage()
    const isOpen = ref(false)
    const editingId = ref<number | null>(null)

    const form = useForm({
        patient_persona_id: null as number | null,
        concept: '',
        payment_method: 'efectivo' as PaymentMethod | '',
        amount: '' as number | '',
        currency: 'MXN',
        status: 'paid' as PagoStatus,
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
        router.get('/pagos', { ...filters, ...extra }, { preserveState: true, replace: true, preserveScroll: true })
    }

    const openCreate = (prefill?: { patient_persona_id?: number }) => {
        editingId.value = null
        form.reset()
        form.clearErrors()
        form.currency = 'MXN'
        form.status = 'paid'
        form.payment_method = 'efectivo'
        if (prefill?.patient_persona_id) form.patient_persona_id = prefill.patient_persona_id
        isOpen.value = true
    }

    const openEdit = (row: PagoRow) => {
        editingId.value = row.id
        form.patient_persona_id = row.patient_persona_id ?? null
        form.concept = row.concept ?? ''
        form.payment_method = (row.payment_method as PaymentMethod) ?? 'efectivo'
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
        const ok = await swalConfirm(
            editingId.value ? '¿Actualizar pago?' : '¿Registrar pago?',
            'Verifica monto, método y datos del pago.',
            editingId.value ? 'Sí, actualizar' : 'Sí, registrar',
        )
        if (!ok) return

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                swalToast(editingId.value ? 'Pago actualizado' : 'Pago registrado', 'success')
                closeModal()
            },
            onError: () => swalErr('No se pudo guardar', 'Revisa los datos del pago.'),
        }

        if (editingId.value) {
            form.put(`/pagos/${editingId.value}`, options)
            return
        }

        form.post('/pagos', options)
    }

    const cancelPago = async (row: PagoRow) => {
        const label = row.concept || row.reference || `Pago #${row.id}`
        const ok = await swalConfirm('¿Cancelar pago?', label, 'Sí, cancelar')
        if (!ok) return

        router.patch(`/pagos/${row.id}/cancelar`, {}, {
            preserveScroll: true,
            onSuccess: () => swalToast('Pago cancelado', 'success'),
            onError: () => swalErr('No se pudo cancelar el pago'),
        })
    }

    return {
        form,
        isOpen,
        editingId,
        can,
        moduleEnabled,
        applyFilters,
        openCreate,
        openEdit,
        closeModal,
        submit,
        cancelPago,
    }
}
