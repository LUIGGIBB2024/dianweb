<script setup lang="ts">
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import { computed, onMounted, ref } from 'vue'
import { VRow } from 'vuetify/components'

    const formatCurrency = (value: number | string) => 
    {
        const num = Number(value) || 0
        return num.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    }
        
    const hoy = new Date().toISOString().split('T')[0]
    const loading = ref(false)
    const isFormValid = ref(false)

    const datafechas = ref({
      desdefecha: hoy,
      hastafecha: hoy,
    })
    
    const capturarEmail = ref({
      email: '', 
    })
    // 🔹 Reglas
    const rules = {
      required: (v: string) => !!v || 'Este campo es obligatorio',
      email: (v: string) => !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Correo inválido',
      password: (v: string) => v.length >= 6 || 'La contraseña debe tener al menos 6 caracteres',
    }    
    
    const datadocument = ref({
      numberdocument: '',
      prefix: '',
      email: '',
    })

    const correoelectronico = ref('')

    const selectedInvoice = ref<any>(null) // 👈 aquí guardaremos el item de la fila

    const showDialog = ref(false)
    const isPasswordVisible = ref(false)

    const searchQuery = ref('')
    const selectedRows = ref([])
    
    const responseData = ref({
        data: [],
        total: 0,
        page: 1,
        per_page: 10,
        totaldctos: 0,
      })
    //
    const showDialogEmail = ref(false)
    const editMode = ref(false)
 
    // 🔹 Variables del DataTable
    const itemsPerPage = ref(10)
    const page = ref(1)
    const sortBy = ref()
    const orderBy = ref()
    const headers = [
      // { title: '# Id', key: 'id', width: '5%' },
      { title: 'Fecha Pago', key: 'date_issue', sortable: true, class: 'col-fecha' },
      { title: 'Desde', key: 'settlement_start_date', sortable: true ,class: 'col-fechad'},
      { title: 'Hasta', key: 'settlement_end_date', sortable: true,class: 'col-fechad'},
      { title: 'Tipo Documento', key: 'type_document_name', sortable: true },
      { title: 'Consecutivo', key: 'consecutive', sortable: true },
      { title: 'Cédula', key: 'identification_number', sortable: true },
      { title: 'Nombre del Empleado', key: 'employee_name', sortable: true },
      { title: 'Devengado', key: 'accrued_total', sortable: true },
      { title: 'Deducciones', key: 'deductions_total', sortable: true },
      { title: 'Total Pagado', key: 'total_payroll', sortable: true },
    ]
        
    const token = localStorage.getItem('auth_token')

    const updateOptions = async (options: any) => {
        page.value = options.page
        itemsPerPage.value = options.itemsPerPage
        sortBy.value = options.sortBy[0]?.key
        orderBy.value = options.sortBy[0]?.order      
        await generarConsulta()  
    }   
  
    const generarConsulta = async () =>
    {
          
        loading.value = true
        try 
        {         
          const qdata  = await axios.post('/api/list/payroll', {
              ...datafechas.value,
              page: page.value,
              per_page: itemsPerPage.value,
          },
          {
             headers: { Authorization: `Bearer ${token}` },
          },)          
           
          responseData.value = {
              data: qdata.data.data ?? [], // 👈 Aquí está el arreglo correcto
              total: qdata.data.total ?? 0,
              page: qdata.data.page ?? 1,
              per_page: qdata.data.per_page ?? 10,
              totaldctos: qdata.data.totaldctos ?? 0,
          }

          console.log('Verificando:', responseData.value.data.length)
          console.log('Data:', responseData.value.data)
          console.log('Total:', responseData.value.total)
                        // responseData.value = data
        } catch (error) {
          console.error(error)
        } finally {
          loading.value = false
        }
    }

    onMounted(() => generarConsulta())

    //console.log("Soy Data Data ", responseData.value.data)
    const infodata = computed(() => responseData.value.data ?? [])
    const currentPage = computed(() => responseData.value.page ?? page.value)
    const perPage = computed(() => responseData.value.per_page ?? itemsPerPage.value)
    const totalData = computed(() => responseData.value.total ?? 0)
    const totaldctos = computed(() => responseData.value.totaldctos ?? 0)

    console.log("Soy infoData:", infodata)

  const abrirDialogoEmail = (item: any) => 
  {
    selectedInvoice.value  = item
    showDialogEmail.value  = true
  }

