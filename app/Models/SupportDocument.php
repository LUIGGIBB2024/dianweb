<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class SupportDocument extends Model
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
        'concept',
        'document_type',
        'document_name',
        'supplier',
        'supplier_name',
        'subtotal',
        'vatvalue',
        'reteiva',
        'reteica',
        'total_purchase',
        'cufe',
        'observations',
        'user',
        'companies_id',
        'state',
    ];

    // 🔗 Relación: una factura pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }
}
