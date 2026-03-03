<script setup lang="ts">
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { VCol, VDivider } from 'vuetify/components'
import { VBtn } from 'vuetify/components/VBtn'
import { VCard, VCardActions, VCardText, VCardTitle } from 'vuetify/components/VCard'

// 🔹 Filtros y variables de estado
const searchQuery = ref('')
const selectedRows = ref([])

// 🔹 Opciones del datatable
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const hoy = new Date().toISOString().split('T')[0]

//const token = localStorage.getItem('auth_token')

const accessToken = useCookie('accessToken', { path: '/' })
//accessToken.value = response.data.token // ← el que te devuelve Laravel

// 🔹 Actualizar opciones de orden
const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// 🔹 Encabezados de la tabla
const headers = [
  { title: '#', key: 'id' },
  { title: 'Nit', key: 'nit', sortable: true,width: '10%', },
  { title: 'Dv', key: 'dv', sortable: true,width: '2%', },
  { title: 'Nombre', key: 'name', sortable: true,width: '35%', },
  { title: 'Dirección', key: 'address', sortable: true,width: '45%', },
  { title: 'Teléfono', key: 'phone', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Ciudad', key: 'city', sortable: true },
  { title: '--Desde--', key: 'date_from', sortable: true,width: '10%' },
  { title: '--Hasta--', key: 'date_to', sortable: true,width: '20%'},
  { title: '#Días',key:'days_difference', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false },  
]

// --- 🔹 Modal y formulario de creación ---
const showDialog = ref(false)
const editMode = ref(false) // 👈 false = crear, true = editar

const newCompany = ref({
  nit: '',
  dv: '',
  representativeid: '',
  name: '',
  address: '',
  email: '',
  phone: '',
  city: '',
  endpoint1: '',
  endpoint2: '',
  token: '',
  date_from: ref(hoy),
  date_to: ref(hoy),
})

// 🔹 Snackbar (toast)
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const companyData = ref({
        data: [],
        total: 0,
        page: 1,
        per_page: 10,
        totaldctos: 0,
      })
    //

// 🔹 Diálogo de confirmación de eliminación
const nameCompanyToDelete = ref('')
const showConfirmDialog = ref(false)
const companyToDelete = ref<number | null>(null)

// 🔹 Reglas de validación
const rules = {
  required: (value: string) => !!value || 'Este campo es obligatorio',
  email: (value: string) =>
    !value || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Correo inválido',
  phone: (value: string) =>
    !value || value.length >= 7 || 'Debe tener al menos 7 dígitos',
}

// 🔹 Observa el estado del diálogo
watch(showDialog, (isOpen) => {
  if (isOpen && !editMode.value) {
    // Se abre el diálogo → limpiar los campos
    newCompany.value = {
      nit: '',
      dv:'',
      representativeid: '',
      name: '',
      address: '',
      email: '',
      phone: '',
      city: '',
      endpoint1: '',
      endpoint2: '',
      token: '',
      date_from: ref(hoy),
      date_to: ref(hoy),      
    }
  }
})

const loadCompanies = async () => 
{
     try {
        const response = await axios.get('/api/getcompanies', {
          params: {
                q: searchQuery.value,
                itemsPerPage: itemsPerPage.value,
                page: page.value,
                sortBy: sortBy.value,
                orderBy: orderBy.value,
              },
         }) 

         companyData.value = response.data   
      } catch (error) {
        console.error('Error al intentar enviar correo :', error)
      }      
}

// 🔹 Ejecutar al montar
onMounted(() => loadCompanies())

// 🔍 Escucha cambios en la barra de búsqueda
watch(searchQuery, () => {
  console.log('🔍 Búsqueda cambiada:', searchQuery.value)
  page.value = 1
  loadCompanies()
})


//🔹 Computed para acceder fácilmente a los datos
const companies = computed(() => companyData.value?.data ?? [])
const totalCompanies = computed(() => companyData.value?.total ?? 0)
const perPage = computed(() => companyData.value.per_page ?? itemsPerPage.value)
const currentPage = computed(() => companyData.value.page ?? page.value)


// 🔹 Guardar o actualizar empresa
const saveCompany = async () => {
  try {  
      if (editMode.value)  
      {
        // 🟡 Editar empresa existente
        try {    
              await axios.put(`/api/companies/${newCompany.value.id}`,newCompany.value)
                 
              snackbarMessage.value = 'Empresa actualizada correctamente' 
              showSnackbar.value = true
              showDialog.value = false
              //snackbarColor.value = 'error'
              showSnackbar.value = true             
              loadCompanies()
            } catch (error) {
                  console.error('❌ Error al guardar empresa:', error)
            }
      } else 
          {
              try {
                    await axios.post('/api/companies',newCompany.value)
                    
                    snackbarMessage.value = 'Empresa creada correctamente'
                    showSnackbar.value = true
                    showDialog.value = false
                    loadCompanies()
                    
                  } catch (error) {
                    console.error('Error al intentar Crear la Emmpresa :', error)
                  } 
          }
          showDialog.value = false
          loadCompanies()
  } catch (error) {
    console.error('❌ Error al guardar empresa:', error)
  }
}

// 🔹 Abrir modal en modo edición
const openEditDialog = (company) => {
  editMode.value = true
 newCompany.value = {
    id: company.id,
    nit: company.nit,
    dv:company.dv,
    representativeid: company.representativeid,
    name: company.name,
    address: company.address,
    email: company.email,
    phone: company.phone,
    city: company.city,
    endpoint1: company.endpoint1,
    endpoint2: company.endpoint2,
    token: company.token,
    date_from: company.date_from,
    date_to:company.date_to,
  } 
  // llenar formulario
  showDialog.value = true
}

// 🔹 Abrir modal en modo creación
const openCreateDialog = () => {
  editMode.value = false
  newCompany.value = {
    id: null,
    nit:'',
    dv:'',
    representativeid: '',
    name: '',
    address: '',
    email: '',
    phone: '',
    city: '',
    endpoint1: '',
    endpoint2: '',
    token: '',
    date_from: ref(hoy),
    date_to:ref(hoy),
  }
  //console.log('🆕 Abriendo modal para nueva empresa')
  showDialog.value = true
}

// 🔹 Abrir confirmación de eliminación
const confirmDelete = (id: number) => {
  console.log('🛑 Confirmar eliminación de empresa ID:', id)
  companyToDelete.value = id
  nameCompanyToDelete.value = companies.value.find(c => c.id === id)?.name || ''
  showConfirmDialog.value = true
}

// 🔹 Eliminar empresa
const deleteCompany = async () => {
  if (!companyToDelete.value) return

  try {
    await $api(`/api/companies/${companyToDelete.value}`, { method: 'DELETE' })
    loadCompanies()

    snackbarMessage.value = '✅ Empresa eliminada correctamente'
    snackbarColor.value = 'success'
  } catch (error) {
    console.error('❌ Error al eliminar empresa:', error)
    snackbarMessage.value = '❌ Error al eliminar empresa'
    snackbarColor.value = 'error'
  } finally {
    showConfirmDialog.value = false
    companyToDelete.value = null

    showSnackbar.value = false
    nextTick(() => (showSnackbar.value = true))
  }
}
</script>


<template>  
   <!-- <VCardText class="d-flex justify-space-between align-center flex-wrap gap-4 toolbar-header">  -->
   
   <VCardText class="d-flex justify-space-between align-center gap-4 toolbar-header"> 
      <!-- 🔍 Campo de búsqueda alineado a la izquierda -->
      
      <AppTextField
        v-model="searchQuery"
        placeholder="Buscar empresa..."
        class="search-field"
        hide-details
        density="compact"
        style="max-width: 250px"
        clearable
        prepend-inner-icon="tabler-search"
      />

      <!-- ➕ Botón "Nueva Empresa" alineado a la derecha -->
      <!-- <VBtn color="primary" class="text-white" @click="openCreateDialog"></VBtn> -->
      <VBtn color="primary" class="text-white" @click="showDialog = true">
        <template #prepend>
          <VIcon icon="tabler-plus" size="20" />
        </template>
        Nueva Empresa
      </VBtn>
  </VCardText>
  <!-- <section v-if="companies && companies.length"></section> -->
  <section>
    <VCard id="company-list">      

    <VDivider />

    <VDataTableServer
        v-model:model-value="selectedRows"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        border-cells="true" 
        show-select
        :stripedRows="true"
        :items-length="totalCompanies"
        :headers="headers"
        :items="companies"
        :search-field="searchQuery"        
        item-value="id"
        class="text-no-wrap text-body-2 company-table"
        @update:options="updateOptions"
      >
        <template #item.id="{ item }">
            <div class="cell-wrap columna_size">#{{ item.id }}</div>
        </template>

        <template #item.name="{ item }">
            <div class="cell-wrap columna_name">{{ item.name }}</div>
        </template>

        <template #item.email="{ item }">
          <a :href="`mailto:${item.email}`" class="columna_size">{{ item.email }}</a>
        </template>

        <template #item.address="{ item }">
            <div class="cell-wrap columna_size">{{ item.address }}</div>
        </template>

        <template #item.phone="{ item }">
            <div class="cell-wrap columna_size">{{ item.phone }}</div>
        </template>

        <template #item.city="{ item }">
            <div class="cell-wrap columna_size">{{ item.city }}</div>
        </template>

        <template #item.date_from="{ item }">
            <div class="cell-wrap columna_size">{{ item.date_from }}</div>
        </template>

         <template #item.date_to="{ item }">
            <div class="cell-wrap columna_size">{{ item.date_to }}</div>
        </template>

        <template #item.actions="{ item }">
          <IconBtn @click="openEditDialog(item)">
            <VIcon icon="tabler-edit" color="primary" />
          </IconBtn>

          <IconBtn @click="confirmDelete(item.id)">
            <VIcon icon="tabler-trash" color="error" />
          </IconBtn>         
        </template>

        <!-- Slot Bottom Personalizado -->
        <template #bottom>
            <VDivider />
            <VRow class="mt-2 mx-0 pb-2 align-center">     
                  <VCol cols="12" md="4">
                      <div class="text-caption text-medium-emphasis ps-4">
                           Mostrando
                           <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                           <strong>{{ Math.min(currentPage * perPage, totalCompanies) }}</strong>
                           de <strong>{{ totalCompanies }}</strong> registros
                      </div>
                  </VCol>
                  <VCol cols="12" md="4" class="d-flex justify-center pagination-wrapper"> 
                        <VPagination
                            v-model="page"
                            :length="Math.ceil(totalCompanies/ perPage)"
                            rounded="circle"
                            size="large"
                            :total-visible="5"
                          />
                  </VCol>             
              </VRow>           
        </template> 

      </VDataTableServer>
    </VCard>    

    <VSnackbar
        v-model="showSnackbar" :color="snackbarColor" location="center" timeout="3000"   multi-line elevation="2">
        <div class="d-flex align-center">
          <VIcon
            :icon="snackbarColor === 'success' ? 'tabler-check' : 'tabler-alert-triangle'" size="25" class="me-2"
          />
          <span class="text-lg">{{ snackbarMessage }}</span>
        </div>
    </VSnackbar>

    <!-- 🌟 Popup Modal para nueva empresa -->
    
  </section>

  <VDialog v-model="showDialog" persistent max-width="1100px">
      <VCard> 
       <!-- <VCardTitle class="text-h5 bg-primary text-white py-4 px-4">Agregar nueva empresa</VCardTitle> -->
       <VCardTitle class="modal-title d-flex align-center text-h5">
          <VIcon icon="tabler-building" size="28" color="white" class="me-3"/>
          <span>Agregar una empresa</span>          
       </VCardTitle>
       
       <VCardText mb="4" class="pt-4 pb-2">
          <VForm @submit.prevent="saveCompany">
            <VRow dense align="center" class="g-2">
                <VCol cols="12" md="3" class="py-0">
                     <AppTextField v-model="newCompany.nit" label="Nit de la Empresa" autofocus required class="mb-3 text_size mt-0" :rules="[rules.required]"  placeholder="Ingrese Nit de la Empresa" 
                          @update:model-value="val => newCompany.nit = val.toUpperCase()">
                        <template #prepend-inner>
                          <VIcon icon="tabler-id" color="primary" size="22" class="me-2" />
                        </template> 
                     </AppTextField> 
                </VCol>
                
                <VCol cols="12" md="1" class="py-0">
                     <AppTextField v-model="newCompany.dv" label="Dv" required class="mb-3 text_size mt-0" :rules="[rules.required]"  placeholder="Ingrese Dígito de Verificación" 
                          @update:model-value="val => newCompany.dv = val.toUpperCase()">
                        <template #prepend-inner>
                          <VIcon icon="tabler-id-badge" color="primary" size="22" class="me-2" />
                        </template> 
                     </AppTextField> 
                </VCol>
                <VCol cols="12" md="2" class="py-0">
                     <AppTextField v-model="newCompany.representativeid" label="Cédula Representante" required class="mb-3 text_size mt-0" :rules="[rules.required]"  placeholder="Ingrese Cédula del Representante Legal" 
                         >
                        <template #prepend-inner>
                          <VIcon icon="tabler-id" color="primary" size="22" class="me-2" />
                        </template> 
                     </AppTextField> 
                </VCol>
                <VCol cols="12" md="3" class="py-0  align-center">                           
                     <AppDateTimePicker                        
                        v-model="newCompany.date_from"  label="Vigencia Desde :" placeholder="Seleccionar Fecha Desde" class="text-center-input column_date_size mt-0"  variant="outlined"
                        prepend-inner-icon="tabler-calendar" :config="{ locale: Spanish, dateFormat: 'Y-m-d'}" 
                      />                          
                </VCol>
                <VCol cols="12" md="3" class="py-0 align-center">
                     <AppDateTimePicker
                        v-model="newCompany.date_to"  label="Vigencia Hasta :" placeholder="Seleccionar Fecha Hasta" class="text-center-input column_date_size mt-0"  variant="outlined"
                        prepend-inner-icon="tabler-calendar" :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
                      />          
                </VCol>
            </VRow>  

            <VRow dense align="center" class="g-2">
              <VCol cols="12" md="6" class="py-0">       
                  <AppTextField v-model="newCompany.name" label="Nombre de la Empresa" required class="mb-3 text_size" :rules="[rules.required]"  placeholder="Ingrese el nombre de la empresa" 
                      @update:model-value="val => newCompany.name = val.toUpperCase()" >
                    <template #prepend-inner>
                      <VIcon icon="tabler-users" color="primary" size="22" class="me-2" />
                    </template> 
                  </AppTextField> 
              </VCol>
              <VCol cols="12" md="6" class="py-0">
                  <AppTextField v-model="newCompany.address" label="Dirección de la Empresa" class="mb-3 text_size" required :rules="[rules.required]"
                    placeholder="Ingrese la dirección"  @update:model-value="val => newCompany.address = val.toUpperCase()">
                    <template #prepend-inner>
                      <VIcon icon="tabler-map-pin" color="primary" size="22" class="me-2" />
                    </template>
                  </AppTextField>  
              </VCol>
            </VRow>   
            <VRow dense align="center" class="g-2">
              <VCol cols="12" md="6" class="py-0">       
                <AppTextField v-model="newCompany.email" label="Correo electrónico" type="email" class="mb-3 text_size" :rules="[rules.required, rules.email]"
                      placeholder="Ingrese el sorreo electrónico"  @update:model-value="val => newCompany.email = val.toLowerCase()" >              
                    <template #prepend-inner>
                        <VIcon icon="tabler-mail" color="primary" size="22" class="me-2" />
                    </template>
                  </AppTextField>
              </VCol>
              <VCol cols="12" md="6" class="py-0">   
                <AppTextField v-model="newCompany.phone" label="Teléfono de la Empresa" class="mb-3 text_size" required :rules="[rules.phone,rules.required]">
                  <template #prepend-inner>
                    <VIcon icon="tabler-phone" color="primary" size="22" class="me-2" />
                  </template>
                </AppTextField>
              </VCol>
            </VRow>
            <VRow dense align="center" class="g-2">
              <VCol cols="12" md="6" class="py-0"> 
                <AppTextField v-model="newCompany.city" label="Nombre de la Ciudad" class="mb-3 text_size" required :rules="[rules.required]"
                  placeholder="Ingrese la ciudad"  @update:model-value="val => newCompany.city = val.toUpperCase()">
                  <template #prepend-inner>
                    <VIcon icon="tabler-building-bank" color="primary" size="22" class="me-2" />
                  </template>
                </AppTextField>
              </VCol>
              <VCol cols="12" md="6" class="py-0"> 
                  <AppTextField v-model="newCompany.endpoint1" label="Endpoint # 1" class="mb-3 text_size" required :rules="[rules.required]"
                    placeholder="Ingrese el endpoint # 1" @update:model-value="val => newCompany.endpoint1 = val.toLowerCase()">
                    <template #prepend-inner>
                      <VIcon icon="tabler-link" color="primary" size="22" class="me-2" />
                    </template>
                  </AppTextField>
              </VCol>
            </VRow>
            <VRow dense align="center" class="g-2">
              <VCol cols="12" md="6" class="py-0"> 
                  <AppTextField v-model="newCompany.endpoint2" label="Endpoint # 2" class="mb-3 text_size" required :rules="[rules.required]" 
                    placeholder="Ingrese el endpoint # 2" @update:model-value="val => newCompany.endpoint2 = val.toLowerCase()">
                    <template #prepend-inner>
                      <VIcon icon="tabler-link" color="primary" size="22" class="me-2" />
                    </template>
                  </AppTextField>
               </VCol>
               <VCol cols="12" md="6" class="py-0"> 
                    <AppTextarea   v-model="newCompany.token" label="Token de Autenticación" placeholder="Ingrese el Token de Autenticación" class="mb-3 text-area-lg" density="comfortable" >
                      <template #prepend-inner>
                         <VIcon icon="tabler-qrcode" color="primary" size="22" class="me-2" />
                      </template>
                    </AppTextarea>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VDivider color="dark" thickness="2" />    
 
        <VCardActions class="justify-end mt-2">
          <VBtn color="success" variant="flat" class = "text-white" @click="showDialog = false">Cancelar</VBtn>
          <VBtn color="primary" variant="flat" class = "text-white" @click="saveCompany">Guardar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

      <!-- ❗ Diálogo de confirmación de eliminación -->
    <VDialog v-model="showConfirmDialog" max-width="400px">
      <VCard>
        <VCardTitle class="text-h6 text-center pt-4">
          <VIcon icon="tabler-alert-circle" color="warning" size="26" class="me-2" />
          Confirmar eliminación <br />
          {{nameCompanyToDelete }}
        </VCardTitle>
        <VCardText class="text-center">
          ¿Está seguro que desea eliminar esta empresa?<br />
          <strong>Esta acción no se puede deshacer.</strong>
        </VCardText>
        <VCardActions class="justify-center pb-4">
          <VBtn color="secondary" variant="tonal" @click="showConfirmDialog = false">Cancelar</VBtn>
          <VBtn color="error" variant="flat" @click="deleteCompany">Eliminar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
</template>

<style lang="scss">
#company-list {
  .company-list-filter {
    inline-size: 12rem;
  }
}
/* Paginación circular */


 /* 1. Estilos para el componente VPagination (el que tienes en el slot #bottom) */
