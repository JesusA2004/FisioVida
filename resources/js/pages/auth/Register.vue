<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
</script>

<template>
    <AuthBase
        title="Acceso restringido"
        description="El registro de nuevas cuentas no está disponible."
    >
        <Head title="Acceso restringido" />
        <div class="text-center text-sm text-muted-foreground">
            El acceso a este sistema es solo para personal autorizado.
            <br /><br />
            <TextLink :href="login()" class="underline underline-offset-4">
                Iniciar sesión
            </TextLink>
        </div>
    </AuthBase>
</template>
