<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    CalendarClock, ClipboardPlus, Clock,
    Heart, UserRound, ChevronDown, ChevronUp, AlertCircle,
    CheckCircle2, Dumbbell, FileText, Download,
    Activity, X, Loader2, ExternalLink, ListTodo, Save,
} from 'lucide-vue-next';
import { formatDateTimeMx, formatDateMx } from '@/lib/dates';
import { tAppointmentStatus } from '@/lib/labels';

type LastSession = {
    session_date: string;
    pain_scale: number | null;
    assessment: string | null;
    plan: string | null;
    subjective: string | null;
    notes: string | null;
};

type AppointmentItem = {
    id: number;
    patient_persona_id: number;
    patient_name: string;
    therapist_name: string;
    start_at: string;
    end_at: string;
    status: string;
    notes: string | null;
    last_session: LastSession | null;
};

// Datos del endpoint /atencion
type AtencionData = {
    appointment: any;
    sessions: any[];
    exercises: any[];
    files: any[];
    upcoming: any[];
    activities: any[];
    exerciseCatalog: any[];
};

const props = defineProps<{
    appointments: AppointmentItem[];
    today: string;
    todayLabel: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mi Jornada', href: '/mi-jornada' },
];

const expandedId   = ref<number | null>(null);
const atencionId   = ref<number | null>(null);
const atencionData = ref<AtencionData | null>(null);
const atencionLoading = ref(false);

// Form sesión inline
const showSesionForm = ref(false);
const sesionSaving   = ref(false);
const sesionForm = ref({
    subjective:   '',
    objective:    '',
    assessment:   '',
    plan:         '',
    pain_scale:   null as number | null,
    notes:        '',
    exercise_ids: [] as number[],
    marcar_done:  true,
});

const resetSesionForm = () => {
    sesionForm.value = { subjective: '', objective: '', assessment: '', plan: '', pain_scale: null, notes: '', exercise_ids: [], marcar_done: true };
    showSesionForm.value = false;
};

const getCsrfToken = (): string => {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
};

const saveSesion = async (appt: AppointmentItem) => {
    sesionSaving.value = true;
    try {
        const res = await fetch(`/mi-jornada/citas/${appt.id}/sesion`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(sesionForm.value),
        });
        if (res.ok) {
            resetSesionForm();
            // Reload atencion data to reflect new session
            atencionData.value = null;
            atencionLoading.value = true;
            const r2 = await fetch(`/mi-jornada/citas/${appt.id}/atencion`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (r2.ok) atencionData.value = await r2.json();
            // If marked done, refresh page so status updates
            if (sesionForm.value.marcar_done) {
                setTimeout(() => router.reload(), 800);
            }
        }
    } catch {
        // silent
    } finally {
        sesionSaving.value = false;
        atencionLoading.value = false;
    }
};

const toggle = (id: number) => {
    expandedId.value = expandedId.value === id ? null : id;
    // Si se colapsa, cerrar también el panel de atención
    if (expandedId.value === null && atencionId.value === id) {
        atencionId.value  = null;
        atencionData.value = null;
    }
};

const loadAtencion = async (appt: AppointmentItem) => {
    if (atencionId.value === appt.id) {
        // Cerrar si ya está abierto
        atencionId.value  = null;
        atencionData.value = null;
        return;
    }
    atencionLoading.value = true;
    atencionId.value = appt.id;
    atencionData.value = null;
    // Abrir también el panel expandido
    expandedId.value = appt.id;
    try {
        const res = await fetch(`/mi-jornada/citas/${appt.id}/atencion`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (res.ok) {
            atencionData.value = await res.json();
        }
    } catch {
        atencionData.value = null;
    } finally {
        atencionLoading.value = false;
    }
};

const statusColors: Record<string, string> = {
    scheduled: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
    confirmed:  'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    arrived:    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    done:       'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
    no_show:    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    cancelled:  'bg-muted text-muted-foreground',
};

const primaryButtonStyle = {
    backgroundColor: 'var(--primary)',
    color: 'var(--primary-foreground)',
};

const atenderCita = (appt: AppointmentItem) => {
    router.visit(`/sesiones?new=1&patient_persona_id=${appt.patient_persona_id}&appointment_id=${appt.id}`);
};

const totalCitas     = computed(() => props.appointments.length);
const citasAtendidas = computed(() => props.appointments.filter(a => a.status === 'done').length);
const citasPendientes = computed(() => props.appointments.filter(a => ['scheduled', 'confirmed', 'arrived'].includes(a.status)).length);

const painColor = (scale: number | null) => {
    if (!scale) return 'text-muted-foreground';
    if (scale <= 3) return 'text-emerald-600 dark:text-emerald-400';
    if (scale <= 6) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
};

const edad = (fechaNacimiento: string | null) => {
    if (!fechaNacimiento) return null;
    const birth = new Date(fechaNacimiento);
    const now = new Date();
    let age = now.getFullYear() - birth.getFullYear();
    const m = now.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) age--;
    return age;
};
</script>

