<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { format } from 'date-fns'
import { es } from 'date-fns/locale'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Spinner } from '@/components/ui/spinner'

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Calendar } from '@/components/ui/calendar'

import { Plus, Search, X, Pencil, Trash2, Calendar as CalendarIcon, Sparkles, Filter } from 'lucide-vue-next'

import FvPageShell from '@/components/fv/FvPageShell.vue'
import type { CrudColumn, CrudField, PageMeta } from '@/components/fv/crud.types'

import { confirmDanger } from '@/lib/fvSwal'
import { swalToast, swalErr } from '@/lib/swal'

type AnyRow = any
type AnyForm = any

const props = withDefaults(
  defineProps<{
    title: string
    subtitle?: string
    createLabel?: string

    /** routeBase = 'appointments' -> appointments.index/store/update/destroy */
    routeBase?: string

    /** nombres exactos si no quieres convención */
    routes?: Partial<{
      index: string
      store: string
      update: string
      destroy: string
    }>

    rows: AnyRow[]
    page: PageMeta
    filters: Record<string, any>

    /** Nota: any para evitar broncas TS (CrudColumn<LogRow> vs CrudColumn<AnyRow>) */
    columns: CrudColumn<any>[]

    fields: CrudField<any>[]
    defaults: AnyForm
    toForm: (row: AnyRow) => AnyForm

    /** Param para update/destroy (ids compuestos, uuid, etc) */
    routeParam?: (row: AnyRow) => any

    /** Key única para v-for (si no es row.id) */
    rowKey?: (row: AnyRow) => string | number

    searchable?: boolean
    statusOptions?: { value: string; label: string }[]
    extraFilters?: { key: string; label: string; options: { value: string; label: string }[] }[]

    forceFormData?: boolean
    modalMaxWidthClass?: string

    /** control de permisos UI (útil para Logs) */
    canCreate?: boolean
    canEdit?: boolean
    canDelete?: boolean
  }>(),
  {
    searchable: true,
    canCreate: true,
    canEdit: true,
    canDelete: true,
  }
)

/** =========================
 *  Ziggy route() SAFE
 *  =========================
 *  El error real era: route NO existe en runtime.
 *  Aquí usamos window.route y si no está, mostramos Swal y no crasheamos.
 */
const ziggyRoute = (name: string, params?: any) => {
  const fn = (window as any).route as undefined | ((n: string, p?: any) => string)
  if (typeof fn !== 'function') return null
  try {
    return fn(name, params)
  } catch {
    return null
  }
}

const requireUrl = (name: string, params?: any) => {
  const u = ziggyRoute(name, params)
  if (!u) {
    swalErr(
      'Rutas no disponibles',
      "No existe route() en el frontend. Activa Ziggy (por ejemplo: @routes en tu layout Blade) y recarga."
    )
    return null
  }
  return u
}

const routeName = computed(() => {
  const base = props.routeBase ?? ''
  return {
    index: props.routes?.index ?? (base ? `${base}.index` : ''),
    store: props.routes?.store ?? (base ? `${base}.store` : ''),
    update: props.routes?.update ?? (base ? `${base}.update` : ''),
    destroy: props.routes?.destroy ?? (base ? `${base}.destroy` : ''),
  }
})

const hasIndex = computed(() => Boolean(routeName.value.index))
const hasStore = computed(() => Boolean(routeName.value.store))
const hasUpdate = computed(() => Boolean(routeName.value.update))
const hasDestroy = computed(() => Boolean(routeName.value.destroy))

/** =========================
 *  State
 *  ========================= */
const dialogOpen = ref(false)
const editingRow = ref<AnyRow | null>(null)

const local = ref({
  q: props.filters?.q ?? '',
  status: props.filters?.status ?? '',
})

const hasStatus = computed(() => props.fields.some(f => String(f.key) === 'status'))

const form = useForm<AnyForm>({ ...(props.defaults as any) })
const isBusy = computed(() => form.processing)

const modalMax = computed(() => props.modalMaxWidthClass ?? 'max-w-4xl')

const activeFilters = computed(() => {
  const q = String(local.value.q ?? '').trim()
  const s = String(local.value.status ?? '').trim()
  const extra = (props.extraFilters ?? []).some(f => {
    const v = props.filters?.[f.key]
    return v !== undefined && v !== null && String(v).trim() !== ''
  })
  return q !== '' || s !== '' || extra
})

/** =========================
 *  Helpers
 *  ========================= */
