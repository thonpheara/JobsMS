<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CompanyProfile;
use App\Models\InternshipPost;
use App\Models\Interview;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Notifications\InterviewScheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyInterviewSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;
    private StudentProfile $student;
    private User $companyUser;
    private CompanyProfile $company;
    private InternshipPost $post;
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->studentUser = User::factory()->create([
            'role'   => 'student',
            'status' => 'active',
            'name'   => 'Sokha Chan',
        ]);
        $this->student = StudentProfile::create([
            'user_id' => $this->studentUser->id,
            'major'   => 'Computer Science',
            'gpa'     => 3.75,
            'skills'  => ['PHP', 'Laravel', 'Alpine.js'],
        ]);

        $this->companyUser = User::factory()->create([
            'role'   => 'company',
            'status' => 'active',
        ]);
        $this->company = CompanyProfile::create([
            'user_id'             => $this->companyUser->id,
            'company_name'        => 'Khmer Innovations',
            'industry'            => 'Technology',
            'location'            => 'Phnom Penh',
            'verification_status' => 'verified',
        ]);

        $this->post = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Backend Laravel Intern',
            'slug'               => 'backend-laravel-intern',
            'category'           => 'Web Development',
            'type'               => 'on_site',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'stipend'            => 300.00,
            'slots'              => 2,
            'deadline'           => now()->addDays(14),
            'description'        => 'Work on enterprise web systems.',
            'requirements'       => 'Laravel, MySQL, REST APIs.',
            'status'             => 'approved',
        ]);

        $this->application = Application::create([
            'student_profile_id' => $this->student->id,
            'internship_post_id' => $this->post->id,
            'status'             => 'pending',
            'cover_letter'       => 'I am highly interested in backend development with Laravel.',
            'applied_at'         => now(),
        ]);
    }

    /**
     * Test company can schedule an online video interview via web form.
     */
    public function test_company_can_schedule_online_interview_via_web(): void
    {
        Notification::fake();

        $scheduledAt = now()->addDays(3)->setTime(14, 0)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->companyUser)
            ->post(route('company.applicants.interview.store', $this->application), [
                'scheduled_at'     => $scheduledAt,
                'meeting_type'     => 'online',
                'location_or_link' => 'https://meet.google.com/xyz-abcd-efg',
                'notes'            => 'Please prepare a 5-minute introduction and review Laravel MVC principles.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('interviews', [
            'application_id'       => $this->application->id,
            'meeting_type'         => 'online',
            'location_or_link'     => 'https://meet.google.com/xyz-abcd-efg',
            'status'               => 'scheduled',
            'confirmed_by_student' => false,
        ]);

        // Application status automatically advanced to 'interviewed'
        $this->assertEquals('interviewed', $this->application->fresh()->status);

        // Student notified via InterviewScheduledNotification (mail + db + broadcast)
        Notification::assertSentTo(
            $this->studentUser,
            InterviewScheduledNotification::class,
            function ($notification) {
                return $notification->interview->application_id === $this->application->id;
            }
        );
    }

    /**
     * Test company can schedule an in-person physical interview.
     */
    public function test_company_can_schedule_in_person_interview_via_web(): void
    {
        Notification::fake();

        $scheduledAt = now()->addDays(5)->setTime(10, 30)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->companyUser)
            ->post(route('company.applicants.interview.store', $this->application), [
                'scheduled_at'     => $scheduledAt,
                'meeting_type'     => 'in_person',
                'location_or_link' => 'Canadia Tower, 14th Floor, Room 1402, Phnom Penh',
                'notes'            => 'Bring national ID or student card for building security access.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('interviews', [
            'application_id'   => $this->application->id,
            'meeting_type'     => 'in_person',
            'location_or_link' => 'Canadia Tower, 14th Floor, Room 1402, Phnom Penh',
            'status'           => 'scheduled',
        ]);
    }

    /**
     * Test student can confirm their interview attendance.
     */
    public function test_student_can_confirm_interview_attendance(): void
    {
        Notification::fake();

        $interview = Interview::create([
            'application_id'       => $this->application->id,
            'scheduled_at'         => now()->addDays(2),
            'meeting_type'         => 'online',
            'location_or_link'     => 'https://meet.google.com/test',
            'status'               => 'scheduled',
            'confirmed_by_student' => false,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.interviews.confirm', $interview));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($interview->fresh()->confirmed_by_student);
        $this->assertEquals('confirmed', $interview->fresh()->status);
        $this->assertNotNull($interview->fresh()->confirmed_at);

        // Recruiter receives confirmation notification
        Notification::assertSentTo(
            $this->companyUser,
            AppNotification::class,
            function ($notification) {
                return str_contains($notification->title, 'Interview Confirmed');
            }
        );
    }

    /**
     * Test student can request an interview reschedule with reason.
     */
    public function test_student_can_request_reschedule_with_reason(): void
    {
        Notification::fake();

        $interview = Interview::create([
            'application_id'       => $this->application->id,
            'scheduled_at'         => now()->addDays(2),
            'meeting_type'         => 'online',
            'location_or_link'     => 'https://meet.google.com/test',
            'status'               => 'scheduled',
            'confirmed_by_student' => false,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.interviews.reschedule', $interview), [
                'reschedule_reason' => 'I have a university mid-term exam at that time. I am available any afternoon after 3:00 PM.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse($interview->fresh()->confirmed_by_student);
        $this->assertEquals('reschedule_requested', $interview->fresh()->status);
        $this->assertStringContainsString('university mid-term exam', $interview->fresh()->reschedule_reason);

        // Recruiter receives reschedule notification
        Notification::assertSentTo(
            $this->companyUser,
            AppNotification::class,
            function ($notification) {
                return str_contains($notification->title, 'Interview Reschedule Requested');
            }
        );

        // Chat message is automatically created in conversation thread for recruiter
        $this->assertDatabaseHas('conversations', [
            'application_id'     => $this->application->id,
            'company_profile_id' => $this->company->id,
            'student_profile_id' => $this->student->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->studentUser->id,
        ]);
    }

    /**
     * Test other student cannot confirm or reschedule someone else's interview.
     */
    public function test_unauthorized_student_cannot_confirm_other_interview(): void
    {
        $otherUser = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $otherStudent = StudentProfile::create(['user_id' => $otherUser->id, 'major' => 'Law']);

        $interview = Interview::create([
            'application_id'       => $this->application->id,
            'scheduled_at'         => now()->addDays(2),
            'meeting_type'         => 'online',
            'location_or_link'     => 'https://meet.google.com/test',
            'status'               => 'scheduled',
            'confirmed_by_student' => false,
        ]);

        $response = $this->actingAs($otherUser)
            ->post(route('student.interviews.confirm', $interview));

        $response->assertStatus(403);
        $this->assertFalse($interview->fresh()->confirmed_by_student);
    }

    /**
     * Test other company cannot schedule interview for candidates of another company.
     */
    public function test_other_company_cannot_schedule_interview_for_candidate(): void
    {
        $otherCompanyUser = User::factory()->create(['role' => 'company', 'status' => 'active']);
        CompanyProfile::create([
            'user_id'             => $otherCompanyUser->id,
            'company_name'        => 'Other Tech Ltd',
            'verification_status' => 'verified',
        ]);

        $response = $this->actingAs($otherCompanyUser)
            ->post(route('company.applicants.interview.store', $this->application), [
                'scheduled_at'     => now()->addDays(3)->format('Y-m-d\TH:i'),
                'meeting_type'     => 'online',
                'location_or_link' => 'https://meet.google.com/hacker',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('interviews', [
            'application_id' => $this->application->id,
        ]);
    }

    /**
     * Test cannot schedule interview for withdrawn application.
     */
    public function test_cannot_schedule_interview_for_withdrawn_application(): void
    {
        $this->application->update(['status' => 'withdrawn']);

        $response = $this->actingAs($this->companyUser)
            ->post(route('company.applicants.interview.store', $this->application), [
                'scheduled_at'     => now()->addDays(3)->format('Y-m-d\TH:i'),
                'meeting_type'     => 'online',
                'location_or_link' => 'https://meet.google.com/test',
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('interviews', [
            'application_id' => $this->application->id,
        ]);
    }

    /**
     * Test company and student interview workflow via Android REST API.
     */
    public function test_interview_workflow_via_mobile_rest_api(): void
    {
        Notification::fake();

        // 1. Company schedules interview via REST API
        Sanctum::actingAs($this->companyUser);

        $response = $this->postJson("/api/company/applicants/{$this->application->id}/interview", [
            'scheduled_at'     => now()->addDays(4)->format('Y-m-d H:i:s'),
            'meeting_type'     => 'online',
            'location_or_link' => 'https://meet.google.com/api-session',
            'notes'            => 'Mobile interview schedule testing.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.application_status', 'interviewed');

        $interviewId = $response->json('data.interview.id');
        $this->assertNotNull($interviewId);

        // 2. Student confirms interview via REST API
        Sanctum::actingAs($this->studentUser);

        $confirmResponse = $this->postJson("/api/student/interviews/{$interviewId}/confirm");
        $confirmResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.confirmed_by_student', true)
            ->assertJsonPath('data.status', 'confirmed');

        // 3. Student requests reschedule via REST API
        $rescheduleResponse = $this->postJson("/api/student/interviews/{$interviewId}/reschedule", [
            'reschedule_reason' => 'Schedule conflict with university presentation.',
        ]);

        $rescheduleResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'reschedule_requested')
            ->assertJsonPath('data.reschedule_reason', 'Schedule conflict with university presentation.');

        // 4. Verify student endpoints eager-load interview details and companyProfile.user
        $appDetailResponse = $this->getJson("/api/student/applications/{$this->application->id}");
        $appDetailResponse->assertStatus(200)
            ->assertJsonPath('data.interview.id', $interviewId)
            ->assertJsonPath('data.interview.type', 'online')
            ->assertJsonPath('data.interview.location', 'https://meet.google.com/api-session')
            ->assertJsonPath('data.interview.status', 'reschedule_requested')
            ->assertJsonPath('data.interview.reschedule_reason', 'Schedule conflict with university presentation.')
            ->assertJsonPath('data.internship_post.company_profile.user.id', $this->companyUser->id);

        $appListResponse = $this->getJson('/api/student/applications');
        $appListResponse->assertStatus(200)
            ->assertJsonPath('data.data.0.interview.id', $interviewId)
            ->assertJsonPath('data.data.0.interviews.0.id', $interviewId)
            ->assertJsonPath('data.data.0.internship_post.company_profile.user.id', $this->companyUser->id);

        $dashboardResponse = $this->getJson('/api/student/dashboard');
        $dashboardResponse->assertStatus(200)
            ->assertJsonPath('data.recent_applications.0.interview.id', $interviewId)
            ->assertJsonPath('data.recent_applications.0.internship_post.company_profile.user.id', $this->companyUser->id);
    }
}

