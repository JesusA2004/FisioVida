import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

export type ArchivoRow = {
    id: number;
    patient_persona_id?: number | null;
    patient_name?: string | null;
    session_id?: number | null;
    session_date?: string | null;
    uploaded_by?: number | null;
    uploaded_by_name?: string | null;
    disk?: string | null;
    path?: string | null;
    original_name: string;
    file_type?: string | null;
    file_type_label?: string | null;
    mime?: string | null;
    extension?: string | null;
    size_bytes?: number | null;
    created_at?: string | null;
    updated_at?: string | null;
    url?: string | null;
    is_image?: boolean;
    is_pdf?: boolean;
    can_preview?: boolean;
};

export type ArchivoFilters = {
    q?: string;
    file_type?: string;
    patient_persona_id?: string | number;
    session_id?: string | number;
    per_page?: number | 'all' | string;
};

export type ArchivoPage = {
    current_page: number;
    last_page: number;
    per_page?: number | 'all' | string;
    total: number;
};

export type ArchivoLookup = {
    patients: { id: number; label: string }[];
    sessions: { id: number; label: string }[];
    fileTypes?: { value: string; label: string }[];
};

type UseArchivoCrudProps = {
    rows: ArchivoRow[];
    page: ArchivoPage;
    filters: ArchivoFilters;
    lookups: ArchivoLookup;
};

const baseUrl = '/archivos';

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    timer: 2800,
    timerProgressBar: true,
    showConfirmButton: false,
});

