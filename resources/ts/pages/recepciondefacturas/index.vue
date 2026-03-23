<script setup lang="ts">
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import fileDownload from 'js-file-download'
import { computed, onMounted, ref } from 'vue'
import { VRow } from 'vuetify/components'

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
        const token      = localStorage.getItem('auth_token')
        const userId  = localStorage.getItem('user_id')  
        const companyId  = localStorage.getItem('company_id')  // ← agrega esta línea

        console.log("Token en LoadDianPortal:", token)
        console.log("Company ID en LoadDianPortal:", companyId) 
        console.log("USe ID en LoadDianPortal:", userId) 

        // // 1. Registra solicitud en Laravel
        // try {
        //     await axios.post('/api/dian/solicitar-token',
        //     {
        //          token: token,
        //          company_id: companyId , // ← agrega esto
        //          user_id: userId  // ← agrega esto
        //     }, 
        //     {
        //         headers: { Authorization: `Bearer ${token}` }
        //     })
        // } catch (e: any) {
        //     mensajeError.value = e.response?.data?.error || 'Error al solicitar token'
        //     return
        // }

        // 2. Copia cédula al portapapeles
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
        const width  = 1200
        const height = 800
        const left   = (screen.width  - width)  / 2
        const top    = (screen.height - height) / 2

        dianWindows = window.open(
            'https://catalogo-vpfe.dian.gov.co/User/Login',
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

        const token      = localStorage.getItem('auth_token')
        const userId  = localStorage.getItem('user_id')  
        const companyId  = localStorage.getItem('company_id')  // ← agrega esta línea

        console.log("Token en LoadDianPortal:", token)
        console.log("Company ID en LoadDianPortal:", companyId) 
        console.log("USe ID en LoadDianPortal:", userId) 

        try {
            const { data } = await axios.post('/api/dian/solicitar-token', {
                token: token,
                company_id: companyId,
                user_id: userId
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

    const formatCurrency = (value: number | string) => {
        const num = Number(value) || 0
        return num.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
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
        data: [], total: 0, page: 1, per_page: 10, totaldctos: 0,
    })

    const showDialogEmail = ref(false)
    const editMode        = ref(false)
    const itemsPerPage    = ref(10)
    const page            = ref(1)
    const sortBy          = ref()
    const orderBy         = ref()

    const headers = [
        { title: '# Id',                    key: 'id',            width: '5%' },
        { title: 'Fecha Documento',          key: 'date_issue',    sortable: true },
        { title: 'Número de Documento',      key: 'number',        sortable: true, width: '6px' },
        { title: 'Prefijo',                  key: 'prefix',        sortable: true },
        { title: 'Tipo Documento',           key: 'document_name', sortable: true },
        { title: 'Nit/Cédula',              key: 'customer',      sortable: true },
        { title: 'Nombre del Cliente/Proveedor', key: 'client_name', sortable: true, width: '35%' },
        { title: 'Valor Documento',          key: 'sale',          sortable: true },
        { title: 'Acciones',                 key: 'actions',       sortable: false, width: '20px' },
    ]

    const updateOptions = async (options: any) => {
        page.value         = options.page
        itemsPerPage.value = options.itemsPerPage
        sortBy.value       = options.sortBy[0]?.key
        orderBy.value      = options.sortBy[0]?.order
        await generarConsulta()
    }

    const generarConsulta = async () => {
        loading.value = true
        try {
            const response = await axios.post('/api/scraping/dianf', {
                q            : searchQuery.value,
                itemsPerPage : itemsPerPage.value,
                page         : page.value,
                sortBy       : sortBy.value,
                orderBy      : orderBy.value,
            }, {
                headers: {
                    Authorization  : `Bearer ${token}`,
                    'Content-Type' : 'application/json',
                },
            })
            loading.value = false
        } catch (error) {
            console.error('Error al generar consulta:', error)
            loading.value = false
        }
    }

    onMounted(() => generarConsulta())

    const facturas      = computed(() => invoiceData.value.data ?? [])
    const currentPage   = computed(() => invoiceData.value.page ?? page.value)
    const perPage       = computed(() => invoiceData.value.per_page ?? itemsPerPage.value)
    const totalInvoices = computed(() => invoiceData.value.total ?? 0)
    const totaldctos    = computed(() => invoiceData.value.totaldctos ?? 0)

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
</script>

<template>
      <!-- <VCard class="mb-2" style="height: 13vh !important;"">  -->
        <VCard class="mb-2 py-3 px-4">
          <VRow class="align-center">
              <VCol cols="12" md="3" class="d-flex align-center flex-column">
                  <h3 class="text-primary mb-2">Recepción de Facturas 2026 - 110</h3>

                  <VBtn
                      color="primary"
                      variant="elevated"
                      prepend-icon="tabler-world-www"
                      :disabled="isLoading || isEsperando"
                      @click="loadDianPortal"
                  >
                      Generar Token DIAN
                  </VBtn>

                  <!-- Ventana abierta -->
                  <div v-if="isLoading" class="mt-2 text-center">
                      <VProgressCircular indeterminate size="20" color="primary" class="me-2" />
                      <span class="text-caption">Genera el token en la DIAN y cierra la ventana...</span>
                      <br>
                      <VBtn size="small" color="error" variant="text" class="mt-1" @click="cancelarDian">
                          Cancelar
                      </VBtn>
                  </div>

                  <!-- Ventana cerrada, esperando correo -->
                  <div v-if="isEsperando" class="mt-2 text-center">
                      <VProgressCircular indeterminate size="20" color="warning" class="me-2" />
                      <span class="text-caption text-warning">Esperando correo de la DIAN...</span>
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
                      ✅ Token recibido correctamente
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
                      Generar Consulta
                  </VBtn>
              </VCol>
          </VRow>
      </VCard>

      <VDialog
          v-model="showIframeDialog"
          fullscreen
          transition="dialog-bottom-transition"
        >
          <VCard>
            <VToolbar color="primary">
              <VBtn icon @click="closeIframe">
                <VIcon icon="tabler-x" color="white" />
              </VBtn>
              <VToolbarTitle class="text-white">Portal Catálogo DIAN - Facturación Electrónica</VToolbarTitle>
              <VSpacer />
              <VBtn 
                variant="tonal" 
                color="white" 
                href="https://catalogo-vpfe.dian.gov.co/User/Login" 
                target="_blank"
                prepend-icon="tabler-external-link"
              >
                Abrir en pestaña nueva
              </VBtn>
            </VToolbar>

            <VCardText class="pa-0 position-relative" style="height: calc(100vh - 64px);">
              <div v-if="isLoading" class="d-flex flex-column justify-center align-center loader-overlay">
                <VProgressCircular indeterminate size="64" color="primary" class="mb-4" />
                <p class="text-h6">Conectando con el servidor de la DIAN...</p>
              </div>

              <iframe 
                v-if="iframeSource"
                :src="iframeSource" 
                class="dian-iframe-full"
                sandbox="allow-forms allow-modals allow-popups allow-scripts allow-same-origin"
                @load="onIframeLoad"
              ></iframe>
            </VCardText>
          </VCard>
       </VDialog>


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
                    Número<br>de Documento
                  </div>
                </template>  

                <template #item.document_name="{ item }">
                  <div style="width: 130px; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    <!-- {{ item.document_name }} -->
                  </div>
                </template>

                <template #item.client_name="{ item }">
                  <div style="width:340px; white-space: normal; word-wrap: break-word; line-height: 1.2;">
                    <!-- {{ item.client_name}} -->
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
                         <!-- {{ formatCurrency(item.sale) }}  -->
                    </div>
                </template>

                <template #item.actions="{ item }">
                    <IconBtn>
                      <VIcon icon="tabler-file-type-xml" color="primary" @click="" />
                    </IconBtn>
                    <IconBtn>
                      <VIcon icon="tabler-file-type-pdf" color="error" @click="" />
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

</style>
