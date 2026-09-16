<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateServicoRequest extends StoreServicoRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('servico'));

        return true;
    }
}
