<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_profissionais', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profissional_id')
                ->constrained('profissionais')
                ->cascadeOnDelete();

            // 0 = Domingo
            // 1 = Segunda
            // 2 = Terça
            // 3 = Quarta
            // 4 = Quinta
            // 5 = Sexta
            // 6 = Sábado
            $table->unsignedTinyInteger('dia_semana');

            $table->time('hora_inicio');
            $table->time('hora_fim');

            $table->timestamps();

            $table->index([
                'profissional_id',
                'dia_semana'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_profissionais');
    }
};