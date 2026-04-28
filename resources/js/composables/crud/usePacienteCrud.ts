import { computed, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { swalConfirm, swalErr, swalToast } from '@/lib/swal'

export type PacienteRow = {
  id: number
  full_name: string
  tipo: 'paciente' | 'ambos' | 'staff'
  status: 'active' | 'inactive'
  telefono?: string | null
  email?: string | null
  sexo?: 'M' | 'F' | 'X' | null
  fecha_nacimiento?: string | null
  direccion?: string | null
  contacto_emergencia_nombre?: string | null
  contacto_emergencia_telefono?: string | null
  notas?: string | null
}

export const usePacienteCrud = (filters: { q?: string; status?: string }) => {
  const page = usePage()
  const isOpen = ref(false)
  const editingId = ref<number | null>(null)

  const form = useForm({
    status: 'active' as 'active' | 'inactive',
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    fecha_nacimiento: '',
    sexo: '' as '' | 'M' | 'F' | 'X',
    telefono: '',
    email: '',
    direccion: '',
    contacto_emergencia_nombre: '',
    contacto_emergencia_telefono: '',
    notas: '',
  })

  const permissions = computed<string[]>(() => ((page.props as any).auth?.permissions ?? []) as string[])
  const isSuperAdmin = computed<boolean>(() => Boolean((page.props as any).auth?.user?.is_super_admin))
  const enabledModules = computed<Record<string, boolean>>(() => ((page.props as any).enabledModules ?? {}) as Record<string, boolean>)

  const can = (permission: string) => isSuperAdmin.value || permissions.value.includes(permission)
  const moduleEnabled = computed(() => enabledModules.value.pacientes !== false)

  const applyFilters = (extra: Record<string, string | number>) => {
    router.get(route('pacientes.index'), { ...filters, ...extra }, { preserveState: true, replace: true, preserveScroll: true })
  }

  const resetForm = () => {
    form.reset()
    form.clearErrors()
    form.status = 'active'
    form.sexo = ''
  }

  const openCreate = () => {
    editingId.value = null
    resetForm()
    isOpen.value = true
  }

  const openEdit = (row: PacienteRow) => {
    editingId.value = row.id
    form.status = row.status ?? 'active'
    const parts = (row.full_name ?? '').trim().split(/\s+/)
    form.nombres = parts.shift() ?? ''
    form.apellido_paterno = parts.shift() ?? ''
    form.apellido_materno = parts.join(' ')
    form.fecha_nacimiento = row.fecha_nacimiento ?? ''
    form.sexo = (row.sexo ?? '') as '' | 'M' | 'F' | 'X'
    form.telefono = row.telefono ?? ''
    form.email = row.email ?? ''
    form.direccion = row.direccion ?? ''
    form.contacto_emergencia_nombre = row.contacto_emergencia_nombre ?? ''
    form.contacto_emergencia_telefono = row.contacto_emergencia_telefono ?? ''
    form.notas = row.notas ?? ''
    isOpen.value = true
  }

  const closeModal = () => {
    isOpen.value = false
    form.clearErrors()
  }

  const submit = async () => {
    const ok = await swalConfirm(editingId.value ? '¿Actualizar paciente?' : '¿Crear paciente?', 'Verifica datos de contacto y estado.', editingId.value ? 'Sí, actualizar' : 'Sí, crear')
    if (!ok) return

    const options = {
      preserveScroll: true,
      onSuccess: () => {
        swalToast(editingId.value ? 'Paciente actualizado' : 'Paciente creado', 'success')
        closeModal()
      },
      onError: () => swalErr('No se pudo guardar', 'Revisa los datos del formulario.'),
    }

    if (editingId.value) {
      form.put(route('pacientes.update', editingId.value), options)
      return
    }

    form.post(route('pacientes.store'), options)
  }

  const destroyPaciente = async (row: PacienteRow) => {
    const ok = await swalConfirm('¿Desactivar/eliminar paciente?', row.full_name, 'Sí, eliminar')
    if (!ok) return

    router.delete(route('pacientes.destroy', row.id), {
      preserveScroll: true,
      onSuccess: () => swalToast('Paciente eliminado', 'success'),
      onError: () => swalErr('No se pudo eliminar'),
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
    destroyPaciente,
  }
}
