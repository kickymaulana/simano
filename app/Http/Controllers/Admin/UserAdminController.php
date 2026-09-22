<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserAdminRequest;
use App\Models\Department;
use App\Models\EvaluationTemplate;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'nik_ktp' => ['nullable', 'boolean'],
            'role' => ['nullable', 'in:employee,hr,admin'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'factory_id' => ['nullable', 'integer', 'exists:factories,id'],
            'evaluation_template_id' => ['nullable', 'integer', 'exists:evaluation_templates,id'],
            'active' => ['nullable', 'boolean'],
        ]);

        return Inertia::render('Admin/Users/Index', [
            'filters' => $filters,
            'positions' => Position::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
            'evaluationTemplates' => EvaluationTemplate::query()->orderBy('target_category')->get(['id', 'target_category', 'active']),
            'users' => User::query()
                ->with(['position', 'departments', 'factories', 'evaluationTemplate'])
                ->where('is_approved', true)
                ->when($filters['q'] ?? null, fn ($query, string $q) => $query->where(fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('nik', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
                ->when($filters['nik_ktp'] ?? false, fn ($query) => $query->whereRaw("nik REGEXP '^[0-9]{16}$'"))
                ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
                ->when($filters['position_id'] ?? null, fn ($query, int $id) => $query->where('position_id', $id))
                ->when($filters['department_id'] ?? null, fn ($query, int $id) => $query->whereHas('departments', fn ($query) => $query->whereKey($id)))
                ->when($filters['factory_id'] ?? null, fn ($query, int $id) => $query->whereHas('factories', fn ($query) => $query->whereKey($id)))
                ->when($filters['evaluation_template_id'] ?? null, fn ($query, int $id) => $query->where('evaluation_template_id', $id))
                ->when(array_key_exists('active', $filters) && $filters['active'] !== null, fn ($query) => $query->where('active', $filters['active']))
                ->latest('created_at')
                ->paginate(10, ['id', 'name', 'email', 'nik', 'avatar_url', 'role', 'position_id', 'department_id', 'evaluation_template_id', 'active', 'created_at'])
                ->through(fn (User $user): User => $user->makeVisible(['nik']))
                ->withQueryString(),
        ]);
    }

    public function summary(): Response
    {
        $baseUsers = DB::table('users')->where('users.is_approved', true)->where('users.active', true);
        $factories = Factory::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn (Factory $factory): array => [
            'id' => $factory->id,
            'name' => $factory->name,
            'user_count' => (clone $baseUsers)->join('factory_user', 'users.id', '=', 'factory_user.user_id')->where('factory_user.factory_id', $factory->id)->count('users.id'),
            'departments' => (clone $baseUsers)->join('factory_user', 'users.id', '=', 'factory_user.user_id')->join('department_user', 'users.id', '=', 'department_user.user_id')->join('departments', 'department_user.department_id', '=', 'departments.id')->where('factory_user.factory_id', $factory->id)->select(['departments.id', 'departments.name'])->selectRaw('count(distinct users.id) as user_count')->groupBy('departments.id', 'departments.name')->orderBy('departments.name')->get()->map(function (object $department) use ($baseUsers, $factory): array {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'user_count' => $department->user_count,
                    'positions' => (clone $baseUsers)->join('factory_user', 'users.id', '=', 'factory_user.user_id')->join('department_user', 'users.id', '=', 'department_user.user_id')->join('positions', 'users.position_id', '=', 'positions.id')->where('factory_user.factory_id', $factory->id)->where('department_user.department_id', $department->id)->select(['positions.id', 'positions.name'])->selectRaw('count(distinct users.id) as user_count')->groupBy('positions.id', 'positions.name')->orderBy('positions.name')->get(),
                ];
            }),
        ]);

        return Inertia::render('Admin/Users/Summary', ['factories' => $factories]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => $user->load(['position', 'departments', 'factories'])->makeVisible(['nik']),
            'positions' => Position::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
            'evaluationTemplates' => EvaluationTemplate::query()->orderBy('target_category')->get(['id', 'target_category', 'active']),
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

    public function bulkTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'template_id' => ['required', 'integer', 'exists:evaluation_templates,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:employee,hr,admin'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'factory_id' => ['nullable', 'integer', 'exists:factories,id'],
            'evaluation_template_id' => ['nullable', 'integer', 'exists:evaluation_templates,id'],
            'active' => ['nullable', 'boolean'],
        ]);

        abort_if(collect($data)->except('template_id')->filter(fn ($value) => $value !== null && $value !== '')->isEmpty(), 422, 'Pilih minimal satu filter sebelum mengatur template massal.');
        abort_unless(EvaluationTemplate::query()->whereKey($data['template_id'])->where('active', true)->exists(), 422, 'Template evaluasi harus aktif.');

        $updated = User::query()
            ->where('is_approved', true)
            ->when($data['q'] ?? null, fn ($query, string $q) => $query->where(fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('nik', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when($data['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when($data['position_id'] ?? null, fn ($query, int $id) => $query->where('position_id', $id))
            ->when($data['department_id'] ?? null, fn ($query, int $id) => $query->whereHas('departments', fn ($query) => $query->whereKey($id)))
            ->when($data['factory_id'] ?? null, fn ($query, int $id) => $query->whereHas('factories', fn ($query) => $query->whereKey($id)))
            ->when($data['evaluation_template_id'] ?? null, fn ($query, int $id) => $query->where('evaluation_template_id', $id))
            ->when(array_key_exists('active', $data) && $data['active'] !== null, fn ($query) => $query->where('active', $data['active']))
            ->update(['evaluation_template_id' => $data['template_id']]);

        return back()->with('success', "Template evaluasi diterapkan ke {$updated} user.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'Admin tidak dapat menghapus akun sendiri.');

        DB::transaction(function () use ($user): void {
            $user->submittedEvaluations()->delete();
            $user->receivedEvaluations()->delete();
            $user->departments()->detach();
            $user->requestedDepartments()->detach();
            $user->factories()->detach();
            $user->requestedFactories()->detach();
            $user->roles()->detach();
            $user->permissions()->detach();
            $user->delete();
        });

        return to_route('admin.users.index')->with('success', 'User dan seluruh relasinya berhasil dihapus.');
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
