<script setup lang="ts">
import { computed, ref } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { format } from 'date-fns'
import CalendarMini from './CalendarMini.vue'
import { Calendar } from 'lucide-vue-next'

const props = withDefaults(defineProps<{
  modelValue: Date | null
  placeholder?: string
}>(), {
  placeholder: 'Seleccionar fecha',
})

const emit = defineEmits<{
  (e: 'update:modelValue', v: Date | null): void
}>()

const open = ref(false)
const month = ref(new Date())
const pop = ref<HTMLElement | null>(null)

onClickOutside(pop, () => open.value = false)

const label = computed(() => {
  if (!props.modelValue) return ''
  return format(props.modelValue, 'yyyy-MM-dd')
})

const pick = (d: Date) => {
  emit('update:modelValue', d)
  open.value = false
}
</script>

<template>
  <div class="relative" ref="pop">
    <button type="button" class="fv-input flex items-center justify-between gap-2" @click="open = !open">
      <div class="flex items-center gap-2">
        <Calendar class="h-4 w-4 text-slate-400" />
        <span class="text-sm" :class="label ? 'text-slate-100' : 'text-slate-400'">
          {{ label || placeholder }}
        </span>
      </div>
      <span class="text-slate-500 text-xs">▼</span>
    </button>

    <div v-if="open" class="absolute z-50 mt-2 fv-card w-[320px] overflow-hidden">
      <CalendarMini
        :model-value="modelValue"
        :month="month"
        @update:month="month = $event"
        @update:model-value="pick"
      />
    </div>
  </div>
</template>
