<x-layout>
@php
    /* ── Read current filter values from request ── */
    $fSearch     = request('search', '');
    $fCategory   = request('category', '');
    $fStipend    = request('stipend', '');
    $fStipendMin = request('stipend_min', '');
    $fDeadline   = request('deadline', 'open');
    $fSort       = request('sort', 'newest');

    /* Active filter count (excluding sort & default deadline) */
    $activeCount = collect([
        $fSearch     !== '',
        $fCategory   !== '',
        $fStipend    !== '',
        $fDeadline   !== 'open',
    ])->filter()->count();
@endphp

<div class="space-y-5"
     x-data="{
         showFilters: {{ ($activeCount > 0 || request()->hasAny(['stipend','deadline','sort','category'])) ? 'true' : 'false' }},
         stipendMode: '{{ $fStipend }}',
     }">

    {{-- ═══════════════════════════════════════════════════════════
         HEADER + TOP CONTROLS
    ════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl font-black tracking-tight text-slate-900">Internship Vacancies</h2>
            <p class="text-xs sm:text-sm text-slate-600 mt-0.5">
                Explore university-vetted opportunities from registered host companies.
                <span class="font-semibold text-slate-800">{{ $posts->count() }} {{ Str::plural('listing', $posts->count()) }} found.</span>
            </p>
        </div>

        {{-- Toggle Filter Panel Button --}}
        <button type="button"
                @click="showFilters = !showFilters"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-xs font-bold
                       transition-all self-start shrink-0 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                :class="showFilters
                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                    : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-400'">
            <i class="fa-solid fa-sliders text-xs"></i>
            <span>Filters & Sort</span>
            @if($activeCount > 0)
                <span class="w-5 h-5 rounded-full bg-white text-emerald-700 font-black text-[10px]
                             flex items-center justify-center leading-none">
                    {{ $activeCount }}
                </span>
            @endif
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         COLLAPSIBLE FILTER PANEL
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
              class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">

            {{-- Row 1: Search + Category --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">

                {{-- Search --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $fSearch }}"
                           placeholder="Role, skills, company..."
                           class="w-full pl-8 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200
                                  focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500
                                  focus:bg-white transition-colors">
                </div>

                {{-- Category --}}
                <div class="relative"
                     x-data="{
                         open: false,
                         catVal: '{{ $fCategory }}',
                         options: [
                             { value: '', label: 'All Categories', icon: 'fa-solid fa-layer-group text-slate-400' },
                             { value: 'General IT', label: 'General IT', icon: 'fa-solid fa-laptop-code text-blue-500' },
                             { value: 'Software Engineering', label: 'Software Engineering', icon: 'fa-solid fa-code text-emerald-500' },
                             { value: 'Networking & Security', label: 'Networking & Security', icon: 'fa-solid fa-shield-halved text-rose-500' },
                             { value: 'Design & UI/UX', label: 'Design & UI/UX', icon: 'fa-solid fa-palette text-purple-500' },
                             { value: 'Business Administration', label: 'Business Administration', icon: 'fa-solid fa-briefcase text-amber-500' },
                         ],
                         get current() { return this.options.find(o => o.value === this.catVal) || this.options[0]; },
                         pick(val) { this.catVal = val; this.open = false; }
                     }">
                    <input type="hidden" name="category" :value="catVal">
                    <button type="button" @click="open = !open"
                            class="w-full inline-flex items-center justify-between gap-2 py-2 pl-3.5 pr-3 text-xs
                                   rounded-xl bg-slate-50 border border-slate-200 hover:border-emerald-400
                                   font-medium text-slate-700 transition-all cursor-pointer focus:outline-none
                                   focus:ring-2 focus:ring-emerald-500/20">
                        <span class="flex items-center gap-2 truncate">
                            <i :class="current.icon" class="text-xs shrink-0"></i>
                            <span x-text="current.label" class="truncate font-semibold text-slate-800"></span>
                        </span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-150 shrink-0"
                           :class="open ? 'rotate-180 text-emerald-600' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open=false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute left-0 top-full z-40 mt-1.5 w-60 rounded-2xl bg-white border border-slate-200
                                shadow-xl p-1.5 space-y-0.5"
                         style="display:none">
                        <template x-for="item in options" :key="item.value">
                            <button type="button" @click="pick(item.value)"
                                    class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl
                                           transition-all cursor-pointer text-left"
                                    :class="catVal===item.value
                                        ? 'bg-emerald-50 text-emerald-800 font-bold'
                                        : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                <span class="flex items-center gap-2">
                                    <i :class="item.icon" class="text-xs"></i>
                                    <span x-text="item.label"></span>
                                </span>
                                <i x-show="catVal===item.value" class="fa-solid fa-check text-[10px] text-emerald-600"></i>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Sort --}}
                <div class="relative"
                     x-data="{
                         open: false,
                         val: '{{ $fSort }}',
                         options: [
                             { value: 'newest',   label: 'Newest First',          icon: 'fa-solid fa-arrow-down-wide-short text-slate-500' },
                             { value: 'deadline', label: 'Deadline (Soonest)',     icon: 'fa-solid fa-calendar-days text-amber-500' },
                             { value: 'stipend',  label: 'Stipend (High → Low)',   icon: 'fa-solid fa-arrow-down-9-1 text-emerald-500' },
                         ],
                         get current() { return this.options.find(o => o.value === this.val) || this.options[0]; },
                         pick(v) { this.val = v; this.open = false; }
                     }">
                    <input type="hidden" name="sort" :value="val">
                    <button type="button" @click="open = !open"
                            class="w-full inline-flex items-center justify-between gap-2 py-2 pl-3.5 pr-3 text-xs
                                   rounded-xl bg-slate-50 border border-slate-200 hover:border-emerald-400
                                   font-medium text-slate-700 transition-all cursor-pointer focus:outline-none
                                   focus:ring-2 focus:ring-emerald-500/20">
                        <span class="flex items-center gap-2 truncate">
                            <i :class="current.icon" class="text-xs shrink-0"></i>
                            <span x-text="current.label" class="truncate font-semibold text-slate-800"></span>
                        </span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-150 shrink-0"
                           :class="open ? 'rotate-180 text-emerald-600' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open=false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute left-0 top-full z-40 mt-1.5 w-52 rounded-2xl bg-white border border-slate-200
                                shadow-xl p-1.5 space-y-0.5"
                         style="display:none">
                        <template x-for="item in options" :key="item.value">
                            <button type="button" @click="pick(item.value)"
                                    class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl
                                           transition-all cursor-pointer text-left"
                                    :class="val===item.value
                                        ? 'bg-emerald-50 text-emerald-800 font-bold'
                                        : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                <span class="flex items-center gap-2">
                                    <i :class="item.icon" class="text-xs"></i>
                                    <span x-text="item.label"></span>
                                </span>
                                <i x-show="val===item.value" class="fa-solid fa-check text-[10px] text-emerald-600"></i>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Row 2: Stipend + Deadline --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-100">

                {{-- Stipend Filter --}}
                <div class="space-y-2">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <i class="fa-solid fa-dollar-sign text-emerald-500 mr-1"></i>Stipend
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach([
                            ''    => ['label' => 'Any',       'icon' => 'fa-solid fa-circle-dot'],
                            'has' => ['label' => 'Has Stipend','icon' => 'fa-solid fa-circle-check'],
                            'none'=> ['label' => 'No Stipend', 'icon' => 'fa-solid fa-circle-xmark'],
                            'min' => ['label' => '≥ Amount',   'icon' => 'fa-solid fa-greater-than-equal'],
                        ] as $val => $opt)
                            <button type="button"
                                    @click="stipendMode = '{{ $val }}'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-[11px] font-semibold border transition-all"
                                    :class="stipendMode === '{{ $val }}'
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                        : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-emerald-400 hover:text-emerald-700'">
                                <i class="{{ $opt['icon'] }} text-[10px]"></i>
                                {{ $opt['label'] }}
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="stipend" :value="stipendMode">

                    {{-- Min stipend input — shown only when mode = 'min' --}}
                    <div x-show="stipendMode === 'min'"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         style="display:none"
                         class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-xs pointer-events-none">$</span>
                            <input type="number"
                                   name="stipend_min"
                                   value="{{ $fStipendMin }}"
                                   min="0"
                                   step="50"
                                   placeholder="e.g. 200"
                                   class="w-full pl-6 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200
                                          focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white">
                        </div>
                        <span class="text-xs text-slate-500 font-medium whitespace-nowrap">per month</span>
                    </div>
                </div>

                {{-- Deadline Filter --}}
                <div class="space-y-2">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <i class="fa-solid fa-calendar text-amber-500 mr-1"></i>Deadline
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach([
                            'open' => ['label' => 'Open Now',       'icon' => 'fa-solid fa-circle-dot',  'color' => 'emerald'],
                            'soon' => ['label' => 'Closing ≤7 days','icon' => 'fa-solid fa-fire-flame-curved','color' => 'amber'],
                        ] as $val => $opt)
                            <label class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-[11px]
                                          font-semibold border cursor-pointer transition-all
                                          {{ $fDeadline === $val
                                              ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                              : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-emerald-400 hover:text-emerald-700' }}">
                                <input type="radio" name="deadline" value="{{ $val }}"
                                       {{ $fDeadline === $val ? 'checked' : '' }}
                                       class="sr-only"
                                       onchange="document.getElementById('filter-form').submit()">
                                <i class="{{ $opt['icon'] }} text-[10px]"></i>
                                {{ $opt['label'] }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Row 3: Actions --}}
            <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                @if($activeCount > 0 || $fSort !== 'newest' || $fDeadline !== 'open')
                    <a href="{{ route('student.posts.index') }}"
                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500
                              hover:text-rose-600 transition-colors">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                        Reset all filters
                    </a>
                @else
                    <div></div>
                @endif

                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-emerald-600 text-white
                               text-xs font-bold hover:bg-emerald-700 transition-colors shadow-xs">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ACTIVE FILTER CHIPS
    ════════════════════════════════════════════════════════════ --}}
    @if($activeCount > 0 || $fSort !== 'newest' || $fDeadline !== 'open')
        <div class="flex flex-wrap items-center gap-2 -mt-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active:</span>

            @if($fSearch)
                <a href="{{ route('student.posts.index', array_merge(request()->except('search'), [])) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold
                          bg-slate-100 text-slate-700 border border-slate-200 hover:bg-rose-50 hover:text-rose-600
                          hover:border-rose-200 transition-colors">
                    <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                    "{{ Str::limit($fSearch, 20) }}"
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fCategory)
                <a href="{{ route('student.posts.index', array_merge(request()->except('category'), [])) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold
                          bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-rose-50 hover:text-rose-600
                          hover:border-rose-200 transition-colors">
                    <i class="fa-solid fa-layer-group text-[10px]"></i>
                    {{ $fCategory }}
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fStipend)
                @php $stipendLabels = ['has'=>'Has Stipend','none'=>'No Stipend','min'=>'≥ $'.$fStipendMin.'/mo']; @endphp
                <a href="{{ route('student.posts.index', array_merge(request()->except(['stipend','stipend_min']), [])) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold
                          bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-rose-50 hover:text-rose-600
                          hover:border-rose-200 transition-colors">
                    <i class="fa-solid fa-dollar-sign text-[10px]"></i>
                    {{ $stipendLabels[$fStipend] ?? $fStipend }}
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fDeadline === 'soon')
                <a href="{{ route('student.posts.index', array_merge(request()->except('deadline'), [])) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold
                          bg-amber-50 text-amber-700 border border-amber-200 hover:bg-rose-50 hover:text-rose-600
                          hover:border-rose-200 transition-colors">
                    <i class="fa-solid fa-fire-flame-curved text-[10px]"></i>
                    Closing Soon
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif

            @if($fSort !== 'newest')
                @php $sortLabels = ['deadline'=>'Sort: Deadline','stipend'=>'Sort: Stipend ↓']; @endphp
                <a href="{{ route('student.posts.index', array_merge(request()->except('sort'), [])) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold
                          bg-blue-50 text-blue-700 border border-blue-200 hover:bg-rose-50 hover:text-rose-600
                          hover:border-rose-200 transition-colors">
                    <i class="fa-solid fa-arrow-down-wide-short text-[10px]"></i>
                    {{ $sortLabels[$fSort] ?? $fSort }}
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </a>
            @endif
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
                @endphp

                <div class="relative p-6 rounded-3xl bg-white border shadow-xs hover:shadow-md transition-all
                            flex flex-col justify-between group
                            {{ $closingSoon ? 'border-amber-200 hover:border-amber-400/60' : 'border-slate-200/80 hover:border-emerald-300/50' }}"
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
                        <div class="absolute top-0 right-5 px-2.5 py-1 rounded-b-xl bg-amber-500 text-white
                                    text-[10px] font-bold shadow-xs flex items-center gap-1">
                            <i class="fa-solid fa-fire-flame-curved text-[9px]"></i>
                            {{ $daysLeft == 0 ? 'Closes today!' : $daysLeft.' day'.($daysLeft > 1?'s':'').' left' }}
                        </div>
                    @endif

                    {{-- Top Row: Category + Bookmark --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            @if ($post->category)
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wider
                                             bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $post->category }}
                                </span>
                            @else
                                <div></div>
                            @endif

                            {{-- ❤️ Bookmark --}}
                            <button @click.prevent="toggle()"
                                    :disabled="loading"
                                    :title="bookmarked ? 'Remove from saved jobs' : 'Save this job'"
                                    class="w-8 h-8 rounded-xl flex items-center justify-center transition-all duration-200
                                           focus:outline-none focus:ring-2 focus:ring-rose-400/40"
                                    :class="bookmarked
                                        ? 'bg-rose-50 text-rose-500 border border-rose-200 hover:bg-rose-100'
                                        : 'bg-slate-100 text-slate-400 border border-slate-200 hover:bg-rose-50 hover:text-rose-400 hover:border-rose-200'">
                                <i class="text-sm"
                                   :class="[bookmarked ? 'fa-solid fa-heart' : 'fa-regular fa-heart', loading ? 'animate-pulse' : '']"></i>
                            </button>
                        </div>

                        {{-- Company Logo + Title --}}
                        <div class="flex items-center gap-3">
                            @php
                                $compLogo     = $post->companyProfile->logo_url;
                                $compHasLogo  = $post->companyProfile->has_logo;
                                $compInitials = $post->companyProfile->initials;
                            @endphp
                            @if($compHasLogo && $compLogo)
                                <a href="{{ route('student.companies.show', $post->companyProfile) }}" title="View {{ $post->companyProfile->company_name }} profile">
                                    <img src="{{ $compLogo }}" alt="{{ $post->companyProfile->company_name }}"
                                         class="w-10 h-10 rounded-xl object-cover shrink-0 border border-slate-200 shadow-xs hover:opacity-90 transition-opacity">
                                </a>
                            @else
                                <a href="{{ route('student.companies.show', $post->companyProfile) }}" title="View {{ $post->companyProfile->company_name }} profile"
                                   class="w-10 h-10 rounded-xl bg-teal-700 text-white flex items-center
                                          justify-center font-bold text-xs shrink-0 shadow-xs hover:bg-teal-800 transition-colors">
                                    {{ $compInitials }}
                                </a>
                            @endif
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-extrabold text-slate-900 group-hover:text-emerald-600
                                           transition-colors line-clamp-1">
                                    <a href="{{ route('student.posts.show', $post) }}">{{ $post->title }}</a>
                                </h3>
                                <p class="text-xs font-semibold text-slate-500 mt-0.5 truncate">
                                    <a href="{{ route('student.companies.show', $post->companyProfile) }}"
                                       class="hover:text-emerald-600 hover:underline transition-colors">
                                        {{ $post->companyProfile->company_name }}
                                    </a>
                                </p>
                            </div>
                        </div>

                        <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                            {{ $post->description }}
                        </p>
                    </div>

                    {{-- Footer --}}
                    <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
                        <div class="text-[11px] text-slate-500 space-y-0.5 min-w-0">
                            <div class="flex items-center gap-1 truncate">
                                <i class="fa-solid fa-location-dot text-slate-300 text-[10px] shrink-0"></i>
                                <span class="truncate">{{ $post->location }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($post->stipend)
                                    <span class="font-bold text-emerald-600">
                                        ${{ number_format($post->stipend, 0) }}/mo
                                    </span>
                                    <span class="text-slate-300">·</span>
                                @endif
                                <span class="{{ $closingSoon ? 'text-amber-600 font-semibold' : '' }}">
                                    {{ \Carbon\Carbon::parse($post->deadline)->format('M d') }}
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('student.posts.show', $post) }}"
                           class="shrink-0 px-3 py-1.5 rounded-xl bg-slate-100 group-hover:bg-emerald-600
                                  group-hover:text-white text-slate-700 text-xs font-bold transition-all
                                  flex items-center gap-1">
                            <span>Details</span>
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
                <h3 class="text-base font-bold text-slate-800">No internships match your filters</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                    Try adjusting or resetting your filters to discover available positions.
                </p>
            </div>
            <a href="{{ route('student.posts.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 text-xs font-bold
                      text-slate-700 hover:bg-slate-200 transition-colors">
                <i class="fa-solid fa-rotate-left text-xs"></i>
                Reset Filters
            </a>
        </div>
    @endif

</div>
</x-layout>
