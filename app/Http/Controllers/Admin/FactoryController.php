<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FactoryRequest;
use App\Models\Factory;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FactoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Factories/Index', [
            'factories' => Factory::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Factories/Form', ['factory' => null]);
    }

    public function store(FactoryRequest $request): RedirectResponse
    {
        $factory = Factory::create($request->validated());
        $this->audit->record('factory.created', $factory, $factory->only(['name']));

        return to_route('admin.factories.index')->with('success', 'Pabrik dibuat.');
    }

    public function edit(Factory $factory): Response
    {
        return Inertia::render('Admin/Factories/Form', ['factory' => $factory]);
    }

    public function update(FactoryRequest $request, Factory $factory): RedirectResponse
    {
        $factory->update($request->validated());
        $this->audit->record('factory.updated', $factory->fresh(), $factory->only(['name']));

        return to_route('admin.factories.index')->with('success', 'Pabrik diperbarui.');
    }

    public function destroy(Factory $factory): RedirectResponse
    {
        $snapshot = $factory->only(['name']);
        $factory->delete();
        $this->audit->record('factory.deleted', null, $snapshot);

        return to_route('admin.factories.index')->with('success', 'Pabrik dihapus.');
    }
}
