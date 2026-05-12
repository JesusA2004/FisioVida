import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { swalConfirm, swalErr, swalToast } from '@/lib/swal';

export type PacienteRow = {
    id: number;
    nombres: string;
    apellido_paterno?: string | null;
    apellido_materno?: string | null;
    full_name: string;
    tipo: 'paciente' | 'ambos' | 'staff';
    status: 'active' | 'inactive';
    telefono?: string | null;
    email?: string | null;
    sexo?: 'M' | 'F' | 'X' | null;
    fecha_nacimiento?: string | null;
    direccion?: string | null;
    contacto_emergencia_nombre?: string | null;
    contacto_emergencia_telefono?: string | null;
    notas?: string | null;
};

export type PacienteFormData = {
    status: 'active' | 'inactive';
    nombres: string;
    apellido_paterno: string;
    apellido_materno: string;
    fecha_nacimiento: string;
    sexo: '' | 'M' | 'F' | 'X';
    telefono: string;
    email: string;
    direccion: string;
    contacto_emergencia_nombre: string;
    contacto_emergencia_telefono: string;
    notas: string;
};

const onlyDigits = (value: string) => value.replace(/\D+/g, '').slice(0, 10);

const isValidEmail = (value: string) => {
    if (!value.trim()) return true;

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
};

