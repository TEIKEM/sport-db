<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    protected $casts = ['kickoff_at' => 'datetime'];

public function league()   { return $this->belongsTo(League::class); }
public function season()   { return $this->belongsTo(Season::class); }
public function homeTeam() { return $this->belongsTo(Team::class, 'home_team_id'); }
public function awayTeam() { return $this->belongsTo(Team::class, 'away_team_id'); }
public function referee()  { return $this->belongsTo(Referee::class); }

public function stats()       { return $this->hasMany(FixtureStat::class); }
public function events()      { return $this->hasMany(FixtureEvent::class); }
public function playerStats() { return $this->hasMany(PlayerStat::class); }
public function odds()        { return $this->hasMany(Odd::class); }
public function trades()      { return $this->hasMany(Trade::class); }
}
