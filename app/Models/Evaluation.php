<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = [
        'evaluator_id',
        'target_id',
        'evaluation_template_id',
        'evaluation_period_id',
        'target_category',
        'average_score',
        'submitted_at',
    ];

    protected $hidden = [
        'evaluator_id',
    ];

    protected function casts(): array
    {
        return [
            'average_score' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EvaluationTemplate::class, 'evaluation_template_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(EvaluationDetail::class);
    }
}
