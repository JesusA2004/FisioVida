import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { swalConfirm, swalToast } from '@/lib/swal';

export type PermissionOption = {
    id: number;
    name: string;
    slug: string;
    module: string;
};

export type RoleRow = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    status: 'active' | 'inactive';
    permission_ids?: number[];
    permissions_count: number;
};

const makeSlug = (value: string) =>
    value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s_-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');

export const useRolCrud = () => {
    const isOpen = ref(false);
    const editingId = ref<number | null>(null);
    const permissionSearch = ref('');

    const form = useForm({
        name: '',
        slug: '',
        description: '',
        status: 'active' as 'active' | 'inactive',
        permission_ids: [] as number[],
    });

    const isEditing = computed(() => editingId.value !== null);
    const selectedCount = computed(() => form.permission_ids.length);

    const syncSlugFromName = () => {
        if (isEditing.value) return;

        form.slug = makeSlug(form.name);
    };

    const openCreate = () => {
        editingId.value = null;
        permissionSearch.value = '';
        form.reset();
        form.clearErrors();

        form.name = '';
        form.slug = '';
        form.description = '';
        form.status = 'active';
        form.permission_ids = [];

        isOpen.value = true;
    };

    const openEdit = (row: RoleRow) => {
        editingId.value = row.id;
        permissionSearch.value = '';
        form.clearErrors();

        form.name = row.name;
        form.slug = row.slug;
        form.description = row.description ?? '';
        form.status = row.status;
        form.permission_ids = row.permission_ids ?? [];

        isOpen.value = true;
    };

    const closeModal = () => {
        isOpen.value = false;
        form.clearErrors();
    };

    const togglePermission = (permissionId: number) => {
        if (form.permission_ids.includes(permissionId)) {
            form.permission_ids = form.permission_ids.filter(
                (id) => id !== permissionId,
            );
            return;
        }

        form.permission_ids = [...form.permission_ids, permissionId];
    };

    const selectAllFromModule = (permissions: PermissionOption[]) => {
        const ids = permissions.map((item) => item.id);
        form.permission_ids = [...new Set([...form.permission_ids, ...ids])];
    };

    const clearModule = (permissions: PermissionOption[]) => {
        const ids = new Set(permissions.map((item) => item.id));
        form.permission_ids = form.permission_ids.filter((id) => !ids.has(id));
    };

    const submit = async () => {
        syncSlugFromName();

        const confirm = await swalConfirm(
            isEditing.value
                ? '¿Deseas actualizar este rol?'
                : '¿Deseas crear este rol?',
            'Se guardará la configuración de accesos seleccionada.',
            isEditing.value ? 'Sí, actualizar' : 'Sí, crear',
        );

        if (!confirm) return;

        if (isEditing.value && editingId.value) {
            form.put(`/roles/${editingId.value}`, {
                preserveScroll: true,
                onSuccess: () => {
                    swalToast('Rol actualizado correctamente', 'success');
                    closeModal();
                },
                onError: () => swalToast('Revisa el formulario', 'warning'),
            });

            return;
        }

        form.post('/roles', {
            preserveScroll: true,
            onSuccess: () => {
                swalToast('Rol creado correctamente', 'success');
                closeModal();
            },
            onError: () => swalToast('Revisa el formulario', 'warning'),
        });
    };

    const destroyRole = async (id: number) => {
        const confirm = await swalConfirm(
            '¿Deseas eliminar este rol?',
            'El rol dejará de estar disponible para asignarse a usuarios.',
            'Sí, eliminar',
        );

        if (!confirm) return;

        router.delete(`/roles/${id}`, {
            preserveScroll: true,
            onSuccess: () =>
                swalToast('Rol eliminado correctamente', 'success'),
        });
    };

    return {
        form,
        isOpen,
        isEditing,
        openCreate,
        openEdit,
        closeModal,
        togglePermission,
        selectAllFromModule,
        clearModule,
        submit,
        destroyRole,
        permissionSearch,
        selectedCount,
        syncSlugFromName,
    };
};
