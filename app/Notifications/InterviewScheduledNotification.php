<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewScheduledNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public Interview $interview
    ) {}

    public function via(object $notifiable): array
    {
        // Support database notifications, live websocket broadcast, and email
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = $this->interview->application;
        $company = $app->internshipPost->companyProfile;
        $dateFormatted = $this->interview->scheduled_at->format('l, F j, Y \a\t g:i A');

        $mail = (new MailMessage)
            ->subject("Interview Invitation: {$app->internshipPost->title} with {$company->company_name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Congratulations! **{$company->company_name}** has scheduled an interview for your application to **{$app->internshipPost->title}**.")
            ->line("**🗓️ Date & Time:** {$dateFormatted}")
            ->line("**📍 Type & Location / Link:** {$this->interview->location_or_link}");

        if (!empty($this->interview->notes)) {
            $mail->line("**📝 Recruiter Notes:** {$this->interview->notes}");
        }

        return $mail
            ->action('View & Confirm Interview', route('student.applications.index'))
            ->line('Please confirm your attendance or request a reschedule if you have a scheduling conflict.')
            ->salutation("Best regards,\n{$company->company_name} & University Placement Office");
    }

    public function toArray(object $notifiable): array
    {
        $app = $this->interview->application;
        $company = $app->internshipPost->companyProfile;
        $dateFormatted = $this->interview->scheduled_at->format('M d, g:i A');

        return [
            'title'      => 'Interview Scheduled 🗓️',
            'message'    => "{$company->company_name} invited you for an interview for '{$app->internshipPost->title}' on {$dateFormatted}.",
            'action_url' => route('student.applications.index'),
            'icon'       => 'fa-solid fa-calendar-check',
            'color'      => 'indigo',
            'interview_id' => $this->interview->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $app = $this->interview->application;
        $company = $app->internshipPost->companyProfile;
        $dateFormatted = $this->interview->scheduled_at->format('M d, g:i A');

        return new BroadcastMessage([
            'id'               => $this->id,
            'title'            => 'Interview Scheduled 🗓️',
            'message'          => "{$company->company_name} invited you for an interview for '{$app->internshipPost->title}' on {$dateFormatted}.",
            'action_url'       => route('student.applications.index'),
            'icon'             => 'fa-solid fa-calendar-check',
            'color'            => 'indigo',
            'created_at_human' => 'Just now',
        ]);
    }
}

