<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\InternshipPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InternshipPostController extends Controller
{
    /**
     * Display all posts created by this company with status tabs, search, and opportunity type filtering.
     */
    public function index(Request $request): View
    {
        $company = Auth::user()->companyProfile;

        $baseQuery = InternshipPost::where('company_profile_id', $company?->id);

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'all'              => $statusCounts->sum(),
            'approved'         => (int) ($statusCounts['approved'] ?? 0),
            'pending_approval' => (int) ($statusCounts['pending_approval'] ?? 0),
            'draft'            => (int) ($statusCounts['draft'] ?? 0),
            'closed'           => (int) ($statusCounts['closed'] ?? 0),
            'rejected'         => (int) ($statusCounts['rejected'] ?? 0),
        ];

        $query = (clone $baseQuery)->withCount([
            'applications',
            'applications as accepted_count' => fn ($q) => $q->where('status', 'accepted'),
        ]);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('opportunity_type') && $request->input('opportunity_type') !== 'all') {
            $oppType = $request->input('opportunity_type');
            if ($oppType === 'internship') {
                $query->where(function ($q) {
                    $q->whereNull('opportunity_type')->orWhere('opportunity_type', 'internship');
                });
            } elseif ($oppType === 'job') {
                $query->whereIn('opportunity_type', ['full_time', 'part_time', 'contract']);
            } else {
                $query->where('opportunity_type', $oppType);
            }
        }

        if ($request->filled('work_mode') && $request->input('work_mode') !== 'all') {
            $query->where('type', $request->input('work_mode'));
        }

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'latest');
        if ($sort === 'applicants_desc') {
            $query->orderByDesc('applications_count')->latest();
        } elseif ($sort === 'closing_soon') {
            $query->orderByRaw('CASE WHEN deadline >= CURRENT_DATE THEN 0 ELSE 1 END')
                  ->orderBy('deadline', 'asc');
        } else {
            $query->latest();
        }

        $posts = $query
            ->paginate(8)
            ->withQueryString();

        return view('company.posts.index', compact('posts', 'counts'));
    }

    /**
     * Show create job post form (redirects to listings with modal open flag).
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('company.posts.index', ['create' => 1]);
    }

    /**
     * Store new job post.
     */
    public function store(Request $request): RedirectResponse
    {
        $company = Auth::user()->companyProfile;

        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'category'        => ['required', 'string', 'max:100'],
            'description'     => ['required', 'string', 'min:30'],
            'responsibilities'=> ['nullable', 'string'],
            'requirements'    => ['nullable', 'string'],
            'location'        => ['required', 'string', 'max:255'],
            'type'            => ['nullable', 'in:remote,on_site,hybrid'],
            'opportunity_type'=> ['nullable', 'in:internship,full_time,part_time,contract'],
            'experience_level'=> ['nullable', 'in:no_experience,entry_level,junior,mid_level'],
            'duration_weeks'  => ['nullable', 'integer', 'min:1', 'max:52'],
            'stipend'         => ['nullable', 'numeric', 'min:0'],
            'salary_max'      => ['nullable', 'numeric', 'min:0'],
            'slots'           => ['required', 'integer', 'min:1', 'max:50'],
            'deadline'        => ['required', 'date', 'after:today'],
        ]);

        $isDraft = $request->input('action') === 'draft' || $request->input('status') === 'draft';

        $opportunityType = $validated['opportunity_type'] ?? 'internship';
        $validated['opportunity_type'] = $opportunityType;
        $validated['company_profile_id'] = $company->id;
        $validated['slug'] = Str::slug($validated['title']) . '-' . rand(1000, 9999);
        $validated['status'] = $isDraft ? 'draft' : 'pending_approval';
        $validated['is_stipend_disclosed'] = !empty($validated['stipend']) || !empty($validated['salary_max']);
        $validated['type'] = $validated['type'] ?? 'on_site';
        if ($opportunityType === 'internship') {
            if (empty($validated['duration_weeks'])) {
                $validated['duration_weeks'] = 12;
            }
            $validated['experience_level'] = null;
        }

        $post = InternshipPost::create($validated);

        if (!$isDraft) {
            // Notify admins about new job post pending approval
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\AppNotification(
                    title: 'New Job Post Pending Approval',
                    message: "{$company->company_name} submitted '{$post->title}' for review.",
                    actionUrl: route('admin.approvals.index', ['status' => 'pending_approval']),
                    icon: 'fa-solid fa-square-check',
                    color: 'amber'
                ));
            }

            return redirect()->route('company.posts.index')
                ->with('success', 'Internship post submitted for admin approval.');
        }

        return redirect()->route('company.posts.index')
            ->with('success', "Internship post saved as draft: '{$post->title}'. Visible only to your team.");
    }

    /**
     * Show edit form (redirects to listings with edit modal flag).
     */
    public function edit(InternshipPost $post): RedirectResponse
    {
        $this->authorizeCompanyPost($post);

        return redirect()->route('company.posts.index', ['edit' => $post->id]);
    }

    /**
     * Update existing job post.
     */
    public function update(Request $request, InternshipPost $post): RedirectResponse
    {
        $this->authorizeCompanyPost($post);

        // Security: Company can only transition between draft, closed, and pending_approval.
        // A company can NEVER self-approve an unapproved post (approved and rejected statuses are reserved exclusively for Admin).
        // Only if the post is ALREADY approved may it remain approved.
        $allowedStatuses = ['draft', 'pending_approval', 'closed'];
        if ($post->status === 'approved') {
            $allowedStatuses[] = 'approved';
        }

        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'category'        => ['required', 'string', 'max:100'],
            'description'     => ['required', 'string', 'min:30'],
            'responsibilities'=> ['nullable', 'string'],
            'requirements'    => ['nullable', 'string'],
            'location'        => ['required', 'string', 'max:255'],
            'type'            => ['nullable', 'in:remote,on_site,hybrid'],
            'opportunity_type'=> ['nullable', 'in:internship,full_time,part_time,contract'],
            'experience_level'=> ['nullable', 'in:no_experience,entry_level,junior,mid_level'],
            'duration_weeks'  => ['nullable', 'integer', 'min:1', 'max:52'],
            'stipend'         => ['nullable', 'numeric', 'min:0'],
            'salary_max'      => ['nullable', 'numeric', 'min:0'],
            'slots'           => ['required', 'integer', 'min:1', 'max:50'],
            'deadline'        => ['required', 'date'],
            'status'          => ['required', Rule::in($allowedStatuses)],
        ]);

        if (empty($validated['type'])) {
            unset($validated['type']);
        }

        if (($validated['opportunity_type'] ?? 'internship') === 'internship') {
            $validated['experience_level'] = null;
        } else {
            $validated['duration_weeks'] = null;
        }

        $validated['is_stipend_disclosed'] = !empty($validated['stipend']) || !empty($validated['salary_max']);

        $previousStatus = $post->status;
        $post->update($validated);

        // If the post was submitted/resubmitted for admin approval, notify admins
        if ($previousStatus !== 'pending_approval' && $validated['status'] === 'pending_approval') {
            $company = Auth::user()->companyProfile;
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\AppNotification(
                    title: 'Job Post Resubmitted for Approval',
                    message: "{$company->company_name} updated and resubmitted '{$post->title}' for review.",
                    actionUrl: route('admin.approvals.index', ['status' => 'pending_approval']),
                    icon: 'fa-solid fa-square-check',
                    color: 'amber'
                ));
            }
        }

        return redirect()->route('company.posts.index')
            ->with('success', 'Internship post updated successfully.');
    }

    /**
     * Delete/close post.
     */
    public function destroy(InternshipPost $post): RedirectResponse
    {
        $this->authorizeCompanyPost($post);

        $post->delete();

        return redirect()->route('company.posts.index')
            ->with('success', 'Internship post removed.');
    }

    /**
     * Submit a draft post for administrator approval.
     */
    public function submitForApproval(InternshipPost $post): RedirectResponse
    {
        $this->authorizeCompanyPost($post);

        if ($post->status !== 'draft') {
            return back()->with('info', 'This post has already been submitted or processed.');
        }

        $post->update([
            'status' => 'pending_approval',
        ]);

        $company = Auth::user()->companyProfile;
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\AppNotification(
                title: 'New Job Post Pending Approval',
                message: "{$company->company_name} submitted '{$post->title}' for review.",
                actionUrl: route('admin.approvals.index', ['status' => 'pending_approval']),
                icon: 'fa-solid fa-square-check',
                color: 'amber'
            ));
        }

        return redirect()->route('company.posts.index')
            ->with('success', "Internship post '{$post->title}' submitted for admin approval.");
    }

    /**
     * Duplicate/clone an existing internship post as draft.
     */
    public function duplicate(InternshipPost $post): RedirectResponse
    {
        $this->authorizeCompanyPost($post);

        $cloned = $post->replicate([
            'applications_count',
            'created_at',
            'updated_at',
            'deleted_at',
            'rejection_reason',
        ]);

        $cloned->title = $post->title . ' (Copy)';
        $cloned->slug = Str::slug($cloned->title) . '-' . rand(1000, 9999);
        $cloned->status = 'draft';

        // Ensure a valid future deadline (at least 30 days ahead if past or missing)
        if (empty($cloned->deadline) || \Carbon\Carbon::parse($cloned->deadline)->isPast()) {
            $cloned->deadline = now()->addDays(30)->toDateString();
        }

        $cloned->save();

        return redirect()->route('company.posts.index', ['edit' => $cloned->id])
            ->with('success', "Internship post duplicated as draft: '{$cloned->title}'. You can now adjust details and submit.");
    }

    /**
     * Quick toggle between Closed and Reopened (Pending Approval / Approved) status.
     */
    public function toggleClose(InternshipPost $post): RedirectResponse
    {
        $this->authorizeCompanyPost($post);

        if ($post->status === 'closed') {
            $post->update(['status' => 'pending_approval']);
            return back()->with('success', "Listing '{$post->title}' reopened and submitted for approval.");
        }

        if (in_array($post->status, ['approved', 'pending_approval'], true)) {
            $post->update(['status' => 'closed']);
            return back()->with('success', "Listing '{$post->title}' has been closed to new applications.");
        }

        return back()->with('info', 'Only active or closed listings can be toggled.');
    }

    protected function authorizeCompanyPost(InternshipPost $post): void
    {
        $company = Auth::user()->companyProfile;
        if ($post->company_profile_id !== $company?->id) {
            abort(403, 'Unauthorized action on this post.');
        }
    }
}
