<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Payroll extends Model
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
        'employee',
        'employee_name',
        'total_paid',
        'total_accrued',
        'total_deduction',
        'companies_id',      
        'cune',
        'state',
    ];
    // 🔗 Relación: una factura pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }
}

