<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nit',
        'dv',
        'representativeid',
        'email',
        'address',
        'phone',
        'token',
        'certificatename',
        'certificatekey',
        'technicalkey',
        'endpoint1',
        'endpoint2',
        'city',
        'date_from',
        'date_to',
    ];

    // 🔗 Relación: una empresa tiene muchos usuarios
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
