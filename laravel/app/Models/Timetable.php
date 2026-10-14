<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    protected $fillable = ['school_id', 'classroom_id', 'created_by', 'subject', 'teacher', 'weekday', 'starts_at', 'ends_at', 'room'];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
