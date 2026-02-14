import { ref, watch } from 'vue'

export type LiveSearchState<T> = {
  q: ReturnType<typeof ref<string>>
  results: ReturnType<typeof ref<T[]>>
  loading: ReturnType<typeof ref<boolean>>
  error: ReturnType<typeof ref<string | null>>
  run: (q?: string) => Promise<void>
}

export function useLiveSearch<T>(opts: {
  minChars?: number
  fetcher: (q: string, signal: AbortSignal) => Promise<T[]>
}) : LiveSearchState<T> {
  const minChars = opts.minChars ?? 1

  const q = ref('')
  const results = ref<T[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  let ctrl: AbortController | null = null

  const run = async (overrideQ?: string) => {
    const query = (overrideQ ?? q.value).trim()
    if (query.length < minChars) {
      results.value = []
      error.value = null
      loading.value = false
      if (ctrl) ctrl.abort()
      return
    }

    if (ctrl) ctrl.abort()
    ctrl = new AbortController()

    loading.value = true
    error.value = null

    try {
      const data = await opts.fetcher(query, ctrl.signal)
      results.value = Array.isArray(data) ? data : []
    } catch (e: any) {
      if (e?.name === 'AbortError') return
      error.value = 'No se pudo buscar. Intenta de nuevo.'
      results.value = []
    } finally {
      loading.value = false
    }
  }

  watch(q, () => {
    // en tiempo real: corre en cada keystroke
    void run()
  })

  return { q, results, loading, error, run }
}
