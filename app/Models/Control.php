<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Control extends Model
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
        'email',
        'password',
        'address',
        'phone',
        'token',
        'technicalkey',
        'idsoftware',
        'testsetid',
        'path',
        'urldian',
    ];
}
