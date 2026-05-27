import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
    const page = usePage()

    const isSuperAdmin = computed<boolean>(() =>
        Boolean((page.props as any).auth?.is_super_admin),
    )

    const permissions = computed<string[]>(
        () => ((page.props as any).auth?.permissions ?? []) as string[],
    )

    const roles = computed<{ id: number; name: string; slug: string }[]>(
        () => ((page.props as any).auth?.roles ?? []),
    )

    const enabledModules = computed<Record<string, boolean>>(
        () => ((page.props as any).enabledModules ?? {}) as Record<string, boolean>,
    )

    const can = (permission: string): boolean =>
        isSuperAdmin.value || permissions.value.includes(permission)

    const canAny = (...perms: string[]): boolean =>
        perms.some((p) => can(p))

    const moduleEnabled = (module: string): boolean =>
        enabledModules.value[module] !== false

    const canAccess = (module: string, permission: string): boolean =>
        moduleEnabled(module) && can(permission)

    const hasRole = (slug: string): boolean =>
        isSuperAdmin.value || roles.value.some((r) => r.slug === slug)

    const hasAnyRole = (...slugs: string[]): boolean =>
        slugs.some((s) => hasRole(s))

    return {
        isSuperAdmin,
        permissions,
        roles,
        enabledModules,
        can,
        canAny,
        moduleEnabled,
        canAccess,
        hasRole,
        hasAnyRole,
    }
}
