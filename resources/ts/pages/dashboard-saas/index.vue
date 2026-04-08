<script setup lang="ts">
import axios from 'axios'
import { onMounted, ref } from 'vue'
import CrmOrderBarChart from './CrmOrderBarChart.vue'
import CrmRevenueGrowth from './CrmRevenueGrowth.vue'
import CrmSalesAreaCharts from './CrmSalesAreaCharts.vue'


const hoy     = new Date().toISOString().split('T')[0]

const año        = new Date().getFullYear()
const fechadesde = `${año}-01-01`
const fechahasta = `${año}-12-31`
const token = localStorage.getItem('auth_token')

const totalventas  = ref(0)
const totalcompras = ref(0)


const formatCurrency = (value: number | string, decimals: number = 0): string => 
{
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
          
    console.log('num:', num, '| result:', num.toLocaleString('en-US', 
    { 
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }))

    return num.toLocaleString('en-US', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,})
}


const loading = ref(false)

const datafechas = ref({
      desdefecha: fechadesde,
      hastafecha: fechahasta,
    })

const simpleStatisticsDemoCards = ref ([
    {
      icon: 'tabler-currency-dollar',
      color: 'error',
      title: 'Total Ventas',
      subTitle: 'Last week',
      stat: '1.28k',
      change: '-12.2%',
    },
    {
      icon: 'tabler-currency-dollar',
      color: 'success',
      title: 'Total Compras',
      subTitle: 'Last week',
      stat: '$4,673',
      change: '+25.2%',
    },
  ])

  // Define la interfaz
interface Compra {
  total_documentos: number,
  // agrega aquí los demás campos que devuelve la API
}

interface Venta {
  total_documentos: number
  // agrega aquí los demás campos que devuelve la API
}

interface Company 
{
  date_to: string  
  date_from:string
}

const recordData = ref({
  status: '',
  message: '',
  ventas: [] as Venta[],
  compras: [] as Compra[],
  company: [] as Company[],
})

const informacion_compañia = ref(
  {
     diasfaltantes: 0,
     fechadesde:'',
     fechahasta:'',
     porcentaje:0
  }
)
const consolidarInformacion = async () =>
    {
      //console.log("Id Company:", localStorage.getItem('company_id'))
      loading.value = true
      try {
           //onsole.log("Generando Consulta con Fechas:", datafechas.value.desdefecha, datafechas.value.hastafecha, "Page:", page.value, "Items/Page:", itemsPerPage.value)
           //console.log("Voy aquí en consolidad información :",token)
           const { data } = await axios.post('/api/dian/consolidar-info', {
                  url_token    : localStorage.getItem('auth_token'),
                  company_id   : localStorage.getItem('company_id'),
                  fechadesde   : datafechas.value.desdefecha,
                  fechahasta   : datafechas.value.hastafecha,          
             }, 
             {
               headers: {Authorization : `Bearer ${token}`,'Content-Type': 'application/json'},
             })
            recordData.value = data;
            console.log("Respuesta API:", data)  
            console.log("Respuesta APIVentas :",data.ventas[0].total_documentos)
            simpleStatisticsDemoCards.value[0].stat = formatCurrency(data.ventas[0].gran_total)   // ajusta según tu JSON
            simpleStatisticsDemoCards.value[0].subTitle = "Año:" + año                     // ajusta según tu JSON
            simpleStatisticsDemoCards.value[0].change = formatCurrency(data.ventas[0].total_iva)   // ajusta según tu JSON

            simpleStatisticsDemoCards.value[1].stat = formatCurrency(data.compras[0].gran_total)   // ajusta según tu JSON  
            simpleStatisticsDemoCards.value[1].subTitle = "Año:" + año                       // ajusta según tu JSON
            simpleStatisticsDemoCards.value[1].change = formatCurrency(data.compras[0].total_iva)   // ajusta según tu JSON  

            const fechaFin   = new Date(recordData.value.company[0].date_to)
            //const fechaFin   = new Date()
            const fechaHoy   = new Date()
            const diferencia = Number(Math.ceil((fechaFin.getTime() - fechaHoy.getTime()) / (1000 * 60 * 60 * 24)))

            informacion_compañia.value.diasfaltantes = diferencia
            informacion_compañia.value.porcentaje    = Number(formatCurrency(diferencia * 100 / 365,2))
            informacion_compañia.value.fechadesde    = recordData.value.company[0].date_from
            informacion_compañia.value.fechahasta    = recordData.value.company[0].date_to

            //console.log("Soy Porcentaje:", informacion_compañia.value.porcentaje)  

          } catch (error) {
            console.error(error)
          } finally {
            loading.value = false
          }
    }

    onMounted(() => {consolidarInformacion()})
    
</script>

<template>
  <VRow class="match-height">
    <VCol cols="12" md="4" sm="6" lg="2">    
      <CrmOrderBarChart
      :total="recordData.compras[0]?.total_documentos ?? 0"
      :año="año" />
    </VCol>

    <VCol cols="12" md="4" m="6" lg="2">
      <CrmSalesAreaCharts
        :total="recordData.ventas[0]?.total_documentos ?? 0"
        :año="año"
      />
    </VCol>

    <VCol v-for="demo in simpleStatisticsDemoCards" :key="demo.title" cols="12" sm="6" md="4" lg="2">
      <VCard>
        <VCardText>
          <VAvatar :color="demo.color"  variant="tonal"  rounded size="44">
            <VIcon :icon="demo.icon" size="28" />
          </VAvatar>
          <h5 class="text-h5 mt-3">  {{ demo.title }}  </h5>
            <p class="my-1"> {{ demo.subTitle }} </p>
            <p class="mb-3 text-high-emphasis"> {{ demo.stat }} </p>
          <VChip :color="demo.color"  label size="small"  > {{ demo.change }} </VChip>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Revenue Growth -->
    <VCol cols="12" md="8" lg="4">
      <CrmRevenueGrowth
      :diasfaltantes  ="informacion_compañia.diasfaltantes"
      :porcentaje     ="informacion_compañia.porcentaje"
      :fechadesde     ="informacion_compañia.fechadesde"
      :fechahasta     ="informacion_compañia.fechahasta" />
    </VCol>

    <!-- 👉 Earning Reports -->
    <!-- <VCol cols="12" md="12">
      <CrmEarningReportsYearlyOverview />
    </VCol> -->

    <!-- 👉 Sales -->
    <!-- <VCol cols="12" md="4">
      <CrmAnalyticsSales />
    </VCol> -->

    <!-- 👉 Browser States -->
    <!-- <VCol cols="12" md="4">        
      <CrmSalesByCountries />
    </VCol> -->

    <!-- 👉 Project Status -->
    <!-- <VCol cols="12" md="4">
      <CrmProjectStatus />
    </VCol> -->

    <!-- 👉 Active Project -->
    <!-- <VCol cols="12"  md="4">
      <CrmActiveProject />
    </VCol> -->

    <!-- 👉 Recent Transactions -->
    <!-- <VCol cols="12" md="6">  
      <CrmRecentTransactions />
    </VCol>

    <VCol cols="12" md="6">
      <CrmActivityTimeline />
    </VCol> -->
  </VRow>
</template>
