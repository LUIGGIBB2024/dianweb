export interface User {
  id: number
  name: string
  email: string
  empresa: string
  type: string
  company_id: number
  password: string
}
 

// Interfaz para parámetros de búsqueda o paginación
export interface UserParams {
  q?: string
  itemsPerPage?: number
  page?: number
  sortBy?: string
  orderBy?: 'asc' | 'desc'
}
