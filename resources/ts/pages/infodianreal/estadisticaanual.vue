<script setup lang="ts">
import axios from 'axios'
import fileDownload from 'js-file-download'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { computed, ref } from 'vue'
import { VRow } from 'vuetify/components'
import * as XLSX from 'xlsx'

    const rawValue = ref(true) // true = Ventas, false = Compras

    const switchProxy = computed({
      get: () => true, // SIEMPRE devolvemos true para que Vuetify crea que está "encendido"
      set: (val) => {
        // Cuando el usuario haga clic, simplemente invertimos nuestra variable real
        isActive.value = !isActive.value
        handleToggle(isActive.value)
      }
    })

    const isActive = ref<boolean>(false) // Cambiado a boolean para evitar líos con null

    const handleToggle = (value: any) => {
      // Vuetify a veces devuelve el evento, aseguramos el booleano
      const boolValue = !!value 
      isActive.value = boolValue
      
      if (boolValue) {
        console.log('Acción derecha (Ventas)')
      } else {
        console.log('Acción izquierda (Compras)')
      }
    }

    const snackbar = ref(false)
    const yaBusco = ref(false) // Nueva variable de control

    const formatCurrency = (value: number | string, decimals: number = 0): string => {
          console.log('value:', value, '| decimals:', decimals) // 👈
          
          const str = String(value).trim()
          const isEuropean = /\d{1,3}(\.\d{3})+(,\d+)?$/.test(str)
          
          let normalized: string
          if (isEuropean) {
            normalized = str.replace(/\./g, '').replace(',', '.')
          } else {
            normalized = str.replace(/,/g, '')
          }

          const cleaned = normalized.replace(/[^\d.-]/g, '')
          const num = parseFloat(cleaned) || 0
          
          console.log('num:', num, '| result:', num.toLocaleString('en-US', { // 👈
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
          }))

          return num.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
          })
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
    
    const recordData = ref({
        status: '',
        message:  '',   
        nombre_mes:'',   
        NumeroMeses:0,
        TotalDocumentos:0,
        AcumuladoTotal:0,
        AcumuladoIva:0,
        data:[], 
        page: 1,
        per_page: 12,       
      })
    //
    const showDialogEmail = ref(false)
    const editMode = ref(false)
 
    // 🔹 Variables del DataTable
    const itemsPerPage = ref(12)
    const page = ref(1)
    const sortBy = ref()
    const orderBy = ref()

    
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
          datafechas.value.desdefecha = `${anioSeleccionado.value}-01-01`
          datafechas.value.hastafecha = `${anioSeleccionado.value}-12-31`

          console.log("Generando Consulta con Toggle:", toggle.value)
          console.log("Id Company:", localStorage.getItem('company_id'),'fechas:', datafechas.value.desdefecha, datafechas.value.hastafecha)
          loading.value = true
          try {
                //onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
                const { data } = await axios.post('/api/dian/estadistica-anual', {   
                  toggle       : toggle.value, // Enviar el estado del toggle               
                  url_token    : localStorage.getItem('auth_token'),
                  company_id   : localStorage.getItem('company_id'),
                  fechadesde   : datafechas.value.desdefecha,
                  fechahasta   : datafechas.value.hastafecha,             
                  page: page.value,
                  per_page: itemsPerPage.value,
             }, 
             {
                headers: { Authorization: `Bearer ${token}` }
             }
          )
            recordData.value = data;
            console.log("Respuesta API:", data)
            console.log("Respuesta InvoiceData:", recordData.value)
            yaBusco.value = true // Marcar que ya se realizó una búsqueda
            if (recordData.value.TotalDocumentos === 0 && yaBusco.value) {               
               snackbar.value = true
            }
          } catch (error) {
            console.error(error)
          } finally {
            loading.value = false
          }
    }

    //onMounted(() => generarConsulta())

    const records         = computed(() => recordData.value?.data ?? [])
    const currentPage     = computed(() => recordData.value.page ?? page.value)
    const perPage         = computed(() => recordData.value.per_page ?? itemsPerPage.value)
    const totalInvoices   = computed(() => recordData.value.AcumuladoTotal ?? 0)
     const totalIva        = computed(() => recordData.value.AcumuladoIva ?? 0)
    const totalregistros  = computed(() => recordData.value.NumeroMeses ?? 0) 
    

    // ── Exportar a Excel ──────────────────────────────────────────
    const exportarExcel = () => {
      const datos = records.value.map(item => ({       
        'Nombre Mes':     item.nombre_mes,
        'NroDocimentos':  item.total_documentos,
        'Subtotal':       item.total_subtotal,
        'Valor Iva':      item.total_iva,
        'Total':          item.gran_total,
      }))

      const hoja  = XLSX.utils.json_to_sheet(datos)
      const libro = XLSX.utils.book_new()
      XLSX.utils.book_append_sheet(libro, hoja, 'Facturas')
      XLSX.writeFile(libro, `datos_${toggle.value}_${datafechas.value.desdefecha}_${datafechas.value.hastafecha}.xlsx`)
    }

    // ── Exportar a PDF ────────────────────────────────────────────
    const exportarPDF = () => {
      const doc = new jsPDF({ orientation: 'landscape' })

      doc.setFontSize(14)
      doc.text(`Estadística Anual (${toggle.value})`, 14, 15)
      doc.setFontSize(9)
      doc.text(`Período: ${datafechas.value.desdefecha}  al  ${datafechas.value.hastafecha}`, 14, 22)

      autoTable(doc, {
        startY: 28,
        head: [[
          'Meses','NroDocumentos', 'SubTotal', 'Total Iva', 'Gran Total'
        ]],
        body: records.value.map(item => [
          item.nombre_mes,
          item.total_documentos,  
          formatCurrency(item.total_documentos),
          formatCurrency(item.total_subtotal),
          formatCurrency(item.total_iva),
          formatCurrency(item.gran_total),          
        ]),
        styles:     { fontSize: 7, cellPadding: 2 },
        headStyles: { fillColor: [25, 118, 210], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [240, 248, 255] },
        foot: [[
          '', '', '', '', '', '', 'TOTALES',
          formatCurrency(records.value.to - totalIva.value),   // opcional si lo tienes calculado
          formatCurrency(totalIva.value),
          formatCurrency(totalInvoices.value),
        ]],
        footStyles: { fillColor: [200, 230, 255], fontStyle: 'bold' },
      })

      doc.save(`datos_${toggle.value}_${datafechas.value.desdefecha}_${datafechas.value.hastafecha}.pdf`)
    }



  //  const headers = [
  //     { title: 'Meses', key: 'nombre_mes', align: 'start' },
  //     { title: 'Número de Documentos', key: 'total_documentos', align: 'end' },
  //     { title: 'Valor Parcial (Subtotal)', key: 'total_subtotal', align: 'end' },
  //     { title: 'Valor Iva', key: 'total_iva', align: 'end' },
  //     { title: 'Gran Total', key: 'gran_total', align: 'end' },
  //   ]

  const headers = [
      { title: 'Meses', key: 'nombre_mes', align: 'start', width: '15%' },
      { title: 'Número de\nDocumentos', key: 'total_documentos', align: 'end', width: '17%' },
      { title: 'Valor Parcial\n(Subtotal)', key: 'total_subtotal', align: 'end', width: '22%' },
      { title: 'Valor Iva', key: 'total_iva', align: 'end', width: '20%' },
      { title: 'Gran Total', key: 'gran_total', align: 'end', width: '26%' },
    ]

   const anioSeleccionado = ref(2026)

   const listaAnios = computed(() => 
   {
        const anios = []
        for (let y = 2020; y <= 2050; y++) 
            {
            anios.push(y)   
            }
            return anios
    })

   const toggle = ref('ventas')
