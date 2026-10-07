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
        Schema::create('stg_becados_obu', function (Blueprint $t) {
            $t->id();
            $t->foreignId('periodo_id')->constrained('periodos_academicos');
            $t->uuid('lote_id')->index();               // una carga = un lote
            $t->string('hoja', 50);                     // título tal cual
            $t->string('escuela', 50);                  // hoja con trim
            $t->unsignedSmallInteger('fila_excel');
            $t->string('nombre_completo', 150);
            $t->string('dni_raw', 20);
            $t->string('codigo_raw', 20);
            $t->string('dni', 8)->nullable();           // normalizado
            $t->string('codigo', 12)->nullable();
            $t->string('estado', 15)->default('pendiente'); // pendiente|valido|observado|duplicado
            $t->string('observacion')->nullable();
            $t->timestamps();
            $t->index(['periodo_id', 'dni']);
            $t->index(['periodo_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stg_becados_obu');
    }
};
