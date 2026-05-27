<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import VueApexCharts from 'vue3-apexcharts';
import {
    Users, CalendarDays, ClipboardList, CreditCard, Activity,
    AlertTriangle, Wallet, Stethoscope, TrendingUp, RotateCcw,
    Clock3, ArrowRight, FileText,
} from 'lucide-vue-next';
import { formatDateTimeMx } from '@/lib/dates';
import { tActivityStatus, tPaymentStatus, tAppointmentStatus } from '@/lib/labels';

const props = defineProps<{
    stats: Record<string, number | null>;
    upcomingAppointments: any[];
    appointmentsByStatus: { status: string; total: number }[];
    sessionsByDay: { date: string; total: number }[];
    incomeByDay: { date: string; total: number }[];
    activitiesByStatus: { status: string; total: number }[];
    paymentsByStatus: { status: string; total: number; amount: number }[];
    therapistProductivity: { therapist: string; sessions: number }[];
    alerts: Array<{ type: 'info' | 'warning' | 'success'; text: string }>;
    enabledModules: Record<string, boolean>;
    appSettings: Record<string, string>;
    filters: { start_date: string; end_date: string; therapist_user_id: number | null; appointment_status: string };
    therapistLookup: { id: number; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

// ── Dark mode detection ──────────────────────────────────────────────────────
const isDark = ref(false);
let observer: MutationObserver | null = null;
onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onUnmounted(() => observer?.disconnect());

// ── Filters ──────────────────────────────────────────────────────────────────
const filterForm = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    therapist_user_id: props.filters.therapist_user_id,
    appointment_status: props.filters.appointment_status ?? '',
});

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
const applyFilters = () => {
    router.get('/dashboard', {
        start_date: filterForm.value.start_date,
        end_date: filterForm.value.end_date,
        therapist_user_id: filterForm.value.therapist_user_id || '',
        appointment_status: filterForm.value.appointment_status || '',
    }, { preserveState: true, replace: true, preserveScroll: true });
};
watch(filterForm, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
}, { deep: true });

const resetFilters = () => {
    const now = new Date();
    const y = now.getFullYear(), m = String(now.getMonth() + 1).padStart(2, '0');
    filterForm.value = {
        start_date: `${y}-${m}-01`,
        end_date: new Date(y, now.getMonth() + 1, 0).toISOString().slice(0, 10),
        therapist_user_id: null,
        appointment_status: '',
    };
};

// ── Helpers ──────────────────────────────────────────────────────────────────
const isModuleEnabled = (module: string) => props.enabledModules[module] !== false;

const formatCurrency = (value: number | null) =>
    value == null ? '—' : new Intl.NumberFormat('es-MX', {
        style: 'currency', currency: props.appSettings.default_currency ?? 'MXN',
    }).format(value);

const formatDateShort = (d: string) => {
    const [, m, day] = d.split('-');
    return `${day}/${m}`;
};

// ── KPI cards ────────────────────────────────────────────────────────────────
const kpiCards = [
    { key: 'activePatients',        label: 'Pacientes activos',    icon: Users,         module: 'pacientes',   gradient: 'from-violet-500 to-violet-600' },
    { key: 'todayAppointments',     label: 'Citas hoy',            icon: CalendarDays,  module: 'agenda',      gradient: 'from-sky-500 to-sky-600' },
    { key: 'pendingAppointments',   label: 'Citas pendientes',     icon: Clock3,        module: 'agenda',      gradient: 'from-amber-500 to-amber-600' },
    { key: 'monthSessions',         label: 'Sesiones del periodo', icon: ClipboardList, module: 'sesiones',    gradient: 'from-emerald-500 to-emerald-600' },
    { key: 'monthIncome',           label: 'Ingresos del periodo', icon: Wallet,        module: 'pagos',       gradient: 'from-teal-500 to-teal-600', currency: true },
    { key: 'pendingPayments',       label: 'Pagos pendientes',     icon: CreditCard,    module: 'pagos',       gradient: 'from-rose-500 to-rose-600' },
    { key: 'pendingActivities',     label: 'Actividades activas',  icon: Activity,      module: 'actividades', gradient: 'from-indigo-500 to-indigo-600' },
    { key: 'overdueActivities',     label: 'Actividades vencidas', icon: AlertTriangle, module: 'actividades', gradient: 'from-red-600 to-red-700' },
    { key: 'activeTherapists',      label: 'Terapeutas activos',   icon: Stethoscope,   module: 'sesiones',    gradient: 'from-zinc-500 to-zinc-600' },
] as const;

