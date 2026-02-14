<script setup lang="ts">
defineProps<{ title?: string; subtitle?: string }>()
</script>

<template>
  <section class="fv-card p-5">
    <header v-if="title || subtitle" class="mb-4">
      <h3 v-if="title" class="text-lg font-bold text-slate-100">{{ title }}</h3>
      <p v-if="subtitle" class="text-sm text-slate-400">{{ subtitle }}</p>
    </header>
    <slot />
  </section>
</template>
