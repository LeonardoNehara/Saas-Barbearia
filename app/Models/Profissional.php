<?php

namespace App\Models;

use Database\Factories\ProfissionalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'telefone', 'email', 'foto', 'descricao', 'user_id'])]
class Profissional extends Model
{
    /** @use HasFactory<ProfissionalFactory> */
    use HasFactory;

    protected $table = 'profissionais';

    protected static function booted(): void
    {
        static::created(function (Profissional $profissional): void {
            foreach (range(1, 6) as $diaSemana) {
                $profissional->horarios()->create([
                    'dia_semana' => $diaSemana,
                    'hora_inicio' => '08:00',
                    'hora_fim' => '19:00',
                ]);
            }
        });
    }

    /** @return BelongsToMany<Servico, $this> */
    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(Servico::class, 'profissional_servico')->withTimestamps();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** @return BelongsTo<Estabelecimento, $this> */
    public function estabelecimento(): BelongsTo
    {
        return $this->belongsTo(Estabelecimento::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioProfissional::class);
    }

    public function bloqueios(): HasMany
    {
        return $this->hasMany(BloqueioProfissional::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }
}
