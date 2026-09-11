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
        Schema::create('becados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->cascadeOnDelete();
            $table->foreignId('stg_becado_id')->nullable()
                ->constrained('stg_becados_obu')->nullOnDelete();
            $table->string('codigo_beca', 30)->nullable();
            $table->string('tipo_beca', 60)->nullable();
            $table->enum('estado', ['activo', 'suspendido', 'dado_de_baja'])->default('activo');
            $table->date('fecha_registro');
            $table->timestamps();

            $table->unique(['estudiante_id', 'periodo_academico_id']); // 1 registro de beca por periodo
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('becados');
    }
};
