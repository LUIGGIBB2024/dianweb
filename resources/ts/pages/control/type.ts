export interface Control {
  id: number
  name: string
  nit: string
  address: string
  phone: string
  email: string
  password  : string
  type: string
  path: string 
}
 

// Interfaz para parámetros de búsqueda o paginación
export interface ControlParams {
  q?: string
  itemsPerPage?: number
  page?: number
  sortBy?: string
  orderBy?: 'asc' | 'desc'
}
