<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { usePagoCrud, type PagoRow } from '@/composables/crud/usePagoCrud';
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
import { Wallet, Search, Pencil, Ban, TrendingUp, Clock, X } from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DateTimePicker from '@/components/ui/DateTimePicker.vue';
import { formatDateTimeMx } from '@/lib/dates';

const props = defineProps<{
    rows: PagoRow[];
    page: { current_page: number; last_page: number; total: number };
    filters: {
        q?: string;
        status?: string;
        payment_method?: string;
        patient_persona_id?: string | number;
        date_from?: string;
        date_to?: string;
    };
    summary: {
        total_count: number;
        paid_amount: number;
        pending_amount: number;
        cancelled_count: number;
    };
    lookups: { patients: { id: number; label: string }[] };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pagos', href: '/pagos' }];
const { form, isOpen, editingId, can, moduleEnabled, applyFilters, openCreate, openEdit, closeModal, submit, cancelPago } =
    usePagoCrud(props.filters);
const isEditing = computed(() => editingId.value !== null);

const fromExpediente = computed(() => Boolean(props.filters.patient_persona_id));
const patientName = computed(() => {
    const id = props.filters.patient_persona_id;
    if (!id) return null;
    return props.lookups.patients.find((p) => p.id === Number(id))?.label ?? null;
});

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('new') === '1') {
        const patientId = params.get('patient_persona_id');
        openCreate(patientId ? { patient_persona_id: Number(patientId) } : undefined);
    }
});

const money = (value: number, currency = 'MXN') =>
    Number(value ?? 0).toLocaleString('es-MX', { style: 'currency', currency: currency || 'MXN' });

const statusConfig = (status: string) => {
    const map: Record<string, { label: string; class: string }> = {
        paid: {
            label: 'Pagado',
            class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        },
        pending: {
            label: 'Pendiente',
            class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        },
        cancelled: {
            label: 'Cancelado',
            class: 'bg-muted text-muted-foreground',
        },
        failed: {
            label: 'Fallido',
            class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
        },
        refunded: {
            label: 'Reembolsado',
            class: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
        },
    };
    return map[status] ?? { label: status, class: '' };
};

const methodLabel = (method?: string | null) => {
    const map: Record<string, string> = {
        efectivo: 'Efectivo',
        transferencia: 'Transferencia',
        tarjeta_terminal: 'Tarjeta / Terminal',
        deposito: 'Depósito',
        otro: 'Otro',
    };
    return method ? (map[method] ?? method) : '—';
};
</script>

