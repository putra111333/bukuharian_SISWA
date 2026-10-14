<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = ['school_id', 'classroom_id', 'created_by', 'title', 'description', 'subject', 'due_date'];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
