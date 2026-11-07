<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CompanyProfile;
use App\Models\InternshipPost;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CompanyKanbanTest extends TestCase
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
     * Test company can view applicant board with both table and Kanban data preloaded.
     */
    public function test_company_can_view_applicants_review_board_with_kanban_data(): void
    {
        $response = $this->actingAs($this->companyUser)
            ->get(route('company.applicants.index'));

        $response->assertStatus(200);
        $response->assertViewHas('applications');
        $response->assertViewHas('companyPosts');
        $response->assertViewHas('kanbanApplications');

        $kanbanApps = $response->viewData('kanbanApplications');
        $this->assertCount(1, $kanbanApps);
        $this->assertEquals($this->application->id, $kanbanApps[0]['id']);
        $this->assertEquals('pending', $kanbanApps[0]['status']);
        $this->assertEquals('Sokha Chan', $kanbanApps[0]['candidate_name']);
        $this->assertEquals('Backend Laravel Intern', $kanbanApps[0]['role_title']);
    }

    /**
     * Test company can drag & drop applicant card, sending AJAX PATCH to update stage.
     */
    public function test_company_can_update_applicant_status_via_ajax_patch(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->companyUser)
            ->patchJson(route('company.applicants.update', $this->application), [
                'status'        => 'under_review',
                'company_notes' => 'Resume looks solid. Moving to assessment stage.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'        => true,
                'status'         => 'under_review',
                'status_label'   => 'Under review',
                'application_id' => $this->application->id,
            ]);

        $this->assertDatabaseHas('applications', [
            'id'            => $this->application->id,
            'status'        => 'under_review',
            'company_notes' => 'Resume looks solid. Moving to assessment stage.',
        ]);

        Notification::assertSentTo($this->studentUser, AppNotification::class);
    }

    /**
     * Test updating applicant status via standard HTML form PUT redirects with success.
     */
    public function test_company_can_update_applicant_status_via_form_put(): void
    {
        $response = $this->actingAs($this->companyUser)
            ->put(route('company.applicants.update', $this->application), [
                'status'        => 'shortlisted',
                'company_notes' => 'Passed first technical screen.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('applications', [
            'id'     => $this->application->id,
            'status' => 'shortlisted',
        ]);
    }

    /**
     * Test company cannot update applicant belonging to another company's post.
     */
    public function test_company_cannot_update_applicant_for_other_company(): void
    {
        $otherCompanyUser = User::factory()->create(['role' => 'company']);
        CompanyProfile::create([
            'user_id'             => $otherCompanyUser->id,
            'company_name'        => 'Rival Corp',
            'verification_status' => 'verified',
        ]);

        $response = $this->actingAs($otherCompanyUser)
            ->patchJson(route('company.applicants.update', $this->application), [
                'status' => 'accepted',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test cannot update status of an application that was withdrawn by the student.
     */
    public function test_cannot_update_withdrawn_application(): void
    {
        $this->application->update(['status' => 'withdrawn']);

        $response = $this->actingAs($this->companyUser)
            ->patchJson(route('company.applicants.update', $this->application), [
                'status' => 'under_review',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot update a withdrawn application.',
            ]);

        $this->assertEquals('withdrawn', $this->application->fresh()->status);
    }

    /**
     * Test student cannot access company applicant review board.
     */
    public function test_student_cannot_access_company_applicant_board(): void
    {
        // Web request redirects to student dashboard with error
        $response = $this->actingAs($this->studentUser)
            ->get(route('company.applicants.index'));

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('error');

        // JSON request returns 403 Forbidden
        $jsonResponse = $this->actingAs($this->studentUser)
            ->getJson(route('company.applicants.index'));

        $jsonResponse->assertStatus(403);
    }
}
