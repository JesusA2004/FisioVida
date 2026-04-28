import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { swalConfirm, swalToast } from '@/lib/swal'

export type PermissionOption = {
  id: number
  name: string
  slug: string
  module: string
}

export type RoleRow = {
  id: number
  name: string
  slug: string
  description?: string | null
  status: 'active' | 'inactive'
  permission_ids?: number[]
  permissions_count: number
}

export const useRolCrud = () => {
  const isOpen = ref(false)
  const editingId = ref<number | null>(null)

  const form = useForm({
    name: '',
    slug: '',
    description: '',
    status: 'active' as 'active' | 'inactive',
    permission_ids: [] as number[],
  })

  const isEditing = computed(() => editingId.value !== null)

  const openCreate = () => {
    editingId.value = null
    form.reset()
    form.status = 'active'
    form.permission_ids = []
    isOpen.value = true
  }

  const openEdit = (row: RoleRow) => {
    editingId.value = row.id
    form.name = row.name
    form.slug = row.slug
    form.description = row.description ?? ''
    form.status = row.status
    form.permission_ids = row.permission_ids ?? []
    isOpen.value = true
  }

  const closeModal = () => {
    isOpen.value = false
    form.clearErrors()
  }

  const togglePermission = (permissionId: number) => {
    if (form.permission_ids.includes(permissionId)) {
      form.permission_ids = form.permission_ids.filter(id => id !== permissionId)
      return
    }

    form.permission_ids = [...form.permission_ids, permissionId]
  }

  const submit = async () => {
    const confirm = await swalConfirm(
      isEditing.value ? '¿Deseas actualizar el rol?' : '¿Deseas crear este rol?',
      'Se guardarán también los permisos seleccionados.',
      isEditing.value ? 'Sí, actualizar' : 'Sí, crear'
    )

    if (!confirm) return

    if (isEditing.value && editingId.value) {
      form.put(route('roles.update', editingId.value), {
        preserveScroll: true,
        onSuccess: () => {
          swalToast('Rol actualizado correctamente', 'success')
          closeModal()
        },
      })
      return
    }

    form.post(route('roles.store'), {
      preserveScroll: true,
      onSuccess: () => {
        swalToast('Rol creado correctamente', 'success')
        closeModal()
      },
    })
  }

  const destroyRole = async (id: number) => {
    const confirm = await swalConfirm('¿Deseas eliminar este rol?', 'Esta acción se puede deshacer restaurando en BD.', 'Sí, eliminar')
    if (!confirm) return

    router.delete(route('roles.destroy', id), {
      preserveScroll: true,
      onSuccess: () => swalToast('Rol eliminado correctamente', 'success'),
    })
  }

  return {
    form,
    isOpen,
    isEditing,
    openCreate,
    openEdit,
    closeModal,
    togglePermission,
    submit,
    destroyRole,
  }
}
