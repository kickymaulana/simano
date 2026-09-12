<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\TargetSearchRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class TargetController extends Controller
{
    public function index(TargetSearchRequest $request): Response
    {
        $query = User::query()
            ->where('active', true)
            ->where('id', '!=', $request->user()->getKey())
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = $request->string('q')->toString();
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('nik', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('role', $request->string('category')))
            ->orderBy('name')
            ->limit(30);

        $cacheKey = sprintf('targets:%d:%s:%s', $request->user()->getKey(), $request->string('q')->lower()->trim(), $request->string('category')->lower()->trim());

        return Inertia::render('Targets/Index', [
            'filters' => $request->only(['q', 'category']),
            'targets' => Cache::remember($cacheKey, now()->addSeconds(30), fn () => $query->get(['id', 'name', 'avatar_url', 'role'])),
        ]);
    }
}
