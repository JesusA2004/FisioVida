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
    XCircle,
    Clock,
    Printer,
    AlertTriangle,
    Info,
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
import SignaturePad from '@/components/fv/SignaturePad.vue';

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

type LegalAcceptance = {
    id: number;
    document_type: string;
    version: string;
    title?: string | null;
    accepted_at: string;
    status: string; // accepted | revoked
    source: string; // staff | portal
    guardian_name?: string | null;
    guardian_relationship?: string | null;
    revoked_at?: string | null;
    revocation_reason?: string | null;
    accepted_by_name?: string | null;
    revoked_by_name?: string | null;
    // Firma digital simple
    signer_name?: string | null;
    signer_role?: string | null;
    signature_method?: string | null;
    document_hash?: string | null;
    signature_hash?: string | null;
    signed_pdf_path?: string | null;
    signed_at?: string | null;
};

type ComplianceStatus = {
    has_privacy_notice: boolean;
    has_sensitive_data: boolean;
    has_treatment_consent: boolean;
    has_image_consent: boolean;
    has_minor_consent: boolean;
    has_sessions: boolean;
    has_plan: boolean;
    has_emergency_contact: boolean;
    has_responsible_therapist: boolean;
};

// 'privacidad' se gestiona en privacy_notice_acceptances (tabla separada por normativa)
const CONSENT_TYPES = [
    { key: 'tratamiento', label: 'Consentimiento de tratamiento' },
    { key: 'imagenes', label: 'Uso de imágenes / fotografías' },
    { key: 'datos_sensibles', label: 'Datos sensibles de salud' },
] as const;

