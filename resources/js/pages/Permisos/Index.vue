<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    usePermisoCrud,
    type PermissionRow,
} from '@/composables/crud/usePermisoCrud';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { ShieldPlus, Search, Pencil, Trash2, Power } from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import { tGeneralStatus, tModule, tPermission } from '@/lib/labels';

const props = defineProps<{
    rows: PermissionRow[];
    page: { current_page: number; last_page: number; total: number };
    filters: { q?: string; status?: string; module?: string };
    moduleOptions: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Permisos', href: '/permisos' },
];

const {
    form,
    isOpen,
    isEditing,
    openCreate,
    openEdit,
    closeModal,
    submit,
    toggleStatus,
    destroyPermission,
} = usePermisoCrud();

const applyFilters = (extra: Record<string, string | number>) => {
    router.get(
        route('permisos.index'),
        { ...props.filters, ...extra },
        { preserveState: true, replace: true },
    );
};

const goPage = (page: number) => {
    applyFilters({ page });
};

const statusVariant = (status: PermissionRow['status']) =>
    status === 'active'
        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
        : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300';
</script>

<template>
    <Head title="Permisos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950"
        >
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Permisos por acción
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Administra los permisos que pueden asignarse a cada rol.
                    </p>
                </div>
                <Button
                    class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
                    @click="openCreate"
                >
                    <ShieldPlus class="mr-2 h-4 w-4" />
                    Nuevo permiso
                </Button>
            </div>

            <div
                class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"
            >
                <div class="grid gap-3 md:grid-cols-3">
                    <div class="relative md:col-span-2">
                        <Search
                            class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-zinc-400"
                        />
                        <Input
                            class="pl-9"
                            :default-value="props.filters.q ?? ''"
                            placeholder="Buscar por permiso"
                            @change="
                                (e) =>
                                    applyFilters({
                                        q: (e.target as HTMLInputElement).value,
                                        page: 1,
                                    })
                            "
                        />
                    </div>
                    <SearchableSelect
                        :model-value="props.filters.module ?? null"
                        :options="[
                            { value: null, label: 'Todos los módulos' },
                            ...props.moduleOptions.map((item) => ({
                                value: item,
                                label: tModule(item),
                            })),
                        ]"
                        placeholder="Filtrar módulo"
                        clearable
                        @update:model-value="
                            (value) =>
                                applyFilters({ module: value ?? '', page: 1 })
                        "
                    />
                    <SearchableSelect
                        :model-value="props.filters.status ?? null"
                        :options="[
                            { value: null, label: 'Todos los estados' },
                            { value: 'active', label: 'Activo' },
                            { value: 'inactive', label: 'Inactivo' },
                        ]"
                        placeholder="Filtrar estado"
                        clearable
                        @update:model-value="
                            (value) =>
                                applyFilters({ status: value ?? '', page: 1 })
                        "
                    />
                </div>
            </div>

            <div
                v-if="props.rows.length === 0"
                class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/40"
            >
                <h3
                    class="text-lg font-semibold text-zinc-800 dark:text-zinc-100"
                >
                    Sin permisos para mostrar
                </h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Intenta ajustar los filtros o crea un nuevo permiso.
                </p>
            </div>

            <div v-else class="space-y-3">
                <article
                    v-for="row in props.rows"
                    :key="row.id"
                    class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900/60"
                >
                    <div
                        class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <h3
                                    class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                                >
                                    {{ tPermission(row.slug) }}
                                </h3>
                                <Badge
                                    class="rounded-full px-3 py-1 text-xs"
                                    :class="statusVariant(row.status)"
                                    >{{ tGeneralStatus(row.status) }}</Badge
                                >
                            </div>
                            <p
                                class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"
                            >
                                {{ row.slug }}
                            </p>
                            <p
                                class="mt-2 inline-flex rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-700 dark:bg-sky-900/30 dark:text-sky-300"
                            >
                                Módulo: {{ tModule(row.module) }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                class="rounded-xl"
                                @click="openEdit(row)"
                                ><Pencil class="mr-2 h-4 w-4" />Editar</Button
                            >
                            <Button
                                variant="outline"
                                class="rounded-xl"
                                @click="toggleStatus(row)"
                                ><Power class="mr-2 h-4 w-4" />{{
                                    row.status === 'active'
                                        ? 'Desactivar'
                                        : 'Activar'
                                }}</Button
                            >
                            <Button
                                variant="destructive"
                                class="rounded-xl"
                                @click="destroyPermission(row.id)"
                                ><Trash2 class="mr-2 h-4 w-4" />Eliminar</Button
                            >
                        </div>
                    </div>
                </article>
            </div>

            <div
                class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/40"
            >
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
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

        <Dialog :open="isOpen" @update:open="closeModal">
            <DialogContent
                class="max-w-3xl rounded-3xl border-none bg-white shadow-2xl dark:bg-zinc-950"
            >
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? 'Editar permiso' : 'Nuevo permiso'
                    }}</DialogTitle>
                    <DialogDescription
                        >Captura la información y guarda los
                        cambios.</DialogDescription
                    >
                </DialogHeader>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="name">Nombre</Label>
                        <Input id="name" v-model="form.name" />
                        <p v-if="form.errors.name" class="text-xs text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="slug">Slug</Label>
                        <Input id="slug" v-model="form.slug" />
                        <p v-if="form.errors.slug" class="text-xs text-red-500">
                            {{ form.errors.slug }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="module">Módulo</Label>
                        <Input id="module" v-model="form.module" />
                    </div>
                    <div class="space-y-2">
                        <Label for="status">Estado</Label>
                        <SearchableSelect
                            id="status"
                            v-model="form.status"
                            :options="[
                                { value: 'active', label: 'Activo' },
                                { value: 'inactive', label: 'Inactivo' },
                            ]"
                        />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="description">Descripción</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="min-h-24 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="closeModal"
                        >Cancelar</Button
                    >
                    <Button :disabled="form.processing" @click="submit">{{
                        form.processing ? 'Guardando...' : 'Guardar'
                    }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
