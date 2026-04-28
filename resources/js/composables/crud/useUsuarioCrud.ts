import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { swalConfirm, swalToast } from '@/lib/swal'

export type UsuarioRole = { id: number; name: string }

export type UsuarioRow = {
  id: number
  name: string
  email: string
  status: 'active' | 'blocked'
  is_super_admin: boolean
  roles: UsuarioRole[]
  role_ids: number[]
  mod_agenda: boolean
  mod_pacientes: boolean
  mod_sesiones: boolean
  mod_ejercicios: boolean
  mod_archivos: boolean
  mod_reportes: boolean
  mod_cobranza: boolean
  mod_config: boolean
}

export const useUsuarioCrud = () => {
  const isOpen = ref(false)
  const editingId = ref<number | null>(null)

  const form = useForm({
    name: '',
    email: '',
    password: '',
    status: 'active' as 'active' | 'blocked',
    is_super_admin: false,
    role_ids: [] as number[],
    mod_agenda: true,
    mod_pacientes: true,
    mod_sesiones: true,
    mod_ejercicios: true,
    mod_archivos: true,
    mod_reportes: false,
    mod_cobranza: false,
    mod_config: false,
  })

  const openCreate = () => {
    editingId.value = null
    form.reset()
    form.status = 'active'
    form.role_ids = []
    isOpen.value = true
  }

  const openEdit = (row: UsuarioRow) => {
    editingId.value = row.id
    form.name = row.name
    form.email = row.email
    form.password = ''
    form.status = row.status
    form.is_super_admin = row.is_super_admin
    form.role_ids = row.role_ids ?? []
    form.mod_agenda = row.mod_agenda
    form.mod_pacientes = row.mod_pacientes
    form.mod_sesiones = row.mod_sesiones
    form.mod_ejercicios = row.mod_ejercicios
    form.mod_archivos = row.mod_archivos
    form.mod_reportes = row.mod_reportes
    form.mod_cobranza = row.mod_cobranza
    form.mod_config = row.mod_config
    isOpen.value = true
  }

  const toggleRole = (roleId: number) => {
    if (form.role_ids.includes(roleId)) {
      form.role_ids = form.role_ids.filter(id => id !== roleId)
      return
    }

    form.role_ids = [...form.role_ids, roleId]
  }

  const closeModal = () => {
    isOpen.value = false
    form.clearErrors()
  }

  const submit = async () => {
    const ok = await swalConfirm(
      editingId.value ? '¿Deseas actualizar este usuario?' : '¿Deseas crear este usuario?',
      'Se guardarán roles y permisos heredados por rol.',
      editingId.value ? 'Sí, actualizar' : 'Sí, crear'
    )
    if (!ok) return

    if (editingId.value) {
      form.put(route('usuarios.update', editingId.value), {
        preserveScroll: true,
        onSuccess: () => {
          swalToast('Usuario actualizado correctamente', 'success')
          closeModal()
        },
      })
      return
    }

    form.post(route('usuarios.store'), {
      preserveScroll: true,
      onSuccess: () => {
        swalToast('Usuario creado correctamente', 'success')
        closeModal()
      },
    })
  }

  const destroyUser = async (id: number) => {
    const ok = await swalConfirm('¿Deseas eliminar este usuario?', 'El usuario quedará desactivado por soft delete.', 'Sí, eliminar')
    if (!ok) return

    router.delete(route('usuarios.destroy', id), {
      preserveScroll: true,
      onSuccess: () => swalToast('Usuario eliminado correctamente', 'success'),
    })
  }

  return {
    form,
    isOpen,
    openCreate,
    openEdit,
    toggleRole,
    closeModal,
    submit,
    destroyUser,
    editingId,
  }
}
