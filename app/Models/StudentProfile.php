<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'student_id_number',
        'department',
        'major',
        'cohort_year',
        'gpa',
        'resume_path',
        'phone',
        'skills',
        'bio',
        'eligibility_status',
        'eligibility_notes',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'gpa' => 'decimal:2',
            'cohort_year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function isEligible(): bool
    {
        return true;
    }

    public function getEligibilityStatusAttribute($value): string
    {
        return $value === 'pending' || empty($value) ? 'eligible' : $value;
    }

    public function getResumePathAttribute($value): ?string
    {
        return ($value === '0' || $value === '' || $value === null) ? null : $value;
    }

    /**
     * Get extracted keywords from major and skills for smart job recommendations.
     *
     * @return array<string>
     */
    public function getRecommendationKeywords(): array
    {
        $keywords = [];

        // 1. Add student skills
        if (is_array($this->skills)) {
            foreach ($this->skills as $skill) {
                $clean = trim((string) $skill);
                if (strlen($clean) >= 2) {
                    $keywords[] = $clean;
                }
            }
        }

        // 2. Add major phrase and significant words (>= 4 characters)
        if (!empty($this->major)) {
            $keywords[] = trim($this->major);
            $words = preg_split('/[\s,\/&]+/', $this->major);
            $stopWords = ['and', 'of', 'in', 'the', '&', 'for', 'with', 'studies', 'general', 'degree', 'bachelor'];
            foreach ($words as $word) {
                $word = trim($word);
                if (strlen($word) >= 4 && !in_array(strtolower($word), $stopWords)) {
                    $keywords[] = $word;
                }
            }
        }

        return array_values(array_unique(array_filter($keywords)));
    }

    protected $appends = [
        'has_resume',
    ];

    public function getHasResumeAttribute(): bool
    {
        return !empty($this->resume_path);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Bookmarked internship posts (Saved Jobs).
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkedPosts(): BelongsToMany
    {
        return $this->belongsToMany(
            InternshipPost::class,
            'student_bookmarks',
            'student_profile_id',
            'internship_post_id'
        )->withTimestamps();
    }

    /**
     * Check if a specific post is bookmarked by this student.
     */
    public function hasBookmarked(int $postId): bool
    {
        return $this->bookmarks()->where('internship_post_id', $postId)->exists();
    }
}
