<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerStat extends Model
{
    public function fixture() { return $this->belongsTo(Fixture::class); }
public function player()  { return $this->belongsTo(Player::class); }
}
