<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipPost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyDashboardController extends Controller
{
    /**
     * Display company portal dashboard with key applicant metrics & analytics charts.
     */
    public function index(): View
    {
        $company = Auth::user()->companyProfile;

        // Post IDs belonging to this company
        $postIds = InternshipPost::where('company_profile_id', $company?->id)->pluck('id');

        // Calculate application counts grouped by status in a single query
        $statusCounts = Application::whereIn('internship_post_id', $postIds)
            ->selectRaw("status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingCount     = (int) ($statusCounts['pending'] ?? 0);
        $underReviewCount = (int) ($statusCounts['under_review'] ?? 0);
        $shortlistedCount = (int) ($statusCounts['shortlisted'] ?? 0);
        $interviewedCount = (int) ($statusCounts['interviewed'] ?? 0);
        $acceptedCount    = (int) ($statusCounts['accepted'] ?? 0);
        $rejectedCount    = (int) ($statusCounts['rejected'] ?? 0);
        $withdrawnCount   = (int) ($statusCounts['withdrawn'] ?? 0);

        $totalApplicants = $statusCounts->sum();

        // 4 Key KPI Metrics
        $stats = [
            'active_posts'     => InternshipPost::where('company_profile_id', $company?->id)->where('status', 'approved')->count(),
            'total_posts'      => InternshipPost::where('company_profile_id', $company?->id)->count(),
            'total_applicants' => $totalApplicants,
            'pending_review'   => $pendingCount,
            'interviewed'      => $interviewedCount + $shortlistedCount,
            'accepted'         => $acceptedCount,
        ];

        // 1. Feature 2.5: Bar Chart — Applications received per post (Top 5 posts)
        $topPosts = InternshipPost::where('company_profile_id', $company?->id)
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->take(5)
            ->get();

        $topPostsChart = [
            'labels'     => $topPosts->map(fn($p) => Str::limit($p->title, 20))->values()->toArray(),
            'fullTitles' => $topPosts->map(fn($p) => $p->title)->values()->toArray(),
            'data'       => $topPosts->map(fn($p) => (int) $p->applications_count)->values()->toArray(),
            'ids'        => $topPosts->map(fn($p) => $p->id)->values()->toArray(),
            'total'      => $topPosts->sum('applications_count'),
            'hasData'    => $topPosts->contains(fn($p) => $p->applications_count > 0),
        ];

        // 2. Feature 2.5: Donut Chart — Applicant Status Breakdown (applied / shortlisted / accepted / rejected)
        $appliedTotal     = $pendingCount + $underReviewCount;
        $shortlistedTotal = $shortlistedCount + $interviewedCount;
        $acceptedTotal    = $acceptedCount;
        $rejectedTotal    = $rejectedCount + $withdrawnCount;
        $totalOutcomes    = $appliedTotal + $shortlistedTotal + $acceptedTotal + $rejectedTotal;
        $placementRate    = $totalOutcomes > 0 ? round(($acceptedTotal / $totalOutcomes) * 100, 1) : 0;

        $donutData = [
            'labels' => ['Applied', 'Shortlisted', 'Accepted', 'Rejected'],
            'data'   => [$appliedTotal, $shortlistedTotal, $acceptedTotal, $rejectedTotal],
            'colors' => ['#0EA5E9', '#6366F1', '#10B981', '#F43F5E'],
            'total'  => $totalOutcomes,
            'placementRate' => $placementRate,
            'counts' => [
                'applied'     => $appliedTotal,
                'shortlisted' => $shortlistedTotal,
                'accepted'    => $acceptedTotal,
                'rejected'    => $rejectedTotal,
            ],
            'percentages' => [
                'applied'     => $totalOutcomes > 0 ? round(($appliedTotal / $totalOutcomes) * 100) : 0,
                'shortlisted' => $totalOutcomes > 0 ? round(($shortlistedTotal / $totalOutcomes) * 100) : 0,
                'accepted'    => $totalOutcomes > 0 ? round(($acceptedTotal / $totalOutcomes) * 100) : 0,
                'rejected'    => $totalOutcomes > 0 ? round(($rejectedTotal / $totalOutcomes) * 100) : 0,
            ],
        ];

        // Recruitment Pipeline Breakdown
        $pipeline = [
            'pending' => [
                'label'      => 'Pending Review',
                'count'      => $pendingCount,
                'percentage' => $totalApplicants > 0 ? round(($pendingCount / $totalApplicants) * 100) : 0,
                'color'      => 'bg-amber-500',
                'textColor'  => 'text-amber-700',
                'bgLight'    => 'bg-amber-50',
                'border'     => 'border-amber-200',
            ],
            'shortlisted' => [
                'label'      => 'Shortlisted',
                'count'      => $shortlistedCount + $underReviewCount,
                'percentage' => $totalApplicants > 0 ? round((($shortlistedCount + $underReviewCount) / $totalApplicants) * 100) : 0,
                'color'      => 'bg-teal-500',
                'textColor'  => 'text-teal-700',
                'bgLight'    => 'bg-teal-50',
                'border'     => 'border-teal-200',
            ],
            'interviewed' => [
                'label'      => 'Interviewing',
                'count'      => $interviewedCount,
                'percentage' => $totalApplicants > 0 ? round(($interviewedCount / $totalApplicants) * 100) : 0,
                'color'      => 'bg-blue-500',
                'textColor'  => 'text-blue-700',
                'bgLight'    => 'bg-blue-50',
                'border'     => 'border-blue-200',
            ],
            'accepted' => [
                'label'      => 'Accepted / Placed',
                'count'      => $acceptedCount,
                'percentage' => $totalApplicants > 0 ? round(($acceptedCount / $totalApplicants) * 100) : 0,
                'color'      => 'bg-[#059669]',
                'textColor'  => 'text-emerald-700',
                'bgLight'    => 'bg-emerald-50',
                'border'     => 'border-emerald-200',
            ],
            'rejected' => [
                'label'      => 'Not Selected',
                'count'      => $rejectedCount,
                'percentage' => $totalApplicants > 0 ? round(($rejectedCount / $totalApplicants) * 100) : 0,
                'color'      => 'bg-rose-500',
                'textColor'  => 'text-rose-700',
                'bgLight'    => 'bg-rose-50',
                'border'     => 'border-rose-200',
            ],
        ];

        // Recent applications to review (fetch latest 10 with relations)
        $recentApplicants = Application::with(['studentProfile.user', 'internshipPost'])
            ->whereIn('internship_post_id', $postIds)
            ->latest('applied_at')
            ->take(10)
            ->get();

        return view('company.dashboard', compact('company', 'stats', 'topPostsChart', 'donutData', 'pipeline', 'recentApplicants'));
    }
}


