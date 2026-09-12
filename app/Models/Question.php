<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'evaluation_template_id',
        'question_number',
        'question_text',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EvaluationTemplate::class, 'evaluation_template_id');
    }
}
