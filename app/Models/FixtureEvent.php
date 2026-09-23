<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixtureEvent extends Model
{
    public function fixture() { return $this->belongsTo(Fixture::class); }
public function team()    { return $this->belongsTo(Team::class); }
public function player()  { return $this->belongsTo(Player::class); }
}
