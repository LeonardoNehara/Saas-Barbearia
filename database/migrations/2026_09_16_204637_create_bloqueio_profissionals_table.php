<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueios_profissionais', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profissional_id')
                ->constrained('profissionais')
                ->cascadeOnDelete();

            $table->dateTime('inicio');
            $table->dateTime('fim');

            $table->string('motivo')->nullable();

            $table->timestamps();

            $table->index([
                'profissional_id',
                'inicio',
                'fim',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueios_profissionais');
    }
};