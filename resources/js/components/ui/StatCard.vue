<script setup lang="ts">
import { computed } from 'vue'
import type { Component } from 'vue'

interface Props {
    label: string
    value: string | number | null
    icon?: Component
    color?: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'muted'
    href?: string
}

const props = withDefaults(defineProps<Props>(), {
    color: 'primary',
    value: null,
})

const colorMap: Record<string, { ring: string; iconBg: string; iconText: string }> = {
    primary: { ring: 'ring-primary/20', iconBg: 'bg-primary/10', iconText: 'text-primary' },
    success: { ring: 'ring-emerald-500/20', iconBg: 'bg-emerald-500/10', iconText: 'text-emerald-500 dark:text-emerald-400' },
    warning: { ring: 'ring-amber-500/20', iconBg: 'bg-amber-500/10', iconText: 'text-amber-500 dark:text-amber-400' },
    danger:  { ring: 'ring-destructive/20', iconBg: 'bg-destructive/10', iconText: 'text-destructive' },
    info:    { ring: 'ring-sky-500/20', iconBg: 'bg-sky-500/10', iconText: 'text-sky-500 dark:text-sky-400' },
    muted:   { ring: 'ring-border', iconBg: 'bg-muted', iconText: 'text-muted-foreground' },
}

const c = computed(() => colorMap[props.color] ?? colorMap.primary)
</script>

<template>
    <component
        :is="href ? 'a' : 'div'"
        :href="href"
        class="group relative overflow-hidden rounded-2xl bg-card p-4 ring-1 transition-all duration-200"
        :class="[c.ring, href ? 'cursor-pointer hover:-translate-y-0.5 hover:shadow-md hover:ring-primary/40' : '']"
    >
        <div class="flex items-start justify-between gap-2">
            <p class="text-xs font-medium leading-snug text-muted-foreground">{{ label }}</p>
            <div v-if="icon" :class="['flex h-7 w-7 shrink-0 items-center justify-center rounded-lg', c.iconBg]">
                <component :is="icon" :class="['h-3.5 w-3.5', c.iconText]" />
            </div>
        </div>
        <p class="mt-2 text-2xl font-bold tracking-tight text-foreground">
            {{ value ?? '—' }}
        </p>
        <slot />
    </component>
</template>
