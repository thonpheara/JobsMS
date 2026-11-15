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

class CompanyPortalUpdatesTest extends TestCase
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
            'major'   => 'Software Engineering',
            'gpa'     => 3.80,
            'skills'  => ['Laravel', 'Tailwind', 'Alpine.js'],
        ]);

        $this->companyUser = User::factory()->create([
            'role'   => 'company',
            'status' => 'active',
            'name'   => 'Angkor Technology',
        ]);
        $this->company = CompanyProfile::create([
            'user_id'             => $this->companyUser->id,
            'company_name'        => 'Angkor Technology',
            'industry'            => 'Software & Cloud Engineering',
            'location'            => 'Phnom Penh',
            'contact_person'      => 'Pheara Thon',
            'contact_phone'       => '+855 12 345 678',
            'verification_status' => 'verified',
        ]);

        $this->post = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Backend Laravel Intern',
            'slug'               => 'backend-laravel-intern-' . uniqid(),
            'category'           => 'IT / Software',
            'type'               => 'remote',
            'opportunity_type'   => 'internship',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'slots'              => 3,
            'deadline'           => now()->addDays(20),
            'description'        => 'Build REST APIs and web portals using Laravel 12.',
            'requirements'       => 'PHP, Laravel, MySQL',
            'status'             => 'approved',
        ]);

        $this->application = Application::create([
            'student_profile_id' => $this->student->id,
            'internship_post_id' => $this->post->id,
            'cover_letter'       => 'I am excited to join Angkor Technology.',
            'status'             => 'shortlisted',
            'applied_at'         => now(),
        ]);
    }

    public function test_company_can_view_and_filter_interviews_page(): void
    {
        Interview::create([
            'application_id'   => $this->application->id,
            'scheduled_at'     => now()->addDays(2),
            'meeting_type'     => 'online',
            'location_or_link' => 'https://meet.google.com/abc-defg-hij',
            'status'           => 'scheduled',
        ]);

        $response = $this->actingAs($this->companyUser)
            ->get(route('company.interviews.index', ['meeting_type' => 'online']));

        $response->assertStatus(200);
        $response->assertSee('Backend Laravel Intern');
        $response->assertSee('Sokha Chan');
    }

    public function test_company_can_schedule_reschedule_and_complete_interview_on_web(): void
    {
        Notification::fake();

        // Schedule
        $storeResponse = $this->actingAs($this->companyUser)->post(route('company.interviews.store'), [
            'application_id'   => $this->application->id,
            'scheduled_at'     => now()->addDays(3)->format('Y-m-d H:i:s'),
            'meeting_type'     => 'online',
            'location_or_link' => 'https://meet.google.com/xyz-1234-abc',
            'notes'            => 'Prepare portfolio demo.',
        ]);
        $storeResponse->assertRedirect();

        $interview = Interview::first();
        $this->assertNotNull($interview);
        $this->assertEquals('scheduled', $interview->status);
        $this->assertEquals('interviewed', $this->application->fresh()->status);
        Notification::assertSentTo($this->studentUser, InterviewScheduledNotification::class);

        // Simulate student reschedule request
        $interview->update([
            'status'            => 'reschedule_requested',
            'reschedule_reason' => 'Have university exam at that hour',
        ]);

        // Company reschedules
        $updateResponse = $this->actingAs($this->companyUser)->put(route('company.interviews.update', $interview), [
            'scheduled_at'     => now()->addDays(5)->format('Y-m-d H:i:s'),
            'meeting_type'     => 'in_person',
            'location_or_link' => 'Room 302, Phnom Penh HQ',
            'notes'            => 'Updated time after exam.',
        ]);
        $updateResponse->assertRedirect();
        $this->assertEquals('scheduled', $interview->fresh()->status);
        $this->assertNull($interview->fresh()->reschedule_reason);

        // Mark completed
        $statusResponse = $this->actingAs($this->companyUser)->patch(route('company.interviews.status', $interview), [
            'status' => 'completed',
            'notes'  => 'Strong Laravel fundamentals.',
        ]);
        $statusResponse->assertRedirect();
        $this->assertEquals('completed', $interview->fresh()->status);
    }

    public function test_company_cannot_modify_another_companys_interview(): void
    {
        $otherCompanyUser = User::factory()->create(['role' => 'company', 'status' => 'active']);
        CompanyProfile::create([
            'user_id'             => $otherCompanyUser->id,
            'company_name'        => 'Other Corp',
            'verification_status' => 'verified',
        ]);

        $interview = Interview::create([
            'application_id'   => $this->application->id,
            'scheduled_at'     => now()->addDays(2),
            'meeting_type'     => 'online',
            'location_or_link' => 'https://meet.google.com/test',
            'status'           => 'scheduled',
        ]);

        $this->actingAs($otherCompanyUser)
            ->patch(route('company.interviews.status', $interview), ['status' => 'cancelled'])
            ->assertStatus(403);
    }

    public function test_company_can_toggle_close_and_filter_posts(): void
    {
        $draftPost = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'UI/UX Draft Role',
            'slug'               => 'ui-ux-draft-' . uniqid(),
            'category'           => 'Design',
            'type'               => 'on_site',
            'opportunity_type'   => 'internship',
            'location'           => 'Siem Reap',
            'duration_weeks'     => 8,
            'slots'              => 2,
            'deadline'           => now()->addDays(10),
            'description'        => 'Design mobile and web user interfaces.',
            'requirements'       => 'Figma',
            'status'             => 'draft',
        ]);

        // Toggle approved -> closed
        $this->actingAs($this->companyUser)
            ->post(route('company.posts.toggle-close', $this->post))
            ->assertRedirect();
        $this->assertEquals('closed', $this->post->fresh()->status);

        // Toggle closed -> pending_approval (reopened for admin approval)
        $this->actingAs($this->companyUser)
            ->post(route('company.posts.toggle-close', $this->post))
            ->assertRedirect();
        $this->assertEquals('pending_approval', $this->post->fresh()->status);

        // Cannot toggle draft post
        $this->actingAs($this->companyUser)
            ->post(route('company.posts.toggle-close', $draftPost))
            ->assertSessionHas('info');
        $this->assertEquals('draft', $draftPost->fresh()->status);

        // Work mode filter (re-approve post for clean state)
        $this->post->update(['status' => 'approved']);
        $filterResponse = $this->actingAs($this->companyUser)
            ->get(route('company.posts.index', ['work_mode' => 'remote', 'sort' => 'applicants_desc']));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('Backend Laravel Intern');
        $filterResponse->assertDontSee('UI/UX Draft Role');
    }

    public function test_company_profile_shows_real_verification_status_active_count_and_sidebar_label(): void
    {
        $response = $this->actingAs($this->companyUser)->get(route('company.profile'));
        $response->assertStatus(200);
        $response->assertSee('1 Active Listing');
        $response->assertSee('Profile Completeness');
        $response->assertSee('Preview Public Profile');
        $response->assertDontSee('Company Profile</span>', false);

        // Pending company sees pending banner instead of hardcoded Verified
        $this->company->update(['verification_status' => 'pending']);
        $this->companyUser->unsetRelation('companyProfile');
        $pendingResponse = $this->actingAs($this->companyUser)->get(route('company.profile'));
        $pendingResponse->assertStatus(200);
        $pendingResponse->assertSee('Account Pending University Verification');
        $pendingResponse->assertSee('Pending Review');
    }

    public function test_company_api_supports_interviews_toggle_close_and_bulk_status(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->companyUser);

        // Profile API includes active_posts_count & completeness_percent
        $this->getJson('/api/company/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.active_posts_count', 1);

        // Toggle close API
        $this->postJson("/api/company/posts/{$this->post->id}/toggle-close")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'closed');

        $this->postJson("/api/company/posts/{$this->post->id}/toggle-close")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        // Store interview API
        $createRes = $this->postJson('/api/company/interviews', [
            'application_id'   => $this->application->id,
            'scheduled_at'     => now()->addDays(4)->format('Y-m-d H:i:s'),
            'meeting_type'     => 'online',
            'location_or_link' => 'https://meet.google.com/api-test',
        ]);
        $createRes->assertStatus(201);
        $interviewId = $createRes->json('data.interview.id');

        // List interviews API
        $this->getJson('/api/company/interviews')
            ->assertStatus(200)
            ->assertJsonPath('data.stats.total', 1);

        // Update interview status API
        $this->patchJson("/api/company/interviews/{$interviewId}/status", [
            'status' => 'completed',
        ])->assertStatus(200)->assertJsonPath('data.status', 'completed');

        // Bulk applicant status API
        $this->patchJson('/api/company/applicants/bulk-status', [
            'application_ids' => [$this->application->id],
            'status'          => 'accepted',
        ])->assertStatus(200)->assertJsonPath('data.updated_count', 1);

        $this->assertEquals('accepted', $this->application->fresh()->status);
        Notification::assertSentTo($this->studentUser, AppNotification::class);
    }
}
