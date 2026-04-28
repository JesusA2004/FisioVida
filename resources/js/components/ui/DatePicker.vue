<script setup lang="ts">
import { computed, ref } from 'vue'
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { formatDateMx } from '@/lib/dates'

const props = withDefaults(defineProps<{ modelValue: string | null; placeholder?: string; clearable?: boolean }>(), {
  placeholder: 'Seleccionar fecha',
  clearable: true,
})

const emit = defineEmits<{ (e: 'update:modelValue', v: string | null): void }>()

const monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre']
const weekDays = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do']

const toLocalDate = (value?: string | null) => {
  if (!value) return null
  const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/)
  if (!match) return null
  return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
}

const pad = (n: number) => String(n).padStart(2, '0')
const toIsoDate = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

const initial = toLocalDate(props.modelValue) ?? new Date()
const viewYear = ref(initial.getFullYear())
const viewMonth = ref(initial.getMonth())

const selectedDate = computed(() => toLocalDate(props.modelValue))
const monthLabel = computed(() => `${monthNames[viewMonth.value]} ${viewYear.value}`)
const triggerLabel = computed(() => (props.modelValue ? formatDateMx(props.modelValue) : props.placeholder))

const today = () => {
  const now = new Date()
  viewMonth.value = now.getMonth()
  viewYear.value = now.getFullYear()
  emit('update:modelValue', toIsoDate(now))
}

const goPrevMonth = () => {
  if (viewMonth.value === 0) {
    viewMonth.value = 11
    viewYear.value -= 1
    return
  }
  viewMonth.value -= 1
}

const goNextMonth = () => {
  if (viewMonth.value === 11) {
    viewMonth.value = 0
    viewYear.value += 1
    return
  }
  viewMonth.value += 1
}

const calendarDays = computed(() => {
  const first = new Date(viewYear.value, viewMonth.value, 1)
  const firstWeekday = (first.getDay() + 6) % 7
  const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate()
  const daysInPrevMonth = new Date(viewYear.value, viewMonth.value, 0).getDate()

  const cells: Array<{ date: Date; inCurrentMonth: boolean }> = []
  for (let i = firstWeekday - 1; i >= 0; i--) {
    cells.push({ date: new Date(viewYear.value, viewMonth.value - 1, daysInPrevMonth - i), inCurrentMonth: false })
  }
  for (let d = 1; d <= daysInMonth; d++) {
    cells.push({ date: new Date(viewYear.value, viewMonth.value, d), inCurrentMonth: true })
  }
  while (cells.length < 42) {
    const day = cells.length - (firstWeekday + daysInMonth) + 1
    cells.push({ date: new Date(viewYear.value, viewMonth.value + 1, day), inCurrentMonth: false })
  }
  return cells
})

const isSelected = (d: Date) => {
  const sel = selectedDate.value
  return !!sel && toIsoDate(sel) === toIsoDate(d)
}

const pickDay = (d: Date) => {
  viewMonth.value = d.getMonth()
  viewYear.value = d.getFullYear()
  emit('update:modelValue', toIsoDate(d))
}
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button variant="outline" class="w-full justify-start rounded-xl text-left font-normal">
        <CalendarDays class="mr-2 h-4 w-4" />
        <span :class="modelValue ? '' : 'text-zinc-400'">{{ triggerLabel }}</span>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[320px] rounded-2xl p-3" align="start">
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <Button type="button" variant="ghost" size="icon-sm" @click="goPrevMonth"><ChevronLeft class="h-4 w-4" /></Button>
          <p class="text-sm font-medium capitalize">{{ monthLabel }}</p>
          <Button type="button" variant="ghost" size="icon-sm" @click="goNextMonth"><ChevronRight class="h-4 w-4" /></Button>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-xs text-zinc-500 dark:text-zinc-400">
          <span v-for="day in weekDays" :key="day">{{ day }}</span>
        </div>

        <div class="grid grid-cols-7 gap-1">
          <button
            v-for="cell in calendarDays"
            :key="cell.date.toISOString()"
            type="button"
            class="h-9 rounded-lg text-sm transition"
            :class="[
              cell.inCurrentMonth ? 'text-zinc-900 hover:bg-zinc-100 dark:text-zinc-100 dark:hover:bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-100 dark:text-zinc-600 dark:hover:bg-zinc-800',
              isSelected(cell.date) ? 'bg-primary text-primary-foreground hover:bg-primary/90 dark:text-primary-foreground' : '',
            ]"
            @click="pickDay(cell.date)"
          >
            {{ cell.date.getDate() }}
          </button>
        </div>

        <div class="flex items-center justify-between gap-2 pt-1">
          <Button type="button" variant="outline" size="sm" @click="today">Hoy</Button>
          <Button v-if="clearable && modelValue" type="button" variant="ghost" size="sm" @click="emit('update:modelValue', null)">Limpiar</Button>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
