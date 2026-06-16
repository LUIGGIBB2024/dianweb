export interface NavItem {
  title: string
  to?: any
  icon?: { icon: string; size?: number }
  roles?: string[]
  children?: NavItem[]
}

export interface NavItem_Saas {
  title: string
  to?: any
  icon?: { icon: string; size?: number }
  roles?: string[]
  children?: NavItem[]
}

const tipo = localStorage.getItem('tipo_de_usuario')

// const menu: NavItem[] =  []

const menu_sup: NavItem[] = [
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
      { title: 'Documentos Electrónicos', to: { name: 'documentosdian' }, icon: { icon: 'tabler-clipboard-text', size: 18 }, roles: ['admin', 'operador'] },
      { title: 'Notas Electrónicas', to: { name: 'documentosdian-notes' }, icon: { icon: 'tabler-brand-notion', size: 18 }, roles: ['admin'] },
      { title: 'Nómina Electrónica', to: { name: 'documentosdian-payroll' }, icon: { icon: 'tabler-user-circle', size: 18 }, roles: ['admin'] },
      { title: 'Documento Soporte', to: { name: 'documentosdian-support' }, icon: { icon: 'tabler-file-invoice', size: 18 }, roles: ['admin'] },
    ],
  },

  {
    title: 'Info DIAN - Real',
    icon: { icon: 'tabler-database' },
    roles: ['admin', 'operador'],
    children: [
      { title: 'Cargar Información', to: { name: 'infodianreal-cargar' }, icon: { icon: 'tabler-upload', size: 18, color: 'error' }, roles: ['admin', 'operador'] },
      { title: 'Documentos Enviados', to: { name: 'infodianreal-enviados' }, icon: { icon: 'tabler-arrows-maximize', size: 18, color: 'error' }, roles: ['admin', 'operador'] },
      { title: 'Documentos Recibidos', to: { name: 'infodianreal-recibidos' }, icon: { icon: 'tabler-arrows-minimize', size: 18, color: 'error' }, roles: ['admin'] },
      { title: 'Procesar Iva', to: { name: 'infodianreal-procesariva' }, icon: { icon: 'tabler-brand-databricks', size: 18, color: 'error' }, roles: ['admin'] },
      { title: 'Estadística Anual', to: { name: 'infodianreal-estadisticaanual' }, icon: { icon: 'tabler-chart-area-line', size: 18, color: 'error' }, roles: ['admin'] },
      { title: 'Validación de Facturas', to: { name: 'infodianreal-validarfacturas' }, icon: { icon: 'tabler-database-search', size: 18, color: 'error' }, roles: ['admin'] },
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

const menu_saas: NavItem_Saas[] = [
  {
    title: 'Inicio (SaaS)',
    to: { name: 'dashboard-saas' },
    icon: { icon: 'tabler-home' },
    roles: ['admin', 'operador', 'consulta'],
  },
  {
    title: 'Info DIAN - Real',
    icon: { icon: 'tabler-database' },
    roles: ['admin', 'operador'],
    children: [
      { title: 'Cargar Información', to: { name: 'infodianreal-cargar' }, icon: { icon: 'tabler-upload', size: 18, color: 'error' }, roles: ['admin', 'operador'] },
      { title: 'Cargar Detalle', to: { name: 'infodianreal-cargardetalle' }, icon: { icon: 'tabler-upload', size: 18, color: 'error' }, roles: ['admin', 'operador'] },
      { title: 'Documentos Enviados', to: { name: 'infodianreal-enviados' }, icon: { icon: 'tabler-arrows-maximize', size: 18, color: 'error' }, roles: ['admin', 'operador'] },
      { title: 'Documentos Recibidos', to: { name: 'infodianreal-recibidos' }, icon: { icon: 'tabler-arrows-minimize', size: 18, color: 'error' }, roles: ['admin'] },
      { title: 'Procesar Iva', to: { name: 'infodianreal-procesariva' }, icon: { icon: 'tabler-brand-databricks', size: 18, color: 'error' }, roles: ['admin'] },
      { title: 'Estadística Anual', to: { name: 'infodianreal-estadisticaanual' }, icon: { icon: 'tabler-chart-area-line', size: 18, color: 'error' }, roles: ['admin'] },
      { title: 'Validación de Facturas', to: { name: 'infodianreal-validarfacturas' }, icon: { icon: 'tabler-database-search', size: 18, color: 'error' }, roles: ['admin'] },
    ],
  },
  {
    title: 'Usuarios',
    to: { name: 'users-userssaas' },
    icon: { icon: 'tabler-user-pentagon' },
    roles: ['admin'],
  },
]

const menu_phx: NavItem_Saas[] = [
  {
    title: 'Inicio (Phx)',
    to: { name: 'dashboard-saas' },
    icon: { icon: 'tabler-home' },
    roles: ['admin', 'operador', 'consulta'],
  },
  {
    title: 'Documentos DIAN',
    icon: { icon: 'tabler-building-broadcast-tower' },
    roles: ['admin', 'operador'],
    children: [
      { title: 'Documentos Electrónicos', to: { name: 'documentosdian' }, icon: { icon: 'tabler-clipboard-text', size: 18 }, roles: ['admin', 'operador'] },
      { title: 'Notas Electrónicas', to: { name: 'documentosdian-notes' }, icon: { icon: 'tabler-brand-notion', size: 18 }, roles: ['admin'] },
      { title: 'Nómina Electrónica', to: { name: 'documentosdian-payroll' }, icon: { icon: 'tabler-user-circle', size: 18 }, roles: ['admin'] },
      { title: 'Documento Soporte', to: { name: 'documentosdian-support' }, icon: { icon: 'tabler-file-invoice', size: 18 }, roles: ['admin'] },
    ],
  },
  {
    title: 'Info DIAN - Real',
    icon: { icon: 'tabler-database' },
    roles: ['admin', 'operador'],
    children: [
      { title: 'Cargar Información', to: { name: 'infodianreal-cargar' }, icon: { icon: 'tabler-upload', size: 18, color: 'error' }, roles: ['admin', 'operador'] },
      { title: 'Cargar Detalle', to: { name: 'infodianreal-cargardetalle' }, icon: { icon: 'tabler-upload', size: 18, color: 'error' }, roles: ['admin', 'operador'] },
      { title: 'Documentos Enviados', to: { name: 'infodianreal-enviados' }, icon: { icon: 'tabler-arrows-maximize', size: 18, color: 'error' }, roles: ['admin', 'operador'] },
      { title: 'Documentos Recibidos', to: { name: 'infodianreal-recibidos' }, icon: { icon: 'tabler-arrows-minimize', size: 18, color: 'error' }, roles: ['admin'] },
      { title: 'Recepción de Facturas', to: { name: 'infodianreal-recepcion' }, icon: { icon: 'tabler-direction-arrows', size: 18, color: 'primary' }, roles: ['admin'] },
      { title: 'Procesar Iva', to: { name: 'infodianreal-procesariva' }, icon: { icon: 'tabler-brand-databricks', size: 18, color: 'error' }, roles: ['admin'] },
      { title: 'Estadística Anual', to: { name: 'infodianreal-estadisticaanual' }, icon: { icon: 'tabler-chart-area-line', size: 18, color: 'error' }, roles: ['admin'] },
      { title: 'Validación de Facturas', to: { name: 'infodianreal-validarfacturas' }, icon: { icon: 'tabler-database-search', size: 18, color: 'error' }, roles: ['admin'] },
    ],
  },
  {
    title: 'Usuarios',
    to: { name: 'users-userssaas' },
    icon: { icon: 'tabler-user-pentagon' },
    roles: ['admin'],
  },
]

const menu = tipo === 'SuperAdmin' ? menu_sup : (tipo === 'Cliente Phx' ? menu_phx : menu_saas)
export default menu
