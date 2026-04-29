<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import FvPagination from '@/components/fv/FvPagination.vue';
import { formatDateTimeMx } from '@/lib/dates';
import { tModule } from '@/lib/labels';
import {
    Activity,
    AlertCircle,
    CalendarDays,
    ChevronDown,
    ClipboardList,
    FileJson,
    MonitorCog,
    Search,
    UserRound,
    X,
} from 'lucide-vue-next';

type LogRow = {
    id: number;
    human_message?: string | null;
    user_name?: string | null;
    module?: string | null;
    action?: string | null;
    created_at?: string | null;
    ip_address?: string | null;
    old_values?: unknown;
    new_values?: unknown;
};

const props = defineProps<{
    rows: LogRow[];
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
        module?: string;
        action?: string;
        user_id?: string | number;
        start_date?: string;
        end_date?: string;
        per_page?: string | number;
    };
    lookups: {
        users: { id: number; label: string }[];
        modules: string[];
        actions: string[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Bitácora', href: '/logs' }];

const search = ref(props.filters.q ?? '');
let searchTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.filters.q,
    (value) => {
        search.value = value ?? '';
    },
);

watch(search, (value) => {
    if (searchTimer) clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        applyFilters({
            q: value.trim(),
            page: 1,
        });
    }, 450);
});

