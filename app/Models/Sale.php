<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = ['product_id', 'region', 'date', 'quantity', 'revenue'];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'revenue' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
