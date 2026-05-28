<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
    ClipboardPlus,
    Clock,
    Info,
    Loader2,
    Pencil,
    Search,
    UserPlus,
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
            'border-border bg-muted text-muted-foreground',
        done: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/40 dark:text-violet-300',
    })[status] ?? 'border-border bg-muted text-muted-foreground';

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

// Quick patient creation
const localPatients = ref<{ id: number; label: string }[]>([...props.lookups.patients]);
const showQuickPatient = ref(false);
const quickSaving      = ref(false);
const quickForm = ref({ nombres: '', apellido_paterno: '', apellido_materno: '', telefono: '' });

const getCsrfMeta = (): string =>
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

const createQuickPatient = async () => {
    if (!quickForm.value.nombres || !quickForm.value.apellido_paterno) return;
    quickSaving.value = true;
    try {
        const res = await fetch('/pacientes/rapido', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfMeta(),
            },
            body: JSON.stringify(quickForm.value),
        });
        if (res.ok) {
            const { id, label } = await res.json();
            localPatients.value.unshift({ id, label });
            form.patient_persona_id = id;
            showQuickPatient.value = false;
            quickForm.value = { nombres: '', apellido_paterno: '', apellido_materno: '', telefono: '' };
        }
    } finally {
        quickSaving.value = false;
    }
};

const inputBase =
    'h-11 rounded-2xl border-input bg-card shadow-sm transition-all duration-200 placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary/20';

const labelBase = 'text-sm font-medium text-foreground';

const sectionTitleBase =
    'flex items-center gap-2 text-sm font-semibold text-foreground';

const primaryButtonStyle = {
    backgroundColor: 'var(--primary)',
    color: 'var(--primary-foreground)',
};

