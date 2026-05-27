<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Claro' },
    { value: 'dark', Icon: Moon, label: 'Oscuro' },
    { value: 'system', Icon: Monitor, label: 'Sistema' },
] as const;
</script>

<template>
    <div class="inline-flex gap-1 rounded-xl border border-border bg-muted p-1">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-sm transition-all duration-150',
                appearance === value
                    ? 'bg-card text-foreground shadow-sm font-medium'
                    : 'text-muted-foreground hover:bg-card/60 hover:text-foreground',
            ]"
        >
            <component :is="Icon" class="h-4 w-4" />
            {{ label }}
        </button>
    </div>
</template>
