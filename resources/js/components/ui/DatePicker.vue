<script setup lang="ts">
import { computed } from 'vue'
import { formatDateMx } from '@/lib/dates'

const props = withDefaults(defineProps<{ modelValue: string | null; placeholder?: string; clearable?: boolean }>(), { placeholder: 'Seleccionar fecha', clearable: true })
const emit = defineEmits<{ (e: 'update:modelValue', v: string | null): void }>()
const label = computed(() => (props.modelValue ? formatDateMx(props.modelValue) : props.placeholder))
</script>

<template>
  <div class="space-y-1">
    <div class="text-xs text-zinc-500">{{ label }}</div>
    <div class="flex gap-2">
      <input :value="modelValue ?? ''" type="date" class="h-10 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-800 dark:bg-zinc-900" @input="emit('update:modelValue', ($event.target as HTMLInputElement).value || null)" />
      <button v-if="clearable && modelValue" type="button" class="rounded-xl border border-zinc-200 px-3 text-sm dark:border-zinc-800" @click="emit('update:modelValue', null)">Limpiar</button>
    </div>
  </div>
</template>