const applyFilters = (extra: Record<string, unknown>) => {
    router.get(
        '/logs',
        {
            ...props.filters,
            ...extra,
            page: extra.page ?? 1,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearSearch = () => {
    search.value = '';
};

const parseJson = (value: unknown) => {
    if (!value) return null;
    if (typeof value === 'object') return value;

    try {
        return JSON.parse(String(value));
    } catch {
        return String(value);
    }
};

const prettyJson = (value: unknown) => {
    const parsed = parseJson(value);

    if (!parsed) return '—';

    if (typeof parsed === 'string') return parsed;

    return JSON.stringify(parsed, null, 2);
};

const actionLabel = (action?: string | null) => {
    if (!action) return 'Acción';

    const labels: Record<string, string> = {
        created: 'Creación',
        updated: 'Actualización',
        deleted: 'Eliminación',
        status_changed: 'Cambio de estado',
        login: 'Inicio de sesión',
        logout: 'Cierre de sesión',
    };

    return labels[action] ?? action;
};

const actionClass = (action?: string | null) => {
    if (action === 'deleted') {
        return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300';
    }

    if (action === 'created') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    if (action === 'updated' || action === 'status_changed') {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-300';
    }

    return 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300';
};

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
    <Head title="Bitácora" />

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
                            <ClipboardList class="h-6 w-6" />
                        </div>

                        <div>
                            <h1
                                class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50"
                            >
                                Bitácora
                            </h1>

                            <p
                                class="mt-1 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-400"
                            >
                                Consulta movimientos importantes del sistema,
                                cambios realizados, usuarios involucrados y
                                detalles técnicos cuando sea necesario.
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl px-4 py-3 text-sm font-medium text-zinc-700 dark:text-zinc-200"
                        :style="primarySoftStyle"
                    >
                        {{ props.page.total }} movimientos registrados
                    </div>
                </div>
            </div>

            <div
                class="rounded-[1.75rem] border border-zinc-200 bg-zinc-50 p-4 shadow-sm transition-all duration-300 dark:border-zinc-800 dark:bg-zinc-900/40"
            >
                <div class="grid gap-3 xl:grid-cols-6">
                    <div class="relative xl:col-span-2">
                        <Search
                            class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-zinc-400"
                        />

                        <Input
                            v-model="search"
                            class="h-11 rounded-2xl border-zinc-200 bg-white pr-10 pl-9 shadow-sm transition-all duration-200 focus-visible:border-[color:var(--primary)] focus-visible:ring-2 focus-visible:ring-[color:var(--primary)]/20 dark:border-zinc-800 dark:bg-zinc-950"
                            placeholder="Buscar por mensaje o usuario"
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
                        :model-value="props.filters.module ?? null"
                        :options="[
                            { value: null, label: 'Todos los módulos' },
                            ...props.lookups.modules.map((module) => ({
                                value: module,
                                label: tModule(module),
                            })),
                        ]"
                        placeholder="Filtrar módulo"
                        clearable
                        @update:model-value="
                            (value) => applyFilters({ module: value ?? '' })
                        "
                    />

                    <SearchableSelect
                        :model-value="props.filters.action ?? null"
                        :options="[
                            { value: null, label: 'Todas las acciones' },
                            ...props.lookups.actions.map((action) => ({
                                value: action,
                                label: actionLabel(action),
                            })),
                        ]"
                        placeholder="Filtrar acción"
                        clearable
                        @update:model-value="
                            (value) => applyFilters({ action: value ?? '' })
                        "
                    />

                    <SearchableSelect
                        :model-value="
                            props.filters.user_id
                                ? Number(props.filters.user_id)
                                : null
                        "
                        :options="[
                            { value: null, label: 'Todos los usuarios' },
                            ...props.lookups.users.map((user) => ({
                                value: user.id,
                                label: user.label,
                            })),
                        ]"
                        placeholder="Filtrar usuario"
                        clearable
                        @update:model-value="
                            (value) => applyFilters({ user_id: value ?? '' })
                        "
                    />

                    <div
                        class="grid gap-3 sm:grid-cols-2 xl:col-span-1 xl:grid-cols-1"
                    >
                        <div class="relative">
                            <CalendarDays
                                class="pointer-events-none absolute top-3.5 left-3 z-10 h-4 w-4 text-zinc-400"
                            />

                            <DatePicker
                                :model-value="props.filters.start_date ?? null"
                                class="pl-8"
                                @update:model-value="
                                    (value) =>
                                        applyFilters({
                                            start_date: value ?? '',
                                        })
                                "
                            />
                        </div>

                        <div class="relative">
                            <CalendarDays
                                class="pointer-events-none absolute top-3.5 left-3 z-10 h-4 w-4 text-zinc-400"
                            />

                            <DatePicker
                                :model-value="props.filters.end_date ?? null"
                                class="pl-8"
                                @update:model-value="
                                    (value) =>
                                        applyFilters({
                                            end_date: value ?? '',
                                        })
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="!props.rows.length"
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
                    Sin movimientos para mostrar
                </h3>

                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Ajusta la búsqueda, el usuario, el módulo o el rango de
                    fechas.
                </p>
            </div>

            <div v-else class="space-y-4">
                <article
                    v-for="row in props.rows"
                    :key="row.id"
                    class="group rounded-[1.75rem] border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[color:var(--primary)] hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900/60"
                >
                    <div
                        class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge
                                    class="rounded-full border px-3 py-1 text-xs"
                                    :class="actionClass(row.action)"
                                >
                                    {{ actionLabel(row.action) }}
                                </Badge>

                                <Badge
                                    class="rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                                >
                                    {{ tModule(row.module || '') }}
                                </Badge>
                            </div>

                            <p
                                class="mt-3 text-base leading-6 font-semibold text-zinc-950 dark:text-zinc-50"
                            >
                                {{
                                    row.human_message ||
                                    'Acción registrada en bitácora.'
                                }}
                            </p>

                            <div
                                class="mt-3 grid gap-2 text-xs text-zinc-500 sm:grid-cols-2 lg:grid-cols-4 dark:text-zinc-400"
                            >
                                <div class="flex items-center gap-2">
                                    <UserRound class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">
                                        {{ row.user_name || 'Sistema' }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Activity class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">
                                        {{ row.action || 'Sin acción' }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <CalendarDays
                                        class="h-3.5 w-3.5 shrink-0"
                                    />
                                    <span class="truncate">
                                        {{
                                            row.created_at
                                                ? formatDateTimeMx(
                                                      row.created_at,
                                                  )
                                                : 'Sin fecha'
                                        }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <MonitorCog class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">
                                        {{ row.ip_address || 'Sin IP' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl"
                            :style="primarySoftStyle"
                        >
                            <FileJson
                                class="h-5 w-5"
                                :style="{ color: 'var(--primary)' }"
                            />
                        </div>
                    </div>

                    <details
                        class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50 p-3 text-xs dark:border-zinc-800 dark:bg-zinc-950/70"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-3 font-medium text-zinc-700 dark:text-zinc-200"
                        >
                            <span>Ver detalle técnico</span>
                            <ChevronDown class="h-4 w-4" />
                        </summary>

                        <div class="mt-3 grid gap-3 lg:grid-cols-2">
                            <div
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950"
                            >
                                <div
                                    class="border-b border-zinc-100 px-3 py-2 text-xs font-semibold text-zinc-700 dark:border-zinc-800 dark:text-zinc-200"
                                >
                                    Anterior
                                </div>

                                <pre
                                    class="max-h-72 overflow-auto p-3 text-xs leading-5 text-zinc-600 dark:text-zinc-300"
                                    >{{ prettyJson(row.old_values) }}</pre
                                >
                            </div>

                            <div
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950"
                            >
                                <div
                                    class="border-b border-zinc-100 px-3 py-2 text-xs font-semibold text-zinc-700 dark:border-zinc-800 dark:text-zinc-200"
                                >
                                    Nuevo
                                </div>

                                <pre
                                    class="max-h-72 overflow-auto p-3 text-xs leading-5 text-zinc-600 dark:text-zinc-300"
                                    >{{ prettyJson(row.new_values) }}</pre
                                >
                            </div>
                        </div>
                    </details>
                </article>
            </div>

            <FvPagination
                :page="props.page"
                item-label="movimientos"
                :per-page-options="[10, 15, 20, 50, 'all']"
                @change="
                    (page) =>
                        applyFilters({
                            page,
                        })
                "
                @per-page-change="
                    (perPage) =>
                        applyFilters({
                            page: 1,
                            per_page: perPage,
                        })
                "
            />
        </section>
    </AppLayout>
</template>
