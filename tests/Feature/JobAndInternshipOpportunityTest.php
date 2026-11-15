<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\InternshipPost;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobAndInternshipOpportunityTest extends TestCase
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
            'company_name'        => 'Acme Tech Solutions',
            'industry'            => 'Information Technology',
            'location'            => 'Phnom Penh',
            'verification_status' => 'verified',
        ]);

        $this->studentUser = User::factory()->create([
            'role'   => 'student',
            'status' => 'active',
        ]);
        $this->student = StudentProfile::create([
            'user_id' => $this->studentUser->id,
            'major'   => 'Computer Science',
        ]);
    }

    /**
     * Test company can create an internship post with duration_weeks.
     */
    public function test_company_can_create_internship_opportunity(): void
    {
        $payload = [
            'title'            => 'Software QA Intern',
            'opportunity_type' => 'internship',
            'category'         => 'Quality Assurance',
            'type'             => 'hybrid',
            'location'         => 'Phnom Penh',
            'duration_weeks'   => 12,
            'slots'            => 2,
            'stipend'          => 200,
            'deadline'         => now()->addMonths(1)->format('Y-m-d'),
            'description'      => 'A 12-week intensive testing internship for students.',
            'responsibilities' => 'Write test cases and perform regression testing.',
            'requirements'     => 'Basic understanding of SDLC.',
        ];

        $response = $this->actingAs($this->companyUser)
            ->post(route('company.posts.store'), $payload);

        $response->assertRedirect(route('company.posts.index'));

        $this->assertDatabaseHas('internship_posts', [
            'company_profile_id' => $this->company->id,
            'title'              => 'Software QA Intern',
            'opportunity_type'   => 'internship',
            'duration_weeks'     => 12,
            'stipend'            => 200.00,
        ]);
    }

    /**
     * Test company can create a full-time job opportunity without duration_weeks.
     */
    public function test_company_can_create_full_time_job_opportunity(): void
    {
        $payload = [
            'title'            => 'Associate Laravel Developer',
            'opportunity_type' => 'full_time',
            'experience_level' => 'entry_level',
            'category'         => 'Software Engineering',
            'type'             => 'on_site',
            'location'         => 'Siem Reap',
            'duration_weeks'   => null,
            'slots'            => 1,
            'stipend'          => 350,
            'salary_max'       => 500,
            'deadline'         => now()->addMonths(2)->format('Y-m-d'),
            'description'      => 'Full-time junior role for recent graduates.',
            'responsibilities' => 'Build web modules and RESTful APIs.',
            'requirements'     => 'Experience with PHP and MySQL.',
        ];

        $response = $this->actingAs($this->companyUser)
            ->post(route('company.posts.store'), $payload);

        $response->assertRedirect(route('company.posts.index'));

        $this->assertDatabaseHas('internship_posts', [
            'company_profile_id' => $this->company->id,
            'title'              => 'Associate Laravel Developer',
            'opportunity_type'   => 'full_time',
            'experience_level'   => 'entry_level',
            'duration_weeks'     => null,
            'stipend'            => 350.00,
            'salary_max'         => 500.00,
        ]);
    }

    /**
     * Test company can update and edit an opportunity.
     */
    public function test_company_can_update_opportunity(): void
    {
        $post = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Frontend Dev Intern',
            'slug'               => 'frontend-dev-intern-' . uniqid(),
            'opportunity_type'   => 'internship',
            'category'           => 'Design & Tech',
            'type'               => 'on_site',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 8,
            'slots'              => 1,
            'stipend'            => 150,
            'deadline'           => now()->addMonth(),
            'description'        => 'Internship description',
            'status'             => 'approved',
        ]);

        $updateData = [
            'title'            => 'Junior Frontend Developer',
            'opportunity_type' => 'full_time',
            'experience_level' => 'junior',
            'category'         => 'Design & Tech',
            'type'             => 'remote',
            'location'         => 'Phnom Penh',
            'duration_weeks'   => null,
            'slots'            => 2,
            'stipend'          => 400,
            'salary_max'       => 650,
            'deadline'         => now()->addMonths(2)->format('Y-m-d'),
            'description'      => 'Upgraded to full-time junior position with complete technical responsibilities.',
            'status'           => 'approved',
        ];

        $response = $this->actingAs($this->companyUser)
            ->put(route('company.posts.update', $post), $updateData);

        $response->assertRedirect(route('company.posts.index'));

        $post->refresh();
        $this->assertEquals('full_time', $post->opportunity_type);
        $this->assertEquals('junior', $post->experience_level);
        $this->assertEquals(400.00, (float) $post->stipend);
        $this->assertEquals(650.00, (float) $post->salary_max);
        $this->assertTrue($post->isJob());
        $this->assertFalse($post->isInternship());
    }

    /**
     * Test student can browse and filter opportunities by type on Web.
     */
    public function test_student_can_filter_by_opportunity_type_on_web(): void
    {
        $internship = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Data Analyst Intern',
            'slug'               => 'data-analyst-intern-' . uniqid(),
            'opportunity_type'   => 'internship',
            'category'           => 'Data Science',
            'type'               => 'on_site',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'slots'              => 1,
            'stipend'            => 200,
            'deadline'           => now()->addMonth(),
            'description'        => 'Intern position',
            'status'             => 'approved',
        ]);

        $job = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Full-Time Cloud Architect',
            'slug'               => 'cloud-architect-' . uniqid(),
            'opportunity_type'   => 'full_time',
            'experience_level'   => 'mid_level',
            'category'           => 'Cloud Computing',
            'type'               => 'remote',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => null,
            'slots'              => 1,
            'stipend'            => 800,
            'salary_max'         => 1200,
            'deadline'           => now()->addMonth(),
            'description'        => 'Full-time cloud position',
            'status'             => 'approved',
        ]);

        // Filter Internships only
        $internshipsResponse = $this->actingAs($this->studentUser)
            ->get(route('student.posts.index', ['opportunity_type' => 'internship']));

        $internshipsResponse->assertStatus(200);
        $internshipsResponse->assertSee('Data Analyst Intern');
        $internshipsResponse->assertDontSee('Full-Time Cloud Architect');

        // Filter Jobs umbrella
        $jobsResponse = $this->actingAs($this->studentUser)
            ->get(route('student.posts.index', ['opportunity_type' => 'job']));

        $jobsResponse->assertStatus(200);
        $jobsResponse->assertSee('Full-Time Cloud Architect');
        $jobsResponse->assertDontSee('Data Analyst Intern');
    }

    /**
     * Test student can view single opportunity page with compensation and badges.
     */
    public function test_student_can_view_job_detail_page(): void
    {
        $job = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Full-Time Mobile App Engineer',
            'slug'               => 'mobile-app-engineer-' . uniqid(),
            'opportunity_type'   => 'full_time',
            'experience_level'   => 'junior',
            'category'           => 'Mobile',
            'type'               => 'hybrid',
            'location'           => 'Phnom Penh',
            'slots'              => 2,
            'stipend'            => 400,
            'salary_max'         => 700,
            'deadline'           => now()->addMonth(),
            'description'        => 'Build Flutter and Native Android apps.',
            'status'             => 'approved',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.posts.show', $job));

        $response->assertStatus(200);
        $response->assertSee('Full-Time Mobile App Engineer');
        $response->assertSee('Full-Time');
        $response->assertSee('Junior');
        $response->assertSee('$400 - $700/mo');
    }

    /**
     * Test Android REST API returns opportunity fields and supports filtering.
     */
    public function test_android_api_opportunity_filtering_and_response_structure(): void
    {
        $internship = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'UI/UX Design Intern',
            'slug'               => 'uiux-design-intern-' . uniqid(),
            'opportunity_type'   => 'internship',
            'category'           => 'Design',
            'type'               => 'on_site',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 12,
            'slots'              => 1,
            'stipend'            => 150,
            'deadline'           => now()->addMonth(),
            'description'        => 'Figma intern',
            'status'             => 'approved',
        ]);

        $job = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Junior Cyber Security Analyst',
            'slug'               => 'junior-cyber-security-' . uniqid(),
            'opportunity_type'   => 'part_time',
            'experience_level'   => 'entry_level',
            'category'           => 'Security',
            'type'               => 'remote',
            'location'           => 'Siem Reap',
            'slots'              => 1,
            'stipend'            => 300,
            'salary_max'         => 450,
            'deadline'           => now()->addMonth(),
            'description'        => 'Part-time analyst role',
            'status'             => 'approved',
        ]);

        $token = $this->studentUser->createToken('android-test')->plainTextToken;

        // 1. Fetch only jobs via API
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/student/posts?opportunity_type=job');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $titles = collect($response->json('data.data'))->pluck('title')->toArray();
        $this->assertContains('Junior Cyber Security Analyst', $titles);
        $this->assertNotContains('UI/UX Design Intern', $titles);

        // Verify JSON fields
        $jobData = collect($response->json('data.data'))->firstWhere('id', $job->id);
        $this->assertEquals('part_time', $jobData['opportunity_type']);
        $this->assertEquals('entry_level', $jobData['experience_level']);
        $this->assertTrue($jobData['is_job']);
        $this->assertFalse($jobData['is_internship']);
        $this->assertStringContainsString('$300 - $450/mo', $jobData['compensation_display']);

        // 2. Fetch by experience level
        $responseEntry = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/student/posts?experience_level=entry_level');

        $responseEntry->assertStatus(200);
        $entryTitles = collect($responseEntry->json('data.data'))->pluck('title')->toArray();
        $this->assertContains('Junior Cyber Security Analyst', $entryTitles);
    }

    /**
     * Test Android API company post creation with opportunity_type.
     */
    public function test_android_api_company_can_store_job_post(): void
    {
        $token = $this->companyUser->createToken('company-test')->plainTextToken;

        $payload = [
            'title'            => 'Contract Backend Engineer',
            'opportunity_type' => 'contract',
            'experience_level' => 'mid_level',
            'category'         => 'Engineering',
            'type'             => 'remote',
            'location'         => 'Phnom Penh',
            'slots'            => 1,
            'stipend'          => 700,
            'salary_max'       => 1000,
            'deadline'         => now()->addMonths(2)->format('Y-m-d'),
            'description'      => 'A 6-month contract role for an experienced backend engineer to develop APIs.',
            'requirements'     => 'Proficiency in PHP, Laravel, and MySQL.',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/company/posts', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'title'            => 'Contract Backend Engineer',
                    'opportunity_type' => 'contract',
                    'experience_level' => 'mid_level',
                ],
            ]);

        $this->assertDatabaseHas('internship_posts', [
            'company_profile_id' => $this->company->id,
            'title'              => 'Contract Backend Engineer',
            'opportunity_type'   => 'contract',
            'experience_level'   => 'mid_level',
        ]);
    }
}