export const usePacienteCrud = (filters: { q?: string; status?: string }) => {
    const page = usePage();
    const isOpen = ref(false);
    const editingId = ref<number | null>(null);
    const isSubmitting = ref(false);
    const deletingId = ref<number | null>(null);
    const activatingId = ref<number | null>(null);

    const form = useForm<PacienteFormData>({
        status: 'active',
        nombres: '',
        apellido_paterno: '',
        apellido_materno: '',
        fecha_nacimiento: '',
        sexo: '',
        telefono: '',
        email: '',
        direccion: '',
        contacto_emergencia_nombre: '',
        contacto_emergencia_telefono: '',
        notas: '',
    });

    const permissions = computed<string[]>(
        () => ((page.props as any).auth?.permissions ?? []) as string[],
    );

    const isSuperAdmin = computed<boolean>(() =>
        Boolean(
            (page.props as any).auth?.is_super_admin ??
                (page.props as any).auth?.user?.is_super_admin,
        ),
    );

    const enabledModules = computed<Record<string, boolean>>(
        () =>
            ((page.props as any).enabledModules ?? {}) as Record<
                string,
                boolean
            >,
    );

    const can = (permission: string) =>
        isSuperAdmin.value || permissions.value.includes(permission);

    const moduleEnabled = computed(() => enabledModules.value.pacientes !== false);

    const applyFilters = (extra: Record<string, string | number | null>) => {
        const payload = {
            ...filters,
            ...extra,
        };

        Object.keys(payload).forEach((key) => {
            const value = payload[key as keyof typeof payload];

            if (value === '' || value === null || value === undefined) {
                delete payload[key as keyof typeof payload];
            }
        });

        router.get('/pacientes', payload, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        });
    };

    const normalizePhones = () => {
        form.telefono = onlyDigits(form.telefono ?? '');
        form.contacto_emergencia_telefono = onlyDigits(
            form.contacto_emergencia_telefono ?? '',
        );
    };

    const validateClient = () => {
        form.clearErrors();
        normalizePhones();

        let hasError = false;

        if (!form.nombres.trim()) {
            form.setError('nombres', 'El nombre del paciente es obligatorio.');
            hasError = true;
        }

        if (!form.apellido_paterno.trim()) {
            form.setError(
                'apellido_paterno',
                'El apellido paterno del paciente es obligatorio.',
            );
            hasError = true;
        }

        if (!form.apellido_materno.trim()) {
            form.setError(
                'apellido_materno',
                'El apellido materno del paciente es obligatorio.',
            );
            hasError = true;
        }

        if (!form.telefono.trim()) {
            form.setError('telefono', 'El teléfono del paciente es obligatorio.');
            hasError = true;
        }

        if (form.telefono && form.telefono.length !== 10) {
            form.setError('telefono', 'El teléfono debe tener exactamente 10 dígitos.');
            hasError = true;
        }

        if (
            form.contacto_emergencia_telefono &&
            form.contacto_emergencia_telefono.length !== 10
        ) {
            form.setError(
                'contacto_emergencia_telefono',
                'El teléfono de emergencia debe tener exactamente 10 dígitos.',
            );
            hasError = true;
        }

        if (form.email && !isValidEmail(form.email)) {
            form.setError('email', 'Ingresa un correo electrónico válido.');
            hasError = true;
        }

        if (hasError) {
            swalToast('Revisa los campos marcados', 'error');
            return false;
        }

        return true;
    };

    const resetForm = () => {
        form.reset();
        form.clearErrors();
        form.status = 'active';
        form.sexo = '';
        editingId.value = null;
        isSubmitting.value = false;
    };

    const openCreate = () => {
        resetForm();
        isOpen.value = true;
    };

    const openEdit = (row: PacienteRow) => {
        editingId.value = row.id;
        form.clearErrors();

        form.status = row.status ?? 'active';
        form.nombres = row.nombres ?? '';
        form.apellido_paterno = row.apellido_paterno ?? '';
        form.apellido_materno = row.apellido_materno ?? '';
        form.fecha_nacimiento = row.fecha_nacimiento ?? '';
        form.sexo = (row.sexo ?? '') as '' | 'M' | 'F' | 'X';
        form.telefono = row.telefono ?? '';
        form.email = row.email ?? '';
        form.direccion = row.direccion ?? '';
        form.contacto_emergencia_nombre = row.contacto_emergencia_nombre ?? '';
        form.contacto_emergencia_telefono = row.contacto_emergencia_telefono ?? '';
        form.notas = row.notas ?? '';

        isSubmitting.value = false;
        isOpen.value = true;
    };

    const closeModal = (open?: boolean) => {
        if (open === true) return;

        isOpen.value = false;
        isSubmitting.value = false;
        form.clearErrors();
    };

    const submit = () => {
        if (isSubmitting.value || form.processing) return;

        if (!validateClient()) return;

        isSubmitting.value = true;

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                swalToast(
                    editingId.value
                        ? 'Paciente actualizado correctamente'
                        : 'Paciente creado correctamente',
                    'success',
                );

                closeModal(false);
            },
            onError: () => {
                swalToast('No se pudo guardar. Revisa los campos marcados.', 'error');
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        };

        if (editingId.value) {
            form.put(`/pacientes/${editingId.value}`, options);
            return;
        }

        form.post('/pacientes', options);
    };

    const destroyPaciente = async (row: PacienteRow) => {
        if (deletingId.value || activatingId.value) return;

        const ok = await swalConfirm(
            '¿Desactivar paciente?',
            `El expediente de ${row.full_name} quedará inactivo, pero conservará su historial clínico.`,
            'Sí, desactivar',
        );

        if (!ok) return;

        deletingId.value = row.id;

        router.delete(`/pacientes/${row.id}`, {
            preserveScroll: true,
            onStart: () => {
                swalToast('Desactivando paciente...', 'info');
            },
            onSuccess: () => {
                swalToast('Paciente desactivado correctamente', 'success');
            },
            onError: () => {
                swalErr(
                    'No se pudo desactivar',
                    'El paciente no existe, ya fue eliminado o no tienes permiso para esta acción.',
                );
            },
            onFinish: () => {
                deletingId.value = null;
            },
        });
    };

    const activatePaciente = async (row: PacienteRow) => {
        if (activatingId.value || deletingId.value) return;

        const ok = await swalConfirm(
            '¿Activar paciente?',
            `El expediente de ${row.full_name} volverá a estar activo para atención y seguimiento.`,
            'Sí, activar',
        );

        if (!ok) return;

        activatingId.value = row.id;

        router.patch(
            `/pacientes/${row.id}/activar`,
            {},
            {
                preserveScroll: true,
                onStart: () => {
                    swalToast('Activando paciente...', 'info');
                },
                onSuccess: () => {
                    swalToast('Paciente activado correctamente', 'success');
                },
                onError: () => {
                    swalErr(
                        'No se pudo activar',
                        'El paciente no existe, ya fue eliminado o no tienes permiso para esta acción.',
                    );
                },
                onFinish: () => {
                    activatingId.value = null;
                },
            },
        );
    };

    return {
        form,
        isOpen,
        editingId,
        isSubmitting,
        can,
        deletingId,
        activatingId,
        moduleEnabled,
        applyFilters,
        openCreate,
        openEdit,
        closeModal,
        submit,
        destroyPaciente,
        activatePaciente,
        onlyDigits,
    };
};