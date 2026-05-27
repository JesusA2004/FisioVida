<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { formatDateMx } from '@/lib/dates';

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        placeholder?: string;
        clearable?: boolean;
        minYear?: number;
        maxYear?: number;
    }>(),
    {
        placeholder: 'Seleccionar fecha',
        clearable: true,
    },
);

const emit = defineEmits<{ (e: 'update:modelValue', v: string | null): void }>();

const isOpen = ref(false);

const monthNames = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
];

const weekDays = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do'];

const fallbackMaxYear = new Date().getFullYear();

const minYearValue = computed(() => props.minYear ?? fallbackMaxYear - 120);
const maxYearValue = computed(() => props.maxYear ?? fallbackMaxYear);

const toLocalDate = (value?: string | null) => {
    if (!value) return null;

    const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (!match) return null;

    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
};

const pad = (n: number) => String(n).padStart(2, '0');

const toIsoDate = (d: Date) =>
    `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

const clampYear = (year: number) =>
    Math.min(Math.max(year, minYearValue.value), maxYearValue.value);

const initial = toLocalDate(props.modelValue) ?? new Date();

const viewYear = ref(clampYear(initial.getFullYear()));
const viewMonth = ref(initial.getMonth());

const selectedDate = computed(() => toLocalDate(props.modelValue));

const years = computed(() => {
    const list: number[] = [];

    for (let year = maxYearValue.value; year >= minYearValue.value; year -= 1) {
        list.push(year);
    }

    return list;
});

const monthLabel = computed(() => `${monthNames[viewMonth.value]} ${viewYear.value}`);

const triggerLabel = computed(() =>
    props.modelValue ? formatDateMx(props.modelValue) : props.placeholder,
);

watch(
    () => props.modelValue,
    (value) => {
        const date = toLocalDate(value);

        if (!date) return;

        viewYear.value = clampYear(date.getFullYear());
        viewMonth.value = date.getMonth();
    },
);

const today = () => {
    const now = new Date();

    viewMonth.value = now.getMonth();
    viewYear.value = clampYear(now.getFullYear());

    emit('update:modelValue', toIsoDate(now));
    isOpen.value = false;
};

const goPrevMonth = () => {
    if (viewMonth.value === 0) {
        if (viewYear.value <= minYearValue.value) return;

        viewMonth.value = 11;
        viewYear.value -= 1;
        return;
    }

    viewMonth.value -= 1;
};

const goNextMonth = () => {
    if (viewMonth.value === 11) {
        if (viewYear.value >= maxYearValue.value) return;

        viewMonth.value = 0;
        viewYear.value += 1;
        return;
    }

    viewMonth.value += 1;
};

const setMonth = (event: Event) => {
    viewMonth.value = Number((event.target as HTMLSelectElement).value);
};

const setYear = (event: Event) => {
    viewYear.value = Number((event.target as HTMLSelectElement).value);
};

const isOutsideRange = (date: Date) => {
    const year = date.getFullYear();

    return year < minYearValue.value || year > maxYearValue.value || date > new Date();
};

const calendarDays = computed(() => {
    const first = new Date(viewYear.value, viewMonth.value, 1);
    const firstWeekday = (first.getDay() + 6) % 7;
    const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
    const daysInPrevMonth = new Date(viewYear.value, viewMonth.value, 0).getDate();

    const cells: Array<{ date: Date; inCurrentMonth: boolean }> = [];

    for (let i = firstWeekday - 1; i >= 0; i -= 1) {
        cells.push({
            date: new Date(viewYear.value, viewMonth.value - 1, daysInPrevMonth - i),
            inCurrentMonth: false,
        });
    }

    for (let d = 1; d <= daysInMonth; d += 1) {
        cells.push({
            date: new Date(viewYear.value, viewMonth.value, d),
            inCurrentMonth: true,
        });
    }

    while (cells.length < 42) {
        const day = cells.length - (firstWeekday + daysInMonth) + 1;

        cells.push({
            date: new Date(viewYear.value, viewMonth.value + 1, day),
            inCurrentMonth: false,
        });
    }

    return cells;
});

const isSelected = (d: Date) => {
    const sel = selectedDate.value;

    return !!sel && toIsoDate(sel) === toIsoDate(d);
};

const pickDay = (d: Date) => {
    if (isOutsideRange(d)) return;

    viewMonth.value = d.getMonth();
    viewYear.value = clampYear(d.getFullYear());

    emit('update:modelValue', toIsoDate(d));
    isOpen.value = false;
};

const clearDate = () => {
    emit('update:modelValue', null);
};
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                class="h-11 w-full justify-start rounded-2xl border-input bg-card text-left font-normal shadow-sm transition-all duration-200 hover:bg-muted focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary/20"
            >
                <CalendarDays class="mr-2 h-4 w-4 shrink-0" />

                <span class="truncate" :class="modelValue ? '' : 'text-muted-foreground'">
                    {{ triggerLabel }}
                </span>
            </Button>
        </PopoverTrigger>

        <PopoverContent
            side="bottom"
            align="center"
            :side-offset="6"
            :collision-padding="12"
            class="z-[100] flex max-h-[420px] w-[min(calc(100vw-1rem),20rem)] flex-col overflow-hidden rounded-2xl border border-border bg-popover p-0 shadow-2xl sm:max-h-[440px] sm:w-[20rem]"
        >
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 pt-3 pb-2">
                <div class="space-y-2">
                    <div class="flex h-8 items-center justify-between gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            :disabled="viewYear <= minYearValue && viewMonth === 0"
                            aria-label="Mes anterior"
                            class="h-8 w-8"
                            @click="goPrevMonth"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>

                        <p
                            class="truncate text-sm font-semibold capitalize text-foreground"
                        >
                            {{ monthLabel }}
                        </p>

                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            :disabled="viewYear >= maxYearValue && viewMonth === 11"
                            aria-label="Mes siguiente"
                            class="h-8 w-8"
                            @click="goNextMonth"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>

                    <div class="grid grid-cols-[1fr_6.5rem] gap-2">
                        <select
                            :value="viewMonth"
                            class="h-9 min-w-0 rounded-xl border border-input bg-card px-3 text-sm capitalize text-foreground shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                            aria-label="Seleccionar mes"
                            @change="setMonth"
                        >
                            <option
                                v-for="(month, index) in monthNames"
                                :key="month"
                                :value="index"
                            >
                                {{ month }}
                            </option>
                        </select>

                        <select
                            :value="viewYear"
                            class="h-9 min-w-0 rounded-xl border border-input bg-card px-3 text-sm text-foreground shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                            aria-label="Seleccionar año"
                            @change="setYear"
                        >
                            <option v-for="year in years" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>

                    <div
                        class="grid grid-cols-7 gap-1 text-center text-[11px] font-medium text-muted-foreground"
                    >
                        <span
                            v-for="day in weekDays"
                            :key="day"
                            class="grid h-5 place-items-center"
                        >
                            {{ day }}
                        </span>
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        <button
                            v-for="cell in calendarDays"
                            :key="cell.date.toISOString()"
                            type="button"
                            class="grid h-7 min-w-0 place-items-center rounded-lg text-xs transition disabled:cursor-not-allowed disabled:opacity-35 sm:h-7"
                            :disabled="isOutsideRange(cell.date)"
                            :class="[
                                cell.inCurrentMonth
                                    ? 'text-foreground hover:bg-muted'
                                    : 'text-muted-foreground hover:bg-muted',
                                isSelected(cell.date)
                                    ? '!bg-primary !text-primary-foreground hover:!bg-primary/90'
                                    : '',
                            ]"
                            @click="pickDay(cell.date)"
                        >
                            {{ cell.date.getDate() }}
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="shrink-0 border-t border-border bg-popover px-3 py-2"
            >
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-9 w-full rounded-xl"
                        @click="today"
                    >
                        Hoy
                    </Button>

                    <Button
                        v-if="clearable && modelValue"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-9 w-full rounded-xl"
                        @click="clearDate"
                    >
                        Quitar selección
                    </Button>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>