<?php

namespace App\Models;

use Database\Factories\ProfissionalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nome', 'telefone', 'email', 'foto', 'descricao', 'user_id'])]
class Profissional extends Model
{
    /** @use HasFactory<ProfissionalFactory> */
    use HasFactory;

    protected $table = 'profissionais';

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
}
