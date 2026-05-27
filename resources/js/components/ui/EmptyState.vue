<script setup lang="ts">
import type { Component } from 'vue'

defineProps<{
    icon?: Component
    title: string
    description?: string
    size?: 'sm' | 'md' | 'lg'
}>()
</script>

<template>
    <div
        class="flex flex-col items-center justify-center text-center"
        :class="size === 'sm' ? 'gap-2 py-8' : size === 'lg' ? 'gap-4 py-20' : 'gap-3 py-12'"
    >
        <div
            v-if="icon"
            class="flex items-center justify-center rounded-2xl bg-muted"
            :class="size === 'sm' ? 'h-10 w-10' : 'h-14 w-14'"
        >
            <component
                :is="icon"
                class="text-muted-foreground opacity-50"
                :class="size === 'sm' ? 'h-5 w-5' : 'h-7 w-7'"
            />
        </div>
        <div>
            <p
                class="font-medium text-foreground"
                :class="size === 'sm' ? 'text-sm' : 'text-base'"
            >
                {{ title }}
            </p>
            <p
                v-if="description"
                class="text-muted-foreground"
                :class="size === 'sm' ? 'mt-0.5 text-xs' : 'mt-1 text-sm'"
            >
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.action">
            <slot name="action" />
        </div>
    </div>
</template>
