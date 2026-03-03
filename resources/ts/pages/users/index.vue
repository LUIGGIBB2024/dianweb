<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref, watch } from 'vue'

// 🔹 Estado principal
const searchQuery = ref('')
const selectedRows = ref([])
const showDialog = ref(false)
const editMode = ref(false)
const showDialogPsw = ref(false)

const isPasswordVisible = ref(false)

// 🔹 Variables del DataTable
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const userFormRef = ref()
const isFormValid = ref(false)

const userData = ref({
        data: [],
        total: 0,
        page: 1,
        per_page: 10,
        totaldctos: 0,
  })

// watch(isFormValid, (newVal) => {
//   if (newVal) {
//     console.log('✅ Formulario válido')
//   } else if (newVal === false) {
//     console.log('❌ Formulario con errores')
//   }
// })

// 🔹 Encabezados de la tabla
const headers = [
  { title: '#', key: 'id', width: '5%' },
  { title: 'Nombre', key: 'name', sortable: true, width: '25%' },
  { title: 'Correo electrónico', key: 'email', sortable: true, width: '25%' },
  { title: 'Empresa', key: 'empresa', sortable: true },
  { title: 'Tipo de usuario', key: 'type', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'center', width: '12%' },
]
const token = localStorage.getItem('auth_token')

const userTypes = [
  { text: 'Super Administrador', value: 'SuperAdmin' },
  { text: 'Administrador', value: 'Administrador' },
  { text: 'Cliente', value: 'Cliente' },
]

// 🔹 Usuario actual (para creación/edición)
const newUser = ref({
  id: null,
  name: '',
  email: '',
  company_id: 0,
  type: '',
  password: '',
  empresa: '',
})

const changePsw = ref({
  password: '',
  confirmPassword: '',
  id: null,
})

// Contraseñas
const newPassword = ref('')
const confirmPassword = ref('')

// 🔹 Snackbar (mensajes)
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

// 🔹 Confirmación de eliminación
const showConfirmDialog = ref(false)
const userToDelete = ref<number | null>(null)
const nameUserToDelete = ref('')

// 🔹 Reglas
const rules = {
  required: (v: string) => !!v || 'Este campo es obligatorio',
  email: (v: string) => !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Correo inválido',
  password: (v: string) => v.length >= 6 || 'La contraseña debe tener al menos 6 caracteres',

}

// 🔹 Control de orden
const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// // 🔹 Cargar usuarios desde el backend
// const { data: userData, execute: fetchUsers } = await useApi<any>(
//   createUrl('http://127.0.0.1:8001/api/users', {
//     query: {
//       q: searchQuery,
//       itemsPerPage,
//       page,
//       sortBy,
//       orderBy,
//     },
//   }),
// )

const loadUsers = async () => 
{
     try {
        const response = await axios.get('/api/users', {
          params: {
                q: searchQuery.value,
                itemsPerPage: itemsPerPage.value,
                page: page.value,
                sortBy: sortBy.value,
                orderBy: orderBy.value,
              },
         }) 

         userData.value = response.data   
      } catch (error) {
        console.error('Error al intentar enviar correo :', error)
      }      
}

onMounted(() => loadUsers())
//onMounted(() => fetchUsers())

// 🔹 Actualizar tabla al buscar
watch(searchQuery, () => {
  console.log("Buscando usuarios con el término:", searchQuery.value)
  page.value = 1
  loadUsers()
})

// 🔹 Computed
const users = computed(() => userData.value?.data ?? [])
const companies = computed(() => userData.value?.companies ?? [])
const totalUsers = computed(() => userData.value?.total ?? 0)
const perPage = computed(() => userData.value.per_page ?? itemsPerPage.value)
const currentPage = computed(() => userData.value.page ?? page.value)


// 🔹 Abrir modal para crear
const openCreateDialog = () => {
  editMode.value = false
  newUser.value = { id: null, name: '', email: '', empresa: '', type: '', password: '', company_id: 0 }
  showDialog.value = true
}

// 🔹 Abrir modal para editar
const openEditDialog = (user: any) => {
  editMode.value = true
  newUser.value = { ...user }
  showDialog.value = true    
}

