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
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

const props = defineProps<{
  rows: RoleRow[]
  page: {
    current_page: number
    last_page: number
    total: number
  }
  filters: {
    q?: string
    status?: string
  }
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
  submit,
  destroyRole,
} = useRolCrud()

const groupedPermissions = computed(() => Object.entries(props.permissionsByModule ?? {}))

const applyFilter = (e: Event) => {
  const target = e.target as HTMLInputElement
  router.get(route('roles.index'), { ...props.filters, q: target.value }, { preserveState: true, replace: true })
}

const changeStatus = (e: Event) => {
  const target = e.target as HTMLSelectElement
  router.get(route('roles.index'), { ...props.filters, status: target.value }, { preserveState: true, replace: true })
}

const goPage = (page: number) => {
  router.get(route('roles.index'), { ...props.filters, page }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Roles" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="space-y-6 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Roles</h1>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">Asigna permisos por acción a cada rol.</p>
        </div>

        <Button class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl" @click="openCreate">
          Nuevo rol
        </Button>
      </div>

      <div class="grid gap-3 md:grid-cols-3">
        <Input :default-value="props.filters.q ?? ''" placeholder="Buscar por nombre o slug" @change="applyFilter" />
        <select
          class="h-10 rounded-xl border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-800 dark:bg-zinc-900"
          :value="props.filters.status ?? ''"
          @change="changeStatus"
        >
          <option value="">Todos los estados</option>
          <option value="active">Activo</option>
          <option value="inactive">Inactivo</option>
        </select>
      </div>

      <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
          <thead class="bg-zinc-50 dark:bg-zinc-900/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium">Nombre</th>
              <th class="px-4 py-3 text-left font-medium">Slug</th>
              <th class="px-4 py-3 text-left font-medium">Estado</th>
              <th class="px-4 py-3 text-left font-medium">Permisos</th>
              <th class="px-4 py-3 text-right font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr v-for="row in props.rows" :key="row.id" class="transition-all duration-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
              <td class="px-4 py-3">{{ row.name }}</td>
              <td class="px-4 py-3">{{ row.slug }}</td>
              <td class="px-4 py-3">{{ row.status }}</td>
              <td class="px-4 py-3">{{ row.permissions_count }}</td>
              <td class="px-4 py-3 text-right">
                <div class="inline-flex gap-2">
                  <Button variant="outline" class="rounded-xl" @click="openEdit(row)">Editar</Button>
                  <Button variant="destructive" class="rounded-xl" @click="destroyRole(row.id)">Eliminar</Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between">
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
        </div>

        <div class="space-y-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
          <h3 class="font-medium text-zinc-900 dark:text-zinc-100">Permisos por módulo</h3>
          <div class="grid gap-4 md:grid-cols-2">
            <div v-for="[module, permissions] in groupedPermissions" :key="module" class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900/60">
              <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ module }}</p>
              <div class="space-y-2">
                <label v-for="permission in permissions" :key="permission.id" class="flex items-center gap-3 text-sm">
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
