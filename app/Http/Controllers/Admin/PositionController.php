<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PositionRequest;
use App\Models\Position;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Positions/Index', [
            'positions' => Position::query()->orderBy('level')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Positions/Form', ['position' => null]);
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        $position = Position::create($request->validated());
        $this->audit->record('position.created', $position, $position->only(['name', 'level']));

        return to_route('admin.positions.index')->with('success', 'Jabatan dibuat.');
    }

    public function edit(Position $position): Response
    {
        return Inertia::render('Admin/Positions/Form', ['position' => $position]);
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());
        $this->audit->record('position.updated', $position->fresh(), $position->only(['name', 'level']));

        return to_route('admin.positions.index')->with('success', 'Jabatan diperbarui.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $snapshot = $position->only(['name', 'level']);
        $position->delete();
        $this->audit->record('position.deleted', null, $snapshot);

        return to_route('admin.positions.index')->with('success', 'Jabatan dihapus.');
    }
}
