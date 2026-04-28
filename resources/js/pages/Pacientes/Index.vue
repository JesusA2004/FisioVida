<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { usePacienteCrud, type PacienteRow } from '@/composables/crud/usePacienteCrud'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { UserPlus, Search, Pencil, Trash2, Phone, Mail, ShieldAlert } from 'lucide-vue-next'

const props = defineProps<{
  rows: PacienteRow[]
  page: { current_page: number; last_page: number; total: number }
  filters: { q?: string; status?: string }
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pacientes', href: '/pacientes' }]

const { form, isOpen, editingId, can, moduleEnabled, applyFilters, openCreate, openEdit, closeModal, submit, destroyPaciente } = usePacienteCrud(props.filters)

const statusClass = (status: PacienteRow['status']) => status === 'active'
  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
  : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'

const isEditing = computed(() => editingId.value !== null)
</script>

<template>
  <Head title="Pacientes" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="space-y-6 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950">
      <div v-if="!moduleEnabled" class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
        Módulo de pacientes deshabilitado.
      </div>

      <template v-else>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Pacientes</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Gestiona expedientes, estado y contactos de emergencia de forma centralizada.</p>
          </div>
          <Button v-if="can('patients.create')" class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl" @click="openCreate">
            <UserPlus class="mr-2 h-4 w-4" /> Nuevo paciente
          </Button>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/40">
          <div class="grid gap-3 md:grid-cols-3">
            <div class="relative md:col-span-2">
              <Search class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-zinc-400" />
              <Input class="pl-9" :default-value="props.filters.q ?? ''" placeholder="Buscar por nombre, teléfono o correo" @change="(e) => applyFilters({ q: (e.target as HTMLInputElement).value, page: 1 })" />
            </div>
            <select class="h-10 rounded-xl border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-800 dark:bg-zinc-900" :value="props.filters.status ?? ''" @change="(e) => applyFilters({ status: (e.target as HTMLSelectElement).value, page: 1 })">
              <option value="">Todos los estados</option><option value="active">Activo</option><option value="inactive">Inactivo</option>
            </select>
          </div>
        </div>

        <div v-if="props.rows.length === 0" class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
          <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Sin pacientes para mostrar</h3>
          <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Agrega tu primer paciente o ajusta los filtros.</p>
        </div>

        <div v-else class="grid gap-3 md:grid-cols-2">
          <article v-for="row in props.rows" :key="row.id" class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900/60">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ row.full_name }}</h3>
                <div class="mt-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                  <Phone class="h-3.5 w-3.5" /> {{ row.telefono || 'Sin teléfono' }}
                </div>
                <div class="mt-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                  <Mail class="h-3.5 w-3.5" /> {{ row.email || 'Sin correo' }}
                </div>
              </div>
              <Badge class="rounded-full px-3 py-1 text-xs" :class="statusClass(row.status)">{{ row.status }}</Badge>
            </div>

            <div class="mt-3 rounded-xl bg-zinc-50 p-3 text-xs text-zinc-600 dark:bg-zinc-900/60 dark:text-zinc-300">
              <p><strong>Contacto emergencia:</strong> {{ row.contacto_emergencia_nombre || '—' }} · {{ row.contacto_emergencia_telefono || '—' }}</p>
              <p class="mt-1 line-clamp-2"><strong>Notas:</strong> {{ row.notas || 'Sin notas' }}</p>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <Button v-if="can('patients.update')" variant="outline" class="rounded-xl" @click="openEdit(row)"><Pencil class="mr-2 h-4 w-4" />Editar</Button>
              <Button v-if="can('patients.delete')" variant="destructive" class="rounded-xl" @click="destroyPaciente(row)"><Trash2 class="mr-2 h-4 w-4" />Eliminar</Button>
            </div>
          </article>
        </div>

        <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/40">
          <p class="text-sm text-zinc-500 dark:text-zinc-400">Total: {{ props.page.total }}</p>
          <div class="flex gap-2">
            <Button variant="outline" :disabled="props.page.current_page <= 1" @click="applyFilters({ page: props.page.current_page - 1 })">Anterior</Button>
            <Button variant="outline" :disabled="props.page.current_page >= props.page.last_page" @click="applyFilters({ page: props.page.current_page + 1 })">Siguiente</Button>
          </div>
        </div>
      </template>
    </section>

    <Dialog :open="isOpen" @update:open="closeModal">
      <DialogContent class="max-h-[90vh] max-w-4xl overflow-y-auto rounded-3xl border-none bg-white shadow-2xl dark:bg-zinc-950">
        <DialogHeader>
          <DialogTitle>{{ isEditing ? 'Editar paciente' : 'Nuevo paciente' }}</DialogTitle>
        </DialogHeader>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-2"><Label>Nombres</Label><Input v-model="form.nombres" /><p v-if="form.errors.nombres" class="text-xs text-red-500">{{ form.errors.nombres }}</p></div>
          <div class="space-y-2"><Label>Estado</Label><select v-model="form.status" class="h-10 w-full rounded-xl border border-zinc-200 px-3 dark:border-zinc-800 dark:bg-zinc-900"><option value="active">Activo</option><option value="inactive">Inactivo</option></select></div>
          <div class="space-y-2"><Label>Apellido paterno</Label><Input v-model="form.apellido_paterno" /></div>
          <div class="space-y-2"><Label>Apellido materno</Label><Input v-model="form.apellido_materno" /></div>
          <div class="space-y-2"><Label>Teléfono</Label><Input v-model="form.telefono" /></div>
          <div class="space-y-2"><Label>Email</Label><Input v-model="form.email" type="email" /></div>
          <div class="space-y-2"><Label>Fecha nacimiento</Label><Input v-model="form.fecha_nacimiento" type="date" /></div>
          <div class="space-y-2"><Label>Sexo</Label><select v-model="form.sexo" class="h-10 w-full rounded-xl border border-zinc-200 px-3 dark:border-zinc-800 dark:bg-zinc-900"><option value="">N/A</option><option value="M">M</option><option value="F">F</option><option value="X">X</option></select></div>
          <div class="space-y-2 md:col-span-2"><Label>Dirección</Label><Input v-model="form.direccion" /></div>
          <div class="space-y-2"><Label><ShieldAlert class="mr-1 inline h-4 w-4" />Contacto emergencia</Label><Input v-model="form.contacto_emergencia_nombre" /></div>
          <div class="space-y-2"><Label>Tel. emergencia</Label><Input v-model="form.contacto_emergencia_telefono" /></div>
          <div class="space-y-2 md:col-span-2"><Label>Notas</Label><textarea v-model="form.notas" class="min-h-20 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900" /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="closeModal">Cancelar</Button>
          <Button :disabled="form.processing" @click="submit">{{ form.processing ? 'Guardando...' : 'Guardar' }}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