</script>

 <template>
      <!-- <VCard class="mb-2" style="height: 13vh !important;"">  -->
        <VCard class="mb-2 py-1 px-4">
          <VRow class="align-center">
              <VCol cols="12" md="3" class="d-flex align-center flex-column">
                  <h4 class="text-primary mb-2">Estadística Anual</h4>             
                  <div class="d-inline-flex align-center pa-1 mb-0">
                       <v-btn-toggle
                          v-model="toggle"
                          mandatory
                          variant="text" 
                          density="compact" 
                          selected-class="bg-primary" 
                          class="custom-toggle"
                        >
                          <v-btn value="ventas" class="btn-toggle rounded-sm text-capitalize px-2">
                            Ventas
                          </v-btn>   

                          <v-btn value="compras" class="btn-toggle rounded-sm text-capitalize px-2">
                            Compras
                          </v-btn>
                       </v-btn-toggle>
                  </div>                     
              </VCol>

              <VCol cols="12" md="4">
                    <h4 class="text-primary mb-2">Escojer el Año</h4>           
                        <VSelect
                          v-model="anioSeleccionado"
                          :items="listaAnios"                               
                          prepend-inner-icon="tabler-calendar"
                          hide-details
                          density="compact"
                          class="my-select"
                         />                       
              </VCol>

              <!-- <VCol cols="12" md="2">
                  <AppDateTimePicker
                      v-model="datafechas.hastafecha"
                      label="Hasta Fecha :"
                      placeholder="Seleccionar Fecha"
                      class="text-center-input"
                      prepend-inner-icon="tabler-calendar"
                      :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
                  />
              </VCol> -->

              <VCol cols="12" md="2" class="d-flex align-center justify-start mt-md-5 mt-2">
                  <VBtn rounded="pill" color="primary" variant="flat" block @click="generarConsulta">
                      Generar Consulta 
                  </VBtn>
              </VCol>

              <VCol cols="12" md="2" class="d-flex align-center justify-start mt-md-5 mt-2 gap-2">
                  <VBtn
                    class="boton-export"
                    rounded="pill"
                    color="success"
                    variant="flat"
                    :disabled="!records?.length"
                    @click="exportarExcel"
                  >
                    <VIcon start icon="tabler-file-spreadsheet" />
                    Excel
                  </VBtn>

                  <VBtn
                    class="boton-export"
                    rounded="pill"
                    color="error"
                    variant="flat"
                    :disabled="!records?.length"
                    @click="exportarPDF"
                  >
                    <VIcon start icon="tabler-file-type-pdf" />
                    PDF
                  </VBtn>
              </VCol>
          </VRow>
      </VCard>
      
      <!-- <section v-if="facturas && facturas.length"> -->
      <section v-if="yaBusco && records && records.length"> 
        <VCard>
              <VDataTable
                v-model:model-value="selectedRows"
                v-model:items-per-page="itemsPerPage"
                v-model:page="page"               
                :headers="headers"
                :items="records"
                item-value="id"               
                :search="searchQuery"
                class="text-body-2" 
                fixed-header
                density="compact"
              >      
              
  
                <template #item.nombre_mes="{ item }">
                    <div class="_fila text-start" style="line-height: 1.2;">
                        {{ item.nombre_mes }}
                    </div>
                </template>

                <template #item.total_documentos="{ item }">
                    <div class="_fila text-end" >
                        {{ formatCurrency(item.total_documentos) }} 
                    </div>
                </template>

                <template #item.total_subtotal="{ item }">
                    <div class="_fila text-end">
                      {{ formatCurrency(item.total_subtotal, 2) }}
                    </div>
                </template>

                <template #item.total_iva="{ item }">
                    <div class="_fila text-end">
                        {{ formatCurrency(item.total_iva, 2) }} 
                    </div>
                </template>

                <template #item.gran_total="{ item }">
                    <div class="_fila text-end">
                        {{ formatCurrency(item.gran_total, 2) }} 
                    </div>
                </template>
                <!-- Slot Bottom Personalizado -->
                <template #bottom>
                      <VDivider />
                      <VRow class="mt-2 mx-0 pb-2 align-center">     
                            <VCol cols="12" md="4">
                                <div class="text-caption text-medium-emphasis ps-4">
                                    Mostrando
                                    <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                                    <strong>{{ Math.min(currentPage * perPage, totalregistros) }}</strong>
                                    de <strong>{{ totalregistros }}</strong> registros
                                </div>
                            </VCol>
                            <VCol cols="12" md="4" class="d-flex justify-center pagination-wrapper"> 
                                <VPagination
                                      v-model="page"
                                      :length="Math.ceil(totalregistros / perPage)"
                                      rounded="circle"
                                      size="large"
                                      :total-visible="5"
                                />
                            </VCol>
                            <VCol cols="12" md="4">
                                <div class="text-caption text-medium-emphasis ps-4 text-end">
                                    Total Documentos $:
                                    <strong class="text-primary">{{ formatCurrency(totalInvoices,2) }}</strong>
                                    <!-- <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                                    <strong>{{ Math.min(currentPage * perPage, totalInvoices) }}</strong>
                                    de <strong>{{ totalInvoices }}</strong> registros -->
                                </div>
                                <div class="text-caption text-medium-emphasis ps-4 text-end">
                                    Total Iva $:
                                    <strong class="text-error">{{ formatCurrency(totalIva,2) }}</strong>
                                    <!-- <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                                    <strong>{{ Math.min(currentPage * perPage, totalInvoices) }}</strong>
                                    de <strong>{{ totalInvoices }}</strong> registros -->
                                </div>
                            </VCol>
                      </VRow>           
                  </template>                
            </VDataTable>             
        </VCard>        
      </section>

     
      <section v-else-if="yaBusco && (!facturas || !facturas.length)">
        <VCard>
          <VCardTitle class="pa-4">No se encontraron registros para el periodo seleccionado</VCardTitle>
        </VCard>
      </section>

      <VOverlay :model-value="loading" persistent class="align-center justify-center">
            <VProgressCircular indeterminate size="64" color="primary" />
      </VOverlay>

      <VSnackbar v-model="snackbar" color="error" timeout="4000" location="center" style="font-size: 5em !important;">
        <VIcon icon="tabler-alert-circle" class="me-2" />
           <span style="font-size: 1.2rem; font-weight: 500;">
              No se encontraron registros, intente nuevamente por favor
          </span>
        <template #actions>
          <VBtn variant="text" @click="snackbar = false">Cerrar</VBtn>
        </template>
      </VSnackbar>
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

  thead th
  {
      background-color: rgb(247, 58, 206) !important;
      color: white !important;      
  }

  .v-data-table thead th 
  {
     text-transform: capitalize !important;
  }

  ._fila {
    font-size: 12px;
    color:black;
    white-space: nowrap;
    line-height: 1.4;
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

  .dian-iframe {
    width: 100%;
    height: 700px;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 8px;
  }

  .loader-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(var(--v-theme-surface), 0.7);
    z-index: 2;
  }

  .dian-iframe-full {
      width: 100%;
      height: 100%;
      border: none;
      background-color: #fff;
    }

   
  .gap-2 {
      gap: 8px;
    }

   /* .header-columna .v-table{
       color: white !important;
    }

     .header-columna {
       color: white !important;
    } */

     .v-data-table thead th .v-table {
        color: white !important;
     }

     .v-data-table thead th {
        color: white !important;
    }

    .header-columna {
        font-size: 1.0em !important;        
    }

    .boton-export {
       width: 10em !important;       
    }

    /* Si también quieres cambiar el tamaño del label */
 .my-select .v-field__input {
      font-size: 1.1rem !important;
    }

  .btn-toggle {
    width: 4rem !important; /* Ajusta el ancho mínimo según tus necesidades */
    height: 2rem !important; /* Ajusta la altura según tus necesidades */
    padding: 0 0 0 0 !important; /* Ajusta el padding para reducir la altura */
    font-size: 0.8rem !important; /* Ajusta el tamaño de fuente si es necesario */    
  }

  .v-data-table td {
  vertical-align: middle;
}

  .v-data-table th {
    white-space: pre-line; /* permite \n en headers */
  }

  .v-data-table td,
  .v-data-table th {
    vertical-align: middle;
  }

  .v-data-table {
    font-variant-numeric: tabular-nums;
  }
      
</style>

