<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import {
    tActivityStatus,
    tAppointmentStatus,
    tGeneralStatus,
    tPaymentStatus,
    tPriority,
} from '@/lib/labels';
import { formatDateMx, formatDateTimeMx } from '@/lib/dates';

const props = defineProps<{
    patient: any;
    appointments: any[];
    sessions: any[];
    files: any[];
    payments: any[];
    activities: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pacientes', href: '/pacientes' },
    { title: 'Expediente', href: `/pacientes/${props.patient.id}` },
];
</script>

<template>
    <Head :title="`Expediente - ${props.patient.full_name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-3xl bg-white p-6 shadow-xl dark:bg-zinc-950"
        >
            <header>
                <h1 class="text-2xl font-semibold">
                    {{ props.patient.full_name }}
                </h1>
                <p class="text-sm text-zinc-500">
                    Expediente clínico integral del paciente.
                </p>
            </header>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border p-3">
                    <p class="text-xs text-zinc-500">Estado</p>
                    <p class="font-medium">
                        {{ tGeneralStatus(props.patient.status) }}
                    </p>
                </article>
                <article class="rounded-2xl border p-3">
                    <p class="text-xs text-zinc-500">Teléfono</p>
                    <p class="font-medium">
                        {{ props.patient.telefono || '—' }}
                    </p>
                </article>
                <article class="rounded-2xl border p-3">
                    <p class="text-xs text-zinc-500">Email</p>
                    <p class="font-medium">{{ props.patient.email || '—' }}</p>
                </article>
                <article class="rounded-2xl border p-3">
                    <p class="text-xs text-zinc-500">Nacimiento</p>
                    <p class="font-medium">
                        {{
                            props.patient.fecha_nacimiento
                                ? formatDateMx(props.patient.fecha_nacimiento)
                                : '—'
                        }}
                    </p>
                </article>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <section class="rounded-2xl border p-4">
                    <h3 class="mb-2 font-semibold">Historial de citas</h3>
                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="item in props.appointments"
                            :key="item.id"
                            class="rounded-xl bg-zinc-50 p-2 dark:bg-zinc-900"
                        >
                            <span class="font-medium">{{
                                formatDateTimeMx(item.start_at)
                            }}</span>
                            · {{ item.therapist_name || 'Sin terapeuta' }} ·
                            {{ tAppointmentStatus(item.status) }}
                        </li>
                    </ul>
                </section>

                <section class="rounded-2xl border p-4">
                    <h3 class="mb-2 font-semibold">Historial de sesiones</h3>
                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="item in props.sessions"
                            :key="item.id"
                            class="rounded-xl bg-zinc-50 p-2 dark:bg-zinc-900"
                        >
                            {{ formatDateMx(item.session_date) }} ·
                            {{ item.therapist_name || 'Sin terapeuta' }} · Dolor
                            {{ item.pain_scale ?? '—' }}/10
                        </li>
                    </ul>
                </section>

                <section class="rounded-2xl border p-4">
                    <h3 class="mb-2 font-semibold">Documentos y archivos</h3>
                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="item in props.files"
                            :key="item.id"
                            class="rounded-xl bg-zinc-50 p-2 dark:bg-zinc-900"
                        >
                            {{ item.original_name }} ·
                            {{ item.file_type || item.mime || '—' }} ·
                            {{ formatDateTimeMx(item.created_at) }}
                        </li>
                    </ul>
                </section>

                <section class="rounded-2xl border p-4">
                    <h3 class="mb-2 font-semibold">Pagos relacionados</h3>
                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="item in props.payments"
                            :key="item.id"
                            class="rounded-xl bg-zinc-50 p-2 dark:bg-zinc-900"
                        >
                            {{
                                Number(item.amount ?? 0).toLocaleString(
                                    'es-MX',
                                    {
                                        style: 'currency',
                                        currency: item.currency || 'MXN',
                                    },
                                )
                            }}
                            · {{ tPaymentStatus(item.status) }} ·
                            {{
                                item.paid_at
                                    ? formatDateTimeMx(item.paid_at)
                                    : '—'
                            }}
                        </li>
                    </ul>
                </section>
            </div>

            <section class="rounded-2xl border p-4">
                <h3 class="mb-2 font-semibold">
                    Línea de tiempo de seguimiento
                </h3>
                <ul class="space-y-2 text-sm">
                    <li
                        v-for="item in props.activities"
                        :key="item.id"
                        class="rounded-xl bg-zinc-50 p-2 dark:bg-zinc-900"
                    >
                        {{
                            item.due_date
                                ? formatDateTimeMx(item.due_date)
                                : 'Sin fecha'
                        }}: {{ item.title }} · {{ tPriority(item.priority) }} ·
                        {{ tActivityStatus(item.status) }} ·
                        {{ item.responsible_name || 'Sin responsable' }}
                    </li>
                </ul>
            </section>
        </section>
    </AppLayout>
</template>
