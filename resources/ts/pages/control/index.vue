<script setup lang="ts">
import { router } from '@/plugins/1.router';
import axios from 'axios'
import { onMounted, ref } from 'vue'

// 🔹 Filtros y variables de estado
const searchQuery = ref('')
const selectedRows = ref([])

// 🔹 Opciones del datatable
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const controlFormRef = ref()
const isFormValid = ref(false)
const isPasswordVisible = ref(false)

// 🔹 Actualizar opciones de orden
const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const showDialog = ref(false)
const editMode = ref(false) // 👈 false = crear, true = editar

const newControl = ref({
  name: '',
  address: '',
  phone: '',
  email: '',
  password: '',
  nit: '',
  path: '',
  token:'',
  technicalkey: '',
  idsoftware: '',
  testsetid: '',
})

// 🔹 Snackbar (toast)
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const token = localStorage.getItem('auth_token')

// 🔹 Reglas de validación
const rules = {
  required: (value: string) => !!value || 'Este campo es obligatorio',
  email: (value: string) =>
    !value || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Correo inválido',
  phone: (value: string) =>
    !value || value.length >= 7 || 'Debe tener al menos 7 dígitos',
  password: (v: string) => v.length >= 6 || 'La contraseña debe tener al menos 6 caracteres',
}

// 🔹 Cargar información del control (solo 1 registro)
const _controlData = ref<any>(null)

const loadControl = async () => { 
  try {

    const response = await axios.get(`/api/getcontrol/1`,
      {
        headers: { Authorization: `Bearer ${token}`},
      },
    )
    //_controlData.value = data.value
    if (response.data) 
    {
        //console.log("Estoy Aqui 200:",response.data)
        const controlInfo = response.data.data  ?? response.data 
        _controlData.value = controlInfo.data
        newControl.value = { ...controlInfo }
        //console.log('🔄 Cargando información de control..:',newControl.value)
    }
  } catch (error) 
  {
    console.error('❌ Error al cargar control:', error)
    snackbarMessage.value = 'Error al cargar información'
    snackbarColor.value = 'error'
    showSnackbar.value = true
  }
}

// 🔹 Actualizar el registro existente
const saveControl = async () => {
  try {
    // await $api(`/api/control/1}`, {
    //   method: 'PUT',
    //   body: newControl.value,
    // })

    const response = await axios.put(`/api/control/1`,
      newControl.value, 
      {
        headers: { Authorization: `Bearer ${token}` },
      },
    )

    snackbarMessage.value = 'Información actualizada correctamente'
    snackbarColor.value = 'success'

    // ✅ Redirigir después de guardar
    //CerrarVista

  } catch (error) {
    console.error('❌ Error al actualizar control:', error)
    snackbarMessage.value = 'Error al actualizar la información'
    snackbarColor.value = 'error'
  } finally {
    showSnackbar.value = true
  }
}

// 🔹 Cargar al montar el componente
onMounted(() => { loadControl() })

const CerrarVista = async () => {
  try {
    // 🔹 Si quieres mostrar un snackbar antes, usa await explícito
    // await mostrarSnackbar('Saliendo...', 'info')

    // 🔹 Siempre usa rutas absolutas
    if (router.currentRoute.value.path !== '/dashboard') {
      await router.push('/dashboard')
      console.log('✅ Redirigido correctamente a /dashboard')
    } else {
      console.log('ℹ️ Ya estás en /dashboard')
    }
  } catch (error) {
    console.error('❌ Error al intentar redirigir:', error)
  }
}

</script>

