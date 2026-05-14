import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { swalConfirm, swalToast } from '@/lib/swal'

export type PermissionRow = {
  id: number
  name: string
  slug: string
  module: string
  description?: string | null
  status: 'active' | 'inactive'
}

export const usePermisoCrud = () => {
  const isOpen = ref(false)
  const editingId = ref<number | null>(null)

  const form = useForm({
    name: '',
    slug: '',
    module: '',
    description: '',
    status: 'active' as 'active' | 'inactive',
  })

  const isEditing = computed(() => editingId.value !== null)

  const openCreate = () => {
    editingId.value = null
    form.reset()
    form.status = 'active'
    isOpen.value = true
  }

  const openEdit = (row: PermissionRow) => {
    editingId.value = row.id
    form.name = row.name
    form.slug = row.slug
    form.module = row.module
    form.description = row.description ?? ''
    form.status = row.status
    isOpen.value = true
  }

  const closeModal = () => {
    isOpen.value = false
    form.clearErrors()
  }

  const submit = async () => {
    const ok = await swalConfirm(
      isEditing.value ? '¿Deseas actualizar este permiso?' : '¿Deseas crear este permiso?',
      'Verifica el slug y el módulo antes de guardar.',
      isEditing.value ? 'Sí, actualizar' : 'Sí, crear'
    )

    if (!ok) return

    if (isEditing.value && editingId.value) {
      form.put(`/permisos/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
          swalToast('Permiso actualizado correctamente', 'success')
          closeModal()
        },
        onError: () => swalToast('Revisa el formulario', 'warning'),
      })
      return
    }

    form.post('/permisos', {
      preserveScroll: true,
      onSuccess: () => {
        swalToast('Permiso creado correctamente', 'success')
        closeModal()
      },
      onError: () => swalToast('Revisa el formulario', 'warning'),
    })
  }

  const toggleStatus = async (row: PermissionRow) => {
    const nextState = row.status === 'active' ? 'desactivar' : 'activar'
    const ok = await swalConfirm(
      `¿Deseas ${nextState} este permiso?`,
      'El cambio impacta la visibilidad y acceso por roles.',
      `Sí, ${nextState}`
    )

    if (!ok) return

    router.patch(`/permisos/${row.id}/toggle-status`, {}, {
      preserveScroll: true,
      onSuccess: () => swalToast('Estado actualizado correctamente', 'success'),
    })
  }

  const destroyPermission = async (id: number) => {
    const ok = await swalConfirm('¿Deseas eliminar este permiso?', 'Esta acción se registra en el sistema.', 'Sí, eliminar')
    if (!ok) return

    router.delete(`/permisos/${id}`, {
      preserveScroll: true,
      onSuccess: () => swalToast('Permiso eliminado correctamente', 'success'),
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
    toggleStatus,
    destroyPermission,
  }
}
