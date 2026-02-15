<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import { useCurrentUrl } from '@/composables/useCurrentUrl'
import { toUrl } from '@/lib/utils'
import type { NavItem } from '@/types'

import { UserRound, KeyRound, ShieldCheck, Palette } from 'lucide-vue-next'

import { edit as editAppearance } from '@/routes/appearance'
import { edit as editProfile } from '@/routes/profile'
import { show } from '@/routes/two-factor'
import { edit as editPassword } from '@/routes/user-password'

const navItems: NavItem[] = [
  { title: 'Perfil', href: editProfile(), icon: UserRound },
  { title: 'Contraseña', href: editPassword(), icon: KeyRound },
  { title: '2FA', href: show(), icon: ShieldCheck },
  { title: 'Apariencia', href: editAppearance(), icon: Palette },
]

const subtitles: Record<string, string> = {
  Perfil: 'Nombre y correo',
  Contraseña: 'Acceso y seguridad',
  '2FA': 'Protección extra',
  Apariencia: 'Tema y modo oscuro',
}

const { isCurrentUrl } = useCurrentUrl()

const activeTitle = computed(() => {
  const found = navItems.find(i => isCurrentUrl(i.href))
  return found?.title ?? 'Configuración'
})
</script>

<template>
  <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <!-- Header (más pro) -->
    <div
      class="relative overflow-hidden rounded-3xl bg-background/50 p-5 shadow-sm ring-1 ring-foreground/5 backdrop-blur sm:p-6"
    >
      <!-- acento suave -->
      <div
        class="pointer-events-none absolute inset-x-0 -top-16 h-32 opacity-70 blur-2xl"
        style="background: radial-gradient(600px 140px at 50% 50%, rgba(5,154,178,.28), transparent 70%);"
      />
      <div class="relative">
        <Heading
          title="Configuración"
          description="Administra tu cuenta, seguridad y preferencias"
        />
      </div>

      <!-- Tabs premium -->
      <div class="relative mt-0">
        <div
          class="rounded-2xl bg-muted/25 p-1.5 ring-1 ring-foreground/5 backdrop-blur"
        >
          <nav
            aria-label="Navegación de configuración"
            class="grid grid-cols-2 gap-1.5 sm:flex sm:flex-wrap sm:gap-1.5"
          >
            <Link
              v-for="item in navItems"
              :key="toUrl(item.href)"
              :href="item.href"
              class="group relative flex items-center gap-3 rounded-2xl px-3 py-2.5 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
              :class="isCurrentUrl(item.href)
                ? 'bg-background shadow-sm ring-1 ring-primary/25'
                : 'hover:bg-background/60'"
              :aria-current="isCurrentUrl(item.href) ? 'page' : undefined"
            >
              <span
                class="grid h-10 w-10 place-items-center rounded-2xl ring-1 ring-foreground/5 transition-all"
                :class="isCurrentUrl(item.href)
                  ? 'bg-primary/10 text-primary shadow-[0_0_0_6px_rgba(5,154,178,0.10)]'
                  : 'bg-background/60 text-foreground/80 group-hover:bg-background/70'"
              >
                <component :is="item.icon" class="h-5 w-5" />
              </span>

              <span class="min-w-0 flex-1">
                <span
                  class="block truncate text-sm font-semibold"
                  :class="isCurrentUrl(item.href) ? 'text-foreground' : 'text-foreground/90'"
                >
                  {{ item.title }}
                </span>
                <span class="mt-0.5 block truncate text-xs text-muted-foreground">
                  {{ subtitles[item.title] ?? '' }}
                </span>
              </span>

              <!-- Indicador activo (barra) -->
              <span
                v-if="isCurrentUrl(item.href)"
                class="absolute inset-x-4 -bottom-[2px] h-[3px] rounded-full bg-primary"
                aria-hidden="true"
              />
            </Link>
          </nav>
        </div>
      </div>
    </div>

    <!-- Content panel (mucho más limpio) -->
    <div class="mt-6">
      <div
        class="relative overflow-hidden rounded-3xl bg-background/60 p-5 shadow-sm ring-1 ring-foreground/5 backdrop-blur sm:p-6"
      >
        <!-- borde/acento lateral sutil -->
        <div
          class="pointer-events-none absolute left-0 top-10 h-24 w-[3px] rounded-full bg-primary/70"
          aria-hidden="true"
        />
        <div class="min-w-0">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
