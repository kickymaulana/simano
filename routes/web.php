<?php

use App\Http\Controllers\Admin\AtasanEvaluationReportController;
use App\Http\Controllers\Admin\AttentionController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EvaluationParticipationController;
use App\Http\Controllers\Admin\EvaluationPeriodController;
use App\Http\Controllers\Admin\EvaluationReportController;
use App\Http\Controllers\Admin\EvaluationTemplateController;
use App\Http\Controllers\Admin\FactoryController;
use App\Http\Controllers\Admin\OrganizationScorecardController;
use App\Http\Controllers\Admin\PendingUserController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\QuestionAnalysisController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Employee\EvaluationController;
use App\Http\Controllers\Employee\EvaluationStatusController;
use App\Http\Controllers\Employee\TargetController;
use App\Http\Controllers\Sso\PendingRoleController;
use App\Http\Controllers\Sso\SsoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::prefix('sso')->name('sso.')->group(function () {
    Route::get('/login', [SsoController::class, 'login'])->name('login');
    Route::get('/callback', [SsoController::class, 'callback'])->name('callback');
});

Route::get('/pending-role', [PendingRoleController::class, 'create'])->name('pending-role');
Route::post('/pending-role', [PendingRoleController::class, 'store'])->name('pending-role.store');

Route::middleware(['auth', 'role:employee|admin|hr'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/targets', [TargetController::class, 'index'])->name('targets.index');
    Route::get('/targets/{target}/evaluation', [EvaluationController::class, 'create'])->name('evaluations.create');
    Route::post('/evaluations', [EvaluationController::class, 'store'])->name('evaluations.store');
    Route::get('/evaluations/status', [EvaluationStatusController::class, 'index'])->name('evaluations.status');
});

Route::middleware(['auth', 'role:admin|hr'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/evaluation-participation', EvaluationParticipationController::class)->name('evaluation-participation.index');
    Route::get('/question-analysis', QuestionAnalysisController::class)->name('question-analysis.index');
    Route::get('/organization-scorecard', OrganizationScorecardController::class)->name('organization-scorecard.index');
    Route::get('/attention', AttentionController::class)->name('attention.index');
    Route::get('/pending-users', [PendingUserController::class, 'index'])->name('pending-users.index');
    Route::get('/pending-users/{user}/edit', [PendingUserController::class, 'edit'])->name('pending-users.edit');
    Route::put('/pending-users/{user}', [PendingUserController::class, 'update'])->name('pending-users.update');
    Route::post('/pending-users/{user}/approve', [PendingUserController::class, 'approve'])->name('pending-users.approve');
    Route::post('/pending-users/{user}/reject', [PendingUserController::class, 'reject'])->name('pending-users.reject');
    Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
    Route::post('/users/bulk-template', [UserAdminController::class, 'bulkTemplate'])->name('users.bulk-template');
    Route::get('/users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserAdminController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/toggle', [UserAdminController::class, 'toggleActive'])->name('users.toggle');
    Route::get('/reports/evaluations', [EvaluationReportController::class, 'index'])->name('reports.evaluations');
    Route::get('/reports/evaluations/atasan', AtasanEvaluationReportController::class)->name('reports.evaluations.atasan');
    Route::get('/reports/evaluations/atasan/evaluators', [AtasanEvaluationReportController::class, 'evaluators'])->name('reports.evaluations.atasan.evaluators');
    Route::delete('/reports/evaluations/atasan/{period}/{target}/evaluators/{evaluation}', [AtasanEvaluationReportController::class, 'destroy'])->name('reports.evaluations.atasan.evaluators.destroy');
    Route::get('/reports/evaluations/atasan/pdf', [AtasanEvaluationReportController::class, 'exportPdf'])->name('reports.evaluations.atasan.pdf');
    Route::get('/reports/evaluations/export', [EvaluationReportController::class, 'export'])->name('reports.evaluations.export');
    Route::get('/reports/evaluations/export/pdf', [EvaluationReportController::class, 'exportPdf'])->name('reports.evaluations.export.pdf');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/reports/evaluations/trend', [EvaluationReportController::class, 'trend'])->name('reports.evaluations.trend');
    Route::resource('evaluation-periods', EvaluationPeriodController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('evaluation-templates', EvaluationTemplateController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('positions', PositionController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('factories', FactoryController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('departments', DepartmentController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
});

Route::post('/logout', [SsoController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
