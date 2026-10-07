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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->cascadeOnDelete();
            $table->foreignId('ventana_atencion_id')->nullable()
                ->constrained('ventanas_atencion')->nullOnDelete();
            $table->foreignId('ticket_origen_id')->nullable()
                ->constrained('tickets')->nullOnDelete(); // autorreferencia (Etapa 5)
            $table->foreignId('trabajador_id')->nullable()
                ->constrained('usuarios_sistema')->nullOnDelete();
            $table->enum('tipo', ['becado', 'no_becado']);
            $table->enum('servicio', ['desayuno', 'almuerzo', 'cena']);
            $table->date('fecha');
            $table->enum('estado', ['generado', 'cancelado', 'recogido', 'no_recogido', 'vencido'])
     ->default('generado');
            $table->string('codigo_qr', 64)->unique();
            $table->dateTime('fecha_hora_recojo')->nullable();
            $table->timestamps();

            $table->unique(['estudiante_id', 'fecha', 'servicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
