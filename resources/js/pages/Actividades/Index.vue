<script setup lang="ts">
import { onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    useActividadCrud,
    type ActivityRow,
    type UserOption,
} from '@/composables/crud/useActividadCrud';
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
    PlusCircle,
    Search,
    Pencil,
    CheckCircle2,
    Ban,
    Trash2,
    Clock3,
    Play,
} from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DateTimePicker from '@/components/ui/DateTimePicker.vue';
import StatusFlow from '@/components/ui/StatusFlow.vue';
import { formatDateTimeMx } from '@/lib/dates';
import { tActivityStatus, tPriority } from '@/lib/labels';
import { swalConfirm, swalToast } from '@/lib/swal';

const props = defineProps<{
    rows: ActivityRow[];
    page: { current_page: number; last_page: number; total: number };
    filters: { q?: string; status?: string; priority?: string };
    users: UserOption[];
    patients?: { id: number; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Actividades', href: '/actividades' },
];

const {
    form,
    isOpen,
    isEditing,
    openCreate,
    openEdit,
    closeModal,
    submit,
    complete,
    cancel,
    destroyActivity,
} = useActividadCrud();

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('new') === '1') {
        const patientId = params.get('patient_persona_id');
        openCreate(patientId ? { patient_persona_id: Number(patientId) } : undefined);
    }
});

