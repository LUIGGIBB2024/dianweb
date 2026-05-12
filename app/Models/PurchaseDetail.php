<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
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
        'document_name',
        'supplier',
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
        'purchases_invoices_id',
    ];

    // 🔗 Relación: un detalle de factura pertenece a una factura de venta
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchasesInvoice::class, 'purchases_invoices_id');
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
