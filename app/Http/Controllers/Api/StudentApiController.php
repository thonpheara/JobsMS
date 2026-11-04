<?php

namespace App\Http\Controllers\Api;

use App\Models\Application;
use App\Models\CompanyProfile;
use App\Models\InternshipPost;
use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentApiController extends BaseApiController
{
    /**
     * Get student dashboard metrics and recent activities.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile ?? $user->studentProfile()->create();

        $applicationsCount = Application::where('student_profile_id', $student->id)->count();
        $pendingCount = Application::where('student_profile_id', $student->id)->where('status', 'pending')->count();
        $shortlistedCount = Application::where('student_profile_id', $student->id)->where('status', 'shortlisted')->count();
        $acceptedCount = Application::where('student_profile_id', $student->id)->where('status', 'accepted')->count();

        $recentApplications = Application::with(['internshipPost.companyProfile'])
            ->where('student_profile_id', $student->id)
            ->latest()
            ->take(5)
            ->get();

        // Feature 1.7: Smart Recommended Posts for Student Dashboard
        $matchingPosts = InternshipPost::with('companyProfile')
            ->recommendedFor($student)
            ->get();

        $rankedPosts = $matchingPosts->map(function ($post) use ($student) {
            $postText = strtolower($post->title . ' ' . $post->category . ' ' . $post->requirements . ' ' . $post->description);
            $matchedSkills = [];
            if ($student && is_array($student->skills)) {
                foreach ($student->skills as $skill) {
                    $s = trim((string) $skill);
                    if ($s !== '' && str_contains($postText, strtolower($s))) {
                        $matchedSkills[] = $s;
                    }
                }
            }
            $isMajorMatch = false;
            if ($student && !empty($student->major)) {
                $isMajorMatch = str_contains($postText, strtolower(trim($student->major)));
            }

            $post->matched_skills   = array_slice(array_unique($matchedSkills), 0, 3);
            $post->is_major_match   = $isMajorMatch;
            $post->has_smart_match  = !empty($matchedSkills) || $isMajorMatch;
            $post->match_score      = (count($matchedSkills) * 2) + ($isMajorMatch ? 3 : 0);
            return $post;
        })->sortByDesc('match_score')->values()->take(5);

        if ($rankedPosts->count() < 5) {
            $existingIds = $rankedPosts->pluck('id')->toArray();
            $fallbacks = InternshipPost::with('companyProfile')
                ->approved()
                ->whereDate('deadline', '>=', now()->toDateString())
                ->whereDoesntHave('applications', fn ($q) => $q->where('student_profile_id', $student->id))
                ->whereNotIn('id', $existingIds)
                ->latest()
                ->take(5 - $rankedPosts->count())
                ->get()
                ->map(function ($p) {
                    $p->matched_skills  = [];
                    $p->is_major_match  = false;
                    $p->has_smart_match = false;
                    $p->match_score     = 0;
                    return $p;
                });
            $rankedPosts = $rankedPosts->concat($fallbacks);
        }

        return $this->sendResponse([
            'student' => $student,
            'stats'   => [
                'total_applications' => $applicationsCount,
                'pending'            => $pendingCount,
                'shortlisted'        => $shortlistedCount,
                'accepted'           => $acceptedCount,
            ],
            'recent_applications' => $recentApplications,
            'recommended_posts'   => $rankedPosts,
            'featured_posts'      => $rankedPosts, // Backwards-compatible alias for existing Android clients
        ], 'Dashboard loaded successfully.');
    }

    /**
     * Get the student's profile information.
     */
    public function getProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile ?? $user->studentProfile()->create();

        return $this->sendResponse([
            'user'    => $user,
            'student' => $student,
        ], 'Student profile retrieved successfully.');
    }

    /**
     * Update student profile fields.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile ?? $user->studentProfile()->create();

        $validator = Validator::make($request->all(), [
            'name'              => ['sometimes', 'required', 'string', 'max:255'],
            'student_id_number' => ['nullable', 'string', 'max:50'],
            'department'        => ['nullable', 'string', 'max:255'],
            'major'             => ['nullable', 'string', 'max:255'],
            'cohort_year'       => ['nullable', 'integer', 'min:2000', 'max:2099'],
            'gpa'               => ['nullable', 'numeric', 'between:0.00,4.00'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'skills'            => ['nullable'],
            'bio'               => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        if ($request->filled('name')) {
            $user->update(['name' => $request->name]);
        }

        $skills = $request->skills;
        if (is_string($skills)) {
            $skills = array_filter(array_map('trim', explode(',', $skills)));
        }

        $student->update([
            'student_id_number' => $request->student_id_number ?? $student->student_id_number,
            'department'        => $request->department ?? $student->department,
            'major'             => $request->major ?? $student->major,
            'cohort_year'       => $request->cohort_year ?? $student->cohort_year,
            'gpa'               => $request->gpa ?? $student->gpa,
            'phone'             => $request->phone ?? $student->phone,
            'skills'            => $skills ?? $student->skills,
            'bio'               => $request->bio ?? $student->bio,
        ]);

        return $this->sendResponse([
            'user'    => $user->fresh(),
            'student' => $student->fresh(),
        ], 'Profile updated successfully.');
    }

    /**
     * Upload or update resume document (PDF).
     */
    public function uploadResume(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Resume file validation failed', $validator->errors(), 422);
        }

        $student = $request->user()->studentProfile ?? $request->user()->studentProfile()->create();

        if ($student->resume_path) {
            Storage::disk('local')->delete($student->resume_path);
            Storage::disk('public')->delete($student->resume_path);
        }

        $file = $request->file('resume');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('resumes/' . $student->id, $fileName, 'local');

        $student->update(['resume_path' => $path]);

        return $this->sendResponse([
            'resume_path' => $path,
            'has_resume'  => true,
            'student'     => $student->fresh(),
        ], 'Resume uploaded successfully.');
    }

    /**
     * Delete student resume file.
     */
    public function deleteResume(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (!$student || !$student->resume_path) {
            return $this->sendError('No resume found to delete.', [], 404);
        }

        Storage::disk('local')->delete($student->resume_path);
        Storage::disk('public')->delete($student->resume_path);

        $student->update(['resume_path' => null]);

        return $this->sendResponse([
            'student' => $student->fresh(),
        ], 'Resume removed successfully.');
    }

    /**
     * Browse available approved internship posts with advanced search, filter, and sort (Feature 1.3).
     */
    public function browsePosts(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile;

        $query = InternshipPost::with('companyProfile')->approved();

        // 1. Deadline filter: 'open' (default) or 'soon' (closing in <= 7 days)
        $deadline = $request->input('deadline', 'open');
        if ($deadline === 'soon') {
            $query->whereDate('deadline', '>=', now()->toDateString())
                  ->whereDate('deadline', '<=', now()->addDays(7)->toDateString());
        } else {
            $query->whereDate('deadline', '>=', now()->toDateString());
        }

        // 2. Search title/company/description/location
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('companyProfile', function ($compQ) use ($search) {
                      $compQ->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // 3. Category filter
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // 4. Type filter (on_site, remote, hybrid)
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // 5. Location filter
        if ($location = $request->input('location')) {
            $query->where('location', 'like', "%{$location}%");
        }

        // 6. Stipend filter: 'has' | 'none' | 'min' (with stipend_min)
        $stipend = $request->input('stipend', '');
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

        // 7. Sort: 'newest' | 'deadline' | 'stipend'
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'deadline' => $query->orderBy('deadline', 'asc'),
            'stipend'  => $query->orderBy('stipend', 'desc')->orderBy('created_at', 'desc'),
            default    => $query->latest(),
        };

        $perPage = min((int) ($request->input('per_page') ?: 15), 50);
        $posts = $query->paginate($perPage);

        // Append has_applied and is_bookmarked flags for mobile UI
        if ($student) {
            $appliedPostIds = Application::where('student_profile_id', $student->id)
                ->pluck('internship_post_id')
                ->toArray();

            $bookmarkedIds = $student->bookmarks()
                ->pluck('internship_post_id')
                ->toArray();

            $posts->getCollection()->transform(function ($post) use ($appliedPostIds, $bookmarkedIds) {
                $post->has_applied   = in_array($post->id, $appliedPostIds, true);
                $post->is_bookmarked = in_array($post->id, $bookmarkedIds, true);
                return $post;
            });
        }

        return $this->sendResponse($posts, 'Internship posts retrieved.');
    }

    /**
     * Get a specific internship post by ID.
     */
    public function showPost(Request $request, int $id): JsonResponse
    {
        $post = InternshipPost::with('companyProfile')->find($id);

        if (!$post) {
            return $this->sendError('Internship post not found.', [], 404);
        }

        $student = $request->user()->studentProfile;
        $existingApplication = null;
        $isBookmarked = false;

        if ($student) {
            $existingApplication = Application::where('student_profile_id', $student->id)
                ->where('internship_post_id', $post->id)
                ->first();

            $isBookmarked = $student->hasBookmarked($post->id);
        }

        return $this->sendResponse([
            'post'                 => $post,
            'has_applied'          => !is_null($existingApplication),
            'is_bookmarked'        => $isBookmarked,
            'existing_application' => $existingApplication,
        ], 'Internship post details loaded.');
    }

    /**
     * Submit an application for an internship post.
     */
    public function applyPost(Request $request, int $id): JsonResponse
    {
        $post = InternshipPost::with('companyProfile.user')->find($id);

        if (!$post) {
            return $this->sendError('Internship post not found.', [], 404);
        }

        if ($post->status !== 'approved' || $post->deadline->lt(now()->startOfDay())) {
            return $this->sendError('This internship post is no longer accepting applications.', [], 422);
        }

        $student = $request->user()->studentProfile ?? $request->user()->studentProfile()->create();

        // Check duplicate active application (allow re-apply if withdrawn)
        $existingApp = Application::where('student_profile_id', $student->id)
            ->where('internship_post_id', $post->id)
            ->first();

        if ($existingApp && $existingApp->status !== 'withdrawn') {
            return $this->sendError('You have already applied for this position.', [], 409);
        }

        $validator = Validator::make($request->all(), [
            'cover_letter' => ['required', 'string', 'min:20', 'max:5000'],
            'resume'       => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Application submission failed', $validator->errors(), 422);
        }

        $customResumePath = null;

        if ($request->hasFile('resume')) {
            $file = $request->file('resume');
            $customResumePath = $file->storeAs('resumes/' . $student->id, time() . '_' . $file->getClientOriginalName(), 'local');

            if (!$student->resume_path) {
                $student->update(['resume_path' => $customResumePath]);
            }
        } elseif ($student->resume_path && (Storage::disk('local')->exists($student->resume_path) || Storage::disk('public')->exists($student->resume_path))) {
            $customResumePath = $student->resume_path;
        } else {
            return $this->sendError('Please upload a resume file (PDF) or add one to your profile to submit your application.', [], 422);
        }

        if ($existingApp) {
            $existingApp->update([
                'cover_letter'       => $request->cover_letter,
                'custom_resume_path' => $customResumePath,
                'status'             => 'pending',
                'company_notes'      => null,
                'applied_at'         => now(),
                'reviewed_at'        => null,
            ]);
            $application = $existingApp;
        } else {
            $application = Application::create([
                'internship_post_id' => $post->id,
                'student_profile_id' => $student->id,
                'cover_letter'       => $request->cover_letter,
                'custom_resume_path' => $customResumePath,
                'status'             => 'pending',
                'applied_at'         => now(),
            ]);
        }

        // Send notification to employer
        $post->companyProfile?->user?->notify(new AppNotification(
            title: 'New Candidate Application',
            message: $request->user()->name . " submitted an application for '{$post->title}'.",
            actionUrl: route('company.applicants.index'),
            icon: 'fa-solid fa-user-plus',
            color: 'blue'
        ));

        $application->load(['internshipPost.companyProfile']);

        return $this->sendResponse($application, 'Application submitted successfully!', 201);
    }

    /**
     * Get student's application history.
     */
    public function myApplications(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (!$student) {
            return $this->sendResponse([], 'No applications found.');
        }

        $query = Application::with(['internshipPost.companyProfile'])
            ->where('student_profile_id', $student->id);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) ($request->input('per_page') ?: 15), 50);
        $applications = $query->latest('applied_at')->paginate($perPage);

        return $this->sendResponse($applications, 'Applications retrieved successfully.');
    }

    /**
     * Get details of a single application.
     */
    public function showApplication(Request $request, int $id): JsonResponse
    {
        $student = $request->user()->studentProfile;

        $application = Application::with(['internshipPost.companyProfile'])
            ->where('student_profile_id', $student?->id)
            ->find($id);

        if (!$application) {
            return $this->sendError('Application not found.', [], 404);
        }

        return $this->sendResponse($application, 'Application details loaded.');
    }

    /**
     * Withdraw a submitted application (Feature 1.5 - status pending/applied only).
     */
    public function withdrawApplication(Request $request, int $id): JsonResponse
    {
        $student = $request->user()->studentProfile;
        $application = Application::with('internshipPost.companyProfile.user')
            ->where('student_profile_id', $student?->id)
            ->find($id);

        if (!$application) {
            return $this->sendError('Application not found.', [], 404);
        }

        if (!$application->canBeWithdrawn()) {
            return $this->sendError('Only applications pending review can be withdrawn.', [
                'current_status' => $application->status,
            ], 422);
        }

        $application->update([
            'status' => 'withdrawn',
        ]);

        // Notify employer of withdrawal
        $companyUser = $application->internshipPost->companyProfile?->user;
        if ($companyUser) {
            $companyUser->notify(new AppNotification(
                title: 'Application Withdrawn',
                message: $request->user()->name . " withdrew their application for '" . $application->internshipPost->title . "'.",
                actionUrl: route('company.applicants.index'),
                icon: 'fa-solid fa-user-minus',
                color: 'rose'
            ));
        }

        return $this->sendResponse($application->fresh(), 'Application successfully withdrawn.');
    }

    /**
     * Get student's bookmarked / saved jobs (Feature 1.2).
     */
    public function bookmarks(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;

        if (!$student) {
            return $this->sendResponse([], 'No bookmarks found.');
        }

        $perPage = min((int) ($request->input('per_page') ?: 15), 50);

        $posts = $student->bookmarkedPosts()
            ->with('companyProfile')
            ->approved()
            ->latest('student_bookmarks.created_at')
            ->paginate($perPage);

        $appliedPostIds = Application::where('student_profile_id', $student->id)
            ->pluck('internship_post_id')
            ->toArray();

        $posts->getCollection()->transform(function ($post) use ($appliedPostIds) {
            $post->has_applied   = in_array($post->id, $appliedPostIds, true);
            $post->is_bookmarked = true;
            return $post;
        });

        return $this->sendResponse($posts, 'Bookmarked internship posts retrieved.');
    }

    /**
     * Toggle bookmark on an internship post (Feature 1.2).
     */
    public function toggleBookmark(Request $request, int $id): JsonResponse
    {
        $student = $request->user()->studentProfile ?? $request->user()->studentProfile()->create();
        $post = InternshipPost::approved()->find($id);

        if (!$post) {
            return $this->sendError('Internship post not found or not approved.', [], 404);
        }

        $existing = $student->bookmarks()->where('internship_post_id', $post->id)->first();

        if ($existing) {
            $existing->delete();
            $bookmarked = false;
            $message = 'Post removed from saved jobs.';
        } else {
            $student->bookmarks()->create(['internship_post_id' => $post->id]);
            $bookmarked = true;
            $message = 'Post saved to bookmarks successfully.';
        }

        return $this->sendResponse([
            'post_id'    => $post->id,
            'bookmarked' => $bookmarked,
        ], $message);
    }

    /**
     * Get company public profile and active openings (Feature 1.6).
     */
    public function showCompany(Request $request, int $id): JsonResponse
    {
        $company = CompanyProfile::with(['user'])->find($id);

        if (!$company) {
            return $this->sendError('Company profile not found.', [], 404);
        }

        $student = $request->user()->studentProfile;

        $activePosts = $company->internshipPosts()
            ->with('companyProfile')
            ->approved()
            ->whereDate('deadline', '>=', now()->toDateString())
            ->latest()
            ->get();

        $appliedIds = $student
            ? Application::where('student_profile_id', $student->id)->pluck('internship_post_id')->toArray()
            : [];

        $bookmarkedIds = $student
            ? $student->bookmarks()->pluck('internship_post_id')->toArray()
            : [];

        $activePosts->transform(function ($p) use ($appliedIds, $bookmarkedIds) {
            $p->has_applied   = in_array($p->id, $appliedIds, true);
            $p->is_bookmarked = in_array($p->id, $bookmarkedIds, true);
            return $p;
        });

        return $this->sendResponse([
            'company'         => $company,
            'active_posts'    => $activePosts,
            'total_positions' => $company->internshipPosts()->approved()->count(),
            'is_verified'     => $company->isVerified(),
        ], 'Company profile loaded successfully.');
    }
}
