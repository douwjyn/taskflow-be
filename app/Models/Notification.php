<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $guarded = [];

    public function teams() {
        return $this->belongsToMany(Team::class);
    }
}
