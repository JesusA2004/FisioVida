<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { useUsuarioCrud, type UsuarioRole, type UsuarioRow } from '@/composables/crud/useUsuarioCrud'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { UserPlus, Search, ShieldCheck, Power, Pencil, Trash2 } from 'lucide-vue-next'

const props = defineProps<{
  rows: UsuarioRow[]
  page: { current_page: number; last_page: number; total: number }
  filters: { q?: string; status?: string }
  roles: UsuarioRole[]
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Usuarios', href: '/usuarios' }]

const { form, isOpen, openCreate, openEdit, toggleRole, closeModal, submit, toggleStatus, destroyUser, editingId } = useUsuarioCrud()

const applyFilter = (e: Event) => {
  const target = e.target as HTMLInputElement
  router.get(route('usuarios.index'), { ...props.filters, q: target.value }, { preserveState: true, replace: true })
}

const changeStatus = (e: Event) => {
  const target = e.target as HTMLSelectElement
  router.get(route('usuarios.index'), { ...props.filters, status: target.value }, { preserveState: true, replace: true })
}

const goPage = (page: number) => {
  router.get(route('usuarios.index'), { ...props.filters, page }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Usuarios" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="space-y-6 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Usuarios</h1>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">Gestión de accesos basada en roles (compatibilidad legacy incluida).</p>
        </div>
        <Button class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl" @click="openCreate"><UserPlus class="mr-2 h-4 w-4" />Nuevo usuario</Button>
      </div>

      <div class="grid gap-3 md:grid-cols-3">
        <Input :default-value="props.filters.q ?? ''" placeholder="Buscar por nombre o email" @change="applyFilter" />
        <select class="h-10 rounded-xl border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-800 dark:bg-zinc-900" :value="props.filters.status ?? ''" @change="changeStatus">
          <option value="">Todos los estados</option>
          <option value="active">Activo</option>
          <option value="blocked">Bloqueado</option>
        </select>
      </div>

            <div v-if="props.rows.length === 0" class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Sin usuarios para mostrar</h3>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Ajusta filtros o crea un usuario nuevo.</p>
      </div>

      <div v-else class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
          <thead class="bg-zinc-50 dark:bg-zinc-900/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium">Nombre</th>
              <th class="px-4 py-3 text-left font-medium">Email</th>
              <th class="px-4 py-3 text-left font-medium">Estado</th>
              <th class="px-4 py-3 text-left font-medium">Roles</th>
              <th class="px-4 py-3 text-right font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr v-for="row in props.rows" :key="row.id" class="transition-all duration-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
              <td class="px-4 py-3">{{ row.name }} <Badge v-if="row.is_super_admin" class="ml-2 rounded-full bg-indigo-100 px-2 py-1 text-xs text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300"><ShieldCheck class="mr-1 inline h-3 w-3" />super</Badge></td>
              <td class="px-4 py-3">{{ row.email }}</td>
              <td class="px-4 py-3"><Badge class="rounded-full px-3 py-1 text-xs" :class="row.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'">{{ row.status }}</Badge></td>
              <td class="px-4 py-3">
                <span v-if="row.roles.length === 0" class="text-zinc-400">Sin roles</span>
                <div v-else class="flex flex-wrap gap-1">
                  <span v-for="role in row.roles" :key="role.id" class="rounded-full bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-800">{{ role.name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="inline-flex gap-2">
                  <Button variant="outline" class="rounded-xl" @click="openEdit(row)"><Pencil class="mr-2 h-4 w-4" />Editar</Button>
                  <Button variant="outline" class="rounded-xl" @click="toggleStatus(row)"><Power class="mr-2 h-4 w-4" />{{ row.status === "active" ? "Bloquear" : "Activar" }}</Button>
                  <Button variant="destructive" class="rounded-xl" @click="destroyUser(row.id)"><Trash2 class="mr-2 h-4 w-4" />Eliminar</Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
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
          <DialogTitle>{{ editingId ? 'Editar usuario' : 'Nuevo usuario' }}</DialogTitle>
        </DialogHeader>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-2">
            <Label for="name">Nombre</Label>
            <Input id="name" v-model="form.name" />
            <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
          </div>
          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input id="email" v-model="form.email" />
            <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
          </div>
          <div class="space-y-2">
            <Label for="password">Contraseña</Label>
            <Input id="password" v-model="form.password" type="password" />
            <p class="text-xs text-zinc-400">En edición, deja vacío para conservar la contraseña actual.</p>
          </div>
          <div class="space-y-2">
            <Label for="status">Estado</Label>
            <select id="status" v-model="form.status" class="h-10 w-full rounded-xl border border-zinc-200 px-3 dark:border-zinc-800 dark:bg-zinc-900">
              <option value="active">Activo</option>
              <option value="blocked">Bloqueado</option>
            </select>
          </div>

          <div class="space-y-2 md:col-span-2">
            <Label>Roles asignados</Label>
            <div class="grid gap-2 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800 md:grid-cols-2">
              <label v-for="role in props.roles" :key="role.id" class="flex items-center gap-3 text-sm">
                <Checkbox :model-value="form.role_ids.includes(role.id)" @update:model-value="toggleRole(role.id)" />
                <span>{{ role.name }}</span>
              </label>
            </div>
          </div>

          <div class="space-y-3 md:col-span-2">
            <Label>Compatibilidad de módulos legacy</Label>
            <div class="grid gap-3 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800 md:grid-cols-2">
              <label class="flex items-center justify-between text-sm"><span>Super admin</span><Checkbox :model-value="form.is_super_admin" @update:model-value="(value) => (form.is_super_admin = !!value)" /></label>
              <label class="flex items-center justify-between text-sm"><span>Módulo Agenda</span><Checkbox :model-value="form.mod_agenda" @update:model-value="(value) => (form.mod_agenda = !!value)" /></label>
              <label class="flex items-center justify-between text-sm"><span>Módulo Pacientes</span><Checkbox :model-value="form.mod_pacientes" @update:model-value="(value) => (form.mod_pacientes = !!value)" /></label>
              <label class="flex items-center justify-between text-sm"><span>Módulo Sesiones</span><Checkbox :model-value="form.mod_sesiones" @update:model-value="(value) => (form.mod_sesiones = !!value)" /></label>
              <label class="flex items-center justify-between text-sm"><span>Módulo Ejercicios</span><Checkbox :model-value="form.mod_ejercicios" @update:model-value="(value) => (form.mod_ejercicios = !!value)" /></label>
              <label class="flex items-center justify-between text-sm"><span>Módulo Archivos</span><Checkbox :model-value="form.mod_archivos" @update:model-value="(value) => (form.mod_archivos = !!value)" /></label>
              <label class="flex items-center justify-between text-sm"><span>Módulo Reportes</span><Checkbox :model-value="form.mod_reportes" @update:model-value="(value) => (form.mod_reportes = !!value)" /></label>
              <label class="flex items-center justify-between text-sm"><span>Módulo Cobranza</span><Checkbox :model-value="form.mod_cobranza" @update:model-value="(value) => (form.mod_cobranza = !!value)" /></label>
              <label class="flex items-center justify-between text-sm"><span>Módulo Configuración</span><Checkbox :model-value="form.mod_config" @update:model-value="(value) => (form.mod_config = !!value)" /></label>
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
