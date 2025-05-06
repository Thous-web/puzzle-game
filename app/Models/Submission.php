<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'puzzle_id',
        'student_name',
        'remaining_letters',
        'used_words',
        'is_completed',
    ];

  
}
