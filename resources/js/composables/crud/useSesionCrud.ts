import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { swalConfirm, swalErr, swalToast } from '@/lib/swal';

export type SesionRow = {
    id: number;
    appointment_id?: number | null;
    patient_persona_id: number;
    therapist_user_id: number;
    session_date: string;
    pain_scale?: number | null;
    subjective?: string | null;
    objective?: string | null;
    assessment?: string | null;
    plan?: string | null;
    notes?: string | null;
    patient_name: string;
    therapist_name: string;
};

export const useSesionCrud = (filters: {
    q?: string;
    per_page?: string | number;
}) => {
    const page = usePage();
    const isOpen = ref(false);
    const editingId = ref<number | null>(null);

    const form = useForm({
        appointment_id: '' as number | '',
        patient_persona_id: '' as number | '',
        therapist_user_id: '' as number | '',
        session_date: '',
        subjective: '',
        objective: '',
        assessment: '',
        plan: '',
        pain_scale: '' as number | '',
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

    const can = (permission: string) =>
        isSuperAdmin.value || permissions.value.includes(permission);

    const moduleEnabled = computed(
        () => enabledModules.value.sesiones !== false,
    );

    const isEditing = computed(() => editingId.value !== null);

    const applyFilters = (
        extra: Record<string, string | number | null | undefined>,
    ) => {
        router.get(
            '/sesiones',
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

        if (!form.session_date) {
            errors.session_date = 'Selecciona la fecha de la sesión.';
        }

        if (
            form.pain_scale !== '' &&
            (Number(form.pain_scale) < 0 || Number(form.pain_scale) > 10)
        ) {
            errors.pain_scale = 'La escala de dolor debe estar entre 0 y 10.';
        }

        if (Object.keys(errors).length > 0) {
            form.setError(errors);
            swalToast('Revisa los campos obligatorios', 'warning');
            return false;
        }

        return true;
    };

    const openCreate = () => {
        editingId.value = null;

        form.reset();
        form.clearErrors();

        form.appointment_id = '';
        form.patient_persona_id = '';
        form.therapist_user_id = '';
        form.session_date = '';
        form.subjective = '';
        form.objective = '';
        form.assessment = '';
        form.plan = '';
        form.pain_scale = '';
        form.notes = '';

        isOpen.value = true;
    };

    const openEdit = (row: SesionRow) => {
        editingId.value = row.id;

        form.clearErrors();

        form.appointment_id = row.appointment_id ?? '';
        form.patient_persona_id = row.patient_persona_id;
        form.therapist_user_id = row.therapist_user_id;
        form.session_date = row.session_date?.slice(0, 10) ?? '';
        form.subjective = row.subjective ?? '';
        form.objective = row.objective ?? '';
        form.assessment = row.assessment ?? '';
        form.plan = row.plan ?? '';
        form.pain_scale = (row.pain_scale ?? '') as number | '';
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
            editingId.value ? '¿Actualizar sesión?' : '¿Crear sesión?',
            'Se guardará la información clínica de la sesión.',
            editingId.value ? 'Sí, actualizar' : 'Sí, crear',
        );

        if (!ok) return;

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                swalToast(
                    editingId.value ? 'Sesión actualizada' : 'Sesión creada',
                    'success',
                );
                closeModal();
            },
            onError: () =>
                swalErr(
                    'No se pudo guardar',
                    'Revisa paciente, terapeuta, fecha y escala de dolor.',
                ),
        };

        if (editingId.value) {
            form.put(`/sesiones/${editingId.value}`, options);
            return;
        }

        form.post('/sesiones', options);
    };

    const destroySesion = async (row: SesionRow) => {
        const ok = await swalConfirm(
            '¿Eliminar sesión?',
            `${row.patient_name} · ${row.session_date}`,
            'Sí, eliminar',
        );

        if (!ok) return;

        router.delete(`/sesiones/${row.id}`, {
            preserveScroll: true,
            onSuccess: () => swalToast('Sesión eliminada', 'success'),
            onError: () => swalErr('No se pudo eliminar la sesión'),
        });
    };

    const goToPatient = (patientPersonaId: number) => {
        router.visit(`/pacientes/${patientPersonaId}`);
    };

    const goToExercises = () => {
        router.visit('/ejercicios');
    };

    return {
        form,
        isOpen,
        editingId,
        isEditing,
        can,
        moduleEnabled,
        applyFilters,
        openCreate,
        openEdit,
        closeModal,
        submit,
        destroySesion,
        goToPatient,
        goToExercises,
    };
};
