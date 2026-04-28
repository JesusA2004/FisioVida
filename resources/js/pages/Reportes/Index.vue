<script setup lang="ts">
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
        activities_overdue: number;
    };
    appointmentsByStatus: StatusRow[];
    sessionsSummary: { total: number };
    patientsSummary: { active: number; new: number };
    paymentsSummary: { income: number; by_status: StatusRow[] };
    activitiesSummary: { overdue: number; by_status: StatusRow[] };
    therapistProductivity: TherapistRow[];
    lookups: {
        therapists: { id: number; label: string }[];
        patients: { id: number; label: string }[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reportes', href: '/reportes' },
];
const { form, loading, moduleEnabled, applyFilters, resetFilters } =
    useReporteCrud(props.filters);

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
            class="space-y-6 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950"
        >
            <div
                v-if="!moduleEnabled"
                class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
            >
                Módulo de reportes deshabilitado.
            </div>

            <template v-else>
                <div
                    class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h1
                            class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100"
                        >
                            Reportes básicos
                        </h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Consulta indicadores operativos y administrativos
                            con filtros por periodo, estado y responsables.
                        </p>
                    </div>
                    <Badge
                        class="rounded-full bg-sky-100 px-3 py-1 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300"
                    >
                        <ChartNoAxesCombined class="mr-1 h-3.5 w-3.5" /> Base
                        operativa
                    </Badge>
                </div>

                <div
                    class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"
                >
                    <div class="grid gap-3 md:grid-cols-5">
                        <DatePicker v-model="form.start_date" />
                        <DatePicker v-model="form.end_date" />
                        <SearchableSelect
                            v-model="form.status"
                            :options="[
                                { value: '', label: 'Todos los estados' },
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
                                    value: 'done',
                                    label: tAppointmentStatus('done'),
                                },
                                {
                                    value: 'pending',
                                    label: tPaymentStatus('pending'),
                                },
                                {
                                    value: 'paid',
                                    label: tPaymentStatus('paid'),
                                },
                                {
                                    value: 'failed',
                                    label: tPaymentStatus('failed'),
                                },
                                {
                                    value: 'refunded',
                                    label: tPaymentStatus('refunded'),
                                },
                            ]"
                        />
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
                    <div class="mt-3 flex gap-2">
                        <Button
                            class="rounded-xl"
                            :disabled="loading"
                            @click="applyFilters"
                            ><CalendarRange class="mr-2 h-4 w-4" />{{
                                loading ? 'Cargando...' : 'Aplicar filtros'
                            }}</Button
                        >
                        <Button
                            variant="outline"
                            class="rounded-xl"
                            :disabled="loading"
                            @click="resetFilters"
                            >Limpiar</Button
                        >
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <article
                        class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <p class="text-xs text-zinc-500">Citas del periodo</p>
                        <p class="text-2xl font-semibold">
                            {{ props.summary.appointments_period }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <p class="text-xs text-zinc-500">
                            Sesiones del periodo
                        </p>
                        <p class="text-2xl font-semibold">
                            {{ props.summary.sessions_period }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <p class="text-xs text-zinc-500">Pacientes activos</p>
                        <p class="text-2xl font-semibold">
                            {{ props.summary.active_patients }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <p class="text-xs text-zinc-500">Pacientes nuevos</p>
                        <p class="text-2xl font-semibold">
                            {{ props.summary.new_patients_period }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <p class="text-xs text-zinc-500">
                            Ingresos del periodo
                        </p>
                        <p class="text-2xl font-semibold">
                            {{ money(props.summary.income_period) }}
                        </p>
                    </article>
                    <article
                        class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <p class="text-xs text-zinc-500">
                            Actividades vencidas
                        </p>
                        <p class="text-2xl font-semibold">
                            {{ props.summary.activities_overdue }}
                        </p>
                    </article>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <section
                        class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <h3
                            class="mb-3 flex items-center text-base font-semibold"
                        >
                            <CalendarRange class="mr-2 h-4 w-4" />Citas por
                            estado
                        </h3>
                        <div
                            v-if="!props.appointmentsByStatus.length"
                            class="text-sm text-zinc-500"
                        >
                            Sin datos para el filtro actual.
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="row in props.appointmentsByStatus"
                                :key="row.status"
                                class="rounded-xl bg-zinc-50 p-2 dark:bg-zinc-900"
                            >
                                <div class="mb-1 flex justify-between text-xs">
                                    <span>{{
                                        tAppointmentStatus(row.status)
                                    }}</span
                                    ><strong>{{ row.total }}</strong>
                                </div>
                                <div
                                    class="h-2 rounded bg-zinc-200 dark:bg-zinc-800"
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
                        class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <h3
                            class="mb-3 flex items-center text-base font-semibold"
                        >
                            <Wallet class="mr-2 h-4 w-4" />Pagos por estado
                        </h3>
                        <div
                            v-if="!props.paymentsSummary.by_status.length"
                            class="text-sm text-zinc-500"
                        >
                            Sin pagos en el periodo.
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="row in props.paymentsSummary.by_status"
                                :key="`pay-${row.status}`"
                                class="flex items-center justify-between rounded-xl bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-900"
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
                        class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <h3
                            class="mb-3 flex items-center text-base font-semibold"
                        >
                            <ListChecks class="mr-2 h-4 w-4" />Actividades por
                            estado
                        </h3>
                        <div
                            v-if="!props.activitiesSummary.by_status.length"
                            class="text-sm text-zinc-500"
                        >
                            Sin actividades para el filtro actual.
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="row in props.activitiesSummary.by_status"
                                :key="`act-${row.status}`"
                                class="flex items-center justify-between rounded-xl bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-900"
                            >
                                <span>{{ tActivityStatus(row.status) }}</span
                                ><strong>{{ row.total }}</strong>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <h3
                            class="mb-3 flex items-center text-base font-semibold"
                        >
                            <Users class="mr-2 h-4 w-4" />Productividad por
                            terapeuta
                        </h3>
                        <div
                            v-if="!props.therapistProductivity.length"
                            class="text-sm text-zinc-500"
                        >
                            Sin sesiones por terapeuta en el periodo.
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="row in props.therapistProductivity"
                                :key="row.therapist_user_id"
                                class="rounded-xl bg-zinc-50 p-2 dark:bg-zinc-900"
                            >
                                <div class="mb-1 flex justify-between text-sm">
                                    <span>{{ row.therapist_name }}</span
                                    ><strong>{{ row.total }}</strong>
                                </div>
                                <div
                                    class="h-2 rounded bg-zinc-200 dark:bg-zinc-800"
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

                <div
                    class="rounded-2xl border border-zinc-200 bg-white p-4 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400"
                >
                    <div class="flex items-center gap-2">
                        <Activity class="h-4 w-4" />Rango aplicado:
                        {{ props.filters.start_date }} al
                        {{ props.filters.end_date }}
                    </div>
                </div>
            </template>
        </section>
    </AppLayout>
</template>
