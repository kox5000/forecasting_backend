<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Forecast extends Model
{
    protected $fillable = ['product_id', 'region', 'date', 'predicted_revenue', 'model_version', 'status', 'input_data', 'result', 'started_at', 'completed_at', 'job_id'];

    protected $casts = [
        'date' => 'date',
        'predicted_revenue' => 'decimal:2',
        'input_data' => 'array',
        'result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
