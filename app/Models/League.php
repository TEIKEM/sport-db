<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
   public function sport()    { return $this->belongsTo(Sport::class); }
public function seasons()  { return $this->hasMany(Season::class); }
public function fixtures() { return $this->hasMany(Fixture::class); }
}
