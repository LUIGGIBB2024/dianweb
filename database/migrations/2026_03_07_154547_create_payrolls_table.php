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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_issue')->nullable()->index();
            $table->dateTime('expiration_date')->nullable();
            $table->decimal('number', 20, 0)->nullable();
            $table->string('prefix', 20)->nullable();
            $table->string('document_name', 30)->nullable();
            $table->string('employee', 20)->nullable()->index();
            $table->string('employee_name', 255)->nullable();
            $table->decimal('total_paid', 20, 2)->nullable();
            $table->decimal('total_accrued', 20, 2)->nullable();
            $table->decimal('total_deduction', 20, 2)->nullable();
            $table->string('cune', 255)->nullable()->index();
            $table->string('state', 20)->nullable();

            $table->index(['number', 'prefix', 'employee', 'companies_id'], 'idx_number_prefix_employee');

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
        Schema::dropIfExists('payrolls');
    }
};
