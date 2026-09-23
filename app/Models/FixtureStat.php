<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixtureStat extends Model
{
    public function fixture() { return $this->belongsTo(Fixture::class); }
public function team()    { return $this->belongsTo(Team::class); }
}
