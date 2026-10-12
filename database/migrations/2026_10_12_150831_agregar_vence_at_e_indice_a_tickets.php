<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'vence_at')) {
                $table->dateTime('vence_at')->nullable()->after('fecha_hora_recojo');
            }
            $table->index(['fecha', 'servicio', 'estado'], 'tickets_fecha_servicio_estado_index');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_fecha_servicio_estado_index');
        });
    }
};