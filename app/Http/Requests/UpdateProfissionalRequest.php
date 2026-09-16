<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateProfissionalRequest extends StoreProfissionalRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('profissional'));

        return true;
    }
}
