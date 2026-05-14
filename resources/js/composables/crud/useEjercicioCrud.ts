import { computed, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { swalConfirm, swalErr, swalToast } from '@/lib/swal'

export type EjercicioRow = {
  id: number
  name: string
  description?: string | null
  video_url?: string | null
  is_active: boolean
}

export const useEjercicioCrud = (filters: { q?: string; active?: string }) => {
  const page = usePage()
  const isOpen = ref(false)
  const editingId = ref<number | null>(null)

  const form = useForm({
    name: '',
    description: '',
    video_url: '',
    is_active: true,
  })

  const permissions = computed<string[]>(() => ((page.props as any).auth?.permissions ?? []) as string[])
  const isSuperAdmin = computed<boolean>(() => Boolean((page.props as any).auth?.user?.is_super_admin))
  const enabledModules = computed<Record<string, boolean>>(() => ((page.props as any).enabledModules ?? {}) as Record<string, boolean>)

  const can = (permission: string) => isSuperAdmin.value || permissions.value.includes(permission)
  const moduleEnabled = computed(() => enabledModules.value.ejercicios !== false)

  const applyFilters = (extra: Record<string, string | number>) => {
    router.get('/ejercicios', { ...filters, ...extra }, { preserveState: true, replace: true, preserveScroll: true })
  }

  const openCreate = () => {
    editingId.value = null
    form.reset()
    form.clearErrors()
    form.is_active = true
    isOpen.value = true
  }

  const openEdit = (row: EjercicioRow) => {
    editingId.value = row.id
    form.name = row.name
    form.description = row.description ?? ''
    form.video_url = row.video_url ?? ''
    form.is_active = Boolean(row.is_active)
    isOpen.value = true
  }

  const closeModal = () => {
    isOpen.value = false
    form.clearErrors()
  }

  const submit = async () => {
    const ok = await swalConfirm(editingId.value ? '¿Actualizar ejercicio?' : '¿Crear ejercicio?', 'Se actualizará el catálogo de sesiones.', editingId.value ? 'Sí, actualizar' : 'Sí, crear')
    if (!ok) return

    const options = {
      preserveScroll: true,
      onSuccess: () => {
        swalToast(editingId.value ? 'Ejercicio actualizado' : 'Ejercicio creado', 'success')
        closeModal()
      },
      onError: () => swalErr('No se pudo guardar', 'Verifica nombre y URL de video.'),
    }

    if (editingId.value) {
      form.put(`/ejercicios/${editingId.value}`, options)
      return
    }

    form.post('/ejercicios', options)
  }

  const toggleActive = async (row: EjercicioRow) => {
    const activate = !row.is_active
    const ok = await swalConfirm(
      activate ? '¿Marcar como disponible?' : '¿Ocultar del catálogo?',
      row.name,
      activate ? 'Sí, marcar disponible' : 'Sí, ocultar',
    )
    if (!ok) return

    router.patch(`/ejercicios/${row.id}/toggle-active`, {}, {
      preserveScroll: true,
      onSuccess: () => swalToast(activate ? 'Marcado como disponible' : 'Ocultado del catálogo', 'success'),
      onError: () => swalErr('No se pudo actualizar el estado'),
    })
  }

  const destroyEjercicio = async (row: EjercicioRow) => {
    const ok = await swalConfirm('¿Eliminar ejercicio?', row.name, 'Sí, eliminar')
    if (!ok) return

    router.delete(`/ejercicios/${row.id}`, {
      preserveScroll: true,
      onSuccess: () => swalToast('Ejercicio eliminado', 'success'),
    })
  }

  return { form, isOpen, editingId, can, moduleEnabled, applyFilters, openCreate, openEdit, closeModal, submit, toggleActive, destroyEjercicio }
}
