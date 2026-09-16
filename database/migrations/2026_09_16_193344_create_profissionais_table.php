<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unique(['id', 'estabelecimento_id']);
        });

        Schema::create('profissionais', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('estabelecimento_id')->constrained('estabelecimentos')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique();
            $table->string('nome');
            $table->string('telefone')->nullable();
            $table->string('email')->nullable();
            $table->string('foto')->nullable();
            $table->text('descricao')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->foreign(['user_id', 'estabelecimento_id'])
                ->references(['id', 'estabelecimento_id'])->on('users')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profissionais');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['id', 'estabelecimento_id']);
        });
    }
};
