<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->active && $user->isAdmin() && $user->estabelecimento_id !== null;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $usuario): Response
    {
        if ($user->estabelecimento_id !== $usuario->estabelecimento_id) {
            return Response::denyAsNotFound();
        }

        return $this->viewAny($user) ? Response::allow() : Response::deny();
    }

    public function changeStatus(User $user, User $usuario): Response
    {
        $response = $this->update($user, $usuario);

        if ($response->denied()) {
            return $response;
        }

        return $user->is($usuario)
            ? Response::deny('Você não pode desativar sua própria conta.')
            : Response::allow();
    }
}
