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
            $table->enum('evento', ['evento_1', 'evento_2', 'evento_3'])->after('evento3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases_invoices', function (Blueprint $table) {
            $table->dropColumn('evento');
        });
    }
};
