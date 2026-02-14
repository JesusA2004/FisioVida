<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import { register } from '@/routes'
import { store } from '@/routes/login'
import { request } from '@/routes/password'

defineProps<{
  status?: string
  canResetPassword: boolean
  canRegister: boolean
}>()

/**
 * Imagen del panel izquierdo (en /public).
 * Nota para mi yo del futuro: aquí solo cambiamos el archivo, el diseño no se toca.
 */
const BG_PANEL = '/bgFisioVida.png'
</script>

<template>
  <Head title="Iniciar sesión" />

  <!--
    Layout pantalla completa:
    - lg+: 2 columnas (50/50), ambos paneles con height completo
    - <lg: solo panel derecho (form), full height, sin fondo global raro
  -->
  <div class="min-h-screen w-full text-foreground lg:grid lg:grid-cols-2">
    <!-- =========================
         Panel Izquierdo (solo lg+)
         ========================= -->
    <section class="relative hidden lg:block">
      <!-- Fondo real: imagen -->
      <div
        class="absolute inset-0 bg-cover bg-center"
        :style="{ backgroundImage: `url('${BG_PANEL}')` }"
      />

      <!--
        Overlay para legibilidad y estética.
        Nota: evita que el fondo “queme” y deja el look premium.
      -->
      <div class="absolute inset-0 bg-gradient-to-tr from-black/75 via-black/35 to-transparent" />

      <!-- Detalles sutiles (marca + profundidad) -->
      <div class="absolute inset-0 ring-inset ring-1 ring-white/10" />
      <div class="fv-shine absolute -left-1/2 top-0 h-full w-1/2 bg-gradient-to-r from-transparent via-white/12 to-transparent" />

      <!--
        Aquí NO pongo texto.
        Nota para futuro: si luego queremos slogan o bullets, se colocan aquí,
        pero manteniendo la zona “limpia” para que el SaaS sea reusado por clientes.
      -->
    </section>

    <!-- =========================
         Panel Derecho (formulario)
         ========================= -->
    <section class="relative flex min-h-screen items-center justify-center bg-background">
      <!--
        Fondo del panel derecho:
        No blanco plano: degradado suave + blobs para “SaaS vibes”
        (todo queda dentro del panel derecho, sin afectar el izquierdo)
      -->
      <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-background via-background to-background/80" />
        <div class="fv-blob absolute -top-28 -right-24 h-[360px] w-[360px] rounded-full bg-primary/18 blur-3xl" />
        <div class="fv-blob2 absolute -bottom-28 -left-24 h-[420px] w-[420px] rounded-full bg-accent/22 blur-3xl" />
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,.08),transparent_55%)] dark:bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,.06),transparent_55%)]" />
      </div>

      <!-- Contenedor del formulario -->
      <div class="relative w-full max-w-md px-5 py-10 sm:px-8">
        <!-- Header -->
        <div class="mb-6 text-center">
          <h1 class="text-2xl font-extrabold tracking-tight">
            Bienvenido de vuelta
          </h1>
          <p class="mt-1 text-sm text-muted-foreground">
            Inicia sesión para entrar a tu panel.
          </p>

          <!-- Mensaje de status desde backend (si aplica) -->
          <div
            v-if="status"
            class="mt-4 rounded-2xl border bg-primary/10 px-4 py-3 text-sm"
          >
            {{ status }}
          </div>
        </div>

        <Form
          v-bind="store.form()"
          :reset-on-success="['password']"
          v-slot="{ errors, processing }"
          class="space-y-5"
        >
          <!--
            Banner de “verificando…”
            Nota: esta parte vende confianza (el usuario siente que el sistema está trabajando).
          -->
          <div
            v-if="processing"
            class="fv-entrance2 rounded-2xl border bg-background/50 px-4 py-3 backdrop-blur"
          >
            <div class="flex items-center gap-3">
              <div
                class="grid h-9 w-9 place-items-center rounded-xl bg-primary/15 ring-1 ring-primary/25"
              >
                <Spinner />
              </div>

              <div class="min-w-0">
                <div class="text-sm font-bold">
                  Verificando acceso<span class="fv-dots"></span>
                </div>
                <div class="text-xs text-muted-foreground">
                  Validando credenciales…
                </div>
              </div>
            </div>

            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-muted">
              <div class="fv-bar h-full w-1/3 rounded-full bg-primary" />
            </div>
          </div>

          <!-- Email -->
          <div class="grid gap-2">
            <Label for="email">Correo electrónico</Label>
            <Input
              id="email"
              type="email"
              name="email"
              required
              autofocus
              autocomplete="email"
              placeholder="correo@ejemplo.com"
              :tabindex="1"
              class="transition-shadow focus:shadow-[0_0_0_4px_hsl(var(--ring)_/_0.18)]"
            />
            <InputError :message="errors.email" />
          </div>

          <!-- Password -->
          <div class="grid gap-2">
            <div class="flex items-center justify-between gap-3">
              <Label for="password">Contraseña</Label>

              <TextLink
                v-if="canResetPassword"
                :href="request()"
                class="text-sm"
                :tabindex="5"
              >
                ¿Olvidaste tu contraseña?
              </TextLink>
            </div>

            <Input
              id="password"
              type="password"
              name="password"
              required
              autocomplete="current-password"
              placeholder="Tu contraseña"
              :tabindex="2"
              class="transition-shadow focus:shadow-[0_0_0_4px_hsl(var(--ring)_/_0.18)]"
            />
            <InputError :message="errors.password" />
          </div>

          <!-- CTA -->
          <Button
            type="submit"
            class="group relative mt-1 w-full overflow-hidden"
            :tabindex="4"
            :disabled="processing"
            data-test="login-button"
          >
            <!-- Brillito hover -->
            <span
              class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-full"
            />
            <span class="relative inline-flex items-center justify-center gap-2">
              <Spinner v-if="processing" />
              <span>{{ processing ? 'Iniciando sesión…' : 'Entrar' }}</span>
            </span>
          </Button>

          <!-- Redes (sin links) -->
          <div class="pt-2">
            <div class="flex items-center gap-3">
              <div class="h-px flex-1 bg-border" />
              <div class="text-xs text-muted-foreground">Redes sociales</div>
              <div class="h-px flex-1 bg-border" />
            </div>

            <div class="mt-4 flex items-center justify-center gap-3">
              <!-- Facebook -->
              <button type="button" class="fv-social-btn" aria-label="Facebook" title="Facebook">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                  <path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.6-1.6H16.7V4.8c-.3 0-1.4-.1-2.7-.1-2.7 0-4.5 1.6-4.5 4.6V11H7v3h2.5v8h4z"/>
                </svg>
              </button>

              <!-- Instagram -->
              <button type="button" class="fv-social-btn" aria-label="Instagram" title="Instagram">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                  <path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9A5.5 5.5 0 0 1 16.5 22h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2zm9 2h-9A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4zm-4.5 4a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9zm0 2a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zm5.6-2.2a1.05 1.05 0 1 1-2.1 0 1.05 1.05 0 0 1 2.1 0z"/>
                </svg>
              </button>

              <!-- WhatsApp -->
              <button type="button" class="fv-social-btn" aria-label="WhatsApp" title="WhatsApp">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                  <path d="M20.5 11.9A8.5 8.5 0 0 1 7 18.7L3 20l1.4-3.8A8.5 8.5 0 1 1 20.5 11.9zm-8.5-6.5a6.5 6.5 0 0 0-5.6 9.8l.2.3-.8 2.1 2.2-.7.3.2A6.5 6.5 0 1 0 12 5.4zm3.7 9.3c-.2.6-1.1 1.1-1.6 1.2-.4.1-.8.2-2.7-.5-2.4-1-3.9-3.3-4-3.4-.1-.2-1-1.3-1-2.5 0-1.2.6-1.8.9-2.1.2-.2.4-.2.6-.2h.4c.1 0 .3 0 .4.3l.7 1.6c.1.2.1.4 0 .5l-.2.3c-.1.2-.2.3-.4.5-.1.1-.2.3-.1.5.1.2.6 1.1 1.4 1.7.9.8 1.6 1 1.8 1.1.2.1.4.1.5-.1l.6-.7c.2-.2.3-.2.5-.1l1.7.8c.2.1.3.2.3.4 0 .1 0 .6-.2 1.2z"/>
                </svg>
              </button>
            </div>
          </div>
        </Form>

        <!-- Footer -->
        <div class="mt-6 text-center text-xs text-muted-foreground">
          © {{ new Date().getFullYear() }} FisioVida
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* Animación de entrada (suave, tipo SaaS) */
@keyframes fv-fade-up {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.fv-entrance { animation: fv-fade-up .45s ease-out both; }
.fv-entrance2 { animation: fv-fade-up .25s ease-out both; }

/* Barra de progreso cuando está “processing” */
@keyframes fv-bar {
  0% { transform: translateX(-80%); }
  50% { transform: translateX(60%); }
  100% { transform: translateX(180%); }
}
.fv-bar { animation: fv-bar 1.1s ease-in-out infinite; }

/* Puntitos “...” animados */
@keyframes fv-dots {
  0% { content: ""; }
  25% { content: "."; }
  50% { content: ".."; }
  75% { content: "..."; }
  100% { content: ""; }
}
.fv-dots::after {
  display: inline-block;
  width: 1.5em;
  text-align: left;
  content: "";
  animation: fv-dots 1.2s infinite;
}

/* Shine del panel izquierdo */
@keyframes fv-shine {
  0% { transform: translateX(-140%); opacity: .0; }
  35% { opacity: .25; }
  100% { transform: translateX(220%); opacity: 0; }
}
.fv-shine { animation: fv-shine 3.2s ease-in-out infinite; }

/* Blobs del panel derecho (movimiento ultra sutil) */
@keyframes fv-blob {
  0% { transform: translate3d(0,0,0) scale(1); }
  50% { transform: translate3d(10px, -12px, 0) scale(1.06); }
  100% { transform: translate3d(0,0,0) scale(1); }
}
.fv-blob { animation: fv-blob 7s ease-in-out infinite; }
.fv-blob2 { animation: fv-blob 8.5s ease-in-out infinite reverse; }

/* Botones sociales: sólo UI (sin links aún) */
.fv-social-btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  height:44px;
  width:44px;
  border-radius:14px;
  border:1px solid hsl(var(--border) / 0.85);
  background: hsl(var(--background) / 0.6);
  color: hsl(var(--foreground) / 0.92);
  transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, border-color .18s ease;
}
.fv-social-btn:hover{
  transform: translateY(-2px);
  background: hsl(var(--accent) / 0.35);
  border-color: hsl(var(--ring) / 0.45);
  box-shadow: 0 0 0 4px hsl(var(--ring) / 0.14);
}
.fv-social-btn:active{
  transform: translateY(0px) scale(.98);
}
</style>
