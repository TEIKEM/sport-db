<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odd extends Model
{
    // IMPORTANT : la table odds n'a pas de colonnes created_at / updated_at
public $timestamps = false;

protected $casts = ['recorded_at' => 'datetime'];

public function fixture() { return $this->belongsTo(Fixture::class); }
}
