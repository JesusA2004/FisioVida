<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    useSesionCrud,
    type SesionRow,
} from '@/composables/crud/useSesionCrud';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import {
    Activity,
    AlertCircle,
    CalendarDays,
    ClipboardList,
    ClipboardPlus,
    Dumbbell,
    Eye,
    FileText,
    FolderOpen,
    HeartPulse,
    NotebookText,
    Pencil,
    Search,
    Trash2,
    UserCog,
    UserPlus,
    UsersRound,
    X,
} from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import FvPagination from '@/components/fv/FvPagination.vue';
import { formatDateMx } from '@/lib/dates';

type AppointmentLookup = {
    id: number;
    label: string;
    patient_persona_id: number;
    therapist_user_id: number;
    start_at: string;
    end_at?: string | null;
    status?: string | null;
    patient_name?: string | null;
    therapist_name?: string | null;
    linked_session_id?: number | null;
};

type ExerciseLookup = {
    id: number;
    name: string;
    description: string | null;
    video_url: string | null;
    is_active: boolean;
};

type PatientSummary = {
    patient_id: number;
    patient_name: string;
    total_sessions: number;
    total_appointments: number;
    avg_pain: number | null;
    last_session_date: string | null;
    last_pain_scale: number | null;
    last_assessment: string | null;
    last_plan: string | null;
    last_notes: string | null;
    last_appointment_at: string | null;
};

type SessionExerciseLookup = {
    session_id: number;
    exercise_id: number;
    sets: number | null;
    reps: number | null;
    seconds: number | null;
    notes: string | null;
    name: string;
    description: string | null;
    video_url: string | null;
};

type PatientGroup = {
    patient_persona_id: number;
    patient_name: string;
    summary: PatientSummary | null;
    sessions: SesionRow[];
    totalSessions: number;
    avgPain: number | null;
    lastSessionDate: string | null;
};

const props = defineProps<{
    rows: SesionRow[];
    page: {
        current_page: number;
        last_page: number;
        per_page?: number | string;
        per_page_selected?: number | string;
        from?: number | null;
        to?: number | null;
        total: number;
    };
    filters: {
        q?: string;
        per_page?: string | number;
    };
    lookups: {
        patients: { id: number; label: string }[];
        therapists: { id: number; label: string }[];
        appointments: AppointmentLookup[];
        exercises: ExerciseLookup[];
        patientSummaries: Record<number, PatientSummary>;
        sessionExercises: Record<number, SessionExerciseLookup[]>;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sesiones', href: '/sesiones' },
];

const {
    form,
    isOpen,
    editingId,
    isEditing,
    can,
    moduleEnabled,
    applyFilters,
    openCreate,
    openEdit,
    closeModal,
    submit,
    destroySesion,
    toggleExercise,
    goToPatients,
    goToTherapists,
} = useSesionCrud(props.filters);

const search = ref(props.filters.q ?? '');
const exerciseSearch = ref('');
const viewSessionOpen = ref(false);
const selectedSession = ref<SesionRow | null>(null);
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const appointmentMap = computed(() => {
    const map = new Map<number, AppointmentLookup>();

    props.lookups.appointments.forEach((appointment) => {
        map.set(appointment.id, appointment);
    });

    return map;
});

const availableAppointmentOptions = computed(() => {
    return props.lookups.appointments.filter((appointment) => {
        if (!appointment.linked_session_id) {
            return true;
        }

        return (
            isEditing.value &&
            editingId.value !== null &&
            appointment.linked_session_id === editingId.value
        );
    });
});

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.q ?? '';
    },
    { deep: true },
);

watch(search, (value) => {
    if (searchTimer) clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        applyFilters({
            q: value.trim(),
            page: 1,
            per_page: props.page.per_page_selected ?? props.page.per_page ?? 10,
        });
    }, 450);
});

watch(
    () => form.appointment_id,
    (appointmentId) => {
        if (!appointmentId) return;

        const appointment = appointmentMap.value.get(Number(appointmentId));

        if (!appointment) return;

        form.patient_persona_id = appointment.patient_persona_id;
        form.therapist_user_id = appointment.therapist_user_id;
        form.session_date =
            appointment.start_at?.slice(0, 10) ?? form.session_date;
    },
);

const clearSearch = () => {
    search.value = '';
};

const painBadgeClass = (value?: number | null) => {
    if (value === null || value === undefined) {
        return 'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300';
    }

    if (value <= 3) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    if (value <= 6) {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-300';
    }

    return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300';
};

const selectedAppointmentText = computed(() => {
    if (!form.appointment_id) {
        return 'Sin cita relacionada';
    }

    const appointment = appointmentMap.value.get(Number(form.appointment_id));

    return appointment?.label ?? 'Cita seleccionada';
});

const selectedPatientSummary = computed(() => {
    if (!form.patient_persona_id) return null;

    return (
        props.lookups.patientSummaries?.[Number(form.patient_persona_id)] ??
        null
    );
});

