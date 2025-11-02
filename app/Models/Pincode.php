<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pincode extends Model
{
    protected $fillable = ['pincode','city','state','country_id','latitude','longitude','active','meta'];
    protected $casts = ['meta'=>'array','active'=>'boolean','latitude'=>'float','longitude'=>'float'];

    public function country() {
        return $this->belongsTo(Country::class);
    }

    public function zones() {
        return $this->belongsToMany(Zone::class, 'zone_pincode');
    }

}
