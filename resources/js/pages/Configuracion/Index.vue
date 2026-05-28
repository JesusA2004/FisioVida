<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import type { ModuleRow } from '@/composables/crud/useConfiguracionCrud'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import {
    Save,
    Settings2,
    Building2,
    Palette,
    SlidersHorizontal,
    Blocks,
    UploadCloud,
    ImageIcon,
    RefreshCw,
    Loader2,
    CheckCircle2,
    ShieldCheck,
    FileText,
    AlertTriangle,
} from 'lucide-vue-next'
import { tModule } from '@/lib/labels'
import { swalConfirm, swalToast } from '@/lib/swal'

const props = defineProps<{
    settings: any[]
    settingsMap: Record<string, string | null>
    modules: ModuleRow[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/configuracion' },
]

const form = useForm({
    clinic_name: props.settingsMap.clinic_name ?? '',
    clinic_logo: props.settingsMap.clinic_logo ?? '',
    clinic_logo_file: null as File | null,
    clinic_phone: props.settingsMap.clinic_phone ?? '',
    clinic_email: props.settingsMap.clinic_email ?? '',
    clinic_address: props.settingsMap.clinic_address ?? '',

    primary_color: props.settingsMap.primary_color ?? '#0EA5A4',
    primary_hover_color: props.settingsMap.primary_hover_color ?? '#0B8F8E',
    primary_foreground_color: props.settingsMap.primary_foreground_color ?? '#ffffff',
    app_background_color: props.settingsMap.app_background_color ?? '#F6FAFB',
    card_background_color: props.settingsMap.card_background_color ?? '#ffffff',
    sidebar_background_color: props.settingsMap.sidebar_background_color ?? '#F0F7F7',

    default_currency: props.settingsMap.default_currency ?? 'MXN',
    appointment_default_duration: Number(
        props.settingsMap.appointment_default_duration ?? 60,
    ),
    dark_mode_enabled:
        String(props.settingsMap.dark_mode_enabled ?? '1') === '1',

    // ── Cumplimiento y privacidad ─────────────────────────────────────────
    clinic_sector: props.settingsMap.clinic_sector ?? 'physiotherapy',
    legal_business_name: props.settingsMap.legal_business_name ?? '',
    legal_representative: props.settingsMap.legal_representative ?? '',
    privacy_responsible_name: props.settingsMap.privacy_responsible_name ?? '',
    privacy_contact_email: props.settingsMap.privacy_contact_email ?? '',
    privacy_contact_phone: props.settingsMap.privacy_contact_phone ?? '',
    privacy_address: props.settingsMap.privacy_address ?? '',
    privacy_notice_version: props.settingsMap.privacy_notice_version ?? '1.0',
    privacy_notice_effective_date: props.settingsMap.privacy_notice_effective_date ?? '',
    privacy_notice_text: props.settingsMap.privacy_notice_text ?? '',
    sensitive_data_consent_text: props.settingsMap.sensitive_data_consent_text ?? '',
    treatment_consent_text: props.settingsMap.treatment_consent_text ?? '',
    image_consent_text: props.settingsMap.image_consent_text ?? '',
    minor_consent_text: props.settingsMap.minor_consent_text ?? '',
    data_retention_policy_text: props.settingsMap.data_retention_policy_text ?? '',
    allow_patient_portal_acceptance:
        String(props.settingsMap.allow_patient_portal_acceptance ?? '1') === '1',
    require_privacy_notice_before_session:
        String(props.settingsMap.require_privacy_notice_before_session ?? '0') === '1',
    require_treatment_consent_before_session:
        String(props.settingsMap.require_treatment_consent_before_session ?? '0') === '1',
    require_image_consent_for_uploads:
        String(props.settingsMap.require_image_consent_for_uploads ?? '0') === '1',
})

const modulesForm = useForm({
    modules: props.modules.map((item) => ({
        module: item.module,
        enabled: item.enabled,
    })),
})

const fileInput = ref<HTMLInputElement | null>(null)
const logoPreview = ref<string | null>(form.clinic_logo || null)

const activeModules = computed(
    () => modulesForm.modules.filter((item) => item.enabled).length,
)

const applyThemeColors = () => {
    const root = document.documentElement
    const dark = root.classList.contains('dark')

    // El color primario aplica en ambos modos
    root.style.setProperty('--primary', form.primary_color)
    root.style.setProperty('--color-primary', form.primary_color)
    root.style.setProperty('--primary-hover', form.primary_hover_color)
    root.style.setProperty('--ring', form.primary_color)
    root.style.setProperty('--color-ring', form.primary_color)
    root.style.setProperty('--primary-foreground', form.primary_foreground_color)
    root.style.setProperty('--color-primary-foreground', form.primary_foreground_color)

    // Los fondos configurados solo aplican en light mode.
    // En dark mode el bloque .dark del CSS define la paleta premium.
    if (!dark) {
        root.style.setProperty('--background', form.app_background_color)
        root.style.setProperty('--color-background', form.app_background_color)
        root.style.setProperty('--card', form.card_background_color)
        root.style.setProperty('--color-card', form.card_background_color)
        root.style.setProperty('--sidebar-background', form.sidebar_background_color)
        root.style.setProperty('--color-sidebar', form.sidebar_background_color)
    }
}

watch(
    () => [
        form.primary_color,
        form.primary_hover_color,
        form.primary_foreground_color,
        form.app_background_color,
        form.card_background_color,
        form.sidebar_background_color,
    ],
    () => applyThemeColors(),
    { immediate: true },
)

const triggerLogoInput = () => {
    fileInput.value?.click()
}

const onLogoSelected = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0] ?? null

    if (!file) return

    form.clinic_logo_file = file
    logoPreview.value = URL.createObjectURL(file)
}

