
import flatpickr from 'flatpickr'

export interface Apidian {
  id: number
  date_issue:string 
  settlement_start_date:string 
  settlement_end_date:string
  consecutive: string
  type_document_name: string
  identification_number:string
  employee_name: string
  accrued_total: number
  deductions_total: number
  total_payroll: number
}
 


// Interfaz para parámetros de búsqueda o paginación
export interface UserParams {
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

  