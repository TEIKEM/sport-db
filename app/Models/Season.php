<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    public function league()   { return $this->belongsTo(League::class); }
public function fixtures() { return $this->hasMany(Fixture::class); }
public function teams() { return $this->belongsToMany(Team::class)->withTimestamps(); }
}
