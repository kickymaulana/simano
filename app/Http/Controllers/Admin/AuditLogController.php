<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditLogRequest;
use App\Models\AuditLog;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(AuditLogRequest $request): Response
    {
        $logs = AuditLog::query()
            ->with('actor:id,name,role')
            ->when($request->filled('action'), fn ($query) => $query->where('action', 'like', '%'.$request->string('action')->toString().'%'))
            ->when($request->filled('actor'), fn ($query) => $query->where('actor_id', $request->integer('actor')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return Inertia::render('Admin/AuditLogs/Index', [
            'filters' => $request->only(['action', 'actor', 'from', 'to']),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action')->values(),
            'actors' => User::query()->whereIn('id', AuditLog::query()->whereNotNull('actor_id')->select('actor_id'))->orderBy('name')->get(['id', 'name', 'role']),
            'logs' => $logs->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => $log->actor?->only(['name', 'role']),
                'auditable_type' => $log->auditable_type ? class_basename($log->auditable_type) : null,
                'created_at' => $log->created_at->toIso8601String(),
            ]),
        ]);
    }
}
