<script setup lang="ts">
import { computed } from 'vue'
import {
  addMonths,
  subMonths,
  startOfMonth,
  endOfMonth,
  startOfWeek,
  endOfWeek,
  eachDayOfInterval,
  format,
  isSameMonth,
  isSameDay,
} from 'date-fns'

const props = defineProps<{
  modelValue: Date | null
  month: Date
}>()

const emit = defineEmits<{
  (e: 'update:month', v: Date): void
  (e: 'update:modelValue', v: Date): void
}>()

const days = computed(() => {
  const start = startOfWeek(startOfMonth(props.month), { weekStartsOn: 1 })
  const end = endOfWeek(endOfMonth(props.month), { weekStartsOn: 1 })
  return eachDayOfInterval({ start, end })
})

const label = computed(() => format(props.month, 'MMMM yyyy'))

const prev = () => emit('update:month', subMonths(props.month, 1))
const next = () => emit('update:month', addMonths(props.month, 1))

const pick = (d: Date) => emit('update:modelValue', d)
</script>

<template>
  <div class="p-3">
    <div class="flex items-center justify-between">
      <button type="button" class="fv-btn fv-btn-ghost px-3 py-1" @click="prev">‹</button>
      <div class="text-sm font-semibold text-slate-100 capitalize">{{ label }}</div>
      <button type="button" class="fv-btn fv-btn-ghost px-3 py-1" @click="next">›</button>
    </div>

    <div class="mt-3 grid grid-cols-7 gap-1 text-xs text-slate-400">
      <div class="text-center">L</div><div class="text-center">M</div><div class="text-center">M</div>
      <div class="text-center">J</div><div class="text-center">V</div><div class="text-center">S</div><div class="text-center">D</div>
    </div>

    <div class="mt-2 grid grid-cols-7 gap-1">
      <button
        v-for="d in days"
        :key="d.toISOString()"
        type="button"
        class="h-9 rounded-xl text-sm transition"
        :class="[
          isSameMonth(d, month) ? 'text-slate-100' : 'text-slate-600',
          modelValue && isSameDay(d, modelValue) ? 'bg-[color:var(--brand-teal)] text-[#06121A] font-semibold' : 'hover:bg-white/5'
        ]"
        @click="pick(d)"
      >
        {{ format(d, 'd') }}
      </button>
    </div>
  </div>
</template>
