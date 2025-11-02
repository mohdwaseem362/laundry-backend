<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'country_id',
        'lat',
        'lng',
        'radius_km',
        'active',
        'launch_date',
        'capacity_limit',
        'meta',
    ];

    protected $casts = [
        'active' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
        'radius_km' => 'float',
        'launch_date' => 'datetime',
        'meta' => 'array',
    ];

    /* ---------- Relations ---------- */

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function pincodes()
    {
        return $this->belongsToMany(Pincode::class, 'zone_pincode');
    }

    public function agents()
    {
        // optional — if each agent has a zone_id
        return $this->hasMany(User::class, 'zone_id')->whereHas('roles', fn($q) => $q->where('name', 'Agent'));
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    public function scopeLaunched($q)
    {
        return $q->where(function ($sub) {
            $sub->whereNull('launch_date')->orWhere('launch_date', '<=', now());
        });
    }

    /* ---------- Helpers ---------- */

    public function toggleActive(): void
    {
        $this->active = !$this->active;
        $this->save();
    }

    public function isLaunched(): bool
    {
        return !$this->launch_date || $this->launch_date->isPast();
    }
}
