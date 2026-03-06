export interface NavItem {
  title: string
  to?: any
  icon?: { icon: string; size?: number }
  roles?: string[]
  children?: NavItem[]   
}

const menu: NavItem[] = [
  {
    title: 'Inicio',
    to: { name: 'dashboard' },
    icon: { icon: 'tabler-home' },
    roles: ['admin', 'operador', 'consulta'],
  },
  {
    title: 'Documentos DIAN',
    icon: { icon: 'tabler-building-broadcast-tower' },
    roles: ['admin', 'operador'],    
    children: [
      { title: 'Documentos Electrónicos - Dian- 2025 2026',to:{name:'documentosdian'}, icon: { icon: 'tabler-clipboard-text', size: 18 }, roles: ['admin', 'operador',]},
      { title: 'Notas Electrónicas', to: { name: 'documentosdian-notes' }, icon: { icon: 'tabler-brand-notion', size: 18 }, roles: ['admin'] },
      { title: 'Nómina Electrónica', to: { name: 'documentosdian-payroll' }, icon: { icon: 'tabler-user-circle', size: 18 }, roles: ['admin'] },
      { title: 'Documento Soporte', to: { name: 'documentosdian-support' }, icon: { icon: 'tabler-file-invoice', size: 18 }, roles: ['admin'] },
    ],
  },

  {
    title: 'Recepción de Facturas',
    to: { name: 'recepciondefacturas' },
    icon: { icon: 'tabler-atom' },
    roles: ['admin', 'operador'],
  },
  {
    title: 'Usuarios',
    to: { name: 'users' },
    icon: { icon: 'tabler-user-pentagon' },
    roles: ['admin'],
  },
  {
    title: 'Empresas',
    to: { name: 'companies' },
    icon: { icon: 'tabler-building-cog' },
    roles: ['admin'],
  },
  {
    title: 'Control',
    icon: { icon: 'tabler-settings-bolt' },
    roles: ['admin'],
    children: [
      { title: 'Tabla de Control', to: 'control', icon: { icon: 'tabler-clipboard-text', size: 18 }, roles: ['admin'] },
      { title: 'Resoluciones DIAN', to: '', icon: { icon: 'tabler-clipboard-text', size: 18 }, roles: ['admin'] },
      { title: 'Ciudades', to: '', icon: { icon: 'tabler-building-plus', size: 18 }, roles: ['admin'] },
      { title: 'Tipos de Documentos', to: '', icon: { icon: 'tabler-file-invoice', size: 18 }, roles: ['admin'] },
    ],
  },
]

export default menu
