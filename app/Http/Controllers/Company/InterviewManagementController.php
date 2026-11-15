<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipPost;
use App\Models\Interview;
use App\Notifications\AppNotification;
use App\Notifications\InterviewScheduledNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InterviewManagementController extends Controller
{
    /**
     * Display the dedicated Company Interview Management portal.
     */
    public function index(Request $request): View
    {
        $company = Auth::user()->companyProfile;
        $postIds = InternshipPost::where('company_profile_id', $company?->id)->pluck('id');
        $applicationIds = Application::whereIn('internship_post_id', $postIds)->pluck('id');

        $baseQuery = Interview::with([
            'application.studentProfile.user',
            'application.internshipPost',
        ])->whereIn('application_id', $applicationIds);

        // Summary KPI counts
        $todayCount = (clone $baseQuery)
            ->whereDate('scheduled_at', Carbon::today())
            ->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested'])
            ->count();

        $upcomingCount = (clone $baseQuery)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->count();

        $rescheduleCount = (clone $baseQuery)
            ->where('status', 'reschedule_requested')
            ->count();

        $completedCount = (clone $baseQuery)
            ->where('status', 'completed')
            ->count();

        $cancelledCount = (clone $baseQuery)
            ->where('status', 'cancelled')
            ->count();

        $totalCount = (clone $baseQuery)->count();

        $stats = [
            'total'      => $totalCount,
            'today'      => $todayCount,
            'upcoming'   => $upcomingCount,
            'reschedule' => $rescheduleCount,
            'completed'  => $completedCount,
            'cancelled'  => $cancelledCount,
        ];

        // Filtering
        $query = clone $baseQuery;
        $tab = $request->input('tab', 'all');

        if ($tab === 'today') {
            $query->whereDate('scheduled_at', Carbon::today())
                  ->whereIn('status', ['scheduled', 'confirmed', 'reschedule_requested']);
        } elseif ($tab === 'upcoming') {
            $query->whereIn('status', ['scheduled', 'confirmed'])
                  ->where('scheduled_at', '>=', now()->startOfDay());
        } elseif ($tab === 'reschedule_requested') {
            $query->where('status', 'reschedule_requested');
        } elseif ($tab === 'completed') {
            $query->where('status', 'completed');
        } elseif ($tab === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        if ($meetingType = $request->input('meeting_type')) {
            $query->where('meeting_type', $meetingType);
        }

        if ($postId = $request->input('post_id')) {
            $query->whereHas('application', fn ($q) => $q->where('internship_post_id', $postId));
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('application', function ($aq) use ($search) {
                $aq->whereHas('studentProfile', function ($sq) use ($search) {
                    $sq->where('major', 'like', "%{$search}%")
                       ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                })->orWhereHas('internshipPost', function ($pq) use ($search) {
                    $pq->where('title', 'like', "%{$search}%");
                });
            });
        }

        // Order: reschedule requests & upcoming first, then by scheduled_at
        $interviews = $query
            ->orderByRaw("CASE WHEN status = 'reschedule_requested' THEN 0 WHEN status IN ('scheduled', 'confirmed') THEN 1 ELSE 2 END")
            ->orderBy('scheduled_at', 'asc')
            ->paginate(10)
            ->withQueryString();

        $companyPosts = InternshipPost::where('company_profile_id', $company?->id)->get();

        // Eligible candidates for scheduling a new interview directly from this page
        $eligibleApplications = Application::with(['studentProfile.user', 'internshipPost'])
            ->whereIn('internship_post_id', $postIds)
            ->whereIn('status', ['pending', 'under_review', 'shortlisted', 'interviewed'])
            ->latest('applied_at')
            ->get();

        return view('company.interviews.index', compact(
            'interviews',
            'stats',
            'tab',
            'companyPosts',
            'eligibleApplications'
        ));
    }

    /**
     * Schedule a new interview directly from the Interviews Management page.
     */
    public function store(Request $request): RedirectResponse
    {
        $company = Auth::user()->companyProfile;

        $validated = $request->validate([
            'application_id'   => ['required', 'integer', 'exists:applications,id'],
            'scheduled_at'     => ['required', 'date', 'after:now'],
            'meeting_type'     => ['required', 'in:online,in_person'],
            'location_or_link' => ['required', 'string', 'max:500'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $application = Application::with(['studentProfile.user', 'internshipPost'])->findOrFail($validated['application_id']);

        abort_if(!$company || $application->internshipPost->company_profile_id !== $company->id, 403, 'Unauthorized.');

        if ($application->status === 'withdrawn') {
            return back()->with('error', 'Cannot schedule an interview for a withdrawn application.');
        }

        $interview = $application->interviews()->create([
            'scheduled_at'         => $validated['scheduled_at'],
            'meeting_type'         => $validated['meeting_type'],
            'location_or_link'     => $validated['location_or_link'],
            'notes'                => $validated['notes'] ?? null,
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
            $studentUser->notify(new InterviewScheduledNotification($interview));
        }

        $dateFormatted = Carbon::parse($validated['scheduled_at'])->format('M d, Y \a\t g:i A');

        return back()->with('success', "Interview scheduled for {$studentUser?->name} on {$dateFormatted}.");
    }

    /**
     * Reschedule or update details of an existing interview.
     */
    public function update(Request $request, Interview $interview): RedirectResponse
    {
        $company = Auth::user()->companyProfile;
        $interview->load(['application.studentProfile.user', 'application.internshipPost']);

        abort_if(
            !$company || $interview->application?->internshipPost?->company_profile_id !== $company->id,
            403,
            'Unauthorized.'
        );

        $validated = $request->validate([
            'scheduled_at'     => ['required', 'date', 'after:now'],
            'meeting_type'     => ['required', 'in:online,in_person'],
            'location_or_link' => ['required', 'string', 'max:500'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $interview->update([
            'scheduled_at'         => $validated['scheduled_at'],
            'meeting_type'         => $validated['meeting_type'],
            'location_or_link'     => $validated['location_or_link'],
            'notes'                => $validated['notes'] ?? null,
            'status'               => 'scheduled',
            'confirmed_by_student' => false,
            'confirmed_at'         => null,
            'reschedule_reason'    => null,
        ]);

        $studentUser = $interview->application?->studentProfile?->user;
        if ($studentUser) {
            $studentUser->notify(new InterviewScheduledNotification($interview->fresh()));
        }

        $dateFormatted = Carbon::parse($validated['scheduled_at'])->format('M d, Y \a\t g:i A');

        return back()->with('success', "Interview with {$studentUser?->name} has been updated to {$dateFormatted}. Candidate notified.");
    }

    /**
     * Update interview status (Mark as Completed / Cancelled) and optionally record hiring decision.
     */
    public function updateStatus(Request $request, Interview $interview): RedirectResponse
    {
        $company = Auth::user()->companyProfile;
        $interview->load(['application.studentProfile.user', 'application.internshipPost']);

        abort_if(
            !$company || $interview->application?->internshipPost?->company_profile_id !== $company->id,
            403,
            'Unauthorized.'
        );

        $validated = $request->validate([
            'status'           => ['required', 'in:completed,cancelled,scheduled,confirmed'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'hiring_decision'  => ['nullable', 'in:keep_interviewed,accepted,rejected'],
            'company_notes'    => ['nullable', 'string', 'max:2000'],
        ]);

        $interview->update([
            'status' => $validated['status'],
            'notes'  => array_key_exists('notes', $validated) && $validated['notes'] !== null ? $validated['notes'] : $interview->notes,
        ]);

        $application = $interview->application;
        $studentUser = $application?->studentProfile?->user;
        $postTitle   = $application?->internshipPost?->title ?? 'Position';

        if ($validated['status'] === 'cancelled' && $studentUser) {
            $studentUser->notify(new AppNotification(
                title: 'Interview Cancelled',
                message: "{$company->company_name} has cancelled the scheduled interview for '{$postTitle}'.",
                actionUrl: route('student.applications.index'),
                icon: 'fa-solid fa-calendar-xmark',
                color: 'rose'
            ));

            return back()->with('success', "Interview with {$studentUser->name} has been cancelled.");
        }

        // Handle post-interview hiring decision if provided
        $decision = $validated['hiring_decision'] ?? 'keep_interviewed';
        if ($application && in_array($decision, ['accepted', 'rejected'], true) && $application->status !== 'withdrawn') {
            $application->update([
                'status'        => $decision,
                'company_notes' => !empty($validated['company_notes']) ? $validated['company_notes'] : $application->company_notes,
                'reviewed_at'   => now(),
            ]);

            $statusLabel = ucfirst($decision);
            if ($studentUser) {
                $studentUser->notify(new AppNotification(
                    title: "Application Status: {$statusLabel}",
                    message: "{$company->company_name} updated your application for '{$postTitle}' to {$statusLabel} following your interview.",
                    actionUrl: route('student.applications.index'),
                    icon: $decision === 'accepted' ? 'fa-solid fa-award' : 'fa-solid fa-circle-xmark',
                    color: $decision === 'accepted' ? 'emerald' : 'rose'
                ));
            }

            return back()->with('success', "Interview marked as completed and candidate status updated to {$statusLabel}.");
        }

        if ($application && !empty($validated['company_notes'])) {
            $application->update([
                'company_notes' => $validated['company_notes'],
            ]);
        }

        return back()->with('success', 'Interview status and evaluation notes saved.');
    }
}
