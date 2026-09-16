<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioProfissional extends Model
{
    use HasFactory;

    protected $table = 'horarios_profissionais';

    protected $fillable = [
        'profissional_id',
        'dia_semana',
        'hora_inicio',
        'hora_fim',
    ];

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }
}