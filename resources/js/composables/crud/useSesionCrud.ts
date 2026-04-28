import { computed, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { swalConfirm, swalErr, swalToast } from '@/lib/swal'

export type SesionRow = {
  id: number
  appointment_id?: number | null
  patient_persona_id: number
  therapist_user_id: number
  session_date: string
  pain_scale?: number | null
  subjective?: string | null
  objective?: string | null
  assessment?: string | null
  plan?: string | null
  notes?: string | null
  patient_name: string
  therapist_name: string
}

export const useSesionCrud = (filters: { q?: string }) => {
  const page = usePage()
  const isOpen = ref(false)
  const editingId = ref<number | null>(null)

  const form = useForm({
    appointment_id: '' as number | '',
    patient_persona_id: '' as number | '',
    therapist_user_id: '' as number | '',
    session_date: '',
    subjective: '',
    objective: '',
    assessment: '',
    plan: '',
    pain_scale: '' as number | '',
    notes: '',
  })

  const permissions = computed<string[]>(() => ((page.props as any).auth?.permissions ?? []) as string[])
  const isSuperAdmin = computed<boolean>(() => Boolean((page.props as any).auth?.user?.is_super_admin))
  const enabledModules = computed<Record<string, boolean>>(() => ((page.props as any).enabledModules ?? {}) as Record<string, boolean>)

  const can = (permission: string) => isSuperAdmin.value || permissions.value.includes(permission)
  const moduleEnabled = computed(() => enabledModules.value.sesiones !== false)

  const applyFilters = (extra: Record<string, string | number>) => {
    router.get(route('sesiones.index'), { ...filters, ...extra }, { preserveState: true, replace: true, preserveScroll: true })
  }

  const openCreate = () => {
    editingId.value = null
    form.reset()
    form.clearErrors()
    isOpen.value = true
  }

  const openEdit = (row: SesionRow) => {
    editingId.value = row.id
    form.appointment_id = row.appointment_id ?? ''
    form.patient_persona_id = row.patient_persona_id
    form.therapist_user_id = row.therapist_user_id
    form.session_date = row.session_date?.slice(0, 10) ?? ''
    form.subjective = row.subjective ?? ''
    form.objective = row.objective ?? ''
    form.assessment = row.assessment ?? ''
    form.plan = row.plan ?? ''
    form.pain_scale = (row.pain_scale ?? '') as number | ''
    form.notes = row.notes ?? ''
    isOpen.value = true
  }

  const closeModal = () => {
    isOpen.value = false
    form.clearErrors()
  }

  const submit = async () => {
    const ok = await swalConfirm(editingId.value ? '¿Actualizar sesión?' : '¿Crear sesión?', 'Asegúrate de completar SOAP y escala de dolor.', editingId.value ? 'Sí, actualizar' : 'Sí, crear')
    if (!ok) return

    const options = {
      preserveScroll: true,
      onSuccess: () => {
        swalToast(editingId.value ? 'Sesión actualizada' : 'Sesión creada', 'success')
        closeModal()
      },
      onError: () => swalErr('No se pudo guardar', 'Revisa paciente, terapeuta y fecha.'),
    }

    if (editingId.value) {
      form.put(route('sesiones.update', editingId.value), options)
      return
    }

    form.post(route('sesiones.store'), options)
  }

  const destroySesion = async (row: SesionRow) => {
    const ok = await swalConfirm('¿Eliminar sesión?', `${row.patient_name} · ${row.session_date}`, 'Sí, eliminar')
    if (!ok) return

    router.delete(route('sesiones.destroy', row.id), {
      preserveScroll: true,
      onSuccess: () => swalToast('Sesión eliminada', 'success'),
    })
  }

  return { form, isOpen, editingId, can, moduleEnabled, applyFilters, openCreate, openEdit, closeModal, submit, destroySesion }
}
