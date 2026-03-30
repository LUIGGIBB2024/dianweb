
import flatpickr from 'flatpickr'

export interface RecepcionFactura {
  Fecha:string 
  NroDocumento:string 
  Prefijo:string
  TipoDocumento: string
  NitEmisor: string
  Emisor:string
  ValorParcial: number
  ValorImptos: number
  ValorTotal: number
}
 


// Interfaz para parámetros de búsqueda o paginación
export interface RecepcionFacturaParams {
  q?: string
  itemsPerPage?: number
  page?: number
  sortBy?: string
  orderBy?: 'asc' | 'desc'
}

// Ajustar zona local manualmente
const offset = new Date().getTimezoneOffset() * 60000
flatpickr.defaultConfig = {
  ...flatpickr.defaultConfig,
  defaultDate: new Date(Date.now() - offset), // Ajuste local
}

  