const LEGAL_DOC_TYPES = [
    { key: 'privacy_notice',    label: 'Aviso de privacidad',                         color: 'indigo',   printSlug: 'aviso-privacidad' },
    { key: 'sensitive_data',    label: 'Datos sensibles de salud',                    color: 'purple',   printSlug: 'consentimiento-datos-sensibles' },
    { key: 'treatment_consent', label: 'Consentimiento de tratamiento',               color: 'emerald',  printSlug: 'consentimiento-tratamiento' },
    { key: 'image_consent',     label: 'Consentimiento de imágenes / evidencia',      color: 'amber',    printSlug: 'consentimiento-imagenes' },
    { key: 'minor_consent',     label: 'Consentimiento para menor de edad',           color: 'rose',     printSlug: null },
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
    legalAcceptances: LegalAcceptance[];
    complianceStatus: ComplianceStatus;
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

// ── Aviso de Privacidad (legacy) ─────────────────────────────────────────────
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

// ── Documentos Legales (firma digital simple) ─────────────────────────────────
const showLegalForm = ref(false);
const staffSignaturePad = ref<InstanceType<typeof SignaturePad> | null>(null);
const staffSignatureData = ref('');

const legalForm = useForm({
    document_type: '',
    version: '',
    guardian_name: '',
    guardian_relationship: '',
    signer_name: '',
    signature_image: '',
});

const showRevokeModal = ref(false);
const revokeTargetId = ref<number | null>(null);
const revokeReason = ref('');

// Obtener la aceptación activa (status=accepted) de un tipo de documento
const legalDocActive = (docType: string): LegalAcceptance | null =>
    props.legalAcceptances.find(a => a.document_type === docType && a.status === 'accepted') ?? null;

// Obtener todos los registros de un tipo (historial)
const legalDocHistory = (docType: string): LegalAcceptance[] =>
    props.legalAcceptances.filter(a => a.document_type === docType);

const totalDocsPending = computed(() =>
    LEGAL_DOC_TYPES.filter(d => !legalDocActive(d.key)).length
);

const totalDocsAccepted = computed(() =>
    LEGAL_DOC_TYPES.filter(d => legalDocActive(d.key) !== null).length
);

const registerLegalDoc = async () => {
    if (!legalForm.document_type) return;
    if (!legalForm.signer_name.trim()) {
        swalToast('Ingresa el nombre del firmante', 'warning');
        return;
    }

    const label = LEGAL_DOC_TYPES.find(d => d.key === legalForm.document_type)?.label ?? legalForm.document_type;
    const hasSignature = staffSignaturePad.value && !staffSignaturePad.value.isEmpty();
    const method = hasSignature ? 'con firma dibujada' : 'sin firma (registro administrativo)';

    const ok = await swalConfirm(
        `¿Registrar "${label}"?`,
        `Se guardará snapshot del documento, ${method}, fecha, IP y nombre del firmante.`,
        'Sí, registrar'
    );
    if (!ok) return;

    legalForm.signature_image = staffSignatureData.value;

    legalForm.post(`/pacientes/${props.patient.id}/documentos-legales`, {
        preserveScroll: true,
        onSuccess: () => {
            swalToast('Documento firmado y registrado', 'success');
            legalForm.reset();
            staffSignatureData.value = '';
            staffSignaturePad.value?.clear();
            showLegalForm.value = false;
        },
        onError: () => swalToast('Revisa el formulario', 'warning'),
    });
};

const startRevoke = (id: number) => {
    revokeTargetId.value = id;
    revokeReason.value = '';
    showRevokeModal.value = true;
};

const confirmRevoke = async () => {
    if (!revokeTargetId.value) return;
    const ok = await swalConfirm('¿Revocar este consentimiento?', 'Esta acción quedará registrada en la bitácora.', 'Sí, revocar');
    if (!ok) return;

    useForm({ reason: revokeReason.value })
        .patch(`/pacientes/${props.patient.id}/documentos-legales/${revokeTargetId.value}/revocar`, {
            preserveScroll: true,
            onSuccess: () => {
                swalToast('Consentimiento revocado', 'success');
                showRevokeModal.value = false;
                revokeTargetId.value = null;
            },
            onError: () => swalToast('No se pudo revocar', 'error'),
        });
};

// Colores por tipo de documento
const docColorClasses = (color: string, accepted: boolean) => {
    if (!accepted) return 'border-border bg-muted/30 dark:border-border dark:bg-muted/10';
    const map: Record<string, string> = {
        indigo: 'border-indigo-200 bg-indigo-50/60 dark:border-indigo-900/40 dark:bg-indigo-950/20',
        purple: 'border-purple-200 bg-purple-50/60 dark:border-purple-900/40 dark:bg-purple-950/20',
        emerald: 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/40 dark:bg-emerald-950/20',
        amber: 'border-amber-200 bg-amber-50/60 dark:border-amber-900/40 dark:bg-amber-950/20',
        rose: 'border-rose-200 bg-rose-50/60 dark:border-rose-900/40 dark:bg-rose-950/20',
    };
    return map[color] ?? 'border-border bg-muted/30';
};

const docIconColor = (color: string, accepted: boolean) => {
    if (!accepted) return 'text-muted-foreground';
    const map: Record<string, string> = {
        indigo: 'text-indigo-600 dark:text-indigo-400',
        purple: 'text-purple-600 dark:text-purple-400',
        emerald: 'text-emerald-600 dark:text-emerald-400',
        amber: 'text-amber-600 dark:text-amber-400',
        rose: 'text-rose-600 dark:text-rose-400',
    };
    return map[color] ?? 'text-muted-foreground';
};

// NOM-004 checklist items
const nom004Items = computed(() => [
    { label: 'Identificación del paciente',          ok: true }, // siempre: tiene expediente
    { label: 'Contacto de emergencia',               ok: props.complianceStatus.has_emergency_contact },
    { label: 'Aviso de privacidad aceptado',         ok: props.complianceStatus.has_privacy_notice },
    { label: 'Consentimiento de datos sensibles',    ok: props.complianceStatus.has_sensitive_data },
    { label: 'Consentimiento de tratamiento',        ok: props.complianceStatus.has_treatment_consent },
    { label: 'Sesiones / evolución clínica',         ok: props.complianceStatus.has_sessions },
    { label: 'Plan de tratamiento registrado',       ok: props.complianceStatus.has_plan },
    { label: 'Responsable de atención identificado', ok: props.complianceStatus.has_responsible_therapist },
]);

const nom004Score = computed(() => nom004Items.value.filter(i => i.ok).length);
const nom004Total = computed(() => nom004Items.value.length);
const expedienteStatus = computed(() => {
    const pct = nom004Score.value / nom004Total.value;
    if (pct >= 1) return { label: 'Completo', color: 'emerald' };
    if (pct >= 0.6) return { label: 'Incompleto', color: 'amber' };
    return { label: 'Requiere atención', color: 'rose' };
});
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

                <!-- Header de cumplimiento con resumen -->
                <section class="rounded-[2rem] border border-border bg-card p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl shadow-md"
                                :style="{ backgroundColor: 'var(--primary)', color: 'var(--primary-foreground)' }">
                                <ShieldCheck class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">Cumplimiento documental</h2>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Herramientas de apoyo documental — No reemplaza asesoría jurídica.
                                </p>
                            </div>
                        </div>
                        <!-- Score chips -->
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                {{ totalDocsAccepted }} / {{ LEGAL_DOC_TYPES.length }} documentos aceptados
                            </span>
                            <span v-if="totalDocsPending > 0" class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                {{ totalDocsPending }} pendiente{{ totalDocsPending !== 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Documentos legales (tabla unificada con snapshot) -->
                <section class="rounded-[2rem] border border-border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between border-b border-border pb-3">
                        <h2 class="flex items-center gap-2 text-base font-semibold text-foreground">
                            <ShieldCheck class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                            Documentos de cumplimiento
                        </h2>
                        <Button variant="outline" class="h-8 rounded-xl text-xs" @click="showLegalForm = !showLegalForm">
                            {{ showLegalForm ? 'Cancelar' : '+ Registrar documento' }}
                        </Button>
                    </div>

                    <!-- Formulario de registro legal con firma digital -->
                    <div v-if="showLegalForm" class="mb-5 rounded-2xl border border-dashed border-border bg-muted/30 p-4 space-y-4">
                        <div>
                            <p class="text-sm font-semibold text-foreground">Registrar documento con firma digital simple</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Se generará snapshot del texto, hashes de trazabilidad y PDF firmado.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted-foreground">Documento *</label>
                                <select v-model="legalForm.document_type" class="w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-foreground">
                                    <option value="">Selecciona el documento...</option>
                                    <option v-for="d in LEGAL_DOC_TYPES" :key="d.key" :value="d.key">{{ d.label }}</option>
                                </select>
                                <p v-if="legalForm.errors.document_type" class="mt-1 text-xs text-rose-600">{{ legalForm.errors.document_type }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted-foreground">Nombre del firmante *</label>
                                <input v-model="legalForm.signer_name" type="text" placeholder="Nombre completo de quien firma" class="w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-foreground" />
                                <p v-if="legalForm.errors.signer_name" class="mt-1 text-xs text-rose-600">{{ legalForm.errors.signer_name }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted-foreground">Versión (opcional)</label>
                                <input v-model="legalForm.version" type="text" placeholder="Ej. 1.0 (usa la configurada si vacío)" class="w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-foreground" />
                            </div>
                        </div>

                        <!-- Campos para menor de edad -->
                        <template v-if="legalForm.document_type === 'minor_consent'">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Nombre del tutor / responsable</label>
                                    <input v-model="legalForm.guardian_name" type="text" placeholder="Nombre completo del tutor" class="w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-foreground" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Parentesco</label>
                                    <input v-model="legalForm.guardian_relationship" type="text" placeholder="Ej. Madre, Padre, Tutor legal" class="w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-foreground" />
                                </div>
                            </div>
                        </template>

                        <!-- Firma dibujada -->
                        <div>
                            <label class="mb-2 block text-xs font-medium text-muted-foreground">
                                Firma dibujada del firmante (recomendado — tablet o mouse)
                            </label>
                            <SignaturePad
                                ref="staffSignaturePad"
                                v-model="staffSignatureData"
                                :height="150"
                            />
                            <p v-if="legalForm.errors.signature_image" class="mt-1 text-xs text-rose-600">{{ legalForm.errors.signature_image }}</p>
                        </div>

                        <!-- Aviso legal -->
                        <div class="rounded-xl border border-amber-200 bg-amber-50/60 px-3 py-2 text-xs text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                            Esta firma digital simple registra evidencia de aceptación dentro del sistema. La clínica debe validar sus documentos con asesor jurídico.
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button variant="outline" class="h-8 rounded-xl text-xs border-border" @click="showLegalForm = false; staffSignaturePad?.clear(); staffSignatureData = ''">
                                Cancelar
                            </Button>
                            <Button class="h-8 rounded-xl text-xs" :style="primaryButtonStyle" :disabled="!legalForm.document_type || !legalForm.signer_name || legalForm.processing" @click="registerLegalDoc">
                                {{ legalForm.processing ? 'Registrando...' : 'Firmar y registrar' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Checklist visual por documento -->
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="doc in LEGAL_DOC_TYPES"
                            :key="doc.key"
                            class="flex flex-col gap-3 rounded-2xl border p-4 transition-colors"
                            :class="docColorClasses(doc.color, legalDocActive(doc.key) !== null)"
                        >
                            <!-- Header del documento -->
                            <div class="flex items-start gap-3">
                                <component
                                    :is="legalDocActive(doc.key) ? ShieldCheck : (legalDocHistory(doc.key).some(a => a.status === 'revoked') ? XCircle : ShieldX)"
                                    class="mt-0.5 h-5 w-5 shrink-0"
                                    :class="docIconColor(doc.color, legalDocActive(doc.key) !== null)"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-foreground">{{ doc.label }}</p>

                                    <!-- Aceptado -->
                                    <template v-if="legalDocActive(doc.key)">
                                        <div class="mt-1 space-y-0.5">
                                            <p class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Aceptado</p>
                                            <p class="text-[11px] text-muted-foreground">
                                                v{{ legalDocActive(doc.key)!.version }} · {{ formatDateTimeMx(legalDocActive(doc.key)!.accepted_at) }}
                                            </p>
                                            <p v-if="legalDocActive(doc.key)!.signer_name" class="text-[11px] text-muted-foreground">
                                                Firmante: {{ legalDocActive(doc.key)!.signer_name }}
                                            </p>
                                            <p v-if="legalDocActive(doc.key)!.accepted_by_name && !legalDocActive(doc.key)!.signer_name" class="text-[11px] text-muted-foreground">
                                                Por: {{ legalDocActive(doc.key)!.accepted_by_name }}
                                            </p>
                                            <p class="text-[11px]"
                                               :class="legalDocActive(doc.key)!.signature_method === 'drawn_signature' ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'">
                                                {{ legalDocActive(doc.key)!.signature_method === 'drawn_signature' ? '✓ Firma dibujada' : legalDocActive(doc.key)!.signature_method === 'staff_recorded' ? 'Registro administrativo' : '' }}
                                            </p>
                                            <p v-if="legalDocActive(doc.key)!.source === 'portal'" class="text-[11px] text-indigo-600 dark:text-indigo-400">
                                                Origen: portal del paciente
                                            </p>
                                            <p v-if="legalDocActive(doc.key)!.guardian_name" class="text-[11px] text-muted-foreground">
                                                Tutor: {{ legalDocActive(doc.key)!.guardian_name }} ({{ legalDocActive(doc.key)!.guardian_relationship }})
                                            </p>
                                            <p v-if="legalDocActive(doc.key)!.document_hash" class="text-[10px] text-muted-foreground font-mono">
                                                Hash: {{ legalDocActive(doc.key)!.document_hash?.substring(0, 16) }}…
                                            </p>
                                        </div>
                                    </template>

                                    <!-- Revocado sin nuevo activo -->
                                    <template v-else-if="legalDocHistory(doc.key).some(a => a.status === 'revoked')">
                                        <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">Revocado</p>
                                        <p class="text-[11px] text-muted-foreground">Requiere nueva aceptación</p>
                                    </template>

                                    <!-- Pendiente -->
                                    <template v-else>
                                        <p class="mt-1 text-xs text-muted-foreground">Pendiente de registro</p>
                                    </template>
                                </div>
                            </div>

                            <!-- Acciones del documento -->
                            <div class="flex flex-wrap items-center gap-1.5 border-t border-border/50 pt-2">
                                <a v-if="doc.printSlug"
                                    :href="`/pacientes/${props.patient.id}/cumplimiento/${doc.printSlug}/imprimir`"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 rounded-lg border border-border bg-card px-2 py-1 text-[11px] font-medium text-muted-foreground hover:bg-muted"
                                >
                                    <Printer class="h-3 w-3" />
                                    Imprimir
                                </a>
                                <!-- PDF firmado: ver en línea y descargar -->
                                <template v-if="legalDocActive(doc.key)?.signed_pdf_path">
                                    <a
                                        :href="`/pacientes/${props.patient.id}/documentos-legales/${legalDocActive(doc.key)!.id}/ver`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2 py-1 text-[11px] font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-400"
                                        title="Ver PDF en el navegador"
                                    >
                                        <Eye class="h-3 w-3" />
                                        Ver PDF
                                    </a>
                                    <a
                                        :href="`/pacientes/${props.patient.id}/documentos-legales/${legalDocActive(doc.key)!.id}/descargar`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-400"
                                        title="Descargar PDF firmado"
                                    >
                                        <Download class="h-3 w-3" />
                                        PDF
                                    </a>
                                </template>
                                <button v-if="legalDocActive(doc.key)"
                                    class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-medium text-rose-700 hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-400"
                                    @click="startRevoke(legalDocActive(doc.key)!.id)"
                                >
                                    <XCircle class="h-3 w-3" />
                                    Revocar
                                </button>
                            </div>

                            <!-- Historial de versiones colapsado -->
                            <div v-if="legalDocHistory(doc.key).length > 1" class="border-t border-border/50 pt-2">
                                <details class="text-[11px] text-muted-foreground">
                                    <summary class="cursor-pointer hover:text-foreground">Ver historial ({{ legalDocHistory(doc.key).length }} registros)</summary>
                                    <ul class="mt-1.5 space-y-1 pl-2">
                                        <li v-for="h in legalDocHistory(doc.key)" :key="h.id" class="flex items-start gap-1">
                                            <ShieldCheck v-if="h.status === 'accepted'" class="mt-0.5 h-3 w-3 shrink-0 text-emerald-500" />
                                            <XCircle v-else class="mt-0.5 h-3 w-3 shrink-0 text-rose-500" />
                                            <span>v{{ h.version }} — {{ formatDateMx(h.accepted_at) }} ({{ h.status === 'revoked' ? 'revocado' : 'aceptado' }})</span>
                                        </li>
                                    </ul>
                                </details>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Modal de revocación -->
                <div v-if="showRevokeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div class="w-full max-w-md rounded-[2rem] border border-border bg-card p-6 shadow-xl">
                        <h3 class="mb-2 text-base font-semibold text-foreground">Revocar consentimiento</h3>
                        <p class="mb-4 text-sm text-muted-foreground">
                            Esta acción quedará registrada en la bitácora de auditoría. El paciente puede requerir firmar nuevamente.
                        </p>
                        <div class="mb-4">
                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Motivo (opcional)</label>
                            <textarea v-model="revokeReason" rows="3" class="w-full rounded-xl border border-border bg-card px-3 py-2 text-sm text-foreground" placeholder="Ej. El paciente solicitó revocar, cambio de decisión..."></textarea>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <Button variant="outline" class="rounded-xl" @click="showRevokeModal = false">Cancelar</Button>
                            <Button class="rounded-xl bg-rose-600 text-white hover:bg-rose-700" @click="confirmRevoke">Confirmar revocación</Button>
                        </div>
                    </div>
                </div>

                <!-- NOM-004 / Estado del expediente -->
                <section class="rounded-[2rem] border border-border bg-card p-5 shadow-sm">
                    <div class="mb-4 border-b border-border pb-3">
                        <div class="flex items-center justify-between">
                            <h2 class="flex items-center gap-2 text-base font-semibold text-foreground">
                                <ClipboardList class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                                Estado del expediente clínico
                            </h2>
                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                                :class="{
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300': expedienteStatus.color === 'emerald',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300': expedienteStatus.color === 'amber',
                                    'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300': expedienteStatus.color === 'rose',
                                }"
                            >
                                {{ expedienteStatus.label }} ({{ nom004Score }}/{{ nom004Total }})
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Checklist de buenas prácticas basado en NOM-004-SSA3-2012. No certifica cumplimiento legal.
                        </p>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <div
                            v-for="item in nom004Items"
                            :key="item.label"
                            class="flex items-center gap-3 rounded-xl border px-3 py-2.5"
                            :class="item.ok
                                ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/40 dark:bg-emerald-950/20'
                                : 'border-amber-200 bg-amber-50/60 dark:border-amber-900/40 dark:bg-amber-950/20'"
                        >
                            <CheckCircle2 v-if="item.ok" class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                            <AlertTriangle v-else class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                            <p class="text-sm text-foreground">{{ item.label }}</p>
                        </div>
                    </div>

                    <!-- Warnings específicos -->
                    <div v-if="!props.complianceStatus.has_emergency_contact || !props.complianceStatus.has_privacy_notice || !props.complianceStatus.has_treatment_consent || !props.complianceStatus.has_sessions"
                         class="mt-4 rounded-2xl border border-amber-200 bg-amber-50/60 p-3 dark:border-amber-900/40 dark:bg-amber-950/20">
                        <p class="mb-2 flex items-center gap-2 text-xs font-semibold text-amber-700 dark:text-amber-400">
                            <AlertTriangle class="h-3.5 w-3.5" /> Elementos pendientes
                        </p>
                        <ul class="space-y-1 text-xs text-amber-700 dark:text-amber-400">
                            <li v-if="!props.complianceStatus.has_emergency_contact">• Falta contacto de emergencia en la ficha del paciente</li>
                            <li v-if="!props.complianceStatus.has_privacy_notice">• Falta aviso de privacidad aceptado</li>
                            <li v-if="!props.complianceStatus.has_treatment_consent">• Falta consentimiento de tratamiento</li>
                            <li v-if="!props.complianceStatus.has_sessions">• No hay sesión clínica inicial registrada</li>
                            <li v-if="!props.complianceStatus.has_plan">• No hay plan de tratamiento registrado</li>
                        </ul>
                    </div>
                </section>

                <!-- Documentos imprimibles -->
                <section class="rounded-[2rem] border border-border bg-card p-5 shadow-sm">
                    <div class="mb-4 border-b border-border pb-3">
                        <h2 class="flex items-center gap-2 text-base font-semibold text-foreground">
                            <Printer class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                            Documentos imprimibles
                        </h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Abre el formato para que el paciente lo revise y firme físicamente.
                        </p>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <a :href="`/pacientes/${props.patient.id}/cumplimiento/ficha-ingreso/imprimir`" target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-border bg-muted/50 px-3 py-3 text-sm font-medium text-foreground transition-colors hover:bg-muted">
                            <FileText class="h-4 w-4 shrink-0 text-zinc-400" />
                            Ficha de ingreso
                        </a>
                        <a :href="`/pacientes/${props.patient.id}/cumplimiento/aviso-privacidad/imprimir`" target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-3 text-sm font-medium text-indigo-700 transition-colors hover:bg-indigo-100 dark:border-indigo-900/40 dark:bg-indigo-950/20 dark:text-indigo-300 dark:hover:bg-indigo-900/40">
                            <ShieldCheck class="h-4 w-4 shrink-0 text-indigo-400" />
                            Aviso de privacidad
                        </a>
                        <a :href="`/pacientes/${props.patient.id}/cumplimiento/consentimiento-tratamiento/imprimir`" target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm font-medium text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-300 dark:hover:bg-emerald-900/40">
                            <ShieldCheck class="h-4 w-4 shrink-0 text-emerald-400" />
                            Consentimiento de tratamiento
                        </a>
                        <a :href="`/pacientes/${props.patient.id}/cumplimiento/consentimiento-imagenes/imprimir`" target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300 dark:hover:bg-amber-900/40">
                            <FileText class="h-4 w-4 shrink-0 text-amber-400" />
                            Consentimiento de imágenes
                        </a>
                        <a :href="`/pacientes/${props.patient.id}/cumplimiento/consentimiento-datos-sensibles/imprimir`" target="_blank"
                            class="flex items-center gap-2 rounded-xl border border-purple-200 bg-purple-50 px-3 py-3 text-sm font-medium text-purple-700 transition-colors hover:bg-purple-100 dark:border-purple-900/40 dark:bg-purple-950/20 dark:text-purple-300 dark:hover:bg-purple-900/40">
                            <ShieldCheck class="h-4 w-4 shrink-0 text-purple-400" />
                            Consentimiento datos sensibles
                        </a>
                    </div>
                </section>
            </div>

        </section>
    </AppLayout>
</template>
