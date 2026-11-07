<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InterviewResponseController extends Controller
{
    /**
     * Student confirms interview attendance.
     */
    public function confirm(Request $request, Interview $interview): JsonResponse|RedirectResponse
    {
        $student = Auth::user()->studentProfile;

        if ($interview->application->student_profile_id !== $student?->id) {
            abort(403, 'Unauthorized.');
        }

        $interview->update([
            'confirmed_by_student' => true,
            'confirmed_at'         => now(),
            'status'               => 'confirmed',
        ]);

        // Notify employer of student confirmation
        $companyUser = $interview->application->internshipPost->companyProfile?->user;
        $studentName = Auth::user()->name;
        $postTitle = $interview->application->internshipPost->title;

        $companyUser?->notify(new AppNotification(
            title: 'Interview Confirmed ✓',
            message: "{$studentName} has confirmed their interview attendance for '{$postTitle}'.",
            actionUrl: route('company.applicants.index', ['post_id' => $interview->application->internship_post_id]),
            icon: 'fa-solid fa-circle-check',
            color: 'emerald'
        ));

        // Post confirmation into Direct Chat Conversation so recruiter sees it in Messages inbox
        $application = $interview->application;
        $companyProfile = $application->internshipPost->companyProfile;
        if ($companyProfile) {
            $conversation = \App\Models\Conversation::firstOrCreate(
                [
                    'student_profile_id' => $student->id,
                    'company_profile_id' => $companyProfile->id,
                    'application_id'     => $application->id,
                ],
                [
                    'subject'         => 'Applicant: ' . Auth::user()->name . ' (' . $application->internshipPost->title . ')',
                    'last_message_at' => now(),
                ]
            );

            $scheduledDateStr = $interview->scheduled_at ? $interview->scheduled_at->format('M d, Y \a\t g:i A') : 'N/A';
            $chatMessage = $conversation->messages()->create([
                'sender_id' => Auth::id(),
                'body'      => "✓ [Interview Confirmed]\nI have confirmed my attendance for the interview session on {$scheduledDateStr}. Thank you!",
                'is_read'   => false,
            ]);

            $conversation->update(['last_message_at' => now()]);

            try {
                broadcast(new \App\Events\MessageSent($chatMessage))->toOthers();
            } catch (\Throwable $e) {}
        }

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Interview confirmed successfully.',
                'interview' => $interview->fresh(),
            ]);
        }

        return back()->with('success', 'Thank you! Your interview attendance has been confirmed.');
    }

    /**
     * Student requests interview rescheduling.
     */
    public function reschedule(Request $request, Interview $interview): JsonResponse|RedirectResponse
    {
        $student = Auth::user()->studentProfile;

        if ($interview->application->student_profile_id !== $student?->id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'reschedule_reason' => ['nullable', 'string', 'max:1000'],
            'reason'            => ['nullable', 'string', 'max:1000'],
        ]);

        $reason = $validated['reschedule_reason'] ?? $validated['reason'] ?? null;
        if (!$reason || trim($reason) === '') {
            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => 'Please provide a reason for rescheduling.'], 422);
            }
            return back()->withErrors(['reschedule_reason' => 'Please provide a reason for the reschedule request.']);
        }

        $interview->update([
            'status'               => 'reschedule_requested',
            'confirmed_by_student' => false,
            'reschedule_reason'    => $reason,
        ]);

        // Notify employer of reschedule request
        $companyUser = $interview->application->internshipPost->companyProfile?->user;
        $studentName = Auth::user()->name;
        $postTitle = $interview->application->internshipPost->title;

        $companyUser?->notify(new AppNotification(
            title: 'Interview Reschedule Requested ↺',
            message: "{$studentName} requested to reschedule their interview for '{$postTitle}'. Reason: {$reason}",
            actionUrl: route('company.applicants.index', ['post_id' => $interview->application->internship_post_id]),
            icon: 'fa-solid fa-clock-rotate-left',
            color: 'amber'
        ));

        // Post message into Direct Chat Conversation so recruiter sees it in Messages inbox
        $application = $interview->application;
        $companyProfile = $application->internshipPost->companyProfile;
        if ($companyProfile) {
            $conversation = \App\Models\Conversation::firstOrCreate(
                [
                    'student_profile_id' => $student->id,
                    'company_profile_id' => $companyProfile->id,
                    'application_id'     => $application->id,
                ],
                [
                    'subject'         => 'Applicant: ' . Auth::user()->name . ' (' . $application->internshipPost->title . ')',
                    'last_message_at' => now(),
                ]
            );

            $scheduledDateStr = $interview->scheduled_at ? $interview->scheduled_at->format('M d, Y \a\t g:i A') : 'N/A';
            $chatMessage = $conversation->messages()->create([
                'sender_id' => Auth::id(),
                'body'      => "🗓️ [Interview Reschedule Request]\nI would like to request rescheduling our interview session originally scheduled for {$scheduledDateStr}.\n\nReason & Availability:\n{$reason}",
                'is_read'   => false,
            ]);

            $conversation->update(['last_message_at' => now()]);

            try {
                broadcast(new \App\Events\MessageSent($chatMessage))->toOthers();
            } catch (\Throwable $e) {}
        }

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reschedule request submitted to recruiter.',
                'interview' => $interview->fresh(),
            ]);
        }

        return back()->with('success', 'Reschedule request sent to the recruiter. They will propose a new time.');
    }
}
