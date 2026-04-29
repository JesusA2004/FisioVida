<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    useRolCrud,
    type PermissionOption,
    type RoleRow,
} from '@/composables/crud/useRolCrud';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import {
    ShieldCheck,
    Search,
    Pencil,
    Trash2,
    CheckCheck,
    Eraser,
    KeyRound,
    Settings2,
    AlertCircle,
    X,
    LockKeyhole,
    Info,
    ListChecks,
} from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { tGeneralStatus, tModule, tPermission } from '@/lib/labels';
import FvPagination from '@/components/fv/FvPagination.vue';

const props = defineProps<{
    rows: RoleRow[];
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
    permissionsByModule: Record<string, PermissionOption[]>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: '/roles' }];

const {
    form,
    isOpen,
    isEditing,
    openCreate,
    openEdit,
    closeModal,
    togglePermission,
    selectAllFromModule,
    clearModule,
    submit,
    destroyRole,
    permissionSearch,
    selectedCount,
    syncSlugFromName,
} = useRolCrud();

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
        applyFilter({
            q: value.trim(),
            status: selectedStatus.value ?? '',
            page: 1,
        });
    }, 450);
});

watch(
    () => form.name,
    () => {
        syncSlugFromName();
    },
);

const groupedPermissions = computed(() =>
    Object.entries(props.permissionsByModule ?? {}),
);

const applyFilter = (extra: Record<string, string | number>) => {
    router.get(
        '/roles',
        { ...props.filters, ...extra },
        { preserveState: true, replace: true },
    );
};

const applyStatus = (value: string | number | null) => {
    selectedStatus.value = value ? String(value) : null;

    applyFilter({
        q: search.value.trim(),
        status: selectedStatus.value ?? '',
        page: 1,
    });
};

const clearSearch = () => {
    search.value = '';
};

const friendlyPermission = (permission: PermissionOption) => {
    const translated = tPermission(permission.slug);

    if (translated && translated !== permission.slug) {
        return translated;
    }

    return permission.name;
};

const filteredBySearch = (permissions: PermissionOption[]) => {
    const q = permissionSearch.value.trim().toLowerCase();
    if (!q) return permissions;

    return permissions.filter((item) => {
        const friendly = friendlyPermission(item).toLowerCase();

        return (
            friendly.includes(q) ||
            item.name.toLowerCase().includes(q) ||
            item.slug.toLowerCase().includes(q) ||
            tModule(item.module).toLowerCase().includes(q)
        );
    });
};

const selectedCountFromModule = (permissions: PermissionOption[]) =>
    permissions.filter((permission) =>
        form.permission_ids.includes(permission.id),
    ).length;

const allModuleSelected = (permissions: PermissionOption[]) => {
    if (permissions.length === 0) return false;

    return permissions.every((permission) =>
        form.permission_ids.includes(permission.id),
    );
};

