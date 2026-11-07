<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\InternshipPost;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CompanyPostDraftModeTest extends TestCase
{
    use RefreshDatabase;

    private User $companyUser;
    private CompanyProfile $company;
    private User $adminUser;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyUser = User::factory()->create([
            'role'   => 'company',
            'status' => 'active',
        ]);
        $this->company = CompanyProfile::create([
            'user_id'             => $this->companyUser->id,
            'company_name'        => 'Mega Systems Cambodia',
            'industry'            => 'Information Technology',
            'location'            => 'Phnom Penh',
            'verification_status' => 'verified',
        ]);

        $this->adminUser = User::factory()->create([
            'role'   => 'admin',
            'status' => 'active',
        ]);

        $this->studentUser = User::factory()->create([
            'role'   => 'student',
            'status' => 'active',
        ]);
    }

    /**
     * Test company can save post as draft without notifying admin.
     */
    public function test_company_can_save_post_as_draft(): void
    {
        Notification::fake();

        $postData = [
            'title'            => 'Junior DevOps Engineer Intern',
            'category'         => 'DevOps & Cloud',
            'location'         => 'Phnom Penh',
            'duration_weeks'   => 12,
            'slots'            => 2,
            'stipend'          => 300.00,
            'deadline'         => now()->addDays(25)->toDateString(),
            'description'      => 'Learn Linux, Docker containers, CI/CD pipelines, and cloud automation.',
            'responsibilities' => 'Assist infrastructure setup and monitoring.',
            'requirements'     => 'Basic Linux and shell scripting knowledge.',
            'action'           => 'draft',
        ];

        $response = $this->actingAs($this->companyUser)
            ->post(route('company.posts.store'), $postData);

        $response->assertRedirect(route('company.posts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('internship_posts', [
            'company_profile_id' => $this->company->id,
            'title'              => 'Junior DevOps Engineer Intern',
            'status'             => 'draft',
        ]);

        // Admins should NOT be notified for drafts
        Notification::assertNothingSent();
    }

    /**
     * Test company submitting for review immediately notifies admin.
     */
    public function test_company_can_submit_post_directly_for_review(): void
    {
        Notification::fake();

        $postData = [
            'title'            => 'Data Analyst Intern',
            'category'         => 'Data Science & Analytics',
            'location'         => 'Phnom Penh',
            'duration_weeks'   => 16,
            'slots'            => 1,
            'stipend'          => 280.00,
            'deadline'         => now()->addDays(20)->toDateString(),
            'description'      => 'Clean and analyze data sets using Python Pandas and generate business reports.',
            'responsibilities' => 'Extract data and build dashboards.',
            'requirements'     => 'Knowledge of SQL and basic Python.',
            'action'           => 'submit',
        ];

        $response = $this->actingAs($this->companyUser)
            ->post(route('company.posts.store'), $postData);

        $response->assertRedirect(route('company.posts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('internship_posts', [
            'company_profile_id' => $this->company->id,
            'title'              => 'Data Analyst Intern',
            'status'             => 'pending_approval',
        ]);

        Notification::assertSentTo($this->adminUser, AppNotification::class);
    }

    /**
     * Test company can submit an existing draft post for admin approval.
     */
    public function test_company_can_submit_existing_draft_for_approval(): void
    {
        Notification::fake();

        $draftPost = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Draft QA Tester Intern',
            'slug'               => 'draft-qa-tester-intern',
            'category'           => 'Quality Assurance',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'slots'              => 1,
            'deadline'           => now()->addDays(15),
            'description'        => 'Write test cases and execute regression testing on mobile apps.',
            'status'             => 'draft',
        ]);

        $response = $this->actingAs($this->companyUser)
            ->post(route('company.posts.submit', $draftPost));

        $response->assertRedirect(route('company.posts.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('pending_approval', $draftPost->fresh()->status);
        Notification::assertSentTo($this->adminUser, AppNotification::class);
    }

    /**
     * Test draft posts are NOT visible in admin approval queue.
     */
    public function test_draft_posts_invisible_in_admin_approvals(): void
    {
        InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Private Company Draft Vacancy',
            'slug'               => 'private-company-draft-vacancy',
            'category'           => 'General IT',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'slots'              => 1,
            'deadline'           => now()->addDays(15),
            'description'        => 'Internal draft description for company planning only.',
            'status'             => 'draft',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.approvals.index'));

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertFalse($posts->contains('title', 'Private Company Draft Vacancy'));

        // Even if requested with ?status=draft
        $draftResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.approvals.index', ['status' => 'draft']));

        $draftResponse->assertStatus(200);
        $draftPosts = $draftResponse->viewData('posts');
        $this->assertFalse($draftPosts->contains('title', 'Private Company Draft Vacancy'));
    }

    /**
     * Test draft posts are NOT visible to students browsing jobs.
     */
    public function test_draft_posts_invisible_to_students(): void
    {
        InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Unpublished Draft Intern',
            'slug'               => 'unpublished-draft-intern',
            'category'           => 'Web Development',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'slots'              => 1,
            'deadline'           => now()->addDays(20),
            'description'        => 'Should not be seen by any student until approved.',
            'status'             => 'draft',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.posts.index'));

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertFalse($posts->contains('title', 'Unpublished Draft Intern'));
    }

    /**
     * Test API: Save draft post and submit post for review.
     */
    public function test_company_can_manage_draft_post_via_api(): void
    {
        Notification::fake();

        // 1. Create Draft via API
        $response = $this->actingAs($this->companyUser, 'sanctum')
            ->postJson(route('api.company.posts.store'), [
                'title'          => 'API Draft Position',
                'category'       => 'Mobile Development',
                'location'       => 'Phnom Penh',
                'type'           => 'on_site',
                'duration_weeks' => 12,
                'slots'          => 2,
                'deadline'       => now()->addDays(30)->toDateString(),
                'description'    => 'Valid description that has more than thirty characters required.',
                'requirements'   => 'Flutter and Dart skills.',
                'status'         => 'draft',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'title'  => 'API Draft Position',
                    'status' => 'draft',
                ],
            ]);

        $postId = $response->json('data.id');
        Notification::assertNothingSent();

        // 2. Submit Draft via API
        $submitResponse = $this->actingAs($this->companyUser, 'sanctum')
            ->postJson(route('api.company.posts.submit', $postId));

        $submitResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'status' => 'pending_approval',
                ],
            ]);

        Notification::assertSentTo($this->adminUser, AppNotification::class);
    }
}

