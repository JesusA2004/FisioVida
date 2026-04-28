<script setup lang="ts">
import { computed } from 'vue';
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
} from '@/components/ui/dialog';
import {
    Wallet,
    Search,
    Pencil,
    Trash2,
    CheckCircle2,
    RotateCcw,
    XCircle,
} from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import DateTimePicker from '@/components/ui/DateTimePicker.vue';
import { formatDateTimeMx } from '@/lib/dates';
import { tPaymentStatus } from '@/lib/labels';

const props = defineProps<{
    rows: PagoRow[];
    page: { current_page: number; last_page: number; total: number };
    filters: { q?: string; status?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pagos', href: '/pagos' }];
const {
    form,
    isOpen,
    editingId,
    can,
    moduleEnabled,
    applyFilters,
    openCreate,
    openEdit,
    closeModal,
    submit,
    setStatus,
    destroyPago,
} = usePagoCrud(props.filters);
const isEditing = computed(() => editingId.value !== null);

const money = (value: number, currency: string) =>
    Number(value ?? 0).toLocaleString('es-MX', {
        style: 'currency',
        currency: currency || 'MXN',
    });

const statusClass = (status: PagoRow['status']) =>
    status === 'paid'
        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
        : status === 'refunded'
          ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300'
          : status === 'failed'
            ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
</script>

<template>
    <Head title="Pagos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950"
        >
            <div
                v-if="!moduleEnabled"
                class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
            >
                Módulo de pagos deshabilitado.
            </div>
            <template v-else>
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h1
                            class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100"
                        >
                            Pagos
                        </h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Este módulo registra pagos de pacientes por citas,
                            sesiones o servicios. En el futuro puede conectarse
                            a una pasarela de pago.
                        </p>
                    </div>
                    <Button
                        v-if="can('payments.create')"
                        class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
                        @click="openCreate"
                        ><Wallet class="mr-2 h-4 w-4" />Nuevo pago</Button
                    >
                </div>

                <div
                    class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"
                >
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="relative md:col-span-2">
                            <Search
                                class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-zinc-400"
                            /><Input
                                class="pl-9"
                                :default-value="props.filters.q ?? ''"
                                placeholder="Buscar por referencia, pasarela o ID de transacción"
                                @change="
                                    (e) =>
                                        applyFilters({
                                            q: (e.target as HTMLInputElement)
                                                .value,
                                            page: 1,
                                        })
                                "
                            />
                        </div>
                        <SearchableSelect
                            :model-value="props.filters.status ?? null"
                            :options="[
                                { value: null, label: 'Todos' },
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
                            placeholder="Filtrar estado"
                            clearable
                            @update:model-value="
                                (value) =>
                                    applyFilters({
                                        status: value ?? '',
                                        page: 1,
                                    })
                            "
                        />
                    </div>
                </div>

                <div
                    v-if="props.rows.length === 0"
                    class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/40"
                >
                    Sin pagos para mostrar.
                </div>
                <div v-else class="space-y-3">
                    <article
                        v-for="row in props.rows"
                        :key="row.id"
                        class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div
                            class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3
                                        class="font-semibold text-zinc-900 dark:text-zinc-100"
                                    >
                                        {{ money(row.amount, row.currency) }}
                                    </h3>
                                    <Badge
                                        class="rounded-full px-3 py-1 text-xs"
                                        :class="statusClass(row.status)"
                                        >{{ tPaymentStatus(row.status) }}</Badge
                                    >
                                </div>
                                <p
                                    class="text-sm text-zinc-500 dark:text-zinc-400"
                                >
                                    Pasarela o método: {{ row.provider }} · ID
                                    de transacción:
                                    {{ row.provider_payment_id || 'Sin ID' }}
                                </p>
                                <p
                                    class="text-xs text-zinc-500 dark:text-zinc-400"
                                >
                                    Referencia: {{ row.reference || '—' }} ·
                                    Fecha de pago:
                                    {{
                                        row.paid_at
                                            ? formatDateTimeMx(row.paid_at)
                                            : '—'
                                    }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-if="can('payments.update')"
                                    variant="outline"
                                    class="rounded-xl"
                                    @click="openEdit(row)"
                                    ><Pencil
                                        class="mr-2 h-4 w-4"
                                    />Editar</Button
                                >
                                <Button
                                    v-if="can('payments.update')"
                                    variant="outline"
                                    class="rounded-xl"
                                    @click="setStatus(row, 'paid')"
                                    ><CheckCircle2
                                        class="mr-2 h-4 w-4"
                                    />Pagado</Button
                                >
                                <Button
                                    v-if="can('payments.update')"
                                    variant="outline"
                                    class="rounded-xl"
                                    @click="setStatus(row, 'refunded')"
                                    ><RotateCcw
                                        class="mr-2 h-4 w-4"
                                    />Reembolsar</Button
                                >
                                <Button
                                    v-if="can('payments.update')"
                                    variant="outline"
                                    class="rounded-xl"
                                    @click="setStatus(row, 'failed')"
                                    ><XCircle
                                        class="mr-2 h-4 w-4"
                                    />Cancelar</Button
                                >
                                <Button
                                    v-if="can('payments.delete')"
                                    variant="destructive"
                                    class="rounded-xl"
                                    @click="destroyPago(row)"
                                    ><Trash2
                                        class="mr-2 h-4 w-4"
                                    />Eliminar</Button
                                >
                            </div>
                        </div>
                    </article>
                </div>

                <div
                    class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/40"
                >
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Total: {{ props.page.total }}
                    </p>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            :disabled="props.page.current_page <= 1"
                            @click="
                                applyFilters({
                                    page: props.page.current_page - 1,
                                })
                            "
                            >Anterior</Button
                        ><Button
                            variant="outline"
                            :disabled="
                                props.page.current_page >= props.page.last_page
                            "
                            @click="
                                applyFilters({
                                    page: props.page.current_page + 1,
                                })
                            "
                            >Siguiente</Button
                        >
                    </div>
                </div>
            </template>
        </section>

        <Dialog :open="isOpen" @update:open="closeModal">
            <DialogContent
                class="max-w-4xl rounded-3xl border-none bg-white shadow-2xl dark:bg-zinc-950"
            >
                <DialogHeader
                    ><DialogTitle>{{
                        isEditing ? 'Editar pago' : 'Nuevo pago'
                    }}</DialogTitle></DialogHeader
                >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label>Pasarela o método</Label
                        ><Input v-model="form.provider" />
                        <p
                            v-if="form.errors.provider"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.provider }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label>ID de transacción</Label
                        ><Input v-model="form.provider_payment_id" />
                    </div>
                    <div class="space-y-2">
                        <Label>Monto</Label
                        ><Input
                            v-model="form.amount"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label>Moneda</Label
                        ><SearchableSelect
                            v-model="form.currency"
                            :options="[
                                { value: 'MXN', label: 'Peso mexicano (MXN)' },
                                { value: 'USD', label: 'Dólar (USD)' },
                                { value: 'EUR', label: 'Euro (EUR)' },
                            ]"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label>Estado</Label
                        ><SearchableSelect
                            v-model="form.status"
                            :options="[
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
                    </div>
                    <div class="space-y-2">
                        <Label>Fecha de pago</Label
                        ><DateTimePicker v-model="form.paid_at" />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label>Referencia</Label
                        ><Input v-model="form.reference" />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label>Notas</Label
                        ><textarea
                            v-model="form.notes"
                            class="min-h-20 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900"
                        />
                    </div>
                </div>
                <DialogFooter
                    ><Button variant="outline" @click="closeModal"
                        >Cancelar</Button
                    ><Button :disabled="form.processing" @click="submit">{{
                        form.processing ? 'Guardando...' : 'Guardar'
                    }}</Button></DialogFooter
                >
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
