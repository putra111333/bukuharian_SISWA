<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolEvent extends Model
{
    protected $fillable = ['school_id', 'created_by', 'title', 'description', 'starts_at', 'location'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }
}
