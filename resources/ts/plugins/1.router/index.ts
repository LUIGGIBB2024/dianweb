import { setupLayouts } from 'virtual:generated-layouts'
import type { App } from 'vue'

import type { RouteRecordRaw } from 'vue-router/auto'

import { createRouter, createWebHistory } from 'vue-router/auto'

function recursiveLayouts(route: RouteRecordRaw): RouteRecordRaw {
  if (route.children) {
    for (let i = 0; i < route.children.length; i++)
      route.children[i] = recursiveLayouts(route.children[i])

    return route
  }

  return setupLayouts([route])[0]
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to) {
    if (to.hash)
      return { el: to.hash, behavior: 'smooth', top: 60 }

    return { top: 0 }
  },
  extendRoutes: pages => [
    ...[...pages].map(route => recursiveLayouts(route)),
  ],
})

router.beforeEach((to, from, next) => {

   if (to.path === '/') {
    next({ name: 'login' })
  } else {
    next()
  }
  const token = localStorage.getItem('auth_token')

  // Si no hay sesión y no estás yendo al login → redirige a login
  if (!token && to.name !== 'login') {
    next({ name: 'login' })
  } 
  // Si ya hay sesión y tratas de ir al login → manda al dashboard
  else if (token && to.name === 'login') {
    next({ name: 'dashboards-crm' }) // o la ruta que quieras como home
  } 
  else {
    next()
  }
})

export { router }

export default function (app: App) {
  app.use(router)
}
