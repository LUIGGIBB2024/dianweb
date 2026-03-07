<?php

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class StakeHolder extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nit',
        'document_type',
        'type',
        'first_name',
        'last_name',
        'name',
        'street',
        'phone',
        'email',
        'city',
        'department',
        'postal_zone',
        'fiscal_responsability',
        'business_registration',
        'type_of_regime',
        'economic_activity',
        'sex',
        'state',
        'companies_id',
        'stake_holders_id',
    ];

    // 🔗 Relación: un stakeholder pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class, 'companies_id');
    }

    // 🔗 Relación: un stakeholder puede tener un stakeholder padre
    public function parent()
    {
        return $this->belongsTo(StakeHolder::class, 'stake_holders_id');
    }   
}