<template>
    <Head title="Mi Jornada" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="space-y-5 pb-8">

            <!-- Header -->
            <div class="fv-gradient-panel">
                <div
                    class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full blur-3xl opacity-20"
                    :style="{ backgroundColor: 'var(--primary)' }"
                />
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl shadow-lg" :style="primaryButtonStyle">
                            <CalendarClock class="h-6 w-6" />
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold text-foreground">Mi Jornada</h1>
                            <p class="text-sm text-muted-foreground capitalize">{{ todayLabel }}</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="fv-kpi-card !p-3 min-w-[90px] text-center">
                            <p class="text-xs text-muted-foreground">Total</p>
                            <p class="text-2xl font-bold text-foreground">{{ totalCitas }}</p>
                        </div>
                        <div class="fv-kpi-card !p-3 min-w-[90px] text-center">
                            <p class="text-xs text-muted-foreground">Atendidas</p>
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ citasAtendidas }}</p>
                        </div>
                        <div class="fv-kpi-card !p-3 min-w-[90px] text-center">
                            <p class="text-xs text-muted-foreground">Pendientes</p>
                            <p class="text-2xl font-bold" :style="{ color: 'var(--primary)' }">{{ citasPendientes }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de citas -->
            <div v-if="appointments.length === 0" class="fv-empty-state">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-card shadow-sm">
                    <CalendarClock class="h-7 w-7 text-muted-foreground opacity-60" />
                </div>
                <h3 class="mt-4 text-base font-semibold text-foreground">Sin citas para hoy</h3>
                <p class="mt-1 text-sm text-muted-foreground">Disfruta tu día libre o revisa la agenda general.</p>
                <Button class="mt-4 rounded-xl" variant="outline" @click="router.visit('/citas')">
                    Ver agenda completa
                </Button>
            </div>

            <div v-else class="space-y-3">
                <article
                    v-for="appt in appointments"
                    :key="appt.id"
                    class="fv-card-premium overflow-hidden"
                >
                    <!-- Card header -->
                    <div
                        class="flex cursor-pointer items-start justify-between gap-4 p-4"
                        @click="toggle(appt.id)"
                    >
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="flex flex-col items-center rounded-xl bg-muted px-3 py-2 text-center shrink-0">
                                <span class="text-xs font-medium text-muted-foreground leading-none">{{ formatDateTimeMx(appt.start_at).split(' ')[1]?.slice(0,5) }}</span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-foreground">{{ appt.patient_name }}</h3>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusColors[appt.status] ?? 'bg-muted text-muted-foreground'">
                                        {{ tAppointmentStatus(appt.status) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground flex items-center gap-1">
                                    <Clock class="h-3 w-3 shrink-0" />
                                    {{ formatDateTimeMx(appt.start_at) }} → {{ formatDateTimeMx(appt.end_at) }}
                                </p>
                                <p v-if="appt.notes" class="mt-1 text-xs text-muted-foreground truncate">{{ appt.notes }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <Button
                                v-if="appt.status === 'arrived'"
                                size="sm"
                                class="h-8 rounded-xl text-xs font-medium"
                                :style="primaryButtonStyle"
                                @click.stop="loadAtencion(appt)"
                            >
                                <ClipboardPlus class="mr-1.5 h-3.5 w-3.5" />
                                Atender
                            </Button>
                            <component :is="expandedId === appt.id ? ChevronUp : ChevronDown" class="h-4 w-4 text-muted-foreground" />
                        </div>
                    </div>

                    <!-- Panel expandible -->
                    <div v-if="expandedId === appt.id" class="border-t border-border">

                        <!-- Panel de atención enriquecido (cargado por Ajax) -->
                        <div v-if="atencionId === appt.id" class="bg-muted/20 p-4">

                            <!-- Loading -->
                            <div v-if="atencionLoading" class="flex items-center justify-center py-6 gap-2 text-muted-foreground">
                                <Loader2 class="h-4 w-4 animate-spin" />
                                <span class="text-sm">Cargando expediente...</span>
                            </div>

                            <!-- Datos cargados -->
                            <div v-else-if="atencionData" class="space-y-5">

                                <!-- Header paciente -->
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-sm text-sm font-bold" :style="primaryButtonStyle">
                                            {{ appt.patient_name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">{{ appt.patient_name }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                <span v-if="atencionData.appointment?.fecha_nacimiento">{{ edad(atencionData.appointment.fecha_nacimiento) }} años</span>
                                                <span v-if="atencionData.appointment?.sexo"> · {{ atencionData.appointment.sexo }}</span>
                                                <span v-if="atencionData.appointment?.telefono"> · {{ atencionData.appointment.telefono }}</span>
                                                <span v-if="atencionData.appointment?.email"> · {{ atencionData.appointment.email }}</span>
                                            </p>
                                            <p v-if="atencionData.appointment?.patient_notes" class="mt-1 text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                                <AlertCircle class="h-3 w-3 shrink-0" />
                                                {{ atencionData.appointment.patient_notes }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="h-7 w-7 rounded-lg p-0"
                                        @click="atencionId = null; atencionData = null"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>

                                <!-- Grid de datos clínicos -->
                                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                                    <!-- Últimas sesiones -->
                                    <div class="rounded-2xl border border-border bg-card p-3 space-y-2">
                                        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                                            <Activity class="h-3.5 w-3.5" />Sesiones recientes
                                        </p>
                                        <div v-if="atencionData.sessions?.length" class="space-y-2">
                                            <div
                                                v-for="s in atencionData.sessions.slice(0,3)"
                                                :key="s.id"
                                                class="rounded-xl bg-muted/40 p-2.5"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs text-foreground font-medium">{{ formatDateMx(s.session_date) }}</span>
                                                    <span v-if="s.pain_scale != null" class="text-xs font-semibold" :class="painColor(s.pain_scale)">{{ s.pain_scale }}/10</span>
                                                </div>
                                                <p v-if="s.assessment" class="text-xs text-muted-foreground mt-1 line-clamp-2">{{ s.assessment }}</p>
                                                <p v-if="s.plan" class="text-xs text-foreground mt-0.5 line-clamp-2 font-medium">Plan: {{ s.plan }}</p>
                                            </div>
                                        </div>
                                        <p v-else class="text-xs text-muted-foreground">Primera sesión del paciente.</p>
                                    </div>

                                    <!-- Ejercicios recientes -->
                                    <div class="rounded-2xl border border-border bg-card p-3 space-y-2">
                                        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                                            <Dumbbell class="h-3.5 w-3.5" />Ejercicios asignados
                                        </p>
                                        <div v-if="atencionData.exercises?.length" class="space-y-1.5">
                                            <div
                                                v-for="ex in atencionData.exercises.slice(0,4)"
                                                :key="ex.name"
                                                class="flex items-center gap-2 rounded-xl bg-muted/40 px-2.5 py-1.5"
                                            >
                                                <Dumbbell class="h-3 w-3 shrink-0 text-muted-foreground" />
                                                <div class="min-w-0">
                                                    <p class="text-xs font-medium text-foreground truncate">{{ ex.name }}</p>
                                                    <p v-if="ex.sets || ex.reps" class="text-[10px] text-muted-foreground">
                                                        {{ [ex.sets && `${ex.sets}s`, ex.reps && `${ex.reps}r`].filter(Boolean).join(' · ') }}
                                                    </p>
                                                </div>
                                                <a v-if="ex.video_url" :href="ex.video_url" target="_blank" class="ml-auto shrink-0" :style="{ color: 'var(--primary)' }">
                                                    <ExternalLink class="h-3 w-3" />
                                                </a>
                                            </div>
                                        </div>
                                        <p v-else class="text-xs text-muted-foreground">Sin ejercicios asignados.</p>
                                    </div>

                                    <!-- Archivos y actividades -->
                                    <div class="rounded-2xl border border-border bg-card p-3 space-y-3">
                                        <div class="space-y-2">
                                            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                                                <FileText class="h-3.5 w-3.5" />Archivos recientes
                                            </p>
                                            <div v-if="atencionData.files?.length" class="space-y-1.5">
                                                <a
                                                    v-for="f in atencionData.files.slice(0,3)"
                                                    :key="f.id"
                                                    :href="f.download_url"
                                                    target="_blank"
                                                    class="flex items-center gap-2 rounded-xl bg-muted/40 px-2.5 py-1.5 hover:bg-muted transition-colors"
                                                >
                                                    <FileText class="h-3 w-3 shrink-0 text-muted-foreground" />
                                                    <span class="text-xs text-foreground truncate min-w-0">{{ f.original_name }}</span>
                                                    <Download class="h-3 w-3 shrink-0 ml-auto text-muted-foreground" />
                                                </a>
                                            </div>
                                            <p v-else class="text-xs text-muted-foreground">Sin archivos.</p>
                                        </div>

                                        <div v-if="atencionData.activities?.length" class="space-y-2">
                                            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                                                <ListTodo class="h-3.5 w-3.5" />Pendientes
                                            </p>
                                            <div class="space-y-1.5">
                                                <div
                                                    v-for="act in atencionData.activities"
                                                    :key="act.id"
                                                    class="flex items-center gap-2 rounded-xl bg-amber-50 dark:bg-amber-900/20 px-2.5 py-1.5"
                                                >
                                                    <AlertCircle class="h-3 w-3 shrink-0 text-amber-500" />
                                                    <span class="text-xs text-foreground truncate">{{ act.title }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Formulario inline de sesión -->
                                <div v-if="showSesionForm" class="rounded-2xl border border-border bg-card p-4 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold text-foreground">Registrar sesión</p>
                                        <Button size="sm" variant="ghost" class="h-6 w-6 p-0 rounded-lg" @click="resetSesionForm()">
                                            <X class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="space-y-1">
                                            <label class="text-xs font-medium text-muted-foreground">Subjetivo</label>
                                            <textarea v-model="sesionForm.subjective" rows="2" placeholder="Lo que refiere el paciente..." class="w-full rounded-xl border border-border bg-background px-3 py-1.5 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary resize-none" />
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-xs font-medium text-muted-foreground">Objetivo</label>
                                            <textarea v-model="sesionForm.objective" rows="2" placeholder="Hallazgos clínicos observados..." class="w-full rounded-xl border border-border bg-background px-3 py-1.5 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary resize-none" />
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-xs font-medium text-muted-foreground">Evaluación</label>
                                            <textarea v-model="sesionForm.assessment" rows="2" placeholder="Interpretación clínica..." class="w-full rounded-xl border border-border bg-background px-3 py-1.5 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary resize-none" />
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-xs font-medium text-muted-foreground">Plan</label>
                                            <textarea v-model="sesionForm.plan" rows="2" placeholder="Próximo plan de tratamiento..." class="w-full rounded-xl border border-border bg-background px-3 py-1.5 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary resize-none" />
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-4 items-end">
                                        <div class="space-y-1">
                                            <label class="text-xs font-medium text-muted-foreground">Escala de dolor (0-10)</label>
                                            <input v-model.number="sesionForm.pain_scale" type="number" min="0" max="10" class="w-24 rounded-xl border border-border bg-background px-3 py-1.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary" />
                                        </div>
                                        <div class="space-y-1 flex-1 min-w-[140px]">
                                            <label class="text-xs font-medium text-muted-foreground">Notas adicionales</label>
                                            <input v-model="sesionForm.notes" type="text" placeholder="Observaciones..." class="w-full rounded-xl border border-border bg-background px-3 py-1.5 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary" />
                                        </div>
                                    </div>
                                    <!-- Ejercicios del catálogo -->
                                    <div v-if="atencionData?.exerciseCatalog?.length" class="space-y-1">
                                        <label class="text-xs font-medium text-muted-foreground">Asignar ejercicios (opcional)</label>
                                        <div class="flex flex-wrap gap-1.5 max-h-28 overflow-y-auto pr-1">
                                            <button
                                                v-for="ex in atencionData.exerciseCatalog"
                                                :key="ex.id"
                                                type="button"
                                                class="rounded-full border px-2.5 py-0.5 text-[11px] transition-colors"
                                                :class="sesionForm.exercise_ids.includes(ex.id)
                                                    ? 'border-transparent text-white'
                                                    : 'border-border bg-background text-muted-foreground hover:border-primary hover:text-foreground'"
                                                :style="sesionForm.exercise_ids.includes(ex.id) ? { backgroundColor: 'var(--primary)' } : {}"
                                                @click="sesionForm.exercise_ids.includes(ex.id)
                                                    ? sesionForm.exercise_ids.splice(sesionForm.exercise_ids.indexOf(ex.id), 1)
                                                    : sesionForm.exercise_ids.push(ex.id)"
                                            >
                                                {{ ex.name }}
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="appt.status === 'arrived'" class="flex items-center gap-2">
                                        <input id="marcar-done" v-model="sesionForm.marcar_done" type="checkbox" class="h-3.5 w-3.5 rounded accent-primary" />
                                        <label for="marcar-done" class="text-xs text-muted-foreground cursor-pointer">Marcar cita como completada al guardar</label>
                                    </div>
                                    <div class="flex gap-2 pt-1">
                                        <Button size="sm" class="rounded-xl text-xs h-8" :style="primaryButtonStyle" :disabled="sesionSaving" @click="saveSesion(appt)">
                                            <Loader2 v-if="sesionSaving" class="mr-1.5 h-3.5 w-3.5 animate-spin" />
                                            <Save v-else class="mr-1.5 h-3.5 w-3.5" />
                                            {{ sesionSaving ? 'Guardando...' : 'Guardar sesión' }}
                                        </Button>
                                        <Button size="sm" variant="outline" class="rounded-xl text-xs h-8 border-border" @click="resetSesionForm()">Cancelar</Button>
                                    </div>
                                </div>

                                <!-- Acciones rápidas -->
                                <div class="flex flex-wrap gap-2 pt-2 border-t border-border">
                                    <Button
                                        v-if="!showSesionForm"
                                        size="sm"
                                        class="rounded-xl text-xs h-8"
                                        :style="primaryButtonStyle"
                                        @click="showSesionForm = true"
                                    >
                                        <ClipboardPlus class="mr-1.5 h-3.5 w-3.5" />
                                        Registrar sesión
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="rounded-xl text-xs h-8 border-border"
                                        @click="router.visit(`/pacientes/${appt.patient_persona_id}`)"
                                    >
                                        <UserRound class="mr-1.5 h-3.5 w-3.5" />
                                        Expediente completo
                                    </Button>
                                    <Button
                                        v-if="atencionData.upcoming?.length"
                                        size="sm"
                                        variant="outline"
                                        class="rounded-xl text-xs h-8 border-border"
                                        @click="router.visit('/citas')"
                                    >
                                        <CalendarClock class="mr-1.5 h-3.5 w-3.5" />
                                        {{ atencionData.upcoming.length }} cita(s) próxima(s)
                                    </Button>
                                </div>

                            </div>
                        </div>

                        <!-- Panel básico (sin atención cargada) -->
                        <div v-else class="bg-muted/30 p-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <!-- Última sesión -->
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Última sesión</p>
                                    <div v-if="appt.last_session" class="space-y-1.5">
                                        <div class="flex items-center gap-2">
                                            <CalendarClock class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                                            <span class="text-xs text-foreground">{{ formatDateMx(appt.last_session.session_date) }}</span>
                                        </div>
                                        <div v-if="appt.last_session.pain_scale != null" class="flex items-center gap-2">
                                            <Heart class="h-3.5 w-3.5 shrink-0" :class="painColor(appt.last_session.pain_scale)" />
                                            <span class="text-xs font-medium" :class="painColor(appt.last_session.pain_scale)">
                                                Dolor: {{ appt.last_session.pain_scale }}/10
                                            </span>
                                        </div>
                                        <div v-if="appt.last_session.assessment">
                                            <p class="text-xs font-medium text-muted-foreground">Evaluación anterior:</p>
                                            <p class="text-xs text-foreground mt-0.5 line-clamp-2">{{ appt.last_session.assessment }}</p>
                                        </div>
                                        <div v-if="appt.last_session.plan">
                                            <p class="text-xs font-medium text-muted-foreground">Plan anterior:</p>
                                            <p class="text-xs text-foreground mt-0.5 line-clamp-2">{{ appt.last_session.plan }}</p>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center gap-2 text-muted-foreground">
                                        <AlertCircle class="h-3.5 w-3.5" />
                                        <span class="text-xs">Primera sesión del paciente</span>
                                    </div>
                                </div>
                                <!-- Acciones -->
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Acciones</p>
                                    <div class="flex flex-col gap-2">
                                        <Button
                                            v-if="!['done','cancelled','no_show'].includes(appt.status)"
                                            size="sm"
                                            class="w-full justify-start rounded-xl text-xs"
                                            :style="primaryButtonStyle"
                                            @click="loadAtencion(appt)"
                                        >
                                            <ClipboardPlus class="mr-2 h-3.5 w-3.5" />
                                            Cargar expediente de atención
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="w-full justify-start rounded-xl text-xs border-border"
                                            @click="router.visit(`/pacientes/${appt.patient_persona_id}`)"
                                        >
                                            <UserRound class="mr-2 h-3.5 w-3.5" />
                                            Ver expediente completo
                                        </Button>
                                        <Button
                                            v-if="appt.status === 'done'"
                                            size="sm"
                                            variant="outline"
                                            class="w-full justify-start rounded-xl text-xs border-border text-emerald-600 dark:text-emerald-400"
                                            disabled
                                        >
                                            <CheckCircle2 class="mr-2 h-3.5 w-3.5" />
                                            Cita finalizada
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </article>
            </div>

        </section>
    </AppLayout>
</template>
