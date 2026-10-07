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
        Schema::create('horarios_clase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->cascadeOnDelete();
            $table->foreignId('stg_horario_id')->nullable()
                ->constrained('stg_horarios_sga')->nullOnDelete();
            $table->string('curso', 150)->nullable();
            $table->enum('dia_semana', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado']);
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('aula', 30)->nullable();
            $table->timestamps();

            $table->index(['estudiante_id', 'dia_semana']); // consulta frecuente: "¿tiene clases hoy?"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_clase');
    }
};