const visibleCards = computed(() =>
    kpiCards.filter(c => isModuleEnabled(c.module) && props.stats[c.key] !== null)
);

// ── Chart shared config ───────────────────────────────────────────────────────
const chartFontColor = computed(() => isDark.value ? 'hsl(215 20% 58%)' : '#71717a');
const gridColor      = computed(() => isDark.value ? 'hsl(222 22% 18%)' : '#f4f4f5');
const tooltipTheme   = computed(() => isDark.value ? 'dark' : 'light');
const valueFontColor = computed(() => isDark.value ? '#f4f4f5' : '#18181b');
const labelFontColor = computed(() => isDark.value ? '#a1a1aa' : '#52525b');

const sharedDonutInner = computed(() => ({
    show: true,
    total: {
        show: true, label: 'Total', fontSize: '13px', fontFamily: 'inherit',
        color: chartFontColor.value,
        formatter: (w: any) => w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0),
    },
    value: { fontSize: '22px', fontWeight: 700, fontFamily: 'inherit', color: valueFontColor.value },
}));

const sharedDonutLegend = computed(() => ({
    position: 'bottom' as const, fontSize: '12px', fontFamily: 'inherit',
    labels: { colors: chartFontColor.value },
    itemMargin: { horizontal: 8, vertical: 4 },
}));

// ── Chart 1: Donut — Citas por estado ─────────────────────────────────────────
const apptPalette: Record<string, string> = {
    scheduled: '#38bdf8', confirmed: '#34d399', done: '#a78bfa',
    cancelled: '#94a3b8', no_show: '#f87171',
};
const donutApptSeries  = computed(() => props.appointmentsByStatus.map(r => r.total));
const donutApptLabels  = computed(() => props.appointmentsByStatus.map(r => tAppointmentStatus(r.status)));
const donutApptColors  = computed(() => props.appointmentsByStatus.map(r => apptPalette[r.status] ?? '#94a3b8'));
const donutApptOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent', toolbar: { show: false }, fontFamily: 'inherit' },
    labels: donutApptLabels.value,
    colors: donutApptColors.value,
    dataLabels: { enabled: false },
    legend: sharedDonutLegend.value,
    plotOptions: { pie: { donut: { size: '68%', labels: sharedDonutInner.value } } },
    stroke: { show: false },
    tooltip: { theme: tooltipTheme.value, style: { fontFamily: 'inherit' } },
}));

// ── Chart 2: Bar — Sesiones por día ──────────────────────────────────────────
const sessionSlice    = computed(() => props.sessionsByDay.slice(-30));
const sessionsSeries  = computed(() => [{ name: 'Sesiones', data: sessionSlice.value.map(r => r.total) }]);
const sessionsOptions = computed(() => ({
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#34d399'],
    xaxis: {
        categories: sessionSlice.value.map(r => formatDateShort(r.date)),
        labels: { style: { fontSize: '10px', colors: chartFontColor.value }, rotate: -45 },
        axisBorder: { show: false }, axisTicks: { show: false },
        tickAmount: Math.min(sessionSlice.value.length, 10),
    },
    yaxis: {
        min: 0,
        labels: {
            style: { fontSize: '11px', colors: chartFontColor.value },
            formatter: (v: number) => v % 1 === 0 ? String(Math.round(v)) : '',
        },
    },
    dataLabels: { enabled: false },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
    grid: { borderColor: gridColor.value, strokeDashArray: 4, xaxis: { lines: { show: false } } },
    tooltip: {
        theme: tooltipTheme.value, style: { fontFamily: 'inherit' },
        x: { formatter: (_: any, { dataPointIndex }: any) => sessionSlice.value[dataPointIndex]?.date ?? '' },
    },
}));

