<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['school_id', 'classroom_id', 'nisn', 'name', 'date_of_birth', 'address', 'phone'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function accounts()
    {
        return $this->hasMany(User::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function notes()
    {
        return $this->hasMany(StudentNote::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
