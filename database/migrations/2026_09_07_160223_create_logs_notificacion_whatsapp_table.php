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
        Schema::create('logs_notificacion_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('numero_destino', 20);
            $table->enum('estado_envio', ['pendiente', 'enviado', 'fallido'])->default('pendiente');
            $table->text('respuesta_proveedor')->nullable();
            $table->integer('intentos')->default(0);
            $table->dateTime('fecha_envio');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_notificacion_whatsapp');
    }
};
