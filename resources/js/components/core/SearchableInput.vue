<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useLiveSearch } from '@/composables/useLiveSearch'
import { X, Search } from 'lucide-vue-next'

export type SearchItem = {
  id: string | number
  label: string
  sublabel?: string
  meta?: any
}

const props = withDefaults(defineProps<{
  modelValue: string
  items?: SearchItem[]
  placeholder?: string
  minChars?: number
  maxResults?: number
  fetcher?: (q: string, signal: AbortSignal) => Promise<SearchItem[]>
}>(), {
  items: () => [],
  placeholder: 'Buscar...',
  minChars: 1,
  maxResults: 8,
})

const emit = defineEmits<{
  (e: 'update:modelValue', v: string): void
  (e: 'select', item: SearchItem): void
  (e: 'clear'): void
}>()

const root = ref<HTMLElement | null>(null)
const input = ref<HTMLInputElement | null>(null)
const open = ref(false)
const active = ref(-1)

const localMode = computed(() => !props.fetcher)

const q = computed({
  get: () => props.modelValue ?? '',
  set: (v: string) => emit('update:modelValue', v),
})

const localFiltered = computed(() => {
  const query = q.value.trim().toLowerCase()
  if (query.length < props.minChars) return []
  const out = (props.items ?? [])
    .filter(i => (i.label ?? '').toLowerCase().includes(query) || (i.sublabel ?? '').toLowerCase().includes(query))
    .slice(0, props.maxResults)
  return out
})

const remote = props.fetcher
  ? useLiveSearch<SearchItem>({
      minChars: props.minChars,
      fetcher: props.fetcher,
    })
  : null

const results = computed<SearchItem[]>(() => {
  if (localMode.value) return localFiltered.value
  return remote?.results.value ?? []
})

const loading = computed(() => remote?.loading.value ?? false)
const error = computed(() => remote?.error.value ?? null)

watch(() => props.modelValue, (v) => {
  if (!v || v.trim().length < props.minChars) {
    open.value = false
    active.value = -1
  } else {
    open.value = true
  }
})

watch(results, () => {
  active.value = results.value.length ? 0 : -1
})

const pick = (item: SearchItem) => {
  emit('select', item)
  open.value = false
}

const clear = async () => {
  emit('update:modelValue', '')
  emit('clear')
  open.value = false
  active.value = -1
  await nextTick()
  input.value?.focus()
}

const onKeydown = (e: KeyboardEvent) => {
  if (!open.value && (e.key === 'ArrowDown' || e.key === 'Enter')) open.value = true
  if (!open.value) return

  if (e.key === 'Escape') {
    open.value = false
    return
  }

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    active.value = Math.min(active.value + 1, results.value.length - 1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    active.value = Math.max(active.value - 1, 0)
  } else if (e.key === 'Enter') {
    const item = results.value[active.value]
    if (item) pick(item)
  }
}

const onFocus = () => {
  if (q.value.trim().length >= props.minChars) open.value = true
}

const onClickOutside = (ev: MouseEvent) => {
  if (!root.value) return
  if (!root.value.contains(ev.target as Node)) open.value = false
}

onMounted(() => {
  document.addEventListener('click', onClickOutside)
})

watch(() => q.value, (val) => {
  if (!props.fetcher || !remote) return
  remote.q.value = val
})
</script>

<template>
  <div ref="root" class="relative">
    <div class="flex items-center gap-2 fv-input">
      <Search class="h-4 w-4 text-slate-400 shrink-0" />
      <input
        ref="input"
        :value="modelValue"
        :placeholder="placeholder"
        class="w-full bg-transparent outline-none text-sm"
        @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        @focus="onFocus"
        @keydown="onKeydown"
      />
      <button v-if="modelValue" type="button" class="rounded-lg p-1 hover:bg-white/5" @click="clear">
        <X class="h-4 w-4 text-slate-300" />
      </button>
    </div>

    <div
      v-if="open"
      class="absolute z-50 mt-2 w-full fv-card overflow-hidden"
    >
      <div v-if="loading" class="px-3 py-2 text-sm text-slate-300">Buscando...</div>
      <div v-else-if="error" class="px-3 py-2 text-sm text-red-300">{{ error }}</div>
      <div v-else-if="!results.length" class="px-3 py-2 text-sm text-slate-400">Sin resultados</div>

      <button
        v-for="(item, idx) in results"
        :key="item.id"
        type="button"
        class="w-full text-left px-3 py-2 transition"
        :class="idx === active ? 'bg-white/6' : 'hover:bg-white/4'"
        @mouseenter="active = idx"
        @click="pick(item)"
      >
        <div class="text-sm font-semibold text-slate-100">{{ item.label }}</div>
        <div v-if="item.sublabel" class="text-xs text-slate-400">{{ item.sublabel }}</div>
      </button>
    </div>
  </div>
</template>
