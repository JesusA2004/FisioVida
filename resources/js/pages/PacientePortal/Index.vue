<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    CalendarClock, Dumbbell, FileText, Activity,
    HeartPulse, UserRound, Clock, ExternalLink,
    Download, AlertCircle, CheckCircle2, ChevronDown, ChevronUp,
    Building2, Phone, Mail, Plus, X, Loader2, Play,
    CalendarPlus, TrendingUp, ClipboardList, Star, ShieldCheck,
} from 'lucide-vue-next';
import { formatDateMx, formatDateTimeMx } from '@/lib/dates';
import { tAppointmentStatus } from '@/lib/labels';
import { youtubeThumbnail, youtubeEmbedUrl, isYoutubeUrl } from '@/lib/youtube';
import VueApexCharts from 'vue3-apexcharts';
import DatePicker from '@/components/ui/DatePicker.vue';
import SignaturePad from '@/components/fv/SignaturePad.vue';

// ───────── Types ─────────
type Patient = {
    id: number; full_name: string; nombres: string;
    fecha_nacimiento?: string | null; sexo?: string | null;
    telefono?: string | null; email?: string | null;
    direccion?: string | null; notas?: string | null;
};
type Appointment = { id: number; status: string; start_at: string; end_at?: string | null; therapist_name?: string | null; };
type Session = { id: number; session_date: string; pain_scale?: number | null; plan?: string | null; subjective?: string | null; notes?: string | null; therapist_name?: string | null; };
type Exercise = { session_id: number; exercise_id: number; name: string; description?: string | null; video_url?: string | null; sets?: number | null; reps?: number | null; seconds?: number | null; exercise_notes?: string | null; };
type PatientFile = { id: number; original_name: string; file_type?: string | null; mime?: string | null; size_bytes?: number | null; created_at: string; uploaded_by_name?: string | null; preview_url: string; download_url: string; is_image?: boolean; };
type Activity = { id: number; title: string; status: string; priority?: string | null; due_date?: string | null; };
type MyRequest = { id: number; preferred_date: string; preferred_time?: string | null; reason?: string | null; status: string; rejection_reason?: string | null; reviewed_at?: string | null; created_at: string; };
type Stats = { total_sessions: number; total_appts: number; active_exercises: number; last_pain_scale: number | null; next_appointment: { start_at: string; therapist_name?: string } | null; pending_requests: number; };
type ClinicSettings = Record<string, string | null>;

type LegalAcceptance = {
    id: number;
    document_type: string;
    version: string;
    title?: string | null;
    accepted_at: string;
    status: string; // accepted | revoked
    source: string; // staff | portal
    signer_name?: string | null;
    signature_method?: string | null;
    signed_pdf_path?: string | null;
    signed_at?: string | null;
};

type ComplianceSettings = Record<string, string | null>;

// ───────── Props ─────────
const props = defineProps<{
    unlinked: boolean;
    patient: Patient | null;
    stats: Stats;
    upcomingAppointments?: Appointment[];
    recentAppointments?: Appointment[];
    sessions?: Session[];
    painHistory?: { date: string; value: number }[];
    exercises?: Exercise[];
    files?: PatientFile[];
    activities?: Activity[];
    consents?: any[];
    legalAcceptances?: LegalAcceptance[];
    privacyNotices?: any[];
    complianceSettings?: ComplianceSettings;
    myRequests?: MyRequest[];
    clinicSettings?: ClinicSettings;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mi Portal', href: '/mi-portal' }];

// ───────── Tabs ─────────
type Tab = 'inicio' | 'citas' | 'ejercicios' | 'archivos' | 'solicitudes' | 'privacidad';
const activeTab = ref<Tab>('inicio');
const tabs: { key: Tab; label: string; icon: any }[] = [
    { key: 'inicio',      label: 'Inicio',          icon: HeartPulse },
    { key: 'citas',       label: 'Mis Citas 🗓️',     icon: CalendarClock },
    { key: 'ejercicios',  label: 'Ejercicios 💪',    icon: Dumbbell },
    { key: 'archivos',    label: 'Archivos',         icon: FileText },
    { key: 'solicitudes', label: 'Solicitar cita',   icon: CalendarPlus },
    { key: 'privacidad',  label: 'Privacidad',       icon: Star },
];

// ───────── Video modal ─────────
const videoOpen   = ref(false);
const videoEmbedUrl = ref<string | null>(null);
const openVideo = (url: string) => {
    videoEmbedUrl.value = youtubeEmbedUrl(url);
    videoOpen.value = true;
};
const closeVideo = () => { videoOpen.value = false; videoEmbedUrl.value = null; };

// ───────── Solicitud de cita ─────────
const showRequestForm = ref(false);
const requestSaving   = ref(false);
const requestForm     = ref({ preferred_date: '', preferred_time: '', reason: '', notes: '' });
const getCsrf = () => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

const sendRequest = async () => {
    if (!requestForm.value.preferred_date) return;
    requestSaving.value = true;
    try {
        const res = await fetch('/solicitudes-cita', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrf() },
            body: JSON.stringify(requestForm.value),
        });
        if (res.ok) {
            showRequestForm.value = false;
            requestForm.value = { preferred_date: '', preferred_time: '', reason: '', notes: '' };
            router.reload();
        }
    } finally {
        requestSaving.value = false;
    }
};

const cancelRequest = async (id: number) => {
    const res = await fetch(`/solicitudes-cita/${id}/cancelar`, {
        method: 'PATCH',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrf() },
    });
    if (res.ok) router.reload();
};

