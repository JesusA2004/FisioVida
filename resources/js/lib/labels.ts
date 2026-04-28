const fallback = (value: string | null | undefined) => value ?? '—';

export const appointmentStatusLabels: Record<string, string> = {
    scheduled: 'Programada',
    confirmed: 'Confirmada',
    arrived: 'Llegó',
    no_show: 'No asistió',
    cancelled: 'Cancelada',
    done: 'Finalizada',
};

export const paymentStatusLabels: Record<string, string> = {
    pending: 'Pendiente',
    paid: 'Pagado',
    failed: 'Fallido',
    refunded: 'Reembolsado',
    cancelled: 'Cancelado',
};

export const activityStatusLabels: Record<string, string> = {
    pending: 'Pendiente',
    in_progress: 'En proceso',
    completed: 'Completada',
    cancelled: 'Cancelada',
    overdue: 'Vencida',
    on_hold: 'En pausa',
};

export const userStatusLabels: Record<string, string> = {
    active: 'Activo',
    inactive: 'Inactivo',
    blocked: 'Bloqueado',
};

export const priorityLabels: Record<string, string> = {
    low: 'Baja',
    medium: 'Media',
    high: 'Alta',
    urgent: 'Urgente',
};

export const generalStatusLabels: Record<string, string> = {
    active: 'Activo',
    inactive: 'Inactivo',
    enabled: 'Habilitado',
    disabled: 'Deshabilitado',
};

export const permissionLabels: Record<string, string> = {
    'dashboard.view': 'Ver dashboard',
    'patients.view': 'Ver pacientes',
    'patients.create': 'Registrar pacientes',
    'patients.update': 'Editar pacientes',
    'patients.delete': 'Eliminar pacientes',
    'appointments.view': 'Ver agenda',
    'appointments.create': 'Registrar citas',
    'appointments.update': 'Editar citas',
    'appointments.cancel': 'Cancelar citas',
    'sessions.view': 'Ver sesiones',
    'sessions.create': 'Registrar sesiones',
    'sessions.update': 'Editar sesiones',
    'exercises.view': 'Ver ejercicios',
    'exercises.create': 'Registrar ejercicios',
    'exercises.update': 'Editar ejercicios',
    'files.view': 'Ver archivos',
    'files.upload': 'Subir archivos',
    'payments.view': 'Ver pagos',
    'payments.create': 'Registrar pagos',
    'payments.update': 'Editar pagos',
    'activities.view': 'Ver actividades',
    'activities.create': 'Registrar actividades',
    'activities.update': 'Editar actividades',
    'activities.complete': 'Completar actividades',
    'roles.view': 'Ver roles',
    'roles.create': 'Registrar roles',
    'roles.update': 'Editar roles',
    'roles.delete': 'Eliminar roles',
    'permissions.view': 'Ver permisos',
    'permissions.create': 'Registrar permisos',
    'permissions.update': 'Editar permisos',
    'users.view': 'Ver usuarios',
    'users.create': 'Registrar usuarios',
    'users.update': 'Editar usuarios',
    'settings.view': 'Ver configuración',
    'settings.update': 'Editar configuración',
    'reports.view': 'Ver reportes',
    'logs.view': 'Ver bitácora',
};

export const moduleLabels: Record<string, string> = {
    dashboard: 'Dashboard',
    patients: 'Pacientes',
    appointments: 'Agenda',
    sessions: 'Sesiones',
    exercises: 'Ejercicios',
    files: 'Archivos',
    payments: 'Pagos',
    activities: 'Actividades',
    roles: 'Roles',
    permissions: 'Permisos',
    users: 'Usuarios',
    settings: 'Configuración',
    reports: 'Reportes',
    logs: 'Bitácora',
};

export const tAppointmentStatus = (status?: string | null) =>
    appointmentStatusLabels[fallback(status)] ?? fallback(status);
export const tPaymentStatus = (status?: string | null) =>
    paymentStatusLabels[fallback(status)] ?? fallback(status);
export const tActivityStatus = (status?: string | null) =>
    activityStatusLabels[fallback(status)] ?? fallback(status);
export const tUserStatus = (status?: string | null) =>
    userStatusLabels[fallback(status)] ?? fallback(status);
export const tPriority = (priority?: string | null) =>
    priorityLabels[fallback(priority)] ?? fallback(priority);
export const tGeneralStatus = (status?: string | null) =>
    generalStatusLabels[fallback(status)] ?? fallback(status);
export const tPermission = (slug?: string | null) =>
    permissionLabels[fallback(slug)] ?? fallback(slug);
export const tModule = (module?: string | null) =>
    moduleLabels[fallback(module)] ?? fallback(module);
