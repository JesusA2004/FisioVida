<script setup lang="ts">
import type { Component } from 'vue'
import { MoreHorizontal } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

export interface DropdownAction {
    label: string
    icon?: Component
    onClick: () => void
    disabled?: boolean
    variant?: 'default' | 'danger'
    hidden?: boolean
    separator?: boolean
}

defineProps<{
    actions: DropdownAction[]
    label?: string
}>()
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="h-7 w-7 shrink-0 rounded-lg">
                <MoreHorizontal class="h-4 w-4" />
                <span class="sr-only">{{ label ?? 'Acciones' }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="min-w-40">
            <template v-for="(action, i) in actions" :key="i">
                <DropdownMenuSeparator v-if="action.separator && i > 0 && !actions[i-1]?.hidden" />
                <DropdownMenuItem
                    v-if="!action.hidden"
                    :disabled="action.disabled"
                    :class="action.variant === 'danger' ? 'text-destructive focus:text-destructive' : ''"
                    @click="action.onClick"
                >
                    <component v-if="action.icon" :is="action.icon" class="mr-2 h-3.5 w-3.5" />
                    {{ action.label }}
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
