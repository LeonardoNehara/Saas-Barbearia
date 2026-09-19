<?php

namespace App\Models;

use Carbon\Carbon;
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
        'intervalo_inicio',
        'intervalo_fim',
    ];

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function conflitaComIntervalo(Carbon $inicio, Carbon $fim): bool
    {
        if (! $this->intervalo_inicio || ! $this->intervalo_fim) {
            return false;
        }

        $inicioIntervalo = $inicio->copy()->setTimeFromTimeString($this->intervalo_inicio);
        $fimIntervalo = $inicio->copy()->setTimeFromTimeString($this->intervalo_fim);

        return $inicio->lt($fimIntervalo) && $fim->gt($inicioIntervalo);
    }
}
