import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import {
    swalClose,
    swalConfirm,
    swalErr,
    swalProgress,
    swalToast,
} from '@/lib/swal';

export type CitaStatus =
    | 'scheduled'
    | 'confirmed'
    | 'arrived'
    | 'no_show'
    | 'cancelled'
    | 'done';

export type CitaRow = {
    id: number;
    patient_persona_id: number;
    therapist_user_id: number;
    start_at: string;
    end_at: string;
    status: CitaStatus;
    notes?: string | null;
    patient_name: string;
    therapist_name: string;
};

const pad = (n: number) => String(n).padStart(2, '0');
const toIsoLocal = (d: Date) =>
    `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;

export const useCitaCrud = (filters: {
    q?: string;
    status?: string;
    per_page?: string | number;
}) => {
    const page = usePage();
    const isOpen = ref(false);
    const editingId = ref<number | null>(null);
    const editingStatus = ref<CitaStatus>('scheduled');

    const form = useForm({
        patient_persona_id: '' as number | '',
        therapist_user_id: '' as number | '',
        start_at: '',
        end_at: '',
        status: 'scheduled' as CitaStatus,
        notes: '',
    });

    const permissions = computed<string[]>(
        () => ((page.props as any).auth?.permissions ?? []) as string[],
    );

    const isSuperAdmin = computed<boolean>(() =>
        Boolean((page.props as any).auth?.user?.is_super_admin),
    );

    const enabledModules = computed<Record<string, boolean>>(
        () =>
            ((page.props as any).enabledModules ?? {}) as Record<
                string,
                boolean
            >,
    );

    const appSettings = computed<Record<string, any>>(
        () => ((page.props as any).appSettings ?? {}) as Record<string, any>,
    );

    const defaultDuration = computed(() =>
        Math.max(5, Number(appSettings.value.appointment_default_duration ?? 60)),
    );

    const authRoles = computed<{ id: number; name: string; slug: string }[]>(
        () => ((page.props as any).auth?.roles ?? []) as { id: number; name: string; slug: string }[],
    );

    const currentUserId = computed<number | null>(
        () => ((page.props as any).auth?.user?.id ?? null) as number | null,
    );

    const isTherapistRole = computed(() =>
        !isSuperAdmin.value &&
        authRoles.value.some((r) =>
            ['terapeuta', 'therapist', 'fisioterapeuta'].includes(
                (r.slug ?? '').toLowerCase(),
            ),
        ),
    );

    const can = (permission: string) =>
        isSuperAdmin.value || permissions.value.includes(permission);

    const moduleEnabled = computed(() => enabledModules.value.agenda !== false);

    const isEditing = computed(() => editingId.value !== null);

    // Auto-calcular end_at cuando start_at cambia y end_at está vacío
    watch(
        () => form.start_at,
        (newStart) => {
            if (!newStart || form.end_at) return;
            const start = new Date(newStart);
            if (Number.isNaN(start.getTime())) return;
            form.end_at = toIsoLocal(
                new Date(start.getTime() + defaultDuration.value * 60 * 1000),
            );
        },
    );

    const applyFilters = (
        extra: Record<string, string | number | null | undefined>,
    ) => {
        router.get(
            '/citas',
            { ...filters, ...extra },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            },
        );
    };

    const validateBeforeSubmit = () => {
        form.clearErrors();

        const errors: Record<string, string> = {};

        if (!form.patient_persona_id) {
            errors.patient_persona_id = 'Selecciona un paciente.';
        }

        if (!form.therapist_user_id) {
            errors.therapist_user_id = 'Selecciona un terapeuta.';
        }

        if (!form.start_at) {
            errors.start_at = 'Selecciona la fecha y hora de inicio.';
        }

        if (!form.end_at) {
            errors.end_at = 'Selecciona la fecha y hora de fin.';
        }

        if (form.start_at && form.end_at) {
            const start = new Date(form.start_at).getTime();
            const end = new Date(form.end_at).getTime();

            if (!Number.isNaN(start) && !Number.isNaN(end) && end <= start) {
                errors.end_at = 'La hora de fin debe ser posterior al inicio.';
            }
        }

        if (Object.keys(errors).length > 0) {
            form.setError(errors);
            swalToast('Revisa los campos obligatorios', 'warning');
            return false;
        }

        return true;
    };

    const openCreate = (prefill?: { patient_persona_id?: number | '' }) => {
        editingId.value = null;
        editingStatus.value = 'scheduled';

        form.reset();
        form.clearErrors();

        form.patient_persona_id = prefill?.patient_persona_id ?? '';
        form.therapist_user_id = '';
        form.start_at = '';
        form.end_at = '';
        form.status = 'scheduled';
        form.notes = '';

        // Pre-llenar terapeuta si el usuario tiene rol terapeuta
        if (isTherapistRole.value && currentUserId.value) {
            form.therapist_user_id = currentUserId.value;
        }

        isOpen.value = true;
    };

    const openEdit = (row: CitaRow) => {
        editingId.value = row.id;
        editingStatus.value = row.status;

        form.clearErrors();

        form.patient_persona_id = row.patient_persona_id;
        form.therapist_user_id = row.therapist_user_id;
        form.start_at = row.start_at?.slice(0, 16) ?? '';
        form.end_at = row.end_at?.slice(0, 16) ?? '';
        form.status = row.status;
        form.notes = row.notes ?? '';

        isOpen.value = true;
    };

    const closeModal = () => {
        isOpen.value = false;
        form.clearErrors();
    };

    const submit = async () => {
        if (!validateBeforeSubmit()) return;

        const ok = await swalConfirm(
            editingId.value ? '¿Actualizar cita?' : '¿Crear cita?',
            editingId.value
                ? 'Se actualizarán los datos de la cita sin retroceder su estado.'
                : 'La cita se registrará como programada y se enviará correo al paciente y terapeuta.',
            editingId.value ? 'Sí, actualizar' : 'Sí, crear',
        );

        if (!ok) return;

        const isUpdate = Boolean(editingId.value);

        if (isUpdate && editingId.value) {
            swalProgress(
                'Actualizando cita...',
                'Estamos guardando la información de la cita.',
            );

            form.status = editingStatus.value;

            form.put(`/citas/${editingId.value}`, {
                preserveScroll: true,
                onSuccess: () => {
                    swalClose();
                    swalToast('Cita actualizada', 'success');
                    closeModal();
                },
                onError: () => {
                    swalClose();
                    swalErr(
                        'No se pudo guardar',
                        'Verifica fecha, terapeuta y paciente.',
                    );
                },
                onFinish: () => {
                    if (form.hasErrors) swalClose();
                },
            });

            return;
        }

        swalProgress(
            'Creando cita...',
            'Estamos registrando la cita y enviando las notificaciones por correo.',
        );

        form.status = 'scheduled';

        form.post('/citas', {
            preserveScroll: true,
            onSuccess: () => {
                swalClose();
                swalToast('Cita creada y notificación enviada', 'success');
                closeModal();
            },
            onError: () => {
                swalClose();
                swalErr(
                    'No se pudo guardar',
                    'Verifica fecha, terapeuta y paciente.',
                );
            },
            onFinish: () => {
                if (form.hasErrors) swalClose();
            },
        });
    };

    const cancelCita = async (row: CitaRow) => {
        if (['cancelled', 'done', 'no_show'].includes(row.status)) return;

        const ok = await swalConfirm(
            '¿Cancelar cita?',
            `${row.patient_name} con ${row.therapist_name}`,
            'Sí, cancelar',
        );

        if (!ok) return;

        swalProgress('Cancelando cita...', 'Actualizando el estado de la cita.');

        router.patch(
            `/citas/${row.id}/cancelar`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    swalClose();
                    swalToast('Cita cancelada', 'success');
                },
                onError: () => {
                    swalClose();
                    swalErr('No se pudo cancelar la cita');
                },
            },
        );
    };

    const markNoShow = async (row: CitaRow) => {
        if (['cancelled', 'done', 'no_show'].includes(row.status)) return;

        const ok = await swalConfirm(
            '¿Marcar como no asistió?',
            row.patient_name,
            'Sí, marcar',
        );

        if (!ok) return;

        swalProgress('Actualizando cita...', 'Marcando la cita como no asistida.');

        router.patch(
            `/citas/${row.id}/no-show`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    swalClose();
                    swalToast('Cita marcada como no asistida', 'success');
                },
                onError: () => {
                    swalClose();
                    swalErr('No se pudo actualizar la cita');
                },
            },
        );
    };

    const advanceStatus = async (row: CitaRow) => {
        const next =
            row.status === 'scheduled'
                ? 'confirmed'
                : row.status === 'confirmed'
                  ? 'arrived'
                  : row.status === 'arrived'
                    ? 'done'
                    : null;

        if (!next) return;

        const labels: Record<CitaStatus, string> = {
            scheduled: 'Programada',
            confirmed: 'Confirmada',
            arrived: 'Llegó',
            no_show: 'No asistió',
            cancelled: 'Cancelada',
            done: 'Finalizada',
        };

        const ok = await swalConfirm(
            '¿Avanzar estado de la cita?',
            `${row.patient_name}: ${labels[row.status]} → ${labels[next]}`,
            'Sí, avanzar',
        );

        if (!ok) return;

        swalProgress('Actualizando estado...', 'Avanzando el estado de la cita.');

        router.patch(
            `/citas/${row.id}/avanzar`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    swalClose();
                    swalToast('Estado de cita actualizado', 'success');
                },
                onError: () => {
                    swalClose();
                    swalErr('No se pudo actualizar la cita');
                },
            },
        );
    };

    const destroyCita = async (row: CitaRow) => {
        if (['cancelled', 'done'].includes(row.status)) return;

        const ok = await swalConfirm(
            '¿Cancelar cita?',
            'La cita no se borrará físicamente. Solo cambiará a estado cancelada.',
            'Sí, cancelar',
        );

        if (!ok) return;

        swalProgress('Cancelando cita...', 'Actualizando el estado de la cita.');

        router.patch(
            `/citas/${row.id}/cancelar`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    swalClose();
                    swalToast('Cita cancelada correctamente', 'success');
                },
                onError: () => {
                    swalClose();
                    swalErr('No se pudo cancelar la cita');
                },
            },
        );
    };

    return {
        form,
        isOpen,
        editingId,
        editingStatus,
        isEditing,
        can,
        moduleEnabled,
        isTherapistRole,
        applyFilters,
        openCreate,
        openEdit,
        closeModal,
        submit,
        cancelCita,
        markNoShow,
        advanceStatus,
        destroyCita,
    };
};
