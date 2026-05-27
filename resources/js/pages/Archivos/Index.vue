<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import FvPagination from '@/components/fv/FvPagination.vue';
import { formatDateTimeMx } from '@/lib/dates';
import Swal from 'sweetalert2';
import {
    Archive,
    CalendarDays,
    CheckCircle2,
    Download,
    Edit3,
    ExternalLink,
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
    Trash2,
    UploadCloud,
    UserRound,
    X,
} from 'lucide-vue-next';

type SelectValue = string | number | null;

type ArchivoRow = {
    id: number;
    patient_persona_id?: number | null;
    patient_name?: string | null;
    session_id?: number | null;
    session_date?: string | null;
    uploaded_by?: number | null;
    uploaded_by_name?: string | null;
    original_name: string;
    file_type?: string | null;
    file_type_label?: string | null;
    mime?: string | null;
    extension?: string | null;
    size_bytes?: number | null;
    created_at?: string | null;
    url?: string | null;
    preview_url?: string | null;
    download_url?: string | null;
    is_image?: boolean;
    is_pdf?: boolean;
    can_preview?: boolean;
};

type ArchivoPage = {
    current_page: number;
    last_page: number;
    per_page: number | string;
    per_page_selected?: number | string;
    from?: number | null;
    to?: number | null;
    total: number;
};

type ArchivoFilters = {
    q?: string;
    file_type?: string;
    patient_persona_id?: string | number;
    session_id?: string | number;
    per_page?: number | string;
};

type ArchivoLookup = {
    patients: { id: number; label: string }[];
    sessions: {
        id: number;
        patient_persona_id?: number | null;
        label: string;
    }[];
    fileTypes: { value: string; label: string }[];
};

const props = defineProps<{
    rows: ArchivoRow[];
    page: ArchivoPage;
    filters: ArchivoFilters;
    lookups: ArchivoLookup;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Archivos', href: '/archivos' },
];

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    timer: 2600,
    timerProgressBar: true,
    showConfirmButton: false,
});

const search = ref(props.filters.q ?? '');
const selectedPreview = ref<ArchivoRow | null>(null);
const selectedEdit = ref<ArchivoRow | null>(null);
const isDragging = ref(false);
const selectedFiles = ref<File[]>([]);
const replacementFile = ref<File | null>(null);
const uploadProgress = ref(0);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const uploadForm = useForm({
    patient_persona_id: (props.filters.patient_persona_id ? Number(props.filters.patient_persona_id) : '') as number | '',
    session_id: '' as number | '',
    file_type: '',
    files: [] as File[],
});

const editForm = useForm({
    patient_persona_id: '' as number | '',
    session_id: '' as number | '',
    file_type: '',
    replacement_file: null as File | null,
});

const acceptedExtensionsLabel =
    'PDF, imágenes, Word, Excel, PowerPoint, TXT, CSV y ZIP';

const perPageSelected = computed(
    () => props.page.per_page_selected ?? props.page.per_page ?? 10,
);

const hasUploadPatient = computed(() => Boolean(uploadForm.patient_persona_id));
const hasUploadSession = computed(() => Boolean(uploadForm.session_id));
const hasEditPatient = computed(() => Boolean(editForm.patient_persona_id));
const hasEditSession = computed(() => Boolean(editForm.session_id));

const fromExpediente = computed(() => Boolean(props.filters.patient_persona_id));
const expedientePatientName = computed(() => {
    if (!fromExpediente.value) return null;
    return props.lookups.patients.find(
        (p) => p.id === Number(props.filters.patient_persona_id),
    )?.label ?? null;
});

const sessionPatientName = computed(() => {
    if (!uploadForm.session_id) return null;
    const session = props.lookups.sessions.find(
        (s) => s.id === Number(uploadForm.session_id),
    );
    if (!session?.patient_persona_id) return null;
    return props.lookups.patients.find(
        (p) => p.id === session.patient_persona_id,
    )?.label ?? null;
});

const selectedFilesTotalSize = computed(() =>
    selectedFiles.value.reduce((sum, file) => sum + file.size, 0),
);

const applyFilters = (payload: Record<string, unknown>) => {
    router.get(
        '/archivos',
        {
            q: search.value.trim(),
            file_type: props.filters.file_type ?? '',
            patient_persona_id: props.filters.patient_persona_id ?? '',
            session_id: props.filters.session_id ?? '',
            per_page: perPageSelected.value,
            ...payload,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.q ?? '';
    },
    { deep: true },
);

watch(search, (value) => {
    if (searchTimer) clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        applyFilters({
            q: value.trim(),
            page: 1,
        });
    }, 450);
});

