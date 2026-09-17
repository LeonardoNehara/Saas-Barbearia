<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estabelecimento_id')
                ->constrained('estabelecimentos')
                ->restrictOnDelete();

            $table->foreignId('profissional_id')
                ->constrained('profissionais')
                ->restrictOnDelete();

            $table->foreignId('servico_id')
                ->constrained('servicos')
                ->restrictOnDelete();

            $table->string('cliente_nome', 150);
            $table->string('cliente_telefone', 20);

            $table->dateTime('inicio');
            $table->dateTime('fim');

            $table->string('status', 20)
                ->default('agendado');

            $table->text('observacoes')
                ->nullable();

            $table->timestamp('cancelado_em')
                ->nullable();

            $table->timestamps();

            $table->index([
                'profissional_id',
                'inicio',
                'fim',
            ]);

            $table->index([
                'estabelecimento_id',
                'status',
                'inicio',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};