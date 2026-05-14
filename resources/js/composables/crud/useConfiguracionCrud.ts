import { useForm } from '@inertiajs/vue3'
import { swalConfirm, swalToast } from '@/lib/swal'

export type ModuleRow = {
  id: number
  module: string
  label: string
  description?: string | null
  enabled: boolean
  sort_order: number
}

export const useConfiguracionCrud = (settingsMap: Record<string, string | null>, modules: ModuleRow[]) => {
  const form = useForm({
    clinic_name: settingsMap.clinic_name ?? '',
    clinic_logo: settingsMap.clinic_logo ?? '',
    clinic_phone: settingsMap.clinic_phone ?? '',
    clinic_email: settingsMap.clinic_email ?? '',
    clinic_address: settingsMap.clinic_address ?? '',
    primary_color: settingsMap.primary_color ?? '#3B82F6',
    secondary_color: settingsMap.secondary_color ?? '#22C55E',
    accent_color: settingsMap.accent_color ?? '#F59E0B',
    default_currency: settingsMap.default_currency ?? 'MXN',
    appointment_default_duration: Number(settingsMap.appointment_default_duration ?? 60),
    demo_mode: String(settingsMap.demo_mode ?? '1') === '1',
    dark_mode_enabled: String(settingsMap.dark_mode_enabled ?? '1') === '1',
  })

  const modulesForm = useForm({
    modules: modules.map(item => ({ module: item.module, enabled: item.enabled })),
  })

  const saveSettings = async () => {
    const ok = await swalConfirm('¿Guardar cambios de configuración?', 'Se actualizarán los datos generales de la clínica.', 'Sí, guardar')
    if (!ok) return

    form.put('/configuracion', {
      preserveScroll: true,
      onSuccess: () => swalToast('Configuración guardada correctamente', 'success'),
      onError: () => swalToast('Revisa los campos del formulario', 'warning'),
    })
  }

  const saveModules = async () => {
    const ok = await swalConfirm('¿Guardar módulos habilitados?', 'Esto afectará la visibilidad global del sistema.', 'Sí, guardar')
    if (!ok) return

    modulesForm.patch('/configuracion/modulos', {
      preserveScroll: true,
      onSuccess: () => swalToast('Módulos actualizados correctamente', 'success'),
      onError: () => swalToast('No se pudieron actualizar los módulos', 'error'),
    })
  }

  return {
    form,
    modulesForm,
    saveSettings,
    saveModules,
  }
}
