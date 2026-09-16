<?php

namespace App\Models;

use Database\Factories\EstabelecimentoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'slug', 'cnpj', 'telefone', 'email', 'logo', 'active', 'timezone', 'trial_ends_at'])]
class Estabelecimento extends Model
{
    /** @use HasFactory<EstabelecimentoFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'trial_ends_at' => 'datetime',
        ];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Profissional, $this> */
    public function profissionais(): HasMany
    {
        return $this->hasMany(Profissional::class);
    }

    /** @return HasMany<Servico, $this> */
    public function servicos(): HasMany
    {
        return $this->hasMany(Servico::class);
    }
}
