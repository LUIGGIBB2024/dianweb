<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class InvoiceDetail extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'prefix',
        'customer',
        'date_issue',
        'product',
        'name',
        'store',
        'quantity',
        'basequantity',
        'unit_value',
        'cost_value',
        'discount1',
        'discount2',
        'valuediscount1',
        'valuediscount2',
        'state',
        'companies_id',
        'sales_invoices_id',
    ];

    // 🔗 Relación: un detalle de factura pertenece a una factura de venta
    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoices_id');
    }

    // 🔗 Relación: un detalle de factura pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }

    // 🔗 Relación: un detalle de factura pertenece a un producto
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