const primarySoftStyle = {
    backgroundColor: 'color-mix(in srgb, var(--primary) 10%, var(--card))',
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

const atenderCita = (row: CitaRow) => {
    router.visit(`/sesiones?new=1&patient_persona_id=${row.patient_persona_id}&appointment_id=${row.id}`);
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
                <div class="fv-gradient-panel">
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
                                    class="text-2xl font-semibold tracking-tight text-foreground"
                                >
                                    Agenda
                                </h1>

                                <p
                                    class="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground"
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

                <div class="fv-toolbar transition-all duration-300">
                    <div class="grid gap-3 lg:grid-cols-[1fr_260px]">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-muted-foreground"
                            />

                            <Input
                                v-model="search"
                                class="h-11 rounded-2xl border-border bg-card pr-10 pl-9 shadow-sm transition-all duration-200 focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary/20"
                                placeholder="Buscar por paciente o terapeuta"
                            />

                            <button
                                v-if="search"
                                type="button"
                                class="absolute top-3 right-3 grid h-5 w-5 place-items-center rounded-full text-muted-foreground transition hover:bg-muted hover:text-foreground"
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
                    class="fv-empty-state transition-all duration-300"
                >
                    <div
                        class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-card shadow-sm"
                    >
                        <AlertCircle class="h-6 w-6 text-muted-foreground" />
                    </div>

                    <h3
                        class="mt-4 text-lg font-semibold text-foreground"
                    >
                        Sin citas para mostrar
                    </h3>

                    <p class="mt-2 text-sm text-muted-foreground">
                        Crea una cita nueva o ajusta la búsqueda y filtros.
                    </p>
                </div>

                <div v-else class="grid gap-4 xl:grid-cols-2">
                    <article
                        v-for="row in props.rows"
                        :key="row.id"
                        class="fv-card-premium p-4 transition-all duration-300 hover:-translate-y-1 hover:border-[color:var(--primary)] hover:shadow-xl"
                    >
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3
                                        class="text-base font-semibold text-foreground"
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
                                    class="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-2"
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
                            class="mt-4 rounded-2xl p-3 text-xs leading-5 text-muted-foreground transition-colors duration-300"
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
                            <!-- Botón Atender — visible cuando llegó (arrived) y hay permiso -->
                            <Button
                                v-if="row.status === 'arrived' && can('sessions.create')"
                                class="h-10 rounded-xl px-4 text-sm font-medium shadow-sm"
                                :style="primaryButtonStyle"
                                @mouseenter="setPrimaryHover"
                                @mouseleave="setPrimaryNormal"
                                @click="() => atenderCita(row)"
                            >
                                <ClipboardPlus class="mr-2 h-4 w-4" />
                                Atender paciente
                            </Button>
                            <Button
                                v-if="can('appointments.update')"
                                variant="outline"
                                class="h-10 rounded-xl border-border transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
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
                class="flex max-h-[94dvh] w-[calc(100vw-1rem)] max-w-none !gap-0 overflow-hidden rounded-[1.75rem] border border-border bg-card !p-0 shadow-2xl sm:w-[calc(100vw-2rem)] sm:!max-w-[calc(100vw-2rem)] md:!max-w-[92vw] lg:!max-w-[1080px] xl:!max-w-[1220px] 2xl:!max-w-[1320px]"
            >
                <div class="flex max-h-[94dvh] min-h-0 w-full flex-col">
                    <DialogHeader
                        class="shrink-0 border-b border-border bg-card px-4 py-4 sm:px-6 lg:px-7"
                    >
                        <div
                            class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                        >
                            <div class="min-w-0">
                                <DialogTitle
                                    class="flex items-center gap-2 text-lg font-semibold text-foreground sm:text-xl"
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
                                    class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground"
                                >
                                    {{
                                        isEditing
                                            ? 'Actualiza la información de la cita. El estado no se cambia desde este formulario para evitar retrocesos.'
                                            : 'Captura la cita. Al registrarla quedará como programada.'
                                    }}
                                </DialogDescription>
                            </div>

                            <div
                                class="rounded-2xl border border-border bg-muted/50 px-3 py-2 text-xs leading-5 text-muted-foreground lg:w-72"
                            >
                                <span
                                    class="font-medium text-foreground"
                                >
                                    Estado:
                                </span>
                                {{ currentStatusText }}
                            </div>
                        </div>
                    </DialogHeader>

                    <div
                        class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-muted/30 px-4 py-4 sm:px-6 sm:py-5 lg:px-7"
                    >
                        <div
                            class="grid gap-4 pb-5 lg:grid-cols-[minmax(0,1fr)_minmax(360px,0.65fr)] xl:grid-cols-[minmax(0,1.2fr)_minmax(420px,0.8fr)]"
                        >
                            <div class="space-y-4">
                                <section
                                    class="rounded-[1.5rem] border border-border bg-card p-4 shadow-sm sm:p-5"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-border pb-3"
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
                                            class="text-xs leading-5 text-muted-foreground"
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
                                            <div class="flex items-center justify-between">
                                                <Label :class="labelBase">
                                                    Paciente
                                                    <span class="text-red-500">*</span>
                                                </Label>
                                                <button
                                                    v-if="can('patients.create') && !isEditing"
                                                    type="button"
                                                    class="flex items-center gap-1 rounded-lg px-2 py-0.5 text-[11px] font-medium transition-colors hover:bg-muted"
                                                    :style="{ color: 'var(--primary)' }"
                                                    @click="showQuickPatient = !showQuickPatient"
                                                >
                                                    <UserPlus class="h-3 w-3" />
                                                    Nuevo paciente
                                                </button>
                                            </div>

                                            <!-- Mini-form paciente rápido -->
                                            <div v-if="showQuickPatient && !isEditing" class="rounded-2xl border border-border bg-muted/30 p-3 space-y-2">
                                                <p class="text-xs font-semibold text-foreground">Registrar paciente rápido</p>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="text-[11px] text-muted-foreground">Nombre(s)*</label>
                                                        <input v-model="quickForm.nombres" type="text" class="mt-0.5 w-full rounded-xl border border-border bg-background px-2.5 py-1.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Sofía" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[11px] text-muted-foreground">Apellido paterno*</label>
                                                        <input v-model="quickForm.apellido_paterno" type="text" class="mt-0.5 w-full rounded-xl border border-border bg-background px-2.5 py-1.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Ramírez" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[11px] text-muted-foreground">Apellido materno</label>
                                                        <input v-model="quickForm.apellido_materno" type="text" class="mt-0.5 w-full rounded-xl border border-border bg-background px-2.5 py-1.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Lozano" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[11px] text-muted-foreground">Teléfono</label>
                                                        <input v-model="quickForm.telefono" type="text" class="mt-0.5 w-full rounded-xl border border-border bg-background px-2.5 py-1.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="5512345678" />
                                                    </div>
                                                </div>
                                                <div class="flex gap-2 pt-1">
                                                    <button
                                                        type="button"
                                                        :disabled="quickSaving || !quickForm.nombres || !quickForm.apellido_paterno"
                                                        class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-medium disabled:opacity-50 transition-opacity"
                                                        :style="{ backgroundColor: 'var(--primary)', color: 'var(--primary-foreground)' }"
                                                        @click="createQuickPatient"
                                                    >
                                                        <Loader2 v-if="quickSaving" class="h-3 w-3 animate-spin" />
                                                        <UserPlus v-else class="h-3 w-3" />
                                                        {{ quickSaving ? 'Guardando...' : 'Crear y seleccionar' }}
                                                    </button>
                                                    <button type="button" class="rounded-xl px-3 py-1.5 text-xs text-muted-foreground hover:text-foreground" @click="showQuickPatient = false">Cancelar</button>
                                                </div>
                                            </div>

                                            <SearchableSelect
                                                v-model="form.patient_persona_id"
                                                :options="[
                                                    { value: '', label: 'Seleccionar paciente' },
                                                    ...localPatients.map((patient) => ({
                                                        value: patient.id,
                                                        label: patient.label,
                                                    })),
                                                ]"
                                            />

                                            <p
                                                v-if="form.errors.patient_persona_id"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.patient_persona_id }}
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
                                                    class="ml-1 text-xs font-normal text-muted-foreground"
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
                                                    class="text-xs font-normal text-muted-foreground"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <textarea
                                                v-model="form.notes"
                                                class="min-h-28 w-full resize-y rounded-2xl border border-border bg-card p-3 text-sm shadow-sm transition-all duration-200 placeholder:text-muted-foreground focus:border-[color:var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/20 focus:outline-none"
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
                                    class="rounded-[1.5rem] border border-border bg-card p-4 shadow-sm sm:p-5"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-border pb-3"
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
                                            class="mt-3 text-xs leading-5 text-muted-foreground"
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
                        class="shrink-0 border-t border-border bg-card px-4 py-3 sm:px-6 lg:px-7"
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
