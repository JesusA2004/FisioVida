<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import { usePermisoCrud, type PermissionRow } from '@/composables/crud/usePermisoCrud'

const props = defineProps<{
  rows: PermissionRow[]
  page: any
  filters: any
  moduleOptions: string[]
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Permisos', href: '/permisos' }]
const { columns, statusOptions, fields, defaults, toForm } = usePermisoCrud()
const moduleFilters = props.moduleOptions.map(item => ({ value: item, label: item }))
</script>

<template>
  <Head title="Permisos" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Permisos"
      subtitle="Permisos por acción para módulos administrativos"
      create-label="Nuevo permiso"
      route-base="permisos"
      :rows="props.rows"
      :page="props.page"
      :filters="props.filters"
      :columns="columns"
      :fields="fields"
      :defaults="defaults"
      :to-form="toForm"
      :status-options="statusOptions"
      :extra-filters="[{ key: 'module', label: 'Módulo', options: moduleFilters }]"
      modal-max-width-class="max-w-4xl"
    />
  </AppLayout>
</template>