export function useArchivoCrud(props: UseArchivoCrudProps) {
    const isDragging = ref(false);
    const uploadProgress = ref(0);
    const selectedFiles = ref<File[]>([]);
    const editingRow = ref<ArchivoRow | null>(null);
    const showEditModal = ref(false);

    const form = useForm({
        patient_persona_id: '' as number | '',
        session_id: '' as number | '',
        file_type: '',
        files: [] as File[],
    });

    const editForm = useForm({
        patient_persona_id: '' as number | '',
        session_id: '' as number | '',
        file_type: '',
    });

    const hasFiles = computed(() => selectedFiles.value.length > 0);

    const totalUploadSize = computed(() => {
        return selectedFiles.value.reduce((sum, file) => sum + file.size, 0);
    });

    const acceptedExtensionsLabel =
        'PDF, imágenes, Word, Excel, PowerPoint, TXT, CSV y archivos comprimidos';

    const allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/svg+xml',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/x-zip-compressed',
    ];

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

    const fileTypeOptions = computed(() => {
        return props.lookups.fileTypes?.length
            ? props.lookups.fileTypes
            : [
                  { value: 'estudio_clinico', label: 'Estudio clínico' },
                  { value: 'consentimiento', label: 'Consentimiento' },
                  { value: 'evidencia', label: 'Evidencia' },
                  { value: 'receta_indicacion', label: 'Receta / indicación' },
                  {
                      value: 'documento_administrativo',
                      label: 'Documento administrativo',
                  },
                  { value: 'imagen_clinica', label: 'Imagen clínica' },
                  { value: 'laboratorio', label: 'Laboratorio' },
                  { value: 'otro', label: 'Otro' },
              ];
    });

    const fmtSize = (bytes?: number | null) => {
        const value = Number(bytes ?? 0);

        if (!value) return '—';

        const kb = value / 1024;

        if (kb < 1024) return `${kb.toFixed(0)} KB`;

        return `${(kb / 1024).toFixed(1)} MB`;
    };

    const getFileExtension = (fileName: string) => {
        return fileName.split('.').pop()?.toLowerCase() ?? '';
    };

    const isAllowedFile = (file: File) => {
        const ext = getFileExtension(file.name);

        return allowedMimeTypes.includes(file.type) || allowedExtensions.includes(ext);
    };

    const addFiles = async (files: FileList | File[]) => {
        const incomingFiles = Array.from(files);

        if (!incomingFiles.length) return;

        const validFiles = incomingFiles.filter(isAllowedFile);
        const invalidFiles = incomingFiles.filter((file) => !isAllowedFile(file));

        if (invalidFiles.length) {
            await Swal.fire({
                icon: 'warning',
                title: 'Algunos archivos no se agregaron',
                html: `
                    <div style="text-align:left">
                        <p>Solo se permiten:</p>
                        <p><strong>${acceptedExtensionsLabel}</strong></p>
                        <br>
                        <p>Archivos rechazados:</p>
                        <ul>
                            ${invalidFiles.map((file) => `<li>${file.name}</li>`).join('')}
                        </ul>
                    </div>
                `,
                confirmButtonText: 'Entendido',
            });
        }

        const currentNames = new Set(
            selectedFiles.value.map((file) => `${file.name}-${file.size}`),
        );

        const newFiles = validFiles.filter(
            (file) => !currentNames.has(`${file.name}-${file.size}`),
        );

        selectedFiles.value = [...selectedFiles.value, ...newFiles];
        form.files = selectedFiles.value;
    };

    const removeSelectedFile = (index: number) => {
        selectedFiles.value.splice(index, 1);
        form.files = selectedFiles.value;
    };

    const clearSelectedFiles = () => {
        selectedFiles.value = [];
        form.files = [];
        uploadProgress.value = 0;
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

    const onFileInputChange = async (event: Event) => {
        const input = event.target as HTMLInputElement;

        if (!input.files?.length) return;

        await addFiles(input.files);

        input.value = '';
    };

    const goToIndex = (extraFilters: Record<string, unknown> = {}) => {
        router.get(
            baseUrl,
            {
                ...props.filters,
                ...extraFilters,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const applyFilter = (key: keyof ArchivoFilters, value: unknown) => {
        goToIndex({
            [key]: value || undefined,
            page: 1,
        });
    };

    const applySearch = (value: string) => {
        goToIndex({
            q: value || undefined,
            page: 1,
        });
    };

    const goPage = (page: number) => {
        goToIndex({ page });
    };

    const changePerPage = (perPage: number | 'all' | string) => {
        goToIndex({
            per_page: perPage,
            page: 1,
        });
    };

    const validateBeforeUpload = async () => {
        if (!selectedFiles.value.length) {
            await Swal.fire({
                icon: 'warning',
                title: 'Selecciona al menos un archivo',
                text: 'Puedes arrastrarlo al recuadro o usar el botón de subir archivos.',
                confirmButtonText: 'Entendido',
            });

            return false;
        }

        if (!form.file_type) {
            await Swal.fire({
                icon: 'warning',
                title: 'Selecciona el tipo de archivo',
                text: 'Esto ayuda a organizar mejor el expediente del paciente.',
                confirmButtonText: 'Entendido',
            });

            return false;
        }

        return true;
    };

    const uploadFiles = async () => {
        const isValid = await validateBeforeUpload();

        if (!isValid) return;

        uploadProgress.value = 0;
        form.files = selectedFiles.value;

        Swal.fire({
            title: 'Subiendo archivos',
            html: `
                <div style="text-align:left">
                    <p>Por favor espera mientras se cargan los documentos.</p>
                    <div style="margin-top:14px; width:100%; background:#e5e7eb; border-radius:999px; overflow:hidden;">
                        <div id="archivo-upload-progress" style="height:10px; width:0%; background:var(--primary); transition:width .25s ease;"></div>
                    </div>
                    <p id="archivo-upload-progress-text" style="margin-top:8px; font-size:13px; color:#71717a;">0%</p>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        form.post(baseUrl, {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (progress) => {
                const percent = progress?.percentage ?? 0;

                uploadProgress.value = percent;

                const bar = document.getElementById('archivo-upload-progress');
                const text = document.getElementById('archivo-upload-progress-text');

                if (bar) bar.style.width = `${percent}%`;
                if (text) text.innerText = `${percent}%`;
            },
            onSuccess: () => {
                clearSelectedFiles();
                form.reset();

                Swal.close();

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
                    text: 'Revisa que el archivo sea válido y que no supere el tamaño permitido.',
                    confirmButtonText: 'Entendido',
                });
            },
            onFinish: () => {
                uploadProgress.value = 0;
            },
        });
    };

    const openEdit = (row: ArchivoRow) => {
        editingRow.value = row;

        editForm.patient_persona_id = row.patient_persona_id ?? '';
        editForm.session_id = row.session_id ?? '';
        editForm.file_type = row.file_type ?? '';

        showEditModal.value = true;
    };

    const closeEdit = () => {
        showEditModal.value = false;
        editingRow.value = null;
        editForm.reset();
        editForm.clearErrors();
    };

    const updateArchivo = async () => {
        if (!editingRow.value) return;

        if (!editForm.file_type) {
            await Swal.fire({
                icon: 'warning',
                title: 'Selecciona el tipo de archivo',
                text: 'El tipo de archivo es obligatorio para mantener organizado el expediente.',
                confirmButtonText: 'Entendido',
            });

            return;
        }

        Swal.fire({
            title: 'Guardando cambios',
            text: 'Actualizando la información del archivo.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        editForm.put(`${baseUrl}/${editingRow.value.id}`, {
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
                    text: 'Revisa los datos e inténtalo nuevamente.',
                    confirmButtonText: 'Entendido',
                });
            },
        });
    };

    const destroyArchivo = async (row: ArchivoRow) => {
        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Eliminar archivo?',
            html: `
                <div style="text-align:left">
                    <p>Se eliminará este archivo del sistema:</p>
                    <p style="margin-top:8px"><strong>${row.original_name}</strong></p>
                    <p style="margin-top:8px;color:#71717a;font-size:13px">
                        Esta acción también intentará eliminar el documento físico del almacenamiento.
                    </p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
        });

        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Eliminando archivo',
            text: 'Por favor espera un momento.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        router.delete(`${baseUrl}/${row.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.close();

                toast.fire({
                    icon: 'success',
                    title: 'Archivo eliminado',
                });
            },
            onError: () => {
                Swal.close();

                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo eliminar',
                    text: 'Intenta nuevamente o revisa los permisos del usuario.',
                    confirmButtonText: 'Entendido',
                });
            },
        });
    };

    const openArchivo = (row: ArchivoRow) => {
        if (!row.url) {
            toast.fire({
                icon: 'warning',
                title: 'Archivo no disponible',
            });

            return;
        }

        window.open(row.url, '_blank', 'noopener,noreferrer');
    };

    return {
        form,
        editForm,
        selectedFiles,
        hasFiles,
        totalUploadSize,
        uploadProgress,
        isDragging,
        editingRow,
        showEditModal,
        fileTypeOptions,
        acceptedExtensionsLabel,

        fmtSize,
        addFiles,
        removeSelectedFile,
        clearSelectedFiles,
        onDrop,
        onDragOver,
        onDragLeave,
        onFileInputChange,

        applyFilter,
        applySearch,
        goPage,
        changePerPage,

        uploadFiles,
        openEdit,
        closeEdit,
        updateArchivo,
        destroyArchivo,
        openArchivo,
    };
}
