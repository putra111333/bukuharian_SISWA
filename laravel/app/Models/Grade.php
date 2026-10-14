<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['school_id', 'student_id', 'created_by', 'subject', 'assessment', 'score'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
