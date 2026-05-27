<script setup lang="ts">
import { watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    useReporteCrud,
    type ReportFilters,
} from '@/composables/crud/useReporteCrud';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    ChartNoAxesCombined,
    CalendarRange,
    Users,
    Wallet,
    ListChecks,
    Activity,
    DownloadCloud,
    AlertCircle,
} from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import {
    tActivityStatus,
    tAppointmentStatus,
    tPaymentStatus,
} from '@/lib/labels';

type StatusRow = { status: string; total: number; amount?: number };
type TherapistRow = {
    therapist_user_id: number;
    therapist_name: string;
    total: number;
};

const props = defineProps<{
    filters: ReportFilters;
    summary: {
        appointments_period: number;
        sessions_period: number;
        active_patients: number;
        new_patients_period: number;
        income_period: number;
        total_payments: number;
        pending_amount: number;
        activities_overdue: number;
    };
    appointmentsByStatus: StatusRow[];
    sessionsSummary: { total: number };
    patientsSummary: { active: number; new: number };
    paymentsSummary: { income: number; by_status: StatusRow[]; total: number; pending: number };
    activitiesSummary: { overdue: number; by_status: StatusRow[] };
    therapistProductivity: TherapistRow[];
    lookups: {
        therapists: { id: number; label: string }[];
        patients: { id: number; label: string }[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reportes', href: '/reportes' }];
const { form, loading, dateError, moduleEnabled, applyFilters, resetFilters } =
    useReporteCrud(props.filters);

// Debounce helper
let debounceTimer: ReturnType<typeof setTimeout> | null = null;
const debounced = (fn: () => void, ms = 400) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fn, ms);
};

// Watch every filter field — apply with debounce
watch(
    () => [
        form.value.start_date,
        form.value.end_date,
        form.value.appointment_status,
        form.value.payment_status,
        form.value.activity_status,
        form.value.therapist_user_id,
        form.value.patient_persona_id,
    ],
    () => debounced(applyFilters, 400),
);

const buildExportQuery = () => {
    const params = new URLSearchParams();
    params.set('start_date', form.value.start_date);
    params.set('end_date', form.value.end_date);
    if (form.value.appointment_status) params.set('appointment_status', form.value.appointment_status);
    if (form.value.payment_status) params.set('payment_status', form.value.payment_status);
    if (form.value.activity_status) params.set('activity_status', form.value.activity_status);
    if (form.value.therapist_user_id) params.set('therapist_user_id', String(form.value.therapist_user_id));
    if (form.value.patient_persona_id) params.set('patient_persona_id', String(form.value.patient_persona_id));
    return params.toString();
};

const money = (value: number) =>
    Number(value ?? 0).toLocaleString('es-MX', {
        style: 'currency',
        currency: 'MXN',
    });
const maxTotal = (rows: Array<{ total: number }>) =>
    Math.max(...rows.map((r) => r.total), 1);
const barWidth = (total: number, rows: Array<{ total: number }>) =>
    `${Math.max(8, Math.round((total / maxTotal(rows)) * 100))}%`;
</script>

<template>
    <Head title="Reportes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-3xl bg-card p-6 shadow-xl transition-all duration-300"
        >
            <div
                v-if="!moduleEnabled"
                class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
            >
                Módulo de reportes deshabilitado.
            </div>

            <template v-else>
                <!-- Header -->
                <div
                    class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h1
                            class="text-2xl font-semibold text-foreground"
                        >
                            Reportes
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Indicadores operativos y administrativos del periodo
                            seleccionado.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            class="rounded-full bg-sky-100 px-3 py-1 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300"
                        >
                            <ChartNoAxesCombined class="mr-1 h-3.5 w-3.5" />
                            Base operativa
                        </Badge>
                        <a
                            :href="`/reportes/export/excel?${buildExportQuery()}`"
                            class="inline-flex items-center rounded-xl border border-border bg-card px-3 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                        >
                            <DownloadCloud class="mr-2 h-4 w-4 text-emerald-600" />Excel
                        </a>
                        <a
                            :href="`/reportes/export/pdf?${buildExportQuery()}`"
                            class="inline-flex items-center rounded-xl border border-border bg-card px-3 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                        >
                            <DownloadCloud class="mr-2 h-4 w-4 text-rose-600" />PDF
                        </a>
                    </div>
                </div>

                <!-- Filtros — tiempo real, sin botón Aplicar -->
                <div
                    class="rounded-2xl border border-border bg-muted/60 p-4"
                >
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Desde</p>
                            <DatePicker v-model="form.start_date" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Hasta</p>
                            <DatePicker v-model="form.end_date" />
                        </div>
                        <SearchableSelect
                            v-model="form.therapist_user_id"
                            :options="[
                                { value: null, label: 'Todos los terapeutas' },
                                ...props.lookups.therapists.map((item) => ({
                                    value: item.id,
                                    label: item.label,
                                })),
                            ]"
                            clearable
                        />
                        <SearchableSelect
                            v-model="form.patient_persona_id"
                            :options="[
                                { value: null, label: 'Todos los pacientes' },
                                ...props.lookups.patients.map((item) => ({
                                    value: item.id,
                                    label: item.label,
                                })),
                            ]"
                            clearable
                        />
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Estado de cita</p>
                            <SearchableSelect
                                v-model="form.appointment_status"
                                :options="[
                                    { value: '', label: 'Todos los estados' },
                                    { value: 'scheduled', label: tAppointmentStatus('scheduled') },
                                    { value: 'confirmed', label: tAppointmentStatus('confirmed') },
                                    { value: 'done', label: tAppointmentStatus('done') },
                                    { value: 'cancelled', label: tAppointmentStatus('cancelled') },
                                    { value: 'no_show', label: tAppointmentStatus('no_show') },
                                ]"
                                clearable
                            />
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Estado de pago</p>
                            <SearchableSelect
                                v-model="form.payment_status"
                                :options="[
                                    { value: '', label: 'Todos los estados' },
                                    { value: 'pending', label: tPaymentStatus('pending') },
                                    { value: 'paid', label: tPaymentStatus('paid') },
                                    { value: 'cancelled', label: tPaymentStatus('cancelled') },
                                ]"
                                clearable
                            />
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Estado de actividad</p>
                            <SearchableSelect
                                v-model="form.activity_status"
                                :options="[
                                    { value: '', label: 'Todos los estados' },
                                    { value: 'pending', label: tActivityStatus('pending') },
                                    { value: 'in_progress', label: tActivityStatus('in_progress') },
                                    { value: 'completed', label: tActivityStatus('completed') },
                                    { value: 'cancelled', label: tActivityStatus('cancelled') },
                                    { value: 'overdue', label: tActivityStatus('overdue') },
                                ]"
                                clearable
                            />
                        </div>
                    </div>

                    <!-- Error de fechas -->
                    <div
                        v-if="dateError"
                        class="mt-3 flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300"
                    >
                        <AlertCircle class="h-4 w-4 flex-shrink-0" />
                        {{ dateError }}
                    </div>

                    <!-- Solo Limpiar filtros -->
                    <div class="mt-3 flex items-center gap-3">
                        <span v-if="loading" class="text-xs text-muted-foreground">
                            <Activity class="mr-1 inline h-3.5 w-3.5 animate-spin" />
                            Actualizando…
                        </span>
                        <Button
                            variant="outline"
                            class="rounded-xl"
                            :disabled="loading"
                            @click="resetFilters"
                        >
                            Limpiar filtros
                        </Button>
                    </div>
                </div>

                <!-- Cards resumen -->
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        class="rounded-2xl border border-sky-100 bg-card p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-sky-900/30 dark:bg-muted/60"
                    >
                        <div class="flex items-center gap-2">
                            <CalendarRange class="h-4 w-4 text-sky-500" />
                            <p class="text-xs text-muted-foreground">Citas del periodo</p>
                        </div>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ props.summary.appointments_period }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-emerald-100 bg-card p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-emerald-900/30 dark:bg-muted/60"
                    >
                        <div class="flex items-center gap-2">
                            <Activity class="h-4 w-4 text-emerald-500" />
                            <p class="text-xs text-muted-foreground">Sesiones del periodo</p>
                        </div>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ props.summary.sessions_period }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-violet-100 bg-card p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-violet-900/30 dark:bg-muted/60"
                    >
                        <div class="flex items-center gap-2">
                            <Users class="h-4 w-4 text-violet-500" />
                            <p class="text-xs text-muted-foreground">Pacientes activos</p>
                        </div>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ props.summary.active_patients }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-border bg-card p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:bg-muted/60"
                    >
                        <div class="flex items-center gap-2">
                            <Users class="h-4 w-4 text-muted-foreground" />
                            <p class="text-xs text-muted-foreground">
                                Pacientes nuevos en el periodo
                            </p>
                        </div>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ props.summary.new_patients_period }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-emerald-900/30 dark:bg-emerald-950/20"
                    >
                        <div class="flex items-center gap-2">
                            <Wallet class="h-4 w-4 text-emerald-600" />
                            <p class="text-xs text-emerald-700 dark:text-emerald-400">
                                Ingresos del periodo
                            </p>
                        </div>
                        <p
                            class="mt-1 text-2xl font-semibold text-emerald-800 dark:text-emerald-200"
                        >
                            {{ money(props.summary.income_period) }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-amber-100 bg-amber-50 p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-amber-900/30 dark:bg-amber-950/20"
                    >
                        <div class="flex items-center gap-2">
                            <Wallet class="h-4 w-4 text-amber-600" />
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                Pagos pendientes
                            </p>
                        </div>
                        <p
                            class="mt-1 text-2xl font-semibold text-amber-800 dark:text-amber-200"
                        >
                            {{ money(props.summary.pending_amount) }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-border bg-card p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:bg-muted/60"
                    >
                        <div class="flex items-center gap-2">
                            <Wallet class="h-4 w-4 text-muted-foreground" />
                            <p class="text-xs text-muted-foreground">Total pagos registrados</p>
                        </div>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ props.summary.total_payments }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-rose-100 bg-rose-50 p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-rose-900/30 dark:bg-rose-950/20"
                    >
                        <div class="flex items-center gap-2">
                            <ListChecks class="h-4 w-4 text-rose-600" />
                            <p class="text-xs text-rose-700 dark:text-rose-400">
                                Actividades vencidas
                            </p>
                        </div>
                        <p
                            class="mt-1 text-2xl font-semibold text-rose-800 dark:text-rose-200"
                        >
                            {{ props.summary.activities_overdue }}
                        </p>
                    </article>
                </div>

                <!-- Detalle por secciones -->
                <div class="grid gap-4 xl:grid-cols-2">
                    <section
                        class="rounded-2xl border border-border bg-card p-4 dark:bg-muted/60"
                    >
                        <h3
                            class="mb-3 flex items-center gap-2 text-base font-semibold"
                        >
                            <CalendarRange class="h-4 w-4" />Citas por estado
                        </h3>
                        <div
                            v-if="!props.appointmentsByStatus.length"
                            class="text-sm text-muted-foreground"
                        >
                            Sin datos para el filtro actual.
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="row in props.appointmentsByStatus"
                                :key="row.status"
                                class="rounded-xl bg-muted p-2 dark:bg-muted"
                            >
                                <div class="mb-1 flex justify-between text-xs">
                                    <span>{{ tAppointmentStatus(row.status) }}</span>
                                    <strong>{{ row.total }}</strong>
                                </div>
                                <div
                                    class="h-2 rounded bg-border"
                                >
                                    <div
                                        class="h-2 rounded bg-sky-500"
                                        :style="{
                                            width: barWidth(
                                                row.total,
                                                props.appointmentsByStatus,
                                            ),
                                        }"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-border bg-card p-4 dark:bg-muted/60"
                    >
                        <h3
                            class="mb-3 flex items-center gap-2 text-base font-semibold"
                        >
                            <Wallet class="h-4 w-4" />Pagos por estado
                        </h3>
                        <div
                            v-if="!props.paymentsSummary.by_status.length"
                            class="text-sm text-muted-foreground"
                        >
                            Sin pagos en el periodo.
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="row in props.paymentsSummary.by_status"
                                :key="`pay-${row.status}`"
                                class="flex items-center justify-between rounded-xl bg-muted px-3 py-2 text-sm dark:bg-muted"
                            >
                                <span
                                    >{{ tPaymentStatus(row.status) }} ({{
                                        row.total
                                    }})</span
                                >
                                <strong>{{ money(row.amount || 0) }}</strong>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-border bg-card p-4 dark:bg-muted/60"
                    >
                        <h3
                            class="mb-3 flex items-center gap-2 text-base font-semibold"
                        >
                            <ListChecks class="h-4 w-4" />Actividades por estado
                        </h3>
                        <div
                            v-if="!props.activitiesSummary.by_status.length"
                            class="text-sm text-muted-foreground"
                        >
                            Sin actividades para el filtro actual.
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="row in props.activitiesSummary.by_status"
                                :key="`act-${row.status}`"
                                class="flex items-center justify-between rounded-xl bg-muted px-3 py-2 text-sm dark:bg-muted"
                            >
                                <span>{{ tActivityStatus(row.status) }}</span>
                                <strong>{{ row.total }}</strong>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-border bg-card p-4 dark:bg-muted/60"
                    >
                        <h3
                            class="mb-3 flex items-center gap-2 text-base font-semibold"
                        >
                            <Users class="h-4 w-4" />Productividad por terapeuta
                        </h3>
                        <div
                            v-if="!props.therapistProductivity.length"
                            class="text-sm text-muted-foreground"
                        >
                            Sin sesiones por terapeuta en el periodo.
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="row in props.therapistProductivity"
                                :key="row.therapist_user_id"
                                class="rounded-xl bg-muted p-2 dark:bg-muted"
                            >
                                <div class="mb-1 flex justify-between text-sm">
                                    <span>{{ row.therapist_name }}</span>
                                    <strong>{{ row.total }}</strong>
                                </div>
                                <div
                                    class="h-2 rounded bg-border"
                                >
                                    <div
                                        class="h-2 rounded bg-emerald-500"
                                        :style="{
                                            width: barWidth(
                                                row.total,
                                                props.therapistProductivity,
                                            ),
                                        }"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Rango aplicado -->
                <div
                    class="rounded-2xl border border-border bg-card p-3 text-sm text-muted-foreground dark:bg-muted/60"
                >
                    <div class="flex items-center gap-2">
                        <Activity class="h-4 w-4" />
                        Rango aplicado: {{ props.filters.start_date }} al
                        {{ props.filters.end_date }}
                    </div>
                </div>
            </template>
        </section>
    </AppLayout>
</template>
