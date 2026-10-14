<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentNote extends Model
{
    protected $fillable = ['school_id', 'student_id', 'created_by', 'title', 'body', 'visible_to_family'];

    protected function casts(): array
    {
        return ['visible_to_family' => 'boolean'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
