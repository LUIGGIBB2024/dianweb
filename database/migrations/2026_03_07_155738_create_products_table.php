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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->index();
            $table->string('name', 255)->nullable()->index();
            $table->string('unit_of_measure', 20)->nullable();
            $table->string('group', 20)->nullable();
            $table->string('subgroup', 20)->nullable();
            $table->decimal('percent', 20, 2)->nullable();
            $table->decimal('listvalue1', 20, 2)->nullable();
            $table->decimal('listvalue2', 20, 2)->nullable();
            $table->decimal('listvalue3', 20, 2)->nullable();
            $table->decimal('cost', 20, 2)->nullable();
            $table->string('state', 20)->nullable();

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
        Schema::dropIfExists('products');
    }
};
