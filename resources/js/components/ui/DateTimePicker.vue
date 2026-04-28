<script setup lang="ts">
import { computed, ref } from 'vue'
import { CalendarClock, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import SearchableSelect from '@/components/ui/SearchableSelect.vue'
import { formatDateTimeMx } from '@/lib/dates'

const props = withDefaults(defineProps<{ modelValue: string | null; placeholder?: string; clearable?: boolean }>(), {
  placeholder: 'Seleccionar fecha y hora',
  clearable: true,
})

const emit = defineEmits<{ (e: 'update:modelValue', v: string | null): void }>()

const monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre']
const weekDays = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do']

const parseDateTime = (value?: string | null) => {
  if (!value) return null
  const d = new Date(value)
  if (!Number.isNaN(d.getTime())) return d
  const match = value.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/)
  if (!match) return null
  return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), Number(match[4]), Number(match[5]))
}

const pad = (n: number) => String(n).padStart(2, '0')
const toIsoDateTimeLocal = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`

const seed = parseDateTime(props.modelValue) ?? new Date()
const viewYear = ref(seed.getFullYear())
const viewMonth = ref(seed.getMonth())
const selectedDay = ref(new Date(seed.getFullYear(), seed.getMonth(), seed.getDate()))
const meridiem = ref<'AM' | 'PM'>(seed.getHours() >= 12 ? 'PM' : 'AM')
const hour12 = ref(((seed.getHours() + 11) % 12) + 1)
const minute = ref(seed.getMinutes())

const triggerLabel = computed(() => (props.modelValue ? formatDateTimeMx(props.modelValue) : props.placeholder))
const monthLabel = computed(() => `${monthNames[viewMonth.value]} ${viewYear.value}`)

const hourOptions = computed(() => Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: String(i + 1).padStart(2, '0') })))
const minuteOptions = computed(() => Array.from({ length: 60 }, (_, i) => ({ value: i, label: String(i).padStart(2, '0') })))

const currentAsDate = () => {
  const base = selectedDay.value
  let h = hour12.value % 12
  if (meridiem.value === 'PM') h += 12
  return new Date(base.getFullYear(), base.getMonth(), base.getDate(), h, minute.value)
}

const syncFromModel = () => {
  const parsed = parseDateTime(props.modelValue)
  if (!parsed) return
  viewYear.value = parsed.getFullYear()
  viewMonth.value = parsed.getMonth()
  selectedDay.value = new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate())
  hour12.value = ((parsed.getHours() + 11) % 12) + 1
  minute.value = parsed.getMinutes()
  meridiem.value = parsed.getHours() >= 12 ? 'PM' : 'AM'
}
syncFromModel()

const publish = () => emit('update:modelValue', toIsoDateTimeLocal(currentAsDate()))
const pickDay = (d: Date) => {
  selectedDay.value = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  viewMonth.value = d.getMonth()
  viewYear.value = d.getFullYear()
  publish()
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
  for (let i = firstWeekday - 1; i >= 0; i--) cells.push({ date: new Date(viewYear.value, viewMonth.value - 1, daysInPrevMonth - i), inCurrentMonth: false })
  for (let d = 1; d <= daysInMonth; d++) cells.push({ date: new Date(viewYear.value, viewMonth.value, d), inCurrentMonth: true })
  while (cells.length < 42) {
    const day = cells.length - (firstWeekday + daysInMonth) + 1
    cells.push({ date: new Date(viewYear.value, viewMonth.value + 1, day), inCurrentMonth: false })
  }
  return cells
})

const isSelected = (d: Date) => d.toDateString() === selectedDay.value.toDateString()

const selectToday = () => {
  const now = new Date()
  selectedDay.value = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  hour12.value = ((now.getHours() + 11) % 12) + 1
  minute.value = now.getMinutes()
  meridiem.value = now.getHours() >= 12 ? 'PM' : 'AM'
  viewMonth.value = now.getMonth()
  viewYear.value = now.getFullYear()
  publish()
}
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button variant="outline" class="w-full justify-start rounded-xl text-left font-normal">
        <CalendarClock class="mr-2 h-4 w-4" />
        <span :class="modelValue ? '' : 'text-zinc-400'">{{ triggerLabel }}</span>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[340px] rounded-2xl p-3" align="start">
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
              isSelected(cell.date) ? 'bg-primary text-primary-foreground hover:bg-primary/90' : '',
            ]"
            @click="pickDay(cell.date)"
          >
            {{ cell.date.getDate() }}
          </button>
        </div>

        <div class="grid grid-cols-3 gap-2">
          <SearchableSelect v-model="hour12" :options="hourOptions" placeholder="Hora" />
          <SearchableSelect v-model="minute" :options="minuteOptions" placeholder="Min" />
          <SearchableSelect v-model="meridiem" :options="[{ value: 'AM', label: 'AM' }, { value: 'PM', label: 'PM' }]" placeholder="AM/PM" />
        </div>

        <div class="flex items-center justify-between gap-2 pt-1">
          <Button type="button" variant="outline" size="sm" @click="selectToday">Hoy</Button>
          <div class="flex gap-2">
            <Button type="button" variant="default" size="sm" @click="publish">Aplicar</Button>
            <Button v-if="clearable && modelValue" type="button" variant="ghost" size="sm" @click="emit('update:modelValue', null)">Limpiar</Button>
          </div>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
