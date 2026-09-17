<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexUsuarioRequest;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(IndexUsuarioRequest $request): View
    {
        $filters = $request->validated();
        $usuarios = User::query()
            ->where('estabelecimento_id', $request->user()->estabelecimento_id)
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('name', '%'.$search.'%')->orWhereLike('email', '%'.$search.'%');
                });
            })
            ->when($filters['role'] ?? null, fn (Builder $query, string $role): Builder => $query->where('role', $role))
            ->when(isset($filters['active']), fn (Builder $query): Builder => $query->where('active', $filters['active']))
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 10)
            ->appends($request->safe()->except('page'));

        return view('usuarios.index', compact('usuarios', 'filters'));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('usuarios.form', ['usuario' => null]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $usuario = new User($request->safe()->only(['name', 'email', 'password']));
        $usuario->estabelecimento_id = $request->user()->estabelecimento_id;
        $usuario->role = $request->validated('role');
        $usuario->active = true;
        $usuario->save();

        return redirect()->route('usuarios.index')->with('status', 'Usuário cadastrado com sucesso.');
    }

    public function edit(User $usuario): View
    {
        Gate::authorize('update', $usuario);

        return view('usuarios.form', compact('usuario'));
    }

    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $usuario->fill($request->safe()->only(['name', 'email']));
        $usuario->role = $request->validated('role');

        if ($request->filled('password')) {
            $usuario->password = $request->validated('password');
        }

        if ($usuario->isDirty('email')) {
            $usuario->email_verified_at = null;
        }

        $usuario->save();

        return redirect()->route('usuarios.index')->with('status', 'Usuário atualizado com sucesso.');
    }

    public function updateStatus(Request $request, User $usuario): RedirectResponse
    {
        Gate::authorize('changeStatus', $usuario);
        $request->validate(['active' => ['required', 'boolean']], [
            'active.required' => 'Informe a situação desejada.',
            'active.boolean' => 'Selecione uma situação válida.',
        ]);
        $usuario->active = $request->boolean('active');
        $usuario->save();

        return redirect()->route('usuarios.index')->with('status', $usuario->active ? 'Usuário ativado.' : 'Usuário desativado.');
    }
}
