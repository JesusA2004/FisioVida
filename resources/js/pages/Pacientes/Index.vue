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
    ChevronLeft,
    ChevronRight,
    UserRound,
    CalendarDays,
    MapPin,
    AlertCircle,
    Loader2,
    X,
} from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import { formatDateMx } from '@/lib/dates';
import { tGeneralStatus } from '@/lib/labels';

const props = defineProps<{
    rows: PacienteRow[];
    page: { current_page: number; last_page: number; total: number };
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
    can,
    moduleEnabled,
    applyFilters,
    openCreate,
    openEdit,
    closeModal,
    submit,
    destroyPaciente,
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
        });
    }, 450);
});

const applyStatus = (value: string | number | null) => {
    selectedStatus.value = value ? String(value) : null;

    applyFilters({
        q: search.value.trim(),
        status: selectedStatus.value ?? '',
        page: 1,
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

const currentFrom = computed(() => {
    if (props.page.total === 0) return 0;

    return (props.page.current_page - 1) * 10 + 1;
});

const currentTo = computed(() =>
    Math.min(props.page.current_page * 10, props.page.total),
);

const visiblePages = computed(() => {
    const current = props.page.current_page;
    const last = props.page.last_page;
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
});

const changePage = (page: number) => {
    if (page < 1 || page > props.page.last_page || page === props.page.current_page) {
        return;
    }

    applyFilters({
        q: search.value.trim(),
        status: selectedStatus.value ?? '',
        page,
    });
};

const normalizePhone = (field: 'telefono' | 'contacto_emergencia_telefono') => {
    form[field] = onlyDigits(form[field] ?? '');
};

const inputBase =
    'h-11 rounded-2xl border-zinc-200 bg-white/90 shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus-visible:ring-2 focus-visible:ring-cyan-400/40 dark:border-zinc-800 dark:bg-zinc-900/80';

const labelBase = 'text-sm font-medium text-zinc-800 dark:text-zinc-100';
</script>

<template>
    <Head title="Pacientes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-[2rem] bg-white p-4 shadow-xl ring-1 ring-zinc-100 transition-all duration-300 dark:bg-zinc-950 dark:ring-zinc-900 sm:p-6"
        >
            <div
                v-if="!moduleEnabled"
                class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
            >
                Módulo de pacientes deshabilitado.
            </div>

            <template v-else>
                <div
                    class="relative overflow-hidden rounded-[2rem] border border-cyan-100 bg-gradient-to-br from-cyan-50 via-white to-white p-5 shadow-sm dark:border-cyan-900/30 dark:from-cyan-950/30 dark:via-zinc-950 dark:to-zinc-950"
                >
                    <div
                        class="pointer-events-none absolute -right-20 -top-20 h-52 w-52 rounded-full bg-cyan-300/20 blur-3xl"
                    />

                    <div
                        class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-cyan-600 text-white shadow-lg shadow-cyan-600/20"
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
                                    estado del paciente y contactos de emergencia.
                                </p>
                            </div>
                        </div>

                        <Button
                            v-if="can('patients.create')"
                            class="h-11 rounded-2xl px-5 shadow-lg shadow-cyan-600/20 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl"
                            @click="openCreate"
                        >
                            <UserPlus class="mr-2 h-4 w-4" />
                            Nuevo paciente
                        </Button>
                    </div>
                </div>

                <div
                    class="rounded-[1.75rem] border border-zinc-200 bg-zinc-50/70 p-4 shadow-sm transition-all duration-300 dark:border-zinc-800 dark:bg-zinc-900/40"
                >
                    <div class="grid gap-3 lg:grid-cols-[1fr_260px]">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-zinc-400"
                            />

                            <Input
                                v-model="search"
                                class="h-11 rounded-2xl border-zinc-200 bg-white pl-9 pr-10 shadow-sm transition-all duration-200 focus-visible:ring-2 focus-visible:ring-cyan-400/40 dark:border-zinc-800 dark:bg-zinc-950"
                                placeholder="Buscar en tiempo real por nombre, teléfono o correo"
                            />

                            <button
                                v-if="search"
                                type="button"
                                class="absolute right-3 top-3 grid h-5 w-5 place-items-center rounded-full text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
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
                        Agrega tu primer paciente o ajusta la búsqueda y filtros.
                    </p>
                </div>

                <div v-else class="grid gap-4 xl:grid-cols-2">
                    <article
                        v-for="row in props.rows"
                        :key="row.id"
                        class="group rounded-[1.75rem] border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-cyan-200 hover:shadow-xl hover:shadow-cyan-950/5 dark:border-zinc-800 dark:bg-zinc-900/60 dark:hover:border-cyan-900/50"
                    >
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3
                                        class="truncate text-base font-semibold text-zinc-950 dark:text-zinc-50"
                                    >
                                        {{ row.full_name || 'Paciente sin nombre' }}
                                    </h3>

                                    <Badge
                                        class="rounded-full border px-3 py-1 text-xs"
                                        :class="statusClass(row.status)"
                                    >
                                        {{ tGeneralStatus(row.status) }}
                                    </Badge>
                                </div>

                                <div class="mt-3 grid gap-2 text-xs text-zinc-500 dark:text-zinc-400 sm:grid-cols-2">
                                    <div class="flex items-center gap-2">
                                        <Phone class="h-3.5 w-3.5 shrink-0" />
                                        <span>{{ row.telefono || 'Sin teléfono' }}</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Mail class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">
                                            {{ row.email || 'Sin correo' }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <CalendarDays class="h-3.5 w-3.5 shrink-0" />
                                        <span>
                                            {{
                                                row.fecha_nacimiento
                                                    ? formatDateMx(row.fecha_nacimiento)
                                                    : 'Sin fecha de nacimiento'
                                            }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <UserRound class="h-3.5 w-3.5 shrink-0" />
                                        <span>{{ sexLabel(row.sexo) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-4 rounded-2xl bg-zinc-50 p-3 text-xs leading-5 text-zinc-600 transition-colors duration-300 group-hover:bg-cyan-50/60 dark:bg-zinc-950/60 dark:text-zinc-300 dark:group-hover:bg-cyan-950/20"
                        >
                            <p class="flex gap-2">
                                <ShieldAlert class="mt-0.5 h-3.5 w-3.5 shrink-0 text-zinc-400" />
                                <span>
                                    <strong>Emergencia:</strong>
                                    {{ row.contacto_emergencia_nombre || '—' }}
                                    ·
                                    {{ row.contacto_emergencia_telefono || '—' }}
                                </span>
                            </p>

                            <p class="mt-1 flex gap-2">
                                <MapPin class="mt-0.5 h-3.5 w-3.5 shrink-0 text-zinc-400" />
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

                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button
                                as-child
                                variant="outline"
                                class="h-10 rounded-xl transition-all duration-200 hover:-translate-y-0.5"
                            >
                                <Link :href="`/pacientes/${row.id}`">
                                    Ver expediente
                                </Link>
                            </Button>

                            <Button
                                v-if="can('patients.update')"
                                variant="outline"
                                class="h-10 rounded-xl transition-all duration-200 hover:-translate-y-0.5"
                                @click="openEdit(row)"
                            >
                                <Pencil class="mr-2 h-4 w-4" />
                                Editar
                            </Button>

                            <Button
                                v-if="can('patients.delete')"
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
                                {{ deletingId === row.id ? 'Eliminando...' : 'Eliminar' }}
                            </Button>
                        </div>
                    </article>
                </div>

                <div
                    class="flex flex-col gap-3 rounded-[1.5rem] border border-zinc-200 bg-zinc-50 px-4 py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/40 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Mostrando
                        <strong class="text-zinc-800 dark:text-zinc-200">
                            {{ currentFrom }}-{{ currentTo }}
                        </strong>
                        de
                        <strong class="text-zinc-800 dark:text-zinc-200">
                            {{ props.page.total }}
                        </strong>
                        pacientes
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            class="h-9 rounded-xl px-3"
                            :disabled="props.page.current_page <= 1"
                            @click="changePage(props.page.current_page - 1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>

                        <Button
                            v-for="pageNumber in visiblePages"
                            :key="pageNumber"
                            variant="outline"
                            class="h-9 min-w-9 rounded-xl px-3 transition-all duration-200"
                            :class="
                                pageNumber === props.page.current_page
                                    ? 'border-cyan-500 bg-cyan-600 text-white hover:bg-cyan-600'
                                    : ''
                            "
                            @click="changePage(pageNumber)"
                        >
                            {{ pageNumber }}
                        </Button>

                        <Button
                            variant="outline"
                            class="h-9 rounded-xl px-3"
                            :disabled="
                                props.page.current_page >= props.page.last_page
                            "
                            @click="changePage(props.page.current_page + 1)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </template>
        </section>

        <Dialog :open="isOpen" @update:open="closeModal">
            <DialogContent
                class="max-h-[92vh] max-w-5xl overflow-hidden rounded-[2rem] border-none bg-white p-0 shadow-2xl dark:bg-zinc-950"
            >
                <div class="flex max-h-[92vh] flex-col">
                    <DialogHeader
                        class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800 sm:px-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <DialogTitle class="text-xl">
                                    {{
                                        isEditing
                                            ? 'Editar paciente'
                                            : 'Nuevo paciente'
                                    }}
                                </DialogTitle>

                                <DialogDescription class="mt-1">
                                    Los campos marcados como opcionales pueden
                                    quedarse vacíos.
                                </DialogDescription>
                            </div>
                        </div>
                    </DialogHeader>

                    <div
                        class="flex-1 overflow-y-auto px-5 py-5 [-ms-overflow-style:none] [scrollbar-width:none] dark:bg-zinc-950 sm:px-6 [&::-webkit-scrollbar]:hidden"
                    >
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="space-y-2">
                                <Label :class="labelBase">
                                    Nombres
                                    <span class="text-red-500">*</span>
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
                                <Label :class="labelBase">Estado</Label>

                                <SearchableSelect
                                    v-model="form.status"
                                    :options="[
                                        {
                                            value: 'active',
                                            label: tGeneralStatus('active'),
                                        },
                                        {
                                            value: 'inactive',
                                            label: tGeneralStatus('inactive'),
                                        },
                                    ]"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label :class="labelBase">
                                    Apellido paterno
                                    <span class="text-xs font-normal text-zinc-400">
                                        opcional
                                    </span>
                                </Label>

                                <Input
                                    v-model="form.apellido_paterno"
                                    :class="inputBase"
                                    placeholder="Ej. Fuentes"
                                    autocomplete="family-name"
                                />

                                <p
                                    v-if="form.errors.apellido_paterno"
                                    class="text-xs text-red-500"
                                >
                                    {{ form.errors.apellido_paterno }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label :class="labelBase">
                                    Apellido materno
                                    <span class="text-xs font-normal text-zinc-400">
                                        opcional
                                    </span>
                                </Label>

                                <Input
                                    v-model="form.apellido_materno"
                                    :class="inputBase"
                                    placeholder="Ej. Peña"
                                    autocomplete="family-name"
                                />

                                <p
                                    v-if="form.errors.apellido_materno"
                                    class="text-xs text-red-500"
                                >
                                    {{ form.errors.apellido_materno }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label :class="labelBase">
                                    Teléfono
                                    <span class="text-xs font-normal text-zinc-400">
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
                                    @input="normalizePhone('telefono')"
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
                                    <span class="text-xs font-normal text-zinc-400">
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

                            <div class="space-y-2">
                                <Label :class="labelBase">
                                    Fecha nacimiento
                                    <span class="text-xs font-normal text-zinc-400">
                                        opcional
                                    </span>
                                </Label>

                                <DatePicker v-model="form.fecha_nacimiento" />

                                <p
                                    v-if="form.errors.fecha_nacimiento"
                                    class="text-xs text-red-500"
                                >
                                    {{ form.errors.fecha_nacimiento }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label :class="labelBase">
                                    Sexo
                                    <span class="text-xs font-normal text-zinc-400">
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
                                        { value: 'M', label: 'Masculino' },
                                        { value: 'F', label: 'Femenino' },
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

                            <div class="space-y-2 lg:col-span-2">
                                <Label :class="labelBase">
                                    Dirección
                                    <span class="text-xs font-normal text-zinc-400">
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

                            <div
                                class="rounded-[1.5rem] border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/40 lg:col-span-2"
                            >
                                <div class="mb-4 flex items-center gap-2">
                                    <ShieldAlert class="h-4 w-4 text-cyan-600" />
                                    <h3
                                        class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                    >
                                        Contacto de emergencia
                                    </h3>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label :class="labelBase">
                                            Nombre del contacto
                                            <span class="text-xs font-normal text-zinc-400">
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
                                            <span class="text-xs font-normal text-zinc-400">
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
                            </div>

                            <div class="space-y-2 lg:col-span-2">
                                <Label :class="labelBase">
                                    Notas
                                    <span class="text-xs font-normal text-zinc-400">
                                        opcional
                                    </span>
                                </Label>

                                <textarea
                                    v-model="form.notas"
                                    class="min-h-28 w-full resize-y rounded-2xl border border-zinc-200 bg-white/90 p-3 text-sm shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/40 dark:border-zinc-800 dark:bg-zinc-900/80"
                                    placeholder="Observaciones importantes del paciente"
                                />

                                <p
                                    v-if="form.errors.notas"
                                    class="text-xs text-red-500"
                                >
                                    {{ form.errors.notas }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <DialogFooter
                        class="border-t border-zinc-100 bg-white px-5 py-4 dark:border-zinc-800 dark:bg-zinc-950 sm:px-6"
                    >
                        <Button
                            variant="outline"
                            class="h-11 rounded-2xl px-5"
                            :disabled="form.processing || isSubmitting"
                            @click="closeModal(false)"
                        >
                            Cancelar
                        </Button>

                        <Button
                            class="h-11 rounded-2xl px-5 shadow-lg shadow-cyan-600/20 transition-all duration-300 hover:-translate-y-0.5"
                            :disabled="form.processing || isSubmitting"
                            @click="submit"
                        >
                            <Loader2
                                v-if="form.processing || isSubmitting"
                                class="mr-2 h-4 w-4 animate-spin"
                            />

                            {{
                                form.processing || isSubmitting
                                    ? 'Guardando...'
                                    : isEditing
                                      ? 'Actualizar'
                                      : 'Crear paciente'
                            }}
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>