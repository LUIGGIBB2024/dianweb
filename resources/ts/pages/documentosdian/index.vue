<script setup lang="ts">
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import fileDownload from 'js-file-download'
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
    
    const invoiceData = ref({
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
      { title: '# Id', key: 'id', width: '5%' },
      { title: 'Fecha Documento', key: 'date_issue', sortable: true },
      { title: 'Número de Factura', key: 'number', sortable: true, width: '6px'},
      { title: 'Prefijo', key: 'prefix', sortable: true,  },
      { title: 'Tipo Documento', key: 'document_name', sortable: true,},
      { title: 'Nit/Cédula', key: 'customer', sortable: true },
      { title: 'Nombre del Cliente/Proveedor', key: 'client_name', sortable: true, width: '35%'},
      { title: 'Valor Documento', key: 'sale', sortable: true },
      { title: 'Acciones', key: 'actions', sortable: false, width: '20px'  },
    ]
    
    const token = localStorage.getItem('auth_token')

    const updateOptions = async (options: any) => {
        page.value = options.page
        itemsPerPage.value = options.itemsPerPage
        sortBy.value = options.sortBy[0]?.key
        orderBy.value = options.sortBy[0]?.order      
        await generarConsulta()  
    }

    const descargarXml = async (item: any) => {
      try {
        const response = await axios.post('/api/downdocument/xml', {
          numberdocument: item.number,
          prefix: item.prefix,
        }, {
          responseType: 'blob', // 👈 recibe el archivo como binario
        })

        // Descargar con nombre dinámico
        fileDownload(response.data, `factura_${item.prefix}_${item.number}.xml`)
      } catch (error) {
        console.error('Error al descargar XML:', error)
      }
    }

    const descargarPdf = async (item: any) => 
    {
        try 
        {
          const response = await axios.post('/api/downdocument/pdf', {
              numberdocument: item.number,
              prefix: item.prefix,
            }, {
              responseType: 'blob',
            })

          fileDownload(response.data, `factura_${item.prefix}_${item.number}.pdf`)          
        } catch (error) {
            console.error('Error al descargar PDF:', error)
        }
    }

    const generarConsulta = async () =>
    {
          
          loading.value = true
          try {
            const { data } = await axios.post('/api/list/documents', {
              ...datafechas.value,
              page: page.value,
              per_page: itemsPerPage.value,
          },
          {
             headers: { Authorization: `Bearer ${token}` },
          },
          )
            invoiceData.value = data
          } catch (error) {
            console.error(error)
          } finally {
            loading.value = false
          }
    }

    onMounted(() => generarConsulta())

    const facturas = computed(() => invoiceData.value.data ?? [])
    const currentPage = computed(() => invoiceData.value.page ?? page.value)
    const perPage = computed(() => invoiceData.value.per_page ?? itemsPerPage.value)
    const totalInvoices = computed(() => invoiceData.value.total ?? 0)
    const totaldctos = computed(() => invoiceData.value.totaldctos ?? 0)

    // 🔹 Guardar o actualizar usuario
  const sendEmail = async () => 
  {
      loading.value = true
      const email = capturarEmail.value.email
      const invoice = selectedInvoice.value // si también es ref
     
      try {
          const response = await axios.post('/api/sendpackage', {
            number: invoice.number,
            prefix: invoice.prefix,
            showacceptrejectbuttons:false,
            email_cc_list: [
              { email: email},             
            ],          
            base64graphicrepresentation:"",               
          },
        {
          headers: {
            Authorization: `Bearer ${token}`, // 👈 Aquí agregas el token
            'Content-Type': 'application/json', // opcional pero recomendable
          },
        }) 

        loading.value = false
      
        } catch (error) {
          console.error('Error al intentar enviar correo :', error)
        }
  }

  const abrirDialogoEmail = (item: any) => 
  {
    selectedInvoice.value  = item
    showDialogEmail.value  = true
  }

</script>

 <template>
      <!-- <VCard class="mb-2" style="height: 13vh !important;"">  -->
      <VCard class="mb-2 py-3 px-4">
          <VRow class="align-center">
            <VCol cols="12" md="3" class="d-flex align-center flex-column">
              <h3 class="text-primary mb-0">Consultar Documentos Electrónicos</h3>
              <h4 class="text-warning mb-0">(Facturas/Notas/Documento Soporte)</h4>
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


      <section v-if="facturas && facturas.length">
            <VCard>
              <VDataTableServer
                v-model:model-value="selectedRows"
                v-model:items-per-page="itemsPerPage"
                v-model:page="page"               
                :headers="headers"
                :items="facturas"
                :items-length="totalInvoices"
                item-value="id"
                show-select
                :search-field="searchQuery"   
                class="text-no-wrap text-body-2 company-table capitalize"
                @update:options="updateOptions"
              >
                <!-- Slots de Cabecera -->
                <template #header.date_issue>
                  <div style="text-align:center; white-space:normal;">
                    Fecha<br>Documento
                  </div>
                </template>

                <template #header.number>
                  <div style="text-align:center; white-space:normal;">
                    Número<br>de Factura
                  </div>
                </template>  

                <template #item.document_name="{ item }">
                  <div style="width: 130px; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.document_name }}
                  </div>
                </template>

                <template #item.client_name="{ item }">
                  <div style="width:340px; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.client_name}}
                  </div>
                </template>


                <template #header.sale>
                  <div style="text-align:center; white-space:normal;">
                    Total<br>Documento
                  </div>
                </template> 

                <template #header.actions>
                  <div style="text-align:center; white-space:normal;">
                    Acciones
                  </div>
                </template>
                
                <!-- Slots de Items -->
                <template #item.sale="{ item }">
                    <div style="text-align:right;">
                         {{ formatCurrency(item.sale) }} 
                    </div>
                </template>

                <template #item.actions="{ item }">
                    <IconBtn>
                      <VIcon icon="tabler-file-type-xml" color="primary" @click="descargarXml(item)" />
                    </IconBtn>
                    <IconBtn>
                      <VIcon icon="tabler-file-type-pdf" color="error" @click="descargarPdf(item)" />
                    </IconBtn>   
                    <IconBtn>
                      <VIcon icon="tabler-mail" color="warning" @click="abrirDialogoEmail(item)" />
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
                              <strong>{{ Math.min(currentPage * perPage, totalInvoices) }}</strong>
                              de <strong>{{ totalInvoices }}</strong> registros
                           </div>
                      </VCol>
                      <VCol cols="12" md="4" class="d-flex justify-center pagination-wrapper"> 
                           <VPagination
                                v-model="page"
                                :length="Math.ceil(totalInvoices / perPage)"
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

              <VOverlay :model-value="loading" persistent class="align-center justify-center">
                  <VProgressCircular indeterminate size="64" color="primary" />
              </VOverlay>
            </VCard>

            <!-- 🔹 Enviar Correos  -->
            <VDialog v-model="showDialogEmail" persistent max-width="500px">
                <VCard>
                    <VCardTitle class="modal-title d-flex align-center">
                      <VIcon icon="tabler-send" size="26" color="white" class="me-3" />
                      <span class="text-white text-h5">{{ 'Enviar Documentos (Correo Electrónico)'}}</span>
                    </VCardTitle>

                    <VCardTitle class="d-flex align-center">
                       <VRow>
                          <VCol>
                            <span class="text-error text-body-2">Documento: <strong>{{ selectedInvoice?.prefix }}{{ selectedInvoice?.number }}</strong></span><br>
                            <span class="text-info text-body-2"><strong>{{ selectedInvoice?.client_name}}</strong></span>
                          </VCol>                     
                                         
                       </VRow>                        
                    </VCardTitle>
                    
                    <VCardText class="pt-4">
                      <VForm @submit.prevent="sendEmail" ref="userFormRef" v-model="isFormValid">
                        <VTextField v-model="capturarEmail.email" label="Correo Electrónico:" :type="isPasswordVisible ? 'text' : 'email'" required :rules="[rules.email]" autofocus class="mb-3" 
                            @update:model-value="val => capturarEmail.email = val.toLowerCase()" placeholder="Ingresar Correo Electrónico">
                          <template #prepend-inner>
                              <VIcon icon="tabler-mail" color="primary" size="22" class="me-3" />
                          </template>
                        </VTextField>                   
                      </VForm>
                    </VCardText>
                    <VCardActions class="justify-end pb-4 px-6">         
                      <VBtn color="success" variant="flat" class = "text-white" @click="showDialogEmail = false">Cancelar</VBtn>
                      <VBtn color="primary" variant="flat" class = "text-white" @click="sendEmail">Enviar</VBtn>
                    </VCardActions>
                </VCard>
            </VDialog>
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

  .custom-card 
  {
    height: 250px !important; /* Ajusta a tu necesidad */
  }
  
 
</style>
