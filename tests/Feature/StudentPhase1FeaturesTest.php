<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CompanyProfile;
use App\Models\InternshipPost;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StudentPhase1FeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;
    private StudentProfile $student;
    private User $companyUser;
    private CompanyProfile $company;
    private InternshipPost $post;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->studentUser = User::factory()->create([
            'role'   => 'student',
            'status' => 'active',
        ]);
        $this->student = StudentProfile::create([
            'user_id' => $this->studentUser->id,
            'major'   => 'Software Engineering',
            'skills'  => ['PHP', 'Laravel', 'VueJS'],
        ]);

        $this->companyUser = User::factory()->create([
            'role'   => 'company',
            'status' => 'active',
        ]);
        $this->company = CompanyProfile::create([
            'user_id'             => $this->companyUser->id,
            'company_name'        => 'Acme Tech Cambodia',
            'industry'            => 'Information Technology',
            'location'            => 'Phnom Penh',
            'verification_status' => 'verified',
        ]);

        $this->post = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Laravel & Vue Web Developer Intern',
            'slug'               => 'laravel-vue-web-dev-intern',
            'category'           => 'Web Development',
            'type'               => 'on_site',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'stipend'            => 250.00,
            'slots'              => 2,
            'deadline'           => now()->addDays(5), // closing soon (<= 7 days)
            'description'        => 'Build web applications using PHP Laravel and Vue.js.',
            'requirements'       => 'Knowledge of Laravel, PHP, and VueJS.',
            'status'             => 'approved',
        ]);

        $this->token = $this->studentUser->createToken('test-token')->plainTextToken;
    }

    /**
     * Feature 1.2: Post Bookmark / Saved Jobs (Toggle & List)
     */
    public function test_student_can_bookmark_and_unbookmark_posts(): void
    {
        // 1. Bookmark the post
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/student/posts/{$this->post->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'post_id'    => $this->post->id,
                    'bookmarked' => true,
                ],
            ]);

        $this->assertTrue($this->student->fresh()->hasBookmarked($this->post->id));

        // 2. Fetch bookmarks
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/student/bookmarks');

        $listResponse->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertCount(1, $listResponse->json('data.data'));
        $this->assertEquals($this->post->id, $listResponse->json('data.data.0.id'));

        // 3. Un-bookmark the post (toggle off)
        $unbookmarkResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/student/posts/{$this->post->id}/bookmark");

        $unbookmarkResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'bookmarked' => false,
                ],
            ]);

        $this->assertFalse($this->student->fresh()->hasBookmarked($this->post->id));
    }

    /**
     * Feature 1.3: Advanced Filter & Sort
     */
    public function test_student_can_filter_and_sort_posts_api(): void
    {
        // Create a non-stipend post
        $unpaidPost = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Volunteer IT Assistant',
            'slug'               => 'volunteer-it-assistant',
            'category'           => 'IT Support',
            'type'               => 'remote',
            'location'           => 'Siem Reap',
            'duration_weeks'     => 8,
            'stipend'            => null,
            'slots'              => 1,
            'deadline'           => now()->addMonths(2),
            'description'        => 'Assist students with basic PC troubleshooting.',
            'requirements'       => 'Basic Windows skills.',
            'status'             => 'approved',
        ]);

        // Filter: stipend=has
        $stipendResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/student/posts?stipend=has');

        $stipendResponse->assertStatus(200);
        $this->assertCount(1, $stipendResponse->json('data.data'));
        $this->assertEquals('Laravel & Vue Web Developer Intern', $stipendResponse->json('data.data.0.title'));

        // Filter: deadline=soon (<= 7 days)
        $soonResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/student/posts?deadline=soon');

        $soonResponse->assertStatus(200);
        $this->assertCount(1, $soonResponse->json('data.data'));
        $this->assertEquals($this->post->id, $soonResponse->json('data.data.0.id'));

        // Sort: stipend high to low
        $sortResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/student/posts?sort=stipend');

        $sortResponse->assertStatus(200);
        $this->assertEquals(250.00, (float) $sortResponse->json('data.data.0.stipend'));
    }

    /**
     * Feature 1.5: Withdraw Application
     */
    public function test_student_can_withdraw_pending_application(): void
    {
        $application = Application::create([
            'internship_post_id' => $this->post->id,
            'student_profile_id' => $this->student->id,
            'cover_letter'       => 'I am eager to contribute to your engineering team with Laravel skills.',
            'status'             => 'pending',
            'applied_at'         => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/student/applications/{$application->id}/withdraw");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'id'     => $application->id,
                    'status' => 'withdrawn',
                ],
            ]);

        $this->assertEquals('withdrawn', $application->fresh()->status);
        $this->assertTrue($application->fresh()->isWithdrawn());

        // Host company should have received notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->companyUser->id,
            'notifiable_type' => User::class,
        ]);
    }

    /**
     * Feature 1.5: Cannot withdraw already accepted application
     */
    public function test_student_cannot_withdraw_accepted_application(): void
    {
        $application = Application::create([
            'internship_post_id' => $this->post->id,
            'student_profile_id' => $this->student->id,
            'cover_letter'       => 'Accepted offer candidate application.',
            'status'             => 'accepted',
            'applied_at'         => now()->subDays(5),
            'reviewed_at'        => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/student/applications/{$application->id}/withdraw");

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertEquals('accepted', $application->fresh()->status);
    }

    /**
     * Feature 1.5: Student can re-apply after withdrawing
     */
    public function test_student_can_reapply_after_withdrawing(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('local')->put('resumes/dummy.pdf', 'fake pdf content');

        $application = Application::create([
            'internship_post_id' => $this->post->id,
            'student_profile_id' => $this->student->id,
            'cover_letter'       => 'Initial application before withdrawal.',
            'status'             => 'withdrawn',
            'applied_at'         => now()->subDays(2),
        ]);

        $this->student->update(['resume_path' => 'resumes/dummy.pdf']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/student/posts/{$this->post->id}/apply", [
                'cover_letter' => 'Re-submitting my updated application for this role.',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertEquals('pending', $application->fresh()->status);
        $this->assertStringContainsString('Re-submitting', $application->fresh()->cover_letter);
    }

    /**
     * Feature 1.6: Company Public Profile API
     */
    public function test_student_can_view_company_public_profile(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/student/companies/{$this->company->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'company' => [
                        'id'           => $this->company->id,
                        'company_name' => 'Acme Tech Cambodia',
                        'industry'     => 'Information Technology',
                    ],
                    'is_verified' => true,
                ],
            ]);

        $this->assertCount(1, $response->json('data.active_posts'));
        $this->assertEquals($this->post->id, $response->json('data.active_posts.0.id'));
    }

    /**
     * Feature 1.7: Student Dashboard Recommends Posts Matching Skills / Major
     */
    public function test_student_dashboard_recommends_posts_by_skills(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/student/dashboard');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $recommended = $response->json('data.recommended_posts');
        $this->assertNotEmpty($recommended);

        // The Laravel & Vue post should be recommended with smart match true
        $matchedPost = collect($recommended)->firstWhere('id', $this->post->id);
        $this->assertNotNull($matchedPost);
        $this->assertTrue($matchedPost['has_smart_match']);
        $this->assertContains('Laravel', $matchedPost['matched_skills']);
    }
}
