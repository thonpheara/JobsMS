<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipPost;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ApplicantReviewController extends Controller
{
    /**
     * List all applications received for this company's posts.
     */
    public function index(Request $request): View
    {
        $company = Auth::user()->companyProfile;
        $postIds = InternshipPost::where('company_profile_id', $company?->id)->pluck('id');

        $query = Application::with(['studentProfile.user', 'internshipPost', 'interview'])
            ->whereIn('internship_post_id', $postIds);

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('applications.status', $status);
        }

        // Filter by post
        if ($postId = $request->input('post_id')) {
            $query->where('applications.internship_post_id', $postId);
        }

        // Filter by opportunity type
        if ($oppType = $request->input('opportunity_type')) {
            if ($oppType === 'internship') {
                $query->whereHas('internshipPost', function ($q) {
                    $q->whereNull('opportunity_type')->orWhere('opportunity_type', 'internship');
                });
            } elseif ($oppType === 'job') {
                $query->whereHas('internshipPost', function ($q) {
                    $q->whereIn('opportunity_type', ['full_time', 'part_time', 'contract']);
                });
            }
        }

        // Filter by reschedule requests only
        if ($request->boolean('reschedule_only')) {
            $query->whereHas('interview', function ($iq) {
                $iq->where('status', 'reschedule_requested');
            });
        }

        // Filter by search keyword
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('studentProfile', function ($sq) use ($search) {
                    $sq->where('major', 'like', "%{$search}%")
                       ->orWhereHas('user', function ($uq) use ($search) {
                           $uq->where('name', 'like', "%{$search}%");
                       });
                })->orWhereHas('internshipPost', function ($pq) use ($search) {
                    $pq->where('title', 'like', "%{$search}%");
                });
            });
        }

        // Sort (Latest or Highest GPA)
        $sort = $request->input('sort', 'latest');
        if ($sort === 'gpa_desc') {
            $query->leftJoin('student_profiles', 'applications.student_profile_id', '=', 'student_profiles.id')
                  ->select('applications.*')
                  ->orderByDesc('student_profiles.gpa')
                  ->orderByDesc('applications.applied_at');
        } else {
            $query->latest('applications.applied_at');
        }

        $applications = (clone $query)->paginate(10)->withQueryString();
        $companyPosts = InternshipPost::where('company_profile_id', $company?->id)->get();
        $rescheduleCount = Application::whereIn('internship_post_id', $postIds)
            ->whereHas('interview', fn ($q) => $q->where('status', 'reschedule_requested'))
            ->count();

        // Preload lightweight applicant records for the interactive Kanban Pipeline Board
        $kanbanAppsRaw = (clone $query)->get();

        $kanbanApplications = $kanbanAppsRaw->map(function ($app) {
            $candUser = $app->studentProfile?->user;
            $candAvatar = $candUser?->avatar_path ? asset('storage/' . ltrim($candUser->avatar_path, '/')) : null;
            $candInitials = strtoupper(substr($candUser?->name ?? 'Candidate', 0, 2));
            $appResumePath = $app->getEffectiveResumePath();
            $resumeExists = $appResumePath && (Storage::disk('local')->exists($appResumePath) || Storage::disk('public')->exists($appResumePath));
            $resumeFileName = $resumeExists ? basename($appResumePath) : '';
            $resumeExt = strtolower(pathinfo($resumeFileName, PATHINFO_EXTENSION));
            $canPreview = $resumeExists && in_array($resumeExt, ['pdf', 'png', 'jpg', 'jpeg']);

            return [
                'id' => $app->id,
                'status' => $app->status,
                'candidate_name' => $candUser?->name ?? 'Unknown Student',
                'profile_url' => $app->student_profile_id ? route('company.students.show', $app->student_profile_id) : null,
                'candidate_initials' => $candInitials,
                'avatar_url' => $candAvatar,
                'major' => $app->studentProfile?->major ?? 'N/A',
                'gpa' => $app->studentProfile?->gpa ? number_format((float)$app->studentProfile->gpa, 2) : 'N/A',
                'gpa_numeric' => $app->studentProfile?->gpa ? (float)$app->studentProfile->gpa : 0,
                'skills' => is_array($app->studentProfile?->skills) ? array_slice($app->studentProfile->skills, 0, 3) : [],
                'role_title' => $app->internshipPost?->title ?? 'Internship',
                'is_internship' => $app->internshipPost?->isInternship() ?? true,
                'opportunity_type_label' => $app->internshipPost?->opportunity_type_label ?? 'Internship',
                'post_id' => $app->internship_post_id,
                'applied_at' => $app->applied_at ? \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') : '',
                'applied_timestamp' => $app->applied_at ? \Carbon\Carbon::parse($app->applied_at)->timestamp : 0,
                'applied_human' => $app->applied_at ? \Carbon\Carbon::parse($app->applied_at)->diffForHumans() : '',
                'cover_letter' => $app->cover_letter ?? '',
                'company_notes' => $app->company_notes ?? '',
                'has_resume' => (bool)$resumeExists,
                'resume_name' => $resumeFileName,
                'resume_preview_url' => $canPreview ? route('company.applicants.resume', $app) : null,
                'resume_download_url' => $resumeExists ? route('company.applicants.resume', [$app, 'download' => 1]) : null,
                'message_url' => route('company.messages.start', $app),
                'update_url' => route('company.applicants.update', $app),
                'interview_store_url' => route('company.applicants.interview.store', $app),
                'is_withdrawn' => $app->status === 'withdrawn',
                'has_interview' => $app->interview !== null,
                'interview' => $app->interview ? [
                    'id'                   => $app->interview->id,
                    'scheduled_at'         => $app->interview->scheduled_at->format('Y-m-d\TH:i'),
                    'scheduled_human'      => $app->interview->scheduled_at->format('M d, Y \a\t g:i A'),
                    'meeting_type'         => $app->interview->meeting_type,
                    'location_or_link'     => $app->interview->location_or_link,
                    'notes'                => $app->interview->notes ?? '',
                    'status'               => $app->interview->status,
                    'confirmed_by_student' => (bool)$app->interview->confirmed_by_student,
                    'reschedule_reason'    => $app->interview->reschedule_reason ?? '',
                    'is_online'            => $app->interview->isOnline(),
                ] : null,
            ];
        });

        return view('company.applicants.index', compact('applications', 'companyPosts', 'kanbanApplications', 'rescheduleCount'));
    }

    /**
     * Update applicant status and optional company notes.
     */
    public function updateStatus(Request $request, Application $application): JsonResponse|RedirectResponse
    {
        $company = Auth::user()->companyProfile;

        // Ensure post belongs to this company
        if ($application->internshipPost->company_profile_id !== $company?->id) {
            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort(403, 'Unauthorized.');
        }

        if ($application->status === 'withdrawn') {
            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update a withdrawn application.',
                ], 422);
            }
            return back()->with('error', 'Cannot update a withdrawn application.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,under_review,shortlisted,interviewed,accepted,rejected'],
            'company_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $application->update([
            'status' => $validated['status'],
            'company_notes' => array_key_exists('company_notes', $validated) ? $validated['company_notes'] : $application->company_notes,
            'reviewed_at' => now(),
        ]);

        // Notify candidate of status update
        $statusLabel = ucfirst(str_replace('_', ' ', $validated['status']));
        $this->notifyStatusUpdate($application, $company, $validated['status'], $statusLabel);

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => "Candidate status updated to {$statusLabel}.",
                'status' => $validated['status'],
                'status_label' => $statusLabel,
                'application_id' => $application->id,
                'company_notes' => $application->company_notes,
            ]);
        }

        return back()->with('success', "Candidate status updated to {$statusLabel}.");
    }

    /**
     * Bulk update status for multiple selected applications owned by this company.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse|RedirectResponse
    {
        $company = Auth::user()->companyProfile;

        $validated = $request->validate([
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['integer'],
            'status' => ['required', 'in:pending,under_review,shortlisted,interviewed,accepted,rejected'],
        ]);

        $applications = Application::with(['studentProfile.user', 'internshipPost'])
            ->whereIn('id', $validated['application_ids'])
            ->whereHas('internshipPost', fn ($q) => $q->where('company_profile_id', $company?->id))
            ->get();

        $statusLabel = ucfirst(str_replace('_', ' ', $validated['status']));
        $updatedCount = 0;

        foreach ($applications as $application) {
            if ($application->status === 'withdrawn') {
                continue;
            }

            $application->update([
                'status' => $validated['status'],
                'reviewed_at' => now(),
            ]);

            $this->notifyStatusUpdate($application, $company, $validated['status'], $statusLabel);
            $updatedCount++;
        }

        if ($updatedCount === 0) {
            $msg = 'No eligible applications were updated (withdrawn applications cannot be modified).';
            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $msg = "Updated {$updatedCount} " . ($updatedCount === 1 ? 'candidate' : 'candidates') . " to {$statusLabel}.";
        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'updated_count' => $updatedCount,
                'status' => $validated['status'],
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Send an in-app notification to the candidate when their application status changes.
     */
    private function notifyStatusUpdate(Application $application, mixed $company, string $status, string $statusLabel): void
    {
        $application->studentProfile?->user?->notify(new \App\Notifications\AppNotification(
            title: "Application Status: {$statusLabel}",
            message: "{$company?->company_name} updated your application for '{$application->internshipPost->title}' to {$statusLabel}.",
            actionUrl: route('student.applications.index'),
            icon: match ($status) {
                'accepted' => 'fa-solid fa-award',
                'shortlisted' => 'fa-solid fa-star',
                'rejected' => 'fa-solid fa-circle-xmark',
                default => 'fa-solid fa-file-circle-check',
            },
            color: match ($status) {
                'accepted' => 'emerald',
                'shortlisted' => 'teal',
                'rejected' => 'rose',
                default => 'blue',
            }
        ));
    }

    /**
     * Preview or download an applicant's resume.
     */
    public function resume(Request $request, Application $application)
    {
        $company = Auth::user()->companyProfile;
        abort_if(!$company || $application->internshipPost->company_profile_id !== $company->id, 403, 'Unauthorized.');

        $resumePath = $application->getEffectiveResumePath();
        if (!$resumePath) {
            return back()->with('error', 'No resume document attached to this application.');
        }

        // Check private local disk first, fallback to public disk for legacy uploads
        $disk = Storage::disk('local')->exists($resumePath) ? 'local' : (Storage::disk('public')->exists($resumePath) ? 'public' : null);

        if (!$disk) {
            return back()->with('error', 'The candidate resume file is not available on the server disk.');
        }

        $fullPath = Storage::disk($disk)->path($resumePath);
        if (!file_exists($fullPath)) {
            return back()->with('error', 'The candidate resume file is not available on the server disk.');
        }

        // Keep exact original file name - never rename
        $filename = basename($resumePath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // For non-previewable files (docx, doc, etc.) or when download parameter is present, download with exact filename
        if ($request->has('download') || !in_array($extension, ['pdf', 'png', 'jpg', 'jpeg'])) {
            return response()->download($fullPath, $filename, [
                'Content-Disposition' => 'attachment; filename="' . addcslashes($filename, '"\\') . '"; filename*=UTF-8\'\'' . rawurlencode($filename),
            ]);
        }

        return response()->file($fullPath, [
            'Content-Disposition' => 'inline; filename="' . addcslashes($filename, '"\\') . '"; filename*=UTF-8\'\'' . rawurlencode($filename),
        ]);
    }

    /**
     * Schedule an interview session with a candidate.
     */
    public function scheduleInterview(Request $request, Application $application): JsonResponse|RedirectResponse
    {
        $company = Auth::user()->companyProfile;
        abort_if(!$company || $application->internshipPost->company_profile_id !== $company->id, 403, 'Unauthorized.');

        if ($application->status === 'withdrawn') {
            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => 'Cannot schedule an interview for a withdrawn application.'], 422);
            }
            return back()->with('error', 'Cannot schedule an interview for a withdrawn application.');
        }

        $validated = $request->validate([
            'scheduled_at'     => ['required', 'date', 'after:now'],
            'meeting_type'     => ['required', 'in:online,in_person'],
            'location_or_link' => ['required', 'string', 'max:500'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $interview = $application->interviews()->create([
            'scheduled_at'         => $validated['scheduled_at'],
            'meeting_type'         => $validated['meeting_type'],
            'location_or_link'     => $validated['location_or_link'],
            'notes'                => $validated['notes'] ?? null,
            'status'               => 'scheduled',
            'confirmed_by_student' => false,
        ]);

        // Advance application status to 'interviewed' if in earlier review stages
        if (in_array($application->status, ['pending', 'under_review', 'shortlisted'], true)) {
            $application->update([
                'status'      => 'interviewed',
                'reviewed_at' => now(),
            ]);
        }

        // Notify Student via AppNotification + Email + Realtime Broadcast
        $studentUser = $application->studentProfile?->user;
        if ($studentUser) {
            $studentUser->notify(new \App\Notifications\InterviewScheduledNotification($interview));
        }

        $dateFormatted = \Carbon\Carbon::parse($validated['scheduled_at'])->format('M d, Y \a\t g:i A');

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success'            => true,
                'message'            => "Interview scheduled for {$dateFormatted}. Invitation sent to candidate.",
                'interview'          => $interview,
                'application_status' => $application->fresh()->status,
            ]);
        }

        return back()->with('success', "Interview scheduled for {$studentUser?->name} on {$dateFormatted}. Invitation sent via in-app notification & email.");
    }

    /**
     * Display a student applicant's full recruiter-facing profile.
     */
    public function showStudentProfile(StudentProfile $student): View
    {
        $company = Auth::user()->companyProfile;
        $postIds = InternshipPost::where('company_profile_id', $company?->id)->pluck('id');

        $applications = Application::with(['internshipPost', 'interview'])
            ->where('student_profile_id', $student->id)
            ->whereIn('internship_post_id', $postIds)
            ->latest('applied_at')
            ->get();

        abort_if($applications->isEmpty(), 403, 'You can only view profiles of students who have applied to your listings.');

        $student->load('user');

        return view('company.students.show', compact('student', 'applications'));
    }
}
