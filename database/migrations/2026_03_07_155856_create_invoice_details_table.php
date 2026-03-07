<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->nullable();
            $table->string('prefix', 20)->nullable();
            $table->string('customer', 20)->nullable();
            $table->dateTime('date_issue')->nullable()->index();
            $table->string('product', 20)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('store', 20)->nullable();
            $table->decimal('quantity', 20, 2)->nullable();
            $table->decimal('basequantity', 20, 8)->nullable();
            $table->decimal('unit_value', 20, 8)->nullable();
            $table->decimal('cost_value', 20, 8)->nullable();
            $table->decimal('discount1', 6, 2)->nullable();
            $table->decimal('discount2', 6, 2)->nullable();
            $table->decimal('valuediscount1', 20, 2)->nullable();
            $table->decimal('valuediscount2', 20, 2)->nullable();

            $table->string('state', 20)->nullable();

            $table->index(['number', 'prefix', 'product', 'store', 'customer', 'companies_id'], 'idx_number_prefix_customer');

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->unsignedBigInteger('sales_invoices_id')->index()->nullable();
            $table->foreign('sales_invoices_id')->references('id')->on('sales_invoices')->onDelete('set null');

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};