<template>
    <Head title="Pagos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-3xl bg-card p-6 shadow-xl transition-all duration-300"
        >
            <div
                v-if="!moduleEnabled"
                class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
            >
                Módulo de pagos deshabilitado.
            </div>

            <template v-else>
                <!-- Banner paciente desde expediente -->
                <div
                    v-if="patientName"
                    class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-800/50 dark:bg-sky-950/40 dark:text-sky-200"
                >
                    Pagos registrados para: <strong>{{ patientName }}</strong>
                </div>

                <!-- Header -->
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h1
                            class="text-2xl font-semibold text-foreground"
                        >
                            Pagos
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Registra pagos realizados por pacientes por sesiones,
                            recetas u otros conceptos.
                        </p>
                    </div>
                    <Button
                        v-if="can('payments.create')"
                        class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
                        @click="
                            () =>
                                openCreate(
                                    fromExpediente && props.filters.patient_persona_id
                                        ? {
                                              patient_persona_id: Number(
                                                  props.filters.patient_persona_id,
                                              ),
                                          }
                                        : undefined,
                                )
                        "
                    >
                        <Wallet class="mr-2 h-4 w-4" />Registrar pago
                    </Button>
                </div>

                <!-- Cards resumen -->
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900/30 dark:bg-emerald-950/20"
                    >
                        <div class="flex items-center gap-2">
                            <TrendingUp class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            <p class="text-xs text-emerald-700 dark:text-emerald-400">
                                Total cobrado
                            </p>
                        </div>
                        <p
                            class="mt-1 text-xl font-bold text-emerald-800 dark:text-emerald-200"
                        >
                            {{ money(props.summary.paid_amount) }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900/30 dark:bg-amber-950/20"
                    >
                        <div class="flex items-center gap-2">
                            <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                Pendiente de cobro
                            </p>
                        </div>
                        <p
                            class="mt-1 text-xl font-bold text-amber-800 dark:text-amber-200"
                        >
                            {{ money(props.summary.pending_amount) }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-border bg-muted p-4"
                    >
                        <div class="flex items-center gap-2">
                            <Wallet class="h-4 w-4 text-muted-foreground" />
                            <p class="text-xs text-muted-foreground">
                                Total de pagos
                            </p>
                        </div>
                        <p
                            class="mt-1 text-xl font-bold text-foreground"
                        >
                            {{ props.summary.total_count }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-border bg-muted p-4"
                    >
                        <div class="flex items-center gap-2">
                            <X class="h-4 w-4 text-muted-foreground" />
                            <p class="text-xs text-muted-foreground">
                                Cancelados
                            </p>
                        </div>
                        <p
                            class="mt-1 text-xl font-bold text-muted-foreground"
                        >
                            {{ props.summary.cancelled_count }}
                        </p>
                    </article>
                </div>

                <!-- Filtros -->
                <div
                    class="rounded-2xl border border-border bg-muted/60 p-4"
                >
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="relative md:col-span-1">
                            <Search
                                class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-muted-foreground"
                            />
                            <Input
                                class="pl-9"
                                :default-value="props.filters.q ?? ''"
                                placeholder="Buscar por concepto, paciente, referencia…"
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
                            :model-value="
                                props.filters.patient_persona_id
                                    ? Number(props.filters.patient_persona_id)
                                    : null
                            "
                            :options="[
                                { value: null, label: 'Todos los pacientes' },
                                ...props.lookups.patients.map((p) => ({
                                    value: p.id,
                                    label: p.label,
                                })),
                            ]"
                            placeholder="Filtrar por paciente"
                            clearable
                            @update:model-value="
                                (v) => applyFilters({ patient_persona_id: v ?? '', page: 1 })
                            "
                        />
                        <SearchableSelect
                            :model-value="props.filters.status ?? null"
                            :options="[
                                { value: null, label: 'Todos los estados' },
                                { value: 'paid', label: 'Pagado' },
                                { value: 'pending', label: 'Pendiente' },
                                { value: 'cancelled', label: 'Cancelado' },
                            ]"
                            placeholder="Estado"
                            clearable
                            @update:model-value="
                                (v) => applyFilters({ status: v ?? '', page: 1 })
                            "
                        />
                        <SearchableSelect
                            :model-value="props.filters.payment_method ?? null"
                            :options="[
                                { value: null, label: 'Todos los métodos' },
                                { value: 'efectivo', label: 'Efectivo' },
                                { value: 'transferencia', label: 'Transferencia' },
                                { value: 'tarjeta_terminal', label: 'Tarjeta / Terminal' },
                                { value: 'deposito', label: 'Depósito' },
                                { value: 'otro', label: 'Otro' },
                            ]"
                            placeholder="Método de pago"
                            clearable
                            @update:model-value="
                                (v) => applyFilters({ payment_method: v ?? '', page: 1 })
                            "
                        />
                        <Input
                            type="date"
                            :default-value="props.filters.date_from ?? ''"
                            @change="
                                (e) =>
                                    applyFilters({
                                        date_from: (e.target as HTMLInputElement).value,
                                        page: 1,
                                    })
                            "
                        />
                        <Input
                            type="date"
                            :default-value="props.filters.date_to ?? ''"
                            @change="
                                (e) =>
                                    applyFilters({
                                        date_to: (e.target as HTMLInputElement).value,
                                        page: 1,
                                    })
                            "
                        />
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    v-if="props.rows.length === 0"
                    class="rounded-3xl border border-dashed border-border bg-muted p-10 text-center"
                >
                    <Wallet class="mx-auto mb-3 h-8 w-8 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">
                        No se encontraron pagos con estos filtros.
                    </p>
                    <Button
                        variant="outline"
                        class="mt-4 rounded-xl text-xs"
                        @click="
                            applyFilters({
                                q: '',
                                status: '',
                                payment_method: '',
                                patient_persona_id: '',
                                date_from: '',
                                date_to: '',
                                page: 1,
                            })
                        "
                    >
                        Limpiar filtros
                    </Button>
                </div>

                <!-- Lista -->
                <div v-else class="space-y-3">
                    <article
                        v-for="row in props.rows"
                        :key="row.id"
                        class="rounded-2xl border border-border bg-card p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                        :class="{ 'opacity-60': row.status === 'cancelled' }"
                    >
                        <div
                            class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="text-lg font-bold text-foreground"
                                        >{{ money(row.amount, row.currency) }}</span
                                    >
                                    <Badge
                                        class="rounded-full px-3 py-0.5 text-xs"
                                        :class="statusConfig(row.status).class"
                                    >
                                        {{ statusConfig(row.status).label }}
                                    </Badge>
                                </div>
                                <p
                                    class="mt-0.5 text-sm font-medium text-foreground"
                                >
                                    {{ row.concept || 'Sin concepto' }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    <span v-if="row.patient_name"
                                        >{{ row.patient_name }} ·
                                    </span>
                                    {{ methodLabel(row.payment_method) }}
                                    <span v-if="row.paid_at">
                                        · {{ formatDateTimeMx(row.paid_at) }}</span
                                    >
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Ref: {{ row.reference || '—' }}
                                    <span v-if="row.created_by_name">
                                        · Registrado por {{ row.created_by_name }}</span
                                    >
                                </p>
                            </div>

                            <div
                                v-if="row.status !== 'cancelled'"
                                class="flex flex-shrink-0 flex-wrap gap-2"
                            >
                                <Button
                                    v-if="can('payments.update')"
                                    variant="outline"
                                    class="rounded-xl"
                                    @click="openEdit(row)"
                                >
                                    <Pencil class="mr-1.5 h-4 w-4" />Editar
                                </Button>
                                <Button
                                    v-if="can('payments.update')"
                                    variant="outline"
                                    class="rounded-xl border-rose-200 text-rose-700 hover:bg-rose-50 dark:border-rose-800/50 dark:text-rose-400 dark:hover:bg-rose-950/30"
                                    @click="cancelPago(row)"
                                >
                                    <Ban class="mr-1.5 h-4 w-4" />Cancelar
                                </Button>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Paginación -->
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
                            @click="applyFilters({ page: props.page.current_page - 1 })"
                            >Anterior</Button
                        >
                        <Button
                            variant="outline"
                            :disabled="props.page.current_page >= props.page.last_page"
                            @click="applyFilters({ page: props.page.current_page + 1 })"
                            >Siguiente</Button
                        >
                    </div>
                </div>
            </template>
        </section>

        <!-- Modal -->
        <Dialog :open="isOpen" @update:open="closeModal">
            <DialogContent
                class="flex max-h-[94dvh] max-w-2xl flex-col rounded-3xl border-none bg-card shadow-2xl"
            >
                <DialogHeader class="flex-shrink-0">
                    <DialogTitle>{{
                        isEditing ? 'Editar pago' : 'Registrar pago'
                    }}</DialogTitle>
                    <DialogDescription>
                        Registra el pago que realizó el paciente.
                    </DialogDescription>
                </DialogHeader>

                <div class="flex-1 overflow-y-auto pr-1">
                    <div class="grid gap-4 md:grid-cols-2">
                        <!-- Paciente -->
                        <div class="space-y-2 md:col-span-2">
                            <Label>Paciente</Label>
                            <div
                                v-if="fromExpediente"
                                class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm text-sky-800 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-200"
                            >
                                Pago para: <strong>{{ patientName }}</strong>
                            </div>
                            <SearchableSelect
                                v-else
                                v-model="form.patient_persona_id"
                                :options="[
                                    { value: null, label: 'Sin paciente asociado' },
                                    ...props.lookups.patients.map((p) => ({
                                        value: p.id,
                                        label: p.label,
                                    })),
                                ]"
                                clearable
                            />
                        </div>

                        <!-- Concepto -->
                        <div class="space-y-2 md:col-span-2">
                            <Label>Concepto</Label>
                            <Input
                                v-model="form.concept"
                                placeholder="Ej: Sesión de fisioterapia, Receta, Consulta, Paquete…"
                            />
                            <p
                                v-if="form.errors.concept"
                                class="text-xs text-red-500"
                            >
                                {{ form.errors.concept }}
                            </p>
                        </div>

                        <!-- Monto y moneda -->
                        <div class="space-y-2">
                            <Label>Monto *</Label>
                            <Input
                                v-model="form.amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                placeholder="0.00"
                            />
                            <p
                                v-if="form.errors.amount"
                                class="text-xs text-red-500"
                            >
                                {{ form.errors.amount }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label>Moneda</Label>
                            <SearchableSelect
                                v-model="form.currency"
                                :options="[
                                    { value: 'MXN', label: 'Peso mexicano (MXN)' },
                                    { value: 'USD', label: 'Dólar (USD)' },
                                    { value: 'EUR', label: 'Euro (EUR)' },
                                ]"
                            />
                        </div>

                        <!-- Método de pago -->
                        <div class="space-y-2">
                            <Label>Método de pago</Label>
                            <SearchableSelect
                                v-model="form.payment_method"
                                :options="[
                                    { value: 'efectivo', label: 'Efectivo' },
                                    {
                                        value: 'transferencia',
                                        label: 'Transferencia bancaria',
                                    },
                                    {
                                        value: 'tarjeta_terminal',
                                        label: 'Tarjeta / Terminal',
                                    },
                                    { value: 'deposito', label: 'Depósito bancario' },
                                    { value: 'otro', label: 'Otro' },
                                ]"
                            />
                        </div>

                        <!-- Estado -->
                        <div class="space-y-2">
                            <Label>Estado</Label>
                            <SearchableSelect
                                v-model="form.status"
                                :options="[
                                    { value: 'paid', label: 'Pagado' },
                                    { value: 'pending', label: 'Pendiente' },
                                    { value: 'cancelled', label: 'Cancelado' },
                                ]"
                            />
                            <p
                                v-if="form.errors.status"
                                class="text-xs text-red-500"
                            >
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Fecha de pago -->
                        <div class="space-y-2 md:col-span-2">
                            <Label>Fecha de pago</Label>
                            <DateTimePicker v-model="form.paid_at" />
                        </div>

                        <!-- Referencia -->
                        <div class="space-y-2 md:col-span-2">
                            <Label
                                >Referencia
                                <span class="text-xs text-muted-foreground"
                                    >(se genera automáticamente si se deja
                                    vacío)</span
                                ></Label
                            >
                            <Input
                                v-model="form.reference"
                                placeholder="Ej: TRANSF-001, FOLIO-23…"
                            />
                        </div>

                        <!-- Notas -->
                        <div class="space-y-2 md:col-span-2">
                            <Label
                                >Notas
                                <span class="text-xs text-muted-foreground"
                                    >(opcional)</span
                                ></Label
                            >
                            <textarea
                                v-model="form.notes"
                                class="min-h-20 w-full rounded-2xl border border-input bg-card p-3 text-sm"
                                placeholder="Observaciones internas…"
                            />
                        </div>
                    </div>
                </div>

                <DialogFooter class="flex-shrink-0">
                    <Button variant="outline" @click="closeModal">Cancelar</Button>
                    <Button :disabled="form.processing" @click="submit">{{
                        form.processing
                            ? 'Guardando…'
                            : isEditing
                              ? 'Actualizar'
                              : 'Registrar pago'
                    }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
