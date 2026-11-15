<x-layout>
@php
    /* ── Read current filter values from request ── */
    $fSearch          = request('search', '');
    $fLocation        = request('location', '');
    $fOppType         = request('opportunity_type', 'all');
    $fExpLevel        = request('experience_level', 'all');
    $fWorkType        = request('type', '');
    $fCategory        = request('category', '');
    $fStipend         = request('stipend', '');
    $fSalaryMin       = request('salary_min', request('stipend_min', ''));
    $fSalaryMax       = request('salary_max', '');
    $fDeadline        = request('deadline', 'open');
    $fDatePosted      = request('date_posted', '');
    $fVerifiedOnly    = request()->boolean('verified_only');
    $fSort            = request('sort', 'newest');

    /* Active filter count (excluding sort, default deadline, and default oppType=all) */
    $activeCount = collect([
        $fSearch          !== '',
        $fLocation        !== '',
        $fOppType         !== 'all' && $fOppType !== '',
        $fExpLevel        !== 'all' && $fExpLevel !== '',
        $fWorkType        !== '',
        $fCategory        !== '',
        $fStipend         !== '' || $fSalaryMin !== '' || $fSalaryMax !== '',
        $fDeadline        !== 'open',
        $fDatePosted      !== '',
        $fVerifiedOnly    === true,
    ])->filter()->count();
@endphp

