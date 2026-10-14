<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolAnnouncement extends Model
{
    protected $fillable = ['school_id', 'created_by', 'title', 'category', 'body'];
}
