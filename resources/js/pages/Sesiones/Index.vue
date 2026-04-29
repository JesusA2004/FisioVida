<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
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
    ClipboardPlus,
    Dumbbell,
    FileText,
    HeartPulse,
    Pencil,
    Search,
    Trash2,
    UserRound,
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
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sesiones', href: '/sesiones' },
];

const {
    form,
    isOpen,
    isEditing,
    can,
    moduleEnabled,
    applyFilters,
    openCreate,
    openEdit,
    closeModal,
    submit,
    destroySesion,
    goToPatient,
    goToExercises,
} = useSesionCrud(props.filters);

const search = ref(props.filters.q ?? '');
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const appointmentMap = computed(() => {
    const map = new Map<number, AppointmentLookup>();

    props.lookups.appointments.forEach((appointment) => {
        map.set(appointment.id, appointment);
    });

    return map;
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
                                    Registra la evolución del paciente, SOAP,
                                    escala de dolor y plan terapéutico. Desde
                                    aquí puedes continuar al expediente y
                                    ejercicios.
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

                <div v-else class="grid gap-4 xl:grid-cols-2">
                    <article
                        v-for="row in props.rows"
                        :key="row.id"
                        class="group rounded-[1.75rem] border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[color:var(--primary)] hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3
                                        class="text-base font-semibold text-zinc-950 dark:text-zinc-50"
                                    >
                                        {{ row.patient_name }}
                                    </h3>

                                    <Badge
                                        class="rounded-full border px-3 py-1 text-xs"
                                        :class="painBadgeClass(row.pain_scale)"
                                    >
                                        Dolor:
                                        {{ row.pain_scale ?? '—' }}/10
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

                                    <div
                                        class="flex items-center gap-2 sm:col-span-2"
                                    >
                                        <FileText
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>
                                            Cita:
                                            {{
                                                row.appointment_id
                                                    ? `#${row.appointment_id}`
                                                    : 'Sin cita relacionada'
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl"
                                :style="primarySoftStyle"
                            >
                                <Activity
                                    class="h-5 w-5"
                                    :style="{ color: 'var(--primary)' }"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-4 rounded-2xl p-3 text-xs leading-5 text-zinc-600 transition-colors duration-300 dark:text-zinc-300"
                            :style="primarySoftStyle"
                        >
                            <p class="line-clamp-2">
                                <strong>Evaluación:</strong>
                                {{ row.assessment || 'Sin evaluación.' }}
                            </p>

                            <p class="mt-1 line-clamp-2">
                                <strong>Plan:</strong>
                                {{ row.plan || 'Sin plan registrado.' }}
                            </p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                @click="goToPatient(row.patient_persona_id)"
                            >
                                <FileText class="mr-2 h-4 w-4" />
                                Ver expediente
                            </Button>

                            <Button
                                v-if="can('sessions.update')"
                                variant="outline"
                                class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                @click="openEdit(row)"
                            >
                                <Pencil class="mr-2 h-4 w-4" />
                                Editar
                            </Button>

                            <Button
                                variant="outline"
                                class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                @click="goToExercises"
                            >
                                <Dumbbell class="mr-2 h-4 w-4" />
                                Ejercicios
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
                                                    ...props.lookups.appointments.map(
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
                                            <FileText
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Expediente del paciente
                                        </h3>
                                    </div>

                                    <div
                                        class="rounded-2xl p-4"
                                        :style="primarySoftStyle"
                                    >
                                        <p
                                            class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                        >
                                            Esta sesión alimenta el expediente
                                            clínico del paciente.
                                        </p>

                                        <p
                                            class="mt-2 text-xs leading-5 text-zinc-600 dark:text-zinc-300"
                                        >
                                            Después de guardar, podrás consultar
                                            el expediente del paciente y
                                            continuar con ejercicios o archivos.
                                        </p>

                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="mt-4 h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                            :disabled="!form.patient_persona_id"
                                            @click="
                                                goToPatient(
                                                    Number(
                                                        form.patient_persona_id,
                                                    ),
                                                )
                                            "
                                        >
                                            <FileText class="mr-2 h-4 w-4" />
                                            Ver expediente
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
                                            <Dumbbell
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Ejercicios
                                        </h3>
                                    </div>

                                    <div
                                        class="rounded-2xl p-4"
                                        :style="primarySoftStyle"
                                    >
                                        <p
                                            class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                        >
                                            Asignación de ejercicios
                                        </p>

                                        <p
                                            class="mt-2 text-xs leading-5 text-zinc-600 dark:text-zinc-300"
                                        >
                                            Para asignar ejercicios específicos,
                                            primero guarda la sesión y luego usa
                                            el módulo de ejercicios o la vista
                                            de expediente.
                                        </p>

                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="mt-4 h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                            @click="goToExercises"
                                        >
                                            <Dumbbell class="mr-2 h-4 w-4" />
                                            Ir a ejercicios
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
    </AppLayout>
</template>