<div class="space-y-6"
     x-data="{
         showFilters: {{ ($activeCount > 0 || request()->hasAny(['stipend','salary_min','salary_max','deadline','sort','category','type','experience_level','date_posted','verified_only'])) ? 'true' : 'false' }},
         oppType: '{{ $fOppType }}',
         workType: '{{ $fWorkType }}',
         expLevel: '{{ $fExpLevel }}',
         stipendMode: '{{ $fStipend }}',
         datePosted: '{{ $fDatePosted }}',
         verifiedOnly: {{ $fVerifiedOnly ? 'true' : 'false' }},
     }">

    {{-- ═══════════════════════════════════════════════════════════
         HEADER & TOP NAVIGATION
    ════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h2 class="text-2xl font-black tracking-tight text-slate-900">Opportunities & Job Board</h2>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>{{ $posts->count() }} {{ Str::plural('open position', $posts->count()) }}</span>
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-600 mt-1">
                Discover university-approved internships and entry-level career jobs from registered employers.
            </p>
        </div>

        {{-- Toggle Filter Button --}}
        <div class="flex items-center gap-2 shrink-0">
            <button type="button"
                    @click="showFilters = !showFilters"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-xs font-bold transition-all cursor-pointer shadow-2xs focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    :class="showFilters
                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                        : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-400 hover:text-emerald-700'">
                <i class="fa-solid fa-sliders text-xs"></i>
                <span>Filter & Refine</span>
                @if($activeCount > 0)
                    <span class="w-5 h-5 rounded-full bg-white text-emerald-800 font-black text-[10px] flex items-center justify-center leading-none">
                        {{ $activeCount }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         OPPORTUNITY TYPE TABS (All vs Internships vs Jobs)
    ════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-wrap items-center gap-2 p-1.5 bg-slate-100/90 rounded-2xl border border-slate-200/80 shadow-2xs">
        <a href="{{ route('student.posts.index', array_merge(request()->except(['opportunity_type', 'page']), ['opportunity_type' => 'all'])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all {{ ($fOppType === 'all' || empty($fOppType)) ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }}">
            <span>🌐 All Opportunities</span>
            <span class="px-1.5 py-0.2 rounded-md text-[10px] {{ ($fOppType === 'all' || empty($fOppType)) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                {{ $counts['all'] ?? 0 }}
            </span>
        </a>

        <a href="{{ route('student.posts.index', array_merge(request()->except(['opportunity_type', 'page']), ['opportunity_type' => 'internship'])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $fOppType === 'internship' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }}">
            <span>🎓 Internships</span>
            <span class="px-1.5 py-0.2 rounded-md text-[10px] {{ $fOppType === 'internship' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                {{ $counts['internships'] ?? 0 }}
            </span>
        </a>

        <a href="{{ route('student.posts.index', array_merge(request()->except(['opportunity_type', 'page']), ['opportunity_type' => 'job'])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $fOppType === 'job' ? 'bg-white text-indigo-800 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }}">
            <span>💼 Career Jobs</span>
            <span class="px-1.5 py-0.2 rounded-md text-[10px] {{ $fOppType === 'job' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-600' }}">
                {{ $counts['jobs'] ?? 0 }}
            </span>
        </a>

        <a href="{{ route('student.posts.index', array_merge(request()->except(['opportunity_type', 'page']), ['opportunity_type' => 'full_time'])) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $fOppType === 'full_time' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
            <span>Full-Time ({{ $counts['full_time'] ?? 0 }})</span>
        </a>

        <a href="{{ route('student.posts.index', array_merge(request()->except(['opportunity_type', 'page']), ['opportunity_type' => 'part_time'])) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $fOppType === 'part_time' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
            <span>Part-Time ({{ $counts['part_time'] ?? 0 }})</span>
        </a>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PRIMARY SEARCH BAR
    ════════════════════════════════════════════════════════════ --}}
    <form action="{{ route('student.posts.index') }}" method="GET" class="bg-white p-2.5 sm:p-3 rounded-2xl border border-slate-200 shadow-xs">
        {{-- Preserve active tab --}}
        <input type="hidden" name="opportunity_type" value="{{ $fOppType }}">

        <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
            {{-- Keyword search --}}
            <div class="md:col-span-6 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="search" value="{{ $fSearch }}"
                       placeholder="Job title, keywords, tech stack, or employer..."
                       class="w-full pl-9 pr-3 py-2.5 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-900 transition-colors">
            </div>

            {{-- Location search --}}
            <div class="md:col-span-4 relative">
                <i class="fa-solid fa-location-dot absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="location" value="{{ $fLocation }}"
                       placeholder="City, province, or 'Remote'..."
                       class="w-full pl-9 pr-3 py-2.5 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-900 transition-colors">
            </div>

            {{-- Search Submit Button --}}
            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit"
                        class="w-full h-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-search text-xs"></i>
                    <span>Search</span>
                </button>
            </div>
        </div>
    </form>

    {{-- ═══════════════════════════════════════════════════════════
         EXTENDED FILTER PANEL (COLLAPSIBLE)
    ════════════════════════════════════════════════════════════ --}}
    <div x-show="showFilters"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         style="display:none">

        <form action="{{ route('student.posts.index') }}" method="GET" id="filter-form"
              class="p-4 sm:p-6 rounded-3xl bg-white border border-slate-200/90 shadow-md space-y-5">

            <input type="hidden" name="search" value="{{ $fSearch }}">
            <input type="hidden" name="location" value="{{ $fLocation }}">

            {{-- Row 1: Opportunity Type, Work Mode, Experience Level, Category --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Listing Type --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        <i class="fa-solid fa-briefcase text-emerald-600 mr-1"></i>Opportunity Type
                    </label>
                    <select name="opportunity_type"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800">
                        <option value="all" @selected($fOppType === 'all')>🌐 All Opportunities</option>
                        <option value="internship" @selected($fOppType === 'internship')>🎓 Internship Only</option>
                        <option value="job" @selected($fOppType === 'job')>💼 All Jobs (Full/Part/Contract)</option>
                        <option value="full_time" @selected($fOppType === 'full_time')>💼 Full-Time Job</option>
                        <option value="part_time" @selected($fOppType === 'part_time')>🕒 Part-Time Job</option>
                        <option value="contract" @selected($fOppType === 'contract')>📄 Contract Job</option>
                    </select>
                </div>

                {{-- Work Mode --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        <i class="fa-solid fa-laptop-house text-indigo-600 mr-1"></i>Work Mode
                    </label>
                    <select name="type"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800">
                        <option value="" @selected(empty($fWorkType))>Any Work Mode</option>
                        <option value="on_site" @selected($fWorkType === 'on_site')>🏢 On-Site (Office)</option>
                        <option value="remote" @selected($fWorkType === 'remote')>🏠 Remote (Work from Home)</option>
                        <option value="hybrid" @selected($fWorkType === 'hybrid')>🔄 Hybrid (Flexible)</option>
                    </select>
                </div>

                {{-- Experience Level --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        <i class="fa-solid fa-graduation-cap text-amber-600 mr-1"></i>Experience Level
                    </label>
                    <select name="experience_level"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800">
                        <option value="all" @selected($fExpLevel === 'all')>Any Experience Level</option>
                        <option value="no_experience" @selected($fExpLevel === 'no_experience')>🌱 No Experience Required</option>
                        <option value="entry_level" @selected($fExpLevel === 'entry_level')>🎓 Entry Level / Fresh Grad</option>
                        <option value="junior" @selected($fExpLevel === 'junior')>⚡ Junior (1-2 years)</option>
                        <option value="mid_level" @selected($fExpLevel === 'mid_level')>🚀 Mid-Level (3+ years)</option>
                    </select>
                </div>

                {{-- Category --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        <i class="fa-solid fa-layer-group text-purple-600 mr-1"></i>Category
                    </label>
                    <select name="category"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800">
                        <option value="" @selected(empty($fCategory))>All Categories</option>
                        <option value="General IT" @selected($fCategory === 'General IT')>General IT</option>
                        <option value="Software Engineering" @selected($fCategory === 'Software Engineering')>Software Engineering</option>
                        <option value="Networking & Security" @selected($fCategory === 'Networking & Security')>Networking & Security</option>
                        <option value="Design & UI/UX" @selected($fCategory === 'Design & UI/UX')>Design & UI/UX</option>
                        <option value="Business Administration" @selected($fCategory === 'Business Administration')>Business Administration</option>
                    </select>
                </div>
            </div>

            {{-- Row 2: Compensation / Salary Range, Date Posted, Sort, Verified Checkbox --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-3 border-t border-slate-100">
                {{-- Salary / Stipend Min --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        <i class="fa-solid fa-dollar-sign text-emerald-600 mr-1"></i>Min Compensation ($/mo)
                    </label>
                    <input type="number" name="salary_min" value="{{ $fSalaryMin }}" placeholder="e.g. 200" step="50" min="0"
                           class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800">
                </div>

                {{-- Salary Max --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        <i class="fa-solid fa-dollar-sign text-emerald-600 mr-1"></i>Max Compensation ($/mo)
                    </label>
                    <input type="number" name="salary_max" value="{{ $fSalaryMax }}" placeholder="e.g. 800" step="50" min="0"
                           class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800">
                </div>

                {{-- Date Posted --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        <i class="fa-solid fa-clock text-sky-600 mr-1"></i>Date Posted
                    </label>
                    <select name="date_posted"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800">
                        <option value="" @selected(empty($fDatePosted))>Any Time</option>
                        <option value="24h" @selected($fDatePosted === '24h')>Past 24 Hours</option>
                        <option value="7d" @selected($fDatePosted === '7d')>Past Week (7 Days)</option>
                        <option value="30d" @selected($fDatePosted === '30d')>Past Month (30 Days)</option>
                    </select>
                </div>

                {{-- Sort Order --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        <i class="fa-solid fa-arrow-down-wide-short text-slate-600 mr-1"></i>Sort By
                    </label>
                    <select name="sort"
                            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800">
                        <option value="newest" @selected($fSort === 'newest')>Newest First</option>
                        <option value="deadline" @selected($fSort === 'deadline')>Closing Soonest</option>
                        <option value="salary_high" @selected($fSort === 'salary_high' || $fSort === 'stipend')>Highest Salary / Stipend</option>
                        <option value="salary_low" @selected($fSort === 'salary_low')>Lowest Salary</option>
                    </select>
                </div>
            </div>

            {{-- Row 3: Quick Checkboxes & Apply Actions --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t border-slate-100">
                <div class="flex flex-wrap items-center gap-4">
                    {{-- Verified Employer checkbox --}}
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="verified_only" value="1" @checked($fVerifiedOnly)
                               class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                        <span>Verified Employers Only ✓</span>
                    </label>

                    {{-- Closing soon filter radio --}}
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="deadline" value="soon" @checked($fDeadline === 'soon')
                               class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-slate-300">
                        <span>Closing Soon (≤ 7 Days) 🔥</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2.5">
                    @if($activeCount > 0 || $fSort !== 'newest' || $fDeadline !== 'open' || $fOppType !== 'all')
                        <a href="{{ route('student.posts.index') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-rose-600 transition-colors">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                            <span>Reset Filters</span>
                        </a>
                    @endif

                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs cursor-pointer">
                        <i class="fa-solid fa-check text-xs"></i>
                        <span>Apply All Filters</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ACTIVE FILTER CHIPS
    ════════════════════════════════════════════════════════════ --}}
    @if($activeCount > 0 || $fSort !== 'newest' || $fDeadline !== 'open')
        <div class="flex flex-wrap items-center gap-2 -mt-2">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Filters:</span>

            @if($fSearch)
                <a href="{{ route('student.posts.index', request()->except('search')) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-colors">
                    <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                    <span>"{{ Str::limit($fSearch, 20) }}"</span>
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fLocation)
                <a href="{{ route('student.posts.index', request()->except('location')) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-colors">
                    <i class="fa-solid fa-location-dot text-[10px]"></i>
                    <span>{{ $fLocation }}</span>
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fOppType && $fOppType !== 'all')
                <a href="{{ route('student.posts.index', request()->except('opportunity_type')) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">
                    <i class="fa-solid fa-briefcase text-[10px]"></i>
                    <span>{{ ucfirst(str_replace('_', ' ', $fOppType)) }}</span>
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fExpLevel && $fExpLevel !== 'all')
                <a href="{{ route('student.posts.index', request()->except('experience_level')) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">
                    <i class="fa-solid fa-graduation-cap text-[10px]"></i>
                    <span>{{ ucfirst(str_replace('_', ' ', $fExpLevel)) }}</span>
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fWorkType)
                <a href="{{ route('student.posts.index', request()->except('type')) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">
                    <i class="fa-solid fa-laptop-house text-[10px]"></i>
                    <span>{{ ucfirst(str_replace('_', ' ', $fWorkType)) }}</span>
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fCategory)
                <a href="{{ route('student.posts.index', request()->except('category')) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-800 border border-purple-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">
                    <i class="fa-solid fa-layer-group text-[10px]"></i>
                    <span>{{ $fCategory }}</span>
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fSalaryMin || $fSalaryMax)
                <a href="{{ route('student.posts.index', request()->except(['salary_min', 'salary_max', 'stipend', 'stipend_min'])) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">
                    <i class="fa-solid fa-dollar-sign text-[10px]"></i>
                    <span>${{ $fSalaryMin ?: '0' }} - ${{ $fSalaryMax ?: 'Any' }}/mo</span>
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fDeadline === 'soon')
                <a href="{{ route('student.posts.index', request()->except('deadline')) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">
                    <i class="fa-solid fa-fire-flame-curved text-[10px]"></i>
                    <span>Closing Soon (≤ 7 Days)</span>
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fVerifiedOnly)
                <a href="{{ route('student.posts.index', request()->except('verified_only')) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-800 border border-teal-200 hover:bg-rose-50 hover:text-rose-600 transition-colors">
                    <i class="fa-solid fa-circle-check text-[10px] text-teal-600"></i>
                    <span>Verified Employers Only</span>
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            <a href="{{ route('student.posts.index') }}"
               class="text-[11px] font-bold text-rose-600 hover:underline ml-1">
                Clear All ✕
            </a>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         POST CARDS GRID
    ════════════════════════════════════════════════════════════ --}}
    @if ($posts->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($posts as $post)
                @php
                    $initBooked   = in_array($post->id, $bookmarkedPostIds ?? []);
                    $daysLeft     = now()->diffInDays(\Carbon\Carbon::parse($post->deadline), false);
                    $closingSoon  = $daysLeft >= 0 && $daysLeft <= 7;
                    $isVerified   = $post->companyProfile?->isVerified();
                @endphp

                <div class="relative p-6 rounded-3xl bg-white border shadow-xs hover:shadow-md transition-all flex flex-col justify-between group {{ $closingSoon ? 'border-amber-200 hover:border-amber-400/60' : 'border-slate-200/80 hover:border-emerald-300/50' }}"
                     x-data="{
                         bookmarked: {{ $initBooked ? 'true' : 'false' }},
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

                    {{-- Closing Soon ribbon --}}
                    @if($closingSoon)
                        <div class="absolute top-0 right-5 px-2.5 py-1 rounded-b-xl bg-amber-500 text-white text-[10px] font-bold shadow-xs flex items-center gap-1">
                            <i class="fa-solid fa-fire-flame-curved text-[9px]"></i>
                            {{ $daysLeft == 0 ? 'Closes today!' : $daysLeft.' day'.($daysLeft > 1?'s':'').' left' }}
                        </div>
                    @endif

                    {{-- Card Header: Badges & Bookmark Button --}}
                    <div class="space-y-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                                {{-- Opportunity Type Badge --}}
                                @if($post->isInternship())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span>🎓</span> Internship
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <span>💼</span> {{ $post->opportunity_type_label }}
                                    </span>
                                @endif

                                {{-- Work Mode Badge --}}
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ match($post->type) {
                                        'remote'  => '🏠 Remote',
                                        'hybrid'  => '🔄 Hybrid',
                                        default   => '🏢 On-Site'
                                    } }}
                                </span>
                            </div>

                            {{-- Heart / Bookmark Button --}}
                            <button @click.prevent="toggle()"
                                    :disabled="loading"
                                    :title="bookmarked ? 'Remove from saved jobs' : 'Save this job'"
                                    class="w-8 h-8 rounded-xl flex items-center justify-center transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-rose-400/40 shrink-0 cursor-pointer"
                                    :class="bookmarked
                                        ? 'bg-rose-50 text-rose-500 border border-rose-200 hover:bg-rose-100'
                                        : 'bg-slate-100 text-slate-400 border border-slate-200 hover:bg-rose-50 hover:text-rose-400 hover:border-rose-200'">
                                <i class="text-sm"
                                   :class="[bookmarked ? 'fa-solid fa-heart' : 'fa-regular fa-heart', loading ? 'animate-pulse' : '']"></i>
                            </button>
                        </div>

                        {{-- Company Logo + Title + Verified Badge --}}
                        <div class="flex items-start gap-3">
                            @php
                                $compLogo     = $post->companyProfile?->logo_url;
                                $compHasLogo  = $post->companyProfile?->has_logo;
                                $compInitials = $post->companyProfile?->initials ?? 'CO';
                            @endphp
                            @if($compHasLogo && $compLogo)
                                <a href="{{ route('student.companies.show', $post->companyProfile) }}" title="View {{ $post->companyProfile->company_name }} profile">
                                    <img src="{{ $compLogo }}" alt="{{ $post->companyProfile->company_name }}"
                                         class="w-11 h-11 rounded-2xl object-cover shrink-0 border border-slate-200 shadow-xs hover:opacity-90 transition-opacity">
                                </a>
                            @else
                                <a href="{{ route('student.companies.show', $post->companyProfile) }}" title="View {{ $post->companyProfile->company_name }} profile"
                                   class="w-11 h-11 rounded-2xl bg-emerald-700 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs hover:bg-emerald-800 transition-colors">
                                    {{ $compInitials }}
                                </a>
                            @endif
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-extrabold text-slate-900 group-hover:text-emerald-700 transition-colors line-clamp-1">
                                    <a href="{{ route('student.posts.show', $post) }}">{{ $post->title }}</a>
                                </h3>
                                <p class="text-xs font-semibold text-slate-500 mt-0.5 truncate flex items-center gap-1.5">
                                    <a href="{{ route('student.companies.show', $post->companyProfile) }}"
                                       class="hover:text-emerald-700 hover:underline transition-colors truncate">
                                        {{ $post->companyProfile->company_name }}
                                    </a>
                                    @if($isVerified)
                                        <i class="fa-solid fa-circle-check text-emerald-600 text-[11px] shrink-0" title="Verified Employer"></i>
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Category & Experience Chips --}}
                        <div class="flex flex-wrap items-center gap-1.5 text-[10px]">
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold border border-slate-200/80">
                                {{ $post->category ?? 'General IT' }}
                            </span>
                            @if($post->experience_level_label)
                                <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 font-semibold border border-amber-200/80">
                                    {{ $post->experience_level_label }}
                                </span>
                            @endif
                        </div>

                        {{-- Job Description Snippet --}}
                        <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                            {{ $post->description }}
                        </p>
                    </div>

                    {{-- Footer: Location, Compensation, Details button --}}
                    <div class="mt-4 pt-3.5 border-t border-slate-100 flex items-center justify-between gap-2">
                        <div class="text-[11px] text-slate-500 space-y-0.5 min-w-0">
                            <div class="flex items-center gap-1 truncate">
                                <i class="fa-solid fa-location-dot text-slate-400 text-[10px] shrink-0"></i>
                                <span class="truncate">{{ $post->location }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-bold text-emerald-700">
                                    {{ $post->compensation_display }}
                                </span>
                                <span class="text-slate-300">·</span>
                                <span class="{{ $closingSoon ? 'text-amber-600 font-bold' : '' }}">
                                    Deadline: {{ \Carbon\Carbon::parse($post->deadline)->format('M d') }}
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('student.posts.show', $post) }}"
                           class="shrink-0 px-3.5 py-2 rounded-xl bg-slate-100 group-hover:bg-emerald-600 group-hover:text-white text-slate-700 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                            <span>View Details</span>
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

    @else
        {{-- Empty State --}}
        <div class="p-12 rounded-3xl bg-white border border-slate-200/80 text-center space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto">
                <i class="fa-solid fa-magnifying-glass-minus text-2xl text-slate-300"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-800">No opportunities match your filter</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                    Try switching between Internships and Jobs tabs or reset your active filters.
                </p>
            </div>
            <a href="{{ route('student.posts.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold hover:bg-emerald-100 transition-colors">
                <i class="fa-solid fa-rotate-left text-xs"></i>
                Reset All Filters
            </a>
        </div>
    @endif

</div>
</x-layout>
