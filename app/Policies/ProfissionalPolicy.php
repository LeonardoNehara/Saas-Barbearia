<?php

namespace App\Policies;

use App\Models\Profissional;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProfissionalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->active && $user->isAdmin() && $user->estabelecimento_id !== null;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, Profissional $profissional): Response
    {
        if ($user->estabelecimento_id !== $profissional->estabelecimento_id) {
            return Response::denyAsNotFound();
        }

        return $this->viewAny($user) ? Response::allow() : Response::deny();
    }

    public function update(User $user, Profissional $profissional): Response
    {
        return $this->view($user, $profissional);
    }
}
