<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent
            variant="sidebar"
            class="overflow-x-hidden pt-16 group-has-data-[collapsible=icon]/sidebar-wrapper:pt-12"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />

            <div class="w-full px-4 py-4 sm:px-5 lg:px-6 xl:px-7">
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
