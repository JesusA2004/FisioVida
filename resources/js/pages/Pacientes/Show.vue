<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Activity,
    AlertCircle,
    CalendarClock,
    Download,
    Eye,
    UploadCloud,
    CheckCircle2,
    ClipboardList,
    CreditCard,
    FileText,
    FolderOpen,
    HeartPulse,
    Mail,
    MapPin,
    NotebookText,
    Phone,
    ShieldAlert,
    ShieldCheck,
    ShieldX,
    Stethoscope,
    UserRound,
} from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { swalConfirm, swalToast } from '@/lib/swal';
import {
    tActivityStatus,
    tAppointmentStatus,
    tGeneralStatus,
    tPaymentStatus,
    tPriority,
} from '@/lib/labels';
import { formatDateMx, formatDateTimeMx } from '@/lib/dates';

type Patient = {
    id: number;
    full_name: string;
    status: string;
    telefono?: string | null;
    email?: string | null;
    fecha_nacimiento?: string | null;
    sexo?: string | null;
    direccion?: string | null;
    emergency_contact_name?: string | null;
    emergency_contact_phone?: string | null;
    notes?: string | null;
};

type Appointment = {
    id: number;
    start_at: string;
    end_at?: string | null;
    status: string;
    therapist_name?: string | null;
    notes?: string | null;
};

type Session = {
    id: number;
    appointment_id?: number | null;
    session_date: string;
    therapist_name?: string | null;
    pain_scale?: number | null;
    subjective?: string | null;
    objective?: string | null;
    assessment?: string | null;
    plan?: string | null;
    notes?: string | null;
};

type PatientFile = {
    id: number;
    original_name: string;
    file_type?: string | null;
    mime?: string | null;
    created_at: string;
    session_id?: number | null;
    session_date?: string | null;
    uploaded_by_name?: string | null;
    preview_url?: string | null;
    download_url?: string | null;
};

type Payment = {
    id: number;
    amount?: number | string | null;
    currency?: string | null;
    status: string;
    paid_at?: string | null;
    created_at?: string | null;
};

type ActivityItem = {
    id: number;
    title: string;
    priority: string;
    status: string;
    due_date?: string | null;
    responsible_name?: string | null;
};

type ConsentItem = {
    id: number;
    consent_type: string;
    accepted_at: string;
    notes?: string | null;
    accepted_by_name?: string | null;
};

// 'privacidad' se gestiona en privacy_notice_acceptances (tabla separada por normativa)
const CONSENT_TYPES = [
    { key: 'tratamiento', label: 'Consentimiento de tratamiento' },
    { key: 'imagenes', label: 'Uso de imágenes / fotografías' },
    { key: 'datos_sensibles', label: 'Datos sensibles de salud' },
] as const;

type PrivacyNotice = {
    id: number;
    version: string;
    accepted_at?: string | null;
    accepted_by_name?: string | null;
};