// 🔹 Abrir modal para crear
const openEditPassword = (user: any) => {
  editMode.value = true 
  changePsw.value = { password: '', confirmPassword: '', id: user.id }
  showDialogPsw.value = true
  
}

// 🔹 Guardar o actualizar empresa
const saveUser = async () => {
  try {  
      if (editMode.value)  
      {
        // 🟡 Editar empresa existente
        try {    
              await axios.put(`/api/users/${newUser.value.id}`,newUser.value)
                 
              snackbarMessage.value = 'Usuario actualizado correctamente' 
              showSnackbar.value = true
              showDialog.value = false
              //snackbarColor.value = 'error'
              showSnackbar.value = true             
              loadUsers()
            } catch (error) {
                  console.error('❌ Error al guardar empresa:', error)
            }
      } else 
        {
            try 
            {
              await axios.post('/api/users',newUser.value)                    
              snackbarMessage.value = 'Empresa creada correctamente'
              showSnackbar.value = true
              showDialog.value = false
              loadUsers()                    
            } catch (error) 
            {
              console.error('Error al intentar Crear la Emmpresa :', error)
            } 
          }
          showDialog.value = false
          loadUsers()
  } catch (error) {
    console.error('❌ Error al guardar empresa:', error)
  }
}

// 🔹 Guardar o actualizar Password / Contraseña
const savePassword = async () => {
 
  if (changePsw.value.password !== changePsw.value.confirmPassword) {
     snackbarMessage.value = '❌ Las contraseñas no coinciden'
     snackbarColor.value = 'error'
     showSnackbar.value = true
    return
  }

  const psw = changePsw.value.password

  try {
      await axios.put(`/api/password/${changePsw.value.id}`,
      {
        password: psw
      },
      {
        headers: { Authorization: `Bearer ${token}` },
      },)
      // await $api(`/api/password/${changePsw.value.id}`, {
      //   method: 'PUT',
      //   body: { password: changePsw.value.password },
      // })
      snackbarMessage.value = 'Contraseña actualizada correctamente'
      snackbarColor.value = 'success'
      showSnackbar.value = true
      showDialogPsw.value = false
  } catch (error) {
    snackbarMessage.value = '❌ Error al actualizar la contraseña'
    snackbarColor.value = 'error'
    showSnackbar.value = true
    console.error(error)
  }
}

// 🔹 Confirmar eliminación
const confirmDelete = (user: any) => {
  userToDelete.value = user.id
  nameUserToDelete.value = user.name
  showConfirmDialog.value = true
}

// 🔹 Eliminar usuario
const deleteUser = async () => {
  if (!userToDelete.value) return
  try {
    await $api(`/api/users/${userToDelete.value}`, { method: 'DELETE' })
    snackbarMessage.value = 'Usuario eliminado correctamente'
    snackbarColor.value = 'success'
    loadUsers()
  } catch (error) {
    snackbarMessage.value = '❌ Error al eliminar usuario'
    snackbarColor.value = 'error'
  } finally {
    showConfirmDialog.value = false
    showSnackbar.value = true
  }
}

</script>

