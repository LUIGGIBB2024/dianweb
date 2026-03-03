import { createFetch } from '@vueuse/core'
import { destr } from 'destr'

export const useApi = createFetch({
  baseUrl: import.meta.env.VITE_API_BASE_URL || '/api',
  fetchOptions: {
    headers: {
      Accept: 'application/json',
    },
  },
  options: {
    refetch: true,
    async beforeFetch({ options }) {
      // Lee primero de la cookie y, si no hay, del localStorage
      const cookieToken = useCookie('accessToken').value
      const localToken = localStorage.getItem('token')
      const accessToken = cookieToken || localToken

      if (accessToken) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${accessToken}`,
        }
      }

      return { options }
    },
    afterFetch(ctx) {
      const { data, response } = ctx
      let parsedData = null

      try {
        parsedData = destr(data)
      } catch (error) {
        console.error(error)
      }

      return { data: parsedData, response }
    },
  },
})

// import axios from 'axios'
// import { ref } from 'vue'

// export function useApi<T = any>(url: string, method: string = 'GET', payload: any = null) {
//   const data = ref<T | null>(null)
//   const error = ref<any>(null)
//   const loading = ref<boolean>(false)

//   const execute = async () => {
//     loading.value = true
//     try {
//       let response
//       if (method.toUpperCase() === 'POST') response = await axios.post(url, payload)
//       else if (method.toUpperCase() === 'DELETE') response = await axios.delete(url)
//       else if (method.toUpperCase() === 'PUT') response = await axios.put(url, payload)
//       else response = await axios.get(url)

//       data.value = response.data
//     } catch (err) {
//       console.error('❌ useApi error:', err)
//       error.value = err
//     } finally {
//       loading.value = false
//     }
//   }

//   // Ejecutar una vez al inicializar
//   execute()

//   return { data, error, loading, execute }
// }
