<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    CalendarClock, Dumbbell, FileText, Activity,
    HeartPulse, UserRound, Clock, ExternalLink,
    Download, AlertCircle, CheckCircle2, ChevronDown, ChevronUp,
    Building2, Phone, Mail,
} from 'lucide-vue-next';
import { formatDateMx, formatDateTimeMx } from '@/lib/dates';
import { tAppointmentStatus } from '@/lib/labels';

type Patient = {
    id: number;
    full_name: string;
    nombres: string;
    fecha_nacimiento?: string | null;
    sexo?: string | null;
    telefono?: string | null;
    email?: string | null;
    direccion?: string | null;
    notas?: string | null;
};

type Appointment = {
    id: number;
    status: string;
    start_at: string;
    end_at?: string | null;
    therapist_name?: string | null;
};

type Session = {
    id: number;
    session_date: string;
    pain_scale?: number | null;
    assessment?: string | null;
    plan?: string | null;
    subjective?: string | null;
    notes?: string | null;
    therapist_name?: string | null;
};

type Exercise = {
    session_id: number;
    exercise_id: number;
    name: string;
    description?: string | null;
    video_url?: string | null;
    sets?: number | null;
    reps?: number | null;
    seconds?: number | null;
    exercise_notes?: string | null;
};

type PatientFile = {
    id: number;
    original_name: string;
    file_type?: string | null;
    created_at: string;
    uploaded_by_name?: string | null;
    preview_url: string;
    download_url: string;
};

type Activity = {
    id: number;
    title: string;
    status: string;
    priority?: string | null;
    due_date?: string | null;
};

const props = defineProps<{
    unlinked: boolean;
    patient: Patient | null;
    upcomingAppointments?: Appointment[];
    recentAppointments?: Appointment[];
    sessions?: Session[];
    exercises?: Exercise[];
    files?: PatientFile[];
    activities?: Activity[];
    consents?: any[];
    clinicSettings?: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mi Portal', href: '/mi-portal' },
];

const activeTab = ref<'resumen' | 'sesiones' | 'ejercicios' | 'archivos' | 'citas'>('resumen');

const lastSession = computed(() => props.sessions?.[0] ?? null);
const nextAppointment = computed(() => props.upcomingAppointments?.[0] ?? null);

const uniqueExercises = computed(() => {
    if (!props.exercises?.length) return [];
    const seen = new Set<number>();
    return props.exercises.filter(e => {
        if (seen.has(e.exercise_id)) return false;
        seen.add(e.exercise_id);
        return true;
    });
});

const painHistory = computed(() =>
    (props.sessions ?? [])
        .filter(s => s.pain_scale != null)
        .slice(0, 8)
        .reverse()
);

const painColor = (scale: number | null) => {
    if (!scale) return 'text-muted-foreground';
    if (scale <= 3) return 'text-emerald-600 dark:text-emerald-400';
    if (scale <= 6) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
};

const painBg = (scale: number | null) => {
    if (!scale) return 'bg-muted';
    if (scale <= 3) return 'bg-emerald-500';
    if (scale <= 6) return 'bg-amber-500';
    return 'bg-rose-500';
};

const primaryStyle = {
    backgroundColor: 'var(--primary)',
    color: 'var(--primary-foreground)',
};

const expandedSession = ref<number | null>(null);
const toggleSession = (id: number) => {
    expandedSession.value = expandedSession.value === id ? null : id;
};

const edad = computed(() => {
    if (!props.patient?.fecha_nacimiento) return null;
    const birth = new Date(props.patient.fecha_nacimiento);
    const now = new Date();
    let age = now.getFullYear() - birth.getFullYear();
    const m = now.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) age--;
    return age;
});

const statusColors: Record<string, string> = {
    scheduled: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
    confirmed:  'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    arrived:    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    done:       'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
    no_show:    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    cancelled:  'bg-muted text-muted-foreground',
};
</script>