const applyFilters = (extra: Record<string, string | number>) => {
    router.get(
        '/actividades',
        { ...props.filters, ...extra },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};

const goPage = (page: number) => applyFilters({ page });

const startActivity = async (row: ActivityRow) => {
    const ok = await swalConfirm('¿Iniciar actividad?', row.title, 'Sí, iniciar');
    if (!ok) return;
    router.patch(`/actividades/${row.id}/start`, {}, {
        preserveScroll: true,
        onSuccess: () => swalToast('Actividad iniciada', 'success'),
    });
};

const advanceActivity = (row: ActivityRow) => {
    if (row.status === 'pending') {
        startActivity(row);
        return;
    }
    if (row.status === 'in_progress') {
        complete(row);
    }
};

const priorityClass = (priority: ActivityRow['priority']) =>
    ({
        low: 'bg-muted text-foreground',
        medium: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
        high: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        urgent: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    })[priority];

const statusClass = (status: ActivityRow['status']) =>
    ({
        pending:
            'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-300',
        in_progress:
            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
        on_hold:
            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
        completed:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        cancelled:
            'bg-muted text-foreground',
        overdue:
            'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    })[status];
</script>

<template>
    <Head title="Actividades" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-3xl bg-card p-6 shadow-xl transition-all duration-300"
        >
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-semibold text-foreground"
                    >
                        Actividades internas
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Las actividades sirven para dar seguimiento operativo o
                        clínico: confirmar citas, contactar pacientes, revisar
                        pagos, preparar ejercicios o dar seguimiento a
                        evolución.
                    </p>
                </div>
                <Button
                    class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
                    @click="() => openCreate()"
                >
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nueva actividad
                </Button>
            </div>

            <div
                class="rounded-2xl border border-border bg-muted/60 p-4"
            >
                <div class="grid gap-3 md:grid-cols-4">
                    <div class="relative md:col-span-2">
                        <Search
                            class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-muted-foreground"
                        />
                        <Input
                            class="pl-9"
                            :default-value="props.filters.q ?? ''"
                            placeholder="Buscar actividad"
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
                        :model-value="props.filters.status ?? null"
                        :options="[
                            { value: null, label: 'Todos los estados' },
                            {
                                value: 'pending',
                                label: tActivityStatus('pending'),
                            },
                            {
                                value: 'in_progress',
                                label: tActivityStatus('in_progress'),
                            },
                            {
                                value: 'on_hold',
                                label: tActivityStatus('on_hold'),
                            },
                            {
                                value: 'completed',
                                label: tActivityStatus('completed'),
                            },
                            {
                                value: 'cancelled',
                                label: tActivityStatus('cancelled'),
                            },
                            {
                                value: 'overdue',
                                label: tActivityStatus('overdue'),
                            },
                        ]"
                        placeholder="Filtrar estado"
                        clearable
                        @update:model-value="
                            (value) =>
                                applyFilters({ status: value ?? '', page: 1 })
                        "
                    />
                    <SearchableSelect
                        :model-value="props.filters.priority ?? null"
                        :options="[
                            { value: null, label: 'Todas las prioridades' },
                            { value: 'low', label: tPriority('low') },
                            { value: 'medium', label: tPriority('medium') },
                            { value: 'high', label: tPriority('high') },
                            { value: 'urgent', label: tPriority('urgent') },
                        ]"
                        placeholder="Filtrar prioridad"
                        clearable
                        @update:model-value="
                            (value) =>
                                applyFilters({ priority: value ?? '', page: 1 })
                        "
                    />
                </div>
            </div>

            <div
                v-if="props.rows.length === 0"
                class="rounded-3xl border border-dashed border-border bg-muted p-10 text-center"
            >
                <h3
                    class="text-lg font-semibold text-foreground"
                >
                    Sin actividades
                </h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Crea una actividad o cambia filtros para encontrar
                    resultados.
                </p>
            </div>

            <div v-else class="grid gap-3 lg:grid-cols-2">
                <article
                    v-for="row in props.rows"
                    :key="row.id"
                    class="rounded-2xl border border-border bg-card p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                >
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3
                                    class="text-base font-semibold text-foreground"
                                >
                                    {{ row.title }}
                                </h3>
                                <p
                                    class="text-xs text-muted-foreground"
                                >
                                    Responsable:
                                    {{ row.responsible_name ?? 'Sin asignar' }}
                                </p>
                                <p
                                    class="text-xs text-muted-foreground"
                                >
                                    Paciente relacionado:
                                    {{ row.patient_name ?? 'No relacionado' }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Badge
                                    class="rounded-full px-3 py-1 text-xs"
                                    :class="priorityClass(row.priority)"
                                    >{{ tPriority(row.priority) }}</Badge
                                >
                                <Badge
                                    class="rounded-full px-3 py-1 text-xs"
                                    :class="statusClass(row.status)"
                                    >{{ tActivityStatus(row.status) }}</Badge
                                >
                            </div>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            {{
                                row.description || 'Sin descripción adicional.'
                            }}
                        </p>

                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span
                                class="inline-flex items-center rounded-full bg-muted px-2 py-1"
                                ><Clock3 class="mr-1 h-3 w-3" />{{
                                    row.due_date
                                        ? formatDateTimeMx(row.due_date)
                                        : 'Sin fecha límite'
                                }}</span
                            >
                            <span
                                v-if="row.is_overdue"
                                class="rounded-full bg-rose-100 px-2 py-1 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300"
                                >Actividad vencida</span
                            >
                        </div>
                        <StatusFlow
                            :current="row.status"
                            :steps="[
                                { value: 'pending', label: 'Pendiente' },
                                { value: 'in_progress', label: 'En proceso' },
                                { value: 'completed', label: 'Completada' },
                            ]"
                            :can-advance="
                                ['pending', 'in_progress'].includes(row.status)
                            "
                            :advance-label="
                                row.status === 'pending'
                                    ? 'Iniciar actividad'
                                    : 'Completar actividad'
                            "
                            :actions="[
                                {
                                    key: 'cancel',
                                    label: 'Cancelar actividad',
                                    variant: 'destructive',
                                    disabled: [
                                        'completed',
                                        'cancelled',
                                    ].includes(row.status),
                                },
                            ]"
                            @advance="advanceActivity(row)"
                            @action="cancel(row)"
                        />

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
                                :disabled="row.status === 'completed'"
                                @click="complete(row)"
                                ><CheckCircle2
                                    class="mr-2 h-4 w-4"
                                />Completar</Button
                            >
                            <Button
                                variant="outline"
                                class="rounded-xl"
                                :disabled="row.status === 'cancelled'"
                                @click="cancel(row)"
                                ><Ban class="mr-2 h-4 w-4" />Cancelar</Button
                            >
                            <Button
                                variant="destructive"
                                class="rounded-xl"
                                @click="destroyActivity(row.id)"
                                ><Trash2 class="mr-2 h-4 w-4" />Eliminar</Button
                            >
                        </div>
                    </div>
                </article>
            </div>

            <div
                class="flex items-center justify-between rounded-2xl border border-border bg-muted px-4 py-3"
            >
                <p class="text-sm text-muted-foreground">
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
                class="max-h-[90vh] max-w-4xl overflow-y-auto rounded-3xl border-none bg-card shadow-2xl"
            >
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? 'Editar actividad' : 'Nueva actividad'
                    }}</DialogTitle>
                    <DialogDescription
                        >Captura la información y guarda los
                        cambios.</DialogDescription
                    >
                </DialogHeader>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <Label for="title">Título</Label>
                        <Input id="title" v-model="form.title" />
                        <p
                            v-if="form.errors.title"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="priority">Prioridad</Label>
                        <SearchableSelect
                            id="priority"
                            v-model="form.priority"
                            :options="[
                                { value: 'low', label: tPriority('low') },
                                { value: 'medium', label: tPriority('medium') },
                                { value: 'high', label: tPriority('high') },
                                { value: 'urgent', label: tPriority('urgent') },
                            ]"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="status">Estado</Label>
                        <SearchableSelect
                            id="status"
                            v-model="form.status"
                            :options="[
                                {
                                    value: 'pending',
                                    label: tActivityStatus('pending'),
                                },
                                {
                                    value: 'in_progress',
                                    label: tActivityStatus('in_progress'),
                                },
                                {
                                    value: 'on_hold',
                                    label: tActivityStatus('on_hold'),
                                },
                                {
                                    value: 'completed',
                                    label: tActivityStatus('completed'),
                                },
                                {
                                    value: 'cancelled',
                                    label: tActivityStatus('cancelled'),
                                },
                                {
                                    value: 'overdue',
                                    label: tActivityStatus('overdue'),
                                },
                            ]"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="responsible">Responsable</Label>
                        <SearchableSelect
                            id="responsible"
                            v-model="form.responsible_user_id"
                            :options="[
                                { value: null, label: 'Sin asignar' },
                                ...props.users.map((user) => ({
                                    value: user.id,
                                    label: user.name,
                                })),
                            ]"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label>Paciente relacionado</Label>
                        <SearchableSelect
                            v-model="form.patient_persona_id"
                            :options="[
                                { value: null, label: 'Sin relación' },
                                ...(props.patients ?? []).map((patient) => ({
                                    value: patient.id,
                                    label: patient.label,
                                })),
                            ]"
                        />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="due_date">Fecha límite</Label>
                        <DateTimePicker id="due_date" v-model="form.due_date" />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="description">Descripción</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="min-h-24 w-full rounded-2xl border border-input bg-card p-3"
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