.pagination-wrapper {
  .v-pagination__first,
  .v-pagination__item,
  .v-pagination__next,
  .v-pagination__prev,
  .v-pagination__last{
    .v-btn {
      background-color:  rgb(238, 33, 43) !important;
     
      /* Cambia el color de los iconos de flecha y números */
      //color: #0EE920 !important; 
      
      .v-icon {
        color: rgb(250, 253, 245) !important;
      }
    }
  }
}



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

.columna_name {
  // max-width: 600px;         /* ancho fijo */
  white-space: normal !important; /* permite salto de línea */
  word-wrap: break-word;    /* divide palabras largas */
  line-height: 1.3;         /* mejora legibilidad */
  overflow-wrap: break-word;
  display: block;
  font-size: .85em;
}
/* Evita que el resto de columnas se vean afectadas */
// .company-table :deep(td),
// .company-table :deep(th) {
//   white-space: nowrap;
// }

.v-data-table__thead th {
  background-color: rgb(138, 238, 91);
  color: white;
}

/* 🌟 Bordes verticales para VDataTableServer */
.company-table :deep(.v-data-table__td),
.company-table :deep(.v-data-table__th),
.company-table :deep(.v-data-table__trselect) {
  border-right: 1px solid rgba(0, 0, 0, 0.12) !important; /* color de línea */
}

