<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfissionalRequest;
use App\Http\Requests\UpdateProfissionalRequest;
use App\Http\Resources\ProfissionalResource;
use App\Models\Profissional;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProfissionalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Profissional::class);

        return ProfissionalResource::collection(Profissional::query()
            ->where('estabelecimento_id', $request->user()->estabelecimento_id)
            ->orderBy('id')->paginate(15));
    }

    public function store(StoreProfissionalRequest $request): ProfissionalResource
    {
        $profissional = new Profissional($request->validated());
        $profissional->estabelecimento_id = $request->user()->estabelecimento_id;
        $profissional->save();

        return new ProfissionalResource($profissional->refresh());
    }

    public function show(Profissional $profissional): ProfissionalResource
    {
        Gate::authorize('view', $profissional);

        return new ProfissionalResource($profissional);
    }

    public function update(UpdateProfissionalRequest $request, Profissional $profissional): ProfissionalResource
    {
        $profissional->update($request->validated());

        return new ProfissionalResource($profissional->refresh());
    }

    public function toggleStatus(Profissional $profissional): ProfissionalResource
    {
        Gate::authorize('update', $profissional);

        return DB::transaction(function () use ($profissional): ProfissionalResource {
            $profissional = Profissional::query()->where('estabelecimento_id', $profissional->estabelecimento_id)
                ->lockForUpdate()->findOrFail($profissional->id);
            $profissional->active = ! $profissional->active;
            $profissional->save();

            return new ProfissionalResource($profissional);
        });
    }
}
