<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentBill extends Model
{
    protected $fillable = ['school_id', 'student_id', 'created_by', 'title', 'amount', 'due_date', 'status'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'amount' => 'integer'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
