<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public function sport()   { return $this->belongsTo(Sport::class); }
public function players() { return $this->hasMany(Player::class); }
public function seasons() { return $this->belongsToMany(Season::class)->withTimestamps(); }
}
