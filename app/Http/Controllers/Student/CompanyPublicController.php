<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompanyPublicController extends Controller
{
    /**
     * Display public company profile and active internship openings (Feature 1.6).
     */
    public function show(CompanyProfile $company): View
    {
        $company->load(['user']);

        $student = Auth::user()?->studentProfile;

        // Active approved internships posted by this company that haven't expired
        $activePosts = $company->internshipPosts()
            ->with('companyProfile')
            ->approved()
            ->whereDate('deadline', '>=', now()->toDateString())
            ->latest()
            ->get();

        // Bookmarks for student
        $bookmarkedPostIds = $student
            ? $student->bookmarks()->pluck('internship_post_id')->toArray()
            : [];

        // Applications submitted by student
        $appliedPostIds = $student
            ? $student->applications()->pluck('internship_post_id')->toArray()
            : [];

        $totalPositions = $company->internshipPosts()->approved()->count();
        $isVerified = $company->isVerified();

        return view('student.companies.show', compact(
            'company',
            'activePosts',
            'bookmarkedPostIds',
            'appliedPostIds',
            'totalPositions',
            'isVerified'
        ));
    }
}

