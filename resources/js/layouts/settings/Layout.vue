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
    <!-- Header + Tabs -->
    <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
      <!-- Acento de color en la parte superior -->
      <div class="h-1 bg-primary" />

      <div class="px-5 pt-4 pb-2 sm:px-6">
        <Heading
          title="Configuración de cuenta"
          description="Perfil, seguridad y preferencias de visualización"
        />
      </div>

      <!-- Tabs -->
      <div class="px-3 pb-3 sm:px-4">
        <nav
          aria-label="Navegación de configuración"
          class="flex flex-wrap gap-1"
        >
          <Link
            v-for="item in navItems"
            :key="toUrl(item.href)"
            :href="item.href"
            class="group flex items-center gap-2 rounded-xl px-3 py-2 text-sm transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
            :class="isCurrentUrl(item.href)
              ? 'bg-primary/10 text-primary font-semibold'
              : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
            :aria-current="isCurrentUrl(item.href) ? 'page' : undefined"
          >
            <component
              :is="item.icon"
              class="h-4 w-4 shrink-0"
              :class="isCurrentUrl(item.href) ? 'text-primary' : 'text-muted-foreground group-hover:text-foreground'"
            />
            {{ item.title }}
          </Link>
        </nav>
      </div>
    </div>

    <!-- Content panel -->
    <div class="mt-4">
      <div class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
        <slot />
      </div>
    </div>
  </div>
</template>
