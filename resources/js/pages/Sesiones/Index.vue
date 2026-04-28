<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { useSesionCrud, type SesionRow } from '@/composables/crud/useSesionCrud'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { ClipboardPlus, Search, Pencil, Trash2, Activity } from 'lucide-vue-next'

const props = defineProps<{
  rows: SesionRow[]
  page: { current_page: number; last_page: number; total: number }
  filters: { q?: string }
  lookups: { patients: { id: number; label: string }[]; therapists: { id: number; label: string }[]; appointments: { id: number; label: string }[] }
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Sesiones', href: '/sesiones' }]
const { form, isOpen, editingId, can, moduleEnabled, applyFilters, openCreate, openEdit, closeModal, submit, destroySesion } = useSesionCrud(props.filters)
const isEditing = computed(() => editingId.value !== null)
</script>

<template>
  <Head title="Sesiones" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="space-y-6 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950">
      <div v-if="!moduleEnabled" class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">Módulo de sesiones deshabilitado.</div>
      <template v-else>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"><div><h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Sesiones clínicas</h1><p class="text-sm text-zinc-500 dark:text-zinc-400">Registra SOAP, escala de dolor y plan terapéutico por sesión.</p></div><Button v-if="can('sessions.create')" class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl" @click="openCreate"><ClipboardPlus class="mr-2 h-4 w-4" />Nueva sesión</Button></div>

        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"><div class="relative"><Search class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-zinc-400" /><Input class="pl-9" :default-value="props.filters.q ?? ''" placeholder="Buscar por paciente o terapeuta" @change="(e) => applyFilters({ q: (e.target as HTMLInputElement).value, page: 1 })" /></div></div>

        <div v-if="props.rows.length === 0" class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/40">Sin sesiones para mostrar.</div>
        <div v-else class="space-y-3">
          <article v-for="row in props.rows" :key="row.id" class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900/60">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ row.patient_name }} · {{ row.session_date }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Terapeuta: {{ row.therapist_name }} · Cita: {{ row.appointment_id || 'N/A' }}</p>
                <p class="mt-2 inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-medium text-rose-700 dark:bg-rose-900/30 dark:text-rose-300"><Activity class="mr-1 h-3.5 w-3.5" />Dolor: {{ row.pain_scale ?? '—' }}/10</p>
                <p class="mt-2 line-clamp-2 text-xs text-zinc-600 dark:text-zinc-300"><strong>Evaluación:</strong> {{ row.assessment || 'Sin evaluación' }}</p>
                <p class="line-clamp-2 text-xs text-zinc-600 dark:text-zinc-300"><strong>Plan:</strong> {{ row.plan || 'Sin plan' }}</p>
              </div>
              <div class="flex gap-2">
                <Button v-if="can('sessions.update')" variant="outline" class="rounded-xl" @click="openEdit(row)"><Pencil class="mr-2 h-4 w-4" />Editar</Button>
                <Button v-if="can('sessions.delete')" variant="destructive" class="rounded-xl" @click="destroySesion(row)"><Trash2 class="mr-2 h-4 w-4" />Eliminar</Button>
              </div>
            </div>
          </article>
        </div>

        <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/40"><p class="text-sm text-zinc-500 dark:text-zinc-400">Total: {{ props.page.total }}</p><div class="flex gap-2"><Button variant="outline" :disabled="props.page.current_page <= 1" @click="applyFilters({ page: props.page.current_page - 1 })">Anterior</Button><Button variant="outline" :disabled="props.page.current_page >= props.page.last_page" @click="applyFilters({ page: props.page.current_page + 1 })">Siguiente</Button></div></div>
      </template>
    </section>

    <Dialog :open="isOpen" @update:open="closeModal">
      <DialogContent class="max-h-[90vh] max-w-5xl overflow-y-auto rounded-3xl border-none bg-white shadow-2xl dark:bg-zinc-950">
        <DialogHeader><DialogTitle>{{ isEditing ? 'Editar sesión' : 'Nueva sesión' }}</DialogTitle></DialogHeader>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-2"><Label>Paciente</Label><select v-model="form.patient_persona_id" class="h-10 w-full rounded-xl border border-zinc-200 px-3 dark:border-zinc-800 dark:bg-zinc-900"><option :value="''">Seleccionar</option><option v-for="p in props.lookups.patients" :key="p.id" :value="p.id">{{ p.label }}</option></select></div>
          <div class="space-y-2"><Label>Terapeuta</Label><select v-model="form.therapist_user_id" class="h-10 w-full rounded-xl border border-zinc-200 px-3 dark:border-zinc-800 dark:bg-zinc-900"><option :value="''">Seleccionar</option><option v-for="t in props.lookups.therapists" :key="t.id" :value="t.id">{{ t.label }}</option></select></div>
          <div class="space-y-2 md:col-span-2"><Label>Cita relacionada (opcional)</Label><select v-model="form.appointment_id" class="h-10 w-full rounded-xl border border-zinc-200 px-3 dark:border-zinc-800 dark:bg-zinc-900"><option :value="''">Sin cita</option><option v-for="a in props.lookups.appointments" :key="a.id" :value="a.id">{{ a.label }}</option></select></div>
          <div class="space-y-2"><Label>Fecha</Label><Input v-model="form.session_date" type="date" /></div>
          <div class="space-y-2"><Label>Escala dolor (0-10)</Label><Input v-model="form.pain_scale" type="number" min="0" max="10" /></div>
          <div class="space-y-2 md:col-span-2"><Label>Subjetivo</Label><textarea v-model="form.subjective" class="min-h-20 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900" /></div>
          <div class="space-y-2 md:col-span-2"><Label>Objetivo</Label><textarea v-model="form.objective" class="min-h-20 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900" /></div>
          <div class="space-y-2 md:col-span-2"><Label>Evaluación</Label><textarea v-model="form.assessment" class="min-h-20 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900" /></div>
          <div class="space-y-2 md:col-span-2"><Label>Plan</Label><textarea v-model="form.plan" class="min-h-20 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900" /></div>
          <div class="space-y-2 md:col-span-2"><Label>Notas</Label><textarea v-model="form.notes" class="min-h-20 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900" /></div>
        </div>
        <DialogFooter><Button variant="outline" @click="closeModal">Cancelar</Button><Button :disabled="form.processing" @click="submit">{{ form.processing ? 'Guardando...' : 'Guardar' }}</Button></DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
