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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 250)->nullable();
            $table->string('nit', 20)->nullable();
            $table->string('representativeid', 20)->nullable();
            $table->string('nit', 20)->nullable();
            $table->string('address', 250);
            $table->string('phone', 250);
            $table->string('email', 250)->nullable();
            $table->string('token', 250)->nullable();
            $table->string('certificatename', 250)->nullable();
            $table->string('certificatekey', 250)->nullable();
            $table->string('technicalkey', 250)->nullable();
            $table->string('testsetid', 250)->nullable();
            $table->string('endpoint1', 250)->nullable();
            $table->string('endpoint2', 250)->nullable();
            $table->string('city', 100)->nullable();
            $table->unsignedBigInteger('companyapi_id')->nullable();
            $table->foreign('companyapi_id')->references('id')->on('company_apis')->onDelete('set null');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('mail_name', 250)->nullable();
            $table->string('mail_password', 250)->nullable();
            $table->string('mail_encryption', 250)->nullable();
            $table->string('mail_port', 250)->nullable();
            $table->string('mail_from_name', 250)->nullable();
            $table->string('imap_server', 250)->nullable();
            $table->string('imap_user', 250)->nullable();
            $table->string('imap_password', 250)->nullable();
            $table->string('imap_encryption', 250)->nullable();
            $table->string('imap_port', 250)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
