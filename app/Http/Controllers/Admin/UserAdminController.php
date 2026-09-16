<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserAdminRequest;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->with(['position', 'departments', 'factories'])
                ->where('is_approved', true)
                ->latest('created_at')
                ->paginate(10, ['id', 'name', 'email', 'nik', 'avatar_url', 'role', 'position_id', 'department_id', 'active', 'created_at'])
                ->through(fn (User $user): User => $user->makeVisible(['nik']))
                ->withQueryString(),
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => $user->load(['position', 'departments', 'factories']),
            'positions' => Position::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UserAdminRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        abort_if($request->user()->is($user) && $data['role'] !== 'admin', 422, 'Admin tidak dapat menurunkan role sendiri.');
        $departmentIds = $data['department_ids'] ?? null;
        unset($data['department_ids'], $data['factory_ids']);
        if ($departmentIds) {
            $data['department_id'] = $departmentIds[0];
        }

        DB::transaction(function () use ($data, $departmentIds, $request, $user): void {
            $role = $data['role'];
            unset($data['role']);
            $user->update([...$data, 'role' => $role]);
            $user->syncRoles([$role]);
            if ($departmentIds !== null) {
                $user->departments()->sync($departmentIds);
            }
            $user->factories()->sync($request->validated('factory_ids', []));
        });

        return to_route('admin.users.index')->with('success', 'User diperbarui.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['active' => ! $user->active]);

        return back()->with('success', match ($user->active) {
            true => 'User aktif kembali.',
            false => 'User diinaktif.',
        });
    }
}
