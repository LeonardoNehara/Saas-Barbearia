<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexProfissionalRequest;
use App\Http\Requests\StoreProfissionalRequest;
use App\Http\Requests\UpdateProfissionalRequest;
use App\Http\Resources\ProfissionalResource;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfissionalController extends Controller
{
    public function index(IndexProfissionalRequest $request): AnonymousResourceCollection|View
    {
        $filters = $request->validated();
        $query = Profissional::query()
            ->where('estabelecimento_id', $request->user()->estabelecimento_id)
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query->whereLike('nome', '%'.$filters['search'].'%')->orWhereLike('email', '%'.$filters['search'].'%');
                });
            })
            ->when(isset($filters['active']), fn (Builder $query): Builder => $query->where('active', $filters['active']))
            ->when($filters['servico_id'] ?? null, fn (Builder $query, int $id): Builder => $query->whereHas('servicos', fn (Builder $query): Builder => $query->where('servicos.id', $id)->where('servicos.estabelecimento_id', $request->user()->estabelecimento_id)));

        if (! $request->expectsJson()) {
            $query->with([
                'servicos' => fn ($query) => $query->where('servicos.estabelecimento_id', $request->user()->estabelecimento_id)->orderBy('nome'),
                'horarios' => fn ($query) => $query->orderBy('dia_semana')->orderBy('hora_inicio'),
                'bloqueios' => fn ($query) =>
                    $query->orderBy('inicio'),
                'user',
            ]);
        }

        $profissionais = $query->orderBy('id')->paginate($filters['per_page'] ?? ($request->expectsJson() ? 15 : 10))
            ->appends($request->safe()->except('page'));

        if ($request->expectsJson()) {
            return ProfissionalResource::collection($profissionais);
        }

        $servicos = Servico::where('estabelecimento_id', $request->user()->estabelecimento_id)->orderBy('nome')->get();
        $usuarios = User::where('estabelecimento_id', $request->user()->estabelecimento_id)->with('profissional')->orderBy('name')->get();
        $hasProfessionals = Profissional::where('estabelecimento_id', $request->user()->estabelecimento_id)->exists();

        return view('profissionais.index', compact('profissionais', 'servicos', 'usuarios', 'filters', 'hasProfessionals'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Profissional::class);
        $usuarios = User::where('estabelecimento_id', $request->user()->estabelecimento_id)->whereDoesntHave('profissional')->orderBy('name')->get();

        return view('profissionais.form', ['profissional' => null, 'usuarios' => $usuarios]);
    }

    public function edit(Request $request, Profissional $profissional): View
    {
        Gate::authorize('update', $profissional);
        $profissional->load(['servicos' => fn ($query) => $query->where('servicos.estabelecimento_id', $request->user()->estabelecimento_id)]);
        $usuarios = User::where('estabelecimento_id', $request->user()->estabelecimento_id)
            ->where(fn (Builder $query): Builder => $query->whereDoesntHave('profissional')->orWhere('id', $profissional->user_id))
            ->orderBy('name')->get();

        return view('profissionais.form', compact('profissional', 'usuarios'));
    }

    public function store(StoreProfissionalRequest $request): ProfissionalResource|RedirectResponse
    {
        $profissional = new Profissional($request->validated());
        $profissional->estabelecimento_id = $request->user()->estabelecimento_id;
        $profissional->save();

        return $request->expectsJson()
        ? new ProfissionalResource($profissional->refresh())
        : redirect()
            ->route('profissionais.index')
            ->with(
                'status',
                'Profissional cadastrado com sucesso.'
            );
    }

    public function show(Profissional $profissional): ProfissionalResource
    {
        Gate::authorize('view', $profissional);

        return new ProfissionalResource($profissional);
    }

    public function update(UpdateProfissionalRequest $request, Profissional $profissional): ProfissionalResource|RedirectResponse
    {
        $profissional->update($request->validated());

        return $request->expectsJson()
            ? new ProfissionalResource($profissional->refresh())
            : redirect()
                ->route('profissionais.index')
                ->with('status', 'Profissional atualizado com sucesso.');
    }

    public function toggleStatus(Request $request, Profissional $profissional): ProfissionalResource|RedirectResponse
    {
        Gate::authorize('update', $profissional);

        $resource = DB::transaction(function () use ($profissional): ProfissionalResource {
            $profissional = Profissional::query()->where('estabelecimento_id', $profissional->estabelecimento_id)
                ->lockForUpdate()->findOrFail($profissional->id);
            $profissional->active = ! $profissional->active;
            $profissional->save();

            return new ProfissionalResource($profissional);
        });

        return $request->expectsJson()
            ? $resource
            : redirect()->route('profissionais.index')->with('status', 'Situação do profissional atualizada.');
    }
}
