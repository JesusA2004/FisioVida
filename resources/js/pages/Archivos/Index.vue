<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import Swal from 'sweetalert2';
import { FileUp, Trash2 } from 'lucide-vue-next';
import { formatDateTimeMx } from '@/lib/dates';

type FileRow = {
    id: number;
    patient_persona_id?: number | null;
    patient_name?: string | null;
    session_id?: number | null;
    session_date?: string | null;
    uploaded_by_name?: string | null;
    original_name: string;
    file_type?: string | null;
    mime?: string | null;
    size_bytes?: number | null;
    created_at?: string | null;
    url?: string | null;
};

const props = defineProps<{
    rows: FileRow[];
    page: { current_page: number; last_page: number; total: number };
    filters: { q?: string };
    lookups: {
        patients: { id: number; label: string }[];
        sessions: { id: number; label: string }[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Archivos', href: '/archivos' },
];

const form = useForm({
    patient_persona_id: '' as number | '',
    session_id: '' as number | '',
    file_type: '',
    file: null as File | null,
});

const fmtSize = (n?: number | null) => {
    const v = Number(n ?? 0);
    if (!v) return '—';
    const kb = v / 1024;
    if (kb < 1024) return `${kb.toFixed(0)} KB`;
    return `${(kb / 1024).toFixed(1)} MB`;
};

const applyFilter = (e: Event) => {
    const value = (e.target as HTMLInputElement).value;
    router.get(
        route('archivos.index'),
        { ...props.filters, q: value },
        { preserveState: true, replace: true },
    );
};

const goPage = (page: number) => {
    router.get(
        route('archivos.index'),
        { ...props.filters, page },
        { preserveState: true, replace: true },
    );
};

const submit = () => {
    form.post(route('archivos.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const destroyFile = async (row: FileRow) => {
    const result = await Swal.fire({
        title: '¿Eliminar archivo?',
        text: row.original_name,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) return;
    router.delete(route('archivos.destroy', row.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Archivos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-3xl bg-white p-6 shadow-xl dark:bg-zinc-950"
        >
            <div>
                <h1
                    class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100"
                >
                    Archivos
                </h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Este módulo permite adjuntar documentos clínicos, estudios,
                    consentimientos, evidencias o archivos administrativos
                    relacionados con un paciente o una sesión.
                </p>
            </div>

            <div
                class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"
            >
                <div class="grid gap-3 md:grid-cols-4">
                    <Input
                        class="md:col-span-2"
                        :default-value="props.filters.q ?? ''"
                        placeholder="Buscar archivo, paciente o quién subió"
                        @change="applyFilter"
                    />
                    <SearchableSelect
                        v-model="form.patient_persona_id"
                        :options="[
                            { value: '', label: 'Paciente (opcional)' },
                            ...props.lookups.patients.map((p) => ({
                                value: p.id,
                                label: p.label,
                            })),
                        ]"
                    />
                    <SearchableSelect
                        v-model="form.session_id"
                        :options="[
                            { value: '', label: 'Sesión (opcional)' },
                            ...props.lookups.sessions.map((s) => ({
                                value: s.id,
                                label: s.label,
                            })),
                        ]"
                    />
                    <SearchableSelect
                        v-model="form.file_type"
                        :options="[
                            { value: '', label: 'Tipo de archivo' },
                            {
                                value: 'estudio_clinico',
                                label: 'Estudio clínico',
                            },
                            {
                                value: 'consentimiento',
                                label: 'Consentimiento',
                            },
                            { value: 'evidencia', label: 'Evidencia' },
                            {
                                value: 'receta_indicacion',
                                label: 'Receta/indicación',
                            },
                            {
                                value: 'documento_administrativo',
                                label: 'Documento administrativo',
                            },
                            { value: 'otro', label: 'Otro' },
                        ]"
                    />
                    <div class="md:col-span-3">
                        <Label>Archivo</Label>
                        <Input
                            type="file"
                            @change="
                                (e) =>
                                    (form.file =
                                        (e.target as HTMLInputElement)
                                            .files?.[0] ?? null)
                            "
                        />
                    </div>
                    <Button
                        class="md:col-span-1"
                        :disabled="form.processing || !form.file"
                        @click="submit"
                        ><FileUp class="mr-2 h-4 w-4" />{{
                            form.processing ? 'Subiendo...' : 'Subir archivo'
                        }}</Button
                    >
                </div>
            </div>

            <div
                v-if="props.rows.length === 0"
                class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/40"
            >
                Sin archivos para mostrar.
            </div>
            <div
                v-else
                class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800"
            >
                <table
                    class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800"
                >
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Archivo
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Tipo de archivo
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Paciente relacionado
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Sesión relacionada
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Subido por
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Fecha
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-zinc-100 dark:divide-zinc-800"
                    >
                        <tr
                            v-for="row in props.rows"
                            :key="row.id"
                            class="hover:bg-zinc-50 dark:hover:bg-zinc-900/40"
                        >
                            <td class="px-4 py-3">
                                <a
                                    v-if="row.url"
                                    :href="row.url"
                                    target="_blank"
                                    class="font-medium text-sky-700 hover:underline dark:text-sky-300"
                                    >{{ row.original_name }}</a
                                >
                                <span v-else>{{ row.original_name }}</span>
                                <p class="text-xs text-zinc-400">
                                    {{ fmtSize(row.size_bytes) }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                {{ row.file_type || row.mime || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ row.patient_name || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    row.session_id
                                        ? `#${row.session_id}${row.session_date ? ` · ${row.session_date}` : ''}`
                                        : '—'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                {{ row.uploaded_by_name || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    row.created_at
                                        ? formatDateTimeMx(row.created_at)
                                        : '—'
                                }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="destroyFile(row)"
                                    ><Trash2
                                        class="mr-1 h-4 w-4"
                                    />Eliminar</Button
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                        @click="goPage(props.page.current_page - 1)"
                        >Anterior</Button
                    >
                    <Button
                        variant="outline"
                        :disabled="
                            props.page.current_page >= props.page.last_page
                        "
                        @click="goPage(props.page.current_page + 1)"
                        >Siguiente</Button
                    >
                </div>
            </div>
        </section>
    </AppLayout>
</template>
