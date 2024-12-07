<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chirp extends Model
{
    protected $fillable = [
        'message',
    ];
    //
    public function chirps(): HasMany
    {
        return $this->hasMany(Chirp::class);
    }
}
