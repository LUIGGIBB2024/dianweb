<script setup lang="ts">
import axios from 'axios'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import { computed, ref } from 'vue'
import { VRow } from 'vuetify/components'

const snackbar = ref(false)

const yaBusco = ref(false) // Nueva variable de control

const formatCurrency = (value: number | string) => {
  const num = Number(value) || 0

  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

interface ProgresoFacturas {
  id: number
  numero: string
  prefix: string
  state_evento: 'pendiente' | 'procesando' | 'completado' | 'fallido'
  evento: string | null
  evento1: string | null
  evento2: string | null
  evento3: string | null
}

// ============================================
// VARIABLES DE ESTADO
// ============================================
// ============================================
// VARIABLES
// ============================================
const procesando = ref(false)
const progresoDialogo = ref(false)
const progresoFacturas = ref<ProgresoFactura[]>([])
let pollingInterval: ReturnType<typeof setInterval> | null = null

// ============================================
// COMPUTED
// ============================================
// const facturasSeleccionadas = computed(() =>
//   facturas.value.filter(f => f.selected === 1)
// )

const facturasCompletadas = computed(() =>
  progresoFacturas.value.filter(
    f => f.state_evento === 'completado' || f.state_evento === 'fallido',
  ).length,
)

const porcentajeProgreso = computed(() =>
  progresoFacturas.value.length
    ? (facturasCompletadas.value / progresoFacturas.value.length) * 100
    : 0,
)

// ============================================
// HELPERS
// ============================================
const iconEvento = (valor: string | null) => {
  if (valor === null)
    return 'tabler-clock'
  if (valor === 'EXITOSO')
    return 'tabler-circle-check'

  return 'tabler-circle-x'
}

const colorEvento = (valor: string | null) => {
  if (valor === null)
    return 'grey'
  if (valor === 'EXITOSO')
    return 'success'

  return 'error'
}

const colorEstado = (estado: string) => {
  const colores: Record<string, string> = {
    pendiente: 'warning',
    procesando: 'info',
    completado: 'success',
    fallido: 'error',
  }

  return colores[estado] ?? 'grey'
}

const textoEstado = (estado: string) => {
  const textos: Record<string, string> = {
    pendiente: 'Pendiente',
    procesando: 'Procesando',
    completado: 'Completado',
    fallido: 'Fallido',
  }

  return textos[estado] ?? estado
}

// ============================================
// POLLING
// ============================================
const iniciarPolling = (lista: any[]) => {
  if (pollingInterval)
    clearInterval(pollingInterval)

  pollingInterval = setInterval(async () => {
    try {
      const { data } = await axios.post('/api/dian/estado-eventos', {
        lista,
      }, {
        headers: { Authorization: `Bearer ${token}` },
      })

      // Actualizar estado de cada factura en tiempo real
      progresoFacturas.value = progresoFacturas.value.map(pf => {
        const actualizado = data.facturas.find((f: any) => f.id === pf.id)

        return actualizado ? { ...pf, ...actualizado } : pf
      })

      // Detener polling cuando todas terminaron
      const todasTerminadas = progresoFacturas.value.every(
        f => f.state_evento === 'completado' || f.state_evento === 'fallido',
      )

      if (todasTerminadas) {
        clearInterval(pollingInterval!)
        pollingInterval = null
        procesando.value = false
      }
    }
    catch (error) {
      console.error('Error en polling:', error)
    }
  }, 5000) // consulta cada 5 segundos
}

const hoy = new Date().toISOString().split('T')[0]
const loading = ref(false)

const datafechas = ref({
  desdefecha: hoy,
  hastafecha: hoy,
})

const searchQuery = ref('')
const selectedRows = ref([])

const invoiceData = ref({
  status: '',
  message: '',
  TotalDocumentos: 0,
  TotalValor: 0,
  TotalIva: 0,
  data: [],
  page: 1,
  per_page: 13,
})

// 🔹 Variables del DataTable
const itemsPerPage = ref(13)
const page = ref(1)
const token = localStorage.getItem('auth_token')

const generarConsulta = async () => {
  loading.value = true
  try {
    // onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
    const { data } = await axios.post('/api/dian/recepcion-facturas', {
      url_token: localStorage.getItem('auth_token'),
      company_id: localStorage.getItem('company_id'),
      fechadesde: datafechas.value.desdefecha,
      fechahasta: datafechas.value.hastafecha,
      page: page.value,
      per_page: itemsPerPage.value,
    },
    {
      headers: { Authorization: `Bearer ${token}` },
    },
    )

    invoiceData.value = data
    console.log('Respuesta API:', data)
    console.log('Respuesta InvoiceData:', invoiceData.value)
    yaBusco.value = true // Marcar que ya se realizó una búsqueda

    if (invoiceData.value.TotalDocumentos === 0 && yaBusco.value)
      snackbar.value = true
  }
  catch (error) {
    console.error(error)
  }
  finally {
    loading.value = false
  }
}

// onMounted(() => generarConsulta())

const facturas = computed(() => invoiceData.value.data ?? [])
const currentPage = computed(() => invoiceData.value.page ?? page.value)
const perPage = computed(() => invoiceData.value.per_page ?? itemsPerPage.value)

console.log('Facturas Computed:', facturas.value)

// const totalInvoices = computed(() => invoiceData.value.TotalValor ?? 0)
// const totalIva = computed(() => invoiceData.value.TotalIva ?? 0)
const totalregistros = computed(() => invoiceData.value.TotalDocumentos ?? 0)

// ============================================
// COMPUTED: Facturas seleccionadas
// ============================================
const facturasSeleccionadas = computed(() =>
  facturas.value.filter(item => item.selected === 1),
)

// ============================================
// FUNCIÓN PRINCIPAL
// ============================================
const procesarEventos = async () => {
  progresoFacturas.value = facturasSeleccionadas.value.map(f => ({
    id: f.id,
    numero: f.number,
    prefix: f.prefix,
    state_evento: 'pendiente',
    evento: null,
    evento1: null,
    evento2: null,
    evento3: null,
  }))

  progresoDialogo.value = true
  procesando.value = true

  try {
    // 🚀 Enviar al backend — los Jobs se encargan del resto
    const { data } = await axios.post('/api/dian/procesar-eventos', {
      url_token: localStorage.getItem('company_token'),
      company_token: localStorage.getItem('company_token'),
      company_id: localStorage.getItem('company_id'),
      lista: facturasSeleccionadas.value,
    }, {
      headers: { Authorization: `Bearer ${token}` },
    })

    console.log('Jobs despachados:', data)

    // Iniciar polling para actualizar estado en tiempo real
    iniciarPolling(facturasSeleccionadas.value)
  }
  catch (error) {
    console.error('Error al procesar eventos:', error)
    procesando.value = false
  }
}

// Limpiar polling al desmontar
onUnmounted(() => {
  if (pollingInterval)
    clearInterval(pollingInterval)
})

// ✅ Computed de items filtrados
const facturasFiltradas = computed(() => {
  if (!searchQuery.value || !facturas.value?.length)
    return facturas.value ?? []
  const q = searchQuery.value.toLowerCase()

  return facturas.value.filter(item =>
    Object.values(item).some(val =>
      String(val ?? '').toLowerCase().includes(q),
    ),
  )
})

// ✅ Totales que reaccionan al filtro
const totalInvoices = computed(() =>
  facturasFiltradas.value.reduce((acc, item) => acc + (Number(item.total_purchase) || 0), 0),
)

const totalIva = computed(() =>
  facturasFiltradas.value.reduce((acc, item) => acc + (Number(item.vatvalue) || 0), 0),
)

const headers = [
  { title: 'id', key: 'id', sortable: true, width: '4px' },
  { title: 'Fecha Documento', key: 'date_issue', sortable: true },
  { title: 'Número de Factura', key: 'number', sortable: true, width: '4px' },
  { title: 'Prefijo', key: 'prefix', sortable: true },
  { title: 'Tipo Documento', key: 'document_name', sortable: true, width: '15%' },
  { title: 'Nit/Cédula', key: 'supplier', sortable: true, width: '10%' },
  { title: 'Nombre del Proveedor', key: 'supplier_name', sortable: true, width: '40%' },
  { title: 'Valor Documento', key: 'total_purchase', sortable: true, align: 'end' },
  { title: 'Evento1', key: 'evento1', sortable: true, width: '12%' },
  { title: 'Evento2', key: 'evento2', sortable: true, width: '12%' },
  { title: 'Evento3', key: 'evento3', sortable: true, width: '12%' },
  { title: 'Acciones', key: 'actions', sortable: false, width: '15%', align: 'center' },
]

const cellProps = () => ({
  style: {
    fontSize: '0.78rem',
    color: '#0a0a0a',
    fontFamily: 'Roboto, sans-serif',
    fontWeight: '400',
  },
})

const headerProps = () => ({
  style: {
    fontSize: '0.78rem',
    color: '#ffffff',
    fontFamily: 'Roboto, sans-serif',
    fontWeight: '600',
  },
})

const cerrarDialogo = () => {
  // Actualizar los datos de la tabla con los resultados del proceso
  progresoFacturas.value.forEach(pf => {
    const index = invoiceData.value.data?.findIndex((f: any) => f.id === pf.id)
    if (index !== -1 && index !== undefined) {
      invoiceData.value.data[index] = {
        ...invoiceData.value.data[index],
        evento1: pf.evento1,
        evento2: pf.evento2,
        evento3: pf.evento3,
        state_evento: pf.state_evento,
        evento: pf.evento,
      }
    }
  })

  progresoDialogo.value = false
}
</script>

<template>
  <!-- <VCard class="mb-2" style="height: 13vh !important;"">  -->
  <VCard class="mb-2 py-3 px-4">
    <VRow class="align-center">
      <VCol
        cols="12"
        md="4"
        class="d-flex align-center flex-column"
      >
        <h3 class="text-primary mb-2">
          Recepción de Facturas
        </h3>
        <VCardText class="d-flex align-center flex-wrap gap-4 pa-0">
          <VTextField
            v-model="searchQuery"
            placeholder="Buscar..."
            density="compact"
            prepend-inner-icon="tabler-search"
            variant="outlined"
            clearable
            hide-details
            style="inline-size: 20em;max-inline-size: 300px;"
          />
        </VCardText>
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
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

      <VCol
        cols="12"
        md="2"
      >
        <AppDateTimePicker
          v-model="datafechas.hastafecha"
          label="Hasta Fecha :"
          placeholder="Seleccionar Fecha"
          class="text-center-input"
          prepend-inner-icon="tabler-calendar"
          :config="{ locale: Spanish, dateFormat: 'Y-m-d' }"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
        class="d-flex align-center justify-start mt-md-5 mt-2"
      >
        <VBtn
          rounded="pill"
          color="primary"
          variant="flat"
          block
          @click="generarConsulta"
        >
          Generar Consulta
        </VBtn>
      </VCol>

      <VCol
        cols="12"
        md="2"
        class="d-flex align-center justify-end mt-md-5 mt-2"
      >
        <VBtn
          rounded="pill"
          color="success"
          variant="flat"
          block
          :disabled="!facturas?.length"
          @click="procesarEventos"
        >
          <VIcon
            start
            icon="tabler-topology-full-hierarchy"
            size="20"
            color="#FF5733"
          />
          Procesar Eventos
        </VBtn>
      </VCol>

      <!-- Diálogo de progreso -->
      <VDialog
        v-model="progresoDialogo"
        max-width="650"
        persistent
      >
        <VCard>
          <VCardTitle class="pa-4 d-flex align-center">
            <VIcon
              icon="tabler-topology-full-hierarchy"
              class="mr-2"
              color="success"
            />
            Procesando Eventos DIAN
            <VSpacer />
            <VChip
              color="info"
              size="small"
            >
              {{ facturasCompletadas }} / {{ progresoFacturas.length }} completadas
            </VChip>
          </VCardTitle>

          <VDivider />

          <VCardText class="pa-4">
            <!-- Barra de progreso -->
            <VProgressLinear
              :model-value="porcentajeProgreso"
              color="success"
              rounded
              height="8"
              class="mb-4"
            />

            <!-- Tabla de facturas -->
            <VTable density="compact">
              <thead>
                <tr>
                  <th>Factura</th>
                  <th class="text-center">
                    Evento 1
                  </th>
                  <th class="text-center">
                    Evento 2
                  </th>
                  <th class="text-center">
                    Evento 3
                  </th>
                  <th class="text-center">
                    Estado
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="f in progresoFacturas"
                  :key="f.id"
                >
                  <td>{{ f.prefix }}{{ f.numero }}</td>
                  <td class="text-center">
                    <VIcon
                      :icon="iconEvento(f.evento1)"
                      :color="colorEvento(f.evento1)"
                      size="20"
                    />
                  </td>
                  <td class="text-center">
                    <VIcon
                      :icon="iconEvento(f.evento2)"
                      :color="colorEvento(f.evento2)"
                      size="20"
                    />
                  </td>
                  <td class="text-center">
                    <VIcon
                      :icon="iconEvento(f.evento3)"
                      :color="colorEvento(f.evento3)"
                      size="20"
                    />
                  </td>
                  <td class="text-center">
                    <VChip
                      :color="colorEstado(f.state_evento)"
                      size="small"
                      label
                    >
                      {{ textoEstado(f.state_evento) }}
                    </VChip>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
          <VDivider />

          <VCardActions class="pa-4">
            <VSpacer />
            <VBtn
              :disabled="procesando"
              color="success"
              variant="flat"
              rounded="pill"
              @click="cerrarDialogo"
            >
              <VIcon start icon="tabler-check" />
              {{ procesando ? 'Procesando...' : 'Cerrar' }}
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>
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
        :cell-props="cellProps"
        :header-props="headerProps"
        class="text-body-2 tabla-facturas"

        fixed-header
        density="compact"
      >
        <template #header.id="{ column }">
          <div class="th-center">
            #Id
          </div>
        </template>
        <template #item.id="{ item }">
          <div class="th-center">
            {{ item.id }}
          </div>
        </template>

        <template #header.date_issue="{ column }">
          <div class="th-center">
            Fecha<br>Documento
          </div>
        </template>
        <template #item.date_issue="{ item }">
          <div class="td-center">
            {{ item.date_issue }}
          </div>
        </template>

        <template #header.number="{ column }">
          <div class="th-center">
            Número<br>Factura
          </div>
        </template>
        <template #item.number="{ item }">
          <div class="td-right">
            {{ item.number }}
          </div>
        </template>

        <template #header.prefix="{ column }">
          <div class="th-center">
            Prefijo
          </div>
        </template>
        <template #item.prefix="{ item }">
          <div class="td-left">
            {{ item.prefix }}
          </div>
        </template>

        <template #header.document_name="{ column }">
          <div class="th-center">
            Tipo<br>Documento
          </div>
        </template>

        <template #item.document_name="{ item }">
          <div class="td-left">
            {{ item.document_name }}
          </div>
        </template>

        <template #header.supplier="{ column }">
          <div class="th-center">
            Nit/Cédula<br>Proveedor
          </div>
        </template>

        <template #item.supplier="{ item }">
          <div class="td-left">
            {{ item.supplier }}
          </div>
        </template>

        <template #header.supplier_name="{ column }">
          <div class="th-center">
            Nombre del Proveedor
          </div>
        </template>

        <template #item.supplier_name="{ item }">
          <div class="td-left">
            {{ item.supplier_name }}
          </div>
        </template>

        <template #header.total_purchase="{ column }">
          <div class="th-center">
            Valor Total
          </div>
        </template>

        <template #item.total_purchase="{ item }">
          <div class="td-right">
            {{ formatCurrency((item.total_purchase)) }}
          </div>
        </template>

        <template #header.evento1="{ column }">
          <div class="th-center">
            Evento 1
          </div>
        </template>

        <template #header.evento2="{ column }">
          <div class="th-center">
            Evento 2
          </div>
        </template>

        <template #header.evento3="{ column }">
          <div class="th-center">
            Evento 3
          </div>
        </template>

        <template #header.actions>
          <div class="th-center">
            Acciones
          </div>
        </template>

        <template #item.actions="{ item }">
          <div class="td-center">
            <VCheckbox
              v-model="item.selected"
              density="compact"
              color="primary"
              :true-value="1"
              :false-value="0"
            />
          </div>
        </template>

        <!-- Slot Bottom Personalizado -->
        <template #bottom>
          <VDivider />
          <VRow class="mt-2 mx-0 pb-2 align-center">
            <VCol
              cols="12"
              md="4"
            >
              <div class="text-caption text-medium-emphasis ps-4">
                Mostrando
                <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                <strong>{{ Math.min(currentPage * perPage, totalregistros) }}</strong>
                de <strong>{{ totalregistros }}</strong> registros
              </div>
            </VCol>
            <VCol
              cols="12"
              md="4"
              class="d-flex justify-center pagination-wrapper"
            >
              <VPagination
                v-model="page"
                :length="Math.ceil(totalregistros / perPage)"
                rounded="circle"
                size="large"
                :total-visible="5"
              />
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <div class="text-caption text-medium-emphasis ps-4 text-end">
                Total Documentos $:
                <strong class="text-primary">{{ formatCurrency(totalInvoices) }}</strong>
                <!--
                  <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                  <strong>{{ Math.min(currentPage * perPage, totalInvoices) }}</strong>
                  de <strong>{{ totalInvoices }}</strong> registros
                -->
              </div>
              <div class="text-caption text-medium-emphasis ps-4 text-end">
                Total Iva $:
                <strong class="text-error">{{ formatCurrency(totalIva) }}</strong>
                <!--
                  <strong>{{ (currentPage - 1) * perPage + 1 }}</strong>–
                  <strong>{{ Math.min(currentPage * perPage, totalInvoices) }}</strong>
                  de <strong>{{ totalInvoices }}</strong> registros
                -->
              </div>
            </VCol>
          </VRow>
        </template>
      </VDataTable>
    </VCard>
  </section>

  <section v-else-if="yaBusco && (!facturas || !facturas.length)">
    <VCard>
      <VCardTitle class="pa-4">
        No se encontraron registros para el periodo seleccionado
      </VCardTitle>
    </VCard>
  </section>

  <VOverlay
    :model-value="loading"
    persistent
    class="align-center justify-center"
  >
    <VProgressCircular
      indeterminate
      size="64"
      color="primary"
    />
  </VOverlay>

  <VSnackbar
    v-model="snackbar"
    color="error"
    timeout="4000"
    location="center"
    style="font-size: 5em !important;"
  >
    <VIcon
      icon="tabler-alert-circle"
      class="me-2"
    />
    <span style="font-size: 1.2rem; font-weight: 500;">
      No se encontraron registros, intente nuevamente por favor
    </span>
    <template #actions>
      <VBtn
        variant="text"
        @click="snackbar = false"
      >
        Cerrar
      </VBtn>
    </template>
  </VSnackbar>
</template>

 <style lang="css">
.pagination-wrapper {
  .v-pagination__first,
  .v-pagination__item,
  .v-pagination__next,
  .v-pagination__prev,
  .v-pagination__last {
    .v-btn {
      background-color: rgb(253, 134, 227) !important;

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
  cursor: pointer;
  text-align: center !important;
}

/* .v-data-table :deep(.v-data-table__thead th) {
    background-color: rgb(243, 16, 175) !important;
    color: white !important;
    text-transform: none !important;
  } */

.v-data-table__thead th {
  background-color: rgb(247, 58, 206) !important;
  color: white !important;
}

thead th {
  background-color: rgb(247, 58, 206) !important;
  color: white !important;
}

.v-data-table thead th {
  text-transform: capitalize !important;
}

._fila {
  color: black;
  font-size: 11px !important;
  inline-size: 6em;
  line-height: 1.2;
  white-space: normal;
  word-wrap: break-word;
}

.modal-title {
  background-color: rgb(var(--v-theme-primary));
  border-start-end-radius: 6px;
  border-start-start-radius: 6px;
  color: white;
  font-size: 1.2rem;
  font-weight: 600;
  padding-block: 16px;
  padding-inline: 24px;
}

.custom-card {
  block-size: 250px !important; /* Ajusta a tu necesidad */
}

.dian-iframe {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
  block-size: 700px;
  inline-size: 100%;
}

.loader-overlay {
  position: absolute;
  z-index: 2;
  background: rgba(var(--v-theme-surface), 0.7);
  block-size: 100%;
  inline-size: 100%;
  inset-block-start: 0;
  inset-inline-start: 0;
}

.dian-iframe-full {
  border: none;
  background-color: #fff;
  block-size: 100%;
  inline-size: 100%;
}

.loader-overlay {
  position: absolute;
  z-index: 10;
  background: rgba(var(--v-theme-surface), 0.9);
  block-size: 100%;
  inline-size: 100%;
  inset-block-start: 0;
  inset-inline-start: 0;
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
  inline-size: 10em !important;
}

/* ─── TABLA FACTURAS ──────────────────────────────────────────────── */

/* Filas de datos */
.tabla-facturas :deep(tbody tr td) {
  color: #0a0a0a !important;
  font-family: Roboto, sans-serif !important;
  font-size: 0.9rem !important;
  font-weight: 300 !important;
  white-space: nowrap;
}

/* Headers */
.tabla-facturas :deep(thead tr th) {
  color: #fff !important;
  font-family: Roboto, sans-serif !important;
  font-size: 0.73rem !important;
  font-weight: 600 !important;
}

/* Columna cliente — única que puede wrappear */
.tabla-facturas :deep(tbody tr td) .col-cliente {
  max-inline-size: 260px;
  min-inline-size: 180px;
  white-space: normal;
  word-break: break-word;
}

.tabla-facturas :deep(tbody tr td) .col-tipo {
  min-inline-size: 140px;
  white-space: nowrap;
}

/* Responsive 1366px */
@media (max-width: 1366px) {
  .tabla-facturas :deep(tbody tr td) {
    font-size: 0.4rem !important;
  }
}

/* Apunta al div interno que Vuetify genera dentro de cada td */
.tabla-facturas :deep(.v-data-table__td-inner) {
  color: #0a0a0a !important;
  font-family: Roboto, sans-serif !important;
  font-size: 0.65rem !important;
}

.tabla-facturas :deep(.v-data-table__th-inner) {
  color: #fff !important;
  font-family: Roboto, sans-serif !important;
  font-size: 0.73rem !important;
  font-weight: 600 !important;
}
</style>
