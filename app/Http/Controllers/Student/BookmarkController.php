<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookmarkController extends Controller
{
    /**
     * Display the student's saved / bookmarked internship posts.
     */
    public function index(Request $request): View
    {
        $student = $request->user()->studentProfile;

        $bookmarkedPosts = $student
            ->bookmarkedPosts()
            ->with('companyProfile')
            ->latest('student_bookmarks.created_at')
            ->paginate(12);

        // Attach a boolean flag so Blade knows each post is bookmarked
        $bookmarkedPosts->getCollection()->transform(function ($post) {
            $post->is_bookmarked = true;
            return $post;
        });

        return view('student.bookmarks.index', compact('bookmarkedPosts'));
    }

    /**
     * Toggle bookmark for a post (AJAX + fallback redirect).
     * POST /student/posts/{post}/bookmark
     * Returns JSON for fetch() requests, redirect for plain form submits.
     */
    public function toggle(Request $request, InternshipPost $post): JsonResponse|RedirectResponse
    {
        $student  = $request->user()->studentProfile;
        $existing = $student->bookmarks()->where('internship_post_id', $post->id)->first();

        if ($existing) {
            $existing->delete();
            $bookmarked    = false;
            $message       = 'Bookmark removed.';
            $bookmarkCount = $student->bookmarks()->count();
        } else {
            $student->bookmarks()->create(['internship_post_id' => $post->id]);
            $bookmarked    = true;
            $message       = 'Post saved to bookmarks!';
            $bookmarkCount = $student->bookmarks()->count();
        }

        // AJAX request → return JSON
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'bookmarked'     => $bookmarked,
                'message'        => $message,
                'bookmark_count' => $bookmarkCount,
            ]);
        }

        // Plain form fallback → redirect back
        return back()->with($bookmarked ? 'success' : 'info', $message);
    }
}

