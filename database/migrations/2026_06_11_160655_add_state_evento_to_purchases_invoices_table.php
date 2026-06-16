<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases_invoices', function (Blueprint $table) {
            $table->enum('state_evento', ['pendiente', 'procesando', 'completado', 'fallido'])->default('pendiente')->after('evento'); //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases_invoices', function (Blueprint $table) {
            $table->dropColumn('state_evento');
        });
    }
};