const setLogoFile = (file: File | null) => {
    if (!file) return

    const allowedTypes = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
        'image/svg+xml',
    ]

    if (!allowedTypes.includes(file.type)) {
        swalToast('El logo debe ser PNG, JPG, WEBP o SVG', 'error')
        return
    }

    form.clinic_logo_file = file
    logoPreview.value = URL.createObjectURL(file)
}

const onLogoDropped = (event: DragEvent) => {
    const file = event.dataTransfer?.files?.[0] ?? null
    setLogoFile(file)
}

const restorePreviousLogo = () => {
    form.clinic_logo_file = null
    logoPreview.value = form.clinic_logo || null

    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const saveSettings = async () => {
    const ok = await swalConfirm(
        '¿Guardar configuración?',
        'Se actualizarán los datos visuales y generales del sistema.',
        'Sí, guardar',
    )

    if (!ok) return

    form
        .transform((data) => ({
            ...data,
            dark_mode_enabled: data.dark_mode_enabled ? '1' : '0',
            allow_patient_portal_acceptance: data.allow_patient_portal_acceptance ? '1' : '0',
            require_privacy_notice_before_session: data.require_privacy_notice_before_session ? '1' : '0',
            require_treatment_consent_before_session: data.require_treatment_consent_before_session ? '1' : '0',
            require_image_consent_for_uploads: data.require_image_consent_for_uploads ? '1' : '0',
        }))
        .post('/configuracion', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                applyThemeColors()
                swalToast('Configuración guardada correctamente', 'success')
            },
            onError: () => {
                swalToast('Revisa los campos del formulario', 'error')
            },
        })
}

const saveModules = async () => {
    const ok = await swalConfirm(
        '¿Guardar módulos?',
        'Esto cambiará la disponibilidad de módulos del sistema.',
        'Sí, guardar',
    )

    if (!ok) return

    modulesForm.patch('/configuracion/modulos', {
        preserveScroll: true,
        onSuccess: () =>
            swalToast('Módulos actualizados correctamente', 'success'),
        onError: () =>
            swalToast('No se pudieron actualizar los módulos', 'error'),
    })
}

const toggleModule = (moduleName: string, value: boolean) => {
    const current = modulesForm.modules.find((m) => m.module === moduleName)

    if (current) current.enabled = value
}

const inputClass =
    'h-11 rounded-2xl border-border bg-card px-4 text-foreground shadow-sm transition-all duration-200 placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary/20 dark:border-border'

const cardClass =
    'rounded-[2rem] border border-border bg-card p-4 shadow-sm transition-all duration-300 hover:shadow-md sm:p-5'

const titleClass =
    'flex items-center gap-2 text-lg font-semibold text-foreground'

const colorInputClass =
    'h-11 w-14 shrink-0 rounded-2xl border-border bg-card p-1 shadow-sm'

const colorTextClass =
    'h-11 rounded-2xl border-border bg-card px-4 text-sm font-semibold text-foreground shadow-sm'

const hoverButtonStyle = (normal: string, hover: string) => ({
    backgroundColor: normal,
})

const setHoverColor = (event: MouseEvent, color: string) => {
    ;(event.currentTarget as HTMLElement).style.backgroundColor = color
}
</script>