// ── Chart 3: Area — Ingresos por día ─────────────────────────────────────────
const incomeSlice    = computed(() => props.incomeByDay.slice(-30));
const incomeSeries   = computed(() => [{ name: 'Ingresos', data: incomeSlice.value.map(r => Number(r.total)) }]);
const incomeOptions  = computed(() => ({
    chart: { type: 'area', background: 'transparent', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#a78bfa'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.04, stops: [0, 100] } },
    xaxis: {
        categories: incomeSlice.value.map(r => formatDateShort(r.date)),
        labels: { style: { fontSize: '10px', colors: chartFontColor.value }, rotate: -45 },
        axisBorder: { show: false }, axisTicks: { show: false },
        tickAmount: Math.min(incomeSlice.value.length, 10),
    },
    yaxis: {
        min: 0,
        labels: {
            style: { fontSize: '11px', colors: chartFontColor.value },
            formatter: (v: number) => `$${Math.round(v).toLocaleString('es-MX')}`,
        },
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth' as const, width: 2 },
    markers: { size: incomeSlice.value.length <= 10 ? 4 : 0, hover: { size: 6 } },
    grid: { borderColor: gridColor.value, strokeDashArray: 4, xaxis: { lines: { show: false } } },
    tooltip: {
        theme: tooltipTheme.value, style: { fontFamily: 'inherit' },
        x: { formatter: (_: any, { dataPointIndex }: any) => incomeSlice.value[dataPointIndex]?.date ?? '' },
        y: { formatter: (v: number) => formatCurrency(v) },
    },
}));

// ── Chart 4: Donut — Actividades por estado ───────────────────────────────────
const actPalette: Record<string, string> = {
    pending: '#fbbf24', in_progress: '#38bdf8', on_hold: '#94a3b8',
    completed: '#34d399', cancelled: '#f87171', overdue: '#ef4444',
};
const donutActSeries  = computed(() => props.activitiesByStatus.map(r => r.total));
const donutActLabels  = computed(() => props.activitiesByStatus.map(r => tActivityStatus(r.status)));
const donutActColors  = computed(() => props.activitiesByStatus.map(r => actPalette[r.status] ?? '#94a3b8'));
const donutActOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent', toolbar: { show: false }, fontFamily: 'inherit' },
    labels: donutActLabels.value,
    colors: donutActColors.value,
    dataLabels: { enabled: false },
    legend: sharedDonutLegend.value,
    plotOptions: { pie: { donut: { size: '68%', labels: sharedDonutInner.value } } },
    stroke: { show: false },
    tooltip: { theme: tooltipTheme.value, style: { fontFamily: 'inherit' } },
}));

// ── Chart 5: Donut — Pagos por estado ─────────────────────────────────────────
const payPalette: Record<string, string> = {
    pending: '#fbbf24', paid: '#34d399', partial: '#38bdf8',
    cancelled: '#94a3b8', refunded: '#a78bfa',
};
const donutPaySeries  = computed(() => props.paymentsByStatus.map(r => r.total));
const donutPayLabels  = computed(() => props.paymentsByStatus.map(r => tPaymentStatus(r.status)));
const donutPayColors  = computed(() => props.paymentsByStatus.map(r => payPalette[r.status] ?? '#94a3b8'));
const donutPayOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent', toolbar: { show: false }, fontFamily: 'inherit' },
    labels: donutPayLabels.value,
    colors: donutPayColors.value,
    dataLabels: { enabled: false },
    legend: sharedDonutLegend.value,
    plotOptions: { pie: { donut: { size: '68%', labels: sharedDonutInner.value } } },
    stroke: { show: false },
    tooltip: {
        theme: tooltipTheme.value, style: { fontFamily: 'inherit' },
        y: {
            formatter: (val: number, opts: any) => {
                const item = props.paymentsByStatus[opts.dataPointIndex];
                return `${val} (${formatCurrency(item?.amount ?? 0)})`;
            },
        },
    },
}));