const getRowKey = (row: AnyRow) => {
  if (props.rowKey) return props.rowKey(row)
  return String(row?.id ?? JSON.stringify(row))
}

const getRouteParam = (row: AnyRow) => {
  if (props.routeParam) return props.routeParam(row)
  return row?.id
}

/** =========================
 *  Open / Close
 *  ========================= */
const openCreate = () => {
  if (!props.canCreate) return
  editingRow.value = null
  form.defaults({ ...(props.defaults as any) })
  form.reset()
  dialogOpen.value = true
}

const openEdit = (row: AnyRow) => {
  if (!props.canEdit) return
  editingRow.value = row
  const v = props.toForm(row)
  form.defaults({ ...(props.defaults as any) })
  form.reset()
  Object.assign(form, v)
  dialogOpen.value = true
}

const closeModal = () => {
  dialogOpen.value = false
}

/** =========================
 *  Date / Datetime (shadcn Calendar)
 *  ========================= */
const pad2 = (n: number) => String(n).padStart(2, '0')
const toDateInput = (d: Date) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`

const formatShort = (d: Date) => format(d, 'dd/MM/yyyy', { locale: es })

const getDateObj = (key: string) => {
  const raw = String((form as any)[key] ?? '').trim()
  if (!raw) return undefined
  // date: YYYY-MM-DD
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
    const [y, m, dd] = raw.split('-').map(Number)
    const d = new Date(y, (m ?? 1) - 1, dd ?? 1)
    return isNaN(d.getTime()) ? undefined : d
  }
  // datetime: YYYY-MM-DDTHH:mm
  if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(raw)) {
    const [datePart] = raw.split('T')
    const [y, m, dd] = datePart.split('-').map(Number)
    const d = new Date(y, (m ?? 1) - 1, dd ?? 1)
    return isNaN(d.getTime()) ? undefined : d
  }
  return undefined
}

const setDateValue = (key: string, d: Date) => {
  ;(form as any)[key] = toDateInput(d)
}

const getTimePart = (key: string) => {
  const raw = String((form as any)[key] ?? '').trim()
  if (!raw) return ''
  const t = raw.includes('T') ? raw.split('T')[1] : ''
  return (t ?? '').slice(0, 5)
}

const setDateTimeDate = (key: string, d: Date) => {
  const dateStr = toDateInput(d)
  const time = getTimePart(key) || '09:00'
  ;(form as any)[key] = `${dateStr}T${time}`
}

const setDateTimeTime = (key: string, hhmm: string) => {
  const raw = String((form as any)[key] ?? '').trim()
  const dateStr =
    raw && raw.includes('T')
      ? raw.split('T')[0]
      : raw && /^\d{4}-\d{2}-\d{2}$/.test(raw)
        ? raw
        : toDateInput(new Date())

  const t = String(hhmm ?? '').trim()
  if (!/^\d{2}:\d{2}$/.test(t)) {
    ;(form as any)[key] = `${dateStr}T09:00`
    return
  }
  ;(form as any)[key] = `${dateStr}T${t}`
}

/** =========================
 *  Submit
 *  ========================= */
const submit = () => {
  if (!hasStore.value) {
    swalErr('Rutas no configuradas', 'Define routeBase o routes.* para este CRUD.')
    return
  }

  const opts: any = { preserveScroll: true }
  if (props.forceFormData) opts.forceFormData = true

  const onOk = () => {
    closeModal()
    swalToast('Guardado', 'success')
  }
  const onFail = () => swalToast('Revisa los campos', 'warning')

  if (editingRow.value) {
    if (!hasUpdate.value) {
      swalErr('Ruta update faltante', 'Configura routes.update o routeBase.update.')
      return
    }
    const u = requireUrl(routeName.value.update, getRouteParam(editingRow.value))
    if (!u) return
    form.put(u, { ...opts, onSuccess: onOk, onError: onFail })
  } else {
    const u = requireUrl(routeName.value.store)
    if (!u) return
    form.post(u, { ...opts, onSuccess: onOk, onError: onFail })
  }
}

/** =========================
 *  Destroy
 *  ========================= */
const destroyRow = async (row: AnyRow) => {
  if (!props.canDelete) return
  if (!hasDestroy.value) {
    swalErr('Rutas no configuradas', 'Define routeBase o routes.* para este CRUD.')
    return
  }

  const ok = await confirmDanger({
    title: 'Eliminar registro',
    text: 'Se eliminará de forma permanente.',
  })
  if (!ok) return

  const u = requireUrl(routeName.value.destroy, getRouteParam(row))
  if (!u) return

  router.delete(u, {
    preserveScroll: true,
    onSuccess: () => swalToast('Eliminado', 'success'),
  })
}

/** =========================
 *  Filters / Pagination
 *  ========================= */
let t: any = null
watch(
  () => local.value.q,
  () => {
    if (!props.searchable) return
    if (!hasIndex.value) return
    const u = ziggyRoute(routeName.value.index)
    if (!u) return

    clearTimeout(t)
    t = setTimeout(() => {
      router.get(
        u,
        { ...props.filters, q: local.value.q },
        { preserveState: true, replace: true, preserveScroll: true }
      )
    }, 350)
  }
)

watch(
  () => local.value.status,
  () => {
    if (!hasStatus.value) return
    if (!hasIndex.value) return
    const u = ziggyRoute(routeName.value.index)
    if (!u) return

    router.get(
      u,
      { ...props.filters, status: local.value.status },
      { preserveState: true, replace: true, preserveScroll: true }
    )
  }
)

const go = (url: string | null | undefined) => {
  if (!url) return
  router.visit(url, { preserveState: true, preserveScroll: true })
}

const resetFilters = () => {
  local.value.q = ''
  local.value.status = ''
  if (!hasIndex.value) return
  const u = requireUrl(routeName.value.index)
  if (!u) return
  router.get(u, {}, { preserveState: false, replace: true, preserveScroll: true })
}
</script>

<template>
  <FvPageShell>
    <div class="w-full max-w-none">
      <!-- Header -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-semibold tracking-tight truncate">
              {{ title }}
            </h1>

            <span
              v-if="activeFilters"
              class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium
                     bg-accent/40 text-accent-foreground ring-1 ring-border/40
                     dark:bg-accent/25"
            >
              <Sparkles class="h-3.5 w-3.5" />
              Búsqueda activa
            </span>
          </div>

          <p v-if="subtitle" class="mt-1 text-sm text-muted-foreground">
            {{ subtitle }}
          </p>

          <div class="mt-2 text-xs text-muted-foreground">
            <span v-if="page?.total">Total: {{ page.total }}</span>
            <span v-if="page?.total" class="mx-2 opacity-50">·</span>
            <span v-if="page?.total">Mostrando {{ page.from ?? 0 }}–{{ page.to ?? 0 }}</span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Button variant="secondary" class="rounded-full shadow-sm" @click="resetFilters">
            Limpiar
          </Button>

          <Button
            v-if="canCreate"
            class="gap-2 rounded-full px-4 shadow-sm transition will-change-transform hover:-translate-y-[1px] hover:shadow-md"
            @click="openCreate"
          >
            <Plus class="h-4 w-4" />
            <span>{{ createLabel ?? 'Nuevo' }}</span>
          </Button>
        </div>
      </div>

      <!-- Panel (FULL WIDTH, mejor en 2XL) -->
      <div class="relative mt-5 w-full rounded-3xl border border-border/50 shadow-sm overflow-hidden">
        <!-- Fondo premium light/dark -->
        <div
          aria-hidden="true"
          class="absolute inset-0
                 bg-gradient-to-br from-white via-white to-accent/20
                 dark:from-card dark:via-card dark:to-accent/10"
        />
        <div
          aria-hidden="true"
          class="absolute -top-24 -right-24 h-72 w-72 rounded-full blur-3xl opacity-40
                 bg-primary/20 dark:bg-primary/10"
        />
        <div class="relative p-4 sm:p-5 2xl:p-6">
          <!-- Filters row -->
          <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap w-full xl:w-auto">
              <div v-if="searchable" class="w-full sm:w-[380px] 2xl:w-[520px]">
                <Label class="text-xs text-muted-foreground">Buscar</Label>
                <div class="relative mt-1">
                  <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                  <Input v-model="local.q" placeholder="Nombre, correo, etc..." class="pl-10 pr-10 h-11 rounded-xl" />
                  <button
                    v-if="local.q"
                    class="absolute right-2 top-1/2 -translate-y-1/2 h-9 w-9 rounded-full hover:bg-accent/50 transition grid place-items-center"
                    type="button"
                    @click="local.q=''"
                    aria-label="Limpiar búsqueda"
                  >
                    <X class="h-4 w-4 text-muted-foreground" />
                  </button>
                </div>
              </div>

              <div v-if="hasStatus && statusOptions?.length" class="w-full sm:w-[240px] 2xl:w-[280px]">
                <Label class="text-xs text-muted-foreground">Estatus</Label>
                <select
                  v-model="local.status"
                  class="mt-1 h-11 w-full rounded-xl bg-white/70 dark:bg-background px-3 text-sm outline-none
                         ring-1 ring-border/40 focus:ring-2 focus:ring-primary/60 transition"
                >
                  <option value="">Todos</option>
                  <option v-for="o in statusOptions" :key="o.value" :value="o.value">
                    {{ o.label }}
                  </option>
                </select>
              </div>

              <div v-for="f in extraFilters ?? []" :key="f.key" class="w-full sm:w-[240px] 2xl:w-[280px]">
                <Label class="text-xs text-muted-foreground">
                  <span class="inline-flex items-center gap-2">
                    <Filter class="h-3.5 w-3.5" />
                    {{ f.label }}
                  </span>
                </Label>

                <select
                  class="mt-1 h-11 w-full rounded-xl bg-white/70 dark:bg-background px-3 text-sm outline-none
                         ring-1 ring-border/40 focus:ring-2 focus:ring-primary/60 transition"
                  :value="filters?.[f.key] ?? ''"
                  @change="
                    hasIndex
                      ? (() => {
                          const u = (window as any).route?.(routeName.index)
                          if (!u) return
                          router.get(
                            u,
                            { ...filters, [f.key]: ($event.target as HTMLSelectElement).value },
                            { preserveState: true, replace: true, preserveScroll: true }
                          )
                        })()
                      : null
                  "
                >
                  <option value="">Todos</option>
                  <option v-for="o in f.options" :key="o.value" :value="o.value">
                    {{ o.label }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Pager -->
            <div class="flex items-center justify-between xl:justify-end gap-2">
              <Button
                variant="secondary"
                class="rounded-full shadow-sm"
                :disabled="!page?.prev"
                @click="go(page.prev)"
              >
                Atrás
              </Button>
              <Button
                variant="secondary"
                class="rounded-full shadow-sm"
                :disabled="!page?.next"
                @click="go(page.next)"
              >
                Siguiente
              </Button>
            </div>
          </div>

          <!-- Content -->
          <div class="mt-4">
            <!-- Mobile cards -->
            <div class="grid gap-3 lg:hidden">
              <div
                v-for="row in rows"
                :key="getRowKey(row)"
                class="rounded-3xl border border-border/50 bg-white/70 dark:bg-background/60 backdrop-blur-sm
                       p-4 transition hover:bg-accent/20"
              >
                <div class="grid gap-2">
                  <div
                    v-for="c in columns.filter(x => !x.hideOnMobile)"
                    :key="c.label"
                    class="flex items-start justify-between gap-3"
                  >
                    <div class="text-xs text-muted-foreground">
                      {{ c.label }}
                    </div>
                    <div class="text-sm text-right break-words max-w-[60%]">
                      {{ c.mobileValue ? c.mobileValue(row) : c.value(row) }}
                    </div>
                  </div>
                </div>

                <div class="mt-3 flex justify-end gap-2">
                  <Button
                    v-if="canEdit"
                    variant="secondary"
                    class="h-9 rounded-full px-3 gap-2 shadow-sm"
                    @click="openEdit(row)"
                  >
                    <Pencil class="h-4 w-4" />
                    Editar
                  </Button>
                  <Button
                    v-if="canDelete"
                    variant="destructive"
                    class="h-9 rounded-full px-3 gap-2 shadow-sm"
                    @click="destroyRow(row)"
                  >
                    <Trash2 class="h-4 w-4" />
                    Eliminar
                  </Button>
                </div>
              </div>

              <div
                v-if="!rows.length"
                class="rounded-3xl border border-dashed border-border/60 bg-white/70 dark:bg-background/60 backdrop-blur-sm
                       p-10 text-center"
              >
                <div class="text-sm font-medium">No hay registros</div>
                <div class="mt-1 text-sm text-muted-foreground">Crea el primero y deja la operación lista.</div>
                <div class="mt-4">
                  <Button v-if="canCreate" class="rounded-full gap-2 shadow-sm" @click="openCreate">
                    <Plus class="h-4 w-4" />
                    {{ createLabel ?? 'Nuevo' }}
                  </Button>
                </div>
              </div>
            </div>

            <!-- Desktop table -->
            <div class="hidden lg:block overflow-auto fv-no-scrollbar rounded-3xl border border-border/40 bg-white/70 dark:bg-background/50 backdrop-blur-sm">
              <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 text-xs text-muted-foreground bg-white/90 dark:bg-card/80 backdrop-blur">
                  <tr class="border-b border-border/30">
                    <th v-for="c in columns" :key="c.label" class="text-left font-medium py-3 px-4" :class="c.class">
                      {{ c.label }}
                    </th>
                    <th class="text-right font-medium py-3 px-4 w-[210px]">Acciones</th>
                  </tr>
                </thead>

                <tbody v-if="rows.length" class="divide-y divide-border/20">
                  <tr v-for="row in rows" :key="getRowKey(row)" class="transition hover:bg-accent/15">
                    <td v-for="c in columns" :key="c.label" class="py-3 px-4 align-top" :class="c.class">
                      {{ c.value(row) }}
                    </td>

                    <td class="py-3 px-4">
                      <div class="flex justify-end gap-2">
                        <Button
                          v-if="canEdit"
                          variant="secondary"
                          class="h-9 rounded-full px-3 gap-2 shadow-sm"
                          @click="openEdit(row)"
                        >
                          <Pencil class="h-4 w-4" />
                          Editar
                        </Button>
                        <Button
                          v-if="canDelete"
                          variant="destructive"
                          class="h-9 rounded-full px-3 gap-2 shadow-sm"
                          @click="destroyRow(row)"
                        >
                          <Trash2 class="h-4 w-4" />
                          Eliminar
                        </Button>
                      </div>
                    </td>
                  </tr>
                </tbody>

                <tbody v-else>
                  <tr>
                    <td :colspan="columns.length + 1" class="py-16 text-center text-sm text-muted-foreground">
                      No hay registros.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal (Dialog shadcn) -->
      <Dialog v-model:open="dialogOpen">
        <DialogContent
          class="w-[calc(100vw-2rem)] sm:w-full rounded-3xl p-0 overflow-hidden border border-border/50 shadow-2xl"
          :class="modalMax"
        >
          <DialogHeader class="p-5 border-b border-border/20 bg-white/90 dark:bg-card/90 backdrop-blur">
            <DialogTitle class="text-base font-semibold">
              {{ editingRow ? 'Editar' : 'Nuevo' }}
            </DialogTitle>
            <DialogDescription class="text-xs">
              Captura la información y guarda cambios.
            </DialogDescription>
          </DialogHeader>

          <form class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submit">
            <div
              v-for="f in fields"
              :key="String(f.key)"
              class="md:col-span-1"
              :class="(f.span ?? 1) === 2 || f.type === 'textarea' ? 'md:col-span-2' : ''"
            >
              <Label class="text-xs text-muted-foreground">{{ f.label }}</Label>

              <Input
                v-if="f.type === 'text'"
                v-model="(form as any)[f.key]"
                class="mt-1 h-11 rounded-xl"
                :placeholder="f.placeholder"
                :disabled="Boolean(f.readonly && editingRow)"
              />

              <textarea
                v-else-if="f.type === 'textarea'"
                v-model="(form as any)[f.key]"
                class="mt-1 min-h-[120px] w-full rounded-xl bg-white/70 dark:bg-background px-3 py-2 text-sm outline-none
                       ring-1 ring-border/40 focus:ring-2 focus:ring-primary/60 transition"
                :placeholder="f.placeholder"
                :disabled="Boolean(f.readonly && editingRow)"
              />

              <!-- DATE (Calendar shadcn) -->
              <div v-else-if="f.type === 'date'" class="mt-1">
                <Popover>
                  <PopoverTrigger as-child>
                    <Button
                      type="button"
                      variant="secondary"
                      class="h-11 w-full justify-start rounded-xl bg-white/70 dark:bg-background shadow-sm"
                      :disabled="Boolean(f.readonly && editingRow)"
                    >
                      <CalendarIcon class="mr-2 h-4 w-4 opacity-70" />
                      <span class="truncate">
                        {{
                          (form as any)[f.key]
                            ? formatShort(getDateObj(String(f.key)) ?? new Date())
                            : (f.placeholder ?? 'Selecciona fecha')
                        }}
                      </span>
                    </Button>
                  </PopoverTrigger>

                  <PopoverContent class="w-auto p-0" align="start">
                    <Calendar
                      mode="single"
                      :selected="getDateObj(String(f.key))"
                      @update:selected="(d: any) => d && setDateValue(String(f.key), d)"
                    />
                  </PopoverContent>
                </Popover>
              </div>

              <!-- DATETIME (Calendar + time) -->
              <div v-else-if="f.type === 'datetime'" class="mt-1">
                <Popover>
                  <PopoverTrigger as-child>
                    <Button
                      type="button"
                      variant="secondary"
                      class="h-11 w-full justify-start rounded-xl bg-white/70 dark:bg-background shadow-sm"
                      :disabled="Boolean(f.readonly && editingRow)"
                    >
                      <CalendarIcon class="mr-2 h-4 w-4 opacity-70" />
                      <span class="truncate">
                        {{
                          (form as any)[f.key]
                            ? (() => {
                                const d = getDateObj(String(f.key))
                                const t = getTimePart(String(f.key))
                                return d ? `${formatShort(d)} ${t ? '· ' + t : ''}` : (form as any)[f.key]
                              })()
                            : (f.placeholder ?? 'Selecciona fecha y hora')
                        }}
                      </span>
                    </Button>
                  </PopoverTrigger>

                  <PopoverContent class="w-auto p-0" align="start">
                    <Calendar
                      mode="single"
                      :selected="getDateObj(String(f.key))"
                      @update:selected="(d: any) => d && setDateTimeDate(String(f.key), d)"
                    />
                    <div class="border-t border-border/20 p-3">
                      <Label class="text-xs text-muted-foreground">Hora</Label>
                      <input
                        type="time"
                        class="mt-1 h-11 w-full rounded-xl bg-white/70 dark:bg-background px-3 text-sm outline-none
                               ring-1 ring-border/40 focus:ring-2 focus:ring-primary/60 transition"
                        :value="getTimePart(String(f.key))"
                        :disabled="Boolean(f.readonly && editingRow)"
                        @input="setDateTimeTime(String(f.key), ($event.target as HTMLInputElement).value)"
                      />
                    </div>
                  </PopoverContent>
                </Popover>
              </div>

              <Input
                v-else-if="f.type === 'number'"
                type="number"
                v-model="(form as any)[f.key]"
                class="mt-1 h-11 rounded-xl"
                :disabled="Boolean(f.readonly && editingRow)"
              />

              <div v-else-if="f.type === 'toggle'" class="mt-2 flex items-center gap-2">
                <Checkbox
                  :checked="Boolean((form as any)[f.key])"
                  @update:checked="(v: any) => ((form as any)[f.key] = v)"
                  :disabled="Boolean(f.readonly && editingRow)"
                />
                <span class="text-sm">{{ (f as any).toggleLabel ?? 'Activo' }}</span>
              </div>

              <select
                v-else-if="f.type === 'select'"
                v-model="(form as any)[f.key]"
                class="mt-1 h-11 w-full rounded-xl bg-white/70 dark:bg-background px-3 text-sm outline-none
                       ring-1 ring-border/40 focus:ring-2 focus:ring-primary/60 transition"
                :disabled="Boolean(f.readonly && editingRow)"
              >
                <option value="">Selecciona</option>
                <option v-for="o in (f.options ?? [])" :key="o.value" :value="o.value" :disabled="o.disabled">
                  {{ o.label }}
                </option>
              </select>

              <input
                v-else-if="f.type === 'file'"
                type="file"
                class="mt-2 block w-full text-sm text-muted-foreground
                       file:mr-3 file:rounded-full file:border-0 file:bg-accent/40 file:px-4 file:py-2
                       file:text-sm file:font-medium hover:file:bg-accent/60 transition"
                :accept="f.accept"
                @change="(e: any) => { (form as any)[f.key] = e.target?.files?.[0] ?? null }"
              />

              <div v-if="f.hint" class="mt-1 text-xs text-muted-foreground">
                {{ f.hint }}
              </div>

              <div v-if="(form as any).errors?.[f.key]" class="mt-1 text-xs text-destructive">
                {{ (form as any).errors?.[f.key] }}
              </div>
            </div>
          </form>

          <DialogFooter class="p-5 border-t border-border/20 bg-white/90 dark:bg-card/90 backdrop-blur">
            <div class="flex items-center justify-end gap-2 w-full">
              <Button type="button" variant="secondary" class="rounded-full shadow-sm" @click="closeModal">
                Cancelar
              </Button>
              <Button type="submit" class="rounded-full gap-2 shadow-sm" :disabled="isBusy" @click="submit">
                <Spinner v-if="isBusy" class="h-4 w-4" />
                Guardar
              </Button>
            </div>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </FvPageShell>
</template>
