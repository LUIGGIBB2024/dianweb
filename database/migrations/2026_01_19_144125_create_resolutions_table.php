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
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->integer('type_document_id')->nullable();
            $table->string('resolution_number', 100)->nullable();
            $table->date('resolution_date')->nullable();
            $table->integer('current')->nullable();
            $table->string('prefix', 20)->nullable();
            $table->integer('from')->nullable();
            $table->integer('to')->nullable();           
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->unsignedBigInteger('company_id')->index()->nullable(); ;
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};