// ── Chart 6: Horizontal Bar — Productividad por terapeuta ─────────────────────
const productivitySeries  = computed(() => [{ name: 'Sesiones', data: props.therapistProductivity.map(r => r.sessions) }]);
const productivityOptions = computed(() => ({
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#8b5cf6'],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 4,
            barHeight: '55%',
        },
    },
    xaxis: {
        categories: props.therapistProductivity.map(r => r.therapist),
        labels: { style: { fontSize: '11px', colors: chartFontColor.value } },
        axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { fontSize: '11px', colors: chartFontColor.value },
            maxWidth: 130,
        },
    },
    dataLabels: {
        enabled: true,
        formatter: (v: number) => String(v),
        style: { fontSize: '11px', fontFamily: 'inherit', colors: [labelFontColor.value] },
        offsetX: 6,
    },
    grid: { borderColor: gridColor.value, strokeDashArray: 4, yaxis: { lines: { show: false } } },
    tooltip: { theme: tooltipTheme.value, style: { fontFamily: 'inherit' } },
}));
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="space-y-5 pb-8">

            <!-- ── Header ─────────────────────────────────────────────────── -->
            <header class="fv-panel relative z-20">
                <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 shadow-sm">
                                <TrendingUp class="h-4 w-4 text-white" />
                            </div>
                            <div>
                                <h1 class="text-lg font-semibold leading-tight text-foreground">
                                    {{ props.appSettings.clinic_name ?? 'FisioVida' }}
                                </h1>
                                <p class="text-xs text-muted-foreground">
                                    {{ props.filters.start_date }} — {{ props.filters.end_date }}
                                </p>
                            </div>
                        </div>
                        <Badge
                            v-if="props.appSettings.demo_mode === '1'"
                            class="mt-3 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                        >
                            Modo demo
                        </Badge>
                    </div>

                    <!-- Filters -->
                    <div class="relative z-30 flex flex-wrap items-end gap-2">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] font-medium text-muted-foreground">Desde</span>
                            <DatePicker v-model="filterForm.start_date" class="w-36" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] font-medium text-muted-foreground">Hasta</span>
                            <DatePicker v-model="filterForm.end_date" class="w-36" />
                        </div>
                        <div v-if="props.therapistLookup.length" class="flex flex-col gap-1">
                            <span class="text-[11px] font-medium text-muted-foreground">Terapeuta</span>
                            <SearchableSelect
                                v-model="filterForm.therapist_user_id"
                                :options="[{ value: null, label: 'Todos' }, ...props.therapistLookup.map(t => ({ value: t.id, label: t.label }))]"
                                clearable
                                class="w-56 min-w-[200px]"
                            />
                        </div>
                        <Button variant="outline" size="sm" class="h-9 rounded-lg gap-1.5" @click="resetFilters">
                            <RotateCcw class="h-3.5 w-3.5" />
                            Limpiar
                        </Button>
                    </div>
                </div>
            </header>

            <!-- ── KPI Cards ───────────────────────────────────────────────── -->
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <article
                    v-for="card in visibleCards"
                    :key="card.key"
                    class="fv-kpi-card"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-medium leading-snug text-muted-foreground">{{ card.label }}</p>
                        <div :class="`flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br ${card.gradient} shadow-sm`">
                            <component :is="card.icon" class="h-3.5 w-3.5 text-white" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-foreground">
                        {{ (card as any).currency
                            ? formatCurrency(props.stats[card.key] as number | null)
                            : (props.stats[card.key] ?? '—') }}
                    </p>
                    <div :class="`absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r ${card.gradient} opacity-0 transition-opacity duration-200 group-hover:opacity-100`" />
                </article>
            </div>

            <!-- ── Main Charts ─────────────────────────────────────────────── -->
            <div class="grid gap-4 xl:grid-cols-3">

                <!-- Donut — Citas por estado -->
                <article
                    v-if="isModuleEnabled('agenda')"
                    class="fv-panel"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-sky-100 dark:bg-sky-900/30">
                            <CalendarDays class="h-3.5 w-3.5 text-sky-600 dark:text-sky-400" />
                        </div>
                        <h3 class="text-sm font-semibold text-foreground">Citas por estado</h3>
                    </div>
                    <div v-if="props.appointmentsByStatus.length" class="-mx-1">
                        <VueApexCharts type="donut" height="240" :series="donutApptSeries" :options="donutApptOptions" />
                    </div>
                    <div v-else class="flex h-48 flex-col items-center justify-center gap-2 text-muted-foreground">
                        <CalendarDays class="h-8 w-8 opacity-30" />
                        <p class="text-sm">Sin citas en el periodo</p>
                    </div>
                </article>

                <!-- Bar — Sesiones por día -->
                <article
                    v-if="isModuleEnabled('sesiones')"
                    class="fv-panel"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-100 dark:bg-emerald-900/30">
                            <ClipboardList class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <h3 class="text-sm font-semibold text-foreground">Sesiones por día</h3>
                    </div>
                    <div v-if="props.sessionsByDay.length" class="-mx-2">
                        <VueApexCharts type="bar" height="220" :series="sessionsSeries" :options="sessionsOptions" />
                    </div>
                    <div v-else class="flex h-48 flex-col items-center justify-center gap-2 text-muted-foreground">
                        <ClipboardList class="h-8 w-8 opacity-30" />
                        <p class="text-sm">Sin sesiones en el periodo</p>
                    </div>
                </article>

                <!-- Area — Ingresos por día -->
                <article
                    v-if="isModuleEnabled('pagos')"
                    class="fv-panel"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-violet-100 dark:bg-violet-900/30">
                            <TrendingUp class="h-3.5 w-3.5 text-violet-600 dark:text-violet-400" />
                        </div>
                        <h3 class="text-sm font-semibold text-foreground">Ingresos por día</h3>
                    </div>
                    <div v-if="props.incomeByDay.length" class="-mx-2">
                        <VueApexCharts type="area" height="220" :series="incomeSeries" :options="incomeOptions" />
                    </div>
                    <div v-else class="flex h-48 flex-col items-center justify-center gap-2 text-muted-foreground">
                        <TrendingUp class="h-8 w-8 opacity-30" />
                        <p class="text-sm">Sin ingresos en el periodo</p>
                    </div>
                </article>
            </div>

            <!-- ── Secondary Charts ────────────────────────────────────────── -->
            <div class="grid gap-4 xl:grid-cols-3">

                <!-- Donut — Actividades por estado -->
                <article
                    v-if="isModuleEnabled('actividades')"
                    class="fv-panel"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-md bg-indigo-100 dark:bg-indigo-900/30">
                                <Activity class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <h3 class="text-sm font-semibold text-foreground">Actividades por estado</h3>
                        </div>
                        <a href="/actividades" class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-indigo-600 dark:hover:text-indigo-400">
                            Ver <ArrowRight class="h-3 w-3" />
                        </a>
                    </div>
                    <div v-if="props.activitiesByStatus.length" class="-mx-1">
                        <VueApexCharts type="donut" height="240" :series="donutActSeries" :options="donutActOptions" />
                    </div>
                    <div v-else class="flex h-48 flex-col items-center justify-center gap-2 text-muted-foreground">
                        <Activity class="h-8 w-8 opacity-30" />
                        <p class="text-sm">Sin actividades registradas</p>
                    </div>
                </article>

                <!-- Donut — Pagos por estado -->
                <article
                    v-if="isModuleEnabled('pagos')"
                    class="fv-panel"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-md bg-teal-100 dark:bg-teal-900/30">
                                <Wallet class="h-3.5 w-3.5 text-teal-600 dark:text-teal-400" />
                            </div>
                            <h3 class="text-sm font-semibold text-foreground">Pagos por estado</h3>
                        </div>
                        <a href="/pagos" class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-teal-600 dark:hover:text-teal-400">
                            Ver <ArrowRight class="h-3 w-3" />
                        </a>
                    </div>
                    <div v-if="props.paymentsByStatus.length" class="-mx-1">
                        <VueApexCharts type="donut" height="240" :series="donutPaySeries" :options="donutPayOptions" />
                    </div>
                    <div v-else class="flex h-48 flex-col items-center justify-center gap-2 text-muted-foreground">
                        <Wallet class="h-8 w-8 opacity-30" />
                        <p class="text-sm">Sin pagos en el periodo</p>
                    </div>
                </article>

                <!-- Horizontal Bar — Productividad por terapeuta -->
                <article
                    v-if="isModuleEnabled('sesiones')"
                    class="fv-panel"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-purple-100 dark:bg-purple-900/30">
                            <Stethoscope class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h3 class="text-sm font-semibold text-foreground">Productividad por terapeuta</h3>
                    </div>
                    <div v-if="props.therapistProductivity.length" class="-mx-2">
                        <VueApexCharts type="bar" height="240" :series="productivitySeries" :options="productivityOptions" />
                    </div>
                    <div v-else class="flex h-48 flex-col items-center justify-center gap-2 text-muted-foreground">
                        <Stethoscope class="h-8 w-8 opacity-30" />
                        <p class="text-sm">Sin datos de terapeutas en el periodo</p>
                    </div>
                </article>
            </div>

            <!-- ── Bottom Row: Upcoming + Quick Actions ──────────────────── -->
            <div class="grid gap-4 xl:grid-cols-2">

                <!-- Compact upcoming (max 3 cards) -->
                <article
                    v-if="isModuleEnabled('agenda')"
                    class="fv-panel"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 dark:bg-amber-900/30">
                                <Clock3 class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <h3 class="text-sm font-semibold text-foreground">Próximas citas</h3>
                        </div>
                        <a href="/citas" class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-amber-600 dark:hover:text-amber-400">
                            Ver agenda <ArrowRight class="h-3 w-3" />
                        </a>
                    </div>

                    <div v-if="!props.upcomingAppointments.length" class="flex h-28 flex-col items-center justify-center gap-1 text-muted-foreground">
                        <Clock3 class="h-7 w-7 opacity-30" />
                        <p class="text-sm">No hay próximas citas</p>
                    </div>
                    <div v-else class="grid gap-3 sm:grid-cols-3">
                        <div
                            v-for="item in props.upcomingAppointments"
                            :key="item.id"
                            class="flex flex-col gap-2 rounded-xl bg-muted p-3 ring-1 ring-border"
                        >
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                {{ (item.patient_name || '?').charAt(0).toUpperCase() }}
                            </div>
                            <p class="truncate text-xs font-semibold text-foreground">
                                {{ item.patient_name || 'Paciente' }}
                            </p>
                            <p class="text-[11px] leading-snug text-muted-foreground">
                                {{ formatDateTimeMx(item.start_at) }}
                            </p>
                            <p class="truncate text-[11px] text-muted-foreground">
                                {{ item.therapist_name || 'Sin terapeuta' }}
                            </p>
                        </div>
                    </div>
                </article>

                <!-- Quick actions -->
                <article class="fv-panel">
                    <div class="mb-4 flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-muted">
                            <ArrowRight class="h-3.5 w-3.5 text-muted-foreground" />
                        </div>
                        <h3 class="text-sm font-semibold text-foreground">Acciones rápidas</h3>
                    </div>
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                        <a
                            v-if="isModuleEnabled('agenda')"
                            href="/citas"
                            class="flex flex-col items-center gap-2 rounded-xl bg-sky-50 px-2 py-4 text-center transition-colors hover:bg-sky-100 dark:bg-sky-900/20 dark:hover:bg-sky-900/40"
                        >
                            <CalendarDays class="h-5 w-5 text-sky-600 dark:text-sky-400" />
                            <span class="text-[11px] font-medium leading-tight text-sky-700 dark:text-sky-300">Agenda</span>
                        </a>
                        <a
                            v-if="isModuleEnabled('pacientes')"
                            href="/pacientes"
                            class="flex flex-col items-center gap-2 rounded-xl bg-violet-50 px-2 py-4 text-center transition-colors hover:bg-violet-100 dark:bg-violet-900/20 dark:hover:bg-violet-900/40"
                        >
                            <Users class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                            <span class="text-[11px] font-medium leading-tight text-violet-700 dark:text-violet-300">Pacientes</span>
                        </a>
                        <a
                            v-if="isModuleEnabled('sesiones')"
                            href="/sesiones"
                            class="flex flex-col items-center gap-2 rounded-xl bg-emerald-50 px-2 py-4 text-center transition-colors hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40"
                        >
                            <ClipboardList class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-[11px] font-medium leading-tight text-emerald-700 dark:text-emerald-300">Sesiones</span>
                        </a>
                        <a
                            v-if="isModuleEnabled('actividades')"
                            href="/actividades"
                            class="flex flex-col items-center gap-2 rounded-xl bg-indigo-50 px-2 py-4 text-center transition-colors hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/40"
                        >
                            <Activity class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                            <span class="text-[11px] font-medium leading-tight text-indigo-700 dark:text-indigo-300">Actividades</span>
                        </a>
                        <a
                            v-if="isModuleEnabled('pagos')"
                            href="/pagos"
                            class="flex flex-col items-center gap-2 rounded-xl bg-teal-50 px-2 py-4 text-center transition-colors hover:bg-teal-100 dark:bg-teal-900/20 dark:hover:bg-teal-900/40"
                        >
                            <Wallet class="h-5 w-5 text-teal-600 dark:text-teal-400" />
                            <span class="text-[11px] font-medium leading-tight text-teal-700 dark:text-teal-300">Pagos</span>
                        </a>
                        <a
                            href="/reportes"
                            class="flex flex-col items-center gap-2 rounded-xl bg-muted px-2 py-4 text-center transition-colors hover:bg-muted/70"
                        >
                            <FileText class="h-5 w-5 text-muted-foreground" />
                            <span class="text-[11px] font-medium leading-tight text-muted-foreground">Reportes</span>
                        </a>
                    </div>
                </article>
            </div>

        </section>
    </AppLayout>
</template>
