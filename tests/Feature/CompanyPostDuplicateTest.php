<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\InternshipPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPostDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private User $companyUser;
    private CompanyProfile $company;
    private InternshipPost $post;
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
            'company_name'        => 'Sunrise Software',
            'industry'            => 'Information Technology',
            'location'            => 'Phnom Penh',
            'verification_status' => 'verified',
        ]);

        $this->post = InternshipPost::create([
            'company_profile_id' => $this->company->id,
            'title'              => 'Flutter Mobile Engineer Intern',
            'slug'               => 'flutter-mobile-engineer-intern',
            'category'           => 'Mobile Development',
            'type'               => 'hybrid',
            'location'           => 'Phnom Penh',
            'duration_weeks'     => 16,
            'stipend'            => 350.00,
            'slots'              => 3,
            'deadline'           => now()->addDays(20),
            'description'        => 'Develop cross-platform mobile apps for Android and iOS using Flutter and Dart.',
            'responsibilities'   => 'Implement UI designs, integrate REST APIs, and write widget tests.',
            'requirements'       => 'Basic knowledge of Dart, Flutter widgets, and Git.',
            'status'             => 'approved',
        ]);

        $this->studentUser = User::factory()->create([
            'role'   => 'student',
            'status' => 'active',
        ]);
    }

    /**
     * Test company can duplicate an existing post via web UI.
     */
    public function test_company_can_duplicate_post_via_web(): void
    {
        $response = $this->actingAs($this->companyUser)
            ->post(route('company.posts.duplicate', $this->post));

        $this->assertDatabaseCount('internship_posts', 2);

        $duplicated = InternshipPost::where('id', '!=', $this->post->id)->first();
        $this->assertNotNull($duplicated);
        $this->assertEquals('Flutter Mobile Engineer Intern (Copy)', $duplicated->title);
        $this->assertEquals('draft', $duplicated->status);
        $this->assertEquals('Mobile Development', $duplicated->category);
        $this->assertEquals('Phnom Penh', $duplicated->location);
        $this->assertEquals('hybrid', $duplicated->type);
        $this->assertEquals(16, $duplicated->duration_weeks);
        $this->assertEquals(350.00, (float)$duplicated->stipend);
        $this->assertEquals(3, $duplicated->slots);
        $this->assertTrue(now()->parse($duplicated->deadline)->isFuture());

        $response->assertRedirect(route('company.posts.index', ['edit' => $duplicated->id]));
        $response->assertSessionHas('success');
    }

    /**
     * Test company cannot duplicate a post belonging to another company.
     */
    public function test_company_cannot_duplicate_other_company_post(): void
    {
        $otherCompanyUser = User::factory()->create(['role' => 'company']);
        CompanyProfile::create([
            'user_id'             => $otherCompanyUser->id,
            'company_name'        => 'Other Tech Co',
            'verification_status' => 'verified',
        ]);

        $response = $this->actingAs($otherCompanyUser)
            ->post(route('company.posts.duplicate', $this->post));

        $response->assertStatus(403);
        $this->assertDatabaseCount('internship_posts', 1);
    }

    /**
     * Test student cannot duplicate post.
     */
    public function test_student_cannot_duplicate_post(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->post(route('company.posts.duplicate', $this->post));

        $response->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseCount('internship_posts', 1);
    }

    /**
     * Test duplicating a post via mobile REST API.
     */
    public function test_company_can_duplicate_post_via_api(): void
    {
        $response = $this->actingAs($this->companyUser, 'sanctum')
            ->postJson(route('api.company.posts.duplicate', $this->post->id));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'title'    => 'Flutter Mobile Engineer Intern (Copy)',
                    'status'   => 'draft',
                    'category' => 'Mobile Development',
                ],
            ]);

        $this->assertDatabaseCount('internship_posts', 2);
    }

    /**
     * Test duplicating non-existent or other company post via API returns 404.
     */
    public function test_cannot_duplicate_unauthorized_post_via_api(): void
    {
        $otherCompanyUser = User::factory()->create(['role' => 'company']);
        CompanyProfile::create([
            'user_id'             => $otherCompanyUser->id,
            'company_name'        => 'Competitor Labs',
            'verification_status' => 'verified',
        ]);

        $response = $this->actingAs($otherCompanyUser, 'sanctum')
            ->postJson(route('api.company.posts.duplicate', $this->post->id));

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }
}

