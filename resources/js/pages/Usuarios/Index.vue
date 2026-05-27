<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    useUsuarioCrud,
    type UsuarioRole,
    type UsuarioRow,
} from '@/composables/crud/useUsuarioCrud';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
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
    ShieldCheck,
    Power,
    Pencil,
    Trash2,
    Search,
    X,
    AlertCircle,
    Users,
    Mail,
    KeyRound,
    Settings2,
    UserRound,
    Info,
    CheckCircle2,
    Phone,
} from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import { tGeneralStatus, tModule } from '@/lib/labels';
import FvPagination from '@/components/fv/FvPagination.vue';

const props = defineProps<{
    rows: UsuarioRow[];
    page: {
        current_page: number;
        last_page: number;
        per_page?: number | string;
        per_page_selected?: number | string;
        from?: number | null;
        to?: number | null;
        total: number;
    };
    filters: { q?: string; status?: string };
    roles: UsuarioRole[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Usuarios', href: '/usuarios' },
];

const {
    form,
    isOpen,
    isEditing,
    openCreate,
    openEdit,
    toggleRole,
    closeModal,
    submit,
    toggleStatus,
    destroyUser,
    onlyDigits,
} = useUsuarioCrud();

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

const applyFilters = (extra: Record<string, string | number>) => {
    router.get(
        '/usuarios',
        { ...props.filters, ...extra },
        { preserveState: true, replace: true },
    );
};

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

const statusClass = (status: UsuarioRow['status']) =>
    status === 'active'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300'
        : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-300';

const fullName = (row: UsuarioRow) =>
    [row.nombres, row.apellido_paterno, row.apellido_materno]
        .filter(Boolean)
        .join(' ')
        .trim() ||
    row.name ||
    'Usuario sin nombre';

const roleNames = (row: UsuarioRow) => {
    if (row.is_super_admin) return 'Super administrador';
    if (!row.roles || row.roles.length === 0) return 'Sin roles asignados';

    return row.roles.map((role) => role.name).join(', ');
};

const moduleItems = [
    { key: 'mod_agenda', label: tModule('appointments') },
    { key: 'mod_pacientes', label: tModule('patients') },
    { key: 'mod_sesiones', label: tModule('sessions') },
    { key: 'mod_ejercicios', label: tModule('exercises') },
    { key: 'mod_archivos', label: tModule('files') },
    { key: 'mod_reportes', label: tModule('reports') },
    { key: 'mod_cobranza', label: tModule('payments') },
    { key: 'mod_config', label: tModule('settings') },
] as const;

const inputBase =
    'h-11 rounded-2xl border-input bg-card shadow-sm transition-all duration-200 placeholder:text-muted-foreground focus-visible:border-[color:var(--primary)] focus-visible:ring-2 focus-visible:ring-[color:var(--primary)]/20';

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
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="w-full space-y-5">
            <div
                class="relative overflow-hidden rounded-[2rem] border border-border bg-card p-5 shadow-sm"
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
                            <Users class="h-6 w-6" />
                        </div>

                        <div>
                            <h1
                                class="text-2xl font-semibold tracking-tight text-foreground"
                            >
                                Usuarios
                            </h1>

                            <p
                                class="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground"
                            >
                                Administra las cuentas de acceso. Cada usuario
                                se relaciona con una persona tipo staff y hereda
                                permisos mediante roles.
                            </p>
                        </div>
                    </div>

                    <Button
                        class="h-11 rounded-2xl px-5 shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl"
                        :style="primaryButtonStyle"
                        @mouseenter="setPrimaryHover"
                        @mouseleave="setPrimaryNormal"
                        @click="openCreate"
                    >
                        <UserPlus class="mr-2 h-4 w-4" />
                        Nuevo usuario
                    </Button>
                </div>
            </div>

            <div
                class="rounded-[1.75rem] border border-border bg-muted p-4 shadow-sm transition-all duration-300"
            >
                <div class="grid gap-3 lg:grid-cols-[1fr_260px]">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-muted-foreground"
                        />

                        <Input
                            v-model="search"
                            class="h-11 rounded-2xl border-border bg-card pr-10 pl-9 shadow-sm transition-all duration-200 focus-visible:border-[color:var(--primary)] focus-visible:ring-2 focus-visible:ring-[color:var(--primary)]/20"
                            placeholder="Buscar por nombre, correo o teléfono"
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
                            {
                                value: 'active',
                                label: tGeneralStatus('active'),
                            },
                            {
                                value: 'blocked',
                                label: tGeneralStatus('blocked'),
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
                class="rounded-[2rem] border border-dashed border-border bg-muted p-10 text-center transition-all duration-300"
            >
                <div
                    class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-card shadow-sm"
                >
                    <AlertCircle class="h-6 w-6 text-muted-foreground" />
                </div>

                <h3
                    class="mt-4 text-lg font-semibold text-foreground"
                >
                    Sin usuarios para mostrar
                </h3>

                <p class="mt-2 text-sm text-muted-foreground">
                    Crea un usuario nuevo o ajusta la búsqueda y filtros.
                </p>
            </div>

            <div v-else class="grid gap-4 xl:grid-cols-2">
                <article
                    v-for="row in props.rows"
                    :key="row.id"
                    class="group rounded-[1.75rem] border border-border bg-card p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[color:var(--primary)] hover:shadow-xl"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3
                                    class="text-base font-semibold text-foreground"
                                >
                                    {{ fullName(row) }}
                                </h3>

                                <Badge
                                    class="rounded-full border px-3 py-1 text-xs"
                                    :class="statusClass(row.status)"
                                >
                                    {{ tGeneralStatus(row.status) }}
                                </Badge>

                                <Badge
                                    v-if="row.is_super_admin"
                                    class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/40 dark:text-indigo-300"
                                >
                                    Super administrador
                                </Badge>
                            </div>

                            <div
                                class="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-2"
                            >
                                <div class="flex items-center gap-2">
                                    <Mail class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">
                                        {{ row.email }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Phone class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">
                                        {{ row.telefono || 'Sin teléfono' }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center gap-2 sm:col-span-2"
                                >
                                    <ShieldCheck class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">
                                        {{ roleNames(row) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl"
                            :style="primarySoftStyle"
                        >
                            <UserRound
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
                            <strong>Roles:</strong>
                            {{ roleNames(row) }}
                        </p>

                        <p class="mt-1">
                            <strong>Último acceso:</strong>
                            {{ row.last_login_at || 'Sin registro' }}
                        </p>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            class="h-10 rounded-xl border-border bg-card transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                            @click="openEdit(row)"
                        >
                            <Pencil class="mr-2 h-4 w-4" />
                            Editar
                        </Button>

                        <Button
                            variant="outline"
                            class="h-10 rounded-xl border-border bg-card transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                            @click="toggleStatus(row)"
                        >
                            <Power class="mr-2 h-4 w-4" />
                            {{
                                row.status === 'active' ? 'Bloquear' : 'Activar'
                            }}
                        </Button>

                        <Button
                            variant="destructive"
                            class="h-10 rounded-xl transition-all duration-200 hover:-translate-y-0.5"
                            @click="destroyUser(row.id)"
                        >
                            <Trash2 class="mr-2 h-4 w-4" />
                            Eliminar
                        </Button>
                    </div>
                </article>
            </div>

            <FvPagination
                :page="props.page"
                item-label="usuarios"
                :per-page-options="[10, 15, 20, 50, 'all']"
                @change="
                    (page) =>
                        applyFilters({
                            q: search.trim(),
                            status: selectedStatus ?? '',
                            page,
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
                                        <UserRound class="h-4 w-4" />
                                    </span>

                                    <span>
                                        {{
                                            isEditing
                                                ? 'Editar usuario'
                                                : 'Nuevo usuario'
                                        }}
                                    </span>
                                </DialogTitle>

                                <DialogDescription
                                    class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground"
                                >
                                    Primero se registra la persona como staff,
                                    después se crea su acceso al sistema.
                                </DialogDescription>
                            </div>

                            <div
                                class="rounded-2xl border border-border bg-muted px-3 py-2 text-xs leading-5 text-muted-foreground lg:w-72"
                            >
                                <span
                                    class="font-medium text-foreground"
                                >
                                    Importante:
                                </span>
                                Al crear un usuario se enviará un correo con sus
                                credenciales.
                            </div>
                        </div>
                    </DialogHeader>

                    <div
                        class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-muted/70 px-4 py-4 sm:px-6 sm:py-5 lg:px-7"
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
                                            Información de la persona
                                        </h3>

                                        <p
                                            class="text-xs leading-5 text-muted-foreground"
                                        >
                                            Estos datos se guardan en personas
                                            como staff. Si también fuera
                                            paciente, el sistema puede manejarlo
                                            como ambos.
                                        </p>
                                    </div>

                                    <div
                                        class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                                    >
                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Nombre(s)
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>

                                            <Input
                                                v-model="form.nombres"
                                                :class="inputBase"
                                                placeholder="Ej. Mariana"
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
                                                <span
                                                    class="text-xs font-normal text-muted-foreground"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <Input
                                                v-model="form.apellido_paterno"
                                                :class="inputBase"
                                                placeholder="Ej. Torres"
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
                                                <span
                                                    class="text-xs font-normal text-muted-foreground"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <Input
                                                v-model="form.apellido_materno"
                                                :class="inputBase"
                                                placeholder="Ej. Méndez"
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
                                            <Label :class="labelBase">
                                                Teléfono
                                                <span
                                                    class="text-xs font-normal text-muted-foreground"
                                                >
                                                    opcional
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
                                                    form.telefono = onlyDigits(
                                                        form.telefono,
                                                    )
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
                                                Correo electrónico
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </Label>

                                            <Input
                                                v-model="form.email"
                                                :class="inputBase"
                                                type="email"
                                                placeholder="usuario@correo.com"
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
                                                Contraseña
                                                <span
                                                    v-if="!isEditing"
                                                    class="text-red-500"
                                                    >*</span
                                                >
                                                <span
                                                    v-else
                                                    class="text-xs font-normal text-muted-foreground"
                                                >
                                                    opcional
                                                </span>
                                            </Label>

                                            <Input
                                                v-model="form.password"
                                                :class="inputBase"
                                                type="password"
                                                autocomplete="new-password"
                                                placeholder="Mínimo 8 caracteres"
                                            />

                                            <p
                                                v-if="form.errors.password"
                                                class="text-xs text-red-500"
                                            >
                                                {{ form.errors.password }}
                                            </p>

                                            <p
                                                v-else
                                                class="text-xs text-muted-foreground"
                                            >
                                                En edición, deja vacío para
                                                conservar la contraseña actual.
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label :class="labelBase">
                                                Estado
                                            </Label>

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
                                                        value: 'blocked',
                                                        label: tGeneralStatus(
                                                            'blocked',
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
                                    </div>
                                </section>

                                <section
                                    class="rounded-[1.5rem] border border-border bg-card p-4 shadow-sm sm:p-5"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-border pb-3"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <ShieldCheck
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Roles asignados
                                        </h3>

                                        <p
                                            class="text-xs leading-5 text-muted-foreground"
                                        >
                                            Los permisos se heredan desde los
                                            roles seleccionados.
                                        </p>
                                    </div>

                                    <div
                                        class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"
                                    >
                                        <button
                                            v-for="role in props.roles"
                                            :key="role.id"
                                            type="button"
                                            class="flex items-center gap-3 rounded-2xl border p-3 text-left text-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:shadow-sm"
                                            :class="
                                                form.role_ids.includes(role.id)
                                                    ? 'border-[color:var(--primary)] bg-[color-mix(in_srgb,var(--primary)_10%,var(--card))]'
                                                    : 'border-border bg-card dark:bg-card/60'
                                            "
                                            @click="toggleRole(role.id)"
                                        >
                                            <Checkbox
                                                :model-value="
                                                    form.role_ids.includes(
                                                        role.id,
                                                    )
                                                "
                                                @click.stop
                                                @update:model-value="
                                                    toggleRole(role.id)
                                                "
                                            />

                                            <span
                                                class="font-medium text-foreground"
                                            >
                                                {{ role.name }}
                                            </span>
                                        </button>
                                    </div>

                                    <p
                                        v-if="form.errors.role_ids"
                                        class="mt-3 text-xs text-red-500"
                                    >
                                        {{ form.errors.role_ids }}
                                    </p>
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
                                            <KeyRound
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Tipo de acceso
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 rounded-2xl border p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)]"
                                        :class="
                                            form.is_super_admin
                                                ? 'border-[color:var(--primary)] bg-[color-mix(in_srgb,var(--primary)_10%,var(--card))]'
                                                : 'border-border bg-card dark:bg-card/60'
                                        "
                                        @click="
                                            form.is_super_admin =
                                                !form.is_super_admin
                                        "
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-semibold text-foreground"
                                            >
                                                Super administrador
                                            </p>

                                            <p
                                                class="mt-1 text-xs leading-5 text-muted-foreground"
                                            >
                                                Tendrá acceso completo al
                                                sistema.
                                            </p>
                                        </div>

                                        <Checkbox
                                            :model-value="form.is_super_admin"
                                            @click.stop
                                            @update:model-value="
                                                (value) =>
                                                    (form.is_super_admin =
                                                        !!value)
                                            "
                                        />
                                    </button>
                                </section>

                                <section
                                    class="rounded-[1.5rem] border border-border bg-card p-4 shadow-sm sm:p-5"
                                >
                                    <div
                                        class="mb-4 flex flex-col gap-1 border-b border-border pb-3"
                                    >
                                        <h3 :class="sectionTitleBase">
                                            <Settings2
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Módulos heredados
                                        </h3>

                                        <p
                                            class="text-xs leading-5 text-muted-foreground"
                                        >
                                            Compatibilidad del sistema. Los
                                            permisos principales vienen del rol.
                                        </p>
                                    </div>

                                    <div class="grid gap-2">
                                        <label
                                            v-for="item in moduleItems"
                                            :key="item.key"
                                            class="flex items-center justify-between gap-3 rounded-2xl border border-border bg-card px-3 py-2 text-sm"
                                        >
                                            <span
                                                class="text-foreground"
                                            >
                                                {{ item.label }}
                                            </span>

                                            <Checkbox
                                                :model-value="form[item.key]"
                                                @update:model-value="
                                                    (value) =>
                                                        (form[item.key] =
                                                            !!value)
                                                "
                                            />
                                        </label>
                                    </div>
                                </section>

                                <section
                                    class="rounded-[1.5rem] border border-border bg-card p-4 shadow-sm sm:p-5"
                                >
                                    <div
                                        class="flex items-start gap-3 rounded-2xl p-4"
                                        :style="primarySoftStyle"
                                    >
                                        <CheckCircle2
                                            class="mt-0.5 h-5 w-5 shrink-0"
                                            :style="{ color: 'var(--primary)' }"
                                        />

                                        <div>
                                            <p
                                                class="text-sm font-semibold text-foreground"
                                            >
                                                Correo automático
                                            </p>

                                            <p
                                                class="mt-1 text-xs leading-5 text-muted-foreground"
                                            >
                                                Al crear el usuario se enviará
                                                un correo con la URL de acceso,
                                                correo y contraseña.
                                            </p>
                                        </div>
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
                                        ? isEditing
                                            ? 'Actualizando...'
                                            : 'Creando y enviando...'
                                        : isEditing
                                          ? 'Actualizar usuario'
                                          : 'Crear usuario'
                                }}
                            </Button>
                        </div>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