/* Quita el borde derecho en la última columna */
.company-table :deep(.v-data-table__td:last-child),
.company-table :deep(.v-data-table__th:last-child) {
  border-right: none;
}

/* Opcional: bordes suaves inferiores */
.company-table :deep(.v-data-table__td) {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
}

.company-table :deep(.v-data-table__wrapper) {
  overflow: visible !important;
}

.company-table :deep(.v-data-table__td),
.company-table :deep(.v-data-table__th) {
  border-right: 1px solid rgba(var(--v-theme-on-surface), 0.15) !important;
}

/* Botón mejor alineado */
.toolbar-header .v-btn {
  height: 40px;
  font-weight: 500;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
}

.v-overlay {
  z-index: 9999 !important;
  position: fixed !important;
}

.textarea {
  font-size: 12px !important;
  line-height: 1.4;
}

textarea {      
      font-size: .85em !important; 
      height: 80px !important; 
 }

 .v-field__input{
    font-size: .84em !important; 
 }

  .columna_size {  
  white-space: normal !important; /* permite salto de línea */
  word-wrap: break-word;    /* divide palabras largas */
  line-height: 1.3;         /* mejora legibilidad */
  overflow-wrap: break-word;
  display: block;
  font-size: .9em;
}

 .column_date_size {  
  // width: 20em !important;
  white-space: normal !important; /* permite salto de línea */  
  line-height: 1.3;         /* mejora legibilidad */   
  font-size: .9em;
  // min-height: 56px!important;  
  margin-top: 0 !important;
  padding-top: 0 !important;
 }

.text-center-input input {
    text-align: center !important;
    cursor: pointer; 
 }

 /* Forzar que el calendario de Flatpickr esté sobre el VDialog */
.flatpickr-calendar {
  z-index: 10000 !important;
}

.v-data-table__thead th 
  {
      background-color: rgb(247, 58, 206) !important;
      color: white !important;
  }

  .v-data-table thead th 
  {
     text-transform: capitalize !important;
  }

  .row-uniform-margin > .v-col > * 
  { 
    margin-top: 12px !important; /* o el valor que quieras */ 
  }

//   .v-col {
//   display: flex;
//   align-items: center;
// }


</style>