const filteredExercises = computed(() => {
    const q = exerciseSearch.value.trim().toLowerCase();

    if (!q) return props.lookups.exercises ?? [];

    return (props.lookups.exercises ?? []).filter((exercise) => {
        return (
            exercise.name.toLowerCase().includes(q) ||
            (exercise.description ?? '').toLowerCase().includes(q)
        );
    });
});

const sessionExercises = (sessionId: number) => {
    return props.lookups.sessionExercises?.[sessionId] ?? [];
};

const sessionExerciseIds = (row: SesionRow) => {
    return sessionExercises(row.id).map((item) => item.exercise_id);
};

const selectedExercisesCount = computed(() => form.exercise_ids.length);

const groupedPatients = computed<PatientGroup[]>(() => {
    const map = new Map<number, PatientGroup>();

    props.rows.forEach((row) => {
        const patientId = row.patient_persona_id;
        const summary =
            props.lookups.patientSummaries?.[Number(patientId)] ?? null;

        if (!map.has(patientId)) {
            map.set(patientId, {
                patient_persona_id: patientId,
                patient_name: row.patient_name,
                summary,
                sessions: [],
                totalSessions: 0,
                avgPain: null,
                lastSessionDate: null,
            });
        }

        map.get(patientId)?.sessions.push(row);
    });

    return Array.from(map.values()).map((group) => {
        const painValues = group.sessions
            .map((session) => session.pain_scale)
            .filter((value): value is number => value !== null && value !== undefined);

        const avgPain =
            painValues.length > 0
                ? Number(
                      (
                          painValues.reduce((sum, value) => sum + value, 0) /
                          painValues.length
                      ).toFixed(1),
                  )
                : group.summary?.avg_pain ?? null;

        const sortedSessions = [...group.sessions].sort((a, b) =>
            String(b.session_date).localeCompare(String(a.session_date)),
        );

        return {
            ...group,
            sessions: sortedSessions,
            totalSessions: group.summary?.total_sessions ?? group.sessions.length,
            avgPain,
            lastSessionDate:
                group.summary?.last_session_date ??
                sortedSessions[0]?.session_date ??
                null,
        };
    });
});

const openViewSession = (row: SesionRow) => {
    selectedSession.value = row;
    viewSessionOpen.value = true;
};

const closeViewSession = () => {
    viewSessionOpen.value = false;
    selectedSession.value = null;
};

const editFromView = () => {
    if (!selectedSession.value) return;

    openEdit(selectedSession.value, sessionExerciseIds(selectedSession.value));
    closeViewSession();
};

const goToPatientRecord = (patientPersonaId: number) => {
    router.visit(`/pacientes/${patientPersonaId}`);
};

const selectedSessionExercises = computed(() => {
    if (!selectedSession.value) return [];

    return sessionExercises(selectedSession.value.id);
});

const inputBase =
    'h-11 rounded-2xl border-zinc-200 bg-white shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus-visible:border-[color:var(--primary)] focus-visible:ring-2 focus-visible:ring-[color:var(--primary)]/20 dark:border-zinc-800 dark:bg-zinc-900/80';

const labelBase = 'text-sm font-medium text-zinc-800 dark:text-zinc-100';

const sectionTitleBase =
    'flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-zinc-100';

const primaryButtonStyle = {
    backgroundColor: 'var(--primary)',
    color: 'var(--primary-foreground)',
};

const primarySoftStyle = {
    backgroundColor: 'color-mix(in srgb, var(--primary) 8%, white)',
};

const setPrimaryHover = (event: MouseEvent) => {
    const hoverColor =
        getComputedStyle(document.documentElement)
            .getPropertyValue('--primary-hover')
            .trim() || 'var(--primary)';

    (event.currentTarget as HTMLElement).style.backgroundColor = hoverColor;
};

const setPrimaryNormal = (event: MouseEvent) => {
    (event.currentTarget as HTMLElement).style.backgroundColor =
        'var(--primary)';
};
</script>

