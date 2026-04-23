<script setup lang="ts">
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import fileDownload from 'js-file-download'
import { T } from 'node_modules/unplugin-vue-router/dist/options-ChnxZdan.mjs'
import { computed, ref } from 'vue'
import { VRow } from 'vuetify/components'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

    const snackbar = ref(false)
    const yaBusco = ref(false) // Nueva variable de control

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
        status: '',
        message:  '',   
        TotalDocumentos:0,   
        TotalValor:0,
        TotalIva:0,
        data: [],
        page: 1,
        per_page: 13,       
      })
    //
    const showDialogEmail = ref(false)
    const editMode = ref(false)
 
    // 🔹 Variables del DataTable
    const itemsPerPage = ref(13)
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
          console.log("Id Company:", localStorage.getItem('company_id'))
          loading.value = true
          try {
                //onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
                const { data } = await axios.post('/api/dian/documentos-enviados', {
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
            invoiceData.value = data;
            console.log("Respuesta API:", data)
            console.log("Respuesta InvoiceData:", invoiceData.value)
            yaBusco.value = true // Marcar que ya se realizó una búsqueda
            if (invoiceData.value.TotalDocumentos === 0 && yaBusco.value) {               
               snackbar.value = true
            }
          } catch (error) {
            console.error(error)
          } finally {
            loading.value = false
          }
    }

    //onMounted(() => generarConsulta())

    const facturas        = computed(() => invoiceData.value.data?? [])
    const currentPage     = computed(() => invoiceData.value.page ?? page.value)
    const perPage         = computed(() => invoiceData.value.per_page ?? itemsPerPage.value)
    // const totalInvoices   = computed(() => invoiceData.value.TotalValor ?? 0)
    // const totalIva        = computed(() => invoiceData.value.TotalIva ?? 0)
    const totalregistros  = computed(() => invoiceData.value.TotalDocumentos ?? 0)
    
    console.log("Soy Registro..:", invoiceData.value.TotalDocumentos)
   

    // ── Exportar a Excel ──────────────────────────────────────────
    const exportarExcel = () => {
      const datos = facturas.value.map(item => ({
        'ID':             item.id,
        'Fecha':          item.date_issue,
        'Prefijo':        item.prefix,
        'Número':         item.number,
        'Tipo Documento': item.document_name,
        'Nit/Cédula':     item.customer,
        'Cliente':        item.client_name,
        'Subtotal':       item.subtotal,
        'IVA':            item.vatvalue,
        'Total':          item.total_sale,
        'CUFE':           item.cufe,
      }))

      const hoja  = XLSX.utils.json_to_sheet(datos)
      const libro = XLSX.utils.book_new()
      XLSX.utils.book_append_sheet(libro, hoja, 'Facturas')
      XLSX.writeFile(libro, `Facturas_Ventas_${datafechas.value.desdefecha}_${datafechas.value.hastafecha}.xlsx`)
    }

    // ── Exportar a PDF ────────────────────────────────────────────
    const exportarPDF = () => {
      const doc = new jsPDF({ orientation: 'landscape' })

      doc.setFontSize(14)
      doc.text('Documentos Enviados (Facturas/Notas)', 14, 15)
      doc.setFontSize(9)
      doc.text(`Período: ${datafechas.value.desdefecha}  al  ${datafechas.value.hastafecha}`, 14, 22)

      autoTable(doc, {
        startY: 28,
        head: [[
          'ID', 'Fecha', 'Prefijo', 'Número', 'Tipo Doc.',
          'Nit/Cédula', 'Cliente', 'Subtotal', 'IVA', 'Total'
        ]],
        body: facturas.value.map(item => [
          item.id,
          item.date_issue,
          item.prefix,
          item.number,
          item.document_name,
          item.customer,
          item.client_name,
          formatCurrency(item.subtotal),
          formatCurrency(item.vatvalue),
          formatCurrency(item.total_sale)          
        ]),
        styles:     { fontSize: 7, cellPadding: 2 },
        headStyles: { fillColor: [25, 118, 210], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [240, 248, 255] },
        foot: [[
          '', '', '', '', '', '', 'TOTALES',
          formatCurrency(totalInvoices.value - totalIva.value),   // opcional si lo tienes calculado
          formatCurrency(totalIva.value),
          formatCurrency(totalInvoices.value),
        ]],
        footStyles: { fillColor: [200, 230, 255], fontStyle: 'bold' },
      })

      doc.save(`Facturas_Ventas_${datafechas.value.desdefecha}_${datafechas.value.hastafecha}.pdf`)
    }


        // Items filtrados según searchQuery
    // ✅ Computed de items filtrados
    const facturasFiltradas = computed(() => {
      if (!searchQuery.value || !facturas.value?.length) return facturas.value ?? []
      const q = searchQuery.value.toLowerCase()
      return facturas.value.filter(item =>
        Object.values(item).some(val =>
          String(val ?? '').toLowerCase().includes(q)
        )
      )
    })

    // ✅ Totales que reaccionan al filtro
    const totalInvoices = computed(() =>
      facturasFiltradas.value.reduce((acc, item) => acc + (Number(item.total_sale) || 0), 0)
    )

    const totalIva = computed(() =>
      facturasFiltradas.value.reduce((acc, item) => acc + (Number(item.vatvalue) || 0), 0)
    )

    const headers = [
        { title: 'id',                              key: 'id',              sortable: true, width: '4px' },
        { title: 'Fecha Documento',                 key: 'date_issue',      sortable: true },
        { title: 'Número de Factura',               key: 'number',          sortable: true, width: '5px' },
        { title: 'Prefijo',                         key: 'prefix',          sortable: true },
        { title: 'Tipo Documento',                  key: 'document_name',   sortable: true },
        { title: 'Nit/Cédula',                      key: 'customer',        sortable: true, width: '12%'},
        { title: 'Nombre del Cliente',              key: 'client_name',     sortable: true, width: '40%' },
        { title: 'Valor Documento',                 key: 'subtotal',        sortable: true },        
        { title: 'Valor Impuestos',                 key: 'vatvalue',        sortable: true },
        { title: 'Total Documento',                 key: 'total_sale',      sortable: true },
        { title: 'Acciones',                        key: 'actions',         sortable: false, width: '15%' },
    ]

</script>

 <template>
      <!-- <VCard class="mb-2" style="height: 13vh !important;"">  -->
        <VCard class="mb-2 py-3 px-4">
          <VRow class="align-center">
              <VCol cols="12" md="3" class="d-flex align-center flex-column">
                  <h3 class="text-primary mb-2">Documentos Enviados</h3>   
                   <!-- Campo de búsqueda -->
                  <!-- <VCardText class="d-flex align-center flex-wrap gap-4 pb-0"></VCardText> -->
                  <VCardText class="d-flex align-center flex-wrap gap-4 pa-0">
                      <VTextField
                        v-model="searchQuery"
                        placeholder="Buscar..."
                        density="compact"
                        prepend-inner-icon="tabler-search"
                        variant="outlined"
                        clearable
                        hide-details
                        style="width:20em;max-width: 300px;"
                      />
                  </VCardText>
          
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
                    :disabled="!facturas?.length"
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
                    :disabled="!facturas?.length"
                    @click="exportarPDF"
                  >
                    <VIcon start icon="tabler-file-type-pdf" />
                    PDF
                  </VBtn>
              </VCol>
          </VRow>
      </VCard>
      
      <!-- <section v-if="facturas && facturas.length"> -->
      <section v-if="yaBusco && facturas && facturas.length"> 
            <VCard>
              <VDataTable
                v-model:model-value="selectedRows"
                v-model:items-per-page="itemsPerPage"
                v-model:page="page"               
                :headers="headers"
                :items="facturasFiltradas"
                item-value="id"               
                :search="searchQuery"
                class="text-body-2" 
                fixed-header
                density="compact"
              >        
              
              <template #header.id="{ column }">
                <div class = "header-columna" style="text-align:center; white-space:normal;">
                    #Id
                </div>
              </template>
              <template #item.id="{ item }">
                <div class="_fila text-right" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.id }}
                </div>
              </template>

              <template #header.date_issue="{ column }">
                <div class = "header-columna" style="text-align:center; white-space:normal;">
                    Fecha<br>Documento
                </div>
              </template>
              <template #item.date_issue="{ item }">
                <div class="_fila text-center" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.date_issue }}
                </div>
              </template>

              <template #header.number="{ column }">
                  <div class = "header-columna" style="text-align:center; white-space:normal;">
                    Número<br>Factura
                  </div>
              </template>
              <template #item.number="{ item }">
                  <div class="_fila text-right" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.number }}
                  </div>
              </template>

              <template #header.prefix="{ column }">
                  <div class = "header-columna" style="text-align:center; white-space:normal;">
                   Prefijo
                  </div>
              </template>
              <template #item.prefix="{ item }">
                  <div class="_fila" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.prefix }}
                  </div>
              </template>

              <template #header.document_name="{ column }">
                  <div class = "header-columna" style="text-align:center; white-space:normal;">
                   Tipo<br>Documento
                  </div>
              </template>
              <template #item.document_name="{ item }">
                  <div class="_fila" style="width: 10.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.document_name }}
                  </div>
              </template>

              <template #header.customer="{ column }">
                   <div class = "header-columna" style="text-align:center; white-space:normal;">
                      Nit/Cédula<br>Cliente
                   </div>             
              </template>
              <template #item.customer="{ item }">
                  <div class="_fila" style="width: 6.5em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.customer }}
                  </div>
              </template>

              <template #header.client_name="{ column }">
                  <div class="header-columna" style="text-align:center; white-space:normal;">
                   Nombre del Cliente
                  </div>
              </template>
              <template #item.client_name="{ item }">
                  <div class="_fila" style="width: 27em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.client_name }}
                  </div>
              </template>

              <template #header.subtotal="{ column }">       
                  <div class="header-columna" style="text-align:center; white-space:normal;">
                   Valor Parcial
                  </div>
              </template>
              <template #item.subtotal="{ item }">
                <div class="_fila text-right" style="width: 6.5em; white-space: normal; word-wrap: break-word; line-height: 1.2;">           
                    {{ formatCurrency((item.subtotal)) }} 
                </div>
              </template>

              <template #header.vatvalue="{ column }">
                  <div class="header-columna" style="text-align:center; white-space:normal;">
                   Valor Impuestos
                  </div>
              </template>
              <template #item.vatvalue="{ item }">
                  <div class="_fila text-right" style="width: 6.5em; white-space: normal; word-wrap: break-word; line-height: 1.2;">                   
                    {{ formatCurrency(item.vatvalue) }} 
                  </div>
              </template>

              <template #header.total_sale="{ column }">
                  <div class="header-columna" style="text-align:center; white-space:normal;">
                   Valor Total
                  </div>
              </template>
              <template #item.total_sale="{ item }">
                  <div class="_fila text-right" style="width: 6.5em; white-space: normal; word-wrap: break-word; line-height: 1.2;">                    
                    {{ formatCurrency(item.total_sale) }} 
                  </div>
              </template>

              <template #header.actions>
                  <div class="header-columna" style="text-align:center; white-space:normal;">
                   Acciones
                  </div>
              </template>

                
              <template #item.actions="{ item }">
                 <div style="display: flex; align-items: center; gap: 4px;">
                    <IconBtn>
                      <VIcon icon="tabler-file-type-xml" color="primary" @click="" />
                    </IconBtn>
                    <!-- <IconBtn>
                      <VIcon icon="tabler-file-type-pdf" color="error" @click="" />
                    </IconBtn>   
                    <IconBtn>
                      <VIcon icon="tabler-mail" color="warning" @click="abrirDialogoEmail(item)" />
                    </IconBtn> -->
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
                              <strong class="text-primary">{{ formatCurrency(totalInvoices) }}</strong>
                              <!-- <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                              <strong>{{ Math.min(currentPage * perPage, totalInvoices) }}</strong>
                              de <strong>{{ totalInvoices }}</strong> registros -->
                           </div>
                           <div class="text-caption text-medium-emphasis ps-4 text-end">
                              Total Iva $:
                              <strong class="text-error">{{ formatCurrency(totalIva) }}</strong>
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

  ._fila
    {
        font-size: 11px !important;
        width: 6.0em; 
        color:black;
        white-space: normal; 
        word-wrap: break-word; 
        line-height: 1.2;
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

   .loader-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(var(--v-theme-surface), 0.9);
      z-index: 10;
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
        font-size: 0.9em !important;        
    }

    .boton-export {
       width: 10em !important;       
    }
   
    /* Contenedor con scroll horizontal en pantallas pequeñas */
    .table-responsive {
      overflow-x: auto;
      width: 100%;
    }

    /* Headers */
    .th-center {
      text-align: center;
      white-space: normal;
      line-height: 1.3;
      font-weight: 600;
    }

    /* Celdas base */
    .td-left,
    .td-right,
    .td-center {
      white-space: normal;
      word-break: break-word;
      line-height: 1.3;
    }

    .td-right  { text-align: right; }
    .td-center { text-align: center; }
    .td-left   { text-align: left; }

    .td-actions {
      display: flex;
      align-items: center;
      gap: 4px;
    }

    /* Columnas con ancho controlado — se achican en pantallas pequeñas */
    .col-cliente {
      min-width: 160px;
      max-width: 220px;
    }

    .col-tipo {
      min-width: 100px;
      max-width: 140px;
    }

    /* Tabla compacta en 1366px */
    @media (max-width: 1366px) {
      .tabla-facturas {
        font-size: 0.78rem;
      }

      .col-cliente {
        min-width: 130px;
        max-width: 180px;
      }
    }

</style>
