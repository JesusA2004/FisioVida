import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

type Edition = 'demo' | 'paid'

export function useEdition() {
  const page = usePage<any>()
  const edition = computed<Edition>(() => page.props?.app?.edition ?? 'demo')
  const isPaid = computed(() => edition.value === 'paid')
  return { edition, isPaid }
}
