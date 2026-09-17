<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexServicoRequest;
use App\Http\Requests\StoreServicoRequest;
use App\Http\Requests\SyncServicoProfissionaisRequest;
use App\Http\Requests\UpdateServicoRequest;
use App\Http\Resources\ServicoResource;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServicoController extends Controller
{
    public function index(IndexServicoRequest $request): AnonymousResourceCollection|View
    {
        $filters = $request->validated();
        $query = Servico::query()
            ->where('estabelecimento_id', $request->user()->estabelecimento_id)
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query->whereLike('nome', '%'.$filters['search'].'%')
                        ->orWhereLike('descricao', '%'.$filters['search'].'%');
                });
            })
            ->when(isset($filters['active']), fn (Builder $query): Builder => $query->where('active', $filters['active']));
        $servicos = $query->orderBy('id')->paginate($filters['per_page'] ?? ($request->expectsJson() ? 15 : 10))
            ->appends($request->safe()->except('page'));

        if ($request->expectsJson()) {
            return ServicoResource::collection($servicos);
        }

        $hasServices = Servico::where('estabelecimento_id', $request->user()->estabelecimento_id)->exists();

        return view('servicos.index', compact('servicos', 'filters', 'hasServices'));
    }

    public function create(): View
    {
        Gate::authorize('create', Servico::class);

        return view('servicos.form', ['servico' => null]);
    }

    public function edit(Request $request, Servico $servico): View
    {
        Gate::authorize('update', $servico);
        $servico->load('profissionais');
        $profissionais = Profissional::where('estabelecimento_id', $request->user()->estabelecimento_id)
            ->orderBy('nome')->get();

        return view('servicos.form', compact('servico', 'profissionais'));
    }

    public function store(StoreServicoRequest $request): ServicoResource|RedirectResponse
    {
        $servico = new Servico($request->validated());
        $servico->estabelecimento_id = $request->user()->estabelecimento_id;
        $servico->save();

        return $request->expectsJson()
            ? new ServicoResource($servico->refresh())
            : redirect()->route('servicos.edit', $servico)->with('status', 'Serviço cadastrado. Agora você pode selecionar os profissionais que o realizam.');
    }

    public function show(Servico $servico): ServicoResource
    {
        Gate::authorize('view', $servico);

        return new ServicoResource($servico->load('profissionais'));
    }

    public function update(UpdateServicoRequest $request, Servico $servico): ServicoResource|RedirectResponse
    {
        $servico->update($request->validated());

        return $request->expectsJson()
            ? new ServicoResource($servico->refresh())
            : redirect()->route('servicos.edit', $servico)->with('status', 'Serviço atualizado com sucesso.');
    }

    public function toggleStatus(Request $request, Servico $servico): ServicoResource|RedirectResponse
    {
        Gate::authorize('update', $servico);

        $resource = DB::transaction(function () use ($servico): ServicoResource {
            $servico = Servico::query()->where('estabelecimento_id', $servico->estabelecimento_id)
                ->lockForUpdate()->findOrFail($servico->id);
            $servico->active = ! $servico->active;
            $servico->save();

            return new ServicoResource($servico);
        });

        return $request->expectsJson()
            ? $resource
            : redirect()->route('servicos.index')->with('status', 'Situação do serviço atualizada.');
    }

    public function syncProfissionais(SyncServicoProfissionaisRequest $request, Servico $servico): ServicoResource|RedirectResponse
    {
        $resource = DB::transaction(function () use ($request, $servico): ServicoResource {
            $servico = Servico::query()->where('estabelecimento_id', $request->user()->estabelecimento_id)
                ->lockForUpdate()->findOrFail($servico->id);
            $servico->profissionais()->sync($request->validated('profissionais'));

            return new ServicoResource($servico->load('profissionais'));
        });

        return $request->expectsJson()
            ? $resource
            : redirect()->route('servicos.edit', $servico)->with('status', 'Profissionais do serviço atualizados.');
    }
}