<template>
  <section>
    <VCard>
      <!-- 🔹 Barra superior -->
      <VCardText class="d-flex align-center justify-space-between flex-wrap gap-4 toolbar-header py-3 px-4">
       
        <!-- Buscar -->
        <VTextField
          v-model="searchQuery"
          placeholder="Buscar usuario..."
          clearable
          density="compact"
          prepend-inner-icon="tabler-search"
          style="max-width: 250px"
          hide-details
        />

        <!-- Selector de registros -->
        <div class="d-flex align-center gap-2">
          <span class="text-body-2">Mostrar:</span>
          <VSelect
            v-model="itemsPerPage"
            :items="[5, 10, 20, 50]"
            density="compact"
            hide-details
            style="width: 90px;"
            @update:model-value="() => { page = 1; fetchUsers() }"
          />
        </div>

        <!-- Botón crear -->
        <VBtn color="primary" class="text-white" @click="openCreateDialog">
          <template #prepend>
            <VIcon icon="tabler-plus" size="20" />
          </template>
          Nuevo Usuario
        </VBtn>
      </VCardText>

      <VDivider />

      <!-- 🔹 Tabla -->
      <VDataTableServer
        v-model:model-value="selectedRows"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="users"
        :items-length="totalUsers"
        show-select
        class="text-body-2 company-table"
        @update:options="updateOptions"
      >
        <template #item.id="{ item }">#{{ item.id }}</template>
        <template #item.actions="{ item }">
            <IconBtn @click="openEditDialog(item)">
              <VIcon icon="tabler-edit" color="primary" />
            </IconBtn>
            <IconBtn @click="confirmDelete(item)">
              <VIcon icon="tabler-trash" color="error" />
            </IconBtn>
            <IconBtn @click="openEditPassword(item)">
              <VIcon icon="tabler-lock-check" color="success" />
            </IconBtn>
        </template>

        <template #bottom>
            <VDivider />
            <VRow class="mt-2 mx-0 pb-2 align-center">     
                  <VCol cols="12" md="4">
                      <div class="text-caption text-medium-emphasis ps-4">
                         Mostrando
                         <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                         <strong>{{ Math.min(currentPage * perPage, totalUsers) }}</strong>
                         de <strong>{{ totalUsers }}</strong> registros
                      </div>
                  </VCol>
                  <VCol cols="12" md="4" class="d-flex justify-center pagination-wrapper"> 
                      <VPagination
                         v-model="page"
                         :length="Math.ceil(totalUsers/ perPage)"
                         rounded="circle"
                         size="large"
                         :total-visible="5"
                      />
                  </VCol>             
              </VRow>           
        </template> 
      </VDataTableServer>     
    </VCard>

    <!-- 🔹 Modal Crear/Editar -->
    <VDialog v-model="showDialog" persistent max-width="500px">
      <VCard>
        <VCardTitle class="modal-title d-flex align-center">
          <VIcon icon="tabler-user" size="26" color="white" class="me-3" />
          <span>{{ editMode ? 'Editar usuario' : 'Agregar usuario' }}</span>
        </VCardTitle>
        <VCardText class="pt-4">
          <VForm @submit.prevent="saveUser" ref="userFormRef" v-model="isFormValid">
            <VTextField v-model="newUser.name"  label="Nombre" required :rules="[rules.required]" autofocus class="mb-3" @update:model-value="val => newUser.name = val.toUpperCase()">
                <template #prepend-inner>
                  <VIcon icon="tabler-user" color="primary" size="22" class="me-3" />
                </template>
            </VTextField>
            <VTextField v-model="newUser.email" label="Correo" type="email" required :rules="[rules.required, rules.email]" class="mb-3" @update:model-value="val => newUser.email = val.toLowerCase()">
                <template #prepend-inner>
                  <VIcon icon="tabler-user" color="primary" size="22" class="me-3" />
                </template>
            </VTextField>            
            <VTextField v-model="newUser.password" v-if="!editMode" :disabled="editMode" label="Password" :type="isPasswordVisible ? 'text' : 'password'" :rules="[rules.password]" 
                class="mb-3" :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'" @click:append-inner="isPasswordVisible = !isPasswordVisible">
                <template #prepend-inner>
                  <VIcon icon="tabler-lock" color="primary" size="22" class="me-3" />
                </template>
            </VTextField>
            <VSelect v-model="newUser.company_id" :items="companies" item-title="name" item-value="id" label="Empresa" :rules="[rules.required]" class="mb-3">
                <template #prepend-inner>
                  <VIcon icon="tabler-building" color="primary" size="22" class="me-3" />
                </template>
            </VSelect>   
            <!-- <VTextField v-model="newUser.company_id" label="Empresa" class="mb-3" />        -->
            <VSelect v-model="newUser.type" :items="userTypes" item-title="text" item-value="value" label="Tipo de usuario" :rules="[rules.required]" class="mb-3">
                <template #prepend-inner>
                    <VIcon icon="tabler-id" color="primary" size="22" class="me-3" />
                </template> 
            </VSelect>
          </VForm>
        </VCardText>
        <VCardActions class="justify-end pb-4 px-6">          
            <VBtn color="success" variant="flat" class = "text-white" @click="showDialog = false">Cancelar</VBtn>
            <VBtn color="primary" variant="flat" class = "text-white" @click="saveUser">Guardar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- 🔹 Modificar Contraseña  -->
    <VDialog v-model="showDialogPsw" persistent max-width="500px">
      <VCard>
        <VCardTitle class="modal-title d-flex align-center">
          <VIcon icon="tabler-lock" size="26" color="white" class="me-3" />
          <span>{{ editMode ? 'Cambiar Contraseña' : 'Cambiar Contraseña' }}</span>
        </VCardTitle>
        <VCardText class="pt-4">
          <VForm @submit.prevent="saveUser" ref="userFormRef" v-model="isFormValid">
            <VTextField v-model="changePsw.password" label="Contraseña" :type="isPasswordVisible ? 'text' : 'password'" required :rules="[rules.required]" autofocus class="mb-3"
               :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'" @click:append-inner="isPasswordVisible = !isPasswordVisible">
               <template #prepend-inner>
                  <VIcon icon="tabler-lock" color="primary" size="22" class="me-3" />
                </template>
            </VTextField>
            <VTextField v-model="changePsw.confirmPassword" label="Confirmar Contraseña" :type="isPasswordVisible ? 'text' : 'password'" required :rules="[rules.required]" class="mb-3"  
              :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'" @click:append-inner="isPasswordVisible = !isPasswordVisible">
                <template #prepend-inner>
                    <VIcon icon="tabler-lock" color="primary" size="22" class="me-3" />
                  </template>
            </VTextField>
          </VForm>
        </VCardText>
        <VCardActions class="justify-end pb-4 px-6">         
          <VBtn color="success" variant="flat" class = "text-white" @click="showDialogPsw = false">Cancelar</VBtn>
          <VBtn color="primary" variant="flat" class = "text-white" @click="savePassword">Guardar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- 🔹 Confirmación de eliminación -->
    <VDialog v-model="showConfirmDialog" max-width="400px">
      <VCard>
        <VCardTitle class="text-h6 text-center pt-4">
          <VIcon icon="tabler-alert-circle" color="warning" size="26" class="me-2" />
          Confirmar eliminación
        </VCardTitle>
        <VCardText class="text-center">
          ¿Desea eliminar el usuario <strong>{{ nameUserToDelete }}</strong>?<br />
          <small>Esta acción no se puede deshacer.</small>
        </VCardText>
        <VCardActions class="justify-center pb-4">
          <VBtn color="secondary" variant="tonal" @click="showConfirmDialog = false">Cancelar</VBtn>
          <VBtn color="error" variant="flat" @click="deleteUser">Eliminar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- 🔹 Snackbar -->
    <VSnackbar v-model="showSnackbar" :color="snackbarColor" location="center" timeout="2500">
      <div class="d-flex align-center">
        <VIcon
          :icon="snackbarColor === 'success' ? 'tabler-check' : 'tabler-alert-triangle'"         
          size="25"
          class="me-2"
        />
         <span class="text-lg">{{ snackbarMessage }}</span>
      </div>
    </VSnackbar>
  </section>
</template>

<style lang="scss">
.toolbar-header {
  background-color: #f8f9fa;
  border-radius: 8px;
}

 .v-data-table__thead th 
  {
      background-color: rgb(247, 58, 206) !important;
      color: white !important;
  }

.modal-title {
  background-color: rgb(var(--v-theme-primary));
  color: white;
  padding: 16px 24px;
  font-weight: 600;
  font-size: 1.2rem;
  border-top-left-radius: 6px;
  border-top-right-radius: 6px;
}

.pagination-wrapper .v-pagination__item--active .v-btn {
  background-color: rgb(var(--v-theme-primary)) !important;
  color: white !important;
}

.v-data-table__thead th {
  background-color: rgb(138, 238, 91);
  color: white;
}

.d-none {
  display: none !important;
}
</style>

