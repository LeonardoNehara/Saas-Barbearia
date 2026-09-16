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
        Schema::create('servicos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('estabelecimento_id')->constrained('estabelecimentos')->restrictOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->unsignedInteger('duracao_minutos');
            $table->decimal('preco', 10, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicos');
    }
};