<template>
    <Head title="Configuración" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="w-full space-y-4 px-2 py-1 sm:px-3 lg:px-4">
            <header class="overflow-hidden rounded-[2rem] border border-border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl shadow-lg"
                            :style="{
                                backgroundColor: form.primary_color,
                                color: form.primary_foreground_color,
                            }"
                        >
                            <Settings2 class="h-6 w-6" />
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold text-foreground">
                                Configuración general
                            </h1>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Personaliza datos de clínica, colores principales y módulos del sistema.
                            </p>
                        </div>
                    </div>

                    <Button
                        class="h-11 rounded-2xl px-6 shadow-lg transition-all duration-200 hover:-translate-y-0.5"
                        :style="{
                            backgroundColor: form.primary_color,
                            color: form.primary_foreground_color,
                        }"
                        :disabled="form.processing"
                        @mouseenter="setHoverColor($event, form.primary_hover_color)"
                        @mouseleave="setHoverColor($event, form.primary_color)"
                        @click="saveSettings"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Save v-else class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Guardando...' : 'Guardar configuración general' }}
                    </Button>
                </div>
            </header>

            <div class="grid gap-4 2xl:grid-cols-[1.2fr_.8fr]">
                <article :class="cardClass">
                    <div class="flex flex-col gap-5">
                        <div>
                            <h2 :class="titleClass">
                                <Building2 class="h-5 w-5" :style="{ color: form.primary_color }" />
                                Datos de clínica
                            </h2>

                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                <div class="space-y-2 lg:col-span-2">
                                    <Label>Nombre</Label>
                                    <Input
                                        v-model="form.clinic_name"
                                        :class="inputClass"
                                        placeholder="Ej. FisioVida"
                                    />
                                    <p v-if="form.errors.clinic_name" class="text-xs text-red-500">
                                        {{ form.errors.clinic_name }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label>Teléfono</Label>
                                    <Input
                                        v-model="form.clinic_phone"
                                        :class="inputClass"
                                        placeholder="Ej. +52 777 123 4567"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label>Email</Label>
                                    <Input
                                        v-model="form.clinic_email"
                                        :class="inputClass"
                                        type="email"
                                        placeholder="Ej. contacto@clinica.com"
                                    />
                                    <p v-if="form.errors.clinic_email" class="text-xs text-red-500">
                                        {{ form.errors.clinic_email }}
                                    </p>
                                </div>

                                <div class="space-y-2 lg:col-span-2">
                                    <Label>Dirección</Label>
                                    <Input
                                        v-model="form.clinic_address"
                                        :class="inputClass"
                                        placeholder="Calle, número, colonia, ciudad"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-border bg-muted/30 p-4">
                            <h2 :class="titleClass">
                                <SlidersHorizontal class="h-5 w-5" :style="{ color: form.primary_color }" />
                                Parámetros generales
                            </h2>

                            <div class="mt-4 grid gap-4 md:grid-cols-[1fr_1fr_auto]">
                                <div class="space-y-2">
                                    <Label>Moneda</Label>
                                    <Input
                                        v-model="form.default_currency"
                                        :class="inputClass"
                                        placeholder="MXN"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label>Duración cita (min)</Label>
                                    <Input
                                        v-model="form.appointment_default_duration"
                                        :class="inputClass"
                                        type="number"
                                        min="1"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-border bg-muted/30 p-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex flex-col gap-4 md:flex-row md:items-center">
                                    <button
                                        type="button"
                                        class="group relative flex h-36 w-full items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-border bg-card p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary hover:bg-muted/50 md:w-64"
                                        @click="triggerLogoInput"
                                        @dragover.prevent
                                        @dragenter.prevent
                                        @drop.prevent="onLogoDropped"
                                    >
                                        <img
                                            v-if="logoPreview"
                                            :src="logoPreview"
                                            alt="Logo clínica"
                                            class="max-h-full max-w-full object-contain transition-transform duration-300 group-hover:scale-105"
                                        />

                                        <div
                                            v-else
                                            class="flex flex-col items-center gap-2 text-muted-foreground"
                                        >
                                            <ImageIcon class="h-9 w-9 opacity-60" />
                                            <span class="text-xs font-medium">Arrastra tu logo aquí</span>
                                        </div>

                                        <div
                                            class="pointer-events-none absolute inset-x-3 bottom-3 rounded-xl bg-card/90 px-3 py-2 text-center text-xs font-medium text-foreground opacity-0 shadow-sm transition-opacity duration-200 group-hover:opacity-100"
                                        >
                                            Clic o arrastra una imagen
                                        </div>
                                    </button>

                                    <div>
                                        <Label class="text-sm font-semibold text-foreground">
                                            Logo de la clínica
                                        </Label>
                                        <p class="mt-1 max-w-md text-xs leading-5 text-muted-foreground">
                                            Sube o arrastra un logo en PNG, JPG, WEBP o SVG. Recomendado con fondo transparente.
                                        </p>

                                        <p
                                            v-if="form.clinic_logo_file"
                                            class="mt-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700"
                                        >
                                            Nueva imagen seleccionada: {{ form.clinic_logo_file.name }}
                                        </p>

                                        <p
                                            v-if="form.errors.clinic_logo_file"
                                            class="mt-2 text-xs text-red-500"
                                        >
                                            {{ form.errors.clinic_logo_file }}
                                        </p>
                                    </div>
                                </div>

                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                                    class="hidden"
                                    @change="onLogoSelected"
                                />

                                <div class="flex flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="h-10 rounded-2xl px-5 text-sm"
                                        @click="triggerLogoInput"
                                    >
                                        <UploadCloud class="mr-2 h-4 w-4" />
                                        Subir logo
                                    </Button>

                                    <Button
                                        v-if="form.clinic_logo_file"
                                        type="button"
                                        variant="ghost"
                                        class="h-10 rounded-2xl px-5 text-sm"
                                        @click="restorePreviousLogo"
                                    >
                                        <RefreshCw class="mr-2 h-4 w-4" />
                                        Restaurar anterior
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article :class="cardClass">
                    <h2 :class="titleClass">
                        <Palette class="h-5 w-5" :style="{ color: form.primary_color }" />
                        Colores del sistema
                    </h2>

                    <p class="mt-1 text-sm text-muted-foreground">
                        Estos colores personalizan el modo claro. En modo oscuro se usa una paleta premium automática para mantener contraste.
                    </p>

                    <div class="mt-4 grid gap-3">
                        <div class="space-y-1.5">
                            <Label>Botones principales</Label>
                            <div class="flex items-center gap-3">
                                <Input v-model="form.primary_color" type="color" :class="colorInputClass" />
                                <Input v-model="form.primary_color" :class="colorTextClass" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Hover de botones</Label>
                            <div class="flex items-center gap-3">
                                <Input v-model="form.primary_hover_color" type="color" :class="colorInputClass" />
                                <Input v-model="form.primary_hover_color" :class="colorTextClass" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Texto de botones</Label>
                            <div class="flex items-center gap-3">
                                <Input v-model="form.primary_foreground_color" type="color" :class="colorInputClass" />
                                <Input v-model="form.primary_foreground_color" :class="colorTextClass" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Fondo general</Label>
                            <div class="flex items-center gap-3">
                                <Input v-model="form.app_background_color" type="color" :class="colorInputClass" />
                                <Input v-model="form.app_background_color" :class="colorTextClass" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Fondo de tarjetas</Label>
                            <div class="flex items-center gap-3">
                                <Input v-model="form.card_background_color" type="color" :class="colorInputClass" />
                                <Input v-model="form.card_background_color" :class="colorTextClass" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Fondo menú lateral</Label>
                            <div class="flex items-center gap-3">
                                <Input v-model="form.sidebar_background_color" type="color" :class="colorInputClass" />
                                <Input v-model="form.sidebar_background_color" :class="colorTextClass" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-[1.5rem] border border-border bg-muted/30 p-3">
                        <p class="text-sm font-semibold text-foreground">
                            Vista previa
                        </p>

                        <div
                            class="mt-3 rounded-2xl border border-border p-3"
                            :style="{ backgroundColor: form.app_background_color }"
                        >
                            <div class="grid gap-3 sm:grid-cols-[135px_1fr]">
                                <div
                                    class="rounded-2xl p-3 text-sm font-semibold"
                                    :style="{ backgroundColor: form.sidebar_background_color, color: '#374151' }"
                                >
                                    Menú lateral
                                </div>

                                <div
                                    class="rounded-2xl border border-border/40 p-3"
                                    :style="{ backgroundColor: form.card_background_color }"
                                >
                                    <button
                                        type="button"
                                        class="rounded-xl px-4 py-2 text-sm font-semibold shadow-sm transition-all duration-200 hover:-translate-y-0.5"
                                        :style="{
                                            backgroundColor: form.primary_color,
                                            color: form.primary_foreground_color,
                                        }"
                                        @mouseenter="setHoverColor($event, form.primary_hover_color)"
                                        @mouseleave="setHoverColor($event, form.primary_color)"
                                    >
                                        Botón principal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- ── Cumplimiento y Privacidad ─────────────────────────────────────── -->
            <article :class="cardClass">
                <div class="mb-4 flex items-start gap-3 border-b border-border pb-4">
                    <div
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl shadow"
                        :style="{ backgroundColor: form.primary_color, color: form.primary_foreground_color }"
                    >
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Cumplimiento y privacidad</h2>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            Herramientas de apoyo documental. Los textos son plantillas editables —
                            <span class="font-medium text-amber-600 dark:text-amber-400">la clínica debe validar su contenido con asesoría jurídica.</span>
                        </p>
                    </div>
                </div>

                <div class="grid gap-6">
                    <!-- Identidad legal -->
                    <div>
                        <p class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            <FileText class="h-3.5 w-3.5" /> Identidad legal de la clínica
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-foreground">Sector de salud</label>
                                <select
                                    v-model="form.clinic_sector"
                                    :class="inputClass"
                                    class="cursor-pointer"
                                >
                                    <option value="physiotherapy">Fisioterapia / Rehabilitación</option>
                                    <option value="dentistry">Odontología</option>
                                    <option value="general_medicine">Medicina general</option>
                                    <option value="psychology">Salud mental / Psicología</option>
                                    <option value="pediatrics">Pediatría</option>
                                    <option value="childcare">Cuidado de niños</option>
                                    <option value="other">Otro sector de salud</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-foreground">Razón social / nombre legal</label>
                                <Input v-model="form.legal_business_name" :class="inputClass" placeholder="Nombre legal de la clínica" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-foreground">Representante legal</label>
                                <Input v-model="form.legal_representative" :class="inputClass" placeholder="Nombre del representante" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-foreground">Responsable de datos personales</label>
                                <Input v-model="form.privacy_responsible_name" :class="inputClass" placeholder="Nombre del responsable ARCO" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-foreground">Correo para derechos ARCO</label>
                                <Input v-model="form.privacy_contact_email" :class="inputClass" type="email" placeholder="privacidad@clinica.com" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-foreground">Teléfono para privacidad</label>
                                <Input v-model="form.privacy_contact_phone" :class="inputClass" placeholder="+52 55 0000 0000" />
                            </div>
                            <div class="space-y-1.5 sm:col-span-2 lg:col-span-3">
                                <label class="text-xs font-medium text-foreground">Domicilio para derechos ARCO</label>
                                <Input v-model="form.privacy_address" :class="inputClass" placeholder="Calle, número, colonia, ciudad, C.P." />
                            </div>
                        </div>
                    </div>

                    <!-- Versión y vigencia -->
                    <div class="rounded-2xl border border-border bg-muted/30 p-4">
                        <p class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            <FileText class="h-3.5 w-3.5" /> Versión y vigencia del aviso de privacidad
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-foreground">Versión actual</label>
                                <Input v-model="form.privacy_notice_version" :class="inputClass" placeholder="Ej. 1.0, 2.1" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-foreground">Fecha de vigencia</label>
                                <Input v-model="form.privacy_notice_effective_date" :class="inputClass" type="date" />
                            </div>
                        </div>
                    </div>

                    <!-- Controles de flujo -->
                    <div class="rounded-2xl border border-border bg-muted/30 p-4">
                        <p class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            <ShieldCheck class="h-3.5 w-3.5" /> Controles de cumplimiento
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-center justify-between rounded-2xl border border-border bg-card px-4 py-3 transition hover:bg-muted/50">
                                <div>
                                    <p class="text-sm font-medium text-foreground">Aceptación desde portal del paciente</p>
                                    <p class="text-xs text-muted-foreground">El paciente puede aceptar sus documentos desde su portal</p>
                                </div>
                                <Checkbox v-model:checked="form.allow_patient_portal_acceptance" />
                            </label>
                            <label class="flex cursor-pointer items-center justify-between rounded-2xl border border-border bg-card px-4 py-3 transition hover:bg-muted/50">
                                <div>
                                    <p class="text-sm font-medium text-foreground">Exigir aviso de privacidad</p>
                                    <p class="text-xs text-muted-foreground">Mostrar alerta antes de registrar sesión sin aviso aceptado</p>
                                </div>
                                <Checkbox v-model:checked="form.require_privacy_notice_before_session" />
                            </label>
                            <label class="flex cursor-pointer items-center justify-between rounded-2xl border border-border bg-card px-4 py-3 transition hover:bg-muted/50">
                                <div>
                                    <p class="text-sm font-medium text-foreground">Exigir consentimiento de tratamiento</p>
                                    <p class="text-xs text-muted-foreground">Mostrar alerta antes de registrar sesión sin consentimiento</p>
                                </div>
                                <Checkbox v-model:checked="form.require_treatment_consent_before_session" />
                            </label>
                            <label class="flex cursor-pointer items-center justify-between rounded-2xl border border-border bg-card px-4 py-3 transition hover:bg-muted/50">
                                <div>
                                    <p class="text-sm font-medium text-foreground">Exigir consentimiento de imágenes</p>
                                    <p class="text-xs text-muted-foreground">Alertar al subir evidencia fotográfica sin consentimiento</p>
                                </div>
                                <Checkbox v-model:checked="form.require_image_consent_for_uploads" />
                            </label>
                        </div>
                    </div>

                    <!-- Textos de documentos -->
                    <div>
                        <p class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            <AlertTriangle class="h-3.5 w-3.5 text-amber-500" />
                            Plantillas de documentos legales
                            <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Editar con asesoría jurídica</span>
                        </p>

                        <div class="grid gap-4">
                            <div v-for="doc in [
                                { key: 'privacy_notice_text', label: 'Aviso de privacidad (LFPDPPP)' },
                                { key: 'sensitive_data_consent_text', label: 'Consentimiento de datos sensibles de salud' },
                                { key: 'treatment_consent_text', label: 'Consentimiento de tratamiento' },
                                { key: 'image_consent_text', label: 'Consentimiento de imágenes / evidencia clínica' },
                                { key: 'minor_consent_text', label: 'Consentimiento para menor de edad' },
                                { key: 'data_retention_policy_text', label: 'Política de conservación del expediente' },
                            ]" :key="doc.key" class="space-y-1.5">
                                <label class="text-xs font-medium text-foreground">{{ doc.label }}</label>
                                <textarea
                                    v-model="(form as any)[doc.key]"
                                    rows="6"
                                    class="w-full rounded-2xl border border-border bg-card px-4 py-3 text-sm font-mono leading-relaxed text-foreground shadow-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :placeholder="`Texto editable del ${doc.label.toLowerCase()}...`"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article :class="cardClass">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 :class="titleClass">
                            <Blocks class="h-5 w-5" :style="{ color: form.primary_color }" />
                            Módulos habilitados
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Activa o desactiva secciones del sistema de forma global.
                        </p>
                    </div>

                    <div
                        class="rounded-2xl px-4 py-2 text-sm font-semibold"
                        :style="{
                            backgroundColor: `${form.primary_color}18`,
                            color: form.primary_color,
                        }"
                    >
                        {{ activeModules }} activos
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                    <label
                        v-for="module in props.modules"
                        :key="module.id"
                        class="group flex cursor-pointer items-center justify-between rounded-2xl border border-border bg-muted/40 px-4 py-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/40 hover:bg-card hover:shadow-sm"
                    >
                        <div class="pr-3">
                            <p class="font-semibold text-foreground">
                                {{ module.label || tModule(module.module) }}
                            </p>
                            <p
                                v-if="module.description"
                                class="mt-0.5 line-clamp-1 text-xs text-muted-foreground"
                            >
                                {{ module.description }}
                            </p>
                        </div>

                        <Checkbox
                            :model-value="
                                modulesForm.modules.find((m) => m.module === module.module)?.enabled
                            "
                            @update:model-value="(value) => toggleModule(module.module, !!value)"
                        />
                    </label>
                </div>

                <div class="mt-5 flex justify-end">
                    <Button
                        class="rounded-2xl px-6 shadow-lg transition-all duration-200 hover:-translate-y-0.5"
                        :style="{
                            backgroundColor: form.primary_color,
                            color: form.primary_foreground_color,
                        }"
                        :disabled="modulesForm.processing"
                        @mouseenter="setHoverColor($event, form.primary_hover_color)"
                        @mouseleave="setHoverColor($event, form.primary_color)"
                        @click="saveModules"
                    >
                        <Loader2
                            v-if="modulesForm.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <CheckCircle2 v-else class="mr-2 h-4 w-4" />
                        {{ modulesForm.processing ? 'Guardando módulos...' : 'Guardar módulos' }}
                    </Button>
                </div>
            </article>
        </section>
    </AppLayout>
</template>
