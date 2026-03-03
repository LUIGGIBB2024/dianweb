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
        Schema::create('controls', function (Blueprint $table) {
            $table->id();
            $table->string('name', 250)->nullable();
            $table->string('nit', 20)->nullable();
            $table->string('address', 250)->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('token', 250)->nullable();
            $table->string('idsoftware', 250)->nullable();
            $table->string('technicalkey', 250)->nullable();
            $table->string('testsetid', 250)->nullable();
            $table->string('email', 250)->nullable();
            $table->string('password', 250)->nullable();
            $table->string('path', 250)->nullable();
            $table->string('urldian', 250)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controls');
    }
};
