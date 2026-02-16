<script setup lang="ts">
type Props = {
  /**
   * Máximo ancho “enterprise”.
   * Ajusta si quieres más/menos aire.
   */
  maxWidthClass?: string
}

withDefaults(defineProps<Props>(), {
  maxWidthClass: 'max-w-[1400px]',
})
</script>

<template>
  <!-- Espaciado simétrico dentro del área de contenido (inset) -->
  <div class="w-full">
    <div
      class="mx-auto w-full px-4 sm:px-6 lg:px-8 2xl:px-10 py-4"
      :class="maxWidthClass"
    >
      <slot />
    </div>
  </div>
</template>
