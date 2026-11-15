<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternshipPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_profile_id',
        'title',
        'slug',
        'category',
        'description',
        'responsibilities',
        'requirements',
        'location',
        'type',
        'opportunity_type',
        'experience_level',
        'duration_weeks',
        'stipend',
        'salary_max',
        'is_stipend_disclosed',
        'slots',
        'deadline',
        'status',
        'rejection_reason',
    ];

    protected $appends = [
        'is_internship',
        'is_job',
        'opportunity_type_label',
        'compensation_display',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'duration_weeks' => 'integer',
            'slots' => 'integer',
            'stipend' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'is_stipend_disclosed' => 'boolean',
        ];
    }

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->whereDate('deadline', '>=', now());
    }

    /**
     * Scope a query to posts recommended for a given student based on major and skills.
     */
    public function scopeRecommendedFor(Builder $query, ?StudentProfile $student): Builder
    {
        // Must be approved and not yet expired
        $query->where('status', 'approved')
              ->whereDate('deadline', '>=', now()->toDateString());

        if (!$student) {
            return $query->latest();
        }

        // Exclude positions the student has already applied to
        $query->whereDoesntHave('applications', function ($q) use ($student) {
            $q->where('student_profile_id', $student->id);
        });

        // Collect keywords from student major and skills
        $keywords = $student->getRecommendationKeywords();

        if (empty($keywords)) {
            return $query->latest();
        }

        // Filter posts that match any of the student's skills or major keywords
        return $query->where(function ($sub) use ($keywords) {
            foreach ($keywords as $keyword) {
                $sub->orWhere('title', 'like', "%{$keyword}%")
                    ->orWhere('category', 'like', "%{$keyword}%")
                    ->orWhere('requirements', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            }
        });
    }

    public function scopeOpportunityType(Builder $query, ?string $type): Builder
    {
        if (empty($type) || $type === 'all') {
            return $query;
        }

        if ($type === 'job' || $type === 'jobs') {
            return $query->where('opportunity_type', '!=', 'internship');
        }

        return $query->where('opportunity_type', $type);
    }

    public function scopeExperienceLevel(Builder $query, ?string $level): Builder
    {
        if (empty($level) || $level === 'all') {
            return $query;
        }

        return $query->where('experience_level', $level);
    }

    public function scopePostedWithin(Builder $query, ?string $period): Builder
    {
        return match ($period) {
            '24h', 'today' => $query->where('created_at', '>=', now()->subDay()),
            '7d', 'week'   => $query->where('created_at', '>=', now()->subDays(7)),
            '30d', 'month' => $query->where('created_at', '>=', now()->subDays(30)),
            default        => $query,
        };
    }

    public function scopeInternships(Builder $query): Builder
    {
        return $query->where('opportunity_type', 'internship');
    }

    public function scopeJobs(Builder $query): Builder
    {
        return $query->where('opportunity_type', '!=', 'internship');
    }

    public function isInternship(): bool
    {
        return ($this->opportunity_type ?? 'internship') === 'internship';
    }

    public function isJob(): bool
    {
        return !$this->isInternship();
    }

    public function getIsInternshipAttribute(): bool
    {
        return $this->isInternship();
    }

    public function getIsJobAttribute(): bool
    {
        return $this->isJob();
    }

    public function getOpportunityTypeLabelAttribute(): string
    {
        return match ($this->opportunity_type) {
            'full_time' => 'Full-Time Job',
            'part_time' => 'Part-Time Job',
            'contract'  => 'Contract Job',
            default     => 'Internship',
        };
    }

    public function getExperienceLevelLabelAttribute(): ?string
    {
        return match ($this->experience_level) {
            'no_experience' => 'No Experience',
            'entry_level'   => 'Entry Level',
            'junior'        => 'Junior (1-2 yrs)',
            'mid_level'     => 'Mid Level (3+ yrs)',
            default         => null,
        };
    }

    public function getCompensationDisplayAttribute(): string
    {
        if (!$this->is_stipend_disclosed) {
            return $this->isJob() ? 'Negotiable' : 'Unpaid / Experience';
        }

        $min = (float) ($this->stipend ?? 0);
        $max = (float) ($this->salary_max ?? 0);

        if ($min > 0 && $max > $min) {
            return '$' . number_format($min, 0) . ' - $' . number_format($max, 0) . '/mo';
        }

        if ($min > 0) {
            return '$' . number_format($min, 0) . '/mo';
        }

        return $this->isJob() ? 'Competitive' : 'Unpaid / Experience';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isOpen(): bool
    {
        return $this->status === 'approved' && $this->deadline >= now()->toDateString();
    }
}
