<x-layout>
    <div class="space-y-4 sm:space-y-5">

        <!-- Header Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shrink-0">
            <div>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900">Welcome, {{ Auth::user()->name }}!</h2>
                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">Manage your job &amp; internship search, track applications, and respond to interview invitations.</p>
            </div>
            <a href="{{ route('student.posts.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors shadow-xs self-start sm:self-auto shrink-0">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                <span>Find Jobs &amp; Internships</span>
            </a>
        </div>

        <!-- Career Readiness Strip (Shown when profile/resume is incomplete) -->
        @if(($readiness['percent'] ?? 100) < 100)
            <div class="p-3.5 sm:px-5 rounded-2xl bg-gradient-to-r from-amber-50 via-amber-50/70 to-white border border-amber-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 font-black text-xs">
                        {{ $readiness['percent'] }}%
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="text-xs font-bold text-amber-950">Career Readiness</h4>
                            <div class="w-24 h-1.5 rounded-full bg-amber-200/70 overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ $readiness['percent'] }}%"></div>
                            </div>
                        </div>
                        <p class="text-[11px] text-amber-800/90 mt-0.5 truncate">
                            Missing: <span class="font-semibold">{{ implode(', ', $readiness['missing']) }}</span> — complete these to boost your Smart Match &amp; recruiter replies.
                        </p>
                    </div>
                </div>
                <a href="{{ route('student.profile') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition-colors shrink-0 self-start sm:self-auto">
                    <span>Complete Profile</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        @endif

        <!-- Upcoming Interviews Banner (Shown when student has upcoming/pending interviews) -->
        @if(isset($upcomingInterviews) && $upcomingInterviews->isNotEmpty())
            <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-50/90 via-white to-emerald-50/50 border border-indigo-200/80 shadow-xs shrink-0 space-y-2.5">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-indigo-900 flex items-center gap-2">
                        <i class="fa-solid fa-calendar-check text-indigo-600"></i>
                        <span>Upcoming Interview Invitations ({{ $upcomingInterviews->count() }})</span>
                    </h4>
                    <a href="{{ route('student.applications.index') }}" class="text-[11px] font-bold text-indigo-700 hover:underline">
                        Manage in Applications &rarr;
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                    @foreach($upcomingInterviews as $interview)
                        @php
                            $ivPost = $interview->application->internshipPost;
                            $ivCompany = $ivPost?->companyProfile;
                            $isOnline = $interview->meeting_type === 'online';
                        @endphp
                        <div class="p-3 rounded-xl bg-white border border-indigo-100 flex flex-col justify-between gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-slate-900 truncate">{{ $ivPost?->title ?? 'Interview' }}</span>
                                    @if($interview->status === 'confirmed' || $interview->confirmed_by_student)
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold shrink-0">Confirmed</span>
                                    @elseif($interview->status === 'reschedule_requested')
                                        <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold shrink-0">Reschedule Sent</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold shrink-0">Action Needed</span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ $ivCompany?->company_name }}</p>
                                <div class="flex items-center gap-2 text-[11px] text-slate-600 mt-1.5 font-medium">
                                    <span class="flex items-center gap-1">
                                        <i class="fa-regular fa-clock text-indigo-500"></i>
                                        {{ \Carbon\Carbon::parse($interview->scheduled_at)->format('M d, h:i A') }}
                                    </span>
                                    <span class="text-slate-300">•</span>
                                    <span class="truncate">{{ $isOnline ? 'Online Video' : 'In-Person' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 pt-1 border-t border-slate-100">
                                @if($interview->status === 'scheduled' && !$interview->confirmed_by_student)
                                    <form action="{{ route('student.interviews.confirm', $interview) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full py-1.5 px-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold transition-colors cursor-pointer">
                                            Confirm Attendance
                                        </button>
                                    </form>
                                @elseif($isOnline && $interview->location_or_link)
                                    <a href="{{ $interview->location_or_link }}" target="_blank" class="flex-1 text-center py-1.5 px-2.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-bold transition-colors">
                                        <i class="fa-solid fa-video mr-1"></i> Join Call
                                    </a>
                                @else
                                    <a href="{{ route('student.applications.index') }}" class="flex-1 text-center py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition-colors">
                                        View Details
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Metrics Overview Grid (4 Clickable KPI Cards) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 shrink-0">
            <!-- 1. Total Applications -->
            <a href="{{ route('student.applications.index') }}" class="block p-4 sm:p-5 rounded-2xl bg-white border border-[#E5E7EB] shadow-xs hover:border-[#059669]/50 hover:shadow-sm transition-all group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500">Total Applications</span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#D1FAE5] text-[#059669] border border-[#A7F3D0]/60 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-paper-plane text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-xl sm:text-2xl font-black text-[#111827]">{{ $stats['total_applications'] }}</h3>
                    <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">Submitted positions</p>
                </div>
            </a>

            <!-- 2. Under Review / Pending -->
            <a href="{{ route('student.applications.index') }}" class="block p-4 sm:p-5 rounded-2xl bg-white border border-[#E5E7EB] shadow-xs hover:border-amber-400/70 hover:shadow-sm transition-all group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500">Under Review</span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-hourglass-half text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-xl sm:text-2xl font-black text-amber-600">{{ $stats['pending'] }}</h3>
                    <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">Awaiting review</p>
                </div>
            </a>

            <!-- 3. Shortlisted & Interviews -->
            <a href="{{ route('student.applications.index') }}" class="block p-4 sm:p-5 rounded-2xl bg-white border border-[#E5E7EB] shadow-xs hover:border-indigo-400/60 hover:shadow-sm transition-all group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500 truncate mr-1">Interviews &amp; Shortlisted</span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-calendar-check text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-xl sm:text-2xl font-black text-[#111827]">{{ $stats['shortlisted'] }}</h3>
                    <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">Active interviews</p>
                </div>
            </a>

            <!-- 4. Offers Accepted -->
            <a href="{{ route('student.applications.index') }}" class="block p-4 sm:p-5 rounded-2xl bg-white border border-[#E5E7EB] shadow-xs hover:border-[#059669]/50 hover:shadow-sm transition-all group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500">Offers Accepted</span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-trophy text-xs sm:text-sm text-emerald-600"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-xl sm:text-2xl font-black text-emerald-700">{{ $stats['accepted'] }}</h3>
                    <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">Confirmed placements</p>
                </div>
            </a>
        </div>

        <!-- 2-Column Section: Application Tracker & Recommended Jobs/Internships -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch">

            <!-- Left: Applications Tracking Section (7 cols) -->
            <div class="lg:col-span-7 p-5 sm:p-6 rounded-3xl bg-white border border-[#E5E7EB] shadow-xs flex flex-col h-full">
                <div class="flex items-center justify-between pb-3 border-b border-[#E5E7EB]">
                    <h4 class="text-sm font-bold text-[#111827] uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane w-4 h-4 text-[#059669]"></i>
                        Application Status Tracker
                    </h4>
                    <a href="{{ route('student.applications.index') }}" class="text-xs font-semibold text-[#059669] hover:underline flex items-center gap-1">
                        <span>View All</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                @if ($recentApplications->isNotEmpty())
                    <div class="divide-y divide-gray-100 {{ $recentApplications->count() >= 4 ? 'flex-1 flex flex-col justify-between' : '' }}">
                        @foreach ($recentApplications as $app)
                            @php
                                $appComp = $app->internshipPost->companyProfile;
                                $stageSteps = ['pending', 'under_review', 'shortlisted', 'interviewed', 'accepted'];
                                $currentStepIndex = array_search($app->status, $stageSteps, true);
                                $isTerminalNegative = in_array($app->status, ['rejected', 'withdrawn'], true);
                            @endphp
                            <div class="py-3.5 sm:py-4 space-y-2 group">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        @if($appComp?->has_logo && $appComp?->logo_url)
                                            <img src="{{ $appComp->logo_url }}" alt="{{ $appComp->company_name }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200 shrink-0">
                                        @else
                                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-[#059669] border border-emerald-200/70 flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ $appComp?->initials ?? 'CO' }}
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('student.posts.show', $app->internship_post_id) }}" class="text-xs font-bold text-[#111827] group-hover:text-[#059669] transition-colors line-clamp-1">
                                                {{ $app->internshipPost->title }}
                                            </a>
                                            <p class="text-xs text-gray-600 mt-0.5 truncate">{{ $appComp?->company_name }}</p>
                                            <div class="flex items-center gap-2 text-[11px] text-gray-400 mt-0.5">
                                                <span><i class="fa-regular fa-clock mr-1 text-[10px]"></i>Applied: {{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</span>
                                                @if($app->internshipPost->location)
                                                    <span>•</span>
                                                    <span class="truncate"><i class="fa-solid fa-location-dot mr-1 text-[10px]"></i>{{ $app->internshipPost->location }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-status-badge :status="$app->status" />
                                        <a href="{{ route('student.messages.start', $app) }}" title="Message Company" class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:text-[#059669] hover:border-[#059669]/40 hover:bg-emerald-50/50 flex items-center justify-center transition-colors">
                                            <i class="fa-regular fa-comment-dots text-xs"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- Mini 5-Step Progress Bar -->
                                @if($isTerminalNegative)
                                    <div class="pl-12 flex items-center gap-2">
                                        <div class="flex-1 h-1 rounded-full {{ $app->status === 'rejected' ? 'bg-rose-200' : 'bg-slate-200' }}"></div>
                                        <span class="text-[10px] font-semibold {{ $app->status === 'rejected' ? 'text-rose-500' : 'text-slate-400' }}">
                                            {{ $app->status === 'rejected' ? 'Not Selected' : 'Withdrawn' }}
                                        </span>
                                    </div>
                                @else
                                    <div class="pl-12 flex items-center gap-1.5" title="Stage: {{ ucfirst(str_replace('_', ' ', $app->status)) }}">
                                        @foreach(['Applied', 'Review', 'Shortlist', 'Interview', 'Offer'] as $idx => $stepLabel)
                                            @php
                                                $reached = $currentStepIndex !== false && $idx <= $currentStepIndex;
                                            @endphp
                                            <div class="flex-1 flex flex-col gap-0.5">
                                                <div class="h-1 rounded-full transition-colors {{ $reached ? 'bg-[#059669]' : 'bg-slate-200' }}"></div>
                                            </div>
                                        @endforeach
                                        <span class="text-[10px] font-semibold text-slate-400 shrink-0 ml-1">
                                            Step {{ $currentStepIndex !== false ? ($currentStepIndex + 1) : 1 }}/5
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-lightbulb text-amber-500"></i>
                            <span>Click the chat icon to message recruiters directly.</span>
                        </span>
                        <a href="{{ route('student.applications.index') }}" class="font-semibold text-[#059669] hover:underline shrink-0">
                            Full Timeline &rarr;
                        </a>
                    </div>
                @else
                    <div class="py-8 text-center text-gray-400 text-xs my-auto">
                        <i class="fa-solid fa-file-circle-question text-3xl mx-auto mb-2 text-gray-300"></i>
                        <p class="font-medium text-gray-500">You haven't applied for any positions yet.</p>
                        <a href="{{ route('student.posts.index') }}" class="mt-3 inline-block px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 font-bold hover:bg-emerald-100 transition-colors text-xs">
                            Browse Open Roles
                        </a>
                    </div>
                @endif
            </div>

            <!-- Right: Recommended Opportunities Section (5 cols - Feature 1.7 Smart Match) -->
            <div class="lg:col-span-5 p-5 sm:p-6 rounded-3xl bg-white border border-[#E5E7EB] shadow-xs flex flex-col h-full space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-[#E5E7EB]">
                    <div class="flex items-center gap-2 min-w-0">
                        <h4 class="text-sm font-bold text-[#111827] uppercase tracking-wider flex items-center gap-2 truncate">
                            <i class="fa-solid fa-wand-magic-sparkles w-4 h-4 text-[#059669]"></i>
                            Recommended For You
                        </h4>
                        @if (!empty($student?->major) || !empty($student?->skills))
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200/80 shrink-0">
                                <i class="fa-solid fa-sparkles text-[8px]"></i>
                                <span>Smart Match</span>
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('student.posts.index') }}" class="text-xs font-semibold text-[#059669] hover:underline flex items-center gap-1 shrink-0">
                        <span>Explore All</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                {{-- Hint for students who haven't completed their profile skills --}}
                @if (empty($student?->major) && empty($student?->skills))
                    <div class="p-2.5 rounded-2xl bg-amber-50 border border-amber-200/80 text-[11px] text-amber-800 flex items-center justify-between gap-2 shrink-0">
                        <span class="flex items-center gap-1.5 min-w-0">
                            <i class="fa-solid fa-lightbulb text-amber-600 shrink-0 text-xs"></i>
                            <span class="truncate">Add your major &amp; skills to unlock tailored matches.</span>
                        </span>
                        <a href="{{ route('student.profile') }}" class="font-bold text-amber-900 underline shrink-0 hover:text-amber-950">Update &rarr;</a>
                    </div>
                @endif

                @if (isset($recommendedPosts) && $recommendedPosts->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($recommendedPosts as $post)
                            @php
                                $comp = $post->companyProfile;
                                $compLogo = $comp?->logo_url;
                                $compHasLogo = $comp?->has_logo;
                                $compInitials = $comp?->initials ?? 'CO';
                                $daysLeft = $post->deadline
                                    ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($post->deadline)->startOfDay(), false)
                                    : null;
                            @endphp
                            <div class="p-3 sm:p-3.5 rounded-2xl border border-gray-100 hover:border-[#059669]/40 hover:bg-emerald-50/20 transition-all flex flex-col gap-2.5 group">
                                <div class="flex items-center gap-3">
                                    @if($compHasLogo && $compLogo)
                                        <a href="{{ route('student.companies.show', $comp) }}" title="View {{ $comp?->company_name }} profile" class="shrink-0">
                                            <img src="{{ $compLogo }}" alt="{{ $comp?->company_name }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200 hover:opacity-90 transition-opacity">
                                        </a>
                                    @else
                                        <a href="{{ route('student.companies.show', $comp) }}" title="View {{ $comp?->company_name }} profile"
                                           class="w-9 h-9 rounded-xl bg-teal-700 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs hover:bg-teal-800 transition-colors">
                                            {{ $compInitials }}
                                        </a>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('student.posts.show', $post) }}" class="text-xs font-bold text-[#111827] group-hover:text-[#059669] transition-colors truncate block">
                                            {{ $post->title }}
                                        </a>
                                        <p class="text-[11px] text-gray-500 truncate">
                                            <a href="{{ route('student.companies.show', $comp) }}" class="hover:text-emerald-600 hover:underline transition-colors">
                                                {{ $comp?->company_name }}
                                            </a>
                                            <span class="text-slate-300">•</span>
                                            <span>{{ $post->location }}</span>
                                        </p>
                                    </div>
                                </div>

                                {{-- Opportunity Type, Work Mode & Smart Match Badges --}}
                                @php
                                    $oppLabel = match($post->opportunity_type ?? 'internship') {
                                        'full_time' => 'Full-Time',
                                        'part_time' => 'Part-Time',
                                        'contract'  => 'Contract',
                                        default     => 'Internship',
                                    };
                                    $workModeLabel = match($post->type ?? 'on_site') {
                                        'remote' => 'Remote',
                                        'hybrid' => 'Hybrid',
                                        default  => 'On-Site',
                                    };
                                @endphp
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-bold">
                                        <i class="fa-solid fa-briefcase text-[9px] text-slate-500"></i>
                                        <span>{{ $oppLabel }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-sky-50 text-sky-700 text-[10px] font-semibold border border-sky-200/70">
                                        <span>{{ $workModeLabel }}</span>
                                    </span>
                                    @if (!empty($post->has_smart_match))
                                        @if (!empty($post->is_major_match))
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-[10px] font-bold border border-indigo-200/80">
                                                <i class="fa-solid fa-graduation-cap text-[9px]"></i>
                                                <span class="truncate max-w-[120px]">{{ $student->major }}</span>
                                            </span>
                                        @endif
                                        @if (!empty($post->matched_skills))
                                            @foreach ($post->matched_skills as $skill)
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-semibold border border-emerald-200/80">
                                                    <i class="fa-solid fa-check text-[8px] text-emerald-600"></i>
                                                    <span>{{ $skill }}</span>
                                                </span>
                                            @endforeach
                                        @endif
                                    @endif
                                </div>

                                <div class="flex items-center justify-between gap-2 pt-2 border-t border-gray-100/80 text-[11px]">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="font-bold text-[#059669] shrink-0">
                                            @if ($post->stipend)
                                                ${{ number_format($post->stipend, 0) }}/mo
                                            @else
                                                Standard Stipend
                                            @endif
                                        </span>
                                        @if ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 5)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold shrink-0 {{ $daysLeft <= 1 ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                                <i class="fa-regular fa-clock text-[9px]"></i>
                                                {{ $daysLeft === 0 ? 'Closes Today' : ($daysLeft === 1 ? '1 day left' : "{$daysLeft} days left") }}
                                            </span>
                                        @endif
                                    </div>
                                    <a href="{{ route('student.posts.show', $post) }}" class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-[#059669] font-bold text-[11px] transition-colors">
                                        View Details &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-lightbulb text-amber-500"></i>
                            <span>Click the chat icon to message recruiters directly.</span>
                        </span>
                        <a href="{{ route('student.applications.index') }}" class="font-semibold text-[#059669] hover:underline shrink-0">
                            Full Timeline &rarr;
                        </a>
                    </div>
                @else
                    <div class="py-8 text-center text-gray-400 text-xs my-auto">
                        <i class="fa-solid fa-briefcase text-3xl mx-auto mb-2 text-gray-300"></i>
                        <p class="font-medium text-gray-500">No recommended openings at the moment.</p>
                        <a href="{{ route('student.posts.index') }}" class="mt-3 inline-block px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 font-bold hover:bg-emerald-100 transition-colors text-xs">
                            View All Opportunities
                        </a>
                    </div>
                @endif
            </div>

        </div>

    </div>
</x-layout>
