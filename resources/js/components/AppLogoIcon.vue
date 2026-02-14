<script setup lang="ts">
import type { HTMLAttributes } from 'vue'

defineOptions({ inheritAttrs: false })

type Props = {
  className?: HTMLAttributes['class']
  src?: string
  alt?: string
}

withDefaults(defineProps<Props>(), {
  src: '/ogLogo.png',
  alt: 'FisioVida',
})
</script>

<template>
  <img :src="src" :alt="alt" :class="className" v-bind="$attrs" />
</template>