<template>
   <section>
       <VCard>
        <VCardTitle class="modal-title d-flex align-center">
          <VIcon icon="tabler-building" size="26" color="warning" class="me-3" />
          <span>{{ 'Actualizar Información de la Empresa'}}</span>
        </VCardTitle>
        <VCardText class="pt-4 mb-4">
          <VForm @submit.prevent="saveControl" ref="userFormRef" v-model="isFormValid">
             <VRow class="mt-4">
                <VCol cols="12" md="6" class="py-0">
                  <VTextField v-model="newControl.nit" label="Nit de la Empresa"  required :rules="[rules.required]" autofocus class="mb-3" placeholder="Ingrese el NIT de la empresa"
                    @update:model-value="val => newControl.nit = val.toUpperCase()">
                    <template #prepend-inner>
                      <VIcon icon="tabler-id" color="primary" size="22" class="me-3" />
                    </template>
                  </VTextField>
                </VCol>
                <!-- 🏢 Campo: Nombre -->
                <VCol cols="12" md="6" class="py-1">
                  <VTextField v-model="newControl.name" label="Nombre de la Empresa"  required  :rules="[rules.required]" class="mb-3" placeholder="Ingrese el nombre de la empresa"
                    @update:model-value="val => newControl.name = val.toUpperCase()">
                    <template #prepend-inner>
                      <VIcon icon="tabler-building" color="primary" size="22" class="me-3" />
                    </template>
                  </VTextField>
                </VCol>
             </VRow>

             <VRow>
                <VCol cols="12" md="6" class="py-0">
                  <VTextField v-model="newControl.address" label="Dirección de la Empresa"  required :rules="[rules.required]" class="mb-3" placeholder="Ingrese Dirección de la Empresa"
                    @update:model-value="val => newControl.address = val.toUpperCase()">
                    <template #prepend-inner>
                      <VIcon icon="tabler-map-pins" color="primary" size="22" class="me-3" />
                    </template>
                  </VTextField>
                </VCol>
                <!-- 🏢 Campo: Nombre -->
                <VCol cols="12" md="6" class="py-0">
                  <VTextField v-model="newControl.email" label="Correo Electrónico" type="email" required :rules="[rules.required, rules.email]" class="mb-3" @update:model-value="val => newControl.email = val.toLowerCase()"
                    placeholder="Ingrese Correo Electrónico">                    
                      <template #prepend-inner>
                        <VIcon icon="tabler-mail" color="primary" size="22" class="me-3" />
                      </template>
                  </VTextField>  
                </VCol>
             </VRow>

             <VRow>
                <VCol cols="12" md="6" class="py-0">
                  <VTextField v-model="newControl.phone" label="Teléfono de la Empresa"  type="phone" required :rules="[rules.required]" class="mb-3" placeholder="Ingrese Teléfono de la Empresa"
                    @update:model-value="val => newControl.phone = val.toUpperCase()">
                    <template #prepend-inner>
                      <VIcon icon="tabler-phone" color="primary" size="22" class="me-3" />
                    </template>
                  </VTextField>
                </VCol>
                <!-- 🏢 Campo: Nombre -->
                <VCol cols="12" md="6" class="py-0">
                  <VTextField v-model="newControl.path" label="Ruta de las Imágenes" type="text" required :rules="[rules.required]" class="mb-3" @update:model-value="val => newControl.path = val.toLowerCase()">
                      <template #prepend-inner>
                        <VIcon icon="tabler-route" color="primary" size="22" class="me-3" />
                      </template>
                  </VTextField>  
                </VCol>
             </VRow>           
  
          </VForm>
        </VCardText>

        <VDivider />     

        <VCardActions class="justify-end pb-4 px-6 mt-4">          
            <VBtn color="success" variant="flat" class = "text-white" @click="CerrarVista">Cancelar</VBtn>
            <VBtn color="primary" variant="flat" class = "text-white" @click="saveControl">Guardar</VBtn>
        </VCardActions>
        <!-- 🔹 Snackbar -->
        <VSnackbar v-model="showSnackbar" :color="snackbarColor" location="center" timeout="2500">
      <div class="d-flex align-center">
        <VIcon
          :icon="snackbarColor === 'success' ? 'tabler-check' : 'tabler-alert-triangle'" size="25" class="me-2"/>
         <span class="text-lg">{{ snackbarMessage }}</span>
      </div>
    </VSnackbar>
   
      </VCard>
      
    

    </section>
</template>

<style lang="scss">
  .modal-title {
          background-color: rgb(var(--v-theme-primary)); /* color primario del tema */
          color: white; /* texto blanco */
          padding: 16px 24px;
          font-weight: 600;
          font-size: 1.25rem;
          border-top-left-radius: 6px;
          border-top-right-radius: 6px;
          margin: 0;
        }
</style>
