<script setup lang="ts">
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import fileDownload from 'js-file-download'
import { url } from 'node:inspector'
import { computed, onMounted, ref } from 'vue'
import { VRow } from 'vuetify/components'

    const yaBusco = ref(false) // Nueva variable de control

    const iframeSource = ref<string | null>(null)
    const isLoading = ref(false)
    const showIframeDialog = ref(false) 
    let dianWindows: Window | null = null

    // ─── Variables DIAN Token ────────────────────────────
    const cedulaUsuario   = ref("77193886")
    const isEsperando     = ref(false)   // Ventana cerrada, esperando correo
    const tokenRecibido   = ref(false)   // Token llegó exitosamente
    const tokenDian       = ref<string | null>(null)
    const urlCompletaDian = ref<string | null>(null)
    const mensajeError    = ref<string | null>(null)
    const _facturas       = ref([])    

    let pollingTimer: ReturnType<typeof setInterval> | null = null
    let ventanaTimer: ReturnType<typeof setInterval> | null = null

    const token = localStorage.getItem('auth_token')

    // ─── Cargar portal DIAN ──────────────────────────────
    const loadDianPortal = async () => {
        // Resetea estado
        mensajeError.value  = null
        tokenRecibido.value = false
        tokenDian.value     = null
        isEsperando.value   = false
        const token         = localStorage.getItem('auth_token')
        const userId        = localStorage.getItem('user_id')  
        const companyId     = localStorage.getItem('company_id')  // ← agrega esta línea
        const urln8n        = localStorage.getItem('url_n8n')  // ← agrega esta línea

        console.log("Token en LoadDianPortal:", token)
        console.log("Company ID en LoadDianPortal:", companyId) 
        console.log("USe ID en LoadDianPortal:", userId) 
   
        try {
            const textarea = document.createElement('textarea')
            textarea.value = cedulaUsuario.value
            document.body.appendChild(textarea)
            textarea.select()
            document.execCommand('copy')
            document.body.removeChild(textarea)
        } catch (e) {
            console.error('Error al copiar:', e)
        }

        // 3. Abre ventana DIAN
        const url_person        = `https://catalogo-vpfe.dian.gov.co/User/PersonLogin`
        const url_companies     = `https://catalogo-vpfe.dian.gov.co/User/CompanyLogin`
        const url_final = ref(url_companies)
        const width  = 1200
        const height = 800
        const left   = (screen.width  - width)  / 2
        const top    = (screen.height - height) / 2

        const _nit_empresa = ref(localStorage.getItem('nit_empresa'))

        const nit = _nit_empresa.value?.trim() || '';
        const esNitValido = /^[89]\d{8}$/.test(nit);

        if (!esNitValido) {
           url_final.value = url_person
        }        


        //'https://catalogo-vpfe.dian.gov.co/User/PersonLogin'
        dianWindows = window.open(
             url_final.value,
            'PortalDIAN',
            `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes`
        )

        isLoading.value = true

        // 4. Detecta cierre de ventana → inicia polling
        ventanaTimer = setInterval(() => {
            if (dianWindows?.closed) {
                //console.error('<< Ventana Cerrada >>')
                SolicitarTokenDian()
                clearInterval(ventanaTimer!)
                isLoading.value   = false
                isEsperando.value = true
                iniciarPolling()
            }
        }, 3000)
    }

    const SolicitarTokenDian = async () => 
    {

        const token         = localStorage.getItem('auth_token')
        const userId        = localStorage.getItem('user_id')  
        const companyId     = localStorage.getItem('company_id')  // ← agrega esta línea
        const urln8n        = localStorage.getItem('url_n8n')  // ← 
        const nitEmpresa     = localStorage.getItem('nit_empresa')  // ←
        const representanteLegal = localStorage.getItem('representante_legal')  // ←

        console.log("Token en LoadDianPortal:", token)
        console.log("Company ID en LoadDianPortal:", companyId) 
        console.log("USe ID en LoadDianPortal:", userId) 

        console.log("URL n8n en LoadDianPortal:", urln8n)
        console.log("NIT Empresa en LoadDianPortal:", nitEmpresa)

        await axios.post('/api/n8n/webhook', {
             token: token,
             company_id: companyId,
             user_id: userId,
             urln8n: urln8n,
             nit_empresa: nitEmpresa,
             representante_legal: representanteLegal
        },);      
 

        try {
            const { data } = await axios.post('/api/dian/solicitar-token', {
                token: token,
                company_id: companyId,
                user_id: userId,
                urln8n: urln8n,
                nit_empresa: nitEmpresa,
                representante_legal: representanteLegal
            }, {
                headers: { Authorization: `Bearer ${token}` }
            })
            return data
        } catch (e) {
            console.error('Error al solicitar token:', e)
            throw e
        }
    }

    // ─── Polling hacia Laravel ───────────────────────────
    const iniciarPolling = () => 
    {
        let intentos    = 0
        const maxIntentos = 20 // 20 x 3 seg = 60 seg máximo
        const token      = localStorage.getItem('auth_token')
        const userId  = localStorage.getItem('user_id')  
        const companyId  = localStorage.getItem('company_id') 

         console.log("Token en iniciarPolling:", token)
         console.log("Company ID en iniciarPolling:", companyId)
         console.log("User ID en iniciarPolling:", userId)

        pollingTimer = setInterval(async () => {
            intentos++

            try {
                const { data } = await axios.post('/api/dian/verificar-token',    
                {
                    token: token,
                    company_id: companyId,
                    user_id: userId
                },
                {
                    headers: { Authorization: `Bearer ${token}` }
                });

                switch (data.status) {
                    case 'received':
                        detenerPolling()
                        isEsperando.value   = false
                        tokenRecibido.value = true
                        tokenDian.value     = data.token
                        urlCompletaDian.value = data.url_completa
                        break

                    case 'timeout':
                        detenerPolling()
                        isEsperando.value  = false
                        mensajeError.value = 'Tiempo agotado. Intenta de nuevo.'
                        break
                }

            } catch (e) {
                console.error('Error en polling:', e)
            }

            // Agotó intentos sin recibir token
            if (intentos >= maxIntentos) {
                detenerPolling()
                isEsperando.value  = false
                mensajeError.value = 'No se recibió el token. Intenta de nuevo.'
                await axios.post('/api/dian/timeout', {}, {
                    headers: { Authorization: `Bearer ${token}` }
                })
            }

        }, 3000)
    }

    const detenerPolling = () => {
        if (pollingTimer)  clearInterval(pollingTimer)
        if (ventanaTimer)  clearInterval(ventanaTimer)
    }

    const cancelarDian = async () => {
        detenerPolling()
        isLoading.value   = false
        isEsperando.value = false

        if (dianWindows && !dianWindows.closed) {
            dianWindows.close()
        }

        try {
            await axios.post('/api/dian/timeout', {}, {
                headers: { Authorization: `Bearer ${token}` }
            })
        } catch (e) {
            console.error('Error al cancelar:', e)
        }
    }    

    const onIframeLoad  = () => { isLoading.value = false }
    const closeIframe   = () => {
        showIframeDialog.value = false
        iframeSource.value     = null
        isLoading.value        = false
    }

    const formatCurrency = (value: number | string): string => {
  // Limpiar el string: quitar espacios y símbolos
    const cleaned = String(value).trim().replace(/[^\d.,-]/g, '')

    // Normalizar formato
    const normalized =
        cleaned.includes(',') && cleaned.indexOf(',') > cleaned.indexOf('.')
        ? cleaned.replace(/\./g, '').replace(',', '.') // formato europeo
        : cleaned.replace(/,/g, '') // formato US

    const num = parseFloat(normalized) || 0

    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
    }

    const hoy           = new Date().toISOString().split('T')[0]
    const loading       = ref(false)
    const isFormValid   = ref(false)
    const datafechas    = ref({ desdefecha: hoy, hastafecha: hoy })
    const capturarEmail = ref({ email: '' })

    const rules = {
        required : (v: string) => !!v || 'Este campo es obligatorio',
        email    : (v: string) => !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Correo inválido',
        password : (v: string) => v.length >= 6 || 'La contraseña debe tener al menos 6 caracteres',
    }

    const datadocument     = ref({ numberdocument: '', prefix: '', email: '' })
    const correoelectronico = ref('')
    const selectedInvoice  = ref<any>(null)
    const showDialog       = ref(false)
    const isPasswordVisible = ref(false)
    const searchQuery      = ref('')
    const selectedRows     = ref([])

    const invoiceData = ref({
        status:"",TotalDocumentos:0,data: [], TotalValor: 0, page: 1, per_page: 10, totaldctos: 0,
    })

    const showDialogEmail = ref(false)
    const editMode        = ref(false)
    const itemsPerPage    = ref(10)
    const page            = ref(1)
    const sortBy          = ref()
    const orderBy         = ref()



    const updateOptions12 = async (options: any) => {
        page.value         = options.page
        itemsPerPage.value = options.itemsPerPage
        sortBy.value       = options.sortBy[0]?.key
        orderBy.value      = options.sortBy[0]?.order
        await generarConsulta()
    }

    const updateOptions1 = async (options: any) => {
        // Solo actualiza si ya se hizo la primera búsqueda manual
        if (!yaBusco.value) return 

        page.value = options.page
        itemsPerPage.value = options.itemsPerPage
        sortBy.value = options.sortBy[0]?.key
        orderBy.value = options.sortBy[0]?.order
        await generarConsulta()
    }

    const updateOptions = async (options: any) => 
    {
        if (!yaBusco.value) return

        const newPage = options.page
        const newItemsPerPage = options.itemsPerPage
        const newSortBy = options.sortBy[0]?.key
        const newOrderBy = options.sortBy[0]?.order

        // 🔥 evita llamadas innecesarias
        if (
            page.value === newPage &&
            itemsPerPage.value === newItemsPerPage &&
            sortBy.value === newSortBy &&
            orderBy.value === newOrderBy
        ) return

        page.value = newPage
        itemsPerPage.value = newItemsPerPage
        sortBy.value = newSortBy
        orderBy.value = newOrderBy

        await generarConsulta()
    }

    const generarConsulta = async () => {
        yaBusco.value = true
        loading.value = true

        try {
            const response = await axios.post('/api/scraping/dianf', {
            url_token    : urlCompletaDian.value,
            company_id   : localStorage.getItem('company_id'),
            fechadesde   : datafechas.value.desdefecha,
            fechahasta   : datafechas.value.hastafecha,
            q            : searchQuery.value,
            itemsPerPage : itemsPerPage.value,
            page         : page.value,
            sortBy       : sortBy.value,
            orderBy      : orderBy.value,
            }, {
            headers: {
                Authorization : `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            })

            const data = response.data

            // 🔥 AQUÍ ESTÁ LA CLAVE
            invoiceData.value = {
            data: data.data,
            TotalValor: data.TotalValor ?? 0,           
            page: data.page,
            per_page: data.per_page,
            totaldctos: data.ValorTotal ?? 0,
            status: data.status,
            TotalDocumentos: data.TotalDocumentos
            }

            // opcional (puedes eliminar _facturas)
            _facturas.value = data.data
            console.log("Respuesta del servidor 20:", invoiceData.value.TotalDocumentos," - ", invoiceData.value.TotalValor)

        } catch (error) {
            console.error('Error al generar consulta:', error)
        } finally {
            loading.value = false
        }
        }

    //onMounted(() => generarConsulta())

    //const facturas      = computed(() => _facturas.value)
    const facturas      = computed(() => invoiceData.value.data || [])
    const currentPage   = computed(() => invoiceData.value.page ?? page.value)
    const perPage       = computed(() => invoiceData.value.per_page ?? itemsPerPage.value)
    const totalInvoices = computed(() => invoiceData.value.TotalDocumentos ?? 0)
    const totaldctos    = computed(() => invoiceData.value.TotalValor ?? 0)

    //console.log("Respuesta del servidor 2:", invoiceData.value.data)

    const sendEmail = async () => {
        loading.value        = true
        const email          = capturarEmail.value.email
        const invoice        = selectedInvoice.value
        try {
            await axios.post('/api/sendpackage', {
                number                     : invoice.number,
                prefix                     : invoice.prefix,
                showacceptrejectbuttons    : false,
                email_cc_list              : [{ email }],
                base64graphicrepresentation: '',
            }, {
                headers: {
                    Authorization  : `Bearer ${token}`,
                    'Content-Type' : 'application/json',
                },
            })
            loading.value = false
        } catch (error) {
            console.error('Error al enviar correo:', error)
            loading.value = false
        }
    }

    const abrirDialogoEmail = (item: any) => {
        selectedInvoice.value = item
        showDialogEmail.value = true
    }

    const headers = [
        { title: 'Fecha Documento',                 key: 'Fecha',           sortable: true },
        { title: 'Número de Factura',               key: 'NroDocumento',    sortable: true, width: '6px' },
        { title: 'Prefijo',                         key: 'Prefijo',         sortable: true },
        { title: 'Tipo Documento',                  key: 'TipoDocumento',   sortable: true },
        { title: 'Nit/Cédula',                      key: 'NitEmisor',       sortable: true },
        { title: 'Nombre del Cliente/Proveedor',    key: 'Emisor',          sortable: true, width: '30%' },
        { title: 'Valor Documento',                 key: 'ValorParcial',    sortable: true },        
        { title: 'Valor Impuestos',                 key: 'ValorImptos',     sortable: true },
        { title: 'Total Documento',                 key: 'ValorTotal',      sortable: true },
        { title: 'Acciones',                        key: 'actions',         sortable: false, width: '15%' },
    ]
</script>

<template>
      <!-- <VCard class="mb-2" style="height: 13vh !important;"">  -->
        <VCard class="mb-2 py-3 px-4">
          <VRow class="align-center">
              <VCol cols="12" md="3" class="d-flex align-center flex-column">
                  <h3 class="text-primary mb-2">Cargar Información</h3>
                  <VBtn
                      color="primary" variant="elevated" prepend-icon="tabler-world-www" :disabled="isLoading || isEsperando" @click="loadDianPortal">
                      Capturar Token
                  </VBtn>

                  <!-- Ventana abierta -->
                  <div v-if="isLoading" class="mt-2 text-center">
                      <VProgressCircular indeterminate size="20" color="primary" class="me-2" />
                      <span class="text-caption">Generando TOKEN</span>
                      <br>
                      <VBtn size="small" color="error" variant="text" class="mt-1" @click="cancelarDian">
                          Cancelar
                      </VBtn>
                  </div>

                  <!-- Ventana cerrada, esperando correo -->
                  <div v-if="isEsperando" class="mt-2 text-center">
                      <VProgressCircular indeterminate size="20" color="warning" class="me-2" />
                      <span class="text-caption text-warning">Esperando Respuesta</span>
                      <br>
                      <VBtn size="small" color="error" variant="text" class="mt-1" @click="cancelarDian">
                          Cancelar
                      </VBtn>
                  </div>

                  <!-- Token recibido -->
                  <VAlert
                      v-if="tokenRecibido"
                      type="success"
                      variant="tonal"
                      density="compact"
                      class="mt-2"
                      closable
                      @click:close="tokenRecibido = false"
                  >
                      ✅ Token Recibido
                  </VAlert>

                  <!-- Error -->
                  <VAlert
                      v-if="mensajeError"
                      type="error"
                      variant="tonal"
                      density="compact"
                      class="mt-2"
                      closable
                      @click:close="mensajeError = null"
                  >
                      {{ mensajeError }}
                  </VAlert>
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
                      Cargar Ventas
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
                :items="facturas"
                item-value="id"
                show-select               
                :search="searchQuery"
                class="text-body-2" 
                fixed-header
                density="compact"
              >                

             <template #header.Fecha="{ column }">
                <div class = "header-columna" style="text-align:center; white-space:normal;">
                    Fecha<br>Documento
                </div>
              </template>
              <template #item.Fecha="{ item }">
                <div class="_fila" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.Fecha }}
                </div>
              </template>

              <template #header.NroDocumento="{ column }">
                  <div style="text-align:center; white-space:normal;">
                    Número<br>Factura
                  </div>
              </template>
              <template #item.NroDocumento="{ item }">
                  <div class="_fila" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.NroDocumento }}
                  </div>
              </template>

              <template #header.Prefijo="{ column }">
                  <div style="text-align:center; white-space:normal;">
                   Prefijo
                  </div>
              </template>
              <template #item.Prefijo="{ item }">
                  <div class="_fila" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.Prefijo }}
                  </div>
              </template>

              <template #header.TipoDocumento="{ column }">
                  <div style="text-align:center; white-space:normal;">
                   Tipo<br>Documento
                  </div>
              </template>
              <template #item.TipoDocumento="{ item }">
                  <div class="_fila" style="width: 10.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.TipoDocumento }}
                  </div>
              </template>

              <template #header.Nitemisor="{ column }">
                   <div style="text-align:center; white-space:normal;">
                      Nit/Cédula<br>Emisor
                   </div>             
              </template>
              <template #item.NitEmisor="{ item }">
                  <div class="_fila" style="width: 6.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.NitEmisor }}
                  </div>
              </template>

              <template #header.Emisor="{ column }">
                  <div style="text-align:center; white-space:normal;">
                   Nombre del Emisor
                  </div>
              </template>
              <template #item.Emisor="{ item }">
                  <div class="_fila" style="width: 20.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    {{ item.Emisor }}
                  </div>
              </template>

              <template #header.ValorParcial>
                  <div style="text-align:center; white-space:normal;">
                   Valor Parcial
                  </div>
              </template>
              <template #item.ValorParcial="{ item }">
                <div class="_fila text-right" style="width: 7.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">           
                    {{ formatCurrency((item.ValorTotal || 0) - (item.ValorImptos || 0)) }} 
                </div>
              </template>

              <template #header.ValorImptos>
                  <div style="text-align:center; white-space:normal;">
                   Valor Impuestos
                  </div>
              </template>
              <template #item.ValorImptos="{ item }">
                  <div class="_fila text-right" style="width: 7.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">                   
                    {{ formatCurrency(item.ValorImptos) }} 
                  </div>
              </template>

              <template #header.ValorTotal>
                  <div style="text-align:center; white-space:normal;">
                   Valor Total
                  </div>
              </template>
              <template #item.ValorTotal="{ item }">
                  <div class="_fila text-right" style="width: 7.0em; white-space: normal; word-wrap: break-word; line-height: 1.2;">                    
                    {{ formatCurrency(item.ValorTotal) }} 
                  </div>
              </template>

              <template #header.actions>
                  <div style="text-align:center; white-space:normal;">
                   Acciones
                  </div>
              </template>

                
              <template #item.actions="{ item }">
                 <div style="display: flex; align-items: center; gap: 4px;">
                    <IconBtn>
                      <VIcon icon="tabler-file-type-xml" color="primary" @click="" />
                    </IconBtn>
                    <IconBtn>
                      <VIcon icon="tabler-file-type-pdf" color="error" @click="" />
                    </IconBtn>   
                    <IconBtn>
                      <VIcon icon="tabler-mail" color="warning" @click="abrirDialogoEmail(item)" />
                    </IconBtn>
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
 </template>  

 <style lang="css">

  .v-data-table .v-data-table__th,
  .v-data-table .v-data-table__td {
  white-space: normal !important;
    word-wrap: break-word !important;
    }

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
  }  */

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

    

</style>
