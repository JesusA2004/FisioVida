import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { swalConfirm, swalToast } from '@/lib/swal'

export type ActivityRow = {
  id: number
  title: string
  description?: string | null
  responsible_user_id?: number | null
  responsible_name?: string | null
  priority: 'low' | 'medium' | 'high' | 'urgent'
  status: 'pending' | 'in_progress' | 'on_hold' | 'completed' | 'cancelled' | 'overdue'
  due_date?: string | null
  is_overdue?: boolean
}

export type UserOption = { id: number; name: string }

export const useActividadCrud = () => {
  const isOpen = ref(false)
  const editingId = ref<number | null>(null)

  const form = useForm({
    title: '',
    description: '',
    responsible_user_id: '' as number | '' | null,
    priority: 'medium' as ActivityRow['priority'],
    status: 'pending' as ActivityRow['status'],
    due_date: '',
  })

  const isEditing = computed(() => editingId.value !== null)

  const openCreate = () => {
    editingId.value = null
    form.reset()
    form.priority = 'medium'
    form.status = 'pending'
    form.responsible_user_id = ''
    isOpen.value = true
  }

  const openEdit = (row: ActivityRow) => {
    editingId.value = row.id
    form.title = row.title
    form.description = row.description ?? ''
    form.priority = row.priority
    form.status = row.status
    form.responsible_user_id = row.responsible_user_id ?? ''
    form.due_date = row.due_date?.slice(0, 16) ?? ''
    isOpen.value = true
  }

  const closeModal = () => {
    isOpen.value = false
    form.clearErrors()
  }

  const submit = async () => {
    const ok = await swalConfirm(
      isEditing.value ? '¿Deseas actualizar esta actividad?' : '¿Deseas crear esta actividad?',
      'Verifica prioridad, estado y fecha límite.',
      isEditing.value ? 'Sí, actualizar' : 'Sí, crear'
    )
    if (!ok) return

    if (editingId.value) {
      form.put(route('actividades.update', editingId.value), {
        preserveScroll: true,
        onSuccess: () => {
          swalToast('Actividad actualizada correctamente', 'success')
          closeModal()
        },
        onError: () => swalToast('Revisa el formulario', 'warning'),
      })
      return
    }

    form.post(route('actividades.store'), {
      preserveScroll: true,
      onSuccess: () => {
        swalToast('Actividad creada correctamente', 'success')
        closeModal()
      },
      onError: () => swalToast('Revisa el formulario', 'warning'),
    })
  }

  const complete = async (row: ActivityRow) => {
    const ok = await swalConfirm('¿Deseas completar esta actividad?', row.title, 'Sí, completar')
    if (!ok) return

    router.patch(route('actividades.complete', row.id), {}, {
      preserveScroll: true,
      onSuccess: () => swalToast('Actividad completada', 'success'),
    })
  }

  const cancel = async (row: ActivityRow) => {
    const ok = await swalConfirm('¿Deseas cancelar esta actividad?', row.title, 'Sí, cancelar')
    if (!ok) return

    router.patch(route('actividades.cancel', row.id), {}, {
      preserveScroll: true,
      onSuccess: () => swalToast('Actividad cancelada', 'success'),
    })
  }

  const destroyActivity = async (id: number) => {
    const ok = await swalConfirm('¿Deseas eliminar esta actividad?', 'Esta acción la quitará del listado activo.', 'Sí, eliminar')
    if (!ok) return

    router.delete(route('actividades.destroy', id), {
      preserveScroll: true,
      onSuccess: () => swalToast('Actividad eliminada', 'success'),
    })
  }

  return {
    form,
    isOpen,
    isEditing,
    openCreate,
    openEdit,
    closeModal,
    submit,
    complete,
    cancel,
    destroyActivity,
  }
}
