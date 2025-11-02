<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAddress extends Model
{
    use SoftDeletes;

    protected $table = 'user_addresses';

    protected $fillable = [
        'user_id','label','line1','line2','city','state','pincode','country','lat','lng','is_primary','meta'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
