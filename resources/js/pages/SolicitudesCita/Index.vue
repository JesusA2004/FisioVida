<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    CalendarClock, CheckCircle2, XCircle, Clock, AlertCircle,
    ChevronDown, ChevronUp, Search, Filter, MessageSquare,
    User, Calendar, RefreshCw,
} from 'lucide-vue-next';

interface AppointmentRequest {
    id: number;
    preferred_date: string | null;
    preferred_time: string | null;
    reason: string | null;
    notes: string | null;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    rejection_reason: string | null;
    reviewed_at: string | null;
    created_at: string;
    patient_name: string;
    therapist_name: string | null;
    reviewed_by_name: string | null;
}

interface Page {
    data: AppointmentRequest[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    rows: Page;
    filters: { status: string; search: string };
}>();

const page = usePage();

// ── filters ──────────────────────────────────────────────────────────────────
const statusFilter = ref(props.filters.status ?? '');
const searchFilter = ref(props.filters.search ?? '');

function applyFilters() {
    router.get('/solicitudes-cita', {
        status: statusFilter.value || undefined,
        search: searchFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

// ── approve modal ─────────────────────────────────────────────────────────────
const approveModal = ref(false);
const approveTarget = ref<AppointmentRequest | null>(null);
const approveDate = ref('');
const approveTime = ref('');
const approveNotes = ref('');
const approveLoading = ref(false);
const approveError = ref('');

function openApprove(req: AppointmentRequest) {
    approveTarget.value = req;
    approveDate.value = req.preferred_date ?? '';
    approveTime.value = req.preferred_time ?? '';
    approveNotes.value = '';
    approveError.value = '';
    approveModal.value = true;
}

async function submitApprove() {
    if (!approveDate.value || !approveTime.value) {
        approveError.value = 'Fecha y hora son requeridas.';
        return;
    }
    approveLoading.value = true;
    approveError.value = '';
    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const res = await fetch(`/solicitudes-cita/${approveTarget.value!.id}/aprobar`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ start_at: `${approveDate.value}T${approveTime.value}`, notes: approveNotes.value }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message ?? 'Error al aprobar');
        approveModal.value = false;
        router.reload({ only: ['rows'] });
    } catch (e: any) {
        approveError.value = e.message ?? 'Error inesperado';
    } finally {
        approveLoading.value = false;
    }
}

// ── reject modal ──────────────────────────────────────────────────────────────
const rejectModal = ref(false);
const rejectTarget = ref<AppointmentRequest | null>(null);
const rejectReason = ref('');
const rejectLoading = ref(false);
const rejectError = ref('');

function openReject(req: AppointmentRequest) {
    rejectTarget.value = req;
    rejectReason.value = '';
    rejectError.value = '';
    rejectModal.value = true;
}

async function submitReject() {
    rejectLoading.value = true;
    rejectError.value = '';
    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const res = await fetch(`/solicitudes-cita/${rejectTarget.value!.id}/rechazar`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ rejection_reason: rejectReason.value }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message ?? 'Error al rechazar');
        rejectModal.value = false;
        router.reload({ only: ['rows'] });
    } catch (e: any) {
        rejectError.value = e.message ?? 'Error inesperado';
    } finally {
        rejectLoading.value = false;
    }
}

// ── helpers ───────────────────────────────────────────────────────────────────
const statusLabel: Record<string, string> = {
    pending: 'Pendiente',
    approved: 'Aprobada',
    rejected: 'Rechazada',
    cancelled: 'Cancelada',
};

const statusClass: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-800/60 dark:text-gray-400',
};

function fmtDate(d: string | null) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
}

function fmtDateTime(d: string | null) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-MX', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

const pendingCount = computed(() => props.rows.data.filter(r => r.status === 'pending').length);
</script>

