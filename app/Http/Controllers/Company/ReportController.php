<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display the Company Hiring Reports page with Job, Status, Date Range, and Search filters.
     */
    public function index(Request $request): View
    {
        $company = Auth::user()->companyProfile;
        $postIds = InternshipPost::where('company_profile_id', $company?->id)->pluck('id');

        $postId   = $request->input('post_id', 'all');
        $status   = $request->input('status', 'all');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $search   = trim((string) $request->input('search', ''));

        $baseQuery = Application::whereIn('internship_post_id', $postIds);

        $totalApplications = (clone $baseQuery)->count();
        $totalShortlisted  = (clone $baseQuery)->whereIn('status', ['shortlisted', 'interviewed'])->count();
        $totalAccepted     = (clone $baseQuery)->where('status', 'accepted')->count();
        $hireRate          = $totalApplications > 0 ? round(($totalAccepted / $totalApplications) * 100, 1) : 0;

        $query = $this->buildReportQuery($postIds, $postId, $status, $dateFrom, $dateTo, $search);
        $applications = $query->latest('applied_at')->get();

        $stats = [
            'total_applications' => $totalApplications,
            'total_shortlisted'  => $totalShortlisted,
            'total_accepted'     => $totalAccepted,
            'hire_rate'          => $hireRate,
            'filtered_count'     => $applications->count(),
        ];

        $companyPosts = InternshipPost::where('company_profile_id', $company?->id)
            ->orderByDesc('created_at')
            ->get(['id', 'title']);

        return view('company.reports.index', compact(
            'company',
            'applications',
            'stats',
            'companyPosts',
            'postId',
            'status',
            'dateFrom',
            'dateTo',
            'search'
        ));
    }

    /**
     * Export filtered Company Hiring Report as CSV (UTF-8 BOM for Excel / Khmer support).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $company = Auth::user()->companyProfile;
        $postIds = InternshipPost::where('company_profile_id', $company?->id)->pluck('id');

        $postId   = $request->input('post_id', 'all');
        $status   = $request->input('status', 'all');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $search   = trim((string) $request->input('search', ''));

        $applications = $this->buildReportQuery($postIds, $postId, $status, $dateFrom, $dateTo, $search)
            ->latest('applied_at')
            ->get();

        $filename = 'company_hiring_report_' . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($applications) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No',
                'Candidate Name',
                'Email',
                'Phone',
                'Major',
                'GPA',
                'Job / Internship Title',
                'Opportunity Type',
                'Status',
                'Applied Date',
                'Interview Date',
                'Interview Status',
            ]);

            foreach ($applications as $index => $app) {
                $student = $app->studentProfile;
                $user = $student?->user;
                $post = $app->internshipPost;
                $interview = $app->interview;

                fputcsv($handle, [
                    $index + 1,
                    $user?->name ?? 'N/A',
                    $user?->email ?? 'N/A',
                    $student?->phone ?? 'N/A',
                    $student?->major ?? 'N/A',
                    $student?->gpa !== null ? number_format((float) $student->gpa, 2) : 'N/A',
                    $post?->title ?? 'N/A',
                    $post?->opportunity_type_label ?? 'Internship',
                    ucfirst(str_replace('_', ' ', $app->status)),
                    $app->applied_at ? \Carbon\Carbon::parse($app->applied_at)->format('Y-m-d') : 'N/A',
                    $interview?->scheduled_at ? \Carbon\Carbon::parse($interview->scheduled_at)->format('Y-m-d H:i') : '-',
                    $interview ? ucfirst(str_replace('_', ' ', $interview->status)) : '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Render printable formal Hiring Report.
     */
    public function print(Request $request): View
    {
        $company = Auth::user()->companyProfile;
        $postIds = InternshipPost::where('company_profile_id', $company?->id)->pluck('id');

        $postId   = $request->input('post_id', 'all');
        $status   = $request->input('status', 'all');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $search   = trim((string) $request->input('search', ''));

        $applications = $this->buildReportQuery($postIds, $postId, $status, $dateFrom, $dateTo, $search)
            ->latest('applied_at')
            ->get();

        $totalFiltered = $applications->count();
        $acceptedFiltered = $applications->where('status', 'accepted')->count();
        $shortlistedFiltered = $applications->whereIn('status', ['shortlisted', 'interviewed'])->count();
        $hireRate = $totalFiltered > 0 ? round(($acceptedFiltered / $totalFiltered) * 100, 1) : 0;

        $selectedPostTitle = $postId !== 'all'
            ? (InternshipPost::where('company_profile_id', $company?->id)->find($postId)?->title ?? 'Selected Listing')
            : 'All Job & Internship Listings';

        return view('company.reports.print', compact(
            'company',
            'applications',
            'totalFiltered',
            'acceptedFiltered',
            'shortlistedFiltered',
            'hireRate',
            'selectedPostTitle',
            'status',
            'dateFrom',
            'dateTo',
            'search'
        ));
    }

    /**
     * Build filtered query scoped to the authenticated company's postings.
     */
    private function buildReportQuery($postIds, string $postId, string $status, string $dateFrom, string $dateTo, string $search)
    {
        $query = Application::with(['studentProfile.user', 'internshipPost', 'interview'])
            ->whereIn('internship_post_id', $postIds);

        if ($postId !== 'all' && $postId !== '') {
            $query->where('internship_post_id', $postId);
        }

        if ($status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        if ($dateFrom !== '') {
            $query->whereDate('applied_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('applied_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('studentProfile', function ($sq) use ($search) {
                    $sq->where('major', 'like', "%{$search}%")
                       ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                })->orWhereHas('internshipPost', function ($pq) use ($search) {
                    $pq->where('title', 'like', "%{$search}%");
                });
            });
        }

        return $query;
    }
}
