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
        Schema::create('stg_becados_obu', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_estudiante', 20);
            $table->string('dni', 15)->nullable();
            $table->string('nombres_apellidos', 200)->nullable();
            $table->string('tipo_beca', 60)->nullable();
            $table->string('periodo_academico', 20);
            $table->string('estado', 30)->nullable();
            $table->string('archivo_origen', 255);
            $table->timestamp('cargado_en')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stg_becados_obu');
    }
};
