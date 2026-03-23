<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class DianTokenQueue extends Model
{
    use HasFactory, Notifiable; // 👈 ESTE trait es el que añade createToken()

    protected $fillable = [
        'user_id',
        'company_id',
        'status',
        'token',
        'url_completa',
        'processing_at',
        'received_at',
        'queued_at'
    ];
}
