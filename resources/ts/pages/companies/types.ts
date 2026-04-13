// resources/ts/pages/companies/types.ts

import flatpickr from 'flatpickr'
// Interfaz que define la estructura del modelo Company
export interface Company {
  id?: number   
  nit: string
  dv: string
  representativeid: string
  name: string
  address: string
  email: string
  dian_email: string
  phone: string
  city: string
  endpoint1: string  
  token: string  
  date_from:string | Date
  date_to:string | Date
  endpoint2:string,
  endpoint3:string,
  certificatename:string | File,
  certificatekey:string,
  days_difference:number,
}

// Interfaz para parámetros de búsqueda o paginación
export interface CompanyParams {
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


