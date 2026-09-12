<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::prefix('sso')->name('sso.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Sso\SsoController::class, 'login'])->name('login');
    Route::get('/callback', [\App\Http\Controllers\Sso\SsoController::class, 'callback'])->name('callback');
});

Route::get('/pending-role', [\App\Http\Controllers\Sso\PendingRoleController::class, 'create'])->name('pending-role');
Route::post('/pending-role', [\App\Http\Controllers\Sso\PendingRoleController::class, 'store'])->name('pending-role.store');

Route::middleware(['auth', 'role:employee|admin|hr'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/targets', [\App\Http\Controllers\Employee\TargetController::class, 'index'])->name('targets.index');
    Route::get('/targets/{target}/evaluation', [\App\Http\Controllers\Employee\EvaluationController::class, 'create'])->name('evaluations.create');
    Route::post('/evaluations', [\App\Http\Controllers\Employee\EvaluationController::class, 'store'])->name('evaluations.store');
    Route::get('/evaluations/status', [\App\Http\Controllers\Employee\EvaluationStatusController::class, 'index'])->name('evaluations.status');
});

Route::middleware(['auth', 'role:admin|hr'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Admin/Dashboard'))->name('dashboard');
    Route::get('/pending-users', [\App\Http\Controllers\Admin\PendingUserController::class, 'index'])->name('pending-users.index');
    Route::post('/pending-users/{user}/approve', [\App\Http\Controllers\Admin\PendingUserController::class, 'approve'])->name('pending-users.approve');
    Route::post('/pending-users/{user}/reject', [\App\Http\Controllers\Admin\PendingUserController::class, 'reject'])->name('pending-users.reject');
    Route::get('/reports/evaluations', [\App\Http\Controllers\Admin\EvaluationReportController::class, 'index'])->name('reports.evaluations');
    Route::get('/reports/evaluations/export', [\App\Http\Controllers\Admin\EvaluationReportController::class, 'export'])->name('reports.evaluations.export');
    Route::get('/reports/evaluations/export/pdf', [\App\Http\Controllers\Admin\EvaluationReportController::class, 'exportPdf'])->name('reports.evaluations.export.pdf');
    Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/reports/evaluations/trend', [\App\Http\Controllers\Admin\EvaluationReportController::class, 'trend'])->name('reports.evaluations.trend');
    Route::resource('evaluation-periods', \App\Http\Controllers\Admin\EvaluationPeriodController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('evaluation-templates', \App\Http\Controllers\Admin\EvaluationTemplateController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
});

Route::post('/logout', [\App\Http\Controllers\Sso\SsoController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
