<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CrudPage from '@/components/fv/CrudPage.vue'
import type { BreadcrumbItem } from '@/types'
import { useRolCrud, type RolRow } from '@/composables/crud/useRolCrud'

const props = defineProps<{
  rows: RolRow[]
  page: any
  filters: any
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: '/roles' }]
const { columns, fields, defaults, toForm, statusOptions } = useRolCrud()
</script>

<template>
  <Head title="Roles" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <CrudPage
      title="Roles"
      subtitle="Gestión de roles dinámicos para permisos por acción"
      create-label="Nuevo rol"
      route-base="roles"
      :rows="props.rows"
      :page="props.page"
      :filters="props.filters"
      :columns="columns"
      :fields="fields"
      :defaults="defaults"
      :to-form="toForm"
      :status-options="statusOptions"
      modal-max-width-class="max-w-3xl"
    />
  </AppLayout>
</template>