<template>
    <Head title="Sesiones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="w-full space-y-5">
            <div
                v-if="!moduleEnabled"
                class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
            >
                Módulo de sesiones deshabilitado.
            </div>

            <template v-else>
                <div
                    class="relative overflow-hidden rounded-[2rem] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/40"
                >
                    <div
                        class="pointer-events-none absolute -top-20 -right-20 h-52 w-52 rounded-full blur-3xl"
                        :style="{
                            backgroundColor:
                                'color-mix(in srgb, var(--primary) 16%, transparent)',
                        }"
                    />

                    <div
                        class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl shadow-lg"
                                :style="primaryButtonStyle"
                            >
                                <HeartPulse class="h-6 w-6" />
                            </div>

                            <div>
                                <h1
                                    class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50"
                                >
                                    Sesiones clínicas
                                </h1>

                                <p
                                    class="mt-1 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-400"
                                >
                                    Consulta la evolución agrupada por paciente,
                                    registra sesiones, SOAP, dolor y ejercicios
                                    asignados.
                                </p>
                            </div>
                        </div>

                        <Button
                            v-if="can('sessions.create')"
                            class="h-11 rounded-2xl px-5 shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl"
                            :style="primaryButtonStyle"
                            @mouseenter="setPrimaryHover"
                            @mouseleave="setPrimaryNormal"
                            @click="openCreate"
                        >
                            <ClipboardPlus class="mr-2 h-4 w-4" />
                            Nueva sesión
                        </Button>
                    </div>
                </div>

                <div
                    class="rounded-[1.75rem] border border-zinc-200 bg-zinc-50 p-4 shadow-sm transition-all duration-300 dark:border-zinc-800 dark:bg-zinc-900/40"
                >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-zinc-400"
                        />

                        <Input
                            v-model="search"
                            class="h-11 rounded-2xl border-zinc-200 bg-white pr-10 pl-9 shadow-sm transition-all duration-200 focus-visible:border-[color:var(--primary)] focus-visible:ring-2 focus-visible:ring-[color:var(--primary)]/20 dark:border-zinc-800 dark:bg-zinc-950"
                            placeholder="Buscar por paciente, terapeuta, evaluación o plan"
                        />

                        <button
                            v-if="search"
                            type="button"
                            class="absolute top-3 right-3 grid h-5 w-5 place-items-center rounded-full text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                            @click="clearSearch"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div
                    v-if="props.rows.length === 0"
                    class="rounded-[2rem] border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center transition-all duration-300 dark:border-zinc-700 dark:bg-zinc-900/40"
                >
                    <div
                        class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white shadow-sm dark:bg-zinc-950"
                    >
                        <AlertCircle class="h-6 w-6 text-zinc-400" />
                    </div>

                    <h3
                        class="mt-4 text-lg font-semibold text-zinc-800 dark:text-zinc-100"
                    >
                        Sin sesiones para mostrar
                    </h3>

                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                        Crea una sesión nueva o ajusta la búsqueda.
                    </p>
                </div>

                <div v-else class="space-y-5">
                    <article
                        v-for="group in groupedPatients"
                        :key="group.patient_persona_id"
                        class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm transition-all duration-300 hover:border-[color:var(--primary)] hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div
                            class="relative overflow-hidden border-b border-zinc-100 p-4 sm:p-5 dark:border-zinc-800"
                        >
                            <div
                                class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full blur-3xl"
                                :style="{
                                    backgroundColor:
                                        'color-mix(in srgb, var(--primary) 14%, transparent)',
                                }"
                            />

                            <div
                                class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl"
                                        :style="primarySoftStyle"
                                    >
                                        <UsersRound
                                            class="h-6 w-6"
                                            :style="{ color: 'var(--primary)' }"
                                        />
                                    </div>

                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h2
                                                class="text-lg font-semibold text-zinc-950 dark:text-zinc-50"
                                            >
                                                {{ group.patient_name }}
                                            </h2>

                                            <Badge
                                                class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                                            >
                                                {{ group.totalSessions }}
                                                sesiones
                                            </Badge>

                                            <Badge
                                                class="rounded-full border px-3 py-1 text-xs"
                                                :class="painBadgeClass(group.avgPain)"
                                            >
                                                Dolor promedio:
                                                {{ group.avgPain ?? '—' }}/10
                                            </Badge>
                                        </div>

                                        <p
                                            class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400"
                                        >
                                            Última sesión:
                                            {{
                                                group.lastSessionDate
                                                    ? formatDateMx(group.lastSessionDate)
                                                    : 'Sin registro'
                                            }}
                                            · Citas registradas:
                                            {{ group.summary?.total_appointments ?? '—' }}
                                        </p>
                                    </div>
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                    @click="goToPatientRecord(group.patient_persona_id)"
                                >
                                    <FolderOpen class="mr-2 h-4 w-4" />
                                    Ver expediente
                                </Button>
                            </div>
                        </div>

                        <div class="grid gap-3 p-4 sm:p-5">
                            <div
                                v-for="row in group.sessions"
                                :key="row.id"
                                class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950/40"
                            >
                                <div
                                    class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                                >
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Badge
                                                class="rounded-full border px-3 py-1 text-xs"
                                                :class="painBadgeClass(row.pain_scale)"
                                            >
                                                Dolor:
                                                {{ row.pain_scale ?? '—' }}/10
                                            </Badge>

                                            <Badge
                                                class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                                            >
                                                {{
                                                    row.appointment_id
                                                        ? `Cita #${row.appointment_id}`
                                                        : 'Sin cita'
                                                }}
                                            </Badge>
                                        </div>

                                        <div
                                            class="mt-3 grid gap-2 text-xs text-zinc-500 sm:grid-cols-2 dark:text-zinc-400"
                                        >
                                            <div class="flex items-center gap-2">
                                                <CalendarDays
                                                    class="h-3.5 w-3.5 shrink-0"
                                                />
                                                <span>
                                                    {{ formatDateMx(row.session_date) }}
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <UsersRound
                                                    class="h-3.5 w-3.5 shrink-0"
                                                />
                                                <span class="truncate">
                                                    {{ row.therapist_name }}
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            class="mt-3 rounded-2xl p-3 text-xs leading-5 text-zinc-600 dark:text-zinc-300"
                                            :style="primarySoftStyle"
                                        >
                                            <p class="line-clamp-2">
                                                <strong>Evaluación:</strong>
                                                {{
                                                    row.assessment ||
                                                    'Sin evaluación.'
                                                }}
                                            </p>

                                            <p class="mt-1 line-clamp-2">
                                                <strong>Plan:</strong>
                                                {{
                                                    row.plan ||
                                                    'Sin plan registrado.'
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl"
                                        :style="primarySoftStyle"
                                    >
                                        <Activity
                                            class="h-5 w-5"
                                            :style="{ color: 'var(--primary)' }"
                                        />
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <Button
                                        variant="outline"
                                        class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                        @click="openViewSession(row)"
                                    >
                                        <Eye class="mr-2 h-4 w-4" />
                                        Ver sesión
                                    </Button>

                                    <Button
                                        v-if="can('sessions.update')"
                                        variant="outline"
                                        class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                        @click="openEdit(row, sessionExerciseIds(row))"
                                    >
                                        <Pencil class="mr-2 h-4 w-4" />
                                        Editar
                                    </Button>

                                    <Button
                                        v-if="can('sessions.delete')"
                                        variant="destructive"
                                        class="h-10 rounded-xl transition-all duration-200 hover:-translate-y-0.5"
                                        @click="destroySesion(row)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Eliminar
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <FvPagination
                    :page="props.page"
                    item-label="sesiones"
                    :per-page-options="[10, 15, 20, 50, 'all']"
                    @change="
                        (page) =>
                            applyFilters({
                                q: search.trim(),
                                page,
                                per_page:
                                    props.page.per_page_selected ??
                                    props.page.per_page ??
                                    10,
                            })
                    "
                    @per-page-change="
                        (perPage) =>
                            applyFilters({
                                q: search.trim(),
                                page: 1,
                                per_page: perPage,
                            })
                    "
                />
            </template>
        </section>

        <Dialog :open="isOpen" @update:open="closeModal">
            <DialogContent
                class="flex max-h-[94dvh] w-[calc(100vw-1rem)] max-w-none !gap-0 overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white !p-0 shadow-2xl sm:w-[calc(100vw-2rem)] sm:!max-w-[calc(100vw-2rem)] md:!max-w-[92vw] lg:!max-w-[1080px] xl:!max-w-[1220px] 2xl:!max-w-[1320px] dark:border-zinc-800 dark:bg-zinc-950"
            >
                <div class="flex max-h-[94dvh] min-h-0 w-full flex-col">
                    <DialogHeader
                        class="shrink-0 border-b border-zinc-100 bg-white px-4 py-4 sm:px-6 lg:px-7 dark:border-zinc-800 dark:bg-zinc-950"
                    >
                        <div
                            class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                        >
                            <div class="min-w-0">
                                <DialogTitle
                                    class="flex items-center gap-2 text-lg font-semibold text-zinc-950 sm:text-xl dark:text-zinc-50"
                                >
                                    <span
                                        class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl shadow-sm"
                                        :style="primaryButtonStyle"
                                    >
                                        <HeartPulse class="h-4 w-4" />
                                    </span>

                                    <span>
                                        {{
                                            isEditing
                                                ? 'Editar sesión'
                                                : 'Nueva sesión'
                                        }}
                                    </span>
                                </DialogTitle>

                                <DialogDescription
                                    class="mt-2 max-w-3xl text-sm leading-6 text-zinc-500 dark:text-zinc-400"
                                >
                                    Captura la evolución clínica del paciente.
                                    Si seleccionas una cita, se precargan
                                    paciente, terapeuta y fecha.
                                </DialogDescription>
                            </div>

                            <div
                                class="rounded-2xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs leading-5 text-zinc-500 lg:w-80 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400"
                            >
                                <span
                                    class="font-medium text-zinc-700 dark:text-zinc-200"
                                >
                                    Cita:
                                </span>
                                {{ selectedAppointmentText }}
                            </div>
                        </div>
                    </DialogHeader>

                    <div
                        class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-zinc-50/70 px-4 py-4 sm:px-6 sm:py-5 lg:px-7 dark:bg-zinc-950"
                    >
                        <div
                            class="grid gap-4 pb-5 lg:grid-cols-[minmax(0,1fr)_minmax(360px,0.65fr)] xl:grid-cols-[minmax(0,1.2fr)_minmax(420px,0.8fr)]"
                        >
                            <div class="space-y-4">
                                <section
                                    class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <FileText
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Datos de la sesión
                                        </h3>

                                        <p
                                            class="text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                        >
                                            Puedes relacionar la sesión con una
                                            cita para llenar datos
                                            automáticamente.
                                        </p>
                                    </div>

                                    <div
                                        class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                                    >
                                        <div
                                            class="space-y-2 md:col-span-2 xl:col-span-3"
                                        >
                                            <Label :class="labelBase">
                                                Cita relacionada
                                                <span
                                                    class="text-xs font-normal text-zinc-400"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <SearchableSelect
                                                v-model="form.appointment_id"
                                                :options="[
                                                    {
                                                        value: '',
                                                        label: 'Sin cita relacionada',
                                                    },
                                                    ...availableAppointmentOptions.map(
                                                        (appointment) => ({
                                                            value: appointment.id,
                                                            label: appointment.label,
                                                        }),
                                                    ),
                                                ]"
                                            />

                                            <p class="text-xs text-zinc-400">
                                                Al seleccionar una cita se
                                                llenan paciente, terapeuta y
                                                fecha de sesión.
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Paciente
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>

                                            <SearchableSelect
                                                v-model="
                                                    form.patient_persona_id
                                                "
                                                :options="[
                                                    {
                                                        value: '',
                                                        label: 'Seleccionar paciente',
                                                    },
                                                    ...props.lookups.patients.map(
                                                        (patient) => ({
                                                            value: patient.id,
                                                            label: patient.label,
                                                        }),
                                                    ),
                                                ]"
                                            />

                                            <p
                                                v-if="
                                                    form.errors
                                                        .patient_persona_id
                                                "
                                                class="text-xs text-red-500"
                                            >
                                                {{
                                                    form.errors
                                                        .patient_persona_id
                                                }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Terapeuta
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>

                                            <SearchableSelect
                                                v-model="form.therapist_user_id"
                                                :options="[
                                                    {
                                                        value: '',
                                                        label: 'Seleccionar terapeuta',
                                                    },
                                                    ...props.lookups.therapists.map(
                                                        (therapist) => ({
                                                            value: therapist.id,
                                                            label: therapist.label,
                                                        }),
                                                    ),
                                                ]"
                                            />

                                            <p
                                                v-if="
                                                    form.errors
                                                        .therapist_user_id
                                                "
                                                class="text-xs text-red-500"
                                            >
                                                {{
                                                    form.errors
                                                        .therapist_user_id
                                                }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Fecha sesión
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>

                                            <DatePicker
                                                v-model="form.session_date"
                                            />

                                            <p
                                                v-if="form.errors.session_date"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.session_date }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Dolor 0-10
                                                <span
                                                    class="text-xs font-normal text-zinc-400"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <Input
                                                v-model="form.pain_scale"
                                                :class="inputBase"
                                                type="number"
                                                min="0"
                                                max="10"
                                                placeholder="Ej. 3"
                                            />

                                            <p
                                                v-if="form.errors.pain_scale"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.pain_scale }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <Button
                                            v-if="can('patients.create')"
                                            type="button"
                                            variant="outline"
                                            class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                            @click="goToPatients"
                                        >
                                            <UserPlus class="mr-2 h-4 w-4" />
                                            Agregar paciente
                                        </Button>

                                        <Button
                                            v-if="can('users.create')"
                                            type="button"
                                            variant="outline"
                                            class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                            @click="goToTherapists"
                                        >
                                            <UserCog class="mr-2 h-4 w-4" />
                                            Agregar terapeuta
                                        </Button>
                                    </div>
                                </section>

                                <section
                                    class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <Activity
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            SOAP clínico
                                        </h3>

                                        <p
                                            class="text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                        >
                                            Registra la evolución para mantener
                                            actualizado el expediente.
                                        </p>
                                    </div>

                                    <div class="grid gap-4">
                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Subjetivo
                                            </Label>

                                            <textarea
                                                v-model="form.subjective"
                                                class="min-h-24 w-full resize-y rounded-2xl border border-zinc-200 bg-white p-3 text-sm shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus:border-[color:var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/20 focus:outline-none dark:border-zinc-800 dark:bg-zinc-900/80"
                                                placeholder="Lo que refiere el paciente..."
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Objetivo
                                            </Label>

                                            <textarea
                                                v-model="form.objective"
                                                class="min-h-24 w-full resize-y rounded-2xl border border-zinc-200 bg-white p-3 text-sm shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus:border-[color:var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/20 focus:outline-none dark:border-zinc-800 dark:bg-zinc-900/80"
                                                placeholder="Observaciones, movilidad, pruebas..."
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Evaluación
                                            </Label>

                                            <textarea
                                                v-model="form.assessment"
                                                class="min-h-24 w-full resize-y rounded-2xl border border-zinc-200 bg-white p-3 text-sm shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus:border-[color:var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/20 focus:outline-none dark:border-zinc-800 dark:bg-zinc-900/80"
                                                placeholder="Interpretación clínica..."
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Plan
                                            </Label>

                                            <textarea
                                                v-model="form.plan"
                                                class="min-h-24 w-full resize-y rounded-2xl border border-zinc-200 bg-white p-3 text-sm shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus:border-[color:var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/20 focus:outline-none dark:border-zinc-800 dark:bg-zinc-900/80"
                                                placeholder="Plan terapéutico, recomendaciones y seguimiento..."
                                            />
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="space-y-4">
                                <section
                                    class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <ClipboardList
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Resumen del expediente
                                        </h3>
                                    </div>

                                    <div
                                        v-if="selectedPatientSummary"
                                        class="space-y-3 rounded-2xl p-4"
                                        :style="primarySoftStyle"
                                    >
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <p
                                                    class="text-xs text-zinc-500"
                                                >
                                                    Sesiones previas
                                                </p>
                                                <p
                                                    class="text-lg font-semibold text-zinc-950 dark:text-zinc-50"
                                                >
                                                    {{
                                                        selectedPatientSummary.total_sessions
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-xs text-zinc-500"
                                                >
                                                    Citas registradas
                                                </p>
                                                <p
                                                    class="text-lg font-semibold text-zinc-950 dark:text-zinc-50"
                                                >
                                                    {{
                                                        selectedPatientSummary.total_appointments
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-xs text-zinc-500"
                                                >
                                                    Dolor promedio
                                                </p>
                                                <p
                                                    class="text-lg font-semibold text-zinc-950 dark:text-zinc-50"
                                                >
                                                    {{
                                                        selectedPatientSummary.avg_pain ??
                                                        '—'
                                                    }}/10
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-xs text-zinc-500"
                                                >
                                                    Último dolor
                                                </p>
                                                <p
                                                    class="text-lg font-semibold text-zinc-950 dark:text-zinc-50"
                                                >
                                                    {{
                                                        selectedPatientSummary.last_pain_scale ??
                                                        '—'
                                                    }}/10
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            class="rounded-2xl bg-white/70 p-3 text-xs leading-5 text-zinc-600 dark:bg-zinc-950/40 dark:text-zinc-300"
                                        >
                                            <p>
                                                <strong>
                                                    Última evaluación:
                                                </strong>
                                                {{
                                                    selectedPatientSummary.last_assessment ||
                                                    'Sin evaluación previa.'
                                                }}
                                            </p>

                                            <p class="mt-2">
                                                <strong>Último plan:</strong>
                                                {{
                                                    selectedPatientSummary.last_plan ||
                                                    'Sin plan previo.'
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/40 dark:text-zinc-400"
                                    >
                                        Selecciona un paciente para ver un
                                        resumen rápido de su expediente.
                                    </div>
                                </section>

                                <section
                                    class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <Dumbbell
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Ejercicios para esta sesión
                                        </h3>

                                        <p
                                            class="text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                        >
                                            Selecciona los ejercicios que se
                                            asignarán al guardar la sesión.
                                            Seleccionados:
                                            {{ selectedExercisesCount }}
                                        </p>
                                    </div>

                                    <div class="relative mb-3">
                                        <Search
                                            class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-zinc-400"
                                        />

                                        <Input
                                            v-model="exerciseSearch"
                                            class="h-11 rounded-2xl border-zinc-200 bg-white pr-10 pl-9 shadow-sm transition-all duration-200 focus-visible:border-[color:var(--primary)] focus-visible:ring-2 focus-visible:ring-[color:var(--primary)]/20 dark:border-zinc-800 dark:bg-zinc-950"
                                            placeholder="Buscar ejercicio..."
                                        />
                                    </div>

                                    <div
                                        v-if="filteredExercises.length === 0"
                                        class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/40 dark:text-zinc-400"
                                    >
                                        No hay ejercicios disponibles con esa
                                        búsqueda.
                                    </div>

                                    <div
                                        v-else
                                        class="grid max-h-[360px] gap-3 overflow-y-auto pr-1"
                                    >
                                        <button
                                            v-for="exercise in filteredExercises"
                                            :key="exercise.id"
                                            type="button"
                                            class="rounded-2xl border p-3 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:shadow-sm"
                                            :class="
                                                form.exercise_ids.includes(
                                                    exercise.id,
                                                )
                                                    ? 'border-[color:var(--primary)] bg-[color-mix(in_srgb,var(--primary)_8%,white)]'
                                                    : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/60'
                                            "
                                            @click="toggleExercise(exercise.id)"
                                        >
                                            <div class="flex items-start gap-3">
                                                <input
                                                    type="checkbox"
                                                    class="mt-1 h-4 w-4 rounded border-zinc-300 accent-[var(--primary)]"
                                                    :checked="
                                                        form.exercise_ids.includes(
                                                            exercise.id,
                                                        )
                                                    "
                                                    @click.stop="
                                                        toggleExercise(
                                                            exercise.id,
                                                        )
                                                    "
                                                />

                                                <div class="min-w-0 flex-1">
                                                    <p
                                                        class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                                    >
                                                        {{ exercise.name }}
                                                    </p>

                                                    <p
                                                        class="mt-1 line-clamp-2 text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                                    >
                                                        {{
                                                            exercise.description ||
                                                            'Sin descripción.'
                                                        }}
                                                    </p>

                                                    <p
                                                        v-if="
                                                            exercise.video_url
                                                        "
                                                        class="mt-2 truncate text-xs text-[color:var(--primary)]"
                                                    >
                                                        Video disponible
                                                    </p>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </section>

                                <section
                                    class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <FileText
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Notas adicionales
                                        </h3>
                                    </div>

                                    <textarea
                                        v-model="form.notes"
                                        class="min-h-40 w-full resize-y rounded-2xl border border-zinc-200 bg-white p-3 text-sm shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus:border-[color:var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/20 focus:outline-none dark:border-zinc-800 dark:bg-zinc-900/80"
                                        placeholder="Notas internas de la sesión..."
                                    />
                                </section>
                            </div>
                        </div>
                    </div>

                    <DialogFooter
                        class="shrink-0 border-t border-zinc-100 bg-white px-4 py-3 sm:px-6 lg:px-7 dark:border-zinc-800 dark:bg-zinc-950"
                    >
                        <div
                            class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                        >
                            <Button
                                variant="outline"
                                class="h-11 w-full rounded-2xl px-5 transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)] sm:w-auto sm:min-w-36"
                                :disabled="form.processing"
                                @click="closeModal"
                            >
                                Cancelar
                            </Button>

                            <Button
                                class="h-11 w-full rounded-2xl px-5 shadow-lg transition-all duration-300 hover:-translate-y-0.5 sm:w-auto sm:min-w-40"
                                :style="primaryButtonStyle"
                                :disabled="form.processing"
                                @mouseenter="setPrimaryHover"
                                @mouseleave="setPrimaryNormal"
                                @click="submit"
                            >
                                {{
                                    form.processing
                                        ? 'Guardando...'
                                        : isEditing
                                          ? 'Actualizar sesión'
                                          : 'Crear sesión'
                                }}
                            </Button>
                        </div>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog :open="viewSessionOpen" @update:open="closeViewSession">
            <DialogContent
                class="flex max-h-[94dvh] w-[calc(100vw-1rem)] max-w-none !gap-0 overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white !p-0 shadow-2xl sm:w-[calc(100vw-2rem)] sm:!max-w-[calc(100vw-2rem)] md:!max-w-[92vw] lg:!max-w-[980px] xl:!max-w-[1120px] dark:border-zinc-800 dark:bg-zinc-950"
            >
                <div class="flex max-h-[94dvh] min-h-0 w-full flex-col">
                    <DialogHeader
                        class="shrink-0 border-b border-zinc-100 bg-white px-4 py-4 sm:px-6 lg:px-7 dark:border-zinc-800 dark:bg-zinc-950"
                    >
                        <DialogTitle
                            class="flex items-center gap-2 text-lg font-semibold text-zinc-950 sm:text-xl dark:text-zinc-50"
                        >
                            <span
                                class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl shadow-sm"
                                :style="primaryButtonStyle"
                            >
                                <Eye class="h-4 w-4" />
                            </span>

                            Detalle de sesión
                        </DialogTitle>

                        <DialogDescription
                            v-if="selectedSession"
                            class="mt-2 max-w-3xl text-sm leading-6 text-zinc-500 dark:text-zinc-400"
                        >
                            {{ selectedSession.patient_name }} ·
                            {{ formatDateMx(selectedSession.session_date) }}
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        v-if="selectedSession"
                        class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-zinc-50/70 px-4 py-4 sm:px-6 sm:py-5 lg:px-7 dark:bg-zinc-950"
                    >
                        <div class="grid gap-4 lg:grid-cols-[1fr_360px]">
                            <div class="space-y-4">
                                <section
                                    class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <NotebookText
                                                class="h-4 w-4"
                                                :style="{ color: 'var(--primary)' }"
                                            />
                                            Información general
                                        </h3>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div
                                            class="rounded-2xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950/40"
                                        >
                                            <p class="text-xs text-zinc-500">
                                                Paciente
                                            </p>
                                            <p
                                                class="mt-1 text-sm font-semibold text-zinc-950 dark:text-zinc-50"
                                            >
                                                {{ selectedSession.patient_name }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950/40"
                                        >
                                            <p class="text-xs text-zinc-500">
                                                Terapeuta
                                            </p>
                                            <p
                                                class="mt-1 text-sm font-semibold text-zinc-950 dark:text-zinc-50"
                                            >
                                                {{ selectedSession.therapist_name }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950/40"
                                        >
                                            <p class="text-xs text-zinc-500">
                                                Fecha
                                            </p>
                                            <p
                                                class="mt-1 text-sm font-semibold text-zinc-950 dark:text-zinc-50"
                                            >
                                                {{
                                                    formatDateMx(
                                                        selectedSession.session_date,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950/40"
                                        >
                                            <p class="text-xs text-zinc-500">
                                                Dolor
                                            </p>
                                            <Badge
                                                class="mt-1 rounded-full border px-3 py-1 text-xs"
                                                :class="
                                                    painBadgeClass(
                                                        selectedSession.pain_scale,
                                                    )
                                                "
                                            >
                                                {{
                                                    selectedSession.pain_scale ??
                                                    '—'
                                                }}/10
                                            </Badge>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <Activity
                                                class="h-4 w-4"
                                                :style="{ color: 'var(--primary)' }"
                                            />
                                            SOAP clínico
                                        </h3>
                                    </div>

                                    <div class="space-y-3">
                                        <div
                                            class="rounded-2xl p-3 text-sm leading-6 text-zinc-700 dark:text-zinc-300"
                                            :style="primarySoftStyle"
                                        >
                                            <strong>Subjetivo:</strong>
                                            <p class="mt-1 whitespace-pre-line">
                                                {{
                                                    selectedSession.subjective ||
                                                    'Sin información registrada.'
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl p-3 text-sm leading-6 text-zinc-700 dark:text-zinc-300"
                                            :style="primarySoftStyle"
                                        >
                                            <strong>Objetivo:</strong>
                                            <p class="mt-1 whitespace-pre-line">
                                                {{
                                                    selectedSession.objective ||
                                                    'Sin información registrada.'
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl p-3 text-sm leading-6 text-zinc-700 dark:text-zinc-300"
                                            :style="primarySoftStyle"
                                        >
                                            <strong>Evaluación:</strong>
                                            <p class="mt-1 whitespace-pre-line">
                                                {{
                                                    selectedSession.assessment ||
                                                    'Sin evaluación registrada.'
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl p-3 text-sm leading-6 text-zinc-700 dark:text-zinc-300"
                                            :style="primarySoftStyle"
                                        >
                                            <strong>Plan:</strong>
                                            <p class="mt-1 whitespace-pre-line">
                                                {{
                                                    selectedSession.plan ||
                                                    'Sin plan registrado.'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="space-y-4">
                                <section
                                    class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <Dumbbell
                                                class="h-4 w-4"
                                                :style="{ color: 'var(--primary)' }"
                                            />
                                            Ejercicios asignados
                                        </h3>
                                    </div>

                                    <div
                                        v-if="selectedSessionExercises.length === 0"
                                        class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/40"
                                    >
                                        Esta sesión no tiene ejercicios
                                        asignados.
                                    </div>

                                    <div v-else class="grid gap-3">
                                        <div
                                            v-for="exercise in selectedSessionExercises"
                                            :key="exercise.exercise_id"
                                            class="rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-950/40"
                                        >
                                            <p
                                                class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                            >
                                                {{ exercise.name }}
                                            </p>

                                            <p
                                                class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                            >
                                                {{
                                                    exercise.description ||
                                                    'Sin descripción.'
                                                }}
                                            </p>

                                            <p
                                                v-if="exercise.video_url"
                                                class="mt-2 text-xs text-[color:var(--primary)]"
                                            >
                                                Video disponible
                                            </p>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <FileText
                                                class="h-4 w-4"
                                                :style="{ color: 'var(--primary)' }"
                                            />
                                            Notas adicionales
                                        </h3>
                                    </div>

                                    <p
                                        class="whitespace-pre-line rounded-2xl bg-zinc-50 p-3 text-sm leading-6 text-zinc-600 dark:bg-zinc-950/40 dark:text-zinc-300"
                                    >
                                        {{
                                            selectedSession.notes ||
                                            'Sin notas adicionales.'
                                        }}
                                    </p>
                                </section>
                            </div>
                        </div>
                    </div>

                    <DialogFooter
                        class="shrink-0 border-t border-zinc-100 bg-white px-4 py-3 sm:px-6 lg:px-7 dark:border-zinc-800 dark:bg-zinc-950"
                    >
                        <div
                            class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                        >
                            <Button
                                variant="outline"
                                class="h-11 w-full rounded-2xl px-5 transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)] sm:w-auto sm:min-w-36"
                                @click="closeViewSession"
                            >
                                Cerrar
                            </Button>

                            <Button
                                v-if="selectedSession && can('sessions.update')"
                                class="h-11 w-full rounded-2xl px-5 shadow-lg transition-all duration-300 hover:-translate-y-0.5 sm:w-auto sm:min-w-40"
                                :style="primaryButtonStyle"
                                @mouseenter="setPrimaryHover"
                                @mouseleave="setPrimaryNormal"
                                @click="editFromView"
                            >
                                Editar sesión
                            </Button>
                        </div>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