const props = defineProps<{
    patient: Patient;
    appointments: Appointment[];
    sessions: Session[];
    files: PatientFile[];
    payments: Payment[];
    activities: ActivityItem[];
    consents: ConsentItem[];
    privacyNotices: PrivacyNotice[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pacientes', href: '/pacientes' },
    { title: 'Expediente', href: `/pacientes/${props.patient.id}` },
];

const activeTab = ref<
    'resumen' | 'clinico' | 'citas' | 'archivos' | 'pagos' | 'seguimiento' | 'cumplimiento'
>('resumen');

const primaryButtonStyle = {
    backgroundColor: 'var(--primary)',
    color: 'var(--primary-foreground)',
};

const primarySoftStyle = {
    backgroundColor: 'color-mix(in srgb, var(--primary) 10%, var(--card))',
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

const patientInitials = computed(() => {
    return props.patient.full_name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();
});

const sortedSessions = computed(() =>
    [...props.sessions].sort((a, b) =>
        String(b.session_date).localeCompare(String(a.session_date)),
    ),
);

const sortedAppointments = computed(() =>
    [...props.appointments].sort((a, b) =>
        String(b.start_at).localeCompare(String(a.start_at)),
    ),
);

const latestSession = computed(() => sortedSessions.value[0] ?? null);

const nextAppointment = computed(() => {
    const now = new Date().getTime();

    return (
        [...props.appointments]
            .filter((appointment) => {
                const time = new Date(appointment.start_at).getTime();

                return !Number.isNaN(time) && time >= now;
            })
            .sort((a, b) =>
                String(a.start_at).localeCompare(String(b.start_at)),
            )[0] ?? null
    );
});

const painValues = computed(() =>
    props.sessions
        .map((session) => session.pain_scale)
        .filter(
            (value): value is number => value !== null && value !== undefined,
        ),
);

const averagePain = computed(() => {
    if (painValues.value.length === 0) return null;

    return Number(
        (
            painValues.value.reduce((sum, value) => sum + value, 0) /
            painValues.value.length
        ).toFixed(1),
    );
});

const lastPain = computed(() => latestSession.value?.pain_scale ?? null);

const paidTotal = computed(() => {
    return props.payments
        .filter((payment) => payment.status === 'paid')
        .reduce((sum, payment) => sum + Number(payment.amount ?? 0), 0);
});

const pendingPayments = computed(() =>
    props.payments.filter((payment) => payment.status !== 'paid'),
);

const pendingActivities = computed(() =>
    props.activities.filter(
        (activity) =>
            !['done', 'completed', 'closed'].includes(activity.status),
    ),
);

const timelineItems = computed(() => {
    const sessionItems = props.sessions.map((session) => ({
        id: `session-${session.id}`,
        type: 'Sesión',
        title: session.assessment || 'Sesión clínica registrada',
        description: session.plan || session.notes || 'Sin plan registrado.',
        date: session.session_date,
        icon: HeartPulse,
        meta: `${session.therapist_name || 'Sin terapeuta'} · Dolor ${session.pain_scale ?? '—'}/10`,
    }));

    const appointmentItems = props.appointments.map((appointment) => ({
        id: `appointment-${appointment.id}`,
        type: 'Cita',
        title: tAppointmentStatus(appointment.status),
        description: appointment.notes || 'Sin notas registradas.',
        date: appointment.start_at,
        icon: CalendarClock,
        meta: appointment.therapist_name || 'Sin terapeuta',
    }));

    const activityItems = props.activities.map((activity) => ({
        id: `activity-${activity.id}`,
        type: 'Seguimiento',
        title: activity.title,
        description: `${tPriority(activity.priority)} · ${tActivityStatus(activity.status)}`,
        date: activity.due_date || '',
        icon: ClipboardList,
        meta: activity.responsible_name || 'Sin responsable',
    }));

    return [...sessionItems, ...appointmentItems, ...activityItems]
        .filter((item) => item.date)
        .sort((a, b) => String(b.date).localeCompare(String(a.date)));
});

const statusBadgeClass = (status: string) =>
    status === 'active'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300'
        : 'border-border bg-muted text-muted-foreground';

const painBadgeClass = (value?: number | null) => {
    if (value === null || value === undefined) {
        return 'border-border bg-muted/50 text-muted-foreground';
    }

    if (value <= 3) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    if (value <= 6) {
        return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-300';
    }

    return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300';
};

const appointmentBadgeClass = (status: string) => {
    if (status === 'done') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    if (status === 'cancelled' || status === 'no_show') {
        return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300';
    }

    if (status === 'arrived' || status === 'confirmed') {
        return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/40 dark:text-sky-300';
    }

    return 'border-border bg-muted/50 text-muted-foreground';
};

const paymentBadgeClass = (status: string) =>
    status === 'paid'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300'
        : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-300';

const formatMoney = (
    amount: number | string | null | undefined,
    currency = 'MXN',
) =>
    Number(amount ?? 0).toLocaleString('es-MX', {
        style: 'currency',
        currency,
    });

const tabs = [
    { key: 'resumen', label: 'Resumen' },
    { key: 'clinico', label: 'Clínico' },
    { key: 'citas', label: 'Citas' },
    { key: 'archivos', label: 'Archivos' },
    { key: 'pagos', label: 'Pagos' },
    { key: 'seguimiento', label: 'Seguimiento' },
    { key: 'cumplimiento', label: 'Cumplimiento' },
] as const;

const goToSessions = () => {
    router.visit(`/sesiones?new=1&patient_persona_id=${props.patient.id}`);
};

const goToAppointments = () => {
    router.visit(`/citas?new=1&patient_persona_id=${props.patient.id}`);
};

const goToFiles = () => {
    router.visit(`/archivos?patient_persona_id=${props.patient.id}`);
};

const goToPayments = () => {
    router.visit(`/pagos?new=1&patient_persona_id=${props.patient.id}`);
};

const goToActivities = () => {
    router.visit(`/actividades?new=1&patient_persona_id=${props.patient.id}`);
};

// ── Compliance / Consentimientos ─────────────────────────────────────────────
const acceptedConsentTypes = computed(() =>
    new Set(props.consents.map(c => c.consent_type))
);

const consentForm = useForm({ consent_type: '', notes: '' });
const showConsentForm = ref(false);

const consentTypeLabel = (type: string) =>
    CONSENT_TYPES.find(t => t.key === type)?.label ?? type;

const lastConsent = (type: string) =>
    props.consents.find(c => c.consent_type === type) ?? null;

const consentPrintSlugs: Record<string, string> = {
    tratamiento: 'consentimiento-tratamiento',
    imagenes: 'consentimiento-imagenes',
    datos_sensibles: 'consentimiento-datos-sensibles',
};
const consentPrintSlug = (key: string) => consentPrintSlugs[key] ?? key;

const registerConsent = async () => {
    if (!consentForm.consent_type) return;
    const label = consentTypeLabel(consentForm.consent_type);
    const ok = await swalConfirm(
        `¿Registrar "${label}"?`,
        'Se quedará registrado en el expediente con fecha y usuario.',
        'Sí, registrar'
    );
    if (!ok) return;

    consentForm.post(`/pacientes/${props.patient.id}/consentimientos`, {
        preserveScroll: true,
        onSuccess: () => {
            swalToast('Consentimiento registrado', 'success');
            consentForm.reset();
            showConsentForm.value = false;
        },
        onError: () => swalToast('Revisa el formulario', 'warning'),
    });
};

// ── Aviso de Privacidad ──────────────────────────────────────────────────────
const privacyForm = useForm({ version: '1.0' });
const latestPrivacyNotice = computed(() => props.privacyNotices[0] ?? null);

const registerPrivacyNotice = async () => {
    const ok = await swalConfirm(
        '¿Registrar aceptación del aviso de privacidad?',
        `Versión ${privacyForm.version}. Se guardará con fecha, IP y usuario.`,
        'Sí, registrar'
    );
    if (!ok) return;

    privacyForm.post(`/pacientes/${props.patient.id}/aviso-privacidad`, {
        preserveScroll: true,
        onSuccess: () => swalToast('Aviso de privacidad registrado', 'success'),
        onError: () => swalToast('No se pudo registrar', 'error'),
    });
};
</script>

<template>
    <Head :title="`Expediente - ${props.patient.full_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="w-full space-y-5">
            <div class="fv-gradient-panel">
                <div
                    class="pointer-events-none absolute -top-24 -right-24 h-64 w-64 rounded-full blur-3xl"
                    :style="{
                        backgroundColor:
                            'color-mix(in srgb, var(--primary) 16%, transparent)',
                    }"
                />

                <div
                    class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl text-lg font-semibold shadow-lg"
                            :style="primaryButtonStyle"
                        >
                            {{ patientInitials }}
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1
                                    class="text-2xl font-semibold tracking-tight text-foreground"
                                >
                                    {{ props.patient.full_name }}
                                </h1>

                                <Badge
                                    class="rounded-full border px-3 py-1 text-xs"
                                    :class="
                                        statusBadgeClass(props.patient.status)
                                    "
                                >
                                    {{ tGeneralStatus(props.patient.status) }}
                                </Badge>
                            </div>

                            <p
                                class="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground"
                            >
                                Expediente clínico integral del paciente:
                                evolución, citas, sesiones, archivos, pagos y
                                seguimiento.
                            </p>

                            <div
                                class="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-2 lg:grid-cols-4"
                            >
                                <div class="flex items-center gap-2">
                                    <Phone class="h-3.5 w-3.5 shrink-0" />
                                    <span>{{
                                        props.patient.telefono || 'Sin teléfono'
                                    }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Mail class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">
                                        {{
                                            props.patient.email || 'Sin correo'
                                        }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <UserRound class="h-3.5 w-3.5 shrink-0" />
                                    <span>
                                        {{
                                            props.patient.fecha_nacimiento
                                                ? formatDateMx(
                                                      props.patient
                                                          .fecha_nacimiento,
                                                  )
                                                : 'Sin nacimiento'
                                        }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <MapPin class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">
                                        {{
                                            props.patient.direccion ||
                                            'Sin dirección'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-2 grid-cols-2 xl:w-[420px]">
                        <Button
                            class="h-11 rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-0.5"
                            :style="primaryButtonStyle"
                            @mouseenter="setPrimaryHover"
                            @mouseleave="setPrimaryNormal"
                            @click="goToSessions"
                        >
                            <HeartPulse class="mr-2 h-4 w-4" />
                            Nueva sesión
                        </Button>

                        <Button
                            variant="outline"
                            class="h-11 rounded-2xl border-border transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                            @click="goToAppointments"
                        >
                            <CalendarClock class="mr-2 h-4 w-4" />
                            Agendar cita
                        </Button>

                        <Button
                            variant="outline"
                            class="h-11 rounded-2xl border-border transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                            @click="goToActivities"
                        >
                            <ClipboardList class="mr-2 h-4 w-4" />
                            Nueva actividad
                        </Button>

                        <Button
                            variant="outline"
                            class="h-11 rounded-2xl border-border transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                            @click="goToPayments"
                        >
                            <CreditCard class="mr-2 h-4 w-4" />
                            Nuevo pago
                        </Button>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    class="rounded-[1.5rem] border border-border bg-card p-4 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs text-muted-foreground">Sesiones</p>
                            <p
                                class="mt-1 text-2xl font-semibold text-foreground"
                            >
                                {{ props.sessions.length }}
                            </p>
                        </div>

                        <div
                            class="grid h-11 w-11 place-items-center rounded-2xl"
                            :style="primarySoftStyle"
                        >
                            <HeartPulse
                                class="h-5 w-5"
                                :style="{ color: 'var(--primary)' }"
                            />
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-muted-foreground">
                        Última:
                        {{
                            latestSession
                                ? formatDateMx(latestSession.session_date)
                                : 'Sin sesiones'
                        }}
                    </p>
                </article>

                <article
                    class="rounded-[1.5rem] border border-border bg-card p-4 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs text-muted-foreground">Dolor promedio</p>
                            <p
                                class="mt-1 text-2xl font-semibold text-foreground"
                            >
                                {{ averagePain ?? '—' }}/10
                            </p>
                        </div>

                        <Badge
                            class="rounded-full border px-3 py-1 text-xs"
                            :class="painBadgeClass(averagePain)"
                        >
                            Último {{ lastPain ?? '—' }}/10
                        </Badge>
                    </div>

                    <p class="mt-3 text-xs text-muted-foreground">
                        Basado en sesiones registradas con escala de dolor.
                    </p>
                </article>

                <article
                    class="rounded-[1.5rem] border border-border bg-card p-4 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs text-muted-foreground">Próxima cita</p>
                            <p
                                class="mt-1 text-sm font-semibold text-foreground"
                            >
                                {{
                                    nextAppointment
                                        ? formatDateTimeMx(
                                              nextAppointment.start_at,
                                          )
                                        : 'Sin cita próxima'
                                }}
                            </p>
                        </div>

                        <div
                            class="grid h-11 w-11 place-items-center rounded-2xl"
                            :style="primarySoftStyle"
                        >
                            <CalendarClock
                                class="h-5 w-5"
                                :style="{ color: 'var(--primary)' }"
                            />
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-muted-foreground">
                        Total de citas: {{ props.appointments.length }}
                    </p>
                </article>

                <article
                    class="rounded-[1.5rem] border border-border bg-card p-4 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs text-muted-foreground">Pagos</p>
                            <p
                                class="mt-1 text-sm font-semibold text-foreground"
                            >
                                {{ formatMoney(paidTotal) }}
                            </p>
                        </div>

                        <div
                            class="grid h-11 w-11 place-items-center rounded-2xl"
                            :style="primarySoftStyle"
                        >
                            <CreditCard
                                class="h-5 w-5"
                                :style="{ color: 'var(--primary)' }"
                            />
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-muted-foreground">
                        Pendientes: {{ pendingPayments.length }}
                    </p>
                </article>
            </div>

            <div
                class="flex gap-2 overflow-x-auto rounded-[1.5rem] border border-border bg-card p-2 shadow-sm"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="shrink-0 rounded-2xl px-4 py-2 text-sm font-medium transition-all duration-200"
                    :class="
                        activeTab === tab.key
                            ? 'text-white shadow-sm'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    :style="
                        activeTab === tab.key ? primaryButtonStyle : undefined
                    "
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div
                v-if="activeTab === 'resumen'"
                class="grid gap-5 xl:grid-cols-[1fr_420px]"
            >
                <section
                    class="rounded-[2rem] border border-border bg-card p-5 shadow-sm"
                >
                    <div
                        class="mb-4 flex items-center justify-between gap-3 border-b border-border pb-3"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-semibold text-foreground"
                        >
                            <NotebookText
                                class="h-4 w-4"
                                :style="{ color: 'var(--primary)' }"
                            />
                            Resumen clínico reciente
                        </h2>
                    </div>

                    <div v-if="latestSession" class="space-y-3">
                        <div class="rounded-2xl p-4" :style="primarySoftStyle">
                            <div class="flex flex-wrap items-center gap-2">
                                <p
                                    class="text-sm font-semibold text-foreground"
                                >
                                    Última sesión:
                                    {{
                                        formatDateMx(latestSession.session_date)
                                    }}
                                </p>

                                <Badge
                                    class="rounded-full border px-3 py-1 text-xs"
                                    :class="
                                        painBadgeClass(latestSession.pain_scale)
                                    "
                                >
                                    Dolor
                                    {{ latestSession.pain_scale ?? '—' }}/10
                                </Badge>
                            </div>

                            <p
                                class="mt-2 text-xs text-muted-foreground"
                            >
                                Terapeuta:
                                {{
                                    latestSession.therapist_name ||
                                    'Sin terapeuta'
                                }}
                            </p>
                        </div>

                        <div class="grid gap-3 lg:grid-cols-2">
                            <div
                                class="rounded-2xl border border-border bg-muted/50 p-4"
                            >
                                <p class="text-xs font-semibold text-muted-foreground">
                                    Evaluación
                                </p>
                                <p
                                    class="mt-2 text-sm leading-6 whitespace-pre-line text-foreground"
                                >
                                    {{
                                        latestSession.assessment ||
                                        'Sin evaluación registrada.'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-border bg-muted/50 p-4"
                            >
                                <p class="text-xs font-semibold text-muted-foreground">
                                    Plan
                                </p>
                                <p
                                    class="mt-2 text-sm leading-6 whitespace-pre-line text-foreground"
                                >
                                    {{
                                        latestSession.plan ||
                                        'Sin plan registrado.'
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-border bg-muted/30 p-6 text-center"
                    >
                        <AlertCircle class="mx-auto h-6 w-6 text-zinc-400" />
                        <p
                            class="mt-3 text-sm font-medium text-foreground"
                        >
                            Aún no hay sesiones clínicas registradas.
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Registra una sesión para iniciar la evolución
                            clínica del paciente.
                        </p>
                    </div>
                </section>

                <aside class="space-y-5">
                    <section
                        class="rounded-[2rem] border border-border bg-card p-5 shadow-sm"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-semibold text-foreground"
                        >
                            <ShieldAlert
                                class="h-4 w-4"
                                :style="{ color: 'var(--primary)' }"
                            />
                            Contacto de emergencia
                        </h2>

                        <div
                            class="mt-4 rounded-2xl p-4"
                            :style="primarySoftStyle"
                        >
                            <p
                                class="text-sm font-semibold text-foreground"
                            >
                                {{
                                    props.patient.emergency_contact_name ||
                                    'Sin contacto registrado'
                                }}
                            </p>
                            <p
                                class="mt-1 text-sm text-muted-foreground"
                            >
                                {{
                                    props.patient.emergency_contact_phone ||
                                    'Sin teléfono'
                                }}
                            </p>
                        </div>
                    </section>

                    <section
                        class="rounded-[2rem] border border-border bg-card p-5 shadow-sm"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-semibold text-foreground"
                        >
                            <ClipboardList
                                class="h-4 w-4"
                                :style="{ color: 'var(--primary)' }"
                            />
                            Seguimientos pendientes
                        </h2>

                        <div
                            v-if="pendingActivities.length"
                            class="mt-4 space-y-3"
                        >
                            <div
                                v-for="item in pendingActivities.slice(0, 4)"
                                :key="item.id"
                                class="rounded-2xl border border-border bg-muted/50 p-3"
                            >
                                <p
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{ item.title }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        item.due_date
                                            ? formatDateTimeMx(item.due_date)
                                            : 'Sin fecha'
                                    }}
                                    · {{ tPriority(item.priority) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-else
                            class="mt-4 rounded-2xl border border-dashed border-border bg-muted/30 p-4 text-sm text-muted-foreground"
                        >
                            No hay seguimientos pendientes.
                        </div>
                    </section>
                </aside>
            </div>

            <div v-if="activeTab === 'clinico'" class="space-y-4">
                <section
                    class="rounded-[2rem] border border-border bg-card p-5 shadow-sm"
                >
                    <h2
                        class="flex items-center gap-2 text-base font-semibold text-foreground"
                    >
                        <Stethoscope
                            class="h-4 w-4"
                            :style="{ color: 'var(--primary)' }"
                        />
                        Evolución clínica
                    </h2>

                    <div v-if="sortedSessions.length" class="mt-4 space-y-4">
                        <article
                            v-for="item in sortedSessions"
                            :key="item.id"
                            class="rounded-[1.5rem] border border-border bg-card p-4 shadow-sm"
                        >
                            <div
                                class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
                            >
                                <div>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p
                                            class="font-semibold text-foreground"
                                        >
                                            {{
                                                formatDateMx(item.session_date)
                                            }}
                                        </p>

                                        <Badge
                                            class="rounded-full border px-3 py-1 text-xs"
                                            :class="
                                                painBadgeClass(item.pain_scale)
                                            "
                                        >
                                            Dolor
                                            {{ item.pain_scale ?? '—' }}/10
                                        </Badge>
                                    </div>

                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{
                                            item.therapist_name ||
                                            'Sin terapeuta'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                <div
                                    class="rounded-2xl p-3 text-sm leading-6 text-foreground"
                                    :style="primarySoftStyle"
                                >
                                    <strong>Subjetivo:</strong>
                                    <p class="mt-1 whitespace-pre-line">
                                        {{
                                            item.subjective ||
                                            'Sin información.'
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl p-3 text-sm leading-6 text-foreground"
                                    :style="primarySoftStyle"
                                >
                                    <strong>Objetivo:</strong>
                                    <p class="mt-1 whitespace-pre-line">
                                        {{
                                            item.objective || 'Sin información.'
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl p-3 text-sm leading-6 text-foreground"
                                    :style="primarySoftStyle"
                                >
                                    <strong>Evaluación:</strong>
                                    <p class="mt-1 whitespace-pre-line">
                                        {{
                                            item.assessment || 'Sin evaluación.'
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl p-3 text-sm leading-6 text-foreground"
                                    :style="primarySoftStyle"
                                >
                                    <strong>Plan:</strong>
                                    <p class="mt-1 whitespace-pre-line">
                                        {{ item.plan || 'Sin plan.' }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-4 rounded-2xl border border-dashed border-border bg-muted/30 p-6 text-center text-sm text-muted-foreground"
                    >
                        No hay sesiones clínicas registradas.
                    </div>
                </section>
            </div>

            <div v-if="activeTab === 'citas'" class="space-y-4">
                <section
                    class="rounded-[2rem] border border-border bg-card p-5 shadow-sm"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-semibold text-foreground"
                        >
                            <CalendarClock
                                class="h-4 w-4"
                                :style="{ color: 'var(--primary)' }"
                            />
                            Historial de citas
                        </h2>

                        <Button
                            variant="outline"
                            class="h-10 rounded-xl border-border bg-card transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                            @click="goToAppointments"
                        >
                            Agendar cita
                        </Button>
                    </div>

                    <div
                        v-if="sortedAppointments.length"
                        class="mt-4 grid gap-3 lg:grid-cols-2"
                    >
                        <article
                            v-for="item in sortedAppointments"
                            :key="item.id"
                            class="rounded-[1.5rem] border border-border bg-muted/50 p-4"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-2"
                            >
                                <p
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{ formatDateTimeMx(item.start_at) }}
                                </p>

                                <Badge
                                    class="rounded-full border px-3 py-1 text-xs"
                                    :class="appointmentBadgeClass(item.status)"
                                >
                                    {{ tAppointmentStatus(item.status) }}
                                </Badge>
                            </div>

                            <p class="mt-2 text-xs text-muted-foreground">
                                Terapeuta:
                                {{ item.therapist_name || 'Sin terapeuta' }}
                            </p>

                            <p
                                class="mt-3 text-sm leading-6 text-muted-foreground"
                            >
                                {{ item.notes || 'Sin notas registradas.' }}
                            </p>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-4 rounded-2xl border border-dashed border-border bg-muted/30 p-6 text-center text-sm text-muted-foreground"
                    >
                        No hay citas registradas.
                    </div>
                </section>
            </div>

            <div v-if="activeTab === 'archivos'" class="space-y-4">
                <section
                    class="rounded-[2rem] border border-border bg-card p-5 shadow-sm"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-semibold text-foreground"
                        >
                            <FolderOpen
                                class="h-4 w-4"
                                :style="{ color: 'var(--primary)' }"
                            />
                            Documentos y archivos
                        </h2>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <button
                                type="button"
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-0.5"
                                :style="primaryButtonStyle"
                                @mouseenter="setPrimaryHover"
                                @mouseleave="setPrimaryNormal"
                                @click="goToFiles"
                            >
                                <UploadCloud class="h-4 w-4" />
                                Subir archivo
                            </button>

                            <button
                                type="button"
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-border bg-card px-4 text-sm font-medium text-foreground transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                @click="goToFiles"
                            >
                                Ir a archivos
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="props.files.length"
                        class="mt-4 grid gap-3 lg:grid-cols-2"
                    >
                        <article
                            v-for="item in props.files"
                            :key="item.id"
                            class="rounded-[1.5rem] border border-border bg-muted/50 p-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl"
                                    :style="primarySoftStyle"
                                >
                                    <FileText
                                        class="h-5 w-5"
                                        :style="{ color: 'var(--primary)' }"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-semibold text-foreground"
                                    >
                                        {{ item.original_name }}
                                    </p>

                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{
                                            item.file_type ||
                                            item.mime ||
                                            'Archivo'
                                        }}
                                        ·
                                        {{ formatDateTimeMx(item.created_at) }}
                                        <template v-if="item.session_id">
                                            · Sesión #{{ item.session_id }}
                                        </template>
                                    </p>

                                    <p
                                        v-if="item.uploaded_by_name"
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        Subido por {{ item.uploaded_by_name }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="mt-4 flex flex-wrap gap-2 border-t border-border pt-4"
                            >
                                <a
                                    v-if="item.preview_url"
                                    :href="item.preview_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-border bg-card px-3 text-sm font-medium text-foreground transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                >
                                    <Eye class="h-4 w-4" />
                                    Ver
                                </a>

                                <a
                                    v-if="item.download_url"
                                    :href="item.download_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-border bg-card px-3 text-sm font-medium text-foreground transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                                >
                                    <Download class="h-4 w-4" />
                                    Descargar
                                </a>

                                <button
                                    type="button"
                                    class="inline-flex h-9 items-center justify-center gap-2 rounded-xl px-3 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-0.5"
                                    :style="primaryButtonStyle"
                                    @mouseenter="setPrimaryHover"
                                    @mouseleave="setPrimaryNormal"
                                    @click="goToFiles"
                                >
                                    <UploadCloud class="h-4 w-4" />
                                    Subir más
                                </button>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-4 rounded-2xl border border-dashed border-border bg-muted/30 p-6 text-center text-sm text-muted-foreground"
                    >
                        No hay archivos cargados para este paciente.
                        <div class="mt-4">
                            <button
                                type="button"
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-0.5"
                                :style="primaryButtonStyle"
                                @mouseenter="setPrimaryHover"
                                @mouseleave="setPrimaryNormal"
                                @click="goToFiles"
                            >
                                <UploadCloud class="h-4 w-4" />
                                Subir archivo
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <div v-if="activeTab === 'pagos'" class="space-y-4">
                <section
                    class="rounded-[2rem] border border-border bg-card p-5 shadow-sm"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-semibold text-foreground"
                        >
                            <CreditCard
                                class="h-4 w-4"
                                :style="{ color: 'var(--primary)' }"
                            />
                            Pagos relacionados
                        </h2>

                        <Button
                            variant="outline"
                            class="h-10 rounded-xl border-border bg-card transition-all duration-200 hover:-translate-y-0.5 hover:border-[color:var(--primary)] hover:text-[color:var(--primary)]"
                            @click="goToPayments"
                        >
                            Ir a cobranza
                        </Button>
                    </div>

                    <div
                        v-if="props.payments.length"
                        class="mt-4 grid gap-3 lg:grid-cols-2"
                    >
                        <article
                            v-for="item in props.payments"
                            :key="item.id"
                            class="rounded-[1.5rem] border border-border bg-muted/50 p-4"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-2"
                            >
                                <p
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{
                                        formatMoney(
                                            item.amount,
                                            item.currency || 'MXN',
                                        )
                                    }}
                                </p>

                                <Badge
                                    class="rounded-full border px-3 py-1 text-xs"
                                    :class="paymentBadgeClass(item.status)"
                                >
                                    {{ tPaymentStatus(item.status) }}
                                </Badge>
                            </div>

                            <p class="mt-2 text-xs text-muted-foreground">
                                {{
                                    item.paid_at
                                        ? formatDateTimeMx(item.paid_at)
                                        : item.created_at
                                          ? formatDateTimeMx(item.created_at)
                                          : 'Sin fecha'
                                }}
                            </p>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-4 rounded-2xl border border-dashed border-border bg-muted/30 p-6 text-center text-sm text-muted-foreground"
                    >
                        No hay pagos relacionados.
                    </div>
                </section>
            </div>

            <div v-if="activeTab === 'seguimiento'" class="space-y-4">
                <section
                    class="rounded-[2rem] border border-border bg-card p-5 shadow-sm"
                >
                    <h2
                        class="flex items-center gap-2 text-base font-semibold text-foreground"
                    >
                        <Activity
                            class="h-4 w-4"
                            :style="{ color: 'var(--primary)' }"
                        />
                        Línea de tiempo de seguimiento
                    </h2>

                    <div v-if="timelineItems.length" class="mt-5 space-y-4">
                        <article
                            v-for="item in timelineItems"
                            :key="item.id"
                            class="relative rounded-[1.5rem] border border-border bg-muted/50 p-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl"
                                    :style="primarySoftStyle"
                                >
                                    <component
                                        :is="item.icon"
                                        class="h-5 w-5"
                                        :style="{ color: 'var(--primary)' }"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <Badge
                                            class="rounded-full border border-border bg-card px-3 py-1 text-xs text-muted-foreground"
                                        >
                                            {{ item.type }}
                                        </Badge>

                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDateTimeMx(item.date) }}
                                        </p>
                                    </div>

                                    <p
                                        class="mt-2 text-sm font-semibold text-foreground"
                                    >
                                        {{ item.title }}
                                    </p>

                                    <p
                                        class="mt-1 text-sm leading-6 text-muted-foreground"
                                    >
                                        {{ item.description }}
                                    </p>

                                    <p class="mt-2 text-xs text-muted-foreground">
                                        {{ item.meta }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-4 rounded-2xl border border-dashed border-border bg-muted/30 p-6 text-center text-sm text-muted-foreground"
                    >
                        No hay eventos de seguimiento registrados.
                    </div>
                </section>
            </div>

            <!-- ── Cumplimiento ─────────────────────────────────────────── -->
            <div v-if="activeTab === 'cumplimiento'" class="space-y-5">

                <!-- Aviso de Privacidad -->
                <section class="rounded-[2rem] border border-border bg-card p-5 shadow-sm">
                    <div class="mb-4 border-b border-border pb-3">
                        <h2 class="flex items-center gap-2 text-base font-semibold text-foreground">
                            <ShieldCheck class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                            Aviso de privacidad (LFPDPPP)
                        </h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Registro independiente del aviso de privacidad conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <!-- Status -->
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full"
                                :class="latestPrivacyNotice ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-muted'"
                            >
                                <component
                                    :is="latestPrivacyNotice ? ShieldCheck : ShieldX"
                                    class="h-5 w-5"
                                    :class="latestPrivacyNotice ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400'"
                                />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-foreground">
                                    {{ latestPrivacyNotice ? 'Aviso de privacidad aceptado' : 'Aviso de privacidad pendiente' }}
                                </p>
                                <p v-if="latestPrivacyNotice" class="text-xs text-muted-foreground">
                                    v{{ latestPrivacyNotice.version }} · {{ formatDateTimeMx(latestPrivacyNotice.accepted_at!) }}
                                    <template v-if="latestPrivacyNotice.accepted_by_name"> · {{ latestPrivacyNotice.accepted_by_name }}</template>
                                </p>
                                <p v-else class="text-xs text-zinc-400">Sin registro aún</p>
                            </div>
                        </div>

                        <!-- Register + print buttons -->
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs text-muted-foreground">Versión</label>
                                <input
                                    v-model="privacyForm.version"
                                    type="text"
                                    class="w-20 rounded-lg border border-border bg-card px-2 py-1.5 text-xs text-foreground"
                                    placeholder="1.0"
                                />
                            </div>
                            <div class="flex items-end">
                                <Button
                                    class="h-9 rounded-xl text-xs"
                                    :style="primaryButtonStyle"
                                    :disabled="privacyForm.processing"
                                    @click="registerPrivacyNotice"
                                >
                                    {{ latestPrivacyNotice ? 'Actualizar aceptación' : 'Registrar aceptación' }}
                                </Button>
                            </div>
                            <div class="flex items-end">
                                <a
                                    :href="`/pacientes/${props.patient.id}/cumplimiento/aviso-privacidad/imprimir`"
                                    target="_blank"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-border bg-card px-3 text-xs font-medium text-muted-foreground hover:bg-muted"
                                >
                                    <FileText class="h-3.5 w-3.5" />
                                    Imprimir aviso
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- History -->
                    <div v-if="props.privacyNotices.length > 1" class="mt-4 border-t border-border pt-3">
                        <p class="mb-2 text-xs font-medium text-muted-foreground uppercase tracking-wide">Historial</p>
                        <ul class="space-y-1">
                            <li v-for="pn in props.privacyNotices" :key="pn.id" class="text-xs text-muted-foreground">
                                v{{ pn.version }} — {{ pn.accepted_at ? formatDateTimeMx(pn.accepted_at) : '—' }}
                                <template v-if="pn.accepted_by_name"> — {{ pn.accepted_by_name }}</template>
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- Estado de consentimientos -->
                <section class="rounded-[2rem] border border-border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between border-b border-border pb-3">
                        <h2 class="flex items-center gap-2 text-base font-semibold text-foreground">
                            <ShieldCheck class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                            Estado de consentimientos
                        </h2>
                        <Button
                            variant="outline"
                            class="h-8 rounded-xl text-xs"
                            @click="showConsentForm = !showConsentForm"
                        >
                            {{ showConsentForm ? 'Cancelar' : '+ Registrar consentimiento' }}
                        </Button>
                    </div>

                    <!-- Consent form -->
                    <div v-if="showConsentForm" class="mb-5 rounded-2xl border border-dashed border-border bg-muted/30 p-4">
                        <p class="mb-3 text-sm font-medium text-foreground">Registrar nuevo consentimiento</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted-foreground">Tipo de consentimiento *</label>
                                <select
                                    v-model="consentForm.consent_type"
                                    class="w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-foreground"
                                >
                                    <option value="">Selecciona...</option>
                                    <option v-for="ct in CONSENT_TYPES" :key="ct.key" :value="ct.key">
                                        {{ ct.label }}
                                    </option>
                                </select>
                                <p v-if="consentForm.errors.consent_type" class="mt-1 text-xs text-rose-600">
                                    {{ consentForm.errors.consent_type }}
                                </p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted-foreground">Notas (opcional)</label>
                                <input
                                    v-model="consentForm.notes"
                                    type="text"
                                    placeholder="Ej. Firmado en papel, copia adjunta"
                                    class="w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-foreground"
                                />
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <Button
                                class="h-8 rounded-xl text-xs"
                                :style="primaryButtonStyle"
                                :disabled="!consentForm.consent_type || consentForm.processing"
                                @click="registerConsent"
                            >
                                Registrar
                            </Button>
                        </div>
                    </div>

                    <!-- Status grid -->
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="ct in CONSENT_TYPES"
                            :key="ct.key"
                            class="flex flex-col gap-2 rounded-2xl border p-4 transition-colors"
                            :class="acceptedConsentTypes.has(ct.key)
                                ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/40 dark:bg-emerald-950/20'
                                : 'border-border bg-muted/30'"
                        >
                            <div class="flex items-start gap-3">
                                <component
                                    :is="acceptedConsentTypes.has(ct.key) ? ShieldCheck : ShieldX"
                                    class="mt-0.5 h-5 w-5 shrink-0"
                                    :class="acceptedConsentTypes.has(ct.key)
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-muted-foreground'"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-foreground">{{ ct.label }}</p>
                                    <p v-if="lastConsent(ct.key)" class="mt-0.5 text-xs text-muted-foreground">
                                        Aceptado {{ formatDateTimeMx(lastConsent(ct.key)!.accepted_at) }}
                                        <template v-if="lastConsent(ct.key)!.accepted_by_name">
                                            · por {{ lastConsent(ct.key)!.accepted_by_name }}
                                        </template>
                                    </p>
                                    <p v-else class="mt-0.5 text-xs text-muted-foreground">
                                        Pendiente de registro
                                    </p>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <a
                                    :href="`/pacientes/${props.patient.id}/cumplimiento/${consentPrintSlug(ct.key)}/imprimir`"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 rounded-lg border border-border bg-card px-2.5 py-1 text-[11px] font-medium text-muted-foreground hover:bg-muted"
                                >
                                    <FileText class="h-3 w-3" />
                                    Imprimir formato
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Documentos imprimibles -->
                <section class="rounded-[2rem] border border-border bg-card p-5 shadow-sm">
                    <div class="mb-4 border-b border-border pb-3">
                        <h2 class="flex items-center gap-2 text-base font-semibold text-foreground">
                            <FileText class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                            Documentos imprimibles
                        </h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Abre el formato para que el paciente lo firme físicamente.
                        </p>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <a
                            :href="`/pacientes/${props.patient.id}/cumplimiento/ficha-ingreso/imprimir`"
                            target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-border bg-muted/50 px-3 py-3 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                        >
                            <FileText class="h-4 w-4 shrink-0 text-zinc-400" />
                            Ficha de ingreso
                        </a>
                        <a
                            :href="`/pacientes/${props.patient.id}/cumplimiento/aviso-privacidad/imprimir`"
                            target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-3 text-sm font-medium text-indigo-700 transition-colors hover:bg-indigo-100 dark:border-indigo-900/40 dark:bg-indigo-950/20 dark:text-indigo-300 dark:hover:bg-indigo-900/40"
                        >
                            <ShieldCheck class="h-4 w-4 shrink-0 text-indigo-400" />
                            Aviso de privacidad
                        </a>
                        <a
                            :href="`/pacientes/${props.patient.id}/cumplimiento/consentimiento-tratamiento/imprimir`"
                            target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm font-medium text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-300 dark:hover:bg-emerald-900/40"
                        >
                            <ShieldCheck class="h-4 w-4 shrink-0 text-emerald-400" />
                            Consentimiento de tratamiento
                        </a>
                        <a
                            :href="`/pacientes/${props.patient.id}/cumplimiento/consentimiento-imagenes/imprimir`"
                            target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300 dark:hover:bg-amber-900/40"
                        >
                            <FileText class="h-4 w-4 shrink-0 text-amber-400" />
                            Consentimiento de imágenes
                        </a>
                        <a
                            :href="`/pacientes/${props.patient.id}/cumplimiento/consentimiento-datos-sensibles/imprimir`"
                            target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-purple-200 bg-purple-50 px-3 py-3 text-sm font-medium text-purple-700 transition-colors hover:bg-purple-100 dark:border-purple-900/40 dark:bg-purple-950/20 dark:text-purple-300 dark:hover:bg-purple-900/40"
                        >
                            <ShieldCheck class="h-4 w-4 shrink-0 text-purple-400" />
                            Consentimiento datos sensibles
                        </a>
                    </div>
                </section>

                <!-- Historial de consentimientos -->
                <section class="rounded-[2rem] border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2 border-b border-border pb-3 text-base font-semibold text-foreground">
                        <ShieldAlert class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                        Historial de consentimientos
                    </h2>

                    <div v-if="!props.consents.length" class="rounded-2xl border border-dashed border-border bg-muted/30 p-6 text-center text-sm text-muted-foreground">
                        No hay consentimientos registrados aún.
                    </div>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="c in props.consents"
                            :key="c.id"
                            class="flex items-center gap-3 rounded-2xl border border-border bg-muted/30 px-4 py-3"
                        >
                            <ShieldCheck class="h-4 w-4 shrink-0 text-emerald-500" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-foreground">
                                    {{ consentTypeLabel(c.consent_type) }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDateTimeMx(c.accepted_at) }}
                                    <template v-if="c.accepted_by_name"> · {{ c.accepted_by_name }}</template>
                                    <template v-if="c.notes"> · {{ c.notes }}</template>
                                </p>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>

        </section>
    </AppLayout>
</template>
