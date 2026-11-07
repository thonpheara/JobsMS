<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipPost;
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
            $query->where('status', $status);
        }

        // Filter by post
        if ($postId = $request->input('post_id')) {
            $query->where('internship_post_id', $postId);
        }

        $applications = (clone $query)->latest('applied_at')->paginate(10)->withQueryString();
        $companyPosts = InternshipPost::where('company_profile_id', $company?->id)->get();

        // Preload lightweight applicant records for the interactive Kanban Pipeline Board
        $kanbanAppsRaw = (clone $query)->latest('applied_at')->get();

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
                'candidate_initials' => $candInitials,
                'avatar_url' => $candAvatar,
                'major' => $app->studentProfile?->major ?? 'N/A',
                'gpa' => $app->studentProfile?->gpa ? number_format((float)$app->studentProfile->gpa, 2) : 'N/A',
                'skills' => is_array($app->studentProfile?->skills) ? array_slice($app->studentProfile->skills, 0, 3) : [],
                'role_title' => $app->internshipPost?->title ?? 'Internship',
                'post_id' => $app->internship_post_id,
                'applied_at' => $app->applied_at ? \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') : '',
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

        return view('company.applicants.index', compact('applications', 'companyPosts', 'kanbanApplications'));
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
        $application->studentProfile?->user?->notify(new \App\Notifications\AppNotification(
            title: "Application Status: {$statusLabel}",
            message: "{$company->company_name} updated your application for '{$application->internshipPost->title}' to {$statusLabel}.",
            actionUrl: route('student.applications.index'),
            icon: match($validated['status']) {
                'accepted' => 'fa-solid fa-award',
                'shortlisted' => 'fa-solid fa-star',
                'rejected' => 'fa-solid fa-circle-xmark',
                default => 'fa-solid fa-file-circle-check',
            },
            color: match($validated['status']) {
                'accepted' => 'emerald',
                'shortlisted' => 'teal',
                'rejected' => 'rose',
                default => 'blue',
            }
        ));

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

        return back()->with('success', "Candidate status updated to " . ucfirst(str_replace('_', ' ', $validated['status'])) . ".");
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
}
