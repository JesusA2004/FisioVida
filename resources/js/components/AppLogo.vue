<script setup lang="ts">
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSidebar } from '@/components/ui/sidebar';

const sidebar = useSidebar();

const isCollapsed = computed(() => {
    return sidebar.state?.value === 'collapsed';
});
</script>

<template>
    <div
        class="flex w-full items-center gap-2 transition-all duration-200"
        :class="isCollapsed ? 'justify-center' : 'justify-start'"
    >
        <!-- Colapsado: icono -->
        <AppLogoIcon
            v-if="isCollapsed"
            src="/favicon.ico"
            class="h-14 w-14 rounded-md object-contain transition-all duration-200"
        />

        <!-- Expandido: logo completo -->
        <AppLogoIcon
            v-else
            src="/ogLogo.png"
            alt="FisioVida"
            class="h-13 w-auto max-w-[180px] object-contain transition-all duration-200"
        />
    </div>
</template>
