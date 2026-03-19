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
        Schema::create('dian_token_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('status', [
                'waiting',    // En cola, esperando su turno
                'processing', // Abrió la DIAN, esperando correo
                'received',   // Token recibido exitosamente
                'timeout',    // Expiró sin recibir token
                'error'
            ])->default('waiting');
            $table->string('token', 36)->nullable();
            $table->string('url_completa')->nullable();
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dian_token_queues');
    }
};
