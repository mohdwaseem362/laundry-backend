<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    // Prefer explicit fillable fields for security
    protected $fillable = [
        'order_number',
        'user_id',
        'agent_id',
        'status',
        'services',
        'subtotal',
        'tax',
        'total',
        'pickup_at',
        'delivery_at',
        'notes',
    ];

    protected $casts = [
        'services' => 'array',
        'pickup_at' => 'datetime',
        'delivery_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($order) {
            $order->conversation()->create([
                'channel' => 'web',
            ]);
        });
    }


    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function conversation()
    {
        return $this->morphOne(Conversation::class, 'owner');
    }
}
