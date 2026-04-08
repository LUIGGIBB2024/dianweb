import { setupLayouts } from 'virtual:generated-layouts'
import type { App } from 'vue'

import type { RouteRecordRaw } from 'vue-router/auto'

import { createRouter, createWebHistory } from 'vue-router/auto'

import { useRouter } from 'vue-router'

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

setTimeout(() => {
  router.getRoutes().forEach(r => console.log('📍 Ruta:', r.name, r.path))
}, 1000)

// router.beforeEach((to, from, next) => {

//    if (to.path === '/') {
//     next({ name: 'login' })
//   } else {
//     next()
//   }
//   const token = localStorage.getItem('auth_token')

//   // Si no hay sesión y no estás yendo al login → redirige a login
//   if (!token && to.name !== 'login') {
//     next({ name: 'login' })
//   } 
//   // Si ya hay sesión y tratas de ir al login → manda al dashboard
//   else if (token && to.name === 'login') {
//     next({ name: 'dashboards-crm' }) // o la ruta que quieras como home
//   } 
//   else {
//     next()
//   }
// })

  router.beforeEach((to, from, next) => {

    console.log('🔁 Guard:', to.name, to.path)
    console.log('🔁 Guard:', to.name, '| path:', to.path, '| token:', localStorage.getItem('auth_token'))
    const token = localStorage.getItem('auth_token')
    const tipoUsuario = localStorage.getItem('tipo_de_usuario')

    if (to.path === '/') {
      next({ name: 'login' })
    }
    else if (!token && to.name !== 'login') {
      next({ name: 'login' })
    }
    else if (token && to.name === 'login') {
      // Si ya tiene sesión y trata de ir al login → manda a su dashboard
      if (tipoUsuario === 'SuperAdmin') {
        next({ name: 'dashboards-crm' })
      } else if (tipoUsuario === 'Cliente SaaS') {
        next({ name: 'dashboard-saas' })
      } else {
        next({ name: 'dashboards-crm' })
      }
    }
    else {
      next()
    }
  })

   
export default function (app: App) {
  app.use(router)
}
