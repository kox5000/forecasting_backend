<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Insight extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'description', 'status', 'input_data', 'result', 'model_version', 'started_at', 'completed_at', 'job_id'];

    protected $casts = [
        'input_data' => 'array',
        'result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
