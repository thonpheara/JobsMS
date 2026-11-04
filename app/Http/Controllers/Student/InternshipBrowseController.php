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
        $query = InternshipPost::with('companyProfile')->approved();

        // ── 1. Deadline scope ──────────────────────────────────────────────
        $deadline = $request->input('deadline', 'open');   // open | soon
        if ($deadline === 'soon') {
            // Closing within the next 7 days
            $query->whereDate('deadline', '>=', now()->toDateString())
                  ->whereDate('deadline', '<=', now()->addDays(7)->toDateString());
        } else {
            // Default: any post that hasn't expired yet
            $query->whereDate('deadline', '>=', now()->toDateString());
        }

        // ── 2. Search (title / description / location / company) ───────────
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('companyProfile', fn ($c) =>
                      $c->where('company_name', 'like', "%{$search}%")
                  );
            });
        }

        // ── 3. Category filter ─────────────────────────────────────────────
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // ── 4. Stipend filter ──────────────────────────────────────────────
        $stipend = $request->input('stipend', '');   // '' | has | none | min
        $stipendMin = $request->input('stipend_min');

        if ($stipend === 'has') {
            $query->whereNotNull('stipend')->where('stipend', '>', 0);
        } elseif ($stipend === 'none') {
            $query->where(function ($q) {
                $q->whereNull('stipend')->orWhere('stipend', 0);
            });
        } elseif ($stipend === 'min' && is_numeric($stipendMin) && $stipendMin >= 0) {
            $query->where('stipend', '>=', (float) $stipendMin);
        }

        // ── 5. Sort ────────────────────────────────────────────────────────
        $sort = $request->input('sort', 'newest');   // newest | deadline | stipend

        match ($sort) {
            'deadline' => $query->orderBy('deadline', 'asc'),
            'stipend'  => $query->orderBy('stipend', 'desc')->orderBy('created_at', 'desc'),
            default    => $query->latest(),           // newest
        };

        $posts = $query->get();

        // ── 6. Bookmarks ───────────────────────────────────────────────────
        $student           = Auth::user()->studentProfile;
        $bookmarkedPostIds = $student
            ? $student->bookmarks()->pluck('internship_post_id')->toArray()
            : [];

        return view('student.posts.index', compact('posts', 'bookmarkedPostIds'));
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
    public function applications(): View
    {
        $student = Auth::user()->studentProfile;

        $applications = Application::with(['internshipPost.companyProfile'])
            ->where('student_profile_id', $student?->id)
            ->latest('applied_at')
            ->paginate(5)
            ->withQueryString();

        return view('student.applications.index', compact('applications'));
    }
}
