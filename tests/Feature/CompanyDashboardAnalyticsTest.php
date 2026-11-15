<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CompanyProfile;
use App\Models\InternshipPost;
use App\Models\Interview;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyDashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $companyUser;
    private CompanyProfile $company;
    private User $studentUser;
    private StudentProfile $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyUser = User::factory()->create([
            'role'   => 'company',
            'status' => 'active',
        ]);
        $this->company = CompanyProfile::create([
            'user_id'             => $this->companyUser->id,
            'company_name'        => 'Acme Digital Labs',
            'industry'            => 'Technology',
            'location'            => 'Phnom Penh',
            'verification_status' => 'verified',
        ]);

        $this->studentUser = User::factory()->create([
            'role'   => 'student',
            'status' => 'active',
            'name'   => 'Dara Sok',
        ]);
        $this->student = StudentProfile::create([
            'user_id' => $this->studentUser->id,
            'major'   => 'Computer Science',
            'gpa'     => 3.80,
        ]);
    }

    /**
     * Test company dashboard renders successfully with top 5 posts bar chart and status donut chart.
     */
    public function test_company_dashboard_renders_with_chart_analytics(): void
    {
        // Create 3 posts for this company with varying applications
        $post1 = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Laravel Backend Engineer',
            'slug'               => 'laravel-backend-engineer',
            'category'           => 'Web Development',
            'type'               => 'on_site',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'stipend'            => 350.00,
            'slots'              => 2,
            'deadline'           => now()->addDays(20),
            'description'        => 'Work on backend web services.',
            'requirements'       => 'PHP, Laravel, MySQL.',
            'status'             => 'approved',
        ]);

        $post2 = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Frontend Vue/Tailwind Developer',
            'slug'               => 'frontend-vue-tailwind-developer',
            'category'           => 'Web Development',
            'type'               => 'on_site',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'stipend'            => 300.00,
            'slots'              => 3,
            'deadline'           => now()->addDays(25),
            'description'        => 'Build modern user interfaces.',
            'requirements'       => 'Tailwind CSS, Vue.js.',
            'status'             => 'approved',
        ]);

        // Submit applications to post 1 (2 applications)
        Application::create([
            'student_profile_id' => $this->student->id,
            'internship_post_id' => $post1->id,
            'status'             => 'accepted',
            'cover_letter'       => 'Excited to apply for Laravel role.',
            'applied_at'         => now()->subDays(2),
        ]);

        $student2User = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $student2 = StudentProfile::create(['user_id' => $student2User->id, 'major' => 'IT']);
        Application::create([
            'student_profile_id' => $student2->id,
            'internship_post_id' => $post1->id,
            'status'             => 'pending',
            'cover_letter'       => 'Another application.',
            'applied_at'         => now()->subDay(),
        ]);

        // Submit application to post 2 (1 application)
        $student3User = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $student3 = StudentProfile::create(['user_id' => $student3User->id, 'major' => 'CS']);
        Application::create([
            'student_profile_id' => $student3->id,
            'internship_post_id' => $post2->id,
            'status'             => 'interviewed',
            'cover_letter'       => 'Frontend enthusiast.',
            'applied_at'         => now(),
        ]);

        $response = $this->actingAs($this->companyUser)->get(route('company.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('topPostsChart');
        $response->assertViewHas('donutData');

        $topPosts = $response->viewData('topPostsChart');
        $this->assertTrue($topPosts['hasData']);
        $this->assertEquals(3, $topPosts['total']);
        $this->assertEquals('Laravel Backend Engineer', $topPosts['fullTitles'][0]);
        $this->assertEquals(2, $topPosts['data'][0]);

        $donut = $response->viewData('donutData');
        $this->assertEquals(3, $donut['total']);
        $this->assertEquals(1, $donut['counts']['applied']);      // 1 pending
        $this->assertEquals(0, $donut['counts']['shortlisted']);  // 0 shortlisted
        $this->assertEquals(1, $donut['counts']['interviewing']); // 1 interviewed
        $this->assertEquals(1, $donut['counts']['accepted']);     // 1 accepted
        $this->assertEquals(0, $donut['counts']['rejected']);

        $response->assertSee('topPostsBarCanvas');
        $response->assertSee('companyStatusDonutCanvas');
        $response->assertSee('Applications per Listing');
        $response->assertSee('Candidacy Outcomes');
    }

    /**
     * Test company dashboard handles 0 applications cleanly with empty state.
     */
    public function test_company_dashboard_empty_state_when_no_applications(): void
    {
        $response = $this->actingAs($this->companyUser)->get(route('company.dashboard'));

        $response->assertStatus(200);
        $topPosts = $response->viewData('topPostsChart');
        $this->assertFalse($topPosts['hasData']);
        $this->assertEquals(0, $topPosts['total']);

        $donut = $response->viewData('donutData');
        $this->assertEquals(0, $donut['total']);
        $this->assertEquals(0, $donut['placementRate']);

        $response->assertSee('No applications received yet');
    }

    /**
     * Test company only sees their own posts and applications in dashboard charts.
     */
    public function test_company_cannot_see_other_companies_data_in_charts(): void
    {
        // Other company with posts and applicants
        $otherCompanyUser = User::factory()->create(['role' => 'company', 'status' => 'active']);
        $otherCompany = CompanyProfile::create([
            'user_id'             => $otherCompanyUser->id,
            'company_name'        => 'Other Corp',
            'verification_status' => 'verified',
        ]);
        $otherPost = InternshipPost::create([
            'company_profile_id' => $otherCompany->id,
            'title'              => 'Secret Agent Intern',
            'slug'               => 'secret-agent-intern',
            'category'           => 'Security',
            'type'               => 'on_site',
            'location'           => 'Siem Reap',
            'duration_weeks'     => 10,
            'slots'              => 1,
            'deadline'           => now()->addDays(10),
            'description'        => 'Confidential role.',
            'requirements'       => 'Classified.',
            'status'             => 'approved',
        ]);
        Application::create([
            'student_profile_id' => $this->student->id,
            'internship_post_id' => $otherPost->id,
            'status'             => 'accepted',
            'cover_letter'       => 'Other company application.',
            'applied_at'         => now(),
        ]);

        $response = $this->actingAs($this->companyUser)->get(route('company.dashboard'));

        $response->assertStatus(200);
        $topPosts = $response->viewData('topPostsChart');
        $this->assertFalse(in_array('Secret Agent Intern', $topPosts['fullTitles'], true));
        $this->assertEquals(0, $topPosts['total']);

        $donut = $response->viewData('donutData');
        $this->assertEquals(0, $donut['total']);
    }

    /**
     * Test Android mobile REST API returns analytics payload for company dashboard.
     */
    public function test_company_api_dashboard_includes_analytics(): void
    {
        $post = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Mobile Android Intern',
            'slug'               => 'mobile-android-intern',
            'category'           => 'Mobile',
            'type'               => 'on_site',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'slots'              => 2,
            'deadline'           => now()->addDays(15),
            'description'        => 'Kotlin & Jetpack Compose development.',
            'requirements'       => 'Kotlin.',
            'status'             => 'approved',
        ]);

        Application::create([
            'student_profile_id' => $this->student->id,
            'internship_post_id' => $post->id,
            'status'             => 'accepted',
            'cover_letter'       => 'Applying for mobile role.',
            'applied_at'         => now(),
        ]);

        Sanctum::actingAs($this->companyUser);

        $response = $this->getJson('/api/company/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'company',
                    'stats' => ['total_posts', 'active_posts', 'total_applicants', 'pending_review', 'accepted'],
                    'analytics' => [
                        'top_posts' => ['labels', 'full_titles', 'data', 'ids', 'total', 'has_data'],
                        'status_distribution' => ['labels', 'data', 'colors', 'total', 'placement_rate', 'counts', 'percentages'],
                    ],
                    'recent_applicants',
                    'upcoming_interviews',
                ],
            ])
            ->assertJsonPath('data.analytics.top_posts.total', 1)
            ->assertJsonPath('data.analytics.status_distribution.total', 1)
            ->assertJsonPath('data.analytics.status_distribution.placement_rate', 100);
    }

    /**
     * Test company dashboard renders upcoming interviews and reschedule alerts.
     */
    public function test_company_dashboard_renders_upcoming_interviews_and_reschedule_alert(): void
    {
        $post = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Flutter Mobile Engineer',
            'slug'               => 'flutter-mobile-engineer-' . uniqid(),
            'category'           => 'Mobile',
            'type'               => 'remote',
            'location'           => 'Phnom Penh',
            'slots'              => 1,
            'deadline'           => now()->addMonth(),
            'description'        => 'Build cross-platform mobile apps.',
            'status'             => 'approved',
        ]);

        $application = Application::create([
            'internship_post_id' => $post->id,
            'student_profile_id' => $this->student->id,
            'status'             => 'interviewed',
            'applied_at'         => now()->subDays(2),
        ]);

        // Create an upcoming interview with a reschedule request
        $interview = Interview::create([
            'application_id'       => $application->id,
            'scheduled_at'         => now()->addDays(2),
            'meeting_type'         => 'online',
            'location_or_link'     => 'https://meet.google.com/abc-defg-hij',
            'status'               => 'reschedule_requested',
            'confirmed_by_student' => false,
            'reschedule_reason'    => 'I have a university final exam on that morning.',
        ]);

        $response = $this->actingAs($this->companyUser)->get(route('company.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Upcoming Scheduled Interviews');
        $response->assertSee('Dara Sok');
        $response->assertSee('Flutter Mobile Engineer');
        $response->assertSee('Reschedule');
        $response->assertSee('I have a university final exam on that morning.');
        $response->assertSee('Join Call');
        $response->assertSee('Interview Reschedule Request');
    }

    /**
     * Test unauthorized users cannot access company dashboard.
     */
    public function test_non_company_cannot_access_company_dashboard(): void
    {
        // Student is redirected away from company dashboard
        $response = $this->actingAs($this->studentUser)->get(route('company.dashboard'));
        $response->assertRedirect(route('student.dashboard'));

        // Guest is redirected to login
        $this->app['auth']->forgetGuards();
        $guestResponse = $this->get(route('company.dashboard'));
        $guestResponse->assertRedirect(route('login'));
    }
}

