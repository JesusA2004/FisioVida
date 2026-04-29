<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { ChevronLeft, ChevronRight, Rows3, ArrowRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

type PerPageOption = number | 'all';

type PageMeta = {
    current_page?: number | null;
    last_page?: number | null;
    per_page?: number | string | null;
    per_page_selected?: number | string | null;
    from?: number | null;
    to?: number | null;
    total?: number | null;
};

const props = withDefaults(
    defineProps<{
        page: PageMeta;
        itemLabel?: string;
        perPageOptions?: PerPageOption[];
        showPerPage?: boolean;
        showGoTo?: boolean;
    }>(),
    {
        itemLabel: 'registros',
        perPageOptions: () => [10, 15, 20, 50, 'all'],
        showPerPage: true,
        showGoTo: true,
    },
);

const emit = defineEmits<{
    (e: 'change', page: number): void;
    (e: 'per-page-change', perPage: PerPageOption): void;
}>();

const currentPage = computed(() => Number(props.page.current_page ?? 1));
const lastPage = computed(() => Math.max(1, Number(props.page.last_page ?? 1)));
const total = computed(() => Number(props.page.total ?? 0));
const from = computed(() => Number(props.page.from ?? 0));
const to = computed(() => Number(props.page.to ?? 0));

const selectedPerPageValue = computed(() => {
    const selected = props.page.per_page_selected;

    if (selected === 'all') return 'all';

    if (selected !== null && selected !== undefined) {
        return String(selected);
    }

    return String(props.page.per_page ?? 10);
});

const goToPage = ref<number>(currentPage.value);
const selectedPerPage = ref<string>(selectedPerPageValue.value);

watch(
    () => props.page.current_page,
    (value) => {
        goToPage.value = Number(value ?? 1);
    },
);

watch(selectedPerPageValue, (value) => {
    selectedPerPage.value = value;
});

const hasMultiplePages = computed(() => lastPage.value > 1);
const canPrev = computed(() => currentPage.value > 1);
const canNext = computed(() => currentPage.value < lastPage.value);

const perPageLabel = (value: PerPageOption) =>
    value === 'all' ? 'Todos' : String(value);

const perPageValue = (value: PerPageOption) =>
    value === 'all' ? 'all' : String(value);

const pages = computed<(number | 'dots')[]>(() => {
    const current = currentPage.value;
    const last = lastPage.value;

    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    if (current <= 4) {
        return [1, 2, 3, 4, 'dots', last];
    }

    if (current >= last - 3) {
        return [1, 'dots', last - 3, last - 2, last - 1, last];
    }

    return [1, 'dots', current - 1, current, current + 1, 'dots', last];
});

const changePage = (page: number) => {
    const normalized = Math.min(Math.max(1, Number(page)), lastPage.value);

    if (normalized === currentPage.value) return;

    emit('change', normalized);
};

const submitGoTo = () => {
    changePage(goToPage.value);
};

const changePerPage = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;
    const normalized: PerPageOption = value === 'all' ? 'all' : Number(value);

    selectedPerPage.value = value;
    emit('per-page-change', normalized);
};
</script>

