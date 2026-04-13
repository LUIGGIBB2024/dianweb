import { setupLayouts } from 'virtual:generated-layouts'
import type { App } from 'vue'
import type { RouteRecordRaw } from 'vue-router'
import { createRouter, createWebHistory } from 'vue-router'

// Rutas autogeneradas por unplugin-vue-router
import { routes as autoRoutes } from 'vue-router/auto-routes'

function recursiveLayouts(route: RouteRecordRaw): RouteRecordRaw {
  if (route.children) {
    for (let i = 0; i < route.children.length; i++)
      route.children[i] = recursiveLayouts(route.children[i])

    return route
  }

  return setupLayouts([route])[0]
}

// ✅ Procesar layouts antes de pasarlos al router
// const routes = [...autoRoutes].map(route => recursiveLayouts(route))

// ✅ Esto aplica layouts solo a rutas que no los tienen aún
const routes = setupLayouts([...autoRoutes])

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to) {
    if (to.hash)
      return { el: to.hash, behavior: 'smooth', top: 60 }

    return { top: 0 }
  },
  routes, // ✅ Sin extendRoutes
})

setTimeout(() => {
  router.getRoutes().forEach(r => console.log('📍 Ruta:', r.name, r.path))
}, 1000)

router.beforeEach((to, from, next) => {
  console.log('🔁 Guard | path:', to.path, '| token:', localStorage.getItem('auth_token'))

  const token = localStorage.getItem('auth_token')
  const tipoUsuario = localStorage.getItem('tipo_de_usuario')

  if (to.path === '/') {
    next('/login')                    // ✅ usar path, no name
  }
  else if (!token && to.path !== '/login') {
    next('/login')
  }
  else if (token && to.path === '/login') {
    if (tipoUsuario === 'SuperAdmin')
      next('/dashboard')              // ✅ nombre real de la ruta
    else if (tipoUsuario === 'Cliente SaaS')
      next('/dashboard-saas')        // ✅ nombre real de la ruta
    else
      next('/dashboard')
  }
  else {
    next()
  }
})

export default function (app: App) {
  app.use(router)
}
