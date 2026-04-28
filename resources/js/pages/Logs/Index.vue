<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import { formatDateTimeMx } from '@/lib/dates';
import { tModule } from '@/lib/labels';

const props = defineProps<{
    rows: any[];
    page: { current_page: number; last_page: number; total: number };
    filters: {
        q?: string;
        module?: string;
        action?: string;
        user_id?: string | number;
        start_date?: string;
        end_date?: string;
    };
    lookups: {
        users: { id: number; label: string }[];
        modules: string[];
        actions: string[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Bitácora', href: '/logs' }];

const apply = (extra: Record<string, unknown>) => {
    router.get(
        route('logs.index'),
        { ...props.filters, ...extra, page: 1 },
        { preserveState: true, replace: true },
    );
};

const goPage = (page: number) =>
    router.get(
        route('logs.index'),
        { ...props.filters, page },
        { preserveState: true, replace: true },
    );
const parseJson = (value: unknown) => {
    if (!value) return null;
    if (typeof value === 'object') return value;
    try {
        return JSON.parse(String(value));
    } catch {
        return String(value);
    }
};
</script>

<template>
    <Head title="Bitácora" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-3xl bg-white p-6 shadow-xl dark:bg-zinc-950"
        >
            <div>
                <h1 class="text-2xl font-semibold">Bitácora del sistema</h1>
                <p class="text-sm text-zinc-500">
                    Registro humano de acciones operativas y clínicas.
                </p>
            </div>

            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                <Input
                    class="md:col-span-2 xl:col-span-2"
                    :default-value="props.filters.q ?? ''"
                    placeholder="Buscar por mensaje o usuario"
                    @change="
                        (e) =>
                            apply({ q: (e.target as HTMLInputElement).value })
                    "
                />
                <SearchableSelect
                    :model-value="props.filters.module ?? null"
                    :options="[
                        { value: null, label: 'Todos los módulos' },
                        ...props.lookups.modules.map((m) => ({
                            value: m,
                            label: tModule(m),
                        })),
                    ]"
                    clearable
                    @update:model-value="(v) => apply({ module: v ?? '' })"
                />
                <SearchableSelect
                    :model-value="props.filters.action ?? null"
                    :options="[
                        { value: null, label: 'Todas las acciones' },
                        ...props.lookups.actions.map((a) => ({
                            value: a,
                            label: a,
                        })),
                    ]"
                    clearable
                    @update:model-value="(v) => apply({ action: v ?? '' })"
                />
                <SearchableSelect
                    :model-value="
                        props.filters.user_id
                            ? Number(props.filters.user_id)
                            : null
                    "
                    :options="[
                        { value: null, label: 'Todos los usuarios' },
                        ...props.lookups.users.map((u) => ({
                            value: u.id,
                            label: u.label,
                        })),
                    ]"
                    clearable
                    @update:model-value="(v) => apply({ user_id: v ?? '' })"
                />
                <DatePicker
                    :model-value="props.filters.start_date ?? null"
                    @update:model-value="(v) => apply({ start_date: v ?? '' })"
                />
                <DatePicker
                    :model-value="props.filters.end_date ?? null"
                    @update:model-value="(v) => apply({ end_date: v ?? '' })"
                />
            </div>

            <div
                v-if="!props.rows.length"
                class="rounded-2xl border border-dashed p-10 text-center text-zinc-500"
            >
                Sin movimientos para los filtros actuales.
            </div>
            <div v-else class="space-y-3">
                <article
                    v-for="row in props.rows"
                    :key="row.id"
                    class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/60"
                >
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">
                        {{
                            row.human_message ||
                            'Acción registrada en bitácora.'
                        }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ row.user_name || 'Sistema' }} ·
                        {{ tModule(row.module) }} · {{ row.action }} ·
                        {{ formatDateTimeMx(row.created_at) }} ·
                        {{ row.ip_address || 'Sin IP' }}
                    </p>
                    <details
                        class="mt-2 rounded-xl bg-zinc-50 p-2 text-xs dark:bg-zinc-900"
                    >
                        <summary class="cursor-pointer font-medium">
                            Ver detalle técnico
                        </summary>
                        <div class="mt-2 grid gap-2 md:grid-cols-2">
                            <pre
                                class="overflow-auto rounded bg-white p-2 dark:bg-zinc-950"
                            >
Anterior: {{ parseJson(row.old_values) ?? '—' }}</pre
                            >
                            <pre
                                class="overflow-auto rounded bg-white p-2 dark:bg-zinc-950"
                            >
Nuevo: {{ parseJson(row.new_values) ?? '—' }}</pre
                            >
                        </div>
                    </details>
                </article>
            </div>

            <div
                class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/40"
            >
                <p class="text-sm text-zinc-500">
                    Total: {{ props.page.total }}
                </p>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        :disabled="props.page.current_page <= 1"
                        @click="goPage(props.page.current_page - 1)"
                        >Anterior</Button
                    >
                    <Button
                        variant="outline"
                        :disabled="
                            props.page.current_page >= props.page.last_page
                        "
                        @click="goPage(props.page.current_page + 1)"
                        >Siguiente</Button
                    >
                </div>
            </div>
        </section>
    </AppLayout>
</template>
