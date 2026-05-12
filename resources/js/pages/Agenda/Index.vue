<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    useCitaCrud,
    type CitaRow,
    type CitaStatus,
} from '@/composables/crud/useCitaCrud';
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
    AlertCircle,
    CalendarClock,
    CalendarPlus,
    CheckCircle2,
    Clock,
    Info,
    Pencil,
    Search,
    UserRound,
    UsersRound,
    X,
} from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DateTimePicker from '@/components/ui/DateTimePicker.vue';
import StatusFlow from '@/components/ui/StatusFlow.vue';
import FvPagination from '@/components/fv/FvPagination.vue';
import { formatDateTimeMx } from '@/lib/dates';
import { tAppointmentStatus } from '@/lib/labels';

const props = defineProps<{
    rows: CitaRow[];
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
        status?: string;
        per_page?: string | number;
    };
    lookups: {
        patients: { id: number; label: string }[];
        therapists: { id: number; label: string }[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Agenda', href: '/citas' }];

const {
    form,
    isOpen,
    editingStatus,
    isEditing,
    can,
    moduleEnabled,
    isTherapistRole,
    applyFilters,
    openCreate,
    openEdit,
    closeModal,
    submit,
    cancelCita,
    markNoShow,
    advanceStatus,
} = useCitaCrud(props.filters);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('new') === '1') {
        const patientId = params.get('patient_persona_id');
        openCreate(patientId ? { patient_persona_id: Number(patientId) } : undefined);
    }
});

const search = ref(props.filters.q ?? '');
const selectedStatus = ref<string | null>(props.filters.status ?? null);
let searchTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.q ?? '';
        selectedStatus.value = filters.status ?? null;
    },
    { deep: true },
);

watch(search, (value) => {
    if (searchTimer) clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        applyFilters({
            q: value.trim(),
            status: selectedStatus.value ?? '',
            page: 1,
            per_page: props.page.per_page_selected ?? props.page.per_page ?? 10,
        });
    }, 450);
});

const clearSearch = () => {
    search.value = '';
};

const applyStatus = (value: string | number | null) => {
    selectedStatus.value = value ? String(value) : null;

    applyFilters({
        q: search.value.trim(),
        status: selectedStatus.value ?? '',
        page: 1,
        per_page: props.page.per_page_selected ?? props.page.per_page ?? 10,
    });
};

const statusOptions = [
    { value: 'scheduled', label: tAppointmentStatus('scheduled') },
    { value: 'confirmed', label: tAppointmentStatus('confirmed') },
    { value: 'arrived', label: tAppointmentStatus('arrived') },
    { value: 'no_show', label: tAppointmentStatus('no_show') },
    { value: 'cancelled', label: tAppointmentStatus('cancelled') },
    { value: 'done', label: tAppointmentStatus('done') },
];

const statusFlowSteps = [
    { value: 'scheduled', label: 'Programada' },
    { value: 'confirmed', label: 'Confirmada' },
    { value: 'arrived', label: 'Llegó' },
    { value: 'done', label: 'Finalizada' },
];

const statusClass = (status: CitaStatus) =>
    ({
        scheduled:
            'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/40 dark:text-sky-300',
        confirmed:
            'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/40 dark:text-indigo-300',
        arrived:
            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300',
        no_show:
            'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-300',
        cancelled:
            'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300',
        done: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/40 dark:text-violet-300',
    })[status] ?? 'border-zinc-200 bg-zinc-100 text-zinc-700';

const nextStatusLabel = (status: CitaStatus) => {
    if (status === 'scheduled') return 'Confirmar cita';
    if (status === 'confirmed') return 'Marcar llegada';
    if (status === 'arrived') return 'Finalizar cita';

    return '';
};

const canAdvance = (status: CitaStatus) =>
    ['scheduled', 'confirmed', 'arrived'].includes(status);

const isClosedStatus = (status: CitaStatus) =>
    ['cancelled', 'done', 'no_show'].includes(status);

