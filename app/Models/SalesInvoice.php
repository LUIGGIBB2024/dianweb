<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
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
        'customer',
        'client_name',
        'total_sale',
        'vatvalue',
        'reteiva',
        'reteica',
        'impoconsumo',
        'cufe',
        'companies_id',
        'state',
    ];

    // 🔗 Relación: una factura pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }
}
