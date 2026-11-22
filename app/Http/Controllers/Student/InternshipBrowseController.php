<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InternshipBrowseController extends Controller
{
    /**
     * Display a listing of available approved internship posts.
     */
    public function index(Request $request): View
    {
        $baseQuery = InternshipPost::with('companyProfile.user')->approved();

        // ── 1. Opportunity Type scope (all / internship / job / full_time / part_time / contract) ──
        $opportunityType = $request->input('opportunity_type', 'all');
        if ($opportunityType === 'job' || $opportunityType === 'jobs') {
            $baseQuery->where('opportunity_type', '!=', 'internship');
        } elseif (!empty($opportunityType) && $opportunityType !== 'all') {
            $baseQuery->where('opportunity_type', $opportunityType);
        }

        // ── 2. Deadline scope ──────────────────────────────────────────────
        $deadline = $request->input('deadline', 'open');   // open | soon | all
        if ($deadline === 'soon') {
            // Closing within the next 7 days
            $baseQuery->whereDate('deadline', '>=', now()->toDateString())
                      ->whereDate('deadline', '<=', now()->addDays(7)->toDateString());
        } elseif ($deadline !== 'all') {
            // Default: any post that hasn't expired yet
            $baseQuery->whereDate('deadline', '>=', now()->toDateString());
        }

        // ── 3. Search (title / description / requirements / location / company) ──
        if ($search = $request->input('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('requirements', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('companyProfile', fn ($c) =>
                      $c->where('company_name', 'like', "%{$search}%")
                  );
            });
        }

        // ── 4. Location specific filter ─────────────────────────────────────
        if ($location = $request->input('location')) {
            $baseQuery->where('location', 'like', "%{$location}%");
        }

        // ── 5. Category filter ─────────────────────────────────────────────
        if ($category = $request->input('category')) {
            $baseQuery->where('category', $category);
        }

        // ── 6. Work Mode filter (type: remote, on_site, hybrid) ────────────
        if ($workType = $request->input('type')) {
            $baseQuery->where('type', $workType);
        }

        // ── 7. Experience Level filter ─────────────────────────────────────
        if ($expLevel = $request->input('experience_level')) {
            if ($expLevel !== 'all') {
                $baseQuery->where('experience_level', $expLevel);
            }
        }

        // ── 8. Date Posted filter (24h, 7d, 30d) ───────────────────────────
        if ($datePosted = $request->input('date_posted')) {
            match ($datePosted) {
                '24h', 'today' => $baseQuery->where('created_at', '>=', now()->subDay()),
                '7d', 'week'   => $baseQuery->where('created_at', '>=', now()->subDays(7)),
                '30d', 'month' => $baseQuery->where('created_at', '>=', now()->subDays(30)),
                default        => null,
            };
        }

        // ── 9. Verified Companies Only filter ──────────────────────────────
        if ($request->boolean('verified_only')) {
            $baseQuery->whereHas('companyProfile', fn ($c) => $c->where('verification_status', 'verified'));
        }

        // ── 10. Stipend / Salary filter ────────────────────────────────────
        $stipend = $request->input('stipend', '');   // '' | has | none | min
        $stipendMin = $request->input('stipend_min') ?? $request->input('salary_min');
        $salaryMax = $request->input('salary_max');

        if ($stipend === 'has') {
            $baseQuery->where(function ($q) {
                $q->where(fn ($sq) => $sq->whereNotNull('stipend')->where('stipend', '>', 0))
                  ->orWhere(fn ($sq) => $sq->whereNotNull('salary_max')->where('salary_max', '>', 0));
            });
        } elseif ($stipend === 'none') {
            $baseQuery->where(function ($q) {
                $q->whereNull('stipend')->orWhere('stipend', 0);
            });
        }

        if (is_numeric($stipendMin) && $stipendMin >= 0) {
            $baseQuery->where(function ($q) use ($stipendMin) {
                $q->where('stipend', '>=', (float) $stipendMin)
                  ->orWhere('salary_max', '>=', (float) $stipendMin);
            });
        }

        if (is_numeric($salaryMax) && $salaryMax > 0) {
            $baseQuery->where(function ($q) use ($salaryMax) {
                $q->where('stipend', '<=', (float) $salaryMax)
                  ->orWhere('salary_max', '<=', (float) $salaryMax);
            });
        }

        // ── 11. Sort ───────────────────────────────────────────────────────
        $sort = $request->input('sort', 'newest');   // newest | deadline | stipend / salary_high | salary_low

        match ($sort) {
            'deadline'    => $baseQuery->orderBy('deadline', 'asc'),
            'stipend',
            'salary_high' => $baseQuery->orderByRaw('COALESCE(salary_max, stipend, 0) DESC')->orderBy('created_at', 'desc'),
            'salary_low'  => $baseQuery->orderByRaw('COALESCE(stipend, salary_max, 0) ASC')->orderBy('created_at', 'desc'),
            default       => $baseQuery->latest(),           // newest
        };

        $posts = $baseQuery->get();

        // ── 12. Bookmarks & Metrics ────────────────────────────────────────
        $student = Auth::user()->studentProfile;
        $bookmarkedPostIds = $student
            ? $student->bookmarks()->pluck('internship_post_id')->toArray()
            : [];

        // Count metrics for Opportunity Type tabs
        $activeBase = InternshipPost::approved()->whereDate('deadline', '>=', now()->toDateString());
        $counts = [
            'all'         => (clone $activeBase)->count(),
            'internships' => (clone $activeBase)->where('opportunity_type', 'internship')->count(),
            'jobs'        => (clone $activeBase)->where('opportunity_type', '!=', 'internship')->count(),
            'full_time'   => (clone $activeBase)->where('opportunity_type', 'full_time')->count(),
            'part_time'   => (clone $activeBase)->where('opportunity_type', 'part_time')->count(),
        ];

        return view('student.posts.index', compact('posts', 'bookmarkedPostIds', 'counts'));
    }

    /**
     * Show detailed internship post.
     */
    public function show(InternshipPost $post): View
    {
        $student = Auth::user()->studentProfile;

        // Check if student has already applied
        $existingApplication = Application::where('student_profile_id', $student?->id)
            ->where('internship_post_id', $post->id)
            ->first();

        // Check if post is bookmarked
        $isBookmarked = $student ? $student->hasBookmarked($post->id) : false;

        return view('student.posts.show', compact('post', 'existingApplication', 'student', 'isBookmarked'));
    }

    /**
     * Apply for an internship post.
     */
    public function apply(Request $request, InternshipPost $post): RedirectResponse
    {
        $student = Auth::user()->studentProfile;

        if (!$student) {
            return back()->with('error', 'Student profile not found.');
        }

        // Prevent duplicate active application (Feature 1.5: allow re-application if previously withdrawn)
        $existingApp = Application::where('student_profile_id', $student->id)
            ->where('internship_post_id', $post->id)
            ->first();

        if ($existingApp && $existingApp->status !== 'withdrawn') {
            return back()->with('error', 'You have already applied for this position.');
        }

        // ── Feature 1.4: resume_source decides whether to use profile or custom resume ──
        $resumeSource = $request->input('resume_source', 'profile'); // profile | custom

        // Determine if student already has a usable profile resume
        $hasProfileResume = $student->resume_path &&
            (Storage::disk('local')->exists($student->resume_path) ||
             Storage::disk('public')->exists($student->resume_path));

        // Validate: resume file is required only when source=custom AND no profile resume exists
        $resumeRequired = ($resumeSource === 'custom' && !$hasProfileResume) ? 'required' : 'nullable';

        $validated = $request->validate([
            'cover_letter'  => ['required', 'string', 'min:30', 'max:3000'],
            'resume_source' => ['nullable', 'in:profile,custom'],
            'resume'        => [$resumeRequired, 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $customResumePath = null;

        if ($resumeSource === 'custom' && $request->hasFile('resume')) {
            // Student chose to upload a tailored resume for this application
            $file = $request->file('resume');
            if ($file->isValid()) {
                $originalName = $file->getClientOriginalName();
                $customResumePath = $file->storeAs('resumes/' . $student->id, $originalName, 'local');

                // Also save as profile resume if student has none yet
                if (!$hasProfileResume) {
                    $student->update(['resume_path' => $customResumePath]);
                }
            }
        } elseif ($resumeSource === 'custom' && $hasProfileResume) {
            // Chose "Upload" tab but submitted without a file → fall back to profile resume
            $customResumePath = $student->resume_path;
        } elseif ($resumeSource === 'profile' && $hasProfileResume) {
            // Student chose to use their profile resume as-is
            $customResumePath = $student->resume_path;
        } else {
            return back()->withInput()->with('error', 'Please upload a resume file (PDF or Word document) to submit your application.');
        }

        if ($existingApp) {
            $existingApp->update([
                'cover_letter'       => $validated['cover_letter'],
                'custom_resume_path' => $customResumePath,
                'status'             => 'pending',
                'company_notes'      => null,
                'applied_at'         => now(),
                'reviewed_at'        => null,
            ]);
        } else {
            Application::create([
                'internship_post_id' => $post->id,
                'student_profile_id' => $student->id,
                'cover_letter'       => $validated['cover_letter'],
                'custom_resume_path' => $customResumePath,
                'status'             => 'pending',
                'applied_at'         => now(),
            ]);
        }

        // Notify employer of new applicant
        $post->companyProfile?->user?->notify(new \App\Notifications\AppNotification(
            title: 'New Candidate Application',
            message: Auth::user()->name . " submitted an application for '{$post->title}'.",
            actionUrl: route('company.applicants.index'),
            icon: 'fa-solid fa-user-plus',
            color: 'blue'
        ));

        return redirect()->route('student.applications.index')
            ->with('success', "Application successfully submitted with your resume to {$post->companyProfile->company_name}!");
    }

    /**
     * Withdraw a submitted application (Feature 1.5 - status = applied/pending only).
     */
    public function withdraw(Application $application): RedirectResponse
    {
        $student = Auth::user()->studentProfile;

        if (!$student || $application->student_profile_id !== $student->id) {
            abort(403, 'Unauthorized action.');
        }

        if (!$application->canBeWithdrawn()) {
            return back()->with('error', 'Only applications currently pending review can be withdrawn.');
        }

        $application->update([
            'status' => 'withdrawn',
        ]);

        // Notify host company recruiter
        $companyUser = $application->internshipPost->companyProfile?->user;
        if ($companyUser) {
            $companyUser->notify(new \App\Notifications\AppNotification(
                title: 'Application Withdrawn',
                message: Auth::user()->name . " withdrew their application for '" . $application->internshipPost->title . "'.",
                actionUrl: route('company.applicants.index'),
                icon: 'fa-solid fa-user-minus',
                color: 'rose'
            ));
        }

        return redirect()->route('student.applications.index')
            ->with('success', "Your application for '{$application->internshipPost->title}' has been successfully withdrawn.");
    }

    /**
     * List all submitted applications for this student.
     */
    public function applications(Request $request): View
    {
        $student = Auth::user()->studentProfile;

        $baseQuery = Application::with(['internshipPost.companyProfile', 'interview'])
            ->where('student_profile_id', $student?->id);

        $tabCounts = [
            'all'           => (clone $baseQuery)->count(),
            'in_review'     => (clone $baseQuery)->whereIn('status', ['pending', 'under_review', 'shortlisted'])->count(),
            'interviews'    => (clone $baseQuery)->where(function ($q) {
                $q->where('status', 'interviewed')
                  ->orWhereHas('interview', fn ($iq) => $iq->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested']));
            })->count(),
            'action_needed' => (clone $baseQuery)->whereHas('interview', function ($iq) {
                $iq->where('status', 'scheduled')->where('confirmed_by_student', false);
            })->count(),
            'accepted'      => (clone $baseQuery)->where('status', 'accepted')->count(),
            'closed'        => (clone $baseQuery)->whereIn('status', ['rejected', 'withdrawn'])->count(),
        ];

        $query = clone $baseQuery;
        $tab = $request->input('tab', 'all');

        if ($tab === 'in_review') {
            $query->whereIn('status', ['pending', 'under_review', 'shortlisted']);
        } elseif ($tab === 'interviews') {
            $query->where(function ($q) {
                $q->where('status', 'interviewed')
                  ->orWhereHas('interview', fn ($iq) => $iq->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested']));
            });
        } elseif ($tab === 'action_needed') {
            $query->whereHas('interview', fn ($iq) => $iq->where('status', 'scheduled')->where('confirmed_by_student', false));
        } elseif ($tab === 'accepted') {
            $query->where('status', 'accepted');
        } elseif ($tab === 'closed') {
            $query->whereIn('status', ['rejected', 'withdrawn']);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('internshipPost', function ($pq) use ($search) {
                $pq->where('title', 'like', "%{$search}%")
                   ->orWhereHas('companyProfile', fn ($cq) => $cq->where('company_name', 'like', "%{$search}%"));
            });
        }

        $applications = $query
            ->latest('applied_at')
            ->paginate(10)
            ->withQueryString();

        return view('student.applications.index', compact('applications', 'tabCounts'));
    }
}
