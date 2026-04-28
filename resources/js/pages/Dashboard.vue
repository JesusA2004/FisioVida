<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { Badge } from '@/components/ui/badge';
import {
    Users,
    CalendarDays,
    ClipboardList,
    CreditCard,
    Activity,
    AlertTriangle,
    Wallet,
    Stethoscope,
} from 'lucide-vue-next';
import { formatDateTimeMx } from '@/lib/dates';
import { tActivityStatus, tPaymentStatus } from '@/lib/labels';

const props = defineProps<{
    stats: Record<string, number | null>;
    todayAppointments: any[];
    upcomingAppointments: any[];
    pendingActivities: any[];
    overdueActivities: any[];
    recentPayments: any[];
    alerts: Array<{ type: 'info' | 'warning' | 'success'; text: string }>;
    enabledModules: Record<string, boolean>;
    appSettings: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const cards = [
    {
        key: 'activePatients',
        label: 'Pacientes activos',
        icon: Users,
        module: 'pacientes',
    },
    {
        key: 'todayAppointments',
        label: 'Citas hoy',
        icon: CalendarDays,
        module: 'agenda',
    },
    {
        key: 'pendingAppointments',
        label: 'Citas pendientes',
        icon: CalendarDays,
        module: 'agenda',
    },
    {
        key: 'cancelledAppointments',
        label: 'Citas canceladas',
        icon: AlertTriangle,
        module: 'agenda',
    },
    {
        key: 'monthSessions',
        label: 'Sesiones del mes',
        icon: ClipboardList,
        module: 'sesiones',
    },
    {
        key: 'pendingPayments',
        label: 'Pagos pendientes',
        icon: CreditCard,
        module: 'pagos',
    },
    {
        key: 'monthIncome',
        label: 'Ingresos del mes',
        icon: Wallet,
        module: 'pagos',
    },
    {
        key: 'pendingActivities',
        label: 'Actividades pendientes',
        icon: Activity,
        module: 'actividades',
    },
    {
        key: 'overdueActivities',
        label: 'Actividades vencidas',
        icon: AlertTriangle,
        module: 'actividades',
    },
    {
        key: 'activeTherapists',
        label: 'Terapeutas activos',
        icon: Stethoscope,
        module: 'sesiones',
    },
];

const isModuleEnabled = (module: string) =>
    props.enabledModules[module] !== false;
const formatCurrency = (value: number | null) =>
    value == null
        ? '—'
        : new Intl.NumberFormat('es-MX', {
              style: 'currency',
              currency: props.appSettings.default_currency ?? 'MXN',
          }).format(value);
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="space-y-6">
      <header class="rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ props.appSettings.clinic_name ?? 'FisioVida' }}</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Bienvenido al panel principal de métricas operativas de la clínica.</p>
        <Badge v-if="props.appSettings.demo_mode === '1'" class="mt-3 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Modo demo activo</Badge>
      </header>

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="space-y-6">
            <header
                class="rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950"
            >
                <h1
                    class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100"
                >
                    {{ props.appSettings.clinic_name ?? 'FisioVida' }}
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Bienvenido al panel principal de métricas operativas de la
                    clínica.
                </p>
                <Badge
                    v-if="props.appSettings.demo_mode === '1'"
                    class="mt-3 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                    >Modo demo activo</Badge
                >
            </header>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <article
                    v-for="card in cards"
                    :key="card.key"
                    v-show="
                        isModuleEnabled(card.module) &&
                        props.stats[card.key] !== null
                    "
                    class="rounded-2xl bg-white p-4 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:bg-zinc-950"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs tracking-wide text-zinc-500 uppercase dark:text-zinc-400"
                        >
                            {{ card.label }}
                        </p>
                        <component
                            :is="card.icon"
                            class="h-4 w-4 text-zinc-500"
                        />
                    </div>
                    <p
                        class="mt-3 text-2xl font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        {{
                            card.key === 'monthIncome'
                                ? formatCurrency(
                                      props.stats[card.key] as number | null,
                                  )
                                : (props.stats[card.key] ?? '—')
                        }}
                    </p>
                </article>
            </div>

            <section
                v-if="props.alerts.length"
                class="space-y-2 rounded-3xl bg-white p-6 shadow-xl dark:bg-zinc-950"
            >
                <h2
                    class="text-lg font-semibold text-zinc-900 dark:text-zinc-100"
                >
                    Alertas importantes
                </h2>
                <div class="space-y-2">
                    <div
                        v-for="(alert, index) in props.alerts"
                        :key="index"
                        class="rounded-2xl border px-3 py-2 text-sm"
                        :class="
                            alert.type === 'warning'
                                ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/30 dark:bg-rose-900/20 dark:text-rose-300'
                                : alert.type === 'success'
                                  ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-900/20 dark:text-emerald-300'
                                  : 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/30 dark:bg-sky-900/20 dark:text-sky-300'
                        "
                    >
                        {{ alert.text }}
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                <article
                    class="rounded-3xl bg-white p-6 shadow-xl dark:bg-zinc-950"
                >
                    <h3
                        class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Citas de hoy
                    </h3>
                    <p
                        v-if="!props.todayAppointments.length"
                        class="text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        Sin citas programadas para hoy.
                    </p>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="item in props.todayAppointments"
                            :key="item.id"
                            class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800"
                        >
                            <p class="font-medium">
                                {{ item.patient_name || 'Paciente sin nombre' }}
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ formatDateTimeMx(item.start_at) }} ·
                                {{ item.therapist_name || 'Sin terapeuta' }}
                            </p>
                        </li>
                    </ul>
                </article>

                <article
                    class="rounded-3xl bg-white p-6 shadow-xl dark:bg-zinc-950"
                >
                    <h3
                        class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Próximas citas
                    </h3>
                    <p
                        v-if="!props.upcomingAppointments.length"
                        class="text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        No hay próximas citas registradas.
                    </p>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="item in props.upcomingAppointments"
                            :key="item.id"
                            class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800"
                        >
                            <p class="font-medium">
                                {{ item.patient_name || 'Paciente sin nombre' }}
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ formatDateTimeMx(item.start_at) }} ·
                                {{ item.therapist_name || 'Sin terapeuta' }}
                            </p>
                        </li>
                    </ul>
                </article>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <article
                    class="rounded-3xl bg-white p-6 shadow-xl dark:bg-zinc-950"
                >
                    <h3
                        class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Actividades pendientes
                    </h3>
                    <p
                        v-if="!props.pendingActivities.length"
                        class="text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        No hay actividades pendientes.
                    </p>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="item in props.pendingActivities"
                            :key="item.id"
                            class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800"
                        >
                            <p class="font-medium">{{ item.title }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ item.responsible_name || 'Sin responsable' }}
                                ·
                                {{
                                    item.due_date
                                        ? formatDateTimeMx(item.due_date)
                                        : 'Sin fecha límite'
                                }}
                                · {{ tActivityStatus(item.status) }}
                            </p>
                        </li>
                    </ul>
                </article>

                <article
                    class="rounded-3xl bg-white p-6 shadow-xl dark:bg-zinc-950"
                >
                    <h3
                        class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Pagos recientes
                    </h3>
                    <p
                        v-if="!props.recentPayments.length"
                        class="text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        No hay pagos recientes.
                    </p>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="item in props.recentPayments"
                            :key="item.id"
                            class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800"
                        >
                            <p class="font-medium">
                                {{ item.patient_name || 'Paciente sin nombre' }}
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ formatCurrency(Number(item.amount ?? 0)) }} ·
                                {{ tPaymentStatus(item.status) }} ·
                                {{ item.reference || 'Sin referencia' }}
                            </p>
                        </li>
                    </ul>
                </article>
            </div>
        </section>
    </AppLayout>
</template>
