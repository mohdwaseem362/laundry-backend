<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['name','iso2','iso3','currency_id','timezone','locale','tax_rules','active','meta'];
    protected $casts = ['tax_rules'=>'array','meta'=>'array','active'=>'boolean'];

    public function currency() {
        return $this->belongsTo(Currency::class);
    }

    public function pincodes() {
        return $this->hasMany(Pincode::class);
    }
}
