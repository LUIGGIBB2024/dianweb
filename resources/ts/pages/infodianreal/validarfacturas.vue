<script setup lang="ts">
import axios from 'axios'
import { computed, ref } from 'vue'
import { VRow } from 'vuetify/components'
import * as XLSX from 'xlsx'

    
    const dataExcel = ref([])
    const columns = ref([])
    const requiredColumns = ['numero', 'prefijo', 'nit']
    const jsonDataHeader = ref<Record<string, any>[]>([]) // Aquí guardaremos el JSON convertido del Excel

    //const dato_json = ref<FacturasRow[]>([])
    const dato_json = ref<Record<string, any>[]>([])
    const headers_excel = ref<any[]>([])

    const handleFileUpload = (event: Event) => 
    {
        const input = event.target as HTMLInputElement

        if (!input.files || input.files.length === 0) return

        const file = input.files[0]

        const reader = new FileReader()

        reader.onload = (e: ProgressEvent<FileReader>) => {
        console.log('Entre a Procesar Archivo de Excel')

        const result = e.target?.result

        if (!result || !(result instanceof ArrayBuffer)) {
            console.error('El archivo no es válido')
            return
          }

        const data = new Uint8Array(result)
        const workbook = XLSX.read(data, { type: 'array' })

        const sheetName = workbook.SheetNames[0]
        const worksheet = workbook.Sheets[sheetName]

        //const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: '' }) as Record<string, any>[]
        //const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: '' }) as Record<string, any>[]
        const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: '' }) as Record<string, any>[]

        // Validar columnas
        const fileColumns = Object.keys(jsonData[0] || {})

        const missingColumns = requiredColumns.filter(col => !fileColumns.includes(col))

        if (missingColumns.length > 0) 
        {
          console.error('Faltan columnas:', missingColumns)
          return
        }

        jsonDataHeader.value = jsonData     
        const validatedData = jsonData.map((row, index) => {
        const errors: string[] = []

        if (!row.numero) errors.push('<número> vacío')       
        if (!row.nit) errors.push('<nit> vacío')
        isActive.value = true // Activar el toggle al procesar el archivo
        return {
          ...row,
          _errors: errors,
          _isValid: errors.length === 0,
          _row: index + 1,
        }
      })

      dato_json.value = validatedData

      console.log('JSON Data:', dato_json.value)  
      const datosValidados = computed(() => {
        return dato_json.value.map(row => ({
          ...row,
          _isValid: true
        }))
      })
      console.log('JSON Data:', dato_json.value, 'Datos Validados:', validatedData)        
    }

      reader.readAsArrayBuffer(file) // 👈 ESTO TE FALTABA ACTIVAR
   }
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

    const recordData = ref({
        status: '',
        message:  '',   
        TotalDocumentos: 0,   
        data:[], 
        page: 1,
        per_page: 12,       
      })
 
    // 🔹 Variables del DataTable
    const itemsPerPage = ref(12)
    const page = ref(1)
    const sortBy = ref()
    const orderBy = ref()

    
    const token = localStorage.getItem('auth_token')
    
    const validarFacturas = async () =>
    {
         
          console.log("Generando Consulta con Toggle:", toggle.value)          
          loading.value = true
          try {
                //onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
                const { data } = await axios.post('/api/dian/validar-facturas', {   
                  dato_json    :dato_json.value,                 
                  url_token    : localStorage.getItem('auth_token'),
                  company_id   : localStorage.getItem('company_id'),           
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
            if (recordData.value.TotalDocumentos === 0 && yaBusco.value) 
            {               
               snackbar.value = true
            }
             dato_json.value = recordData.value.data.map((item: Record<string, any>) => ({
                  numero: item.numero,
                  prefijo: item.prefijo,
                  nit: item.nit,
                  estado: item.estado,
              }))
            console.log("Datos para DataTable:", dato_json.value)
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

    

    //console.log('Datos para DataTable:', dato_json.value)

    // ── Exportar a Excel ──────────────────────────────────────────
    const exportarExcel = () => {
      const datos = dato_json.value.map(item => ({       
        'Numero de Factura' :item.numero,
        'Prefijo'           :item.prefijo,
        'Nit'               :item.nit,
        'Estado'            :item.estado,        
      }))

      const hoja  = XLSX.utils.json_to_sheet(datos)
      const libro = XLSX.utils.book_new()
      const fecha = new Date().toISOString().split('T')[0]
      XLSX.utils.book_append_sheet(libro, hoja, 'Facturas')
      XLSX.writeFile(libro, `Validacion_de_Facturas_de_Ventas_${fecha}.xlsx`)
    }

    const headers = [
        { title: 'Numero de\nFactura',              key: 'numero',         sortable: true, width: '10px' },
        { title: 'Prefijo',                         key: 'prefijo',        sortable: true, width: '12%'},
        { title: 'Nit',                             key: 'nit',           sortable: true, width: '12%'},
        { title: 'Estado',                          key: 'estado',        sortable: true, width: '12%'},
    ]


   const toggle = ref('ventas')
</script>

 <template>
      <!-- <VCard class="mb-2" style="height: 13vh !important;"">  -->
        <VCard class="mb-2 py-1 px-4">
          <VRow class="align-center">
              <VCol cols="12" md="4" class="d-flex align-center flex-column">
                  <h4 class="text-primary mb-1">Validación de Facturas de Ventas</h4> 
                  <h5 class="text-success mb-1">(Cargar datos desde su ERP)</h5>                                           
              </VCol>

              <VCol cols="12" md="4">
                  <div>
                    <h2>Cargar archivo de Excel</h2>

                    <input type="file" accept=".xlsx, .xls" @change="handleFileUpload" />

                    <div v-if="dataExcel.length" style="margin-top: 20px;">
                      <h3>Datos cargados:</h3>

                      <table border="1" cellpadding="8">
                        <thead>
                          <tr>
                            <th v-for="col in columns" :key="col">
                              {{ col }}
                            </th>
                          </tr>
                        </thead>

                        <tbody>
                          <tr v-for="(row, index) in dataExcel" :key="index">
                            <td v-for="col in columns" :key="col">
                              {{ row[col] }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>                    
              </VCol>


              <VCol cols="12" md="2" class="d-flex align-center justify-start mt-md-5 mt-2">
                  <VBtn rounded="pill" color="primary" variant="flat" block @click="validarFacturas">
                      Validar Facturas
                  </VBtn>
              </VCol>

              <VCol cols="12" md="2" class="d-flex align-center justify-start mt-md-5 mt-2 gap-2">
                  <VBtn
                    class="boton-export"
                    rounded="pill"
                    color="success"
                    variant="flat"
                    :disabled="!dato_json?.length"
                    @click="exportarExcel"
                  >
                    <VIcon start icon="tabler-file-spreadsheet" />
                    Excel
                  </VBtn>            
              </VCol>
          </VRow>
      </VCard>
      
      <!-- <section v-if="facturas && facturas.length"> -->
      <section v-if="dato_json && dato_json.length"> 
          <VRow>
            <VCol cols="12" md="5">
              <VCard class="mb-2 py-1 px-4">
                <VDataTable
                    :headers="headers"
                    :items="dato_json"
                    fixed-header
                    density="compact">
                    <template #header.numero>
                      <div  style="text-align:center; white-space:normal;">
                          Número de<br>Factura
                      </div>
                    </template>
                    <template #item.numero="{ item }">
                        <div class="_fila text-right" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                           {{item.numero}}  
                        </div>
                    </template>
                    <template #item.prefijo="{ item }">
                        <div class="_fila text-left" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                           {{item.prefijo}}  
                        </div>
                    </template>
                    <template #item.nit="{ item }">
                        <div class="_fila text-left" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                           {{item.nit}}  
                        </div>
                    </template>
                  <template #item.estado="{ item }">
                    <div 
                      class="_fila text-left" 
                      :style="{
                        width: '7.5em', // Aumentado ligeramente para que quepa bien el texto con padding
                        'word-wrap': 'break-word',
                        'line-height': '1.2', // Reducido para que el texto quede más compacto
                        'padding': '2px 2px', // Espacio interno: arriba/abajo y lados
                        'border-radius': '15px', // <--- Aquí redondeas los bordes
                        'text-align': 'center', // Opcional: centra el texto para que luzca mejor el redondeo
                        'font-weight': 'bold', // Opcional: resalta el estado
                        'background-color': item.estado === 'Encontrada' ? 'lightgreen' : 'lightgrey',
                        color: item.estado === 'Encontrada' ? 'green' : 'red',  
                      }"
                    >
                      {{ item.estado }}
                    </div>
                  </template>

                    <template #bottom>
                        <VDivider />
                        <VRow class="d-flex mt-2 mx-0 pb-2 align-center">     
                            <VCol cols="12" md="4">
                                <div class="text-caption text-medium-emphasis ps-4">
                                    Filas
                                    <spam>{{ (currentPage - 1) * perPage + 1 }}</spam>–
                                    <spam>{{ Math.min(currentPage * perPage, dato_json.length) }}</spam>
                                    de <spam>{{ dato_json.length }}</spam> 
                                </div>
                            </VCol>
                            <VCol cols="12" md="4" class="d-flex justify-center pagination-wrapper"> 
                                <VPagination
                                      v-model="page"
                                      :length="Math.ceil(dato_json.length / perPage)"
                                      rounded="circle"
                                      size="small"
                                      :total-visible="5"
                                />
                            </VCol>                    
                        </VRow>           
                      </template>  
               </VDataTable>
              </VCard>
            </VCol>
          </VRow>        
      </section>

      <!-- <template #item.numero="{ value }">
          <div class="_fila text-right" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
               {{item.numero}}
          </div>
      </template> -->

     
      <!-- <section v-else-if="yaBusco && (!facturas || !facturas.length)">
        <VCard>
          <VCardTitle class="pa-4">No se encontraron registros para el periodo seleccionado</VCardTitle>
        </VCard>
      </section> -->

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
        font-size: 12px !important;
        width: 6.0em; 
        white-space: normal; 
        word-wrap: break-word; 
        line-height: 2.8;
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
  
</style>

