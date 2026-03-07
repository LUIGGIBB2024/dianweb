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
        Schema::create('stake_holders', function (Blueprint $table) {
            $table->id();
            $table->string('nit', 20)->nullable();
            $table->string('document_type', 10)->nullable();
            $table->string('type', 10)->nullable();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('street', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('city', 20)->nullable();
            $table->string('department', 20)->nullable();
            $table->string('postal_zone', 20)->nullable();
            $table->string('fiscal_responsability', 20)->nullable();
            $table->string('business_registration', 20)->nullable();
            $table->string('type_of_regime', 20)->nullable();
            $table->string('economic_activity', 20)->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('state', 20)->nullable();

            $table->index(['nit', 'type'], 'idx_nit_type');

            $table->unsignedBigInteger('companies_id')->index()->nullable();
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('set null');

            $table->unsignedBigInteger('stake_holders_id')->index()->nullable();
            $table->foreign('stake_holders_id')->references('id')->on('stake_holders')->onDelete('set null');

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stake_holders');
    }
};