const currentStatusText = computed(() =>
    isEditing.value
        ? tAppointmentStatus(editingStatus.value)
        : tAppointmentStatus('scheduled'),
);

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
    <Head title="Agenda" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="w-full space-y-5">
            <div
                v-if="!moduleEnabled"
                class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
            >
                Módulo de agenda deshabilitado.
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
                                <CalendarClock class="h-6 w-6" />
                            </div>

                            <div>
                                <h1
                                    class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50"
                                >
                                    Agenda
                                </h1>

                                <p
                                    class="mt-1 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-400"
                                >
                                    Coordina citas de pacientes con sus
                                    terapeutas y controla el avance de cada cita
                                    sin retroceder estados.
                                </p>
                            </div>
                        </div>

                        <Button
                            v-if="can('appointments.create')"
                            class="h-11 rounded-2xl px-5 shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl"
                            :style="primaryButtonStyle"
                            @mouseenter="setPrimaryHover"
                            @mouseleave="setPrimaryNormal"
                            @click="() => openCreate()"
                        >
                            <CalendarPlus class="mr-2 h-4 w-4" />
                            Nueva cita
                        </Button>
                    </div>
                </div>

                <div
                    class="rounded-[1.75rem] border border-zinc-200 bg-zinc-50 p-4 shadow-sm transition-all duration-300 dark:border-zinc-800 dark:bg-zinc-900/40"
                >
                    <div class="grid gap-3 lg:grid-cols-[1fr_260px]">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-zinc-400"
                            />

                            <Input
                                v-model="search"
                                class="h-11 rounded-2xl border-zinc-200 bg-white pr-10 pl-9 shadow-sm transition-all duration-200 focus-visible:border-[color:var(--primary)] focus-visible:ring-2 focus-visible:ring-[color:var(--primary)]/20 dark:border-zinc-800 dark:bg-zinc-950"
                                placeholder="Buscar por paciente o terapeuta"
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

                        <SearchableSelect
                            :model-value="selectedStatus"
                            :options="[
                                { value: null, label: 'Todos los estados' },
                                ...statusOptions,
                            ]"
                            placeholder="Filtrar estado"
                            clearable
                            @update:model-value="applyStatus"
                        />
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
                        Sin citas para mostrar
                    </h3>

                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                        Crea una cita nueva o ajusta la búsqueda y filtros.
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
                                        :class="statusClass(row.status)"
                                    >
                                        {{ tAppointmentStatus(row.status) }}
                                    </Badge>
                                </div>

                                <div
                                    class="mt-3 grid gap-2 text-xs text-zinc-500 sm:grid-cols-2 dark:text-zinc-400"
                                >
                                    <div class="flex items-center gap-2">
                                        <UserRound
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span class="truncate">
                                            {{ row.patient_name }}
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
                                        <Clock class="h-3.5 w-3.5 shrink-0" />
                                        <span>
                                            {{ formatDateTimeMx(row.start_at) }}
                                            →
                                            {{ formatDateTimeMx(row.end_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl"
                                :style="primarySoftStyle"
                            >
                                <CalendarClock
                                    class="h-5 w-5"
                                    :style="{ color: 'var(--primary)' }"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-4 rounded-2xl p-3 text-xs leading-5 text-zinc-600 transition-colors duration-300 dark:text-zinc-300"
                            :style="primarySoftStyle"
                        >
                            <p>
                                <strong>Notas:</strong>
                                {{ row.notes || 'Sin notas registradas.' }}
                            </p>
                        </div>

                        <StatusFlow
                            class="mt-4"
                            :current="row.status"
                            :steps="statusFlowSteps"
                            :can-advance="canAdvance(row.status)"
                            :advance-label="nextStatusLabel(row.status)"
                            :actions="[
                                {
                                    key: 'no_show',
                                    label: 'Marcar no asistió',
                                    variant: 'outline',
                                    disabled: isClosedStatus(row.status),
                                },
                                {
                                    key: 'cancel',
                                    label: 'Cancelar cita',
                                    variant: 'destructive',
                                    disabled: ['cancelled', 'done'].includes(
                                        row.status,
                                    ),
                                },
                            ]"
                            @advance="advanceStatus(row)"
                            @action="
                                (key) =>
                                    key === 'cancel'
                                        ? cancelCita(row)
                                        : markNoShow(row)
                            "
                        />

                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button
                                v-if="can('appointments.update')"
                                variant="outline"
                                class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                @click="openEdit(row)"
                            >
                                <Pencil class="mr-2 h-4 w-4" />
                                Editar
                            </Button>
                        </div>
                    </article>
                </div>

                <FvPagination
                    :page="props.page"
                    item-label="citas"
                    :per-page-options="[10, 15, 20, 50, 'all']"
                    @change="
                        (page) =>
                            applyFilters({
                                q: search.trim(),
                                status: selectedStatus ?? '',
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
                                status: selectedStatus ?? '',
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
                                        <CalendarClock class="h-4 w-4" />
                                    </span>

                                    <span>
                                        {{
                                            isEditing
                                                ? 'Editar cita'
                                                : 'Nueva cita'
                                        }}
                                    </span>
                                </DialogTitle>

                                <DialogDescription
                                    class="mt-2 max-w-3xl text-sm leading-6 text-zinc-500 dark:text-zinc-400"
                                >
                                    {{
                                        isEditing
                                            ? 'Actualiza la información de la cita. El estado no se cambia desde este formulario para evitar retrocesos.'
                                            : 'Captura la cita. Al registrarla quedará como programada.'
                                    }}
                                </DialogDescription>
                            </div>

                            <div
                                class="rounded-2xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs leading-5 text-zinc-500 lg:w-72 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400"
                            >
                                <span
                                    class="font-medium text-zinc-700 dark:text-zinc-200"
                                >
                                    Estado:
                                </span>
                                {{ currentStatusText }}
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
                                            <Info
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Información de la cita
                                        </h3>

                                        <p
                                            class="text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                        >
                                            Selecciona paciente, terapeuta y
                                            horario. El fin debe ser posterior
                                            al inicio.
                                        </p>
                                    </div>

                                    <div
                                        class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                                    >
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
                                                <span
                                                    v-if="isTherapistRole && !isEditing"
                                                    class="ml-1 text-xs font-normal text-zinc-400"
                                                >
                                                    (auto-asignado)
                                                </span>
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
                                                Inicio
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>

                                            <DateTimePicker
                                                v-model="form.start_at"
                                            />

                                            <p
                                                v-if="form.errors.start_at"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.start_at }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Fin
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>

                                            <DateTimePicker
                                                v-model="form.end_at"
                                            />

                                            <p
                                                v-if="form.errors.end_at"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.end_at }}
                                            </p>
                                        </div>

                                        <div
                                            class="space-y-2 md:col-span-2 xl:col-span-3"
                                        >
                                            <Label :class="labelBase">
                                                Notas
                                                <span
                                                    class="text-xs font-normal text-zinc-400"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <textarea
                                                v-model="form.notes"
                                                class="min-h-28 w-full resize-y rounded-2xl border border-zinc-200 bg-white p-3 text-sm shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus:border-[color:var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/20 focus:outline-none dark:border-zinc-800 dark:bg-zinc-900/80"
                                                placeholder="Notas internas de la cita"
                                            />

                                            <p
                                                v-if="form.errors.notes"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.notes }}
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
                                            <CheckCircle2
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Estado de la cita
                                        </h3>
                                    </div>

                                    <div
                                        class="rounded-2xl p-4"
                                        :style="primarySoftStyle"
                                    >
                                        <Badge
                                            class="rounded-full border px-3 py-1 text-xs"
                                            :class="
                                                statusClass(
                                                    isEditing
                                                        ? editingStatus
                                                        : 'scheduled',
                                                )
                                            "
                                        >
                                            {{ currentStatusText }}
                                        </Badge>

                                        <p
                                            class="mt-3 text-xs leading-5 text-zinc-600 dark:text-zinc-300"
                                        >
                                            {{
                                                isEditing
                                                    ? 'Para avanzar el estado usa las acciones de la tarjeta de la cita. Así evitamos regresar una cita a un estado anterior por error.'
                                                    : 'Toda cita nueva inicia como Programada. Después podrás confirmarla, marcar llegada o finalizarla desde el listado.'
                                            }}
                                        </p>
                                    </div>
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
                                          ? 'Actualizar cita'
                                          : 'Crear cita'
                                }}
                            </Button>
                        </div>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
