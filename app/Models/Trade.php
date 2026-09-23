<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    public function fixture() { return $this->belongsTo(Fixture::class); }
}