<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold" style="color:var(--foreground)">Solicitudes de Cita</h1>
                    <p class="text-sm mt-0.5" style="color:var(--muted-foreground)">
                        Gestión de solicitudes enviadas por pacientes
                    </p>
                </div>
                <div v-if="pendingCount > 0" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                    <AlertCircle class="w-4 h-4" />
                    {{ pendingCount }} pendiente{{ pendingCount !== 1 ? 's' : '' }}
                </div>
            </div>

            <!-- Filters -->
            <div class="fv-card-premium p-4 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--muted-foreground)" />
                    <input
                        v-model="searchFilter"
                        type="text"
                        placeholder="Buscar por paciente o motivo…"
                        class="w-full pl-9 pr-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2"
                        style="background:var(--input);border-color:var(--border);color:var(--foreground);--tw-ring-color:var(--primary)"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2"
                    style="background:var(--input);border-color:var(--border);color:var(--foreground);--tw-ring-color:var(--primary)"
                    @change="applyFilters"
                >
                    <option value="">Todos los estados</option>
                    <option value="pending">Pendientes</option>
                    <option value="approved">Aprobadas</option>
                    <option value="rejected">Rechazadas</option>
                    <option value="cancelled">Canceladas</option>
                </select>
                <button
                    class="px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2"
                    style="background:var(--primary);color:var(--primary-foreground)"
                    @click="applyFilters"
                >
                    <Filter class="w-4 h-4" />
                    Filtrar
                </button>
            </div>

            <!-- Table -->
            <div class="fv-card-premium overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border)">
                                <th class="text-left px-4 py-3 font-medium" style="color:var(--muted-foreground)">Paciente</th>
                                <th class="text-left px-4 py-3 font-medium" style="color:var(--muted-foreground)">Fecha preferida</th>
                                <th class="text-left px-4 py-3 font-medium" style="color:var(--muted-foreground)">Motivo</th>
                                <th class="text-left px-4 py-3 font-medium" style="color:var(--muted-foreground)">Estado</th>
                                <th class="text-left px-4 py-3 font-medium" style="color:var(--muted-foreground)">Recibida</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="rows.data.length === 0">
                                <td colspan="6" class="text-center py-16" style="color:var(--muted-foreground)">
                                    <CalendarClock class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                    <p class="font-medium">Sin solicitudes</p>
                                    <p class="text-xs mt-1">No hay solicitudes que coincidan con los filtros.</p>
                                </td>
                            </tr>
                            <tr
                                v-for="req in rows.data"
                                :key="req.id"
                                class="transition-colors"
                                style="border-bottom:1px solid var(--border)"
                                :style="{ background: req.status === 'pending' ? 'var(--accent)' : '' }"
                            >
                                <!-- Patient -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                            style="background:var(--primary);color:var(--primary-foreground)">
                                            {{ req.patient_name?.charAt(0) ?? '?' }}
                                        </div>
                                        <span class="font-medium" style="color:var(--foreground)">{{ req.patient_name }}</span>
                                    </div>
                                </td>
                                <!-- Preferred date -->
                                <td class="px-4 py-3" style="color:var(--foreground)">
                                    <div class="flex items-center gap-1.5">
                                        <Calendar class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--muted-foreground)" />
                                        <span>{{ fmtDate(req.preferred_date) }}</span>
                                    </div>
                                    <div v-if="req.preferred_time" class="text-xs mt-0.5 flex items-center gap-1" style="color:var(--muted-foreground)">
                                        <Clock class="w-3 h-3" />
                                        {{ req.preferred_time }}
                                    </div>
                                </td>
                                <!-- Reason -->
                                <td class="px-4 py-3 max-w-[220px]">
                                    <p class="truncate text-sm" style="color:var(--foreground)" :title="req.reason ?? ''">
                                        {{ req.reason ?? '—' }}
                                    </p>
                                    <p v-if="req.rejection_reason" class="text-xs mt-0.5 truncate" style="color:var(--muted-foreground)" :title="req.rejection_reason">
                                        Motivo rechazo: {{ req.rejection_reason }}
                                    </p>
                                </td>
                                <!-- Status -->
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass[req.status]">
                                        <CheckCircle2 v-if="req.status === 'approved'" class="w-3 h-3" />
                                        <XCircle v-else-if="req.status === 'rejected' || req.status === 'cancelled'" class="w-3 h-3" />
                                        <Clock v-else class="w-3 h-3" />
                                        {{ statusLabel[req.status] ?? req.status }}
                                    </span>
                                    <div v-if="req.reviewed_by_name" class="text-xs mt-0.5" style="color:var(--muted-foreground)">
                                        por {{ req.reviewed_by_name }}
                                    </div>
                                </td>
                                <!-- Created at -->
                                <td class="px-4 py-3 text-xs whitespace-nowrap" style="color:var(--muted-foreground)">
                                    {{ fmtDateTime(req.created_at) }}
                                </td>
                                <!-- Actions -->
                                <td class="px-4 py-3">
                                    <div v-if="req.status === 'pending'" class="flex items-center gap-2">
                                        <button
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                            style="background:var(--primary);color:var(--primary-foreground)"
                                            @click="openApprove(req)"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5" />
                                            Aprobar
                                        </button>
                                        <button
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                            style="background:var(--destructive);color:var(--destructive-foreground)"
                                            @click="openReject(req)"
                                        >
                                            <XCircle class="w-3.5 h-3.5" />
                                            Rechazar
                                        </button>
                                    </div>
                                    <span v-else class="text-xs" style="color:var(--muted-foreground)">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="rows.last_page > 1" class="flex items-center justify-between px-4 py-3" style="border-top:1px solid var(--border)">
                    <p class="text-xs" style="color:var(--muted-foreground)">
                        {{ rows.total }} solicitudes en total
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in rows.links" :key="link.label">
                            <button
                                v-if="link.url"
                                class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
                                :style="link.active
                                    ? 'background:var(--primary);color:var(--primary-foreground)'
                                    : 'background:var(--muted);color:var(--muted-foreground)'"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Approve Modal ──────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="approveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60" @click="approveModal = false" />
                <div class="relative w-full max-w-md rounded-2xl shadow-2xl p-6 space-y-5" style="background:var(--card);border:1px solid var(--border)">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background:var(--primary)">
                            <CheckCircle2 class="w-5 h-5" style="color:var(--primary-foreground)" />
                        </div>
                        <div>
                            <h3 class="font-bold text-lg" style="color:var(--foreground)">Aprobar solicitud</h3>
                            <p class="text-sm" style="color:var(--muted-foreground)">{{ approveTarget?.patient_name }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium mb-1" style="color:var(--foreground)">
                                Fecha de la cita <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="approveDate"
                                type="date"
                                class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2"
                                style="background:var(--input);border-color:var(--border);color:var(--foreground)"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" style="color:var(--foreground)">
                                Hora <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="approveTime"
                                type="time"
                                class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2"
                                style="background:var(--input);border-color:var(--border);color:var(--foreground)"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" style="color:var(--foreground)">Notas internas</label>
                            <textarea
                                v-model="approveNotes"
                                rows="2"
                                class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 resize-none"
                                style="background:var(--input);border-color:var(--border);color:var(--foreground)"
                                placeholder="Opcional…"
                            />
                        </div>
                        <p v-if="approveError" class="text-sm text-red-600 dark:text-red-400">{{ approveError }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            class="px-4 py-2 rounded-lg text-sm font-medium"
                            style="background:var(--muted);color:var(--muted-foreground)"
                            @click="approveModal = false"
                        >Cancelar</button>
                        <button
                            class="px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 disabled:opacity-50"
                            style="background:var(--primary);color:var(--primary-foreground)"
                            :disabled="approveLoading"
                            @click="submitApprove"
                        >
                            <RefreshCw v-if="approveLoading" class="w-4 h-4 animate-spin" />
                            <CheckCircle2 v-else class="w-4 h-4" />
                            Confirmar cita
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Reject Modal ───────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60" @click="rejectModal = false" />
                <div class="relative w-full max-w-md rounded-2xl shadow-2xl p-6 space-y-5" style="background:var(--card);border:1px solid var(--border)">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center bg-red-500">
                            <XCircle class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="font-bold text-lg" style="color:var(--foreground)">Rechazar solicitud</h3>
                            <p class="text-sm" style="color:var(--muted-foreground)">{{ rejectTarget?.patient_name }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium mb-1" style="color:var(--foreground)">
                                Motivo del rechazo
                            </label>
                            <textarea
                                v-model="rejectReason"
                                rows="3"
                                class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 resize-none"
                                style="background:var(--input);border-color:var(--border);color:var(--foreground)"
                                placeholder="Ej: No hay disponibilidad en esa fecha…"
                            />
                            <p class="text-xs mt-1" style="color:var(--muted-foreground)">Opcional — se le mostrará al paciente.</p>
                        </div>
                        <p v-if="rejectError" class="text-sm text-red-600 dark:text-red-400">{{ rejectError }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            class="px-4 py-2 rounded-lg text-sm font-medium"
                            style="background:var(--muted);color:var(--muted-foreground)"
                            @click="rejectModal = false"
                        >Cancelar</button>
                        <button
                            class="px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 disabled:opacity-50 bg-red-600 text-white"
                            :disabled="rejectLoading"
                            @click="submitReject"
                        >
                            <RefreshCw v-if="rejectLoading" class="w-4 h-4 animate-spin" />
                            <XCircle v-else class="w-4 h-4" />
                            Rechazar solicitud
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
