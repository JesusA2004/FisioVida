<script setup lang="ts">
import { computed } from 'vue'

type StatusType = 'appointment' | 'payment' | 'activity' | 'user' | 'patient' | 'general'

interface Props {
    status: string
    type?: StatusType
    size?: 'sm' | 'md'
}

const props = withDefaults(defineProps<Props>(), {
    type: 'general',
    size: 'md',
})

// Mapa de status → clases Tailwind (bg + text compatibles con dark mode)
const statusClasses: Record<string, string> = {
    // Citas
    scheduled:  'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
    confirmed:  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    arrived:    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    done:       'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
    no_show:    'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    cancelled:  'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
    // Pagos
    pending:    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    paid:       'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    failed:     'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    refunded:   'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
    partial:    'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
    // Actividades
    in_progress:'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
    completed:  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    on_hold:    'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
    overdue:    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    // Usuarios / pacientes
    active:     'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    inactive:   'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
    blocked:    'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    suspended:  'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    // Módulos
    enabled:    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    disabled:   'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
}

const labelMap: Record<string, string> = {
    // Citas
    scheduled:  'Programada',
    confirmed:  'Confirmada',
    arrived:    'Llegó',
    done:       'Finalizada',
    no_show:    'No asistió',
    cancelled:  'Cancelada',
    // Pagos
    pending:    'Pendiente',
    paid:       'Pagado',
    failed:     'Fallido',
    refunded:   'Reembolsado',
    partial:    'Parcial',
    // Actividades
    in_progress:'En proceso',
    completed:  'Completada',
    on_hold:    'En pausa',
    overdue:    'Vencida',
    // General
    active:     'Activo',
    inactive:   'Inactivo',
    blocked:    'Bloqueado',
    suspended:  'Suspendido',
    enabled:    'Habilitado',
    disabled:   'Deshabilitado',
}

const cls = computed(() => statusClasses[props.status] ?? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400')
const label = computed(() => labelMap[props.status] ?? props.status)
</script>

<template>
    <span
        class="inline-flex items-center rounded-full font-medium leading-none"
        :class="[cls, size === 'sm' ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-1 text-xs']"
    >
        {{ label }}
    </span>
</template>
