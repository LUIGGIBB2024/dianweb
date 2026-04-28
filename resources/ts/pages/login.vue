<script setup lang="ts">
import axios from 'axios'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import AuthProvider from '@/views/pages/authentication/AuthProvider.vue'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

// --- Configuración de la página ---
definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

// --- Datos del formulario ---
const form = ref({
  email: '',
  password: '',
  remember: false,
})

// --- Estado ---
const isPasswordVisible = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

// --- Router ---
const router = useRouter()

// --- Generación de imágenes según tema ---
const authThemeImg = useGenerateImageVariant(
  authV2LoginIllustrationLight,
  authV2LoginIllustrationDark,
  authV2LoginIllustrationBorderedLight,
  authV2LoginIllustrationBorderedDark,
  true
)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

// --- Función de Login ---
const handleLogin = async () => {
  errorMessage.value = ''
  isLoading.value = true

  try {
    if (!form.value.email || !form.value.password) {
      errorMessage.value = 'Debes ingresar tu correo y contraseña.'
      isLoading.value = false
      return
    }

    const { data } = await axios.post('/api/login', {
      email: form.value.email,
      password: form.value.password,
    })

    console.log('Login exitoso:', data)
    const url_token_info = ref('https://catalogo-vpfe.dian.gov.co/User/AuthToken?pk=10910094%7C77193886&amp;rk=901148547&amp;token=560d3a3c-c689-40e9-9eed-f5edce77ddd8')

    // ✅ 1. Limpiar TODO el localStorage antes de escribir la nueva sesión
    //       Esto evita que queden residuos de la sesión anterior
    localStorage.clear()

    // ✅ 2. Escribir los nuevos valores
    localStorage.setItem('auth_token', data.token)
    localStorage.setItem('company_name', data.company_name)
    localStorage.setItem('user_name', data.user_name)
    localStorage.setItem('user_id', data.user_id)
    localStorage.setItem('company_id', data.company_id)
    localStorage.setItem('url_n8n', data.url_n8n)
    localStorage.setItem('nit_empresa', data.nit_empresa)
    localStorage.setItem('representante_legal', data.representante_legal)
    localStorage.setItem('tipo_de_usuario', data.user['type'])
    localStorage.setItem('dian_token_info', url_token_info.value)

    window.company_user = data.company_name

    const tipoUsuario = data.user['type'] // ✅ 3. Leer directo de `data`, NO de localStorage

    console.log('🧭 Tipo usuario:', tipoUsuario)

    // ✅ 4. Usar await en router.push para asegurarse que la navegación
    //       ocurra DESPUÉS de que todo lo anterior esté listo

    if (tipoUsuario === 'SuperAdmin') {
      window.location.href = '/dashboard'        // ← recarga completa, limpia todo
    } else if (tipoUsuario === 'Cliente SaaS') {
      window.location.href = '/dashboard-saas'
    } else if (tipoUsuario === 'Cliente Phx') {
      window.location.href = '/dashboard-saas'
    }

    // if (tipoUsuario === 'SuperAdmin') {
    //   await router.push({ name: 'dashboard' })
    // } else if (tipoUsuario === 'Cliente SaaS') {
    //   await router.push({ name: 'dashboard-saas' })
    // } else if (tipoUsuario === 'Cliente Phx') {
    //   await router.push({ name: 'dashboard-saas' })
    // } else {
    //   // ✅ 5. Manejar tipo de usuario desconocido
    //   console.warn('Tipo de usuario no reconocido:', tipoUsuario)
    //   errorMessage.value = 'Tipo de usuario no válido.'
    // }

  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || 'Credenciales incorrectas. Intenta de nuevo.'
    console.log("Soy Error:", errorMessage.value)
  } finally {
    isLoading.value = false
  }
}

</script>

<template>
  <a href="javascript:void(0)">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">{{ themeConfig.app.title }}</h1>
    </div>
  </a>

  <VRow no-gutters class="auth-wrapper bg-surface">
    <!-- Imagen lateral -->
    <VCol md="8" class="d-none d-md-flex">
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem;"
        >
          <VImg
            max-width="613"
            :src="authThemeImg"
            class="auth-illustration mt-16 mb-2"
          />
        </div>

        <img
          class="auth-footer-mask flip-in-rtl"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        />
      </div>
    </VCol>

    <!-- Formulario -->
    <VCol cols="12" md="4" class="auth-card-v2 d-flex align-center justify-center">
      <VCard flat :max-width="500" class="mt-12 mt-sm-0 pa-6">
        <VCardText>
          <h4 class="text-h4 mb-1">
            Bienvenido a <span class="text-capitalize">{{ themeConfig.app.title }}</span> ! 👋🏻
          </h4>
          <p class="mb-0">Inicia sesión en tu cuenta</p>
        </VCardText>

        <VCardText>
          <!-- 🔹 Mensaje de error -->
          <VAlert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ errorMessage }}
          </VAlert>

          <!-- 🔹 Formulario -->
          <VForm @submit.prevent="handleLogin">
            <VRow>
              <!-- Email -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.email"
                  autofocus
                  label="Email :"
                  type="email"
                  placeholder="johndoe@email.com"
                />
              </VCol>

              <!-- Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.password"
                  label="Contraseña :"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />

                <div
                  class="d-flex align-center flex-wrap justify-space-between my-6"
                >
                  <VCheckbox
                    v-model="form.remember"
                    label="Recordarme"
                  />
                  <a
                    class="text-primary"
                    href="javascript:void(0)"
                  >
                    Olvidó su Contraseña?
                  </a>
                </div>

                <VBtn
                  block
                  type="submit"
                  :loading="isLoading"
                  color="primary"
                >
                  Iniciar Sesión
                </VBtn>
              </VCol>

              <!-- Crear cuenta -->
              <VCol cols="12" class="text-body-1 text-center">
                <span class="d-inline-block">¿Eres nuevo en la plataforma?</span>
                <a
                  class="text-primary ms-1 d-inline-block text-body-1"
                  href="javascript:void(0)"
                >
                  Crear una cuenta
                </a>
              </VCol>

              <VCol cols="12" class="d-flex align-center">
                <VDivider />
                <span class="mx-4">o</span>
                <VDivider />
              </VCol>

              <!-- Proveedores -->
              <VCol cols="12" class="text-center">
                <AuthProvider />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
    @use "@core-scss/template/pages/page-auth";
</style>
