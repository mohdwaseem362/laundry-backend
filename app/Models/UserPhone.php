<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPhone extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id','phone','type','is_primary','verified_at','meta'];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
