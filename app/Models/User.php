<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['nik', 'name', 'avatar_url', 'role', 'position_id', 'department_id', 'requested_position_id', 'requested_department_id', 'active', 'is_approved', 'requested_role', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'nik'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    public function submittedEvaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'evaluator_id');
    }

    public function receivedEvaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'target_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user');
    }

    public function requestedDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'requested_department_user');
    }

    public function requestedPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'requested_position_id');
    }

    public function requestedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'requested_department_id');
    }

    public function factories(): BelongsToMany
    {
        return $this->belongsToMany(Factory::class, 'factory_user');
    }

    public function requestedFactories(): BelongsToMany
    {
        return $this->belongsToMany(Factory::class, 'requested_factory_user');
    }
}