// ───────── Helpers ─────────
const primaryStyle = { backgroundColor: 'var(--primary)', color: 'var(--primary-foreground)' };

const painColor = (v: number | null) => {
    if (v === null) return 'text-muted-foreground';
    if (v <= 3)  return 'text-emerald-600 dark:text-emerald-400';
    if (v <= 6)  return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
};

const edad = (dob: string | null | undefined) => {
    if (!dob) return null;
    const b = new Date(dob), n = new Date();
    let a = n.getFullYear() - b.getFullYear();
    if (n.getMonth() - b.getMonth() < 0 || (n.getMonth() === b.getMonth() && n.getDate() < b.getDate())) a--;
    return a;
};

const fileIcon = (mime: string | null | undefined) => {
    if (!mime) return '📄';
    if (mime.startsWith('image/')) return '🖼️';
    if (mime === 'application/pdf') return '📋';
    return '📄';
};

const requestStatusBadge = (status: string) => {
    const m: Record<string, string> = {
        pending:  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
        cancelled:'bg-muted text-muted-foreground',
    };
    return m[status] ?? 'bg-muted text-muted-foreground';
};
const requestStatusLabel = (s: string) => ({ pending:'Pendiente', approved:'Aprobada', rejected:'No disponible', cancelled:'Cancelada' }[s] ?? s);

// ───────── ApexCharts: dolor ─────────
const painDates  = computed(() => (props.painHistory ?? []).map(p => p.date));
const painValues = computed(() => (props.painHistory ?? []).map(p => p.value));

const painChartSeries = computed(() => [{
    name: 'Escala de dolor',
    data: painValues.value,
}]);

const painChartOptions = computed(() => ({
    chart: { type: 'area', toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2, colors: ['var(--primary)'] },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
    colors: ['var(--primary)'],
    xaxis: {
        categories: painDates.value,
        labels: { style: { colors: 'var(--muted-foreground)', fontSize: '11px' } },
        axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: {
        min: 0, max: 10,
        labels: { style: { colors: 'var(--muted-foreground)', fontSize: '11px' } },
    },
    tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
    grid: { borderColor: 'var(--border)', strokeDashArray: 4 },
}));

// ───────── Computed ─────────
const todayIso = computed(() => {
    const n = new Date();
    return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
});

const hasSessions    = computed(() => (props.sessions?.length ?? 0) > 0);
const hasExercises   = computed(() => (props.exercises?.length ?? 0) > 0);
const hasFiles       = computed(() => (props.files?.length ?? 0) > 0);
const hasActivities  = computed(() => (props.activities?.length ?? 0) > 0);
const hasPainHistory = computed(() => (props.painHistory?.length ?? 0) > 1);
const hasRequests    = computed(() => (props.myRequests?.length ?? 0) > 0);
const pendingRequests = computed(() => (props.myRequests ?? []).filter(r => r.status === 'pending'));
const latestPlan     = computed(() => props.sessions?.find(s => s.plan)?.plan ?? null);

// ───────── Privacidad / documentos legales ─────────
const PORTAL_DOCS = [
    { key: 'privacy_notice',    label: 'Aviso de privacidad',                    textKey: 'privacy_notice_text',        required: true },
    { key: 'sensitive_data',    label: 'Consentimiento de datos sensibles',       textKey: 'sensitive_data_consent_text', required: true },
    { key: 'treatment_consent', label: 'Consentimiento de tratamiento',           textKey: 'treatment_consent_text',     required: false },
    { key: 'image_consent',     label: 'Consentimiento de imágenes / evidencia',  textKey: 'image_consent_text',         required: false },
] as const;

const legalActive = (docType: string): LegalAcceptance | null =>
    (props.legalAcceptances ?? []).find(a => a.document_type === docType && a.status === 'accepted') ?? null;

const portalAllowed = computed(() =>
    (props.complianceSettings?.allow_patient_portal_acceptance ?? '1') !== '0'
);

const pendingDocs = computed(() =>
    PORTAL_DOCS.filter(d => !legalActive(d.key))
);

const showDocText = ref<string | null>(null);
const acceptingDoc = ref<string | null>(null);
const acceptSaving = ref(false);
const acceptError  = ref('');
const portalSignaturePad = ref<InstanceType<typeof SignaturePad> | null>(null);
const portalSignatureData = ref('');

const acceptForm = ref({
    document_type: '',
    confirmed: false,
    signer_name: '',
    guardian_name: '',
    guardian_relationship: '',
});

const startAccept = (docKey: string) => {
    acceptForm.value = {
        document_type: docKey,
        confirmed: false,
        signer_name: props.patient?.full_name ?? '',
        guardian_name: '',
        guardian_relationship: '',
    };
    portalSignatureData.value = '';
    acceptError.value = '';
    acceptingDoc.value = docKey;
    showDocText.value = null;
};

const getCsrfPortal = () =>
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

const canSubmitAccept = computed(() =>
    acceptForm.value.confirmed &&
    !!acceptForm.value.document_type &&
    !!acceptForm.value.signer_name.trim() &&
    !!portalSignatureData.value
);

const submitAccept = async () => {
    acceptError.value = '';

    if (!acceptForm.value.confirmed) {
        acceptError.value = 'Debes marcar la casilla de aceptación.';
        return;
    }
    if (!acceptForm.value.signer_name.trim()) {
        acceptError.value = 'Ingresa tu nombre completo.';
        return;
    }
    if (!portalSignatureData.value) {
        acceptError.value = 'Se requiere firma dibujada. Dibuja tu firma en el recuadro.';
        return;
    }
    if (!acceptForm.value.document_type) return;

    acceptSaving.value = true;
    try {
        const body: Record<string, unknown> = {
            document_type: acceptForm.value.document_type,
            confirmed: true,
            signer_name: acceptForm.value.signer_name.trim(),
            signature_image: portalSignatureData.value,
        };

        if (acceptForm.value.guardian_name.trim()) {
            body.guardian_name = acceptForm.value.guardian_name.trim();
            body.guardian_relationship = acceptForm.value.guardian_relationship.trim();
        }

        const res = await fetch('/mi-portal/documentos/aceptar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfPortal(),
            },
            body: JSON.stringify(body),
        });

        const json = await res.json().catch(() => ({}));

        if (res.ok || res.status === 200) {
            acceptingDoc.value = null;
            portalSignatureData.value = '';
            router.reload();
        } else if (res.status === 422) {
            const errs = json?.errors ?? {};
            acceptError.value = Object.values(errs).flat().join(' ') || 'Revisa los datos ingresados.';
        } else {
            acceptError.value = json?.message ?? 'Error al registrar. Intenta de nuevo.';
        }
    } finally {
        acceptSaving.value = false;
    }
};
</script>