</script>

 <template>
      <VCard class="mb-2 py-3 px-4">
          <VRow class="align-center">
            <VCol cols="12" md="3" class="d-flex align-center mt-0">
              <h3 class="text-primary mb-0">Consultar Nómina Electrónica </h3>            
            </VCol>

            <VCol cols="12" md="2">
              <AppDateTimePicker
                v-model="datafechas.desdefecha"
                label="Desde Fecha :"
                placeholder="Seleccionar Fecha"
                class="text-center-input"
                variant="outlined"
                prepend-inner-icon="tabler-calendar"
                :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
              />
            </VCol>

            <VCol cols="12" md="2">
              <AppDateTimePicker
                v-model="datafechas.hastafecha"
                label="Hasta Fecha :"
                placeholder="Seleccionar Fecha"
                class="text-center-input"
                prepend-inner-icon="tabler-calendar"
                :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
              />
            </VCol>

            <VCol cols="12" md="2" class="d-flex align-center justify-start mt-md-5 mt-2">
              <VBtn
                rounded="pill"
                color="primary"
                variant="flat"
                @click="generarConsulta"
              >
                Generar Consulta
              </VBtn>
            </VCol>
          </VRow>
      </VCard>


      <section v-if="infodata && infodata.length">
            <VCard>
              <VDataTableServer
                v-model:model-value="selectedRows"
                v-model:items-per-page="itemsPerPage"
                v-model:page="page"               
                :headers="headers"
                :items="infodata"
                :items-length="totalData"
                item-value="id"
                show-select         
                class="text-no-wrap text-body-2 company-table capitalize"
                @update:options="updateOptions"
              >

              <template #item.id="{ item }">
                  <div class="cell-wrap columna_size"> {{ item.id }}</div>
              </template>
              <template #item.date_issue="{ item }">
                  <div class="cell-wrap columna_size"> {{ item.date_issue }}</div>
              </template>             
              <template #item.settlement_start_date="{ item }">
                  <div class="cell-wrap columna_size"> {{ item.settlement_start_date }}</div>
              </template>    
              <template #item.settlement_end_date="{ item }">
                  <div class="cell-wrap columna_size"> {{ item.settlement_end_date }}</div>
              </template>     
              <template #item.type_document_name="{ item }">
                  <div class="cell-wrap columna_size"> {{ item.type_document_name }}</div>
              </template>
              <template #item.consecutive="{ item }">
                  <div class="cell-wrap columna_size"> {{ item.consecutive }}</div>
              </template>
              <template #item.employee_name="{ item }">
                  <div class="cell-wrap columna_size"> {{ item.employee_name }}</div>
              </template>
              <template #item.identification_number="{ item }">
                  <div class="cell-wrap columna_size"> {{ item.identification_number }}</div>
              </template>
              <template #item.accrued_total="{ item }">
                  <div class="text-right cell-wrap columna_size"> {{ formatCurrency(item.accrued_total) }} </div>          
              </template>
              <template #item.deductions_total="{ item }">
                  <div class="text-right cell-wrap columna_size"> {{ formatCurrency(item.deductions_total) }}</div>
              </template>
              <template #item.total_payroll="{ item }">
                  <div class="text-right cell-wrap columna_size"> {{ formatCurrency(item.total_payroll) }}</div>
              </template>
     
              <template #bottom>
                  <VDivider />
                  <VRow class="mt-2 mx-0 pb-2 align-center">     
                      <VCol cols="12" md="4">
                          <div class="text-caption text-medium-emphasis ps-4">
                              Mostrando
                              <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                              <strong>{{ Math.min(currentPage * perPage, totaldctos) }}</strong>
                              de <strong>{{ totalData }}</strong> registros
                           </div>
                      </VCol>
                      <VCol cols="12" md="4" class="d-flex justify-center pagination-wrapper"> 
                           <VPagination
                                v-model="page"
                                :length="Math.ceil(totalData / perPage)"
                                rounded="circle"
                                size="large"
                                :total-visible="5"
                           />
                       </VCol>
                       <VCol cols="12" md="4">
                          <div class="text-caption text-medium-emphasis ps-4 text-end">
                              Total Documentos $:
                              <strong class="text-primary">{{ formatCurrency(totaldctos)}}</strong>
                              <!-- <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                              <strong>{{ Math.min(currentPage * perPage, totalInvoices) }}</strong>
                              de <strong>{{ totalInvoices }}</strong> registros -->
                           </div>
                       </VCol>
                  </VRow>           
                </template>                
                               
            </VDataTableServer>
            <!-- <pre>{{ infodata }}</pre> -->

            <VOverlay :model-value="loading" persistent class="align-center justify-center">
                <VProgressCircular indeterminate size="64" color="primary" />
            </VOverlay>
          </VCard>
      </section>

      <section v-else>
        <VCard>
          <VCardTitle class="pa-4">No se encontraron registros para el periodo seleccionado</VCardTitle>
        </VCard>
      </section>
 </template>  

 <style lang="css">

  .pagination-wrapper {
  .v-pagination__first,
  .v-pagination__item,
  .v-pagination__next,
  .v-pagination__prev,
  .v-pagination__last{
    .v-btn {
      background-color:  rgb(253, 134, 227) !important;     
      .v-icon {
        color: rgb(250, 253, 245) !important;
      }
    }
  }
}

  .pagination-wrapper :deep(.v-pagination__list) {
    justify-content: center;
  }

    /* Si no usas scoped, puedes hacerlo así: */
  .text-center-input input {
    text-align: center !important;
    cursor: pointer; 
  }


  /* .v-data-table :deep(.v-data-table__thead th) {
    background-color: rgb(243, 16, 175) !important;
    color: white !important;
    text-transform: none !important;
  } */

  .v-data-table__thead th 
  {
      background-color: rgb(247, 58, 206) !important;
      color: white !important;
  }

  .v-data-table thead th 
  {
     text-transform: capitalize !important;
  }

  .modal-title 
  {
    background-color: rgb(var(--v-theme-primary));
    color: white;
    padding: 16px 24px;
    font-weight: 600;
    font-size: 1.2rem;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
  }

  .columna_size {  
  white-space: normal !important; /* permite salto de línea */
  word-wrap: break-word;    /* divide palabras largas */
  line-height: 1.3;         /* mejora legibilidad */
  overflow-wrap: break-word;
  display: block;
  font-size: .9em;
}

.col-id { width: 60px !important; text-align: center !important; }
.col-fecha { width: 150px !important; text-align: center !important; }
.col-fechad { width: 200px !important; text-align: center !important; }
.col-tipo { width: 160px !important; text-align: left !important; }
.col-nombre { min-width: 220px !important; text-align: left !important; }
.col-money { text-align: right !important; min-width: 120px !important; }

.text-right {
  text-align: right !important;
}

</style>
