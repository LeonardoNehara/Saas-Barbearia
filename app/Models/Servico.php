<?php

namespace App\Models;

use Database\Factories\ServicoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nome', 'descricao', 'duracao_minutos', 'preco'])]
class Servico extends Model
{
    /** @use HasFactory<ServicoFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['active' => 'boolean', 'duracao_minutos' => 'integer', 'preco' => 'decimal:2'];
    }

    /** @return BelongsTo<Estabelecimento, $this> */
    public function estabelecimento(): BelongsTo
    {
        return $this->belongsTo(Estabelecimento::class);
    }

    /** @return BelongsToMany<Profissional, $this> */
    public function profissionais(): BelongsToMany
    {
        return $this->belongsToMany(Profissional::class, 'profissional_servico')->withTimestamps();
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }
}
