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