<template>
    <Head :title="patient?.full_name ? `Mi Portal — ${patient.full_name}` : 'Mi Portal'" />
    <AppLayout :breadcrumbs="breadcrumbs">

        <!-- Video modal -->
        <Teleport to="body">
            <div v-if="videoOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="closeVideo">
                <div class="relative w-full max-w-3xl rounded-2xl overflow-hidden shadow-2xl bg-black">
                    <button class="absolute top-3 right-3 z-10 h-8 w-8 flex items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80" @click="closeVideo">
                        <X class="h-4 w-4" />
                    </button>
                    <div class="aspect-video w-full">
                        <iframe v-if="videoEmbedUrl" :src="videoEmbedUrl" class="h-full w-full" frameborder="0" allowfullscreen allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" />
                    </div>
                </div>
            </div>
        </Teleport>

        <section class="space-y-5 pb-10">

            <!-- Estado sin vínculo -->
            <div v-if="unlinked" class="fv-empty-state">
                <HeartPulse class="h-12 w-12 text-muted-foreground opacity-40" />
                <h3 class="mt-4 text-lg font-semibold text-foreground">Portal no configurado</h3>
                <p class="mt-1 text-sm text-muted-foreground max-w-sm text-center">Tu cuenta no está vinculada a un expediente clínico. Contacta a la clínica para que lo activen.</p>
                <div v-if="clinicSettings?.clinic_phone" class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                    <Phone class="h-4 w-4" /> {{ clinicSettings.clinic_phone }}
                </div>
            </div>

            <template v-else>

                <!-- Header -->
                <div class="fv-gradient-panel relative overflow-hidden">
                    <div class="pointer-events-none absolute -top-12 -right-12 h-40 w-40 rounded-full blur-3xl opacity-20" :style="{ backgroundColor: 'var(--primary)' }" />
                    <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl text-xl font-bold shadow-lg shrink-0" :style="primaryStyle">
                                {{ patient?.nombres?.charAt(0) ?? '?' }}
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-foreground">{{ patient?.full_name }}</h1>
                                <p class="text-sm text-muted-foreground">
                                    <span v-if="patient?.fecha_nacimiento">{{ edad(patient.fecha_nacimiento) }} años</span>
                                    <span v-if="patient?.sexo"> · {{ patient.sexo === 'M' ? 'Masculino' : 'Femenino' }}</span>
                                    <span v-if="patient?.telefono"> · {{ patient.telefono }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <div class="fv-kpi-card !p-3 min-w-[80px] text-center">
                                <p class="text-xs text-muted-foreground">Sesiones</p>
                                <p class="text-2xl font-bold text-foreground">{{ stats.total_sessions }}</p>
                            </div>
                            <div class="fv-kpi-card !p-3 min-w-[80px] text-center">
                                <p class="text-xs text-muted-foreground">Ejercicios</p>
                                <p class="text-2xl font-bold" :style="{ color: 'var(--primary)' }">{{ stats.active_exercises }}</p>
                            </div>
                            <div v-if="stats.last_pain_scale !== null" class="fv-kpi-card !p-3 min-w-[80px] text-center">
                                <p class="text-xs text-muted-foreground">Último dolor</p>
                                <p class="text-2xl font-bold" :class="painColor(stats.last_pain_scale)">{{ stats.last_pain_scale }}/10</p>
                            </div>
                        </div>
                    </div>

                    <!-- Próxima cita banner -->
                    <div v-if="stats.next_appointment" class="mt-4 flex items-center gap-3 rounded-2xl bg-card/60 px-4 py-3 border border-border">
                        <CalendarClock class="h-5 w-5 shrink-0" :style="{ color: 'var(--primary)' }" />
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">Próxima cita</p>
                            <p class="text-sm font-semibold text-foreground">{{ formatDateTimeMx(stats.next_appointment.start_at) }}<span v-if="stats.next_appointment.therapist_name"> — {{ stats.next_appointment.therapist_name }}</span></p>
                        </div>
                        <Button size="sm" variant="outline" class="ml-auto shrink-0 rounded-xl text-xs h-8 border-border" @click="activeTab = 'citas'">Ver citas</Button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-1 overflow-x-auto rounded-2xl bg-muted/40 p-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-medium whitespace-nowrap transition-all"
                        :class="activeTab === tab.key
                            ? 'text-white shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'"
                        :style="activeTab === tab.key ? primaryStyle : {}"
                        @click="activeTab = tab.key"
                    >
                        <component :is="tab.icon" class="h-3.5 w-3.5" />
                        {{ tab.label }}
                        <span v-if="tab.key === 'solicitudes' && pendingRequests.length" class="ml-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-white/30 text-[10px] font-bold">{{ pendingRequests.length }}</span>
                    </button>
                </div>

                <!-- ═══════ TAB: INICIO ═══════ -->
                <div v-if="activeTab === 'inicio'" class="space-y-5">

                    <!-- Cards rápidas -->
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">

                        <!-- Gráfica evolución dolor -->
                        <div class="sm:col-span-2 fv-card-premium p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <TrendingUp class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                                <p class="text-sm font-semibold text-foreground">Tu progreso 📈</p>
                            </div>
                            <div v-if="hasPainHistory">
                                <VueApexCharts type="area" height="180" :series="painChartSeries" :options="painChartOptions" />
                            </div>
                            <div v-else class="flex flex-col items-center justify-center h-28 text-center gap-2">
                                <Activity class="h-8 w-8 text-muted-foreground opacity-40" />
                                <p class="text-sm text-muted-foreground">Sin datos de sesiones aún</p>
                                <p class="text-xs text-muted-foreground">Tu fisioterapeuta registrará tu progreso en cada sesión</p>
                            </div>
                        </div>

                        <!-- Indicaciones pendientes -->
                        <div class="fv-card-premium p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <AlertCircle class="h-4 w-4 text-amber-500" />
                                <p class="text-sm font-semibold text-foreground">Indicaciones</p>
                            </div>
                            <div v-if="hasActivities" class="space-y-2">
                                <div v-for="act in activities?.slice(0,3)" :key="act.id" class="flex items-start gap-2 rounded-xl bg-amber-50 dark:bg-amber-900/20 p-2.5">
                                    <AlertCircle class="h-3.5 w-3.5 mt-0.5 shrink-0 text-amber-500" />
                                    <div>
                                        <p class="text-xs font-medium text-foreground leading-snug">{{ act.title }}</p>
                                        <p v-if="act.due_date" class="text-[10px] text-muted-foreground">{{ formatDateMx(act.due_date) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center h-24 text-center gap-1">
                                <CheckCircle2 class="h-7 w-7 text-emerald-500 opacity-60" />
                                <p class="text-xs text-muted-foreground">Sin indicaciones pendientes</p>
                            </div>
                        </div>
                    </div>

                    <!-- Plan actual + últimas sesiones -->
                    <div class="grid gap-3 sm:grid-cols-2">

                        <!-- Tu plan actual -->
                        <div class="fv-card-premium p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <ClipboardList class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                                <p class="text-sm font-semibold text-foreground">Recomendaciones de tu fisioterapeuta</p>
                            </div>
                            <div v-if="latestPlan" class="rounded-xl bg-muted/40 p-3">
                                <p class="text-sm text-foreground leading-relaxed">{{ latestPlan }}</p>
                            </div>
                            <div v-else-if="hasSessions">
                                <p class="text-sm text-muted-foreground">Tu fisioterapeuta aún no ha registrado un plan de tratamiento.</p>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-4 text-center gap-1">
                                <ClipboardList class="h-7 w-7 text-muted-foreground opacity-40" />
                                <p class="text-sm text-muted-foreground">Sin sesiones registradas</p>
                                <p class="text-xs text-muted-foreground">Tu fisioterapeuta te asignará un plan después de tu primera sesión</p>
                            </div>
                        </div>

                        <!-- Historial rápido sesiones -->
                        <div class="fv-card-premium p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <Activity class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                                <p class="text-sm font-semibold text-foreground">Últimas sesiones</p>
                            </div>
                            <div v-if="hasSessions" class="space-y-2">
                                <div v-for="s in sessions?.slice(0,4)" :key="s.id" class="flex items-center justify-between rounded-xl bg-muted/40 px-3 py-2">
                                    <div>
                                        <p class="text-xs font-medium text-foreground">{{ formatDateMx(s.session_date) }}</p>
                                        <p v-if="s.therapist_name" class="text-[10px] text-muted-foreground">{{ s.therapist_name }}</p>
                                    </div>
                                    <span v-if="s.pain_scale != null" class="text-sm font-bold" :class="painColor(s.pain_scale)">{{ s.pain_scale }}/10</span>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-4 text-center gap-1">
                                <Activity class="h-7 w-7 text-muted-foreground opacity-40" />
                                <p class="text-sm text-muted-foreground">Sin sesiones aún</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contacto clínica -->
                    <div v-if="clinicSettings?.clinic_phone || clinicSettings?.clinic_address" class="fv-card-premium p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <Building2 class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                            <p class="text-sm font-semibold text-foreground">{{ clinicSettings?.clinic_name ?? 'Clínica' }}</p>
                        </div>
                        <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                            <div v-if="clinicSettings?.clinic_phone" class="flex items-center gap-1.5">
                                <Phone class="h-3.5 w-3.5 shrink-0" /> {{ clinicSettings.clinic_phone }}
                            </div>
                            <div v-if="clinicSettings?.clinic_email" class="flex items-center gap-1.5">
                                <Mail class="h-3.5 w-3.5 shrink-0" /> {{ clinicSettings.clinic_email }}
                            </div>
                            <div v-if="clinicSettings?.clinic_address" class="flex items-center gap-1.5">
                                <Building2 class="h-3.5 w-3.5 shrink-0" /> {{ clinicSettings.clinic_address }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════ TAB: CITAS ═══════ -->
                <div v-if="activeTab === 'citas'" class="space-y-4">

                    <!-- Próximas -->
                    <div class="fv-card-premium p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <CalendarClock class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                                <p class="text-sm font-semibold text-foreground">Próximas citas</p>
                            </div>
                            <Button size="sm" class="h-8 rounded-xl text-xs" :style="primaryStyle" @click="activeTab = 'solicitudes'; showRequestForm = true">
                                <Plus class="mr-1 h-3.5 w-3.5" /> Solicitar cita
                            </Button>
                        </div>
                        <div v-if="upcomingAppointments?.length" class="space-y-2">
                            <article v-for="appt in upcomingAppointments" :key="appt.id" class="flex items-center gap-3 rounded-2xl border border-border bg-muted/30 p-3">
                                <div class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-xl shadow-sm text-center" :style="primaryStyle">
                                    <span class="text-[10px] font-semibold leading-none">{{ new Date(appt.start_at).toLocaleDateString('es-MX', { month: 'short' }).toUpperCase() }}</span>
                                    <span class="text-lg font-bold leading-none">{{ new Date(appt.start_at).getDate() }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-foreground">{{ formatDateTimeMx(appt.start_at) }}</p>
                                    <p v-if="appt.therapist_name" class="text-xs text-muted-foreground">{{ appt.therapist_name }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    {{ tAppointmentStatus(appt.status) }}
                                </span>
                            </article>
                        </div>
                        <div v-else class="fv-empty-state !py-6">
                            <CalendarClock class="h-8 w-8 text-muted-foreground opacity-40" />
                            <p class="mt-2 text-sm font-medium text-foreground">Sin citas próximas</p>
                            <p class="text-xs text-muted-foreground">Solicita una cita para comenzar tu tratamiento</p>
                        </div>
                    </div>

                    <!-- Historial -->
                    <div class="fv-card-premium p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <Clock class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                            <p class="text-sm font-semibold text-foreground">Historial de citas</p>
                        </div>
                        <div v-if="recentAppointments?.length" class="space-y-2">
                            <div v-for="appt in recentAppointments" :key="appt.id" class="flex items-center gap-3 rounded-xl bg-muted/30 px-3 py-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-foreground">{{ formatDateTimeMx(appt.start_at) }}</p>
                                    <p v-if="appt.therapist_name" class="text-[10px] text-muted-foreground">{{ appt.therapist_name }}</p>
                                </div>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300': appt.status === 'scheduled',
                                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300': appt.status === 'confirmed',
                                        'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300': appt.status === 'done',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300': appt.status === 'no_show',
                                        'bg-muted text-muted-foreground': appt.status === 'cancelled',
                                    }"
                                >{{ tAppointmentStatus(appt.status) }}</span>
                            </div>
                        </div>
                        <div v-else class="fv-empty-state !py-4">
                            <p class="text-sm text-muted-foreground">No hay historial de citas</p>
                        </div>
                    </div>
                </div>

                <!-- ═══════ TAB: EJERCICIOS ═══════ -->
                <div v-if="activeTab === 'ejercicios'" class="space-y-4">
                    <div v-if="hasExercises" class="rounded-2xl border border-amber-200 bg-amber-50 dark:border-amber-800/40 dark:bg-amber-900/20 px-4 py-2.5 flex items-center gap-2">
                        <span class="text-base">⚠️</span>
                        <p class="text-xs text-amber-800 dark:text-amber-300">Realiza estos ejercicios <strong>solo si tu fisioterapeuta te lo indicó</strong>. Ante dudas, consulta antes de practicarlos.</p>
                    </div>
                    <div v-if="hasExercises" class="grid gap-3 sm:grid-cols-2">
                        <article
                            v-for="ex in exercises"
                            :key="ex.exercise_id"
                            class="fv-card-premium overflow-hidden"
                        >
                            <!-- Thumbnail YouTube o placeholder -->
                            <div class="relative aspect-video bg-muted overflow-hidden rounded-t-2xl">
                                <img
                                    v-if="isYoutubeUrl(ex.video_url)"
                                    :src="youtubeThumbnail(ex.video_url) ?? undefined"
                                    :alt="ex.name"
                                    class="h-full w-full object-cover"
                                    @error="(e) => (e.target as HTMLImageElement).style.display = 'none'"
                                />
                                <div v-else class="flex h-full items-center justify-center">
                                    <Dumbbell class="h-10 w-10 text-muted-foreground opacity-30" />
                                </div>
                                <!-- Play overlay si hay video -->
                                <button
                                    v-if="isYoutubeUrl(ex.video_url)"
                                    class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 hover:opacity-100 transition-opacity"
                                    @click="openVideo(ex.video_url!)"
                                >
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/90 shadow-lg">
                                        <Play class="h-5 w-5 text-gray-900 ml-0.5" />
                                    </div>
                                </button>
                            </div>

                            <div class="p-4 space-y-2">
                                <h3 class="text-sm font-semibold text-foreground">{{ ex.name }}</h3>

                                <!-- Series / reps / segundos -->
                                <div class="flex flex-wrap gap-2">
                                    <span v-if="ex.sets" class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs text-foreground font-medium">
                                        {{ ex.sets }} series
                                    </span>
                                    <span v-if="ex.reps" class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs text-foreground font-medium">
                                        {{ ex.reps }} repeticiones
                                    </span>
                                    <span v-if="ex.seconds" class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs text-foreground font-medium">
                                        {{ ex.seconds }}s
                                    </span>
                                </div>

                                <p v-if="ex.description" class="text-xs text-muted-foreground leading-relaxed">{{ ex.description }}</p>
                                <p v-if="ex.exercise_notes" class="text-xs text-foreground bg-amber-50 dark:bg-amber-900/20 rounded-lg px-2.5 py-1.5 leading-relaxed">
                                    💬 {{ ex.exercise_notes }}
                                </p>

                                <Button
                                    v-if="isYoutubeUrl(ex.video_url)"
                                    size="sm"
                                    class="w-full rounded-xl text-xs h-8 mt-1"
                                    :style="primaryStyle"
                                    @click="openVideo(ex.video_url!)"
                                >
                                    <Play class="mr-1.5 h-3.5 w-3.5" /> Ver video
                                </Button>
                                <a
                                    v-else-if="ex.video_url"
                                    :href="ex.video_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-border bg-card text-xs h-8 mt-1 font-medium text-foreground hover:border-primary transition-colors"
                                >
                                    <ExternalLink class="h-3.5 w-3.5" /> Ver recurso
                                </a>
                            </div>
                        </article>
                    </div>

                    <div v-else class="fv-empty-state">
                        <Dumbbell class="h-10 w-10 text-muted-foreground opacity-40" />
                        <h3 class="mt-3 text-base font-semibold text-foreground">Sin ejercicios asignados</h3>
                        <p class="mt-1 text-sm text-muted-foreground max-w-xs text-center">Tu fisioterapeuta te asignará actividades y ejercicios después de tu sesión clínica.</p>
                    </div>
                </div>

                <!-- ═══════ TAB: ARCHIVOS ═══════ -->
                <div v-if="activeTab === 'archivos'" class="space-y-4">
                    <div v-if="hasFiles" class="grid gap-3 sm:grid-cols-2">
                        <article
                            v-for="file in files"
                            :key="file.id"
                            class="fv-card-premium p-4 flex items-start gap-3"
                        >
                            <!-- Preview imagen o icono -->
                            <div class="h-14 w-14 shrink-0 rounded-xl overflow-hidden border border-border bg-muted flex items-center justify-center">
                                <img
                                    v-if="file.is_image"
                                    :src="file.preview_url"
                                    :alt="file.original_name"
                                    class="h-full w-full object-cover"
                                    @error="(e) => { (e.target as HTMLImageElement).style.display = 'none'; }"
                                />
                                <span v-else class="text-2xl leading-none">{{ fileIcon(file.mime) }}</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-foreground truncate">{{ file.original_name }}</p>
                                <p class="text-xs text-muted-foreground mt-0.5">{{ formatDateMx(file.created_at) }}</p>
                                <p v-if="file.file_type" class="text-[10px] text-muted-foreground capitalize mt-0.5">{{ file.file_type.replace(/_/g, ' ') }}</p>
                                <div class="flex gap-2 mt-2">
                                    <a :href="file.preview_url" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-medium rounded-lg border border-border px-2 py-1 hover:border-primary text-muted-foreground hover:text-foreground transition-colors">
                                        <ExternalLink class="h-3 w-3" /> Ver
                                    </a>
                                    <a :href="file.download_url" class="inline-flex items-center gap-1 text-[11px] font-medium rounded-lg border border-border px-2 py-1 hover:border-primary text-muted-foreground hover:text-foreground transition-colors">
                                        <Download class="h-3 w-3" /> Descargar
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="fv-empty-state">
                        <FileText class="h-10 w-10 text-muted-foreground opacity-40" />
                        <h3 class="mt-3 text-base font-semibold text-foreground">Sin archivos disponibles</h3>
                        <p class="mt-1 text-sm text-muted-foreground max-w-xs text-center">Aquí aparecerán recetas, indicaciones e imágenes que tu fisioterapeuta comparta contigo.</p>
                    </div>
                </div>

                <!-- ═══════ TAB: SOLICITUDES ═══════ -->
                <div v-if="activeTab === 'solicitudes'" class="space-y-4">

                    <!-- Formulario de solicitud -->
                    <div class="fv-card-premium p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <CalendarPlus class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                                <p class="text-sm font-semibold text-foreground">Solicitar una cita</p>
                            </div>
                            <Button
                                v-if="!showRequestForm"
                                size="sm"
                                class="h-8 rounded-xl text-xs"
                                :style="primaryStyle"
                                @click="showRequestForm = true"
                            >
                                <Plus class="mr-1 h-3.5 w-3.5" /> Nueva solicitud
                            </Button>
                        </div>

                        <div v-if="showRequestForm" class="space-y-3">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <label class="text-xs font-medium" style="color:var(--muted-foreground)">Fecha preferida *</label>
                                    <DatePicker
                                        v-model="requestForm.preferred_date"
                                        :disable-future="false"
                                        :disable-past="true"
                                        :min-date="todayIso"
                                        placeholder="Seleccionar fecha..."
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium" style="color:var(--muted-foreground)">Hora preferida</label>
                                    <div class="relative">
                                        <input
                                            v-model="requestForm.preferred_time"
                                            type="time"
                                            class="w-full h-11 rounded-2xl border px-3 text-sm focus:outline-none focus:ring-2 shadow-sm transition-all duration-200"
                                            style="background:var(--card);border-color:var(--input);color:var(--foreground);--tw-ring-color:var(--primary)"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-muted-foreground">Motivo de la cita</label>
                                <input v-model="requestForm.reason" type="text" placeholder="Dolor de espalda, seguimiento, evaluación..." class="w-full rounded-xl border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-muted-foreground">Notas adicionales</label>
                                <textarea v-model="requestForm.notes" rows="2" placeholder="Información relevante para tu terapeuta..." class="w-full rounded-xl border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary resize-none" />
                            </div>
                            <div class="flex gap-2">
                                <Button size="sm" class="rounded-xl text-xs h-9" :style="primaryStyle" :disabled="requestSaving || !requestForm.preferred_date" @click="sendRequest">
                                    <Loader2 v-if="requestSaving" class="mr-1.5 h-3.5 w-3.5 animate-spin" />
                                    {{ requestSaving ? 'Enviando...' : 'Enviar solicitud' }}
                                </Button>
                                <Button size="sm" variant="outline" class="rounded-xl text-xs h-9 border-border" @click="showRequestForm = false">Cancelar</Button>
                            </div>
                        </div>

                        <p v-else class="text-xs text-muted-foreground">Envía una solicitud y nuestro equipo la revisará para confirmar tu cita.</p>
                    </div>

                    <!-- Lista de solicitudes -->
                    <div class="fv-card-premium p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <ClipboardList class="h-4 w-4" :style="{ color: 'var(--primary)' }" />
                            <p class="text-sm font-semibold text-foreground">Mis solicitudes</p>
                        </div>

                        <div v-if="hasRequests" class="space-y-2">
                            <article v-for="req in myRequests" :key="req.id" class="rounded-2xl border border-border bg-muted/30 p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-foreground">{{ formatDateMx(req.preferred_date) }}<span v-if="req.preferred_time"> — {{ req.preferred_time }}</span></p>
                                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full" :class="requestStatusBadge(req.status)">{{ requestStatusLabel(req.status) }}</span>
                                        </div>
                                        <p v-if="req.reason" class="text-xs text-muted-foreground mt-0.5">{{ req.reason }}</p>
                                        <p v-if="req.rejection_reason" class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ req.rejection_reason }}</p>
                                    </div>
                                    <Button
                                        v-if="req.status === 'pending'"
                                        size="sm"
                                        variant="ghost"
                                        class="h-6 w-6 p-0 rounded-lg shrink-0"
                                        @click="cancelRequest(req.id)"
                                    >
                                        <X class="h-3.5 w-3.5 text-muted-foreground" />
                                    </Button>
                                </div>
                            </article>
                        </div>

                        <div v-else class="fv-empty-state !py-4">
                            <CalendarPlus class="h-7 w-7 text-muted-foreground opacity-40" />
                            <p class="mt-1 text-sm text-muted-foreground">Sin solicitudes enviadas</p>
                        </div>
                    </div>
                </div>

                <!-- ═══════ TAB: PRIVACIDAD ═══════ -->
                <div v-if="activeTab === 'privacidad'" class="space-y-4">

                    <!-- Aviso general -->
                    <div class="fv-card-premium p-4">
                        <div class="flex items-start gap-3">
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl" :style="primaryStyle">
                                <Star class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="font-semibold text-foreground">Tus datos y tu privacidad</p>
                                <p class="mt-1 text-sm text-muted-foreground leading-relaxed">
                                    Tus datos se usan exclusivamente para tu atención médica: citas, seguimiento de tratamiento, expediente clínico y comunicación contigo dentro de esta clínica. No se venden ni se comparten con terceros.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Documentos pendientes de aceptar -->
                    <div v-if="pendingDocs.length > 0 && portalAllowed" class="rounded-[2rem] border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                        <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-amber-700 dark:text-amber-400">
                            <TrendingUp class="h-4 w-4" />
                            Documentos pendientes de aceptar ({{ pendingDocs.length }})
                        </p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <button
                                v-for="doc in pendingDocs"
                                :key="doc.key"
                                class="flex items-center justify-between rounded-2xl border border-amber-300 bg-white px-4 py-3 text-left shadow-sm transition hover:-translate-y-0.5 dark:border-amber-900/40 dark:bg-amber-950/10"
                                @click="startAccept(doc.key)"
                            >
                                <div>
                                    <p class="text-sm font-semibold text-foreground">{{ doc.label }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5">Toca para leer y aceptar</p>
                                </div>
                                <ChevronDown class="h-4 w-4 shrink-0 text-amber-500" />
                            </button>
                        </div>
                    </div>

                    <!-- No habilitado desde portal -->
                    <div v-if="!portalAllowed && pendingDocs.length > 0" class="rounded-[2rem] border border-border bg-muted/30 p-4 text-center text-sm text-muted-foreground">
                        La aceptación de documentos desde el portal no está disponible en este momento. Consulta con el personal de la clínica.
                    </div>

                    <!-- Modal de aceptación con firma digital -->
                    <div v-if="acceptingDoc" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 p-2 sm:p-4">
                        <div class="w-full max-w-xl rounded-[2rem] border border-border bg-card shadow-2xl overflow-hidden flex flex-col max-h-[95vh]">

                            <!-- Header -->
                            <div class="p-5 border-b border-border shrink-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-semibold text-foreground">{{ PORTAL_DOCS.find(d => d.key === acceptingDoc)?.label }}</p>
                                        <p class="text-xs text-muted-foreground mt-0.5">Lee el documento completo y firma digitalmente</p>
                                    </div>
                                    <button class="text-muted-foreground hover:text-foreground p-1" @click="acceptingDoc = null; portalSignatureData = ''">
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Texto del documento -->
                            <div class="overflow-y-auto px-5 py-4 flex-1">
                                <pre class="whitespace-pre-wrap text-xs leading-relaxed text-foreground font-sans rounded-xl border border-border bg-muted/40 p-4">{{ props.complianceSettings?.[PORTAL_DOCS.find(d => d.key === acceptingDoc)?.textKey ?? ''] ?? 'Cargando documento...' }}</pre>
                            </div>

                            <!-- Formulario de aceptación y firma -->
                            <div class="p-5 border-t border-border space-y-4 shrink-0">

                                <!-- Checkbox de lectura -->
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" v-model="acceptForm.confirmed" class="mt-0.5 h-4 w-4 rounded border-border" />
                                    <span class="text-sm text-foreground">
                                        He leído y entendido el documento. Acepto voluntariamente.
                                    </span>
                                </label>

                                <!-- Nombre del firmante -->
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Tu nombre completo *</label>
                                    <input
                                        v-model="acceptForm.signer_name"
                                        type="text"
                                        placeholder="Como aparece en tu identificación"
                                        class="w-full rounded-xl border border-border bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                                    />
                                </div>

                                <!-- Datos de tutor si es minor_consent -->
                                <template v-if="acceptingDoc === 'minor_consent'">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Nombre del tutor / representante legal *</label>
                                            <input v-model="acceptForm.guardian_name" type="text" placeholder="Nombre del tutor" class="w-full rounded-xl border border-border bg-background px-3 py-2.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-primary" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Parentesco</label>
                                            <input v-model="acceptForm.guardian_relationship" type="text" placeholder="Madre, Padre, Tutor legal..." class="w-full rounded-xl border border-border bg-background px-3 py-2.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-primary" />
                                        </div>
                                    </div>
                                </template>

                                <!-- Firma dibujada -->
                                <div>
                                    <label class="mb-2 block text-xs font-medium text-muted-foreground">
                                        Firma digital * <span class="font-normal">(dibuja tu firma con el mouse o dedo)</span>
                                    </label>
                                    <SignaturePad
                                        ref="portalSignaturePad"
                                        v-model="portalSignatureData"
                                        :height="140"
                                    />
                                </div>

                                <!-- Error -->
                                <p v-if="acceptError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-400">
                                    {{ acceptError }}
                                </p>

                                <!-- Aviso legal -->
                                <p class="text-[11px] text-muted-foreground leading-snug">
                                    Esta firma digital simple registra evidencia de aceptación. La clínica debe validar su contenido con asesor jurídico. No es firma electrónica avanzada.
                                </p>

                                <!-- Acciones -->
                                <div class="flex gap-2 justify-end">
                                    <button
                                        class="h-9 rounded-xl border border-border bg-card px-4 text-sm font-medium text-muted-foreground hover:bg-muted"
                                        @click="acceptingDoc = null; portalSignatureData = ''; acceptError = ''"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        class="h-9 rounded-xl px-5 text-sm font-semibold text-white disabled:opacity-50 transition-opacity"
                                        :style="primaryStyle"
                                        :disabled="!canSubmitAccept || acceptSaving"
                                        @click="submitAccept"
                                    >
                                        <Loader2 v-if="acceptSaving" class="mr-1.5 inline h-3.5 w-3.5 animate-spin" />
                                        {{ acceptSaving ? 'Firmando...' : 'Firmar y aceptar' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documentos aceptados -->
                    <div class="fv-card-premium p-4">
                        <p class="mb-3 font-semibold text-foreground flex items-center gap-2">
                            <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                            Mis documentos aceptados
                        </p>

                        <div v-if="(props.legalAcceptances ?? []).filter(a => a.status === 'accepted').length === 0" class="text-center py-6 text-sm text-muted-foreground">
                            No has aceptado ningún documento aún.
                        </div>

                        <ul v-else class="space-y-2">
                            <li
                                v-for="doc in (props.legalAcceptances ?? []).filter(a => a.status === 'accepted')"
                                :key="doc.id"
                                class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 dark:border-emerald-900/40 dark:bg-emerald-950/20"
                            >
                                <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-foreground">{{ doc.title ?? doc.document_type }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        v{{ doc.version }} · {{ doc.accepted_at ? new Date(doc.accepted_at).toLocaleDateString('es-MX') : '—' }}
                                        <span v-if="doc.signature_method === 'drawn_signature'" class="ml-1 text-emerald-600 dark:text-emerald-400">· Firma dibujada</span>
                                        <span v-if="doc.source === 'portal'" class="ml-1 text-indigo-600 dark:text-indigo-400">· Portal</span>
                                    </p>
                                </div>
                                <a
                                    v-if="doc.signed_pdf_path"
                                    :href="`/mi-portal/documentos/${doc.id}/descargar`"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 rounded-lg border border-emerald-300 bg-white px-2.5 py-1.5 text-[11px] font-medium text-emerald-700 hover:bg-emerald-50 shrink-0"
                                    title="Descargar PDF firmado"
                                >
                                    <Download class="h-3 w-3" />
                                    PDF
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Privacidad info -->
                    <div class="fv-card-premium p-4 text-xs text-muted-foreground leading-relaxed">
                        <p class="font-semibold text-foreground mb-1">¿Por qué se recaban mis datos?</p>
                        <p>Tus datos de salud se recaban para prestarte atención médica personalizada, dar seguimiento a tu tratamiento y mantener tu expediente clínico organizado. Puedes ejercer tus derechos ARCO (Acceso, Rectificación, Cancelación, Oposición) contactando al responsable de privacidad de la clínica.</p>
                        <p v-if="props.clinicSettings?.privacy_contact_email" class="mt-2">
                            Contacto: <span class="font-medium text-foreground">{{ props.clinicSettings.privacy_contact_email }}</span>
                        </p>
                    </div>
                </div>

            </template>
        </section>
    </AppLayout>
</template>
