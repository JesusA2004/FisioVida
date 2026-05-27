<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { CalendarClock, ChevronLeft, ChevronRight, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import { formatDateTimeMx } from '@/lib/dates';

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        placeholder?: string;
        clearable?: boolean;
    }>(),
    {
        placeholder: 'Seleccionar fecha y hora',
        clearable: true,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', v: string | null): void;
}>();

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

const parseDateTime = (value?: string | null) => {
    if (!value) return null;

    const d = new Date(value);

    if (!Number.isNaN(d.getTime())) return d;

    const match = value.match(
        /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/,
    );

    if (!match) return null;

    return new Date(
        Number(match[1]),
        Number(match[2]) - 1,
        Number(match[3]),
        Number(match[4]),
        Number(match[5]),
    );
};

const pad = (n: number) => String(n).padStart(2, '0');

const toIsoDateTimeLocal = (d: Date) =>
    `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(
        d.getHours(),
    )}:${pad(d.getMinutes())}`;

const getSeed = () => parseDateTime(props.modelValue) ?? new Date();

const seed = getSeed();

const open = ref(false);
const viewYear = ref(seed.getFullYear());
const viewMonth = ref(seed.getMonth());
const selectedDay = ref(
    new Date(seed.getFullYear(), seed.getMonth(), seed.getDate()),
);
const meridiem = ref<'AM' | 'PM'>(seed.getHours() >= 12 ? 'PM' : 'AM');
const hour12 = ref(((seed.getHours() + 11) % 12) + 1);
const minute = ref(seed.getMinutes());

const triggerLabel = computed(() =>
    props.modelValue ? formatDateTimeMx(props.modelValue) : props.placeholder,
);

const monthLabel = computed(
    () => `${monthNames[viewMonth.value]} ${viewYear.value}`,
);

const hourOptions = computed(() =>
    Array.from({ length: 12 }, (_, i) => ({
        value: i + 1,
        label: String(i + 1).padStart(2, '0'),
    })),
);

const minuteOptions = computed(() =>
    Array.from({ length: 60 }, (_, i) => ({
        value: i,
        label: String(i).padStart(2, '0'),
    })),
);

const meridiemOptions = [
    { value: 'AM', label: 'AM' },
    { value: 'PM', label: 'PM' },
];

const currentAsDate = () => {
    const base = selectedDay.value;
    let h = hour12.value % 12;

    if (meridiem.value === 'PM') {
        h += 12;
    }

    return new Date(
        base.getFullYear(),
        base.getMonth(),
        base.getDate(),
        h,
        minute.value,
    );
};

const syncFromModel = () => {
    const parsed = parseDateTime(props.modelValue);

    if (!parsed) return;

    viewYear.value = parsed.getFullYear();
    viewMonth.value = parsed.getMonth();
    selectedDay.value = new Date(
        parsed.getFullYear(),
        parsed.getMonth(),
        parsed.getDate(),
    );
    hour12.value = ((parsed.getHours() + 11) % 12) + 1;
    minute.value = parsed.getMinutes();
    meridiem.value = parsed.getHours() >= 12 ? 'PM' : 'AM';
};

// Sincronizar cuando el modelo cambia externamente
watch(
    () => props.modelValue,
    () => {
        syncFromModel();
    },
);

// Sincronizar estado interno cuando se abre el picker
watch(open, (isOpen) => {
    if (isOpen) syncFromModel();
});

const publish = () => {
    emit('update:modelValue', toIsoDateTimeLocal(currentAsDate()));
    open.value = false;
};

const clear = () => {
    emit('update:modelValue', null);
    open.value = false;
};

// Solo actualiza el día seleccionado visualmente, sin publicar ni cerrar
const pickDay = (d: Date) => {
    selectedDay.value = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    viewMonth.value = d.getMonth();
    viewYear.value = d.getFullYear();
};

const goPrevMonth = () => {
    if (viewMonth.value === 0) {
        viewMonth.value = 11;
        viewYear.value -= 1;
        return;
    }

    viewMonth.value -= 1;
};

const goNextMonth = () => {
    if (viewMonth.value === 11) {
        viewMonth.value = 0;
        viewYear.value += 1;
        return;
    }

    viewMonth.value += 1;
};

