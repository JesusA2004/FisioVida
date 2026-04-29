<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="fixed top-0 right-0 left-0 z-50 flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 bg-background/95 px-6 backdrop-blur transition-[left,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:left-[var(--sidebar-width)] md:px-4 group-has-data-[collapsible=icon]/sidebar-wrapper:md:left-[calc(var(--sidebar-width-icon)+1rem)]"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />

            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>
