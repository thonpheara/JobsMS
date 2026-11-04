<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    protected $table = 'student_bookmarks';

    protected $fillable = [
        'student_profile_id',
        'internship_post_id',
    ];

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function internshipPost(): BelongsTo
    {
        return $this->belongsTo(InternshipPost::class);
    }
}