const normalizeId = (value: SelectValue): number | '' => {
    if (value === null || value === undefined || value === '') return '';

    const numeric = Number(value);

    return Number.isNaN(numeric) ? '' : numeric;
};

const setUploadPatient = (value: SelectValue) => {
    uploadForm.patient_persona_id = normalizeId(value);

    if (uploadForm.patient_persona_id) {
        uploadForm.session_id = '';
    }
};

const setUploadSession = (value: SelectValue) => {
    uploadForm.session_id = normalizeId(value);

    if (uploadForm.session_id) {
        uploadForm.patient_persona_id = '';
    }
};

const setEditPatient = (value: SelectValue) => {
    editForm.patient_persona_id = normalizeId(value);

    if (editForm.patient_persona_id) {
        editForm.session_id = '';
    }
};

const setEditSession = (value: SelectValue) => {
    editForm.session_id = normalizeId(value);

    if (editForm.session_id) {
        editForm.patient_persona_id = '';
    }
};

const clearSearch = () => {
    search.value = '';
};

const fmtSize = (bytes?: number | null) => {
    const value = Number(bytes ?? 0);

    if (!value) return '—';

    const kb = value / 1024;

    if (kb < 1024) return `${kb.toFixed(0)} KB`;

    return `${(kb / 1024).toFixed(1)} MB`;
};

const getExtension = (row: ArchivoRow) => {
    if (row.extension) return row.extension.toUpperCase();

    const ext = row.original_name?.split('.').pop();

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
        return 'bg-sky-50 text-sky-700 ring-sky-100';
    }

    if (row.is_pdf || extension === 'pdf') {
        return 'bg-red-50 text-red-700 ring-red-100';
    }

    if (['xls', 'xlsx', 'csv'].includes(extension)) {
        return 'bg-emerald-50 text-emerald-700 ring-emerald-100';
    }

    if (['doc', 'docx', 'txt'].includes(extension)) {
        return 'bg-blue-50 text-blue-700 ring-blue-100';
    }

    if (['zip', 'rar', '7z'].includes(extension)) {
        return 'bg-amber-50 text-amber-700 ring-amber-100';
    }

    return 'bg-muted text-foreground ring-border';
};

const getFileTypeLabel = (row: ArchivoRow) => {
    return row.file_type_label || row.file_type || 'Sin clasificar';
};

const allowedExtensions = [
    'pdf',
    'jpg',
    'jpeg',
    'png',
    'webp',
    'gif',
    'svg',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
    'txt',
    'csv',
    'zip',
];

const isAllowedFile = (file: File) => {
    const ext = file.name.split('.').pop()?.toLowerCase() ?? '';

    return allowedExtensions.includes(ext);
};

const addFiles = async (files: FileList | File[]) => {
    const incoming = Array.from(files);
    const valid = incoming.filter(isAllowedFile);
    const invalid = incoming.filter((file) => !isAllowedFile(file));

    if (invalid.length) {
        await Swal.fire({
            icon: 'warning',
            title: 'Archivo no permitido',
            text: `Solo se permiten ${acceptedExtensionsLabel}.`,
            confirmButtonText: 'Entendido',
        });
    }

    selectedFiles.value = [...selectedFiles.value, ...valid];
    uploadForm.files = selectedFiles.value;
};

const removeSelectedFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
    uploadForm.files = selectedFiles.value;
};

const clearSelectedFiles = () => {
    selectedFiles.value = [];
    uploadForm.files = [];
    uploadProgress.value = 0;
};

const onFileInputChange = async (event: Event) => {
    const input = event.target as HTMLInputElement;

    if (!input.files?.length) return;

    await addFiles(input.files);

    input.value = '';
};

const onDrop = async (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;

    if (!event.dataTransfer?.files?.length) return;

    await addFiles(event.dataTransfer.files);
};

const onDragOver = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = true;
};

const onDragLeave = () => {
    isDragging.value = false;
};

