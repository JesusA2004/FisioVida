<script setup lang="ts">
    import { Link, router } from '@inertiajs/vue3'
    import Swal from 'sweetalert2'
    import { LogOut, Settings } from 'lucide-vue-next'

    import {
        DropdownMenuGroup,
        DropdownMenuItem,
        DropdownMenuLabel,
        DropdownMenuSeparator,
    } from '@/components/ui/dropdown-menu'

    import UserInfo from '@/components/UserInfo.vue'
    import type { User } from '@/types'
    import { logout } from '@/routes'
    import { edit } from '@/routes/profile'

    type Props = { user: User }
    defineProps<Props>()

    const confirmLogout = async (e?: Event) => {
        e?.preventDefault()

        // Limpia estado/visitas antes de salir (ok)
        router.flushAll()

        const res = await Swal.fire({
            title: '¿Cerrar sesión?',
            text: 'Se cerrará tu sesión actual y volverás a la pantalla de acceso.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,

            // Paleta (verde agua)
            buttonsStyling: false,
            customClass: {
            popup:
                'rounded-3xl bg-background/95 text-foreground shadow-2xl ring-1 ring-foreground/10 backdrop-blur',
            title: 'text-lg font-extrabold tracking-tight',
            htmlContainer: 'text-sm text-muted-foreground',
            actions: 'gap-2',
            confirmButton:
                'h-11 rounded-2xl px-4 font-extrabold text-primary-foreground bg-primary hover:opacity-95 active:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30',
            cancelButton:
                'h-11 rounded-2xl px-4 font-semibold bg-muted/50 text-foreground hover:bg-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/20',
            icon: 'border-0',
            },
        })

        if (!res.isConfirmed) return

        // Logout real (Starter Kit suele usar POST)
        router.post(logout(), {}, { preserveScroll: true })
    }
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
        <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>

    <DropdownMenuSeparator />

    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
        <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
            <Settings class="mr-2 h-4 w-4" />
            Configuración
        </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator />

    <!-- IMPORTANTE: ya no navegamos directo, interceptamos -->
    <DropdownMenuItem :as-child="true">
        <button
        type="button"
        class="block w-full cursor-pointer text-left"
        @click="confirmLogout"
        data-test="logout-button"
        >
        <span class="flex items-center">
            <LogOut class="mr-2 h-4 w-4" />
            Cerrar sesión
        </span>
        </button>
    </DropdownMenuItem>
</template>
