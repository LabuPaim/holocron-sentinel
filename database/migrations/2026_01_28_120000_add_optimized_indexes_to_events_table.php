<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adiciona índices otimizados para melhorar performance de queries:
     * - [entity_id, type, created_at]: Otimiza ranking crítico e queries filtradas por entidade e tipo
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Índice composto otimizado para ranking crítico e queries que filtram por entity_id + type + created_at
            // Este índice é mais eficiente que usar os índices separados para queries que combinam essas condições
            $table->index(['entity_id', 'type', 'created_at'], 'idx_events_entity_type_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_entity_type_created');
        });
    }
};
