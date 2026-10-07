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
        Schema::create('stg_horarios_sga', function (Blueprint $table) {
            $table->id();
            $table->string('edu_seccion_id', 40)->nullable()->index();
            $table->string('edu_particion_id', 40)->nullable();
            $table->string('tipo', 5)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('periodo_academico', 20);
            $table->string('escuela', 150);
            $table->string('seccion', 30)->nullable();
            $table->string('cod_curso', 20)->nullable();
            $table->string('curso', 150)->nullable();
            $table->string('ciclo', 10)->nullable();
            $table->string('docente', 150)->nullable();
            $table->string('dia', 15)->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('aula', 30)->nullable();
            $table->string('sede', 60)->nullable();
            $table->integer('n_matriculados')->nullable();
            $table->string('archivo_origen', 255)->default('API SGA');
            $table->timestamp('cargado_en')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stg_horarios_sga');
    }
};