const submitUpload = async () => {
    if (!uploadForm.file_type) {
        await Swal.fire({
            icon: 'warning',
            title: 'Selecciona el tipo de archivo',
            confirmButtonText: 'Entendido',
        });

        return;
    }

    if (!selectedFiles.value.length) {
        await Swal.fire({
            icon: 'warning',
            title: 'Selecciona al menos un archivo',
            confirmButtonText: 'Entendido',
        });

        return;
    }

    uploadForm.files = selectedFiles.value;

    Swal.fire({
        title: 'Subiendo archivos',
        text: 'Por favor espera un momento.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });

    uploadForm.post('/archivos', {
        forceFormData: true,
        preserveScroll: true,
        onProgress: (progress) => {
            uploadProgress.value = progress?.percentage ?? 0;
        },
        onSuccess: () => {
            Swal.close();
            clearSelectedFiles();
            uploadForm.reset();

            toast.fire({
                icon: 'success',
                title: 'Archivo cargado correctamente',
            });
        },
        onError: () => {
            Swal.close();

            Swal.fire({
                icon: 'error',
                title: 'No se pudo subir el archivo',
                text: 'Revisa el tipo de archivo, el peso o la configuración de carga.',
                confirmButtonText: 'Entendido',
            });
        },
    });
};

const openPreview = (row: ArchivoRow) => {
    selectedPreview.value = row;
};

const closePreview = () => {
    selectedPreview.value = null;
};

const openInNewTab = (row: ArchivoRow) => {
    if (!row.preview_url) return;

    window.open(row.preview_url, '_blank', 'noopener,noreferrer');
};

const downloadFile = (row: ArchivoRow) => {
    if (!row.download_url) return;

    window.open(row.download_url, '_blank', 'noopener,noreferrer');
};

const openEdit = (row: ArchivoRow) => {
    selectedEdit.value = row;

    editForm.patient_persona_id = row.patient_persona_id ?? '';
    editForm.session_id = row.session_id ?? '';
    editForm.file_type = row.file_type ?? '';
    editForm.replacement_file = null;
    replacementFile.value = null;
    editForm.clearErrors();
};

const closeEdit = () => {
    selectedEdit.value = null;
    replacementFile.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const onReplacementChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (!file) return;

    if (!isAllowedFile(file)) {
        input.value = '';

        Swal.fire({
            icon: 'warning',
            title: 'Archivo no permitido',
            text: `Solo se permiten ${acceptedExtensionsLabel}.`,
            confirmButtonText: 'Entendido',
        });

        return;
    }

    replacementFile.value = file;
    editForm.replacement_file = file;
};

const updateArchivo = async () => {
    if (!selectedEdit.value) return;

    if (!editForm.file_type) {
        await Swal.fire({
            icon: 'warning',
            title: 'Selecciona el tipo de archivo',
            confirmButtonText: 'Entendido',
        });

        return;
    }

    Swal.fire({
        title: 'Guardando cambios',
        text: 'Actualizando archivo.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });

    editForm
        .transform((data) => ({
            ...data,
            _method: 'put',
        }))
        .post(`/archivos/${selectedEdit.value.id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                Swal.close();
                closeEdit();

                toast.fire({
                    icon: 'success',
                    title: 'Archivo actualizado',
                });
            },
            onError: () => {
                Swal.close();

                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo actualizar',
                    text: 'Revisa los datos o el archivo de reemplazo.',
                    confirmButtonText: 'Entendido',
                });
            },
            onFinish: () => {
                editForm.transform((data) => data);
            },
        });
};

const destroyArchivo = async (row: ArchivoRow) => {
    const result = await Swal.fire({
        icon: 'warning',
        title: '¿Eliminar archivo?',
        text: row.original_name,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    });

    if (!result.isConfirmed) return;

    router.delete(`/archivos/${row.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.fire({
                icon: 'success',
                title: 'Archivo eliminado',
            });
        },
    });
};
</script>

<template>
    <Head title="Archivos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="w-full space-y-6">
            <!-- HEADER -->
            <div
                class="flex flex-col gap-4 rounded-[2rem] border border-border bg-card p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <div
                        class="mb-3 inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 text-xs font-semibold text-muted-foreground"
                    >
                        <Archive class="h-3.5 w-3.5" />
                        Expediente digital
                    </div>

                    <h1 class="text-2xl font-bold tracking-tight text-foreground">
                        Archivos
                    </h1>

                    <p class="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground">
                        Administra documentos clínicos, imágenes, archivos de
                        sesión y documentos relacionados con pacientes.
                    </p>
                </div>
            </div>

            <!-- UPLOAD -->
            <div
                class="rounded-[2rem] border border-border bg-card p-4 shadow-sm sm:p-5 lg:p-6"
            >
                <div
                    class="mb-5 flex flex-col gap-3 border-b border-border pb-5 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <div>
                            <h2 class="text-lg font-semibold text-foreground">
                                Subir nuevos archivos
                            </h2>

                            <p
                                v-if="expedientePatientName"
                                class="mt-1 text-sm font-medium text-sky-700 dark:text-sky-300"
                            >
                                Archivo para: {{ expedientePatientName }}
                            </p>

                            <p v-else class="mt-1 text-sm text-muted-foreground">
                                Relaciona los archivos con un paciente, sesión o
                                tipo de documento para mantener el expediente
                                organizado.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="selectedFiles.length"
                        class="w-fit rounded-full px-3 py-1 text-xs font-semibold"
                        style="
                            background: color-mix(
                                in srgb,
                                var(--primary) 10%,
                                white
                            );
                            color: var(--primary);
                        "
                    >
                        {{ selectedFiles.length }} archivo(s) seleccionados
                    </div>
                </div>

                <div
                    class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(360px,0.55fr)]"
                >
                    <!-- DROPZONE -->
                    <div
                        class="flex min-h-[340px] flex-col justify-center rounded-[1.75rem] border border-dashed p-6 text-center transition sm:p-8"
                        :class="
                            isDragging
                                ? 'scale-[1.01] border-[var(--primary)] bg-[color-mix(in_srgb,var(--primary)_10%,var(--card))]'
                                : 'border-border bg-muted/40 hover:border-muted-foreground/40'
                        "
                        @drop="onDrop"
                        @dragover="onDragOver"
                        @dragleave="onDragLeave"
                    >
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-[1.5rem] shadow-sm ring-1 ring-border"
                            style="
                                background: color-mix(
                                    in srgb,
                                    var(--primary) 12%,
                                    white
                                );
                                color: var(--primary);
                            "
                        >
                            <FileUp class="h-8 w-8" />
                        </div>

                        <h3 class="text-lg font-semibold text-foreground">
                            Arrastra tus archivos aquí
                        </h3>

                        <p
                            class="mx-auto mt-2 max-w-xl text-sm leading-6 text-muted-foreground"
                        >
                            Se permiten {{ acceptedExtensionsLabel }}. Máximo 50
                            MB por archivo y hasta 15 archivos por carga.
                        </p>

                        <div
                            class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row"
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
                                :disabled="
                                    !selectedFiles.length ||
                                    uploadForm.processing
                                "
                                @click="clearSelectedFiles"
                            >
                                <X class="mr-2 h-4 w-4" />
                                Limpiar
                            </Button>

                            <input
                                id="archivo-input-dropzone"
                                type="file"
                                multiple
                                class="hidden"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.svg,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip"
                                @change="onFileInputChange"
                            />
                        </div>
                    </div>

                    <!-- FORM CONFIG -->
                    <div class="space-y-4">
                        <div
                            class="rounded-[1.75rem] border border-border bg-muted p-4"
                        >
                            <h3
                                class="mb-4 text-sm font-semibold text-foreground"
                            >
                                Datos del archivo
                            </h3>

                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <Label>Paciente relacionado</Label>

                                    <div
                                        v-if="fromExpediente"
                                        class="flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm text-sky-800 dark:border-sky-800/50 dark:bg-sky-950/40 dark:text-sky-200"
                                    >
                                        <UserRound class="h-4 w-4 shrink-0" />
                                        Archivo para: <strong>{{ expedientePatientName }}</strong>
                                    </div>

                                    <div
                                        v-else
                                        :class="{
                                            'pointer-events-none opacity-50':
                                                hasUploadSession,
                                        }"
                                    >
                                        <SearchableSelect
                                            :model-value="
                                                uploadForm.patient_persona_id
                                            "
                                            :options="[
                                                {
                                                    value: '',
                                                    label: 'Sin paciente específico',
                                                },
                                                ...props.lookups.patients.map(
                                                    (p) => ({
                                                        value: p.id,
                                                        label: p.label,
                                                    }),
                                                ),
                                            ]"
                                            @update:model-value="
                                                setUploadPatient
                                            "
                                        />
                                    </div>

                                    <p
                                        v-if="!fromExpediente && hasUploadSession"
                                        class="text-xs text-amber-600"
                                    >
                                        Desactiva la sesión para elegir paciente
                                        manual.
                                    </p>

                                    <p
                                        v-else-if="!fromExpediente && !hasUploadSession"
                                        class="text-xs text-muted-foreground"
                                    >
                                        Opcional, pero recomendado.
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label>Sesión relacionada</Label>

                                    <div
                                        :class="{
                                            'pointer-events-none opacity-50':
                                                hasUploadPatient && !fromExpediente,
                                        }"
                                    >
                                        <SearchableSelect
                                            :model-value="uploadForm.session_id"
                                            :options="[
                                                {
                                                    value: '',
                                                    label: 'Sin sesión específica',
                                                },
                                                ...props.lookups.sessions.map(
                                                    (s) => ({
                                                        value: s.id,
                                                        label: s.label,
                                                    }),
                                                ),
                                            ]"
                                            @update:model-value="
                                                setUploadSession
                                            "
                                        />
                                    </div>

                                    <p
                                        v-if="sessionPatientName"
                                        class="text-xs text-sky-600"
                                    >
                                        Paciente de esta sesión: {{ sessionPatientName }}
                                    </p>

                                    <p
                                        v-else-if="hasUploadPatient && !fromExpediente"
                                        class="text-xs text-amber-600"
                                    >
                                        Desactiva el paciente para elegir
                                        sesión.
                                    </p>

                                    <p v-else class="text-xs text-muted-foreground">
                                        Si eliges sesión, el paciente queda
                                        implícito.
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label>Tipo de archivo</Label>

                                    <SearchableSelect
                                        v-model="uploadForm.file_type"
                                        :options="[
                                            {
                                                value: '',
                                                label: 'Seleccionar tipo',
                                            },
                                            ...props.lookups.fileTypes,
                                        ]"
                                    />

                                    <p
                                        v-if="uploadForm.errors.file_type"
                                        class="text-xs font-medium text-red-600"
                                    >
                                        {{ uploadForm.errors.file_type }}
                                    </p>

                                    <p v-else class="text-xs text-muted-foreground">
                                        Ayuda a organizar el expediente.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="uploadForm.errors.files"
                            class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                        >
                            {{ uploadForm.errors.files }}
                        </div>

                        <div
                            v-if="selectedFiles.length"
                            class="rounded-[1.75rem] border border-border bg-card p-4 shadow-sm"
                        >
                            <div
                                class="mb-4 flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        Listos para subir
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Peso total:
                                        {{ fmtSize(selectedFilesTotalSize) }}
                                    </p>
                                </div>

                                <Button
                                    type="button"
                                    class="rounded-2xl px-5"
                                    :disabled="
                                        uploadForm.processing ||
                                        !selectedFiles.length
                                    "
                                    style="
                                        background: var(--primary);
                                        color: var(--primary-foreground);
                                    "
                                    @click="submitUpload"
                                >
                                    <Loader2
                                        v-if="uploadForm.processing"
                                        class="mr-2 h-4 w-4 animate-spin"
                                    />
                                    <UploadCloud v-else class="mr-2 h-4 w-4" />

                                    {{
                                        uploadForm.processing
                                            ? 'Subiendo...'
                                            : 'Subir'
                                    }}
                                </Button>
                            </div>

                            <div
                                v-if="uploadForm.processing"
                                class="mb-4 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-2 rounded-full transition-all"
                                    :style="{
                                        width: `${uploadProgress}%`,
                                        background: 'var(--primary)',
                                    }"
                                />
                            </div>

                            <div
                                class="max-h-[260px] space-y-2 overflow-y-auto pr-1"
                            >
                                <div
                                    v-for="(file, index) in selectedFiles"
                                    :key="`${file.name}-${file.size}-${index}`"
                                    class="flex items-center gap-3 rounded-2xl border border-border bg-muted p-3"
                                >
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl ring-1 ring-border"
                                        style="
                                            background: color-mix(
                                                in srgb,
                                                var(--primary) 10%,
                                                white
                                            );
                                            color: var(--primary);
                                        "
                                    >
                                        <File class="h-5 w-5" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-semibold text-foreground"
                                        >
                                            {{ file.name }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ fmtSize(file.size) }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="rounded-xl p-2 text-muted-foreground transition hover:bg-red-50 hover:text-red-600"
                                        :disabled="uploadForm.processing"
                                        @click="removeSelectedFile(index)"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTERS -->
            <div
                class="rounded-[1.75rem] border border-border bg-card p-4 shadow-sm"
            >
                <div
                    class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h2 class="text-base font-semibold text-foreground">
                            Biblioteca de archivos
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Busca, filtra y administra los documentos cargados.
                        </p>
                    </div>

                    <div
                        class="flex w-fit items-center gap-2 rounded-full bg-muted px-3 py-1 text-xs font-semibold text-muted-foreground"
                    >
                        <Filter class="h-3.5 w-3.5" />
                        Filtros
                    </div>
                </div>

                <div
                    class="grid gap-3 xl:grid-cols-[minmax(280px,1fr)_240px_280px]"
                >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-muted-foreground"
                        />

                        <Input
                            v-model="search"
                            class="h-11 rounded-2xl border-border bg-muted pr-10 pl-9 shadow-sm focus-visible:border-[color:var(--primary)] focus-visible:ring-2 focus-visible:ring-[color:var(--primary)]/20"
                            placeholder="Buscar archivo, paciente o usuario..."
                        />

                        <button
                            v-if="search"
                            type="button"
                            class="absolute top-3 right-3 grid h-5 w-5 place-items-center rounded-full text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            @click="clearSearch"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <SearchableSelect
                        :model-value="props.filters.file_type ?? ''"
                        :options="[
                            { value: '', label: 'Todos los tipos' },
                            ...props.lookups.fileTypes,
                        ]"
                        @update:model-value="
                            applyFilters({
                                file_type: $event ?? '',
                                page: 1,
                            })
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
                            applyFilters({
                                patient_persona_id: $event ?? '',
                                page: 1,
                            })
                        "
                    />
                </div>
            </div>

            <!-- EMPTY STATE -->
            <div
                v-if="props.rows.length === 0"
                class="rounded-[2rem] border border-dashed border-border bg-muted p-10 text-center"
            >
                <FolderOpen class="mx-auto h-10 w-10 text-muted-foreground" />

                <h3 class="mt-4 text-lg font-semibold text-foreground">
                    Sin archivos para mostrar
                </h3>

                <p class="mt-2 text-sm text-muted-foreground">
                    Sube tu primer archivo o ajusta los filtros.
                </p>
            </div>

            <!-- FILE GRID -->
            <div v-else class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                <article
                    v-for="row in props.rows"
                    :key="row.id"
                    class="group flex min-h-[320px] flex-col rounded-[1.75rem] border border-border bg-card p-4 shadow-sm transition hover:-translate-y-1 hover:border-[color:var(--primary)] hover:shadow-xl"
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
                                class="line-clamp-2 text-left text-base font-semibold text-foreground transition hover:text-[color:var(--primary)]"
                                @click="openPreview(row)"
                            >
                                {{ row.original_name }}
                            </button>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-muted-foreground"
                                >
                                    {{ getExtension(row) }}
                                </span>

                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    style="
                                        background: color-mix(
                                            in srgb,
                                            var(--primary) 10%,
                                            white
                                        );
                                        color: var(--primary);
                                    "
                                >
                                    {{ getFileTypeLabel(row) }}
                                </span>

                                <span
                                    class="rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground"
                                >
                                    {{ fmtSize(row.size_bytes) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 text-sm">
                        <div
                            class="flex items-center gap-2 rounded-2xl bg-muted px-3 py-2"
                        >
                            <UserRound class="h-4 w-4 shrink-0 text-muted-foreground" />
                            <span class="truncate text-muted-foreground">
                                {{
                                    row.patient_name ||
                                    'Sin paciente relacionado'
                                }}
                            </span>
                        </div>

                        <div
                            class="flex items-center gap-2 rounded-2xl bg-muted px-3 py-2"
                        >
                            <CalendarDays
                                class="h-4 w-4 shrink-0 text-muted-foreground"
                            />
                            <span class="truncate text-muted-foreground">
                                {{
                                    row.session_id
                                        ? `Sesión #${row.session_id}${row.session_date ? ` · ${row.session_date}` : ''}`
                                        : 'Sin sesión relacionada'
                                }}
                            </span>
                        </div>

                        <div
                            class="flex items-center gap-2 rounded-2xl bg-muted px-3 py-2"
                        >
                            <UploadCloud
                                class="h-4 w-4 shrink-0 text-muted-foreground"
                            />
                            <span class="truncate text-muted-foreground">
                                Subido por
                                {{
                                    row.uploaded_by_name ||
                                    'usuario no registrado'
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-auto pt-4">
                        <div class="border-t border-border pt-4">
                            <p class="mb-3 text-xs text-muted-foreground">
                                {{
                                    row.created_at
                                        ? formatDateTimeMx(row.created_at)
                                        : 'Sin fecha'
                                }}
                            </p>

                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-10 rounded-xl"
                                    @click="openPreview(row)"
                                >
                                    <Eye class="mr-2 h-4 w-4" />
                                    Ver
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-10 rounded-xl"
                                    @click="openInNewTab(row)"
                                >
                                    <ExternalLink class="mr-2 h-4 w-4" />
                                    Abrir
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-10 rounded-xl"
                                    @click="downloadFile(row)"
                                >
                                    <Download class="mr-2 h-4 w-4" />
                                    Descargar
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-10 rounded-xl"
                                    @click="openEdit(row)"
                                >
                                    <Edit3 class="mr-2 h-4 w-4" />
                                    Editar
                                </Button>

                                <Button
                                    type="button"
                                    variant="destructive"
                                    class="col-span-2 h-10 rounded-xl sm:col-span-2"
                                    @click="destroyArchivo(row)"
                                >
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Eliminar
                                </Button>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <FvPagination
                :page="props.page"
                item-label="archivos"
                :per-page-options="[10, 15, 20, 50, 'all']"
                @change="
                    (page) =>
                        applyFilters({
                            q: search.trim(),
                            page,
                            per_page: perPageSelected,
                        })
                "
                @per-page-change="
                    (perPage) =>
                        applyFilters({
                            q: search.trim(),
                            page: 1,
                            per_page: perPage,
                        })
                "
            />
        </section>

        <!-- PREVIEW MODAL -->
        <div
            v-if="selectedPreview"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3 backdrop-blur-sm sm:p-4"
        >
            <div
                class="flex h-[94vh] w-full max-w-7xl flex-col overflow-hidden rounded-[2rem] bg-card shadow-2xl"
            >
                <div
                    class="flex shrink-0 flex-col gap-3 border-b border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                >
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-foreground">
                            Vista previa
                        </h2>

                        <p class="truncate text-sm text-muted-foreground">
                            {{ selectedPreview.original_name }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="rounded-2xl"
                            @click="openInNewTab(selectedPreview)"
                        >
                            <ExternalLink class="mr-2 h-4 w-4" />
                            Abrir
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            class="rounded-2xl"
                            @click="downloadFile(selectedPreview)"
                        >
                            <Download class="mr-2 h-4 w-4" />
                            Descargar
                        </Button>

                        <button
                            type="button"
                            class="rounded-2xl p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            @click="closePreview"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 bg-muted p-3 sm:p-4">
                    <iframe
                        v-if="selectedPreview.is_pdf"
                        :src="
                            selectedPreview.preview_url ??
                            selectedPreview.url ??
                            ''
                        "
                        class="h-full w-full rounded-2xl border border-border bg-card"
                    />

                    <div
                        v-else-if="selectedPreview.is_image"
                        class="flex h-full items-center justify-center"
                    >
                        <img
                            :src="
                                selectedPreview.preview_url ??
                                selectedPreview.url ??
                                ''
                            "
                            :alt="selectedPreview.original_name"
                            class="max-h-full max-w-full rounded-2xl object-contain shadow-sm"
                        />
                    </div>

                    <div
                        v-else
                        class="flex h-full items-center justify-center rounded-2xl border border-dashed border-border bg-card text-center"
                    >
                        <div>
                            <File class="mx-auto h-12 w-12 text-muted-foreground" />

                            <p class="mt-3 text-sm font-semibold text-foreground">
                                Este formato no se puede previsualizar aquí.
                            </p>

                            <p class="mt-1 text-sm text-muted-foreground">
                                Puedes abrirlo en otra pestaña o descargarlo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- EDIT MODAL -->
        <div
            v-if="selectedEdit"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-3 backdrop-blur-sm sm:p-4"
        >
            <div
                class="flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-[2rem] bg-card shadow-2xl"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-border px-5 py-4"
                >
                    <div class="flex min-w-0 gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                            style="
                                background: color-mix(
                                    in srgb,
                                    var(--primary) 10%,
                                    white
                                );
                                color: var(--primary);
                            "
                        >
                            <Edit3 class="h-5 w-5" />
                        </div>

                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-foreground">
                                Editar archivo
                            </h2>

                            <p class="mt-1 text-sm text-muted-foreground">
                                Cambia la clasificación, relación o reemplaza el
                                documento.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="rounded-2xl p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                        @click="closeEdit"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
                    <div
                        class="mb-5 rounded-2xl border border-border bg-muted p-4"
                    >
                        <p
                            class="text-xs font-semibold text-muted-foreground uppercase"
                        >
                            Archivo actual
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold break-words text-foreground"
                        >
                            {{ selectedEdit.original_name }}
                        </p>

                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ fmtSize(selectedEdit.size_bytes) }}
                        </p>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div class="space-y-2 lg:col-span-2">
                            <Label>Reemplazar archivo</Label>

                            <label
                                for="replacement-file"
                                class="flex cursor-pointer flex-col gap-3 rounded-2xl border border-dashed border-border bg-muted px-4 py-4 transition hover:border-[color:var(--primary)] sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl"
                                        style="
                                            background: color-mix(
                                                in srgb,
                                                var(--primary) 10%,
                                                white
                                            );
                                            color: var(--primary);
                                        "
                                    >
                                        <UploadCloud class="h-5 w-5" />
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-semibold text-foreground"
                                        >
                                            {{
                                                replacementFile
                                                    ? replacementFile.name
                                                    : 'Seleccionar nuevo archivo'
                                            }}
                                        </p>

                                        <p class="text-xs text-muted-foreground">
                                            Opcional. Si no eliges archivo, solo
                                            se guardan los datos.
                                        </p>
                                    </div>
                                </div>

                                <span class="text-xs text-muted-foreground">
                                    {{
                                        replacementFile
                                            ? fmtSize(replacementFile.size)
                                            : 'Máx. 50 MB'
                                    }}
                                </span>
                            </label>

                            <input
                                id="replacement-file"
                                type="file"
                                class="hidden"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.svg,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip"
                                @change="onReplacementChange"
                            />
                        </div>

                        <div class="space-y-2 lg:col-span-2">
                            <Label>Tipo de archivo</Label>

                            <SearchableSelect
                                v-model="editForm.file_type"
                                :options="[
                                    { value: '', label: 'Seleccionar tipo' },
                                    ...props.lookups.fileTypes,
                                ]"
                            />

                            <p
                                v-if="editForm.errors.file_type"
                                class="text-xs font-medium text-red-600"
                            >
                                {{ editForm.errors.file_type }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Paciente relacionado</Label>

                            <div
                                :class="{
                                    'pointer-events-none opacity-50':
                                        hasEditSession,
                                }"
                            >
                                <SearchableSelect
                                    :model-value="editForm.patient_persona_id"
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
                                    @update:model-value="setEditPatient"
                                />
                            </div>

                            <p
                                v-if="hasEditSession"
                                class="text-xs text-amber-600"
                            >
                                Desactiva la sesión para elegir paciente.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Sesión relacionada</Label>

                            <div
                                :class="{
                                    'pointer-events-none opacity-50':
                                        hasEditPatient,
                                }"
                            >
                                <SearchableSelect
                                    :model-value="editForm.session_id"
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
                                    @update:model-value="setEditSession"
                                />
                            </div>

                            <p
                                v-if="hasEditPatient"
                                class="text-xs text-amber-600"
                            >
                                Desactiva el paciente para elegir sesión.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex shrink-0 flex-col-reverse gap-2 border-t border-border px-5 py-4 sm:flex-row sm:justify-end"
                >
                    <Button
                        type="button"
                        variant="outline"
                        class="rounded-2xl"
                        :disabled="editForm.processing"
                        @click="closeEdit"
                    >
                        Cancelar
                    </Button>

                    <Button
                        type="button"
                        class="rounded-2xl"
                        :disabled="editForm.processing"
                        style="
                            background: var(--primary);
                            color: var(--primary-foreground);
                        "
                        @click="updateArchivo"
                    >
                        <Loader2
                            v-if="editForm.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />

                        <CheckCircle2 v-else class="mr-2 h-4 w-4" />

                        {{
                            editForm.processing
                                ? 'Guardando...'
                                : 'Guardar cambios'
                        }}
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
