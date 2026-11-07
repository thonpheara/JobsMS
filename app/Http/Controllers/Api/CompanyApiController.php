<?php

namespace App\Http\Controllers\Api;

use App\Models\Application;
use App\Models\CompanyProfile;
use App\Models\InternshipPost;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CompanyApiController extends BaseApiController
{
    /**
     * Get company dashboard overview & analytics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->companyProfile;

        if (!$company) {
            return $this->sendError('Company profile not initialized.', [], 404);
        }

        $postIds = $company->internshipPosts()->pluck('id');

        $totalPosts = $company->internshipPosts()->count();
        $activePosts = $company->internshipPosts()->where('status', 'approved')->whereDate('deadline', '>=', now())->count();
        $totalApplicants = Application::whereIn('internship_post_id', $postIds)->count();
        $pendingReview = Application::whereIn('internship_post_id', $postIds)->where('status', 'pending')->count();
        $acceptedCount = Application::whereIn('internship_post_id', $postIds)->where('status', 'accepted')->count();

        $recentApplicants = Application::with(['studentProfile.user', 'internshipPost', 'interview', 'interviews'])
            ->whereIn('internship_post_id', $postIds)
            ->latest('applied_at')
            ->take(5)
            ->get();

        // 1. Top 5 posts by applications received (Bar Chart)
        $topPosts = $company->internshipPosts()
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->take(5)
            ->get();

        $topPostsChart = [
            'labels'      => $topPosts->map(fn($p) => \Illuminate\Support\Str::limit($p->title, 20))->values()->toArray(),
            'full_titles' => $topPosts->map(fn($p) => $p->title)->values()->toArray(),
            'data'        => $topPosts->map(fn($p) => (int) $p->applications_count)->values()->toArray(),
            'ids'         => $topPosts->map(fn($p) => $p->id)->values()->toArray(),
            'total'       => $topPosts->sum('applications_count'),
            'has_data'    => $topPosts->contains(fn($p) => $p->applications_count > 0),
        ];

        // 2. Status breakdown for donut
        $statusCounts = Application::whereIn('internship_post_id', $postIds)
            ->selectRaw("status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $appliedTotal     = (int)(($statusCounts['pending'] ?? 0) + ($statusCounts['under_review'] ?? 0));
        $shortlistedTotal = (int)(($statusCounts['shortlisted'] ?? 0) + ($statusCounts['interviewed'] ?? 0));
        $acceptedTotal    = (int)($statusCounts['accepted'] ?? 0);
        $rejectedTotal    = (int)(($statusCounts['rejected'] ?? 0) + ($statusCounts['withdrawn'] ?? 0));
        $totalOutcomes    = $appliedTotal + $shortlistedTotal + $acceptedTotal + $rejectedTotal;
        $placementRate    = $totalOutcomes > 0 ? round(($acceptedTotal / $totalOutcomes) * 100, 1) : 0;

        $donutData = [
            'labels' => ['Applied', 'Shortlisted', 'Accepted', 'Rejected'],
            'data'   => [$appliedTotal, $shortlistedTotal, $acceptedTotal, $rejectedTotal],
            'colors' => ['#0EA5E9', '#6366F1', '#10B981', '#F43F5E'],
            'total'  => $totalOutcomes,
            'placement_rate' => $placementRate,
            'counts' => [
                'applied'     => $appliedTotal,
                'shortlisted' => $shortlistedTotal,
                'accepted'    => $acceptedTotal,
                'rejected'    => $rejectedTotal,
            ],
            'percentages' => [
                'applied'     => $totalOutcomes > 0 ? round(($appliedTotal / $totalOutcomes) * 100) : 0,
                'shortlisted' => $totalOutcomes > 0 ? round(($shortlistedTotal / $totalOutcomes) * 100) : 0,
                'accepted'    => $totalOutcomes > 0 ? round(($acceptedTotal / $totalOutcomes) * 100) : 0,
                'rejected'    => $totalOutcomes > 0 ? round(($rejectedTotal / $totalOutcomes) * 100) : 0,
            ],
        ];

        return $this->sendResponse([
            'company' => $company,
            'stats'   => [
                'total_posts'      => $totalPosts,
                'active_posts'     => $activePosts,
                'total_applicants' => $totalApplicants,
                'pending_review'   => $pendingReview,
                'accepted'         => $acceptedCount,
            ],
            'analytics' => [
                'top_posts'           => $topPostsChart,
                'status_distribution' => $donutData,
            ],
            'recent_applicants' => $recentApplicants,
        ], 'Company dashboard retrieved.');
    }

    /**
     * Get company profile.
     */
    public function getProfile(Request $request): JsonResponse
    {
        $company = $request->user()->companyProfile;

        if (!$company) {
            return $this->sendError('Company profile not found.', [], 404);
        }

        return $this->sendResponse($company, 'Company profile retrieved.');
    }

    /**
     * Update company profile information and logo.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $company = $request->user()->companyProfile;

        if (!$company) {
            return $this->sendError('Company profile not found.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'company_name'   => ['required', 'string', 'max:255'],
            'industry'       => ['nullable', 'string', 'max:255'],
            'location'       => ['nullable', 'string', 'max:255'],
            'address'        => ['nullable', 'string', 'max:500'],
            'website'        => ['nullable', 'url', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone'  => ['nullable', 'string', 'max:50'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'logo'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = $request->only([
            'company_name',
            'industry',
            'location',
            'address',
            'website',
            'contact_person',
            'contact_phone',
            'description',
        ]);

        if ($request->hasFile('logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('companies/logos', 'public');
        }

        $company->update($data);

        return $this->sendResponse($company->fresh(), 'Company profile updated successfully.');
    }

    /**
     * List all posts created by the authenticated company.
     */
    public function listPosts(Request $request): JsonResponse
    {
        $company = $request->user()->companyProfile;

        if (!$company) {
            return $this->sendError('Company profile not found.', [], 404);
        }

        $query = $company->internshipPosts()->withCount('applications');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) ($request->input('per_page') ?: 15), 50);
        $posts = $query->latest()->paginate($perPage);

        return $this->sendResponse($posts, 'Company listings retrieved.');
    }

    /**
     * Create a new internship listing.
     */
    public function storePost(Request $request): JsonResponse
    {
        $company = $request->user()->companyProfile;

        if (!$company) {
            return $this->sendError('Company profile not found.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'                => ['required', 'string', 'max:255'],
            'category'             => ['required', 'string', 'max:100'],
            'type'                 => ['required', 'in:remote,on_site,hybrid'],
            'location'             => ['required', 'string', 'max:255'],
            'duration_weeks'       => ['required', 'integer', 'min:1', 'max:104'],
            'slots'                => ['required', 'integer', 'min:1', 'max:100'],
            'deadline'             => ['required', 'date', 'after:today'],
            'description'          => ['required', 'string', 'min:30'],
            'responsibilities'     => ['nullable', 'string'],
            'requirements'         => ['required', 'string'],
            'stipend'              => ['nullable', 'numeric', 'min:0'],
            'is_stipend_disclosed' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $baseSlug = Str::slug($request->title);
        $slug = $baseSlug . '-' . Str::random(6);

        $isDraft = $request->input('status') === 'draft' || $request->input('action') === 'draft';
        $postStatus = $isDraft ? 'draft' : 'pending_approval';

        $post = $company->internshipPosts()->create([
            'title'                => $request->title,
            'slug'                 => $slug,
            'category'             => $request->category,
            'type'                 => $request->type ?? 'on_site',
            'location'             => $request->location,
            'duration_weeks'       => $request->duration_weeks,
            'slots'                => $request->slots,
            'deadline'             => $request->deadline,
            'description'          => $request->description,
            'responsibilities'     => $request->responsibilities,
            'requirements'         => $request->requirements,
            'stipend'              => $request->stipend,
            'is_stipend_disclosed' => $request->boolean('is_stipend_disclosed', false),
            'status'               => $postStatus,
        ]);

        if (!$isDraft) {
            // Notify admins for review
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new AppNotification(
                    title: 'New Listing Pending Review',
                    message: "{$company->company_name} posted '{$post->title}' requiring review.",
                    actionUrl: route('admin.approvals.index'),
                    icon: 'fa-solid fa-briefcase',
                    color: 'blue'
                ));
            }

            return $this->sendResponse($post, 'Internship post submitted and awaiting admin moderation.', 201);
        }

        return $this->sendResponse($post, 'Internship post saved as draft.', 201);
    }

    /**
     * Show a single company post.
     */
    public function showPost(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companyProfile;

        $post = $company?->internshipPosts()->withCount('applications')->find($id);

        if (!$post) {
            return $this->sendError('Post not found.', [], 404);
        }

        return $this->sendResponse($post, 'Post details loaded.');
    }

    /**
     * Update an internship post.
     */
    public function updatePost(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companyProfile;
        $post = $company?->internshipPosts()->find($id);

        if (!$post) {
            return $this->sendError('Post not found.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'                => ['sometimes', 'required', 'string', 'max:255'],
            'category'             => ['sometimes', 'required', 'string', 'max:100'],
            'type'                 => ['sometimes', 'required', 'in:remote,on_site,hybrid'],
            'location'             => ['sometimes', 'required', 'string', 'max:255'],
            'duration_weeks'       => ['sometimes', 'required', 'integer', 'min:1'],
            'slots'                => ['sometimes', 'required', 'integer', 'min:1'],
            'deadline'             => ['sometimes', 'required', 'date'],
            'description'          => ['sometimes', 'required', 'string', 'min:30'],
            'responsibilities'     => ['nullable', 'string'],
            'requirements'         => ['sometimes', 'required', 'string'],
            'stipend'              => ['nullable', 'numeric', 'min:0'],
            'is_stipend_disclosed' => ['nullable', 'boolean'],
            'status'               => ['nullable', 'in:pending,approved,closed,draft'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = $request->only([
            'title', 'category', 'type', 'location', 'duration_weeks',
            'slots', 'deadline', 'description', 'responsibilities',
            'requirements', 'stipend', 'is_stipend_disclosed'
        ]);

        // Security check: company cannot approve own post
        if ($request->filled('status') && in_array($request->status, ['draft', 'closed'], true)) {
            $data['status'] = $request->status;
        }

        $post->update($data);

        return $this->sendResponse($post->fresh(), 'Post updated successfully.');
    }

    /**
     * Delete an internship post.
     */
    public function deletePost(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companyProfile;
        $post = $company?->internshipPosts()->find($id);

        if (!$post) {
            return $this->sendError('Post not found.', [], 404);
        }

        $post->delete();

        return $this->sendResponse(null, 'Post deleted successfully.');
    }

    /**
     * Duplicate/clone an existing internship post as a new draft.
     */
    public function duplicatePost(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companyProfile;
        $post = $company?->internshipPosts()->find($id);

        if (!$post) {
            return $this->sendError('Post not found or unauthorized.', [], 404);
        }

        $cloned = $post->replicate([
            'applications_count',
            'created_at',
            'updated_at',
            'deleted_at',
            'rejection_reason',
        ]);

        $cloned->title = $post->title . ' (Copy)';
        $cloned->slug = Str::slug($cloned->title) . '-' . Str::random(6);
        $cloned->status = 'draft';

        // Ensure valid future deadline (at least 30 days ahead if past or missing)
        if (empty($cloned->deadline) || \Carbon\Carbon::parse($cloned->deadline)->isPast()) {
            $cloned->deadline = now()->addDays(30)->toDateString();
        }

        $cloned->save();

        return $this->sendResponse($cloned, "Post duplicated successfully as draft: '{$cloned->title}'.", 201);
    }

    /**
     * Submit a draft internship post for administrator approval.
     */
    public function submitPost(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companyProfile;
        $post = $company?->internshipPosts()->find($id);

        if (!$post) {
            return $this->sendError('Post not found or unauthorized.', [], 404);
        }

        if ($post->status !== 'draft') {
            return $this->sendError("Only draft posts can be submitted for review. Current status: {$post->status}.", [], 422);
        }

        $post->update(['status' => 'pending_approval']);

        // Notify admins for review
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new AppNotification(
                title: 'New Listing Pending Review',
                message: "{$company->company_name} posted '{$post->title}' requiring review.",
                actionUrl: route('admin.approvals.index'),
                icon: 'fa-solid fa-briefcase',
                color: 'blue'
            ));
        }

        return $this->sendResponse($post, "Internship post '{$post->title}' submitted for administrator review.");
    }

    /**
     * List applicants for the company's postings.
     */
    public function listApplicants(Request $request): JsonResponse
    {
        $company = $request->user()->companyProfile;

        if (!$company) {
            return $this->sendError('Company profile not found.', [], 404);
        }

        $postIds = $company->internshipPosts()->pluck('id');

        $query = Application::with(['studentProfile.user', 'internshipPost', 'interview', 'interviews'])
            ->whereIn('internship_post_id', $postIds);

        if ($postId = $request->input('post_id')) {
            $query->where('internship_post_id', $postId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) ($request->input('per_page') ?: 15), 50);
        $applicants = $query->latest('applied_at')->paginate($perPage);

        return $this->sendResponse($applicants, 'Applicants retrieved successfully.');
    }

    /**
     * Get details of a single applicant.
     */
    public function showApplicant(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companyProfile;
        $postIds = $company?->internshipPosts()->pluck('id') ?? collect();

        $application = Application::with(['studentProfile.user', 'internshipPost', 'interview', 'interviews'])
            ->whereIn('internship_post_id', $postIds)
            ->find($id);

        if (!$application) {
            return $this->sendError('Applicant not found.', [], 404);
        }

        return $this->sendResponse($application, 'Applicant details retrieved.');
    }

    /**
     * Update application status (reviewed, shortlisted, accepted, rejected).
     */
    public function updateApplicantStatus(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companyProfile;
        $postIds = $company?->internshipPosts()->pluck('id') ?? collect();

        $application = Application::with(['studentProfile.user', 'internshipPost'])
            ->whereIn('internship_post_id', $postIds)
            ->find($id);

        if (!$application) {
            return $this->sendError('Applicant not found.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'status'        => ['required', 'in:reviewed,shortlisted,accepted,rejected'],
            'company_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $application->update([
            'status'        => $request->status,
            'company_notes' => $request->company_notes ?? $application->company_notes,
            'reviewed_at'   => now(),
        ]);

        // Notify student about application status change
        $statusLabel = ucfirst($request->status);
        $studentUser = $application->studentProfile?->user;

        if ($studentUser) {
            $color = match ($request->status) {
                'accepted'    => 'emerald',
                'shortlisted' => 'blue',
                'rejected'    => 'rose',
                default       => 'gray',
            };

            $studentUser->notify(new AppNotification(
                title: "Application Status Update: {$statusLabel}",
                message: "{$company->company_name} updated your application status for '{$application->internshipPost->title}' to {$statusLabel}.",
                actionUrl: route('student.applications.index'),
                icon: 'fa-solid fa-file-circle-check',
                color: $color
            ));
        }

        return $this->sendResponse($application->fresh(), "Application status updated to {$request->status}.");
    }

    /**
     * Schedule an interview session for an applicant.
     */
    public function scheduleInterview(Request $request, int $id): JsonResponse
    {
        $company = $request->user()->companyProfile;
        $postIds = $company?->internshipPosts()->pluck('id') ?? collect();

        $application = Application::with(['studentProfile.user', 'internshipPost'])
            ->whereIn('internship_post_id', $postIds)
            ->find($id);

        if (!$application) {
            return $this->sendError('Applicant not found or unauthorized.', [], 404);
        }

        if ($application->status === 'withdrawn') {
            return $this->sendError('Cannot schedule an interview for a withdrawn application.', [], 422);
        }

        $validator = Validator::make($request->all(), [
            'scheduled_at'     => ['required', 'date', 'after:now'],
            'meeting_type'     => ['required', 'in:online,in_person'],
            'location_or_link' => ['required', 'string', 'max:500'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $interview = $application->interviews()->create([
            'scheduled_at'         => $request->scheduled_at,
            'meeting_type'         => $request->meeting_type,
            'location_or_link'     => $request->location_or_link,
            'notes'                => $request->notes,
            'status'               => 'scheduled',
            'confirmed_by_student' => false,
        ]);

        if (in_array($application->status, ['pending', 'under_review', 'shortlisted'], true)) {
            $application->update([
                'status'      => 'interviewed',
                'reviewed_at' => now(),
            ]);
        }

        $studentUser = $application->studentProfile?->user;
        if ($studentUser) {
            $studentUser->notify(new \App\Notifications\InterviewScheduledNotification($interview));
        }

        return $this->sendResponse([
            'interview'          => $interview,
            'application_status' => $application->fresh()->status,
        ], 'Interview scheduled successfully. Invitation sent to candidate.', 201);
    }
}
