<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    CalendarClock, ClipboardPlus, Clock,
    Heart, UserRound, ChevronDown, ChevronUp, AlertCircle,
    CheckCircle2,
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

const props = defineProps<{
    appointments: AppointmentItem[];
    today: string;
    todayLabel: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mi Jornada', href: '/mi-jornada' },
];

const expandedId = ref<number | null>(null);

const toggle = (id: number) => {
    expandedId.value = expandedId.value === id ? null : id;
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

const totalCitas = computed(() => props.appointments.length);
const citasAtendidas = computed(() => props.appointments.filter(a => a.status === 'done').length);
const citasPendientes = computed(() => props.appointments.filter(a => ['scheduled','confirmed','arrived'].includes(a.status)).length);

const painColor = (scale: number | null) => {
    if (!scale) return 'text-muted-foreground';
    if (scale <= 3) return 'text-emerald-600 dark:text-emerald-400';
    if (scale <= 6) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
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
                <Button
                    class="mt-4 rounded-xl"
                    variant="outline"
                    @click="router.visit('/citas')"
                >
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
                            <!-- Hora -->
                            <div class="flex flex-col items-center rounded-xl bg-muted px-3 py-2 text-center shrink-0">
                                <span class="text-xs font-medium text-muted-foreground leading-none">{{ formatDateTimeMx(appt.start_at).split(' ')[1]?.slice(0,5) }}</span>
                            </div>
                            <!-- Datos -->
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
                            <!-- Botón atender directo -->
                            <Button
                                v-if="appt.status === 'arrived'"
                                size="sm"
                                class="h-8 rounded-xl text-xs font-medium"
                                :style="primaryButtonStyle"
                                @click.stop="atenderCita(appt)"
                            >
                                <ClipboardPlus class="mr-1.5 h-3.5 w-3.5" />
                                Atender
                            </Button>
                            <component :is="expandedId === appt.id ? ChevronUp : ChevronDown" class="h-4 w-4 text-muted-foreground" />
                        </div>
                    </div>

                    <!-- Panel expandible del paciente -->
                    <div v-if="expandedId === appt.id" class="border-t border-border bg-muted/30 p-4">
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
                                        @click="atenderCita(appt)"
                                    >
                                        <ClipboardPlus class="mr-2 h-3.5 w-3.5" />
                                        Registrar sesión clínica
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="w-full justify-start rounded-xl text-xs border-border"
                                        @click="router.visit(`/pacientes/${appt.patient_persona_id}`)"
                                    >
                                        <UserRound class="mr-2 h-3.5 w-3.5" />
                                        Ver expediente
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
                </article>
            </div>

        </section>
    </AppLayout>
</template>
