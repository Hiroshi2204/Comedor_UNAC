<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // cuándo se le mandó a la última franja (null = nunca); solo se permite una vez por ticket
            if (!Schema::hasColumn('tickets', 'ultima_oportunidad_at')) {
                $table->dateTime('ultima_oportunidad_at')->nullable()->after('vence_at');
            }
            // la franja que había elegido antes de ser reprogramado
            if (!Schema::hasColumn('tickets', 'ventana_original_id')) {
                $table->foreignId('ventana_original_id')->nullable()->after('ultima_oportunidad_at')
                    ->constrained('ventanas_atencion')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ventana_original_id');
            $table->dropColumn('ultima_oportunidad_at');
        });
    }
};
