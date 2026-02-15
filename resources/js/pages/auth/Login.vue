<script setup lang="ts">
import { ref } from 'vue'
import { Form, Head } from '@inertiajs/vue3'
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import { store } from '@/routes/login'
import { request } from '@/routes/password'

defineProps<{
  status?: string
  canResetPassword: boolean
  canRegister: boolean
}>()

const BG = '/bgFisioVida.png'
const year = new Date().getFullYear()
const showPassword = ref(false)
</script>

<template>
  <Head title="Iniciar sesión" />

  <main class="fv-screen">
    <div class="fv-bg" aria-hidden="true">
      <img :src="BG" alt="" class="fv-bg-img" draggable="false" />
    </div>

    <div class="fv-center">
      <div class="fv-stack">
        <div class="fv-card">
          <div class="fv-brand">
            <div class="fv-logo">
              <img src="/favicon.ico" alt="FisioVida" class="fv-logo-img" draggable="false" />
            </div>

            <h1 class="fv-title">Bienvenido a FisioVida</h1>
            <p class="fv-subtitle">Accede a tu cuenta para entrar al panel.</p>

            <div v-if="status" class="fv-status">
              {{ status }}
            </div>
          </div>

          <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="fv-form"
          >
            <!-- Animación / mensaje de validación -->
            <div v-if="processing" class="fv-wait" role="status" aria-live="polite">
              <Spinner />
              <span>Validando usuario…</span>
              <span class="fv-dots" aria-hidden="true">
                <span></span><span></span><span></span>
              </span>
            </div>

            <div class="fv-field">
              <Label for="email" class="fv-label">Correo electrónico</Label>
              <Input
                id="email"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="email"
                placeholder="correo@ejemplo.com"
                class="fv-input"
              />
              <InputError :message="errors.email" />
            </div>

            <div class="fv-field">
              <div class="fv-row">
                <Label for="password" class="fv-label">Contraseña</Label>
              </div>

              <div class="fv-pass">
                <Input
                  id="password"
                  :type="showPassword ? 'text' : 'password'"
                  name="password"
                  required
                  autocomplete="current-password"
                  placeholder="••••••••"
                  class="fv-input fv-input-pass"
                />

                <button
                  type="button"
                  class="fv-eye"
                  :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                  :title="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                  @click="showPassword = !showPassword"
                >
                  <svg
                    v-if="!showPassword"
                    viewBox="0 0 24 24"
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                  >
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>

                  <svg
                    v-else
                    viewBox="0 0 24 24"
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                  >
                    <path d="M3 3l18 18"/>
                    <path d="M10.6 10.6a2.5 2.5 0 0 0 3.3 3.3"/>
                    <path d="M9.9 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a18.3 18.3 0 0 1-4.3 5.2"/>
                    <path d="M6.2 6.2C3.5 8.2 2 12 2 12s3.5 7 10 7c1 0 1.9-.1 2.8-.4"/>
                  </svg>
                </button>
              </div>

              <InputError :message="errors.password" />
            </div>

            <div class="fv-row fv-end">
              <TextLink v-if="canResetPassword" :href="request()" class="fv-link">
                ¿Olvidó su contraseña?
              </TextLink>
            </div>

            <Button
              type="submit"
              class="fv-btn"
              :disabled="processing"
              data-test="login-button"
            >
              <span class="fv-btn-shine" aria-hidden="true" />
              <span class="fv-btn-content">
                <Spinner v-if="processing" />
                <span>{{ processing ? 'Validando…' : 'Iniciar sesión' }}</span>
              </span>

              <!-- barra animada al validar -->
              <span v-if="processing" class="fv-btn-bar" aria-hidden="true"></span>
            </Button>
          </Form>
        </div>

        <div class="fv-footer" aria-label="Redes sociales">
          <button type="button" class="fv-social" aria-label="Facebook" title="Facebook">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
              <path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.6-1.6H16.7V4.8c-.3 0-1.4-.1-2.7-.1-2.7 0-4.5 1.6-4.5 4.6V11H7v3h2.5v8h4z"/>
            </svg>
          </button>

          <button type="button" class="fv-social" aria-label="Instagram" title="Instagram">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
              <path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9A5.5 5.5 0 0 1 16.5 22h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2zm9 2h-9A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4zm-4.5 4a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9zm0 2a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zm5.6-2.2a1.05 1.05 0 1 1-2.1 0 1.05 1.05 0 0 1 2.1 0z"/>
            </svg>
          </button>

          <button type="button" class="fv-social" aria-label="WhatsApp" title="WhatsApp">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
              <path d="M20.5 11.9A8.5 8.5 0 0 1 7 18.7L3 20l1.4-3.8A8.5 8.5 0 1 1 20.5 11.9zm-8.5-6.5a6.5 6.5 0 0 0-5.6 9.8l.2.3-.8 2.1 2.2-.7.3.2A6.5 6.5 0 1 0 12 5.4zm3.7 9.3c-.2.6-1.1 1.1-1.6 1.2-.4.1-.8.2-2.7-.5-2.4-1-3.9-3.3-4-3.4-.1-.2-1-1.3-1-2.5 0-1.2.6-1.8.9-2.1.2-.2.4-.2.6-.2h.4c.1 0 .3 0 .4.3l.7 1.6c.1.2.1.4 0 .5l-.2.3c-.1.2-.2.3-.4.5-.1.1-.2.3-.1.5.1.2.6 1.1 1.4 1.7.9.8 1.6 1 1.8 1.1.2.1.4.1.5-.1l.6-.7c.2-.2.3-.2.5-.1l1.7.8c.2.1.3.2.3.4 0 .1 0 .6-.2 1.2z"/>
            </svg>
          </button>

          <button type="button" class="fv-social" aria-label="TikTok" title="TikTok">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
              <path d="M16.5 3c.7 2.4 2.4 4.2 4.8 4.8V11c-1.8 0-3.4-.6-4.8-1.6v6.4c0 3.3-2.7 6-6 6s-6-2.7-6-6 2.7-6 6-6c.4 0 .8 0 1.2.1v3.3c-.4-.2-.8-.3-1.2-.3-1.5 0-2.7 1.2-2.7 2.7s1.2 2.7 2.7 2.7 2.7-1.2 2.7-2.7V3h3.3z"/>
            </svg>
          </button>
        </div>

        <div class="fv-copy">© {{ year }} FisioVida</div>
      </div>
    </div>
  </main>
</template>

<style scoped src="@/../css/FisioVidaLogin.css"></style>