<template>
    <div
        v-if="total > 0"
        class="rounded-[2rem] border border-border bg-card/90 p-4 shadow-sm transition-all duration-300 hover:shadow-lg dark:bg-card/80"
    >
        <div
            class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
        >
            <!-- Resumen -->
            <div class="min-w-0">
                <p class="text-sm text-muted-foreground">
                    Mostrando
                    <span class="font-semibold text-foreground">
                        {{ from }}-{{ to }}
                    </span>
                    de
                    <span class="font-semibold text-foreground">
                        {{ total }}
                    </span>
                    {{ itemLabel }}
                </p>

                <p class="mt-1 text-xs text-muted-foreground">
                    Página {{ currentPage }} de {{ lastPage }}
                </p>
            </div>

            <!-- Navegación -->
            <div
                class="flex w-full flex-col gap-3 xl:w-auto xl:flex-row xl:items-center"
            >
                <div
                    class="flex flex-wrap items-center gap-2 rounded-[1.4rem] border border-border bg-background/70 p-1.5 shadow-inner"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        class="h-10 rounded-2xl px-4 text-sm font-semibold transition-all duration-200 hover:-translate-y-0.5 hover:bg-accent hover:text-accent-foreground disabled:hover:translate-y-0"
                        :disabled="!canPrev"
                        @click="changePage(currentPage - 1)"
                    >
                        <ChevronLeft class="mr-1 h-4 w-4" />
                        Atrás
                    </Button>

                    <template
                        v-for="(pageNumber, index) in pages"
                        :key="`${pageNumber}-${index}`"
                    >
                        <span
                            v-if="pageNumber === 'dots'"
                            class="grid h-10 min-w-8 place-items-center text-sm font-semibold text-muted-foreground"
                        >
                            ...
                        </span>

                        <button
                            v-else
                            type="button"
                            class="grid h-10 min-w-10 place-items-center rounded-2xl text-sm font-semibold transition-all duration-200 hover:-translate-y-0.5"
                            :class="
                                pageNumber === currentPage
                                    ? 'bg-primary text-primary-foreground shadow-md shadow-primary/25'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                            "
                            @click="changePage(pageNumber)"
                        >
                            {{ pageNumber }}
                        </button>
                    </template>

                    <Button
                        type="button"
                        variant="ghost"
                        class="h-10 rounded-2xl px-4 text-sm font-semibold transition-all duration-200 hover:-translate-y-0.5 hover:bg-accent hover:text-accent-foreground disabled:hover:translate-y-0"
                        :disabled="!canNext"
                        @click="changePage(currentPage + 1)"
                    >
                        Siguiente
                        <ChevronRight class="ml-1 h-4 w-4" />
                    </Button>
                </div>

                <!-- Controles derechos -->
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:items-center xl:justify-end"
                >
                    <div
                        v-if="showPerPage"
                        class="flex items-center justify-between gap-2 rounded-[1.4rem] border border-border bg-background/70 px-3 py-2 shadow-sm"
                    >
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <span
                                class="grid h-8 w-8 place-items-center rounded-xl bg-primary/10 text-primary"
                            >
                                <Rows3 class="h-4 w-4" />
                            </span>
                            <span>Por página</span>
                        </div>

                        <select
                            :value="selectedPerPage"
                            class="h-9 rounded-xl border border-border bg-card px-3 text-sm font-semibold text-foreground outline-none transition-all duration-200 hover:border-primary/50 focus:border-primary focus:ring-2 focus:ring-primary/20"
                            @change="changePerPage"
                        >
                            <option
                                v-for="option in perPageOptions"
                                :key="perPageValue(option)"
                                :value="perPageValue(option)"
                            >
                                {{ perPageLabel(option) }}
                            </option>
                        </select>
                    </div>

                    <form
                        v-if="showGoTo && hasMultiplePages"
                        class="flex items-center justify-between gap-2 rounded-[1.4rem] border border-border bg-background/70 px-3 py-2 shadow-sm"
                        @submit.prevent="submitGoTo"
                    >
                        <label class="text-sm font-medium text-muted-foreground">
                            Página
                        </label>

                        <input
                            v-model.number="goToPage"
                            type="number"
                            min="1"
                            :max="lastPage"
                            class="h-9 w-16 rounded-xl border border-border bg-card px-2 text-center text-sm font-semibold text-foreground outline-none transition-all duration-200 hover:border-primary/50 focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />

                        <Button
                            type="submit"
                            class="h-9 rounded-xl px-3 shadow-sm transition-all duration-200 hover:-translate-y-0.5"
                        >
                            Ir
                            <ArrowRight class="ml-1 h-4 w-4" />
                        </Button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
