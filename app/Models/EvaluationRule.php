<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationRule extends Model
{
    protected $fillable = [
        'evaluator_position_id',
        'target_position_id',
        'target_category',
        'same_department',
        'same_factory',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'target_category' => 'string',
            'same_department' => 'boolean',
            'same_factory' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function evaluatorPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'evaluator_position_id');
    }

    public function targetPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'target_position_id');
    }
}