const calendarDays = computed(() => {
    const first = new Date(viewYear.value, viewMonth.value, 1);
    const firstWeekday = (first.getDay() + 6) % 7;
    const daysInMonth = new Date(
        viewYear.value,
        viewMonth.value + 1,
        0,
    ).getDate();
    const daysInPrevMonth = new Date(
        viewYear.value,
        viewMonth.value,
        0,
    ).getDate();

    const cells: Array<{ date: Date; inCurrentMonth: boolean }> = [];

    for (let i = firstWeekday - 1; i >= 0; i--) {
        cells.push({
            date: new Date(
                viewYear.value,
                viewMonth.value - 1,
                daysInPrevMonth - i,
            ),
            inCurrentMonth: false,
        });
    }

    for (let d = 1; d <= daysInMonth; d++) {
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

const isSelected = (d: Date) =>
    d.toDateString() === selectedDay.value.toDateString();

// "Hoy" actualiza los refs pero no cierra — el usuario confirma con Aplicar
const selectToday = () => {
    const now = new Date();

    selectedDay.value = new Date(
        now.getFullYear(),
        now.getMonth(),
        now.getDate(),
    );
    hour12.value = ((now.getHours() + 11) % 12) + 1;
    minute.value = now.getMinutes();
    meridiem.value = now.getHours() >= 12 ? 'PM' : 'AM';
    viewMonth.value = now.getMonth();
    viewYear.value = now.getFullYear();
};
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                class="h-11 w-full justify-start overflow-hidden rounded-2xl border-input bg-card px-3 text-left font-normal shadow-sm transition-all duration-200 hover:border-primary focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary/20"
            >
                <CalendarClock class="mr-2 h-4 w-4 shrink-0" />

                <span
                    class="min-w-0 truncate"
                    :class="modelValue ? 'text-foreground' : 'text-muted-foreground'"
                >
                    {{ triggerLabel }}
                </span>
            </Button>
        </PopoverTrigger>

        <PopoverContent
            align="start"
            side="bottom"
            class="z-[80] w-[min(calc(100vw-2rem),21rem)] rounded-[1.35rem] border border-border bg-popover p-3 shadow-2xl"
        >
            <div class="space-y-3">
                <!-- Navegación de mes -->
                <div class="flex items-center justify-between gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        class="h-9 w-9 rounded-xl border-input bg-card transition hover:border-primary hover:text-primary"
                        @click="goPrevMonth"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </Button>

                    <p
                        class="truncate px-2 text-center text-sm font-semibold capitalize text-foreground"
                    >
                        {{ monthLabel }}
                    </p>

                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        class="h-9 w-9 rounded-xl border-input bg-card transition hover:border-primary hover:text-primary"
                        @click="goNextMonth"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </div>

                <!-- Días de la semana -->
                <div
                    class="grid grid-cols-7 gap-1 text-center text-[11px] font-medium text-muted-foreground"
                >
                    <span v-for="day in weekDays" :key="day">
                        {{ day }}
                    </span>
                </div>

                <!-- Celdas del calendario -->
                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="cell in calendarDays"
                        :key="cell.date.toISOString()"
                        type="button"
                        class="grid h-8 w-8 place-items-center rounded-xl text-xs transition"
                        :class="[
                            cell.inCurrentMonth
                                ? 'text-foreground hover:bg-muted'
                                : 'text-muted-foreground hover:bg-muted',
                            isSelected(cell.date)
                                ? 'bg-[color:var(--primary)] text-white hover:bg-[color:var(--primary-hover)]'
                                : '',
                        ]"
                        @click="pickDay(cell.date)"
                    >
                        {{ cell.date.getDate() }}
                    </button>
                </div>

                <!-- Selector de hora -->
                <div class="rounded-xl bg-muted/60 p-2">
                    <p class="mb-2 text-[11px] font-medium text-muted-foreground">
                        Hora
                    </p>
                    <div class="grid grid-cols-[1fr_1fr_90px] gap-2">
                        <SearchableSelect
                            v-model="hour12"
                            :options="hourOptions"
                            placeholder="Hr"
                        />

                        <SearchableSelect
                            v-model="minute"
                            :options="minuteOptions"
                            placeholder="Min"
                        />

                        <SearchableSelect
                            v-model="meridiem"
                            :options="meridiemOptions"
                            placeholder="AM/PM"
                        />
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex items-center justify-between gap-2 pt-1">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-9 rounded-xl border-input bg-card px-3 transition hover:border-primary hover:text-primary"
                        @click="selectToday"
                    >
                        Hoy
                    </Button>

                    <div class="flex gap-2">
                        <Button
                            v-if="clearable && modelValue"
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-9 rounded-xl border-input bg-card px-3 transition hover:border-red-300 hover:text-red-600"
                            @click="clear"
                        >
                            <X class="mr-1 h-3.5 w-3.5" />
                            Limpiar
                        </Button>

                        <Button
                            type="button"
                            size="sm"
                            class="h-9 rounded-xl px-4 shadow-sm transition hover:-translate-y-0.5"
                            :style="{
                                backgroundColor: 'var(--primary)',
                                color: 'var(--primary-foreground)',
                            }"
                            @click="publish"
                        >
                            Aplicar
                        </Button>
                    </div>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
