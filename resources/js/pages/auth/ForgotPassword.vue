<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Recuperar contraseña"
        description="Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña."
    >
        <Head title="Recuperar contraseña" />

        <!-- Estado / Mensaje -->
        <div
            v-if="status"
            class="mb-4 rounded-xl border border-green-500/20 bg-green-500/10 px-4 py-3 text-center text-sm font-medium text-green-700 dark:text-green-300"
        >
            {{ status }}
        </div>

        <div class="space-y-6">
            <Form v-bind="email.form()" v-slot="{ errors, processing }">
                <div class="grid gap-2">
                    <Label for="email">Correo electrónico</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        placeholder="correo@ejemplo.com"
                        class="mt-1"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="mt-6">
                    <Button
                        class="w-full"
                        :disabled="processing"
                        data-test="email-password-reset-link-button"
                    >
                        <Spinner v-if="processing" />
                        <span>{{ processing ? 'Enviando enlace…' : 'Enviar enlace de recuperación' }}</span>
                    </Button>

                    <p class="mt-3 text-center text-xs text-muted-foreground">
                        Si el correo existe, te llegará un enlace en unos minutos.
                    </p>
                </div>
            </Form>

            <div class="text-center text-sm text-muted-foreground">
                <span>¿Ya la recordaste?</span>
                <TextLink :href="login()" class="ml-1">Volver a iniciar sesión</TextLink>
            </div>
        </div>
    </AuthLayout>
</template>
