import { createApp} from 'vue'
import axios from 'axios' // 👈 Falta esta línea para usar axios

// Styles
import '@core-scss/template/index.scss'
import '@styles/styles.scss'

import App from '@/App.vue'
import { registerPlugins } from '@core/utils/plugins'

// Styles
import '@core-scss/template/index.scss'
import '@styles/styles.scss'

// Create vue app
const app = createApp(App)

const token = localStorage.getItem('auth_token')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

// Register plugins
registerPlugins(app)

declare global {
  interface Window {
    company_user: string | null
  }
}



// Mount vue app
app.mount('#app')
