import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { swalClose, swalConfirm, swalProgress, swalToast } from '@/lib/swal';

export type UsuarioRole = {
    id: number;
    name: string;
};

export type UsuarioRow = {
    id: number;
    persona_id: number | null;

    name: string;
    email: string;
    status: 'active' | 'blocked';
    is_super_admin: boolean;

    persona_tipo?: 'paciente' | 'staff' | 'ambos' | null;
    persona_status?: 'active' | 'inactive' | null;
    nombres: string;
    apellido_paterno: string;
    apellido_materno: string;
    telefono: string;
    persona_email?: string | null;

    roles: UsuarioRole[];
    role_ids: number[];

    mod_agenda: boolean;
    mod_pacientes: boolean;
    mod_sesiones: boolean;
    mod_ejercicios: boolean;
    mod_archivos: boolean;
    mod_reportes: boolean;
    mod_cobranza: boolean;
    mod_config: boolean;

    last_login_at?: string | null;
    last_login_ip?: string | null;
};

export const useUsuarioCrud = () => {
    const isOpen = ref(false);
    const editingId = ref<number | null>(null);

    const form = useForm({
        nombres: '',
        apellido_paterno: '',
        apellido_materno: '',
        telefono: '',
        email: '',

        password: '',
        status: 'active' as 'active' | 'blocked',
        is_super_admin: false,
        role_ids: [] as number[],

        mod_agenda: true,
        mod_pacientes: true,
        mod_sesiones: true,
        mod_ejercicios: true,
        mod_archivos: true,
        mod_reportes: false,
        mod_cobranza: false,
        mod_config: false,
    });

    const isEditing = computed(() => editingId.value !== null);

    const onlyDigits = (value: string) => value.replace(/\D+/g, '');

    const validateBeforeSubmit = () => {
        form.clearErrors();

        const errors: Record<string, string> = {};

        if (!form.nombres.trim()) {
            errors.nombres = 'El nombre de la persona es obligatorio.';
        }

        if (!form.email.trim()) {
            errors.email = 'El correo electrónico es obligatorio.';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
            errors.email = 'Ingresa un correo electrónico válido.';
        }

        if (!isEditing.value && !form.password.trim()) {
            errors.password =
                'La contraseña es obligatoria para crear usuario.';
        }

        if (form.password && form.password.length < 8) {
            errors.password = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if (!form.is_super_admin && form.role_ids.length === 0) {
            errors.role_ids = 'Selecciona al menos un rol para el usuario.';
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

        form.nombres = '';
        form.apellido_paterno = '';
        form.apellido_materno = '';
        form.telefono = '';
        form.email = '';

        form.password = '';
        form.status = 'active';
        form.is_super_admin = false;
        form.role_ids = [];

        form.mod_agenda = true;
        form.mod_pacientes = true;
        form.mod_sesiones = true;
        form.mod_ejercicios = true;
        form.mod_archivos = true;
        form.mod_reportes = false;
        form.mod_cobranza = false;
        form.mod_config = false;

        isOpen.value = true;
    };

    const openEdit = (row: UsuarioRow) => {
        editingId.value = row.id;
        form.clearErrors();

        form.nombres = row.nombres ?? row.name ?? '';
        form.apellido_paterno = row.apellido_paterno ?? '';
        form.apellido_materno = row.apellido_materno ?? '';
        form.telefono = row.telefono ?? '';
        form.email = row.email ?? '';

        form.password = '';
        form.status = row.status;
        form.is_super_admin = row.is_super_admin;
        form.role_ids = row.role_ids ?? [];

        form.mod_agenda = row.mod_agenda;
        form.mod_pacientes = row.mod_pacientes;
        form.mod_sesiones = row.mod_sesiones;
        form.mod_ejercicios = row.mod_ejercicios;
        form.mod_archivos = row.mod_archivos;
        form.mod_reportes = row.mod_reportes;
        form.mod_cobranza = row.mod_cobranza;
        form.mod_config = row.mod_config;

        isOpen.value = true;
    };

    const toggleRole = (roleId: number) => {
        if (form.role_ids.includes(roleId)) {
            form.role_ids = form.role_ids.filter((id) => id !== roleId);
            return;
        }

        form.role_ids = [...form.role_ids, roleId];
    };

    const closeModal = () => {
        isOpen.value = false;
        form.clearErrors();
    };

    const submit = async () => {
        if (!validateBeforeSubmit()) return;

        const ok = await swalConfirm(
            isEditing.value
                ? '¿Deseas actualizar este usuario?'
                : '¿Deseas crear este usuario?',
            isEditing.value
                ? 'Se actualizarán sus datos y roles asignados.'
                : 'Primero se registrará la persona, después el usuario y se enviará un correo con sus credenciales.',
            isEditing.value ? 'Sí, actualizar' : 'Sí, crear',
        );

        if (!ok) return;

        if (isEditing.value && editingId.value) {
            swalProgress(
                'Actualizando usuario...',
                'Estamos guardando los datos de la persona y sus accesos.',
            );

            form.put(`/usuarios/${editingId.value}`, {
                preserveScroll: true,
                onSuccess: () => {
                    swalClose();
                    swalToast('Usuario actualizado correctamente', 'success');
                    closeModal();
                },
                onError: () => {
                    swalClose();
                    swalToast('Revisa el formulario', 'warning');
                },
                onFinish: () => {
                    if (form.hasErrors) swalClose();
                },
            });

            return;
        }

        swalProgress(
            'Creando usuario...',
            'Estamos registrando la persona, creando el usuario y enviando sus credenciales por correo.',
        );

        form.post('/usuarios', {
            preserveScroll: true,
            onSuccess: () => {
                swalClose();
                swalToast(
                    'Usuario creado y correo enviado correctamente',
                    'success',
                );
                closeModal();
            },
            onError: () => {
                swalClose();
                swalToast('Revisa el formulario', 'warning');
            },
            onFinish: () => {
                if (form.hasErrors) swalClose();
            },
        });
    };

    const toggleStatus = async (row: UsuarioRow) => {
        const nextState = row.status === 'active' ? 'bloquear' : 'activar';

        const ok = await swalConfirm(
            `¿Deseas ${nextState} este usuario?`,
            'El acceso al sistema se ajustará inmediatamente.',
            `Sí, ${nextState}`,
        );

        if (!ok) return;

        router.patch(
            `/usuarios/${row.id}/toggle-status`,
            {},
            {
                preserveScroll: true,
                onSuccess: () =>
                    swalToast('Estado actualizado correctamente', 'success'),
            },
        );
    };

    const destroyUser = async (id: number) => {
        const ok = await swalConfirm(
            '¿Deseas eliminar este usuario?',
            'El usuario quedará eliminado del listado principal y su persona se marcará como inactiva.',
            'Sí, eliminar',
        );

        if (!ok) return;

        router.delete(`/usuarios/${id}`, {
            preserveScroll: true,
            onSuccess: () =>
                swalToast('Usuario eliminado correctamente', 'success'),
        });
    };

    return {
        form,
        isOpen,
        isEditing,
        openCreate,
        openEdit,
        toggleRole,
        closeModal,
        submit,
        toggleStatus,
        destroyUser,
        editingId,
        onlyDigits,
    };
};
