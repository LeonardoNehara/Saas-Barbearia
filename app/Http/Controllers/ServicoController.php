<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServicoRequest;
use App\Http\Requests\SyncServicoProfissionaisRequest;
use App\Http\Requests\UpdateServicoRequest;
use App\Http\Resources\ServicoResource;
use App\Models\Servico;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ServicoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Servico::class);

        return ServicoResource::collection(Servico::query()
            ->where('estabelecimento_id', $request->user()->estabelecimento_id)
            ->orderBy('id')->paginate(15));
    }

    public function store(StoreServicoRequest $request): ServicoResource
    {
        $servico = new Servico($request->validated());
        $servico->estabelecimento_id = $request->user()->estabelecimento_id;
        $servico->save();

        return new ServicoResource($servico->refresh());
    }

    public function show(Servico $servico): ServicoResource
    {
        Gate::authorize('view', $servico);

        return new ServicoResource($servico->load('profissionais'));
    }

    public function update(UpdateServicoRequest $request, Servico $servico): ServicoResource
    {
        $servico->update($request->validated());

        return new ServicoResource($servico->refresh());
    }

    public function toggleStatus(Servico $servico): ServicoResource
    {
        Gate::authorize('update', $servico);

        return DB::transaction(function () use ($servico): ServicoResource {
            $servico = Servico::query()->where('estabelecimento_id', $servico->estabelecimento_id)
                ->lockForUpdate()->findOrFail($servico->id);
            $servico->active = ! $servico->active;
            $servico->save();

            return new ServicoResource($servico);
        });
    }

    public function syncProfissionais(SyncServicoProfissionaisRequest $request, Servico $servico): ServicoResource
    {
        return DB::transaction(function () use ($request, $servico): ServicoResource {
            $servico = Servico::query()->where('estabelecimento_id', $request->user()->estabelecimento_id)
                ->lockForUpdate()->findOrFail($servico->id);
            $servico->profissionais()->sync($request->validated('profissionais'));

            return new ServicoResource($servico->load('profissionais'));
        });
    }
}
