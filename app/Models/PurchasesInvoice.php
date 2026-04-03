<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class PurchasesInvoice extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'date_issue',
        'expiration_date',
        'number',
        'prefix',
        'document_name',
        'supplier',
        'supplier_name',
        'total_purchase',
        'discounts',
        'subtotal',
        'vatvalue',
        'retefuente',
        'reteiva',
        'reteica',
        'cufe',
        'companies_id',
        'evento1',
        'evento2',
        'evento3',
        'state',
    ];
    // 🔗 Relación: una factura pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }
}
