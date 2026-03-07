<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('puchaces_invoices', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_issue')->nullable()->index();
            $table->dateTime('expiration_date')->nullable();
            $table->decimal('number', 20, 0)->nullable();
            $table->string('prefix', 20)->nullable();
            $table->string('document_name', 30)->nullable();
            $table->string('supplier', 20)->nullable()->index();
            $table->string('supplier_name', 255)->nullable();
            $table->decimal('subtotal', 20, 2)->nullable();             
            $table->decimal('vatvalue', 20, 2)->nullable();
            $table->decimal('reteiva', 20, 2)->nullable();
            $table->decimal('reteica', 20, 2)->nullable();
            $table->decimal('total_purchase', 20, 2)->nullable();
            $table->string('cufe', 255)->nullable()->index();
            $table->string('evento1', 20)->nullable();
            $table->string('evento2', 20)->nullable();
            $table->string('evento3', 20)->nullable();
            $table->string('state', 20)->nullable();

            $table->index(['number', 'prefix', 'supplier', 'companies_id'], 'idx_number_prefix_purchaser');

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puchaces_invoices');
    }
};
