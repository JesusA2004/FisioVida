<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    useEjercicioCrud,
    type EjercicioRow,
} from '@/composables/crud/useEjercicioCrud';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Dumbbell, Search, Pencil, Trash2, Power } from 'lucide-vue-next';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';

const props = defineProps<{
    rows: EjercicioRow[];
    page: { current_page: number; last_page: number; total: number };
    filters: { q?: string; active?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ejercicios', href: '/ejercicios' },
];
const {
    form,
    isOpen,
    editingId,
    can,
    moduleEnabled,
    applyFilters,
    openCreate,
    openEdit,
    closeModal,
    submit,
    toggleActive,
    destroyEjercicio,
} = useEjercicioCrud(props.filters);
const isEditing = computed(() => editingId.value !== null);

const youtubeEmbed = (url?: string | null) => {
    if (!url) return null;
    const match = url.match(
        /(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]{11})/,
    );
    return match ? `https://www.youtube.com/embed/${match[1]}` : null;
};
</script>

<template>
    <Head title="Ejercicios" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section
            class="space-y-6 rounded-3xl bg-white p-6 shadow-xl transition-all duration-300 dark:bg-zinc-950"
        >
            <div
                v-if="!moduleEnabled"
                class="rounded-2xl border border-amber-300/70 bg-amber-50 p-4 text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
            >
                Módulo de ejercicios deshabilitado.
            </div>
            <template v-else>
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h1
                            class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100"
                        >
                            Catálogo de ejercicios
                        </h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Este catálogo permite registrar ejercicios que
                            después pueden asignarse a sesiones clínicas o
                            pacientes.
                        </p>
                    </div>
                    <Button
                        v-if="can('exercises.create')"
                        class="rounded-2xl shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
                        @click="openCreate"
                        ><Dumbbell class="mr-2 h-4 w-4" />Nuevo
                        ejercicio</Button
                    >
                </div>

                <div
                    class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/40"
                >
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="relative md:col-span-2">
                            <Search
                                class="pointer-events-none absolute top-3.5 left-3 h-4 w-4 text-zinc-400"
                            /><Input
                                class="pl-9"
                                :default-value="props.filters.q ?? ''"
                                placeholder="Buscar por nombre"
                                @change="
                                    (e) =>
                                        applyFilters({
                                            q: (e.target as HTMLInputElement)
                                                .value,
                                            page: 1,
                                        })
                                "
                            />
                        </div>
                        <SearchableSelect
                            :model-value="props.filters.active ?? null"
                            :options="[
                                { value: null, label: 'Todos' },
                                { value: '1', label: 'Disponibles' },
                                { value: '0', label: 'No disponibles' },
                            ]"
                            placeholder="Filtrar disponibilidad"
                            clearable
                            @update:model-value="
                                (value) =>
                                    applyFilters({
                                        active: value ?? '',
                                        page: 1,
                                    })
                            "
                        />
                    </div>
                </div>

                <div
                    v-if="props.rows.length === 0"
                    class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/40"
                >
                    No hay ejercicios registrados.
                </div>
                <div v-else class="grid gap-3 md:grid-cols-2">
                    <article
                        v-for="row in props.rows"
                        :key="row.id"
                        class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3
                                    class="font-semibold text-zinc-900 dark:text-zinc-100"
                                >
                                    {{ row.name }}
                                </h3>
                                <p
                                    class="mt-1 line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400"
                                >
                                    {{ row.description || 'Sin descripción' }}
                                </p>
                                <p
                                    v-if="
                                        row.video_url &&
                                        !youtubeEmbed(row.video_url)
                                    "
                                    class="mt-2 text-xs"
                                >
                                    <a
                                        :href="row.video_url"
                                        target="_blank"
                                        class="text-sky-600 hover:underline dark:text-sky-400"
                                        >Ver video de referencia</a
                                    >
                                </p>
                            </div>
                            <Badge
                                class="rounded-full px-3 py-1 text-xs"
                                :class="
                                    row.is_active
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                        : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                                "
                                >{{
                                    row.is_active
                                        ? 'Disponible'
                                        : 'No disponible'
                                }}</Badge
                            >
                        </div>
                        <iframe
                            v-if="youtubeEmbed(row.video_url)"
                            :src="youtubeEmbed(row.video_url)!"
                            class="mt-3 h-44 w-full rounded-xl border border-zinc-200 dark:border-zinc-800"
                            title="Video de ejercicio"
                            allowfullscreen
                        />
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button
                                v-if="can('exercises.update')"
                                variant="outline"
                                class="rounded-xl"
                                @click="openEdit(row)"
                                ><Pencil class="mr-2 h-4 w-4" />Editar</Button
                            ><Button
                                v-if="can('exercises.update')"
                                variant="outline"
                                class="rounded-xl"
                                @click="toggleActive(row)"
                                ><Power class="mr-2 h-4 w-4" />{{
                                    row.is_active
                                        ? 'Ocultar del catálogo'
                                        : 'Marcar disponible'
                                }}</Button
                            ><Button
                                v-if="can('exercises.delete')"
                                variant="destructive"
                                class="rounded-xl"
                                @click="destroyEjercicio(row)"
                                ><Trash2 class="mr-2 h-4 w-4" />Eliminar</Button
                            >
                        </div>
                    </article>
                </div>

                <div
                    class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900/40"
                >
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Total: {{ props.page.total }}
                    </p>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            :disabled="props.page.current_page <= 1"
                            @click="
                                applyFilters({
                                    page: props.page.current_page - 1,
                                })
                            "
                            >Anterior</Button
                        ><Button
                            variant="outline"
                            :disabled="
                                props.page.current_page >= props.page.last_page
                            "
                            @click="
                                applyFilters({
                                    page: props.page.current_page + 1,
                                })
                            "
                            >Siguiente</Button
                        >
                    </div>
                </div>
            </template>
        </section>

        <Dialog :open="isOpen" @update:open="closeModal">
            <DialogContent
                class="max-w-3xl rounded-3xl border-none bg-white shadow-2xl dark:bg-zinc-950"
            >
                <DialogHeader
                    ><DialogTitle>{{
                        isEditing ? 'Editar ejercicio' : 'Nuevo ejercicio'
                    }}</DialogTitle></DialogHeader
                >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <Label>Nombre</Label><Input v-model="form.name" />
                        <p v-if="form.errors.name" class="text-xs text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label>Video URL</Label
                        ><Input
                            v-model="form.video_url"
                            placeholder="https://..."
                        />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label>Descripción</Label
                        ><textarea
                            v-model="form.description"
                            class="min-h-20 w-full rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900"
                        />
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm"
                        ><Checkbox
                            :model-value="form.is_active"
                            @update:model-value="
                                (value) => (form.is_active = !!value)
                            "
                        />
                        Disponible</label
                    >
                </div>
                <DialogFooter
                    ><Button variant="outline" @click="closeModal"
                        >Cancelar</Button
                    ><Button :disabled="form.processing" @click="submit">{{
                        form.processing ? 'Guardando...' : 'Guardar'
                    }}</Button></DialogFooter
                >
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