<template>
    <Head title="Mi Portal" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="space-y-5 pb-10">

            <!-- Sin vínculo -->
            <div v-if="unlinked" class="fv-empty-state min-h-[60vh]">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-card shadow-sm">
                    <UserRound class="h-8 w-8 text-muted-foreground opacity-60" />
                </div>
                <h3 class="mt-5 text-lg font-semibold text-foreground">Cuenta no vinculada</h3>
                <p class="mt-2 max-w-sm text-center text-sm text-muted-foreground">
                    Tu cuenta aún no está vinculada a un expediente. Contacta a tu clínica para que lo configuren.
                </p>
                <div v-if="clinicSettings?.clinic_phone || clinicSettings?.clinic_email" class="mt-6 flex flex-col gap-2 items-center text-sm text-muted-foreground">
                    <span v-if="clinicSettings?.clinic_phone" class="flex items-center gap-2">
                        <Phone class="h-4 w-4" />{{ clinicSettings.clinic_phone }}
                    </span>
                    <span v-if="clinicSettings?.clinic_email" class="flex items-center gap-2">
                        <Mail class="h-4 w-4" />{{ clinicSettings.clinic_email }}
                    </span>
                </div>
            </div>

            <!-- Portal con datos -->
            <template v-else-if="patient">

                <!-- Header de bienvenida -->
                <div class="fv-gradient-panel">
                    <div
                        class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full blur-3xl opacity-15"
                        :style="{ backgroundColor: 'var(--primary)' }"
                    />
                    <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl shadow-lg text-xl font-bold"
                                :style="primaryStyle"
                            >
                                {{ patient.nombres?.charAt(0)?.toUpperCase() ?? '?' }}
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Bienvenido/a</p>
                                <h1 class="text-xl font-semibold text-foreground">{{ patient.full_name }}</h1>
                                <p class="text-xs text-muted-foreground">
                                    <span v-if="edad">{{ edad }} años</span>
                                    <span v-if="edad && patient.sexo"> · </span>
                                    <span v-if="patient.sexo">{{ patient.sexo === 'M' ? 'Masculino' : patient.sexo === 'F' ? 'Femenino' : patient.sexo }}</span>
                                </p>
                            </div>
                        </div>
                        <!-- Info de clínica -->
                        <div v-if="clinicSettings?.clinic_name" class="flex flex-col gap-1 text-right text-sm text-muted-foreground">
                            <span class="flex items-center gap-1 justify-end">
                                <Building2 class="h-3.5 w-3.5" />
                                <span class="font-medium text-foreground">{{ clinicSettings.clinic_name }}</span>
                            </span>
                            <span v-if="clinicSettings.clinic_phone" class="flex items-center gap-1 justify-end">
                                <Phone class="h-3.5 w-3.5" />{{ clinicSettings.clinic_phone }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- KPIs rápidos -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="fv-kpi-card text-center !p-4">
                        <p class="text-xs text-muted-foreground">Sesiones</p>
                        <p class="mt-1 text-2xl font-bold text-foreground">{{ sessions?.length ?? 0 }}</p>
                    </div>
                    <div class="fv-kpi-card text-center !p-4">
                        <p class="text-xs text-muted-foreground">Ejercicios</p>
                        <p class="mt-1 text-2xl font-bold" :style="{ color: 'var(--primary)' }">{{ uniqueExercises.length }}</p>
                    </div>
                    <div class="fv-kpi-card text-center !p-4">
                        <p class="text-xs text-muted-foreground">Archivos</p>
                        <p class="mt-1 text-2xl font-bold text-foreground">{{ files?.length ?? 0 }}</p>
                    </div>
                    <div class="fv-kpi-card text-center !p-4">
                        <p class="text-xs text-muted-foreground">Próx. cita</p>
                        <p class="mt-1 text-sm font-semibold" :style="{ color: 'var(--primary)' }">
                            {{ nextAppointment ? formatDateMx(nextAppointment.start_at) : 'Sin agendar' }}
                        </p>
                    </div>
                </div>

                <!-- Tabs de navegación -->
                <div class="flex gap-1 overflow-x-auto rounded-2xl border border-border bg-muted/40 p-1">
                    <button
                        v-for="tab in [
                            { key: 'resumen', label: 'Resumen' },
                            { key: 'sesiones', label: 'Sesiones' },
                            { key: 'ejercicios', label: 'Ejercicios' },
                            { key: 'archivos', label: 'Archivos' },
                            { key: 'citas', label: 'Citas' },
                        ]"
                        :key="tab.key"
                        type="button"
                        class="shrink-0 rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200"
                        :class="activeTab === tab.key
                            ? 'bg-card text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'"
                        @click="activeTab = (tab.key as any)"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- TAB: Resumen -->
                <div v-if="activeTab === 'resumen'" class="grid gap-4 lg:grid-cols-2">

                    <!-- Próxima cita -->
                    <div class="fv-panel">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Próxima cita</p>
                        <div v-if="nextAppointment" class="mt-3 flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-sm"
                                :style="primaryStyle"
                            >
                                <CalendarClock class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-foreground">{{ formatDateTimeMx(nextAppointment.start_at) }}</p>
                                <p v-if="nextAppointment.therapist_name" class="text-xs text-muted-foreground">Con {{ nextAppointment.therapist_name }}</p>
                                <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="statusColors[nextAppointment.status] ?? 'bg-muted text-muted-foreground'">
                                    {{ tAppointmentStatus(nextAppointment.status) }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="mt-3 flex items-center gap-2 text-sm text-muted-foreground">
                            <AlertCircle class="h-4 w-4" />
                            <span>No tienes citas próximas agendadas.</span>
                        </div>
                    </div>

                    <!-- Último plan/indicaciones -->
                    <div class="fv-panel">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Último plan de tratamiento</p>
                        <div v-if="lastSession" class="mt-3 space-y-3">
                            <p class="text-xs text-muted-foreground">Sesión del {{ formatDateMx(lastSession.session_date) }}</p>
                            <div v-if="lastSession.plan" class="rounded-xl bg-muted/40 p-3">
                                <p class="text-sm text-foreground leading-relaxed">{{ lastSession.plan }}</p>
                            </div>
                            <div v-if="lastSession.assessment" class="rounded-xl bg-muted/40 p-3">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Evaluación</p>
                                <p class="text-sm text-foreground leading-relaxed">{{ lastSession.assessment }}</p>
                            </div>
                            <div v-if="lastSession.pain_scale != null" class="flex items-center gap-2">
                                <HeartPulse class="h-4 w-4 shrink-0" :class="painColor(lastSession.pain_scale)" />
                                <span class="text-sm font-medium" :class="painColor(lastSession.pain_scale)">
                                    Dolor: {{ lastSession.pain_scale }}/10
                                </span>
                            </div>
                        </div>
                        <div v-else class="mt-3 text-sm text-muted-foreground">Sin sesiones registradas aún.</div>
                    </div>

                    <!-- Escala de dolor histórica -->
                    <div v-if="painHistory.length > 0" class="fv-panel lg:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Historial de dolor</p>
                        <div class="mt-4 flex items-end gap-3">
                            <div
                                v-for="s in painHistory"
                                :key="s.id"
                                class="flex flex-1 flex-col items-center gap-1.5"
                            >
                                <span class="text-xs font-bold" :class="painColor(s.pain_scale)">{{ s.pain_scale }}</span>
                                <div
                                    class="w-full rounded-t-lg transition-all"
                                    :class="painBg(s.pain_scale)"
                                    :style="{ height: `${(s.pain_scale! / 10) * 64}px` }"
                                />
                                <span class="text-[10px] text-muted-foreground">{{ formatDateMx(s.session_date).slice(0, 5) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Ejercicios recientes (preview) -->
                    <div v-if="uniqueExercises.length > 0" class="fv-panel">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Ejercicios asignados</p>
                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="ex in uniqueExercises.slice(0, 3)"
                                :key="ex.exercise_id"
                                class="flex items-center gap-3 rounded-xl bg-muted/40 px-3 py-2"
                            >
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" :style="primaryStyle">
                                    <Dumbbell class="h-4 w-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ ex.name }}</p>
                                    <p v-if="ex.sets || ex.reps || ex.seconds" class="text-xs text-muted-foreground">
                                        {{ [ex.sets && `${ex.sets} series`, ex.reps && `${ex.reps} reps`, ex.seconds && `${ex.seconds}s`].filter(Boolean).join(' · ') }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                        <button
                            v-if="uniqueExercises.length > 3"
                            type="button"
                            class="mt-3 text-xs font-medium hover:underline"
                            :style="{ color: 'var(--primary)' }"
                            @click="activeTab = 'ejercicios'"
                        >
                            Ver todos ({{ uniqueExercises.length }}) →
                        </button>
                    </div>

                    <!-- Actividades pendientes -->
                    <div v-if="activities && activities.length > 0" class="fv-panel">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Indicaciones pendientes</p>
                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="act in activities"
                                :key="act.id"
                                class="flex items-start gap-3 rounded-xl bg-muted/40 px-3 py-2"
                            >
                                <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
                                <div>
                                    <p class="text-sm text-foreground">{{ act.title }}</p>
                                    <p v-if="act.due_date" class="text-xs text-muted-foreground">Para: {{ formatDateMx(act.due_date) }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                </div>

                <!-- TAB: Sesiones -->
                <div v-if="activeTab === 'sesiones'" class="space-y-3">
                    <div v-if="!sessions?.length" class="fv-empty-state">
                        <Activity class="h-8 w-8 text-muted-foreground opacity-60" />
                        <p class="mt-3 text-sm text-muted-foreground">Sin sesiones registradas.</p>
                    </div>
                    <article
                        v-for="session in sessions"
                        :key="session.id"
                        class="fv-card-premium overflow-hidden"
                    >
                        <div
                            class="flex cursor-pointer items-center justify-between gap-4 p-4"
                            @click="toggleSession(session.id)"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :style="primaryStyle">
                                    <Activity class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-foreground">{{ formatDateMx(session.session_date) }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        <span v-if="session.therapist_name">{{ session.therapist_name }}</span>
                                        <span v-if="session.therapist_name && session.pain_scale != null"> · </span>
                                        <span v-if="session.pain_scale != null" :class="painColor(session.pain_scale)">
                                            Dolor: {{ session.pain_scale }}/10
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <component :is="expandedSession === session.id ? ChevronUp : ChevronDown" class="h-4 w-4 shrink-0 text-muted-foreground" />
                        </div>
                        <div v-if="expandedSession === session.id" class="border-t border-border bg-muted/20 p-4 space-y-3">
                            <div v-if="session.subjective" class="space-y-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase">Subjetivo</p>
                                <p class="text-sm text-foreground leading-relaxed">{{ session.subjective }}</p>
                            </div>
                            <div v-if="session.assessment" class="space-y-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase">Evaluación</p>
                                <p class="text-sm text-foreground leading-relaxed">{{ session.assessment }}</p>
                            </div>
                            <div v-if="session.plan" class="space-y-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase">Plan</p>
                                <p class="text-sm text-foreground leading-relaxed">{{ session.plan }}</p>
                            </div>
                            <div v-if="session.notes" class="space-y-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase">Notas</p>
                                <p class="text-sm text-foreground leading-relaxed">{{ session.notes }}</p>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- TAB: Ejercicios -->
                <div v-if="activeTab === 'ejercicios'">
                    <div v-if="!uniqueExercises.length" class="fv-empty-state">
                        <Dumbbell class="h-8 w-8 text-muted-foreground opacity-60" />
                        <p class="mt-3 text-sm text-muted-foreground">Sin ejercicios asignados aún.</p>
                    </div>
                    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="ex in uniqueExercises"
                            :key="ex.exercise_id"
                            class="fv-card-premium flex flex-col gap-3 p-4"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-sm"
                                    :style="primaryStyle"
                                >
                                    <Dumbbell class="h-5 w-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-foreground truncate">{{ ex.name }}</p>
                                    <p v-if="ex.sets || ex.reps || ex.seconds" class="text-xs text-muted-foreground">
                                        {{ [ex.sets && `${ex.sets} series`, ex.reps && `${ex.reps} reps`, ex.seconds && `${ex.seconds}s`].filter(Boolean).join(' · ') }}
                                    </p>
                                </div>
                            </div>
                            <p v-if="ex.description" class="text-xs text-muted-foreground leading-relaxed line-clamp-3">{{ ex.description }}</p>
                            <p v-if="ex.exercise_notes" class="text-xs text-foreground bg-muted/40 rounded-lg px-3 py-2">{{ ex.exercise_notes }}</p>
                            <a
                                v-if="ex.video_url"
                                :href="ex.video_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-auto flex items-center gap-1.5 text-xs font-medium hover:underline"
                                :style="{ color: 'var(--primary)' }"
                            >
                                <ExternalLink class="h-3.5 w-3.5" />
                                Ver video
                            </a>
                        </article>
                    </div>
                </div>

                <!-- TAB: Archivos -->
                <div v-if="activeTab === 'archivos'">
                    <div v-if="!files?.length" class="fv-empty-state">
                        <FileText class="h-8 w-8 text-muted-foreground opacity-60" />
                        <p class="mt-3 text-sm text-muted-foreground">Sin archivos compartidos.</p>
                    </div>
                    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="file in files"
                            :key="file.id"
                            class="fv-card-premium flex items-center gap-3 p-4"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-muted">
                                <FileText class="h-5 w-5 text-muted-foreground" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-foreground truncate">{{ file.original_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatDateMx(file.created_at) }}</p>
                            </div>
                            <a
                                :href="file.download_url"
                                target="_blank"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-muted hover:bg-muted/70 transition-colors"
                                title="Descargar"
                            >
                                <Download class="h-4 w-4 text-foreground" />
                            </a>
                        </article>
                    </div>
                </div>

                <!-- TAB: Citas -->
                <div v-if="activeTab === 'citas'" class="space-y-3">
                    <div v-if="!recentAppointments?.length" class="fv-empty-state">
                        <CalendarClock class="h-8 w-8 text-muted-foreground opacity-60" />
                        <p class="mt-3 text-sm text-muted-foreground">Sin citas registradas.</p>
                    </div>
                    <article
                        v-for="appt in recentAppointments"
                        :key="appt.id"
                        class="fv-card-premium flex items-center gap-4 p-4"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :style="primaryStyle">
                            <CalendarClock class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-foreground">{{ formatDateTimeMx(appt.start_at) }}</p>
                            <p v-if="appt.therapist_name" class="text-xs text-muted-foreground">Con {{ appt.therapist_name }}</p>
                        </div>
                        <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusColors[appt.status] ?? 'bg-muted text-muted-foreground'">
                            {{ tAppointmentStatus(appt.status) }}
                        </span>
                    </article>
                </div>

            </template>

        </section>
    </AppLayout>
</template>
