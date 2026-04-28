<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    useConfiguracionCrud,
    type ModuleRow,
} from '@/composables/crud/useConfiguracionCrud';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Save,
    Settings2,
    Building2,
    Palette,
    SlidersHorizontal,
    Blocks,
} from 'lucide-vue-next';
import { tModule } from '@/lib/labels';

const props = defineProps<{
    settings: any[];
    settingsMap: Record<string, string | null>;
    modules: ModuleRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/configuracion' },
];

const { form, modulesForm, saveSettings, saveModules } = useConfiguracionCrud(
    props.settingsMap,
    props.modules,
);
</script>

<template>
    <Head title="Configuración" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="space-y-6">
            <header
                class="rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950"
            >
                <div class="flex items-center gap-3">
                    <Settings2 class="h-6 w-6 text-primary" />
                    <div>
                        <h1
                            class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100"
                        >
                            Configuración general
                        </h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Ajusta datos de clínica, apariencia y módulos
                            habilitados del sistema.
                        </p>
                    </div>
                </div>
            </header>

            <div class="grid gap-6 xl:grid-cols-2">
                <article
                    class="space-y-4 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:bg-zinc-950"
                >
                    <h2
                        class="flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        <Building2 class="h-5 w-5" />Datos de clínica
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label>Nombre</Label
                            ><Input v-model="form.clinic_name" />
                        </div>
                        <div class="space-y-2">
                            <Label>Teléfono</Label
                            ><Input v-model="form.clinic_phone" />
                        </div>
                        <div class="space-y-2">
                            <Label>Email</Label
                            ><Input v-model="form.clinic_email" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label>Dirección</Label
                            ><Input v-model="form.clinic_address" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label>Logo (URL)</Label
                            ><Input v-model="form.clinic_logo" />
                        </div>
                    </div>
                </article>

                <article
                    class="space-y-4 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:bg-zinc-950"
                >
                    <h2
                        class="flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        <Palette class="h-5 w-5" />Apariencia
                    </h2>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label>Primario</Label
                            ><Input
                                v-model="form.primary_color"
                                type="color"
                                class="h-12"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Secundario</Label
                            ><Input
                                v-model="form.secondary_color"
                                type="color"
                                class="h-12"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Acento</Label
                            ><Input
                                v-model="form.accent_color"
                                type="color"
                                class="h-12"
                            />
                        </div>
                    </div>

                    <h2
                        class="flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        <SlidersHorizontal class="h-5 w-5" />Parámetros
                        generales
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Moneda</Label
                            ><Input v-model="form.default_currency" />
                        </div>
                        <div class="space-y-2">
                            <Label>Duración cita (min)</Label
                            ><Input
                                v-model="form.appointment_default_duration"
                                type="number"
                                min="1"
                            />
                        </div>
                        <label
                            class="flex items-center justify-between rounded-2xl border border-zinc-200 px-3 py-2 dark:border-zinc-800"
                            ><span>Modo demo</span
                            ><Checkbox
                                :model-value="form.demo_mode"
                                @update:model-value="
                                    (value) => (form.demo_mode = !!value)
                                "
                        /></label>
                        <label
                            class="flex items-center justify-between rounded-2xl border border-zinc-200 px-3 py-2 dark:border-zinc-800"
                            ><span>Modo oscuro habilitado</span
                            ><Checkbox
                                :model-value="form.dark_mode_enabled"
                                @update:model-value="
                                    (value) =>
                                        (form.dark_mode_enabled = !!value)
                                "
                        /></label>
                    </div>

                    <Button
                        class="w-full rounded-2xl"
                        :disabled="form.processing"
                        @click="saveSettings"
                        ><Save class="mr-2 h-4 w-4" />{{
                            form.processing
                                ? 'Guardando...'
                                : 'Guardar configuración'
                        }}</Button
                    >
                </article>
            </div>

            <article
                class="space-y-4 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:bg-zinc-950"
            >
                <h2
                    class="flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100"
                >
                    <Blocks class="h-5 w-5" />Módulos habilitados
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Control global para habilitar o deshabilitar secciones del
                    sistema.
                </p>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <label
                        v-for="module in props.modules"
                        :key="module.id"
                        class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/40"
                    >
                        <div>
                            <p
                                class="font-medium text-zinc-900 dark:text-zinc-100"
                            >
                                {{ module.label || tModule(module.module) }}
                            </p>
                        </div>
                        <Checkbox
                            :model-value="
                                modulesForm.modules.find(
                                    (m) => m.module === module.module,
                                )?.enabled
                            "
                            @update:model-value="
                                (value) => {
                                    const current = modulesForm.modules.find(
                                        (m) => m.module === module.module,
                                    );
                                    if (current) current.enabled = !!value;
                                }
                            "
                        />
                    </label>
                </div>

                <Button
                    class="rounded-2xl"
                    :disabled="modulesForm.processing"
                    @click="saveModules"
                    ><Save class="mr-2 h-4 w-4" />{{
                        modulesForm.processing
                            ? 'Guardando módulos...'
                            : 'Guardar módulos'
                    }}</Button
                >
            </article>
        </section>
    </AppLayout>
</template>
