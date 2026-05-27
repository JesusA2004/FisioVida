<script setup lang="ts">
import { computed, ref } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { Check, ChevronsUpDown, Search, X } from 'lucide-vue-next'

export type SelectOption = { value: string | number | null; label: string }

const props = withDefaults(defineProps<{
  modelValue: string | number | null
  options: SelectOption[]
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  clearable?: boolean
  disabled?: boolean
}>(), {
  placeholder: 'Seleccionar',
  searchPlaceholder: 'Buscar...',
  emptyText: 'Sin resultados',
  clearable: false,
  disabled: false,
})

const emit = defineEmits<{ (e: 'update:modelValue', v: string | number | null): void }>()

const open = ref(false)
const query = ref('')
const root = ref<HTMLElement | null>(null)
onClickOutside(root, () => (open.value = false))

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return props.options
  return props.options.filter((opt) => opt.label.toLowerCase().includes(q))
})

const selected = computed(() => props.options.find((opt) => opt.value === props.modelValue) ?? null)

const select = (value: string | number | null) => {
  emit('update:modelValue', value)
  open.value = false
  query.value = ''
}
</script>

<template>
  <div ref="root" class="relative w-full">
    <button
      type="button"
      :disabled="disabled"
      class="flex h-10 w-full items-center justify-between rounded-xl border border-input bg-card px-3 text-sm shadow-sm transition hover:border-primary/50 disabled:opacity-60"
      @click="open = !open"
    >
      <span class="truncate" :class="selected ? 'text-foreground' : 'text-muted-foreground'">
        {{ selected?.label || placeholder }}
      </span>
      <ChevronsUpDown class="h-4 w-4 text-muted-foreground" />
    </button>

    <div v-if="open" class="absolute z-50 mt-2 w-full rounded-2xl border border-border bg-popover p-2 shadow-xl">
      <div class="relative mb-2">
        <Search class="pointer-events-none absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
        <input
          v-model="query"
          :placeholder="searchPlaceholder"
          class="h-9 w-full rounded-lg border border-input bg-card pl-8 pr-2 text-sm text-foreground outline-none placeholder:text-muted-foreground"
        />
      </div>

      <div class="max-h-56 overflow-auto">
        <button
          v-for="opt in filtered"
          :key="String(opt.value) + opt.label"
          type="button"
          class="flex w-full items-center justify-between rounded-lg px-2 py-2 text-left text-sm text-foreground transition hover:bg-muted"
          @click="select(opt.value)"
        >
          <span>{{ opt.label }}</span>
          <Check v-if="opt.value === modelValue" class="h-4 w-4 text-emerald-500" />
        </button>
        <p v-if="!filtered.length" class="px-2 py-2 text-sm text-muted-foreground">{{ emptyText }}</p>
      </div>

      <button
        v-if="clearable && modelValue !== null && modelValue !== ''"
        type="button"
        class="mt-2 flex w-full items-center justify-center rounded-lg border border-border px-2 py-1.5 text-xs text-muted-foreground hover:bg-muted"
        @click="select(null)"
      >
        <X class="mr-1 h-3.5 w-3.5" /> Limpiar
      </button>
    </div>
  </div>
</template>
