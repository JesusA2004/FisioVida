<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import { formatDateTimeMx } from '@/lib/dates';
import {
    Archive,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Download,
    Edit3,
    Eye,
    File,
    FileArchive,
    FileImage,
    FileSpreadsheet,
    FileText,
    FileUp,
    Filter,
    FolderOpen,
    Loader2,
    Search,
    Sparkles,
    Trash2,
    UploadCloud,
    UserRound,
    X,
} from 'lucide-vue-next';
import {
    type ArchivoFilters,
    type ArchivoLookup,
    type ArchivoPage,
    type ArchivoRow,
    useArchivoCrud,
} from '@/composables/crud/useArchivoCrud';

const props = defineProps<{
    rows: ArchivoRow[];
    page: ArchivoPage;
    filters: ArchivoFilters;
    lookups: ArchivoLookup;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Archivos', href: '/archivos' }];

const crud = useArchivoCrud(props);

const perPageOptions = [
    { value: 10, label: '10 por página' },
    { value: 15, label: '15 por página' },
    { value: 20, label: '20 por página' },
    { value: 50, label: '50 por página' },
    { value: 'all', label: 'Todos' },
];

const getExtension = (row: ArchivoRow) => {
    if (row.extension) return row.extension.toUpperCase();

    const cleanName = row.original_name ?? '';
    const ext = cleanName.split('.').pop();

    return ext ? ext.toUpperCase() : 'FILE';
};

const getFileIcon = (row: ArchivoRow) => {
    const mime = row.mime ?? '';
    const extension = (row.extension ?? '').toLowerCase();

    if (row.is_image || mime.startsWith('image/')) return FileImage;
    if (row.is_pdf || extension === 'pdf') return FileText;

    if (['xls', 'xlsx', 'csv'].includes(extension)) return FileSpreadsheet;
    if (['zip', 'rar', '7z'].includes(extension)) return FileArchive;
    if (['doc', 'docx', 'txt'].includes(extension)) return FileText;

    return File;
};

const getFileSoftColor = (row: ArchivoRow) => {
    const extension = (row.extension ?? '').toLowerCase();
    const mime = row.mime ?? '';

    if (row.is_image || mime.startsWith('image/')) {
        return 'bg-sky-50 text-sky-700 ring-sky-100 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900';
    }

    if (row.is_pdf || extension === 'pdf') {
        return 'bg-red-50 text-red-700 ring-red-100 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-900';
    }

    if (['xls', 'xlsx', 'csv'].includes(extension)) {
        return 'bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900';
    }

    if (['doc', 'docx', 'txt'].includes(extension)) {
        return 'bg-blue-50 text-blue-700 ring-blue-100 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-900';
    }

    if (['zip', 'rar', '7z'].includes(extension)) {
        return 'bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900';
    }

    return 'bg-zinc-50 text-zinc-700 ring-zinc-100 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-800';
};

const getFileTypeLabel = (row: ArchivoRow) => {
    return row.file_type_label || row.file_type || 'Sin clasificar';
};
</script>

<template>
    <Head title="Archivos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="w-full space-y-5">
            <!-- Header -->
            <div
                class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950"
            >
                <div
                    class="relative isolate overflow-hidden px-5 py-6 sm:px-7 lg:px-8"
                    style="
                        background:
                            radial-gradient(circle at top left, color-mix(in srgb, var(--primary) 16%, transparent), transparent 34%),
                            linear-gradient(135deg, color-mix(in srgb, var(--primary) 8%, white), white);
                    "
                >
                    <div
                        class="absolute right-6 top-5 hidden h-28 w-28 rounded-full opacity-40 blur-3xl sm:block"
                        style="background: var(--primary)"
                    />

                    <div
                        class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div class="flex gap-4">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-3xl shadow-sm"
                                style="
                                    background: var(--primary);
                                    color: var(--primary-foreground);
                                "
                            >
                                <FolderOpen class="h-7 w-7" />
                            </div>

                            <div>
                                <div
                                    class="mb-2 inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/70 px-3 py-1 text-xs font-medium text-zinc-600 shadow-sm backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/70 dark:text-zinc-300"
                                >
                                    <Sparkles class="h-3.5 w-3.5" />
                                    Expediente digital
                                </div>

                                <h1
                                    class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-3xl"
                                >
                                    Archivos clínicos
                                </h1>

                                <p
                                    class="mt-2 max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-300"
                                >
                                    Administra documentos, estudios, imágenes,
                                    consentimientos, recetas, archivos de Excel
                                    y evidencias relacionadas con pacientes o
                                    sesiones clínicas.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <label
                                for="archivo-input-principal"
                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl px-5 py-3 text-sm font-semibold shadow-sm transition hover:scale-[1.01] active:scale-[0.99]"
                                style="
                                    background: var(--primary);
                                    color: var(--primary-foreground);
                                "
                            >
                                <UploadCloud class="h-4 w-4" />
                                Seleccionar archivos
                            </label>

                            <input
                                id="archivo-input-principal"
                                type="file"
                                multiple
                                class="hidden"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.svg,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip"
                                @change="crud.onFileInputChange"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload panel -->
            <div
                class="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]"
            >
                <div
                    class="rounded-[2rem] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 sm:p-5"
                >
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2
                                class="text-base font-semibold text-zinc-950 dark:text-white"
                            >
                                Subir nuevos archivos
                            </h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                Arrastra tus documentos o usa el botón para
                                seleccionarlos.
                            </p>
                        </div>

                        <div
                            v-if="crud.hasFiles.value"
                            class="hidden rounded-full px-3 py-1 text-xs font-semibold sm:block"
                            style="
                                background: color-mix(in srgb, var(--primary) 10%, white);
                                color: var(--primary);
                            "
                        >
                            {{ crud.selectedFiles.value.length }}
                            archivo(s)
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-3">
                        <div class="space-y-2">
                            <Label>Paciente relacionado</Label>
                            <SearchableSelect
                                v-model="crud.form.patient_persona_id"
                                :options="[
                                    {
                                        value: '',
                                        label: 'Sin paciente específico',
                                    },
                                    ...props.lookups.patients.map((p) => ({
                                        value: p.id,
                                        label: p.label,
                                    })),
                                ]"
                            />
                            <p class="text-xs text-zinc-500">
                                Opcional, pero recomendado.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Sesión relacionada</Label>
                            <SearchableSelect
                                v-model="crud.form.session_id"
                                :options="[
                                    {
                                        value: '',
                                        label: 'Sin sesión específica',
                                    },
                                    ...props.lookups.sessions.map((s) => ({
                                        value: s.id,
                                        label: s.label,
                                    })),
                                ]"
                            />
                            <p class="text-xs text-zinc-500">
                                Útil para evidencias o evolución.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Tipo de archivo</Label>
                            <SearchableSelect
                                v-model="crud.form.file_type"
                                :options="[
                                    { value: '', label: 'Seleccionar tipo' },
                                    ...crud.fileTypeOptions.value,
                                ]"
                            />
                            <p
                                v-if="crud.form.errors.file_type"
                                class="text-xs font-medium text-red-600"
                            >
                                {{ crud.form.errors.file_type }}
                            </p>
                            <p v-else class="text-xs text-zinc-500">
                                Ayuda a organizar el expediente.
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-5 rounded-[1.75rem] border border-dashed p-6 text-center transition sm:p-8"
                        :class="
                            crud.isDragging.value
                                ? 'scale-[1.01] border-[var(--primary)] bg-[color-mix(in_srgb,var(--primary)_8%,white)]'
                                : 'border-zinc-300 bg-zinc-50/70 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900/40 dark:hover:border-zinc-600'
                        "
                        @drop="crud.onDrop"
                        @dragover="crud.onDragOver"
                        @dragleave="crud.onDragLeave"
                    >
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-[1.5rem] shadow-sm ring-1 ring-zinc-200 dark:ring-zinc-800"
                            style="
                                background: color-mix(in srgb, var(--primary) 12%, white);
                                color: var(--primary);
                            "
                        >
                            <FileUp class="h-8 w-8" />
                        </div>

                        <h3
                            class="text-base font-semibold text-zinc-950 dark:text-white"
                        >
                            Arrastra y suelta tus archivos aquí
                        </h3>

                        <p
                            class="mx-auto mt-2 max-w-xl text-sm leading-6 text-zinc-500 dark:text-zinc-400"
                        >
                            Se permiten {{ crud.acceptedExtensionsLabel }}. Puedes
                            subir varios archivos en una sola carga.
                        </p>

                        <div
                            class="mt-5 flex flex-col items-center justify-center gap-3 sm:flex-row"
                        >
                            <label
                                for="archivo-input-dropzone"
                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl px-5 py-3 text-sm font-semibold shadow-sm transition hover:scale-[1.01] active:scale-[0.99]"
                                style="
                                    background: var(--primary);
                                    color: var(--primary-foreground);
                                "
                            >
                                <UploadCloud class="h-4 w-4" />
                                Buscar archivos
                            </label>

                            <Button
                                type="button"
                                variant="outline"
                                class="rounded-2xl"
                                :disabled="!crud.hasFiles.value || crud.form.processing"
                                @click="crud.clearSelectedFiles"
                            >
                                <X class="mr-2 h-4 w-4" />
                                Limpiar selección
                            </Button>

                            <input
                                id="archivo-input-dropzone"
                                type="file"
                                multiple
                                class="hidden"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.svg,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip"
                                @change="crud.onFileInputChange"
                            />
                        </div>
                    </div>

                    <div
                        v-if="crud.form.errors.files"
                        class="mt-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300"
                    >
                        {{ crud.form.errors.files }}
                    </div>

                    <div
                        v-if="crud.selectedFiles.value.length"
                        class="mt-5 space-y-3"
                    >
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p
                                    class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                >
                                    Archivos listos para subir
                                </p>
                                <p class="text-xs text-zinc-500">
                                    Peso total:
                                    {{ crud.fmtSize(crud.totalUploadSize.value) }}
                                </p>
                            </div>

                            <Button
                                type="button"
                                class="rounded-2xl px-5"
                                :disabled="
                                    crud.form.processing ||
                                    !crud.selectedFiles.value.length
                                "
                                style="
                                    background: var(--primary);
                                    color: var(--primary-foreground);
                                "
                                @click="crud.uploadFiles"
                            >
                                <Loader2
                                    v-if="crud.form.processing"
                                    class="mr-2 h-4 w-4 animate-spin"
                                />
                                <UploadCloud v-else class="mr-2 h-4 w-4" />
                                {{
                                    crud.form.processing
                                        ? 'Subiendo...'
                                        : 'Subir archivos'
                                }}
                            </Button>
                        </div>

                        <div
                            v-if="crud.form.processing"
                            class="overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"
                        >
                            <div
                                class="h-2 rounded-full transition-all"
                                :style="{
                                    width: `${crud.uploadProgress.value}%`,
                                    background: 'var(--primary)',
                                }"
                            />
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div
                                v-for="(file, index) in crud.selectedFiles.value"
                                :key="`${file.name}-${file.size}-${index}`"
                                class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                            >
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ring-1 ring-zinc-200 dark:ring-zinc-800"
                                    style="
                                        background: color-mix(in srgb, var(--primary) 10%, white);
                                        color: var(--primary);
                                    "
                                >
                                    <File class="h-5 w-5" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                    >
                                        {{ file.name }}
                                    </p>
                                    <p class="text-xs text-zinc-500">
                                        {{ crud.fmtSize(file.size) }}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-xl p-2 text-zinc-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                    :disabled="crud.form.processing"
                                    @click="crud.removeSelectedFile(index)"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help / Summary -->
                <div
                    class="rounded-[2rem] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                            style="
                                background: color-mix(in srgb, var(--primary) 10%, white);
                                color: var(--primary);
                            "
                        >
                            <Archive class="h-5 w-5" />
                        </div>

                        <div>
                            <h2
                                class="text-base font-semibold text-zinc-950 dark:text-white"
                            >
                                Organización del expediente
                            </h2>
                            <p
                                class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400"
                            >
                                Los archivos pueden quedar ligados a un paciente,
                                a una sesión o solo guardarse como documento
                                general para clasificarlo después.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3">
                        <div
                            class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"
                        >
                            <div class="flex items-center gap-2">
                                <CheckCircle2
                                    class="h-4 w-4"
                                    style="color: var(--primary)"
                                />
                                <p
                                    class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                >
                                    Formatos permitidos
                                </p>
                            </div>
                            <p class="mt-1 text-sm text-zinc-500">
                                PDF, imágenes, Word, Excel, PowerPoint, TXT, CSV
                                y ZIP.
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"
                        >
                            <div class="flex items-center gap-2">
                                <CheckCircle2
                                    class="h-4 w-4"
                                    style="color: var(--primary)"
                                />
                                <p
                                    class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                >
                                    Carga múltiple
                                </p>
                            </div>
                            <p class="mt-1 text-sm text-zinc-500">
                                Puedes seleccionar varios documentos y subirlos
                                juntos.
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"
                        >
                            <div class="flex items-center gap-2">
                                <CheckCircle2
                                    class="h-4 w-4"
                                    style="color: var(--primary)"
                                />
                                <p
                                    class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                                >
                                    Seguimiento visual
                                </p>
                            </div>
                            <p class="mt-1 text-sm text-zinc-500">
                                El sistema muestra progreso mientras se realiza
                                la carga.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="rounded-[2rem] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 sm:p-5"
            >
                <div class="mb-4 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl"
                        style="
                            background: color-mix(in srgb, var(--primary) 10%, white);
                            color: var(--primary);
                        "
                    >
                        <Filter class="h-5 w-5" />
                    </div>

                    <div>
                        <h2
                            class="text-base font-semibold text-zinc-950 dark:text-white"
                        >
                            Buscar y filtrar
                        </h2>
                        <p class="text-sm text-zinc-500">
                            Encuentra archivos por nombre, paciente, tipo o
                            sesión.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 lg:grid-cols-5">
                    <div class="relative lg:col-span-2">
                        <Search
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
                        />
                        <Input
                            :default-value="props.filters.q ?? ''"
                            class="h-11 rounded-2xl pl-9"
                            placeholder="Buscar archivo, paciente o usuario..."
                            @change="
                                crud.applySearch(
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                    </div>

                    <SearchableSelect
                        :model-value="props.filters.file_type ?? ''"
                        :options="[
                            { value: '', label: 'Todos los tipos' },
                            ...crud.fileTypeOptions.value,
                        ]"
                        @update:model-value="
                            crud.applyFilter('file_type', $event)
                        "
                    />

                    <SearchableSelect
                        :model-value="props.filters.patient_persona_id ?? ''"
                        :options="[
                            { value: '', label: 'Todos los pacientes' },
                            ...props.lookups.patients.map((p) => ({
                                value: p.id,
                                label: p.label,
                            })),
                        ]"
                        @update:model-value="
                            crud.applyFilter('patient_persona_id', $event)
                        "
                    />

                    <SearchableSelect
                        :model-value="props.filters.per_page ?? 10"
                        :options="perPageOptions"
                        @update:model-value="
                            crud.changePerPage(($event ?? 10) as number | 'all' | string)
                        "
                    />
                </div>
            </div>

            <!-- List -->
            <div
                class="rounded-[2rem] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 sm:p-5"
            >
                <div
                    class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            class="text-base font-semibold text-zinc-950 dark:text-white"
                        >
                            Archivos registrados
                        </h2>
                        <p class="text-sm text-zinc-500">
                            Total: {{ props.page.total }} archivo(s)
                        </p>
                    </div>

                    <div
                        class="rounded-full px-3 py-1 text-xs font-semibold"
                        style="
                            background: color-mix(in srgb, var(--primary) 10%, white);
                            color: var(--primary);
                        "
                    >
                        Página {{ props.page.current_page }} de
                        {{ props.page.last_page }}
                    </div>
                </div>

                <div
                    v-if="props.rows.length === 0"
                    class="rounded-[2rem] border border-dashed border-zinc-300 bg-zinc-50 px-6 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900/40"
                >
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-[1.5rem]"
                        style="
                            background: color-mix(in srgb, var(--primary) 10%, white);
                            color: var(--primary);
                        "
                    >
                        <FolderOpen class="h-8 w-8" />
                    </div>

                    <h3
                        class="mt-4 text-base font-semibold text-zinc-950 dark:text-white"
                    >
                        No hay archivos para mostrar
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm text-zinc-500">
                        Cuando subas documentos, aparecerán aquí organizados en
                        cards para consultarlos fácilmente.
                    </p>
                </div>

                <div v-else class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
                    <article
                        v-for="row in props.rows"
                        :key="row.id"
                        class="group rounded-[1.75rem] border border-zinc-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900/40"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-1"
                                :class="getFileSoftColor(row)"
                            >
                                <component :is="getFileIcon(row)" class="h-6 w-6" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <button
                                    type="button"
                                    class="line-clamp-2 text-left text-sm font-semibold leading-5 text-zinc-950 transition hover:underline dark:text-white"
                                    @click="crud.openArchivo(row)"
                                >
                                    {{ row.original_name }}
                                </button>

                                <div
                                    class="mt-2 flex flex-wrap items-center gap-2"
                                >
                                    <span
                                        class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                                    >
                                        {{ getExtension(row) }}
                                    </span>

                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                        style="
                                            background: color-mix(in srgb, var(--primary) 10%, white);
                                            color: var(--primary);
                                        "
                                    >
                                        {{ getFileTypeLabel(row) }}
                                    </span>

                                    <span
                                        class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
                                    >
                                        {{ crud.fmtSize(row.size_bytes) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-2 text-sm">
                            <div
                                class="flex items-center gap-2 rounded-2xl bg-zinc-50 px-3 py-2 dark:bg-zinc-950/50"
                            >
                                <UserRound
                                    class="h-4 w-4 shrink-0 text-zinc-400"
                                />
                                <span class="truncate text-zinc-600 dark:text-zinc-300">
                                    {{ row.patient_name || 'Sin paciente relacionado' }}
                                </span>
                            </div>

                            <div
                                class="flex items-center gap-2 rounded-2xl bg-zinc-50 px-3 py-2 dark:bg-zinc-950/50"
                            >
                                <CalendarDays
                                    class="h-4 w-4 shrink-0 text-zinc-400"
                                />
                                <span class="truncate text-zinc-600 dark:text-zinc-300">
                                    {{
                                        row.session_id
                                            ? `Sesión #${row.session_id}${row.session_date ? ` · ${row.session_date}` : ''}`
                                            : 'Sin sesión relacionada'
                                    }}
                                </span>
                            </div>

                            <div
                                class="flex items-center gap-2 rounded-2xl bg-zinc-50 px-3 py-2 dark:bg-zinc-950/50"
                            >
                                <UploadCloud
                                    class="h-4 w-4 shrink-0 text-zinc-400"
                                />
                                <span class="truncate text-zinc-600 dark:text-zinc-300">
                                    Subido por
                                    {{ row.uploaded_by_name || 'usuario no registrado' }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="mt-4 flex flex-col gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p class="text-xs text-zinc-500">
                                {{
                                    row.created_at
                                        ? formatDateTimeMx(row.created_at)
                                        : 'Sin fecha'
                                }}
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="rounded-xl"
                                    :disabled="!row.url"
                                    @click="crud.openArchivo(row)"
                                >
                                    <Eye class="mr-1.5 h-4 w-4" />
                                    Ver
                                </Button>

                                <a
                                    v-if="row.url"
                                    :href="row.url"
                                    target="_blank"
                                    download
                                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-900"
                                >
                                    <Download class="h-4 w-4" />
                                    Descargar
                                </a>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="rounded-xl"
                                    @click="crud.openEdit(row)"
                                >
                                    <Edit3 class="mr-1.5 h-4 w-4" />
                                    Editar
                                </Button>

                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    class="rounded-xl"
                                    @click="crud.destroyArchivo(row)"
                                >
                                    <Trash2 class="mr-1.5 h-4 w-4" />
                                    Eliminar
                                </Button>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Pagination -->
                <div
                    class="mt-5 flex flex-col gap-3 rounded-[1.5rem] border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/40 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Mostrando página
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ props.page.current_page }}
                        </span>
                        de
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ props.page.last_page }}
                        </span>
                    </p>

                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="rounded-2xl"
                            :disabled="props.page.current_page <= 1"
                            @click="crud.goPage(props.page.current_page - 1)"
                        >
                            <ChevronLeft class="mr-1 h-4 w-4" />
                            Anterior
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            class="rounded-2xl"
                            :disabled="
                                props.page.current_page >= props.page.last_page
                            "
                            @click="crud.goPage(props.page.current_page + 1)"
                        >
                            Siguiente
                            <ChevronRight class="ml-1 h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Edit modal -->
            <div
                v-if="crud.showEditModal.value"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4 backdrop-blur-sm"
            >
                <div
                    class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-[2rem] bg-white shadow-2xl dark:bg-zinc-950"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800"
                    >
                        <div class="flex gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                                style="
                                    background: color-mix(in srgb, var(--primary) 10%, white);
                                    color: var(--primary);
                                "
                            >
                                <Edit3 class="h-5 w-5" />
                            </div>

                            <div>
                                <h2
                                    class="text-lg font-semibold text-zinc-950 dark:text-white"
                                >
                                    Editar archivo
                                </h2>
                                <p class="mt-1 text-sm text-zinc-500">
                                    Solo se actualiza la clasificación y la
                                    relación del archivo. El documento físico no
                                    se reemplaza.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-2xl p-2 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-900 dark:hover:text-zinc-200"
                            @click="crud.closeEdit"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="overflow-y-auto px-5 py-5">
                        <div
                            v-if="crud.editingRow.value"
                            class="mb-5 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"
                        >
                            <p class="text-xs font-semibold uppercase text-zinc-400">
                                Archivo seleccionado
                            </p>
                            <p
                                class="mt-1 break-words text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                            >
                                {{ crud.editingRow.value.original_name }}
                            </p>
                            <p class="mt-1 text-xs text-zinc-500">
                                {{ crud.fmtSize(crud.editingRow.value.size_bytes) }}
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2 md:col-span-2">
                                <Label>Tipo de archivo</Label>
                                <SearchableSelect
                                    v-model="crud.editForm.file_type"
                                    :options="[
                                        {
                                            value: '',
                                            label: 'Seleccionar tipo',
                                        },
                                        ...crud.fileTypeOptions.value,
                                    ]"
                                />
                                <p
                                    v-if="crud.editForm.errors.file_type"
                                    class="text-xs font-medium text-red-600"
                                >
                                    {{ crud.editForm.errors.file_type }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label>Paciente relacionado</Label>
                                <SearchableSelect
                                    v-model="crud.editForm.patient_persona_id"
                                    :options="[
                                        {
                                            value: '',
                                            label: 'Sin paciente específico',
                                        },
                                        ...props.lookups.patients.map((p) => ({
                                            value: p.id,
                                            label: p.label,
                                        })),
                                    ]"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label>Sesión relacionada</Label>
                                <SearchableSelect
                                    v-model="crud.editForm.session_id"
                                    :options="[
                                        {
                                            value: '',
                                            label: 'Sin sesión específica',
                                        },
                                        ...props.lookups.sessions.map((s) => ({
                                            value: s.id,
                                            label: s.label,
                                        })),
                                    ]"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex flex-col-reverse gap-2 border-t border-zinc-200 px-5 py-4 dark:border-zinc-800 sm:flex-row sm:justify-end"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="rounded-2xl"
                            :disabled="crud.editForm.processing"
                            @click="crud.closeEdit"
                        >
                            Cancelar
                        </Button>

                        <Button
                            type="button"
                            class="rounded-2xl"
                            :disabled="crud.editForm.processing"
                            style="
                                background: var(--primary);
                                color: var(--primary-foreground);
                            "
                            @click="crud.updateArchivo"
                        >
                            <Loader2
                                v-if="crud.editForm.processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            <CheckCircle2 v-else class="mr-2 h-4 w-4" />
                            {{
                                crud.editForm.processing
                                    ? 'Guardando...'
                                    : 'Guardar cambios'
                            }}
                        </Button>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
