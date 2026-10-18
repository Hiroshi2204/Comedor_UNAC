<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ajustes')) {
            Schema::create('ajustes', function (Blueprint $table) {
                $table->string('clave', 120)->primary();
                $table->string('valor', 255);
                $table->timestamp('updated_at')->nullable();
            });
        }

        // un registro por archivo de OBU cargado desde el panel
        if (!Schema::hasTable('importaciones_obu')) {
            Schema::create('importaciones_obu', function (Blueprint $table) {
                $table->id();
                $table->foreignId('periodo_id')->nullable()->constrained('periodos_academicos')->nullOnDelete();
                $table->string('lote_id', 40)->index();
                $table->string('archivo', 190);
                $table->unsignedInteger('registros')->default(0);
                $table->json('resumen')->nullable();                 // estados del staging al cargar
                $table->enum('estado', ['cargado', 'procesado'])->default('cargado');
                $table->unsignedInteger('procesados')->nullable();   // becados creados o actualizados
                $table->foreignId('usuario_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
                $table->timestamp('procesado_at')->nullable();
                $table->timestamps();
            });
        }

        // quién levantó la sanción y por qué
        Schema::table('sanciones', function (Blueprint $table) {
            if (!Schema::hasColumn('sanciones', 'levantada_por')) {
                $table->foreignId('levantada_por')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            }
            if (!Schema::hasColumn('sanciones', 'nota_levantamiento')) {
                $table->string('nota_levantamiento', 250)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sanciones', function (Blueprint $table) {
            if (Schema::hasColumn('sanciones', 'levantada_por')) {
                $table->dropConstrainedForeignId('levantada_por');
            }
            if (Schema::hasColumn('sanciones', 'nota_levantamiento')) {
                $table->dropColumn('nota_levantamiento');
            }
        });
        Schema::dropIfExists('importaciones_obu');
        Schema::dropIfExists('ajustes');
    }
};
