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
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_issue')->nullable()->index();
            $table->dateTime('expiration_date')->nullable();
            $table->decimal('number', 20, 0)->nullable();
            $table->string('prefix', 20)->nullable();
            $table->string('document_name', 30)->nullable();
            $table->string('customer', 20)->nullable()->index();
            $table->string('client_name', 255)->nullable();
            $table->decimal('subtotal', 20, 2)->nullable();            
            $table->decimal('vatvalue', 20, 2)->nullable();
            $table->decimal('reteiva', 20, 2)->nullable();
            $table->decimal('reteica', 20, 2)->nullable();
            $table->decimal('impoconsumo', 20, 2)->nullable();
            $table->decimal('total_sale', 20, 2)->nullable();
            $table->string('cufe', 255)->nullable()->index();
            $table->string('state', 20)->nullable();

            $table->index(['number', 'prefix', 'customer','companies_id'], 'idx_number_prefix_customer');

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
        Schema::dropIfExists('sales_invoices');
    }
};
