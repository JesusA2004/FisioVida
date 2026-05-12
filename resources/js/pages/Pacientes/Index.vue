<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    usePacienteCrud,
    type PacienteRow,
} from '@/composables/crud/usePacienteCrud';
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
    UserPlus,
    Search,
    Pencil,
    Trash2,
    Phone,
    Mail,
    ShieldAlert,
    UserRound,
    CalendarDays,
    MapPin,
    AlertCircle,
    Loader2,
    X,
    Info,
    HelpCircle,
    ContactRound,
    NotebookText,
    CheckCircle2,
} from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import { formatDateMx } from '@/lib/dates';
import { tGeneralStatus } from '@/lib/labels';
import FvPagination from '@/components/fv/FvPagination.vue';

const props = defineProps<{
    rows: PacienteRow[];
    page: {
        current_page: number;
        last_page: number;
        per_page: number | string;
        per_page_selected?: number | string;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { q?: string; status?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pacientes', href: '/pacientes' },
];

const {
    form,
    isOpen,
    editingId,
    isSubmitting,
    deletingId,
    activatingId,
    can,
    moduleEnabled,
    applyFilters,
    openCreate,
    openEdit,
    closeModal,
    submit,
    destroyPaciente,
    activatePaciente,
    onlyDigits,
} = usePacienteCrud(props.filters);

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

const applyStatus = (value: string | number | null) => {
    selectedStatus.value = value ? String(value) : null;

    applyFilters({
        q: search.value.trim(),
        status: selectedStatus.value ?? '',
        page: 1,
        per_page: props.page.per_page_selected ?? props.page.per_page ?? 10,
    });
};

const clearSearch = () => {
    search.value = '';
};

const statusClass = (status: PacienteRow['status']) =>
    status === 'active'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300'
        : 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300';

const sexLabel = (sex?: PacienteRow['sexo']) =>
    sex === 'M'
        ? 'Masculino'
        : sex === 'F'
          ? 'Femenino'
          : sex === 'X'
            ? 'No especificado'
            : 'No especificado';

const isEditing = computed(() => editingId.value !== null);

const birthDateMinYear = new Date().getFullYear() - 120;
const birthDateMaxYear = new Date().getFullYear();

const normalizePhone = (field: 'telefono' | 'contacto_emergencia_telefono') => {
    form[field] = onlyDigits(form[field] ?? '');
};

const whatsappUrl = (phone?: string | null) => {
    const digits = onlyDigits(phone ?? '');

    if (digits.length !== 10) return null;

    return `https://wa.me/52${digits}`;
};

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
    <Head title="Pacientes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="w-full space-y-5">
            <div
                v-if="!moduleEnabled"
                class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
            >
                Módulo de pacientes deshabilitado.
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
                                <UserRound class="h-6 w-6" />
                            </div>

                            <div>
                                <h1
                                    class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50"
                                >
                                    Pacientes
                                </h1>

                                <p
                                    class="mt-1 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-400"
                                >
                                    Gestiona expedientes, datos de contacto,
                                    estado del paciente y contactos de
                                    emergencia.
                                </p>
                            </div>
                        </div>

                        <Button
                            v-if="can('patients.create')"
                            class="h-11 rounded-2xl px-5 shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl"
                            :style="primaryButtonStyle"
                            @mouseenter="setPrimaryHover"
                            @mouseleave="setPrimaryNormal"
                            @click="openCreate"
                        >
                            <UserPlus class="mr-2 h-4 w-4" />
                            Nuevo paciente
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
                                placeholder="Buscar por nombre, teléfono o correo"
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
                                {
                                    value: 'active',
                                    label: tGeneralStatus('active'),
                                },
                                {
                                    value: 'inactive',
                                    label: tGeneralStatus('inactive'),
                                },
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
                        Sin pacientes para mostrar
                    </h3>

                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                        Agrega tu primer paciente o ajusta la búsqueda y
                        filtros.
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
                                        class="truncate text-base font-semibold text-zinc-950 dark:text-zinc-50"
                                    >
                                        {{
                                            row.full_name ||
                                            'Paciente sin nombre'
                                        }}
                                    </h3>

                                    <Badge
                                        class="rounded-full border px-3 py-1 text-xs"
                                        :class="statusClass(row.status)"
                                    >
                                        {{ tGeneralStatus(row.status) }}
                                    </Badge>
                                </div>

                                <div
                                    class="mt-3 grid gap-2 text-xs text-zinc-500 sm:grid-cols-2 dark:text-zinc-400"
                                >
                                    <div class="flex items-center gap-2">
                                        <Phone class="h-3.5 w-3.5 shrink-0" />
                                        <span>{{
                                            row.telefono || 'Sin teléfono'
                                        }}</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Mail class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">
                                            {{ row.email || 'Sin correo' }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <CalendarDays
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>
                                            {{
                                                row.fecha_nacimiento
                                                    ? formatDateMx(
                                                          row.fecha_nacimiento,
                                                      )
                                                    : 'Sin fecha de nacimiento'
                                            }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <UserRound
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>{{ sexLabel(row.sexo) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-4 rounded-2xl p-3 text-xs leading-5 text-zinc-600 transition-colors duration-300 dark:text-zinc-300"
                            :style="primarySoftStyle"
                        >
                            <p class="flex gap-2">
                                <ShieldAlert
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-zinc-400"
                                />
                                <span>
                                    <strong>Emergencia:</strong>
                                    {{ row.contacto_emergencia_nombre || '—' }}
                                    ·
                                    {{
                                        row.contacto_emergencia_telefono || '—'
                                    }}
                                </span>
                            </p>

                            <p class="mt-1 flex gap-2">
                                <MapPin
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-zinc-400"
                                />
                                <span class="line-clamp-1">
                                    <strong>Dirección:</strong>
                                    {{ row.direccion || 'Sin dirección' }}
                                </span>
                            </p>

                            <p class="mt-1 line-clamp-2">
                                <strong>Notas:</strong>
                                {{ row.notas || 'Sin notas' }}
                            </p>
                        </div>

                        <div
                            class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    as-child
                                    variant="outline"
                                    class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                >
                                    <Link :href="`/pacientes/${row.id}`">
                                        Ver expediente
                                    </Link>
                                </Button>

                                <Button
                                    v-if="can('patients.update')"
                                    variant="outline"
                                    class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                    @click="openEdit(row)"
                                >
                                    <Pencil class="mr-2 h-4 w-4" />
                                    Editar
                                </Button>

                                <Button
                                    v-if="row.status === 'active' && can('patients.delete')"
                                    variant="destructive"
                                    class="h-10 rounded-xl transition-all duration-200 hover:-translate-y-0.5"
                                    :disabled="deletingId === row.id"
                                    @click="destroyPaciente(row)"
                                >
                                    <Loader2
                                        v-if="deletingId === row.id"
                                        class="mr-2 h-4 w-4 animate-spin"
                                    />
                                    <Trash2 v-else class="mr-2 h-4 w-4" />
                                    {{
                                        deletingId === row.id
                                            ? 'Desactivando...'
                                            : 'Desactivar'
                                    }}
                                </Button>

                                <Button
                                    v-if="row.status === 'inactive' && can('patients.update')"
                                    variant="outline"
                                    class="h-10 rounded-xl border-emerald-200 bg-emerald-50 text-emerald-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-950"
                                    :disabled="activatingId === row.id"
                                    @click="activatePaciente(row)"
                                >
                                    <Loader2
                                        v-if="activatingId === row.id"
                                        class="mr-2 h-4 w-4 animate-spin"
                                    />
                                    <CheckCircle2 v-else class="mr-2 h-4 w-4" />
                                    {{
                                        activatingId === row.id
                                            ? 'Activando...'
                                            : 'Activar'
                                    }}
                                </Button>
                            </div>

                            <a
                                v-if="whatsappUrl(row.telefono)"
                                :href="whatsappUrl(row.telefono)!"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="group/wa relative ml-auto flex h-11 w-11 min-w-11 items-center justify-start overflow-hidden rounded-full bg-[#25D366] shadow-sm transition-all duration-300 hover:w-[225px] hover:shadow-md focus:w-[225px] focus:outline-none focus:ring-2 focus:ring-[#25D366]/30 sm:ml-0"
                                aria-label="Contactar por WhatsApp"
                                title="Contactar por WhatsApp"
                            >
                                <span
                                    aria-hidden="true"
                                    class="absolute top-0 left-0 z-10 block h-11 w-11 min-w-11 bg-[url('/icons/whatsapp.svg')] bg-center bg-no-repeat [background-size:26px_26px]"
                                />

                                <span
                                    class="ml-11 max-w-0 overflow-hidden whitespace-nowrap pr-3 text-sm font-medium text-white opacity-0 transition-all duration-300 group-hover/wa:max-w-[170px] group-hover/wa:opacity-100 group-focus/wa:max-w-[170px] group-focus/wa:opacity-100"
                                >
                                    Contactar por WhatsApp
                                </span>
                            </a>
                        </div>
                    </article>
                </div>

                <FvPagination
                    :page="props.page"
                    item-label="pacientes"
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
                class="flex max-h-[94dvh] w-[calc(100vw-1rem)] max-w-none !gap-0 overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white !p-0 shadow-2xl sm:w-[calc(100vw-2rem)] sm:!max-w-[calc(100vw-2rem)] md:!max-w-[94vw] lg:!max-w-[1120px] xl:!max-w-[1280px] 2xl:!max-w-[1380px] dark:border-zinc-800 dark:bg-zinc-950"
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
                                        <UserRound class="h-4 w-4" />
                                    </span>

                                    <span>
                                        {{
                                            isEditing
                                                ? 'Editar paciente'
                                                : 'Nuevo paciente'
                                        }}
                                    </span>
                                </DialogTitle>

                                <DialogDescription
                                    class="mt-2 max-w-3xl text-sm leading-6 text-zinc-500 dark:text-zinc-400"
                                >
                                    Registra los datos de identificación y
                                    contacto del paciente. Los campos marcados
                                    con * son obligatorios.
                                </DialogDescription>
                            </div>
                        </div>
                    </DialogHeader>

                    <div
                        class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-zinc-50/70 px-4 py-4 sm:px-6 sm:py-5 lg:px-7 dark:bg-zinc-950"
                    >
                        <div
                            class="grid gap-4 pb-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(360px,0.65fr)] xl:grid-cols-[minmax(0,1.45fr)_minmax(420px,0.55fr)]"
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
                                            Información principal
                                        </h3>

                                        <p
                                            class="text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                        >
                                            Datos básicos para identificar al
                                            paciente dentro del sistema.
                                        </p>
                                    </div>

                                    <div
                                        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                                    >
                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Nombres
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>

                                            <Input
                                                v-model="form.nombres"
                                                :class="inputBase"
                                                placeholder="Ej. Andrea"
                                                autocomplete="given-name"
                                            />

                                            <p
                                                v-if="form.errors.nombres"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.nombres }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Apellido paterno
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>

                                            <Input
                                                v-model="form.apellido_paterno"
                                                :class="inputBase"
                                                placeholder="Ej. Fuentes"
                                                autocomplete="family-name"
                                            />

                                            <p
                                                v-if="
                                                    form.errors.apellido_paterno
                                                "
                                                class="text-xs text-red-500"
                                            >
                                                {{
                                                    form.errors.apellido_paterno
                                                }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Apellido materno
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>

                                            <Input
                                                v-model="form.apellido_materno"
                                                :class="inputBase"
                                                placeholder="Ej. Peña"
                                                autocomplete="family-name"
                                            />

                                            <p
                                                v-if="
                                                    form.errors.apellido_materno
                                                "
                                                class="text-xs text-red-500"
                                            >
                                                {{
                                                    form.errors.apellido_materno
                                                }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <Label :class="labelBase">
                                                    Estado del expediente
                                                </Label>

                                                <div
                                                    class="group relative inline-flex"
                                                >
                                                    <button
                                                        type="button"
                                                        class="grid h-5 w-5 place-items-center rounded-full text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 focus:bg-zinc-100 focus:text-zinc-700 focus:outline-none dark:hover:bg-zinc-800 dark:hover:text-zinc-200 dark:focus:bg-zinc-800 dark:focus:text-zinc-200"
                                                        aria-label="Información sobre el estado del expediente"
                                                    >
                                                        <HelpCircle
                                                            class="h-4 w-4"
                                                        />
                                                    </button>

                                                    <div
                                                        class="pointer-events-none absolute top-7 left-0 z-50 hidden w-72 rounded-2xl border border-zinc-200 bg-white p-3 text-xs leading-5 text-zinc-600 shadow-xl group-hover:block group-focus-within:block dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300"
                                                    >
                                                        Indica si el expediente
                                                        del paciente está activo
                                                        para atención y
                                                        seguimiento, o inactivo
                                                        cuando ya no se
                                                        encuentra en tratamiento.
                                                        No elimina su historial.
                                                    </div>
                                                </div>
                                            </div>

                                            <SearchableSelect
                                                v-model="form.status"
                                                :options="[
                                                    {
                                                        value: 'active',
                                                        label: tGeneralStatus(
                                                            'active',
                                                        ),
                                                    },
                                                    {
                                                        value: 'inactive',
                                                        label: tGeneralStatus(
                                                            'inactive',
                                                        ),
                                                    },
                                                ]"
                                            />

                                            <p
                                                v-if="form.errors.status"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.status }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Fecha nacimiento
                                                <span
                                                    class="text-xs font-normal text-zinc-400"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <DatePicker
                                                v-model="form.fecha_nacimiento"
                                                :min-year="birthDateMinYear"
                                                :max-year="birthDateMaxYear"
                                            />

                                            <p
                                                v-if="
                                                    form.errors.fecha_nacimiento
                                                "
                                                class="text-xs text-red-500"
                                            >
                                                {{
                                                    form.errors.fecha_nacimiento
                                                }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Sexo
                                                <span
                                                    class="text-xs font-normal text-zinc-400"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <SearchableSelect
                                                v-model="form.sexo"
                                                :options="[
                                                    {
                                                        value: '',
                                                        label: 'No especificado',
                                                    },
                                                    {
                                                        value: 'M',
                                                        label: 'Masculino',
                                                    },
                                                    {
                                                        value: 'F',
                                                        label: 'Femenino',
                                                    },
                                                    {
                                                        value: 'X',
                                                        label: 'No especificado / Otro',
                                                    },
                                                ]"
                                            />

                                            <p
                                                v-if="form.errors.sexo"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.sexo }}
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
                                            <ContactRound
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Contacto y ubicación
                                        </h3>

                                        <p
                                            class="text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                        >
                                            Información para comunicación y
                                            localización del paciente.
                                        </p>
                                    </div>

                                    <div
                                        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                                    >
                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Teléfono
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                                <span
                                                    class="text-xs font-normal text-zinc-400"
                                                >
                                                    10 dígitos
                                                </span>
                                            </Label>

                                            <Input
                                                v-model="form.telefono"
                                                :class="inputBase"
                                                inputmode="numeric"
                                                maxlength="10"
                                                placeholder="Ej. 7771234567"
                                                autocomplete="tel"
                                                @input="
                                                    normalizePhone('telefono')
                                                "
                                            />

                                            <p
                                                v-if="form.errors.telefono"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.telefono }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Email
                                                <span
                                                    class="text-xs font-normal text-zinc-400"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <Input
                                                v-model="form.email"
                                                :class="inputBase"
                                                type="email"
                                                placeholder="Ej. paciente@correo.com"
                                                autocomplete="email"
                                            />

                                            <p
                                                v-if="form.errors.email"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.email }}
                                            </p>
                                        </div>

                                        <div
                                            class="space-y-2 sm:col-span-2 xl:col-span-1"
                                        >
                                            <Label :class="labelBase">
                                                Dirección
                                                <span
                                                    class="text-xs font-normal text-zinc-400"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <Input
                                                v-model="form.direccion"
                                                :class="inputBase"
                                                placeholder="Calle, número, colonia, ciudad"
                                                autocomplete="street-address"
                                            />

                                            <p
                                                v-if="form.errors.direccion"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.direccion }}
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
                                            <ShieldAlert
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Contacto de emergencia
                                        </h3>
                                    </div>

                                    <div class="grid gap-4">
                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Nombre del contacto
                                                <span
                                                    class="text-xs font-normal text-zinc-400"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <Input
                                                v-model="
                                                    form.contacto_emergencia_nombre
                                                "
                                                :class="inputBase"
                                                placeholder="Ej. Familiar o responsable"
                                            />

                                            <p
                                                v-if="
                                                    form.errors
                                                        .contacto_emergencia_nombre
                                                "
                                                class="text-xs text-red-500"
                                            >
                                                {{
                                                    form.errors
                                                        .contacto_emergencia_nombre
                                                }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Teléfono de emergencia
                                                <span
                                                    class="text-xs font-normal text-zinc-400"
                                                >
                                                    10 dígitos
                                                </span>
                                            </Label>

                                            <Input
                                                v-model="
                                                    form.contacto_emergencia_telefono
                                                "
                                                :class="inputBase"
                                                inputmode="numeric"
                                                maxlength="10"
                                                placeholder="Ej. 7777654321"
                                                @input="
                                                    normalizePhone(
                                                        'contacto_emergencia_telefono',
                                                    )
                                                "
                                            />

                                            <p
                                                v-if="
                                                    form.errors
                                                        .contacto_emergencia_telefono
                                                "
                                                class="text-xs text-red-500"
                                            >
                                                {{
                                                    form.errors
                                                        .contacto_emergencia_telefono
                                                }}
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
                                            <NotebookText
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Notas adicionales
                                        </h3>
                                    </div>

                                    <div class="space-y-2">
                                        <textarea
                                            v-model="form.notas"
                                            class="min-h-44 w-full resize-y rounded-2xl border border-zinc-200 bg-white p-3 text-sm shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus:border-[color:var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/20 focus:outline-none lg:min-h-34 dark:border-zinc-800 dark:bg-zinc-900/80"
                                            placeholder="Observaciones importantes del paciente"
                                        />

                                        <p
                                            v-if="form.errors.notas"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.notas }}
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
                                class="h-11 rounded-2xl"
                                :disabled="isSubmitting"
                                @click="closeModal(false)"
                            >
                                Cancelar
                            </Button>

                            <Button
                                class="h-11 rounded-2xl px-6 shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl"
                                :style="primaryButtonStyle"
                                :disabled="isSubmitting"
                                @mouseenter="setPrimaryHover"
                                @mouseleave="setPrimaryNormal"
                                @click="submit"
                            >
                                <Loader2
                                    v-if="isSubmitting"
                                    class="mr-2 h-4 w-4 animate-spin"
                                />

                                {{
                                    isSubmitting
                                        ? 'Guardando...'
                                        : isEditing
                                          ? 'Guardar cambios'
                                          : 'Crear paciente'
                                }}
                            </Button>
                        </div>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>