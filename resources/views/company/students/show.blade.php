<x-layout>
    @php
        $studentUser = $student->user;
        $nameParts = array_filter(explode(' ', trim($studentUser?->name ?? 'Student')));
        $initials = count($nameParts) >= 2
            ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
            : strtoupper(substr($studentUser?->name ?? 'ST', 0, 2));
        $primaryApp = $applications->first();
        $hasResume = $primaryApp && $primaryApp->getEffectiveResumePath();
    @endphp

    <div class="space-y-6">

        <!-- Top Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('company.applicants.index') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:border-[#059669]/40 text-slate-700 hover:text-[#059669] text-xs font-semibold shadow-xs transition-colors">
                    <i class="fa-solid fa-arrow-left text-[11px]"></i>
                    <span>Back to Applicants</span>
                </a>
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-900">Candidate Profile</h1>
                    <p class="text-xs text-slate-500">Academic credentials, technical skills, and application history with your organization.</p>
                </div>
            </div>

            @if ($primaryApp)
                <div class="flex items-center gap-2 shrink-0">
                    @if ($hasResume)
                        <a href="{{ route('company.applicants.resume', $primaryApp) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:border-[#059669]/40 text-slate-700 hover:text-[#059669] text-xs font-semibold shadow-xs transition-colors">
                            <i class="fa-solid fa-file-pdf text-rose-500"></i>
                            <span>View Resume</span>
                        </a>
                    @endif
                    <a href="{{ route('company.messages.start', $primaryApp) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#059669] hover:bg-[#047857] text-white text-xs font-semibold shadow-xs transition-colors">
                        <i class="fa-regular fa-comment-dots"></i>
                        <span>Message Candidate</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Main Profile Card -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Banner -->
            <div class="h-28 bg-gradient-to-r from-[#059669] via-emerald-600 to-teal-600 relative px-6 pt-4 flex items-start justify-between">
                <span class="px-3 py-1 rounded-full bg-white/20 backdrop-blur-xs text-white text-[11px] font-semibold">
                    <i class="fa-solid fa-graduation-cap mr-1"></i> Student Applicant
                </span>
            </div>

            <div class="px-6 pb-6 relative z-10">
                <!-- Header Row -->
                <div class="-mt-12 flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
                    <div class="flex items-end gap-4">
                        @if ($studentUser?->avatar_path)
                            <img src="{{ asset('storage/' . ltrim($studentUser->avatar_path, '/')) }}"
                                 alt="{{ $studentUser->name }}"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                 class="w-24 h-24 rounded-2xl object-cover border-4 border-white shadow-md bg-white">
                            <div style="display: none;" class="w-24 h-24 rounded-2xl bg-[#059669] text-white text-2xl font-black items-center justify-center border-4 border-white shadow-md">
                                {{ $initials }}
                            </div>
                        @else
                            <div class="w-24 h-24 rounded-2xl bg-[#059669] text-white text-2xl font-black flex items-center justify-center border-4 border-white shadow-md">
                                {{ $initials }}
                            </div>
                        @endif

                        <div class="pb-1">
                            <h2 class="text-xl font-bold text-slate-900">{{ $studentUser?->name ?? 'Student' }}</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $student->major ?: 'Major not specified' }}
                                @if ($student->department)
                                    · {{ $student->department }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-50 text-[#059669] border border-emerald-200/80 text-xs font-bold shrink-0 self-start sm:self-auto">
                        <i class="fa-solid fa-award"></i>
                        <span>GPA: {{ $student->gpa !== null ? number_format($student->gpa, 2) : 'N/A' }} / 4.00</span>
                    </div>
                </div>

                <!-- Contact & Academic Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs mb-6">
                    <div class="flex items-center gap-2.5 text-slate-600 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-white border border-slate-200/80 flex items-center justify-center text-slate-400 shrink-0">
                            <i class="fa-solid fa-envelope text-[11px]"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-[10px] font-bold uppercase text-slate-400">Email</span>
                            <span class="font-medium text-slate-800 truncate block">{{ $studentUser?->email ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 text-slate-600 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-white border border-slate-200/80 flex items-center justify-center text-slate-400 shrink-0">
                            <i class="fa-solid fa-phone text-[11px]"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-[10px] font-bold uppercase text-slate-400">Phone</span>
                            <span class="font-medium text-slate-800 truncate block">{{ $student->phone ?: 'Not provided' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 text-slate-600 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-white border border-slate-200/80 flex items-center justify-center text-slate-400 shrink-0">
                            <i class="fa-solid fa-id-card text-[11px]"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-[10px] font-bold uppercase text-slate-400">Student ID</span>
                            <span class="font-medium text-slate-800 truncate block">{{ $student->student_id_number ?: 'Not specified' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 text-slate-600 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-white border border-slate-200/80 flex items-center justify-center text-slate-400 shrink-0">
                            <i class="fa-solid fa-calendar-days text-[11px]"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-[10px] font-bold uppercase text-slate-400">Cohort Year</span>
                            <span class="font-medium text-slate-800 truncate block">{{ $student->cohort_year ?: 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Technical Skills -->
                <div class="mb-6">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5">Technical Skills &amp; Competencies</h3>
                    @if (is_array($student->skills) && count($student->skills) > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($student->skills as $skill)
                                <span class="px-3 py-1 rounded-lg bg-[#D1FAE5] text-[#065F46] text-xs font-semibold border border-[#A7F3D0]">
                                    {{ $skill }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">No technical skills listed.</p>
                    @endif
                </div>

                <!-- Bio -->
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">About Candidate</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $student->bio ?: 'No professional summary provided by the candidate.' }}</p>
                </div>
            </div>
        </div>

        <!-- Applications to Your Company -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-briefcase text-[#059669]"></i>
                    <span>Applications to Your Company ({{ $applications->count() }})</span>
                </h3>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach ($applications as $app)
                    <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900">{{ $app->internshipPost->title }}</h4>
                            <div class="flex items-center gap-2.5 text-[11px] text-slate-500 mt-1 flex-wrap">
                                <span><i class="fa-regular fa-clock mr-1"></i>Applied {{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</span>
                                @if ($app->interview)
                                    <span>•</span>
                                    <span class="text-indigo-600 font-semibold">
                                        <i class="fa-solid fa-calendar-check mr-1"></i>
                                        Interview: {{ \Carbon\Carbon::parse($app->interview->scheduled_at)->format('M d, h:i A') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <x-status-badge :status="$app->status" />
                            @if ($app->getEffectiveResumePath())
                                <a href="{{ route('company.applicants.resume', $app) }}" target="_blank"
                                   class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition-colors">
                                    CV
                                </a>
                            @endif
                            <a href="{{ route('company.messages.start', $app) }}"
                               class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-[#059669] text-[11px] font-semibold transition-colors">
                                Chat
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-layout>
