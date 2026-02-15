<script setup lang="ts">
    import { computed } from 'vue'
    import AppLogoIcon from '@/components/AppLogoIcon.vue'
    import { useSidebar } from '@/components/ui/sidebar'

    const sidebar = useSidebar()

    const isCollapsed = computed(() => {
        return sidebar.state?.value === 'collapsed'
    })
</script>

<template>
    <div class="w-full flex items-center gap-2 transition-all duration-200"
    :class="isCollapsed ? 'justify-center' : 'justify-start'">
        <!-- Colapsado: icono -->
        <AppLogoIcon v-if="isCollapsed" src="/favicon.ico" alt="FisioVida"
        class="h-10 w-10 rounded-md object-contain transition-all 
        duration-200"/>

        <!-- Expandido: logo completo -->
        <AppLogoIcon v-else src="/ogLogo.png" alt="FisioVida"
        class="h-10 w-auto max-w-[180px] object-contain transition-all duration-200"/>
    </div>
</template>