const statusClass = (status: RoleRow['status']) =>
    status === 'active'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300'
        : 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300';

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
    <Head title="Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="w-full space-y-5">
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
                            <ShieldCheck class="h-6 w-6" />
                        </div>

                        <div>
                            <h1
                                class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50"
                            >
                                Roles
                            </h1>

                            <p
                                class="mt-1 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-400"
                            >
                                Define qué puede hacer cada tipo de usuario. Los
                                accesos se configuran aquí y después el rol se
                                asigna a un usuario.
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
                        <ShieldCheck class="mr-2 h-4 w-4" />
                        Nuevo rol
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
                            placeholder="Buscar por nombre o descripción"
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
                    Sin roles para mostrar
                </h3>

                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Crea un rol nuevo o ajusta la búsqueda y filtros.
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
                                    {{ row.name || 'Rol sin nombre' }}
                                </h3>

                                <Badge
                                    class="rounded-full border px-3 py-1 text-xs"
                                    :class="statusClass(row.status)"
                                >
                                    {{ tGeneralStatus(row.status) }}
                                </Badge>
                            </div>

                            <p
                                class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400"
                            >
                                {{
                                    row.description ||
                                    'Sin descripción para este rol.'
                                }}
                            </p>
                        </div>

                        <div
                            class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl"
                            :style="primarySoftStyle"
                        >
                            <LockKeyhole
                                class="h-5 w-5"
                                :style="{ color: 'var(--primary)' }"
                            />
                        </div>
                    </div>

                    <div
                        class="mt-4 grid gap-3 rounded-2xl p-3 text-xs text-zinc-600 sm:grid-cols-2 dark:text-zinc-300"
                        :style="primarySoftStyle"
                    >
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">
                                Accesos configurados
                            </p>

                            <p
                                class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-50"
                            >
                                {{ row.permissions_count }}
                            </p>
                        </div>

                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">
                                Uso del rol
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-50"
                            >
                                Asignable a usuarios
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            class="h-10 rounded-xl border-zinc-200 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                            @click="openEdit(row)"
                        >
                            <Pencil class="mr-2 h-4 w-4" />
                            Editar accesos
                        </Button>

                        <Button
                            variant="destructive"
                            class="h-10 rounded-xl transition-all duration-200 hover:-translate-y-0.5"
                            @click="destroyRole(row.id)"
                        >
                            <Trash2 class="mr-2 h-4 w-4" />
                            Eliminar
                        </Button>
                    </div>
                </article>
            </div>

            <FvPagination
                :page="props.page"
                item-label="roles"
                :per-page-options="[10, 15, 20, 50, 'all']"
                @change="
                    (page) =>
                        applyFilter({
                            q: search.trim(),
                            status: selectedStatus ?? '',
                            page,
                        })
                "
                @per-page-change="
                    (perPage) =>
                        applyFilter({
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
                                        <ShieldCheck class="h-4 w-4" />
                                    </span>

                                    <span>
                                        {{
                                            isEditing
                                                ? 'Editar rol'
                                                : 'Nuevo rol'
                                        }}
                                    </span>
                                </DialogTitle>

                                <DialogDescription
                                    class="mt-2 max-w-3xl text-sm leading-6 text-zinc-500 dark:text-zinc-400"
                                >
                                    Configura qué acciones podrá realizar este
                                    rol dentro del sistema.
                                </DialogDescription>
                            </div>

                            <div
                                class="rounded-2xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs leading-5 text-zinc-500 lg:w-72 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400"
                            >
                                <span
                                    class="font-medium text-zinc-700 dark:text-zinc-200"
                                >
                                    Seleccionados:
                                </span>
                                {{ selectedCount }} accesos
                            </div>
                        </div>
                    </DialogHeader>

                    <div
                        class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-zinc-50/70 px-4 py-4 sm:px-6 sm:py-5 lg:px-7 dark:bg-zinc-950"
                    >
                        <div class="space-y-4 pb-5">
                            <section
                                class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                            >
                                <div
                                    class="mb-4 flex flex-col gap-1 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                >
                                    <h3 :class="sectionTitleBase">
                                        <Info
                                            class="h-4 w-4"
                                            :style="{ color: 'var(--primary)' }"
                                        />
                                        Información del rol
                                    </h3>

                                    <p
                                        class="text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                    >
                                        Usa nombres claros como Terapeuta,
                                        Recepción, Cobranza o Administrador.
                                    </p>
                                </div>

                                <div
                                    class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px] xl:grid-cols-[minmax(0,1fr)_240px_minmax(300px,0.8fr)]"
                                >
                                    <div class="space-y-2">
                                        <Label :class="labelBase">
                                            Nombre del rol
                                            <span class="text-red-500">*</span>
                                        </Label>

                                        <Input
                                            v-model="form.name"
                                            :class="inputBase"
                                            placeholder="Ej. Terapeuta"
                                        />

                                        <p
                                            v-if="form.errors.name"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.name }}
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

                                    <div
                                        class="space-y-2 md:col-span-2 xl:col-span-1"
                                    >
                                        <Label :class="labelBase">
                                            Descripción
                                            <span
                                                class="text-xs font-normal text-zinc-400"
                                            >
                                                opcional
                                            </span>
                                        </Label>

                                        <textarea
                                            v-model="form.description"
                                            class="min-h-11 w-full resize-y rounded-2xl border border-zinc-200 bg-white p-3 text-sm shadow-sm transition-all duration-200 placeholder:text-zinc-400 focus:border-[color:var(--primary)] focus:ring-2 focus:ring-[color:var(--primary)]/20 focus:outline-none xl:min-h-11 dark:border-zinc-800 dark:bg-zinc-900/80"
                                            placeholder="Ej. Puede gestionar pacientes, citas y sesiones."
                                        />

                                        <p
                                            v-if="form.errors.description"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.description }}
                                        </p>
                                    </div>

                                    <input v-model="form.slug" type="hidden" />

                                    <p
                                        v-if="form.errors.slug"
                                        class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600 md:col-span-2 xl:col-span-3"
                                    >
                                        {{ form.errors.slug }}
                                    </p>
                                </div>
                            </section>

                            <section
                                class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                            >
                                <div
                                    class="mb-4 flex flex-col gap-3 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                                >
                                    <div
                                        class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between"
                                    >
                                        <div>
                                            <h3 :class="sectionTitleBase">
                                                <Settings2
                                                    class="h-4 w-4"
                                                    :style="{
                                                        color: 'var(--primary)',
                                                    }"
                                                />
                                                Accesos del rol
                                            </h3>

                                            <p
                                                class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                            >
                                                Selecciona qué podrá hacer este
                                                rol. No se muestran claves
                                                técnicas.
                                            </p>
                                        </div>

                                        <div
                                            class="grid gap-2 sm:grid-cols-[180px_1fr] xl:w-[520px]"
                                        >
                                            <div
                                                class="rounded-2xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900"
                                            >
                                                <span
                                                    class="font-medium text-zinc-700 dark:text-zinc-200"
                                                >
                                                    Total:
                                                </span>
                                                {{ selectedCount }} accesos
                                            </div>

                                            <div class="relative">
                                                <Search
                                                    class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-zinc-400"
                                                />

                                                <Input
                                                    v-model="permissionSearch"
                                                    class="h-11 rounded-2xl border-zinc-200 bg-white pr-10 pl-9 shadow-sm transition-all duration-200 focus-visible:border-[color:var(--primary)] focus-visible:ring-2 focus-visible:ring-[color:var(--primary)]/20 dark:border-zinc-800 dark:bg-zinc-950"
                                                    placeholder="Buscar acceso"
                                                />

                                                <button
                                                    v-if="permissionSearch"
                                                    type="button"
                                                    class="absolute top-3 right-3 grid h-5 w-5 place-items-center rounded-full text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                                    @click="
                                                        permissionSearch = ''
                                                    "
                                                >
                                                    <X class="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3"
                                >
                                    <div
                                        v-for="[
                                            module,
                                            permissions,
                                        ] in groupedPermissions"
                                        :key="module"
                                        class="rounded-[1.25rem] border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950/70"
                                    >
                                        <div class="mb-3 flex flex-col gap-3">
                                            <div
                                                class="flex items-start justify-between gap-2"
                                            >
                                                <div>
                                                    <p
                                                        class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                                    >
                                                        {{ tModule(module) }}
                                                    </p>

                                                    <p
                                                        class="text-xs text-zinc-500 dark:text-zinc-400"
                                                    >
                                                        {{
                                                            selectedCountFromModule(
                                                                permissions,
                                                            )
                                                        }}
                                                        de
                                                        {{ permissions.length }}
                                                        seleccionados
                                                    </p>
                                                </div>

                                                <Badge
                                                    class="shrink-0 rounded-full border px-2 py-1 text-[11px]"
                                                    :class="
                                                        allModuleSelected(
                                                            permissions,
                                                        )
                                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                            : 'border-zinc-200 bg-white text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'
                                                    "
                                                >
                                                    {{
                                                        allModuleSelected(
                                                            permissions,
                                                        )
                                                            ? 'Completo'
                                                            : 'Parcial'
                                                    }}
                                                </Badge>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    class="h-8 rounded-xl border-zinc-200 bg-white text-xs transition-all duration-200 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                                    @click="
                                                        selectAllFromModule(
                                                            filteredBySearch(
                                                                permissions,
                                                            ),
                                                        )
                                                    "
                                                >
                                                    <CheckCheck
                                                        class="mr-1 h-3 w-3"
                                                    />
                                                    Todo
                                                </Button>

                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    class="h-8 rounded-xl border-zinc-200 bg-white text-xs transition-all duration-200 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                                    @click="
                                                        clearModule(
                                                            filteredBySearch(
                                                                permissions,
                                                            ),
                                                        )
                                                    "
                                                >
                                                    <Eraser
                                                        class="mr-1 h-3 w-3"
                                                    />
                                                    Limpiar
                                                </Button>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <button
                                                v-for="permission in filteredBySearch(
                                                    permissions,
                                                )"
                                                :key="permission.id"
                                                type="button"
                                                class="flex w-full items-start gap-3 rounded-2xl border p-3 text-left text-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:shadow-sm"
                                                :class="
                                                    form.permission_ids.includes(
                                                        permission.id,
                                                    )
                                                        ? 'border-[color:var(--primary)] bg-[color-mix(in_srgb,var(--primary)_8%,white)] dark:bg-zinc-900'
                                                        : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/60'
                                                "
                                                @click="
                                                    togglePermission(
                                                        permission.id,
                                                    )
                                                "
                                            >
                                                <Checkbox
                                                    class="mt-0.5"
                                                    :model-value="
                                                        form.permission_ids.includes(
                                                            permission.id,
                                                        )
                                                    "
                                                    @click.stop
                                                    @update:model-value="
                                                        togglePermission(
                                                            permission.id,
                                                        )
                                                    "
                                                />

                                                <span class="min-w-0">
                                                    <span
                                                        class="block leading-5 font-medium text-zinc-900 dark:text-zinc-100"
                                                    >
                                                        {{
                                                            friendlyPermission(
                                                                permission,
                                                            )
                                                        }}
                                                    </span>
                                                </span>
                                            </button>

                                            <div
                                                v-if="
                                                    filteredBySearch(
                                                        permissions,
                                                    ).length === 0
                                                "
                                                class="rounded-2xl border border-dashed border-zinc-200 bg-white p-4 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400"
                                            >
                                                No hay accesos que coincidan con
                                                la búsqueda.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <p
                                    v-if="form.errors.permission_ids"
                                    class="mt-3 text-xs text-red-500"
                                >
                                    {{ form.errors.permission_ids }}
                                </p>
                            </section>

                            <section
                                class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900/50"
                            >
                                <div
                                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <h3 :class="sectionTitleBase">
                                            <KeyRound
                                                class="h-4 w-4"
                                                :style="{
                                                    color: 'var(--primary)',
                                                }"
                                            />
                                            Resumen
                                        </h3>

                                        <p
                                            class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400"
                                        >
                                            Revisa el total antes de guardar.
                                        </p>
                                    </div>

                                    <div
                                        class="flex items-center gap-3 rounded-2xl px-4 py-3"
                                        :style="primarySoftStyle"
                                    >
                                        <ListChecks
                                            class="h-5 w-5"
                                            :style="{ color: 'var(--primary)' }"
                                        />

                                        <div>
                                            <p
                                                class="text-2xl font-semibold text-zinc-950 dark:text-zinc-50"
                                            >
                                                {{ selectedCount }}
                                            </p>
                                            <p
                                                class="text-xs text-zinc-500 dark:text-zinc-400"
                                            >
                                                accesos seleccionados
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </section>
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
                                          ? 'Actualizar rol'
                                          : 'Crear rol'
                                }}
                            </Button>
                        </div>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
