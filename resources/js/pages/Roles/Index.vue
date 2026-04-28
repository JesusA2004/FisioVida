<script setup lang="ts">
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { useRolCrud, type PermissionOption, type RoleRow } from '@/composables/crud/useRolCrud'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { ShieldCheck, Search, Pencil, Trash2, CheckCheck, Eraser } from 'lucide-vue-next'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'

const props = defineProps<{
  rows: RoleRow[]
  page: { current_page: number; last_page: number; total: number }
  filters: { q?: string; status?: string }
  permissionsByModule: Record<string, PermissionOption[]>
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: '/roles' }]

const {
  form,
  isOpen,
  isEditing,
  openCreate,
  openEdit,
  closeModal,
  togglePermission,
  selectAllFromModule,
  clearModule,
  submit,
  destroyRole,
  permissionSearch,
  selectedCount,
} = useRolCrud()

const groupedPermissions = computed(() => Object.entries(props.permissionsByModule ?? {}))

const applyFilter = (extra: Record<string, string | number>) => {
  router.get(route('roles.index'), { ...props.filters, ...extra }, { preserveState: true, replace: true })
}

const goPage = (page: number) => applyFilter({ page })

const filteredBySearch = (permissions: PermissionOption[]) => {
  const q = permissionSearch.value.trim().toLowerCase()
  if (!q) return permissions

  return permissions.filter(item => item.name.toLowerCase().includes(q) || item.slug.toLowerCase().includes(q))
}
</script>

<template>
  <Head title="Roles" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="space-y-6 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Roles</h1>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">Asigna permisos por acción, agrupados por módulo, con control visual moderno.</p>
        </div>

        <Button class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl" @click="openCreate">
          <ShieldCheck class="mr-2 h-4 w-4" />
          Nuevo rol
        </Button>
      </div>

      <div class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/40">
        <div class="grid gap-3 md:grid-cols-3">
          <div class="relative md:col-span-2">
            <Search class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-zinc-400" />
            <Input class="pl-9" :default-value="props.filters.q ?? ''" placeholder="Buscar por nombre o slug" @change="(e) => applyFilter({ q: (e.target as HTMLInputElement).value, page: 1 })" />
          </div>
          <select class="h-10 rounded-xl border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-800 dark:bg-zinc-900" :value="props.filters.status ?? ''" @change="(e) => applyFilter({ status: (e.target as HTMLSelectElement).value, page: 1 })">
            <option value="">Todos los estados</option>
            <option value="active">Activo</option>
            <option value="inactive">Inactivo</option>
          </select>
        </div>
      </div>

      <div v-if="props.rows.length === 0" class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Sin roles para mostrar</h3>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Crea un rol nuevo o ajusta los filtros actuales.</p>
      </div>

      <div v-else class="grid gap-3">
        <article v-for="row in props.rows" :key="row.id" class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900/60">
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ row.name }}</h3>
                <Badge class="rounded-full px-3 py-1 text-xs" :class="row.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'">{{ row.status }}</Badge>
              </div>
              <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ row.slug }}</p>
              <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Permisos asignados: <strong>{{ row.permissions_count }}</strong></p>
            </div>
            <div class="flex flex-wrap gap-2">
              <Button variant="outline" class="rounded-xl" @click="openEdit(row)"><Pencil class="mr-2 h-4 w-4" />Editar</Button>
              <Button variant="destructive" class="rounded-xl" @click="destroyRole(row.id)"><Trash2 class="mr-2 h-4 w-4" />Eliminar</Button>
            </div>
          </div>
        </article>
      </div>

      <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/40">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Total: {{ props.page.total }}</p>
        <div class="flex gap-2">
          <Button variant="outline" :disabled="props.page.current_page <= 1" @click="goPage(props.page.current_page - 1)">Anterior</Button>
          <Button variant="outline" :disabled="props.page.current_page >= props.page.last_page" @click="goPage(props.page.current_page + 1)">Siguiente</Button>
        </div>
      </div>
    </section>

    <Dialog :open="isOpen" @update:open="closeModal">
      <DialogContent class="max-h-[90vh] max-w-5xl overflow-y-auto rounded-3xl border-none bg-white shadow-2xl dark:bg-zinc-950">
        <DialogHeader>
          <DialogTitle>{{ isEditing ? 'Editar rol' : 'Nuevo rol' }}</DialogTitle>
          <p class="text-xs text-zinc-500 dark:text-zinc-400">Permisos seleccionados: {{ selectedCount }}</p>
        </DialogHeader>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-2">
            <Label for="name">Nombre</Label>
            <Input id="name" v-model="form.name" />
            <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
          </div>
          <div class="space-y-2">
            <Label for="slug">Slug</Label>
            <Input id="slug" v-model="form.slug" />
            <p v-if="form.errors.slug" class="text-xs text-red-500">{{ form.errors.slug }}</p>
          </div>
          <div class="space-y-2 md:col-span-2">
            <Label for="description">Descripción</Label>
            <textarea id="description" v-model="form.description" class="min-h-20 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900" />
          </div>
          <div class="space-y-2">
            <Label for="status">Estado</Label>
            <select id="status" v-model="form.status" class="h-10 w-full rounded-xl border border-zinc-200 px-3 dark:border-zinc-800 dark:bg-zinc-900">
              <option value="active">Activo</option>
              <option value="inactive">Inactivo</option>
            </select>
          </div>
          <div class="space-y-2">
            <Label for="permission-search">Buscar permiso</Label>
            <Input id="permission-search" v-model="permissionSearch" placeholder="Escribe nombre o slug" />
          </div>
        </div>

        <div class="space-y-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
          <h3 class="font-medium text-zinc-900 dark:text-zinc-100">Permisos por módulo</h3>
          <div class="grid gap-4 md:grid-cols-2">
            <div v-for="[module, permissions] in groupedPermissions" :key="module" class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900/60">
              <div class="mb-3 flex items-center justify-between gap-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ module }}</p>
                <div class="flex gap-2">
                  <Button size="sm" variant="outline" class="h-8 rounded-lg" @click="selectAllFromModule(filteredBySearch(permissions))"><CheckCheck class="mr-1 h-3 w-3" />Todo</Button>
                  <Button size="sm" variant="outline" class="h-8 rounded-lg" @click="clearModule(filteredBySearch(permissions))"><Eraser class="mr-1 h-3 w-3" />Limpiar</Button>
                </div>
              </div>
              <div class="max-h-48 space-y-2 overflow-y-auto pr-1">
                <label v-for="permission in filteredBySearch(permissions)" :key="permission.id" class="flex items-center gap-3 rounded-xl px-2 py-1 text-sm transition hover:bg-zinc-100 dark:hover:bg-zinc-800/60">
                  <Checkbox :model-value="form.permission_ids.includes(permission.id)" @update:model-value="togglePermission(permission.id)" />
                  <span>{{ permission.name }} <span class="text-zinc-400">({{ permission.slug }})</span></span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="closeModal">Cancelar</Button>
          <Button :disabled="form.processing" @click="submit">{{ form.processing ? 'Guardando...' : 'Guardar' }}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
