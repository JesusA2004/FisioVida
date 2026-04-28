import { computed, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { swalConfirm, swalErr, swalToast } from '@/lib/swal'

export type CitaRow = {
  id: number
  patient_persona_id: number
  therapist_user_id: number
  start_at: string
  end_at: string
  status: 'scheduled' | 'confirmed' | 'arrived' | 'no_show' | 'cancelled' | 'done'
  notes?: string | null
  patient_name: string
  therapist_name: string
}

export const useCitaCrud = (filters: { q?: string; status?: string }) => {
  const page = usePage()
  const isOpen = ref(false)
  const editingId = ref<number | null>(null)

  const form = useForm({
    patient_persona_id: '' as number | '',
    therapist_user_id: '' as number | '',
    start_at: '',
    end_at: '',
    status: 'scheduled' as CitaRow['status'],
    notes: '',
  })

  const permissions = computed<string[]>(() => ((page.props as any).auth?.permissions ?? []) as string[])
  const isSuperAdmin = computed<boolean>(() => Boolean((page.props as any).auth?.user?.is_super_admin))
  const enabledModules = computed<Record<string, boolean>>(() => ((page.props as any).enabledModules ?? {}) as Record<string, boolean>)

  const can = (permission: string) => isSuperAdmin.value || permissions.value.includes(permission)
  const moduleEnabled = computed(() => enabledModules.value.agenda !== false)

  const applyFilters = (extra: Record<string, string | number>) => {
    router.get(route('citas.index'), { ...filters, ...extra }, { preserveState: true, replace: true, preserveScroll: true })
  }

  const openCreate = () => {
    editingId.value = null
    form.reset()
    form.clearErrors()
    form.status = 'scheduled'
    isOpen.value = true
  }

  const openEdit = (row: CitaRow) => {
    editingId.value = row.id
    form.patient_persona_id = row.patient_persona_id
    form.therapist_user_id = row.therapist_user_id
    form.start_at = row.start_at?.slice(0, 16) ?? ''
    form.end_at = row.end_at?.slice(0, 16) ?? ''
    form.status = row.status
    form.notes = row.notes ?? ''
    isOpen.value = true
  }

  const closeModal = () => {
    isOpen.value = false
    form.clearErrors()
  }

  const submit = async () => {
    const ok = await swalConfirm(editingId.value ? '¿Actualizar cita?' : '¿Crear cita?', 'Se actualizará la agenda del paciente y terapeuta.', editingId.value ? 'Sí, actualizar' : 'Sí, crear')
    if (!ok) return

    const options = {
      preserveScroll: true,
      onSuccess: () => {
        swalToast(editingId.value ? 'Cita actualizada' : 'Cita creada', 'success')
        closeModal()
      },
      onError: () => swalErr('No se pudo guardar', 'Verifica fecha, terapeuta y paciente.'),
    }

    if (editingId.value) {
      form.put(route('citas.update', editingId.value), options)
      return
    }

    form.post(route('citas.store'), options)
  }

  const cancelCita = async (row: CitaRow) => {
    const ok = await swalConfirm('¿Cancelar cita?', `${row.patient_name} con ${row.therapist_name}`, 'Sí, cancelar')
    if (!ok) return

    router.put(route('citas.update', row.id), {
      patient_persona_id: row.patient_persona_id,
      therapist_user_id: row.therapist_user_id,
      start_at: row.start_at,
      end_at: row.end_at,
      status: 'cancelled',
      notes: row.notes ?? '',
    }, {
      preserveScroll: true,
      onSuccess: () => swalToast('Cita cancelada', 'success'),
      onError: () => swalErr('No se pudo cancelar la cita'),
    })
  }

  const destroyCita = async (row: CitaRow) => {
    const ok = await swalConfirm('¿Eliminar cita?', 'Esta acción no se puede deshacer.', 'Sí, eliminar')
    if (!ok) return

    router.delete(route('citas.destroy', row.id), {
      preserveScroll: true,
      onSuccess: () => swalToast('Cita eliminada', 'success'),
    })
  }

  return { form, isOpen, editingId, can, moduleEnabled, applyFilters, openCreate, openEdit, closeModal, submit, cancelCita, destroyCita }
}
