<script setup lang="ts">
import { computed } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps<{
    permission?: string
    permissions?: string[]
    role?: string
    roles?: string[]
    module?: string
    requireAll?: boolean
}>()

const { can, canAny, hasRole, hasAnyRole, moduleEnabled, isSuperAdmin } = usePermissions()

const allowed = computed(() => {
    if (isSuperAdmin.value) return true

    if (props.module && !moduleEnabled(props.module)) return false

    if (props.permission && !can(props.permission)) return false

    if (props.permissions?.length) {
        const check = props.requireAll
            ? props.permissions.every((p) => can(p))
            : canAny(...props.permissions)
        if (!check) return false
    }

    if (props.role && !hasRole(props.role)) return false

    if (props.roles?.length) {
        if (!hasAnyRole(...props.roles)) return false
    }

    return true
})
</script>

<template>
    <slot v-if="allowed" />
</template>
