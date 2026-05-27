<script setup lang="ts">
import { computed } from 'vue'

type Step = { value: string; label: string }
type Action = { key: string; label: string; variant?: 'default' | 'outline' | 'destructive'; disabled?: boolean }

const props = withDefaults(defineProps<{
  current: string
  steps: Step[]
  actions?: Action[]
  advanceLabel?: string
  canAdvance?: boolean
}>(), {
  actions: () => [],
  advanceLabel: 'Avanzar',
  canAdvance: false,
})

const emit = defineEmits<{
  (e: 'advance'): void
  (e: 'action', key: string): void
}>()

const currentIndex = computed(() => props.steps.findIndex((step) => step.value === props.current))
const isReached = (index: number) => currentIndex.value >= 0 && index <= currentIndex.value
</script>

<template>
  <div class="space-y-2">
    <div class="flex flex-wrap items-center gap-2">
      <template v-for="(step, index) in steps" :key="step.value">
        <div
          class="inline-flex items-center gap-2 rounded-full border px-2 py-1 text-xs"
          :class="isReached(index)
            ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300'
            : 'border-border bg-muted text-muted-foreground'"
        >
          <span
            class="inline-block h-2 w-2 rounded-full"
            :class="isReached(index) ? 'bg-emerald-500' : 'bg-muted-foreground/40'"
          />
          {{ step.label }}
        </div>
        <span v-if="index < steps.length - 1" class="text-muted-foreground">→</span>
      </template>
    </div>

    <div class="flex flex-wrap gap-2">
      <button
        v-if="canAdvance"
        type="button"
        class="rounded-xl border border-sky-300 bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-300"
        @click="emit('advance')"
      >
        {{ advanceLabel }}
      </button>
      <button
        v-for="action in actions"
        :key="action.key"
        type="button"
        :disabled="action.disabled"
        class="rounded-xl border px-3 py-1.5 text-xs font-medium disabled:opacity-50"
        :class="action.variant === 'destructive'
          ? 'border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300'
          : action.variant === 'default'
          ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300'
          : 'border-border bg-card text-foreground hover:bg-muted'"
        @click="emit('action', action.key)"
      >
        {{ action.label }}
      </button>
    </div>
  </div>
</template>
