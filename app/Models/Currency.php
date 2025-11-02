<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['code','symbol','name','decimals','active','meta'];
    protected $casts = ['meta'=>'array','active'=>'boolean'];
}
