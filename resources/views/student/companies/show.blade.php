<x-layout>
    <div class="space-y-6">

        {{-- ─── Breadcrumb & Navigation ─── --}}
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <a href="{{ url()->previous() ?: route('student.posts.index') }}"
               class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-emerald-600 transition-colors">
                <i class="fa-solid fa-arrow-left w-4 h-4"></i>
                <span>Back</span>
            </a>

            <div class="flex items-center gap-2 text-xs text-slate-500 font-semibold">
                <a href="{{ route('student.posts.index') }}" class="hover:text-emerald-600 transition-colors">Internships</a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-400">Companies</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-900 font-bold truncate max-w-[200px]">{{ $company->company_name }}</span>
            </div>
        </div>

        {{-- ─── Company Hero Header Card ─── --}}
        @php
            $compLogo    = $company->logo_url;
            $compHasLogo = $company->has_logo;
            $compInitials= $company->initials;
        @endphp

        <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
                
                {{-- Logo + Name + Industry --}}
                <div class="flex items-start sm:items-center gap-4 sm:gap-5 min-w-0">
                    @if ($compHasLogo && $compLogo)
                        <img src="{{ $compLogo }}" alt="{{ $company->company_name }}"
                             class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl object-cover shrink-0 border border-slate-200 shadow-xs">
                    @else
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl bg-teal-700 text-white flex items-center justify-center font-black text-2xl shrink-0 shadow-xs">
                            {{ $compInitials }}
                        </div>
                    @endif

                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight">
                                {{ $company->company_name }}
                            </h1>

                            @if ($company->isVerified())
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 ring-1 ring-emerald-500/10 shadow-2xs">
                                    <i class="fa-solid fa-circle-check text-emerald-600 text-[11px]"></i>
                                    <span>Verified Partner</span>
                                </span>
                            @endif
                        </div>

                        <p class="text-xs sm:text-sm text-slate-500 font-semibold mt-1 flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1 text-slate-700">
                                <i class="fa-solid fa-briefcase text-slate-400 text-xs"></i>
                                <span>{{ $company->industry }}</span>
                            </span>
                            <span class="text-slate-300">•</span>
                            <span class="inline-flex items-center gap-1">
                                <i class="fa-solid fa-location-dot text-slate-400 text-xs"></i>
                                <span>{{ $company->location }}</span>
                            </span>
                        </p>
                    </div>
                </div>

                {{-- Action Links --}}
                <div class="flex items-center gap-2.5 shrink-0 w-full sm:w-auto">
                    @if ($company->website)
                        <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer"
                           class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:text-emerald-700 hover:border-emerald-300 hover:bg-emerald-50/50 text-xs font-bold transition-all shadow-2xs">
                            <i class="fa-solid fa-globe text-slate-400"></i>
                            <span>Official Website</span>
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-slate-400"></i>
                        </a>
                    @endif

                    <a href="#active-internships"
                       class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs">
                        <i class="fa-solid fa-briefcase text-xs"></i>
                        <span>View {{ $activePosts->count() }} Openings</span>
                    </a>
                </div>

            </div>

            {{-- Metric Strip --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-6 border-t border-slate-100">
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="block text-slate-500 text-[10px] uppercase font-bold tracking-wider">Active Openings</span>
                    <span class="text-lg font-black text-emerald-600 mt-0.5 block">{{ $activePosts->count() }} Position(s)</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="block text-slate-500 text-[10px] uppercase font-bold tracking-wider">Primary Sector</span>
                    <span class="text-sm font-bold text-slate-900 mt-0.5 block truncate">{{ $company->industry }}</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="block text-slate-500 text-[10px] uppercase font-bold tracking-wider">Headquarters</span>
                    <span class="text-sm font-bold text-slate-900 mt-0.5 block truncate">{{ $company->location }}</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="block text-slate-500 text-[10px] uppercase font-bold tracking-wider">Partner Status</span>
                    <span class="text-sm font-bold text-slate-900 mt-0.5 block flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full {{ $company->isVerified() ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <span>{{ $company->isVerified() ? 'University Verified' : 'Under Moderation' }}</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- ─── Main Content Grid: About + Organization Details ─── --}}
        <div class="grid lg:grid-cols-3 gap-6">

            {{-- Left: About Organization (2 cols) --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-4">
                    <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-emerald-600"></i>
                        <span>About {{ $company->company_name }}</span>
                    </h2>

                    @if ($company->description)
                        <div class="whitespace-pre-line text-slate-600 text-xs sm:text-sm leading-relaxed font-normal">
                            {{ $company->description }}
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">
                            No company description provided yet.
                        </p>
                    @endif
                </div>

                {{-- ─── Active Internships Section ─── --}}
                <div id="active-internships" class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-briefcase text-emerald-600"></i>
                                <span>Active Internships by this Company</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Explore currently open vacancies and submit your candidacy.
                            </p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ $activePosts->count() }} Available
                        </span>
                    </div>

                    @if ($activePosts->isNotEmpty())
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach ($activePosts as $post)
                                @php
                                    $isApplied = in_array($post->id, $appliedPostIds);
                                    $isBookmarked = in_array($post->id, $bookmarkedPostIds);
                                    $daysRemaining = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($post->deadline)->startOfDay(), false);
                                    $isClosingSoon = $daysRemaining >= 0 && $daysRemaining <= 7;
                                @endphp

                                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-5 hover:border-emerald-300 hover:shadow-md transition-all duration-200 flex flex-col justify-between gap-4 group">
                                    <div class="space-y-3">
                                        <div class="flex items-start justify-between gap-2">
                                            @if ($post->category)
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                                    {{ $post->category }}
                                                </span>
                                            @endif

                                            {{-- Bookmark toggle button --}}
                                            <div x-data="{
                                                     bookmarked: {{ $isBookmarked ? 'true' : 'false' }},
                                                     loading: false,
                                                     async toggle() {
                                                         if (this.loading) return;
                                                         this.loading = true;
                                                         try {
                                                             const res = await fetch('{{ route('student.posts.bookmark', $post) }}', {
                                                                 method: 'POST',
                                                                 headers: {
                                                                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                                     'Accept': 'application/json',
                                                                 },
                                                             });
                                                             const data = await res.json();
                                                             this.bookmarked = data.bookmarked;
                                                         } catch(e) { console.error(e); }
                                                         finally { this.loading = false; }
                                                     }
                                                 }">
                                                <button @click="toggle()"
                                                        :disabled="loading"
                                                        :title="bookmarked ? 'Remove bookmark' : 'Bookmark this role'"
                                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors cursor-pointer"
                                                        :class="bookmarked ? 'text-rose-600 bg-rose-50' : 'text-slate-400 hover:text-rose-500 hover:bg-slate-50'">
                                                    <i class="text-xs" :class="bookmarked ? 'fa-solid fa-heart' : 'fa-regular fa-heart'"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div>
                                            <h3 class="font-extrabold text-sm sm:text-base text-slate-900 group-hover:text-emerald-600 transition-colors line-clamp-2">
                                                <a href="{{ route('student.posts.show', $post) }}">
                                                    {{ $post->title }}
                                                </a>
                                            </h3>
                                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                                <i class="fa-solid fa-location-dot text-slate-300 text-[10px]"></i>
                                                <span>{{ $post->location }}</span>
                                                <span class="text-slate-300">•</span>
                                                <span>{{ $post->duration_weeks }} Weeks</span>
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-2 text-xs flex-wrap">
                                            <span class="font-extrabold text-emerald-600">
                                                {{ $post->stipend ? '$' . number_format($post->stipend, 0) . '/mo' : 'Standard Stipend' }}
                                            </span>
                                            @if ($isClosingSoon)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-200">
                                                    <i class="fa-solid fa-fire text-amber-500 text-[9px]"></i>
                                                    <span>{{ $daysRemaining === 0 ? 'Closes Today' : $daysRemaining . 'd left' }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                                        @if ($isApplied)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                                                <span>Already Applied</span>
                                            </span>
                                        @else
                                            <span class="text-[11px] text-slate-400 font-medium">
                                                {{ $post->slots }} Candidate(s) needed
                                            </span>
                                        @endif

                                        <a href="{{ route('student.posts.show', $post) }}"
                                           class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs inline-flex items-center gap-1 transition-colors">
                                            <span>View Details</span>
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-12 text-center text-slate-400 text-xs space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto text-slate-300">
                                <i class="fa-solid fa-briefcase text-xl"></i>
                            </div>
                            <p class="font-medium text-slate-600">
                                No active internship vacancies are currently published by {{ $company->company_name }}.
                            </p>
                            <a href="{{ route('student.posts.index') }}"
                               class="inline-block px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors shadow-xs">
                                Browse Other Opportunities
                            </a>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Right Column: Fast Facts & Contact Info (1 col) --}}
            <div class="space-y-6">

                {{-- Fast Facts Card --}}
                <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-4 text-xs">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 pb-2 border-b border-slate-100">
                        Organization Profile
                    </h3>

                    <div class="space-y-3">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Company Name</span>
                            <span class="font-bold text-slate-900 text-sm">{{ $company->company_name }}</span>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Industry Sector</span>
                            <span class="font-semibold text-slate-800">{{ $company->industry }}</span>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Primary Location</span>
                            <span class="font-semibold text-slate-800">{{ $company->location }}</span>
                        </div>

                        @if ($company->address)
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Full Address</span>
                                <span class="font-semibold text-slate-800 leading-relaxed">{{ $company->address }}</span>
                            </div>
                        @endif

                        @if ($company->website)
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Web Presence</span>
                                <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer"
                                   class="font-semibold text-emerald-600 hover:underline break-all inline-flex items-center gap-1 mt-0.5">
                                    <span>{{ $company->website }}</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                </a>
                            </div>
                        @endif

                        @if ($company->contact_person)
                            <div class="pt-3 border-t border-slate-100">
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Representative</span>
                                <span class="font-semibold text-slate-800">{{ $company->contact_person }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- University Accreditation Card --}}
                <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-50/70 to-teal-50/40 border border-emerald-200/80 shadow-xs space-y-3 text-xs">
                    <div class="flex items-center gap-2.5 text-emerald-900 font-bold">
                        <i class="fa-solid fa-shield-halved text-emerald-600 text-base"></i>
                        <span>Accredited Internship Host</span>
                    </div>
                    <p class="text-slate-600 text-[11px] leading-relaxed">
                        This host organization operates under agreement with our university career office. Internship hours count toward academic graduation credits.
                    </p>
                </div>

            </div>

        </div>

    </div>
</x-layout>

