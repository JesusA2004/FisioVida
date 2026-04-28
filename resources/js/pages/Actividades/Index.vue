<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import { useActividadCrud, type ActivityRow } from '@/composables/crud/useActividadCrud'

const props = defineProps<{
  rows: ActivityRow[]
  page: any
  filters: any
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Actividades', href: '/actividades' }]
const { columns, fields, defaults, toForm, statusOptions, priorityOptions } = useActividadCrud()
</script>

<template>
  <Head title="Actividades" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Actividades internas"
      subtitle="Control operativo de pendientes internos por clínica"
      create-label="Nueva actividad"
      route-base="actividades"
      :rows="props.rows"
      :page="props.page"
      :filters="props.filters"
      :columns="columns"
      :fields="fields"
      :defaults="defaults"
      :to-form="toForm"
      :status-options="statusOptions"
      :extra-filters="[{ key: 'priority', label: 'Prioridad', options: priorityOptions }]"
      modal-max-width-class="max-w-4xl"
    />
  </AppLayout>
</template>
