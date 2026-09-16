<?php

namespace App\Policies;

use App\Models\Servico;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServicoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->active && $user->isAdmin() && $user->estabelecimento_id !== null;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, Servico $servico): Response
    {
        if ($user->estabelecimento_id !== $servico->estabelecimento_id) {
            return Response::denyAsNotFound();
        }

        return $this->viewAny($user) ? Response::allow() : Response::deny();
    }

    public function update(User $user, Servico $servico): Response
    {
        return $this->view($user, $servico);
    }

    public function syncProfissionais(User $user, Servico $servico): Response
    {
        return $this->view($user, $servico);
    }
}
