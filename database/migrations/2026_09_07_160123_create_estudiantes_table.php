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
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_universitario', 20)->unique();
            $table->string('dni', 15)->nullable();
            $table->string('nombres', 120);
            $table->string('apellidos', 120);
            $table->string('email_institucional', 150)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('escuela_profesional', 150)->nullable();
            $table->foreignId('periodo_academico_id')->nullable()
                ->constrained('periodos_academicos')->nullOnDelete();
            $table->boolean('matriculado')->default(false);
            $table->timestamp('ultima_sincronizacion_sga')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
