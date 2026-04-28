<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useCitaCrud, type CitaRow } from '@/composables/crud/useCitaCrud';
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
import { CalendarPlus, Search, Pencil, Trash2, Ban } from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DateTimePicker from '@/components/ui/DateTimePicker.vue';
import StatusFlow from '@/components/ui/StatusFlow.vue';
import { formatDateTimeMx } from '@/lib/dates';
import { tAppointmentStatus } from '@/lib/labels';

const props = defineProps<{
    rows: CitaRow[];
    page: { current_page: number; last_page: number; total: number };
    filters: { q?: string; status?: string };
    lookups: {
        patients: { id: number; label: string }[];
        therapists: { id: number; label: string }[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Agenda', href: '/citas' }];
const {
    form,
    isOpen,
    editingId,
    can,
    moduleEnabled,
    applyFilters,
    openCreate,
    openEdit,
    closeModal,
    submit,
    cancelCita,
    markNoShow,
    advanceStatus,
    destroyCita,
} = useCitaCrud(props.filters);
const isEditing = computed(() => editingId.value !== null);

const statusClass = (s: string) =>
    ({
        scheduled:
            'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
        confirmed:
            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
        arrived:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        no_show:
            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        cancelled:
            'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
        done: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
    })[s] ?? 'bg-zinc-100 text-zinc-700';
</script>

<template>
    <Head title="Agenda" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950"
        >
            <div
                v-if="!moduleEnabled"
                class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
            >
                Módulo de agenda deshabilitado.
            </div>
            <template v-else>
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h1
                            class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100"
                        >
                            Agenda de citas
                        </h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Coordina horarios de pacientes y fisioterapeutas con
                            control de asistencia y seguimiento.
                        </p>
                    </div>
                    <Button
                        v-if="can('appointments.create')"
                        class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
                        @click="openCreate"
                        ><CalendarPlus class="mr-2 h-4 w-4" />Nueva cita</Button
                    >
                </div>

                <div
                    class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"
                >
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="relative md:col-span-2">
                            <Search
                                class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-zinc-400"
                            /><Input
                                class="pl-9"
                                :default-value="props.filters.q ?? ''"
                                placeholder="Buscar por paciente o terapeuta"
                                @change="
                                    (e) =>
                                        applyFilters({
                                            q: (e.target as HTMLInputElement)
                                                .value,
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
                                    value: 'scheduled',
                                    label: tAppointmentStatus('scheduled'),
                                },
                                {
                                    value: 'confirmed',
                                    label: tAppointmentStatus('confirmed'),
                                },
                                {
                                    value: 'arrived',
                                    label: tAppointmentStatus('arrived'),
                                },
                                {
                                    value: 'no_show',
                                    label: tAppointmentStatus('no_show'),
                                },
                                {
                                    value: 'cancelled',
                                    label: tAppointmentStatus('cancelled'),
                                },
                                {
                                    value: 'done',
                                    label: tAppointmentStatus('done'),
                                },
                            ]"
                            placeholder="Filtrar estado"
                            clearable
                            @update:model-value="
                                (value) =>
                                    applyFilters({
                                        status: value ?? '',
                                        page: 1,
                                    })
                            "
                        />
                    </div>
                </div>

                <div
                    v-if="props.rows.length === 0"
                    class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/40"
                >
                    Sin citas para mostrar.
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
                                        class="font-semibold text-zinc-900 dark:text-zinc-100"
                                    >
                                        {{ row.patient_name }}
                                    </h3>
                                    <Badge
                                        class="rounded-full px-3 py-1 text-xs"
                                        :class="statusClass(row.status)"
                                        >{{
                                            tAppointmentStatus(row.status)
                                        }}</Badge
                                    >
                                </div>
                                <p
                                    class="text-sm text-zinc-500 dark:text-zinc-400"
                                >
                                    Terapeuta: {{ row.therapist_name }}
                                </p>
                                <p
                                    class="text-xs text-zinc-500 dark:text-zinc-400"
                                >
                                    {{ formatDateTimeMx(row.start_at) }} →
                                    {{ formatDateTimeMx(row.end_at) }}
                                </p>
                                <StatusFlow
                                    class="mt-2"
                                    :current="row.status"
                                    :steps="[
                                        {
                                            value: 'scheduled',
                                            label: 'Programada',
                                        },
                                        {
                                            value: 'confirmed',
                                            label: 'Confirmada',
                                        },
                                        { value: 'arrived', label: 'Llegó' },
                                        { value: 'done', label: 'Finalizada' },
                                    ]"
                                    :can-advance="
                                        ![
                                            'cancelled',
                                            'done',
                                            'no_show',
                                        ].includes(row.status)
                                    "
                                    :advance-label="
                                        row.status === 'scheduled'
                                            ? 'Avanzar a confirmada'
                                            : row.status === 'confirmed'
                                              ? 'Marcar llegada'
                                              : 'Finalizar cita'
                                    "
                                    :actions="[
                                        {
                                            key: 'no_show',
                                            label: 'Marcar no asistió',
                                            variant: 'outline',
                                            disabled: [
                                                'cancelled',
                                                'done',
                                                'no_show',
                                            ].includes(row.status),
                                        },
                                        {
                                            key: 'cancel',
                                            label: 'Cancelar cita',
                                            variant: 'destructive',
                                            disabled: [
                                                'cancelled',
                                                'done',
                                            ].includes(row.status),
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
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-if="can('appointments.update')"
                                    variant="outline"
                                    class="rounded-xl"
                                    @click="openEdit(row)"
                                    ><Pencil
                                        class="mr-2 h-4 w-4"
                                    />Editar</Button
                                >
                                <Button
                                    v-if="can('appointments.cancel')"
                                    variant="outline"
                                    class="rounded-xl"
                                    @click="cancelCita(row)"
                                    ><Ban
                                        class="mr-2 h-4 w-4"
                                    />Cancelar</Button
                                >
                                <Button
                                    v-if="can('appointments.delete')"
                                    variant="destructive"
                                    class="rounded-xl"
                                    @click="destroyCita(row)"
                                    ><Trash2
                                        class="mr-2 h-4 w-4"
                                    />Eliminar</Button
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
                            @click="
                                applyFilters({
                                    page: props.page.current_page - 1,
                                })
                            "
                            >Anterior</Button
                        ><Button
                            variant="outline"
                            :disabled="
                                props.page.current_page >= props.page.last_page
                            "
                            @click="
                                applyFilters({
                                    page: props.page.current_page + 1,
                                })
                            "
                            >Siguiente</Button
                        >
                    </div>
                </div>
            </template>
        </section>

        <Dialog :open="isOpen" @update:open="closeModal">
            <DialogContent
                class="max-w-3xl rounded-3xl border-none bg-white shadow-2xl dark:bg-zinc-950"
            >
                <DialogHeader
                    ><DialogTitle>{{
                        isEditing ? 'Editar cita' : 'Nueva cita'
                    }}</DialogTitle
                    ><DialogDescription
                        >Captura la información y guarda los
                        cambios.</DialogDescription
                    ></DialogHeader
                >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label>Paciente</Label
                        ><SearchableSelect
                            v-model="form.patient_persona_id"
                            :options="[
                                { value: '', label: 'Seleccionar paciente' },
                                ...props.lookups.patients.map((p) => ({
                                    value: p.id,
                                    label: p.label,
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
                        <Label>Terapeuta</Label
                        ><SearchableSelect
                            v-model="form.therapist_user_id"
                            :options="[
                                { value: '', label: 'Seleccionar terapeuta' },
                                ...props.lookups.therapists.map((t) => ({
                                    value: t.id,
                                    label: t.label,
                                })),
                            ]"
                        />
                        <p
                            v-if="form.errors.therapist_user_id"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.therapist_user_id }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label>Inicio</Label
                        ><DateTimePicker v-model="form.start_at" />
                    </div>
                    <div class="space-y-2">
                        <Label>Fin</Label
                        ><DateTimePicker v-model="form.end_at" />
                    </div>
                    <div class="space-y-2">
                        <Label>Estado</Label
                        ><SearchableSelect
                            v-model="form.status"
                            :options="[
                                {
                                    value: 'scheduled',
                                    label: tAppointmentStatus('scheduled'),
                                },
                                {
                                    value: 'confirmed',
                                    label: tAppointmentStatus('confirmed'),
                                },
                                {
                                    value: 'arrived',
                                    label: tAppointmentStatus('arrived'),
                                },
                                {
                                    value: 'no_show',
                                    label: tAppointmentStatus('no_show'),
                                },
                                {
                                    value: 'cancelled',
                                    label: tAppointmentStatus('cancelled'),
                                },
                                {
                                    value: 'done',
                                    label: tAppointmentStatus('done'),
                                },
                            ]"
                        />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label>Notas</Label
                        ><textarea
                            v-model="form.notes"
                            class="min-h-20 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900"
                        />
                    </div>
                </div>
                <DialogFooter
                    ><Button variant="outline" @click="closeModal"
                        >Cancelar</Button
                    ><Button :disabled="form.processing" @click="submit">{{
                        form.processing ? 'Guardando...' : 'Guardar'
                    }}</Button></DialogFooter
                >
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
