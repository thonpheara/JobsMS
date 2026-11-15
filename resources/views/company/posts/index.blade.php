<x-layout>
    @php
        $autoEditPost = null;
        if (request('edit')) {
            $autoEditPost = $posts->firstWhere('id', request('edit')) ?? \App\Models\InternshipPost::find(request('edit'));
        }
        $failedEditPostId = old('editing_post_id');
        $initialEditOpen = ($errors->any() && $failedEditPostId) || ($autoEditPost !== null);
        $initialCreateOpen = ($errors->any() && !$failedEditPostId) || request('create');

        $initialEditPost = [
            'id' => '',
            'title' => '',
            'category' => 'General IT',
            'opportunity_type' => 'internship',
            'type' => 'on_site',
            'experience_level' => 'entry_level',
            'location' => '',
            'duration_weeks' => 12,
            'slots' => 1,
            'stipend' => '',
            'salary_max' => '',
            'deadline' => '',
            'status' => 'pending_approval',
            'description' => '',
            'responsibilities' => '',
            'requirements' => '',
            'updateUrl' => '',
        ];

        if ($failedEditPostId && $errors->any()) {
            $initialEditPost = [
                'id' => $failedEditPostId,
                'title' => old('title', ''),
                'category' => old('category', 'General IT'),
                'opportunity_type' => old('opportunity_type', 'internship'),
                'type' => old('type', 'on_site'),
                'experience_level' => old('experience_level', 'entry_level'),
                'location' => old('location', ''),
                'duration_weeks' => old('duration_weeks', 12),
                'slots' => old('slots', 1),
                'stipend' => old('stipend', ''),
                'salary_max' => old('salary_max', ''),
                'deadline' => old('deadline', ''),
                'status' => old('status', 'pending_approval'),
                'description' => old('description', ''),
                'responsibilities' => old('responsibilities', ''),
                'requirements' => old('requirements', ''),
                'updateUrl' => route('company.posts.update', $failedEditPostId),
            ];
        } elseif ($autoEditPost) {
            $initialEditPost = [
                'id' => $autoEditPost->id,
                'title' => $autoEditPost->title,
                'category' => $autoEditPost->category ?? 'General IT',
                'opportunity_type' => $autoEditPost->opportunity_type ?? 'internship',
                'type' => $autoEditPost->type ?? 'on_site',
                'experience_level' => $autoEditPost->experience_level ?? 'entry_level',
                'location' => $autoEditPost->location,
                'duration_weeks' => $autoEditPost->duration_weeks,
                'slots' => $autoEditPost->slots,
                'stipend' => $autoEditPost->stipend ? (string)(float)$autoEditPost->stipend : '',
                'salary_max' => $autoEditPost->salary_max ? (string)(float)$autoEditPost->salary_max : '',
                'deadline' => \Carbon\Carbon::parse($autoEditPost->deadline)->format('Y-m-d'),
                'status' => $autoEditPost->status,
                'description' => $autoEditPost->description,
                'responsibilities' => $autoEditPost->responsibilities ?? '',
                'requirements' => $autoEditPost->requirements ?? '',
                'updateUrl' => route('company.posts.update', $autoEditPost),
            ];
        }
    @endphp

    @php
        $currentStatus = request('status', 'all');
        $currentOppType = request('opportunity_type', 'all');
        $currentWorkMode = request('work_mode', 'all');
        $currentSort = request('sort', 'latest');
        $currentSearch = request('search', '');

        $tabs = [
            'all'              => ['label' => 'All', 'count' => $counts['all'] ?? $posts->total(), 'dot' => 'bg-slate-400'],
            'approved'         => ['label' => 'Published', 'count' => $counts['approved'] ?? 0, 'dot' => 'bg-emerald-500'],
            'pending_approval' => ['label' => 'Pending', 'count' => $counts['pending_approval'] ?? 0, 'dot' => 'bg-amber-500'],
            'draft'            => ['label' => 'Drafts', 'count' => $counts['draft'] ?? 0, 'dot' => 'bg-slate-400'],
            'closed'           => ['label' => 'Closed', 'count' => $counts['closed'] ?? 0, 'dot' => 'bg-rose-400'],
        ];
        if (($counts['rejected'] ?? 0) > 0) {
            $tabs['rejected'] = ['label' => 'Rejected', 'count' => $counts['rejected'], 'dot' => 'bg-rose-600'];
        }
    @endphp

    <div class="space-y-5"
         x-data="listingsManager({{ $initialCreateOpen ? 'true' : 'false' }}, {{ $initialEditOpen ? 'true' : 'false' }}, @js($initialEditPost))"
         @open-create-modal.window="createModalOpen = true">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900">Manage Opportunities</h2>
                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">Create, track, and manage internship and job postings published to students.</p>
            </div>
            <button type="button" 
                    @click="createModalOpen = true" 
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-[#059669] hover:bg-[#047857] text-white shadow-xs transition-colors cursor-pointer shrink-0 self-start sm:self-auto">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Post Opportunity</span>
            </button>
        </div>

        <!-- Filter Toolbar: Status Tabs + Search + Dropdowns + Opportunity Type -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-3.5 sm:p-4 space-y-3.5">
            
            <!-- Top Row: Status Tabs + Opportunity Type Segmented Pills -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-2.5 border-b border-slate-100">
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
                    @foreach ($tabs as $tabKey => $tab)
                        @php
                            $isActive = $currentStatus === $tabKey;
                            $tabUrl = route('company.posts.index', array_filter([
                                'status'           => $tabKey === 'all' ? null : $tabKey,
                                'opportunity_type' => $currentOppType === 'all' ? null : $currentOppType,
                                'work_mode'        => $currentWorkMode === 'all' ? null : $currentWorkMode,
                                'sort'             => $currentSort === 'latest' ? null : $currentSort,
                                'search'           => $currentSearch ?: null,
                            ]));
                        @endphp
                        <a href="{{ $tabUrl }}" 
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ $isActive ? 'bg-[#D1FAE5] text-[#065F46]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-[#059669]' : $tab['dot'] }}"></span>
                            <span>{{ $tab['label'] }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $isActive ? 'bg-white text-[#065F46]' : 'bg-slate-100 text-slate-600' }}">
                                {{ $tab['count'] }}
                            </span>
                        </a>
                    @endforeach
                </div>

                <!-- Opportunity Type Segmented Pills -->
                <div class="flex items-center gap-1 bg-slate-100/80 p-1 rounded-xl self-start lg:self-auto shrink-0 border border-slate-200/60">
                    @php
                        $oppOptions = [
                            'all'        => ['label' => 'All Types', 'icon' => ''],
                            'internship' => ['label' => 'Internships', 'icon' => '🎓'],
                            'job'        => ['label' => 'Jobs', 'icon' => '💼'],
                        ];
                    @endphp
                    @foreach ($oppOptions as $oppKey => $opp)
                        @php
                            $isOppActive = $currentOppType === $oppKey;
                            $oppUrl = route('company.posts.index', array_filter([
                                'status'           => $currentStatus === 'all' ? null : $currentStatus,
                                'opportunity_type' => $oppKey === 'all' ? null : $oppKey,
                                'work_mode'        => $currentWorkMode === 'all' ? null : $currentWorkMode,
                                'sort'             => $currentSort === 'latest' ? null : $currentSort,
                                'search'           => $currentSearch ?: null,
                            ]));
                        @endphp
                        <a href="{{ $oppUrl }}" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $isOppActive ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                            @if ($opp['icon'])
                                <span>{{ $opp['icon'] }}</span>
                            @endif
                            <span>{{ $opp['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Bottom Row: Search Input + Work Mode Dropdown + Sort Dropdown -->
            <form action="{{ route('company.posts.index') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                @if ($currentStatus !== 'all')
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                @if ($currentOppType !== 'all')
                    <input type="hidden" name="opportunity_type" value="{{ $currentOppType }}">
                @endif

                <!-- Search Input -->
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ $currentSearch }}" 
                           placeholder="Search by title, location, or category..." 
                           class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/60 focus:bg-white text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all">
                </div>

                <!-- Custom Dropdowns & Filter Buttons -->
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <!-- Work Mode Filter Dropdown -->
                    <div class="relative"
                         x-data="{
                             open: false,
                             modeVal: '{{ $currentWorkMode === 'all' ? '' : $currentWorkMode }}',
                             options: [
                                 { value: '', label: 'All Work Modes', dot: 'bg-slate-400' },
                                 { value: 'on_site', label: 'On-Site (Office)', dot: 'bg-emerald-500 ring-2 ring-emerald-100' },
                                 { value: 'remote', label: 'Remote (Home)', dot: 'bg-indigo-500 ring-2 ring-indigo-100' },
                                 { value: 'hybrid', label: 'Hybrid (Flexible)', dot: 'bg-amber-500 ring-2 ring-amber-100' }
                             ],
                             get currentOption() {
                                 return this.options.find(o => o.value === this.modeVal) || this.options[0];
                             },
                             selectMode(val) {
                                 this.modeVal = val;
                                 this.open = false;
                                 $nextTick(() => { $el.closest('form').submit(); });
                             }
                         }">
                        <input type="hidden" name="work_mode" :value="modeVal">
                        <button type="button"
                                @click="open = !open"
                                class="inline-flex items-center justify-between gap-2.5 py-2 pl-3 pr-2.5 text-xs rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] font-medium text-slate-700 transition-all cursor-pointer shadow-2xs min-w-[155px]">
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full shrink-0" :class="currentOption.dot"></span>
                                <span class="font-semibold text-slate-800" x-text="currentOption.label"></span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200"
                               :class="open ? 'rotate-180 text-[#059669]' : ''"></i>
                        </button>

                        <div x-show="open"
                             x-cloak
                             @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                             class="absolute left-0 top-full z-40 mt-1.5 w-48 rounded-2xl bg-white border border-slate-200 shadow-xl p-1.5 space-y-0.5"
                             style="display: none;">
                            <template x-for="item in options" :key="item.value">
                                <button type="button"
                                        @click="selectMode(item.value)"
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                        :class="modeVal === item.value ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="item.dot"></span>
                                        <span x-text="item.label"></span>
                                    </div>
                                    <i x-show="modeVal === item.value" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Sort By Filter Dropdown -->
                    <div class="relative"
                         x-data="{
                             open: false,
                             sortVal: '{{ $currentSort }}',
                             options: [
                                 { value: 'latest', label: 'Newest First', icon: 'fa-regular fa-clock' },
                                 { value: 'applicants_desc', label: 'Most Applicants', icon: 'fa-solid fa-users' },
                                 { value: 'closing_soon', label: 'Closing Soon', icon: 'fa-solid fa-hourglass-half' }
                             ],
                             get currentOption() {
                                 return this.options.find(o => o.value === this.sortVal) || this.options[0];
                             },
                             selectSort(val) {
                                 this.sortVal = val;
                                 this.open = false;
                                 $nextTick(() => { $el.closest('form').submit(); });
                             }
                         }">
                        <input type="hidden" name="sort" :value="sortVal">
                        <button type="button"
                                @click="open = !open"
                                class="inline-flex items-center justify-between gap-2.5 py-2 pl-3 pr-2.5 text-xs rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] font-medium text-slate-700 transition-all cursor-pointer shadow-2xs min-w-[155px]">
                            <span class="flex items-center gap-2">
                                <i class="text-[11px] text-slate-400" :class="currentOption.icon"></i>
                                <span class="font-semibold text-slate-800" x-text="currentOption.label"></span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200"
                               :class="open ? 'rotate-180 text-[#059669]' : ''"></i>
                        </button>

                        <div x-show="open"
                             x-cloak
                             @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                             class="absolute right-0 sm:left-0 top-full z-40 mt-1.5 w-48 rounded-2xl bg-white border border-slate-200 shadow-xl p-1.5 space-y-0.5"
                             style="display: none;">
                            <template x-for="item in options" :key="item.value">
                                <button type="button"
                                        @click="selectSort(item.value)"
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                        :class="sortVal === item.value ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                    <div class="flex items-center gap-2.5">
                                        <i class="text-[11px] w-3.5 text-center" :class="item.icon"></i>
                                        <span x-text="item.label"></span>
                                    </div>
                                    <i x-show="sortVal === item.value" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    <button type="submit" class="py-2 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors cursor-pointer shadow-2xs">
                        Filter
                    </button>

                    @if ($currentSearch || $currentWorkMode !== 'all' || $currentSort !== 'latest')
                        <a href="{{ route('company.posts.index', array_filter(['status' => $currentStatus === 'all' ? null : $currentStatus, 'opportunity_type' => $currentOppType === 'all' ? null : $currentOppType])) }}"
                           class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-colors"
                           title="Clear Filters">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Opportunities Table Card -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            @if ($posts->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50/90 border-b border-slate-100">
                            <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-6">Opportunity</th>
                                <th class="py-3.5 px-4">Compensation</th>
                                <th class="py-3.5 px-4">Applicants</th>
                                <th class="py-3.5 px-4">Status & Deadline</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($posts as $post)
                                @php
                                    $deadline = \Carbon\Carbon::parse($post->deadline);
                                    $isExpired = $deadline->isPast();
                                    $isExpiringSoon = !$isExpired && $deadline->diffInDays(now()) <= 5;
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition-colors group">
                                    <!-- 1. Opportunity Details -->
                                    <td class="py-3.5 px-6 max-w-md">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-extrabold text-slate-900 group-hover:text-emerald-700 transition-colors">
                                                {{ $post->title }}
                                            </span>
                                            @if ($post->isInternship())
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                                    <span>🎓</span> Internship
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                                                    <span>💼</span> {{ $post->opportunity_type_label }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[11px] text-slate-500 mt-1.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold border border-slate-200/70">
                                                {{ $post->category ?? 'General IT' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <i class="fa-solid fa-location-dot text-slate-400"></i>
                                                {{ $post->location }}
                                            </span>
                                            <span>•</span>
                                            <span class="capitalize">{{ str_replace('_', '-', $post->type ?? 'on_site') }}</span>
                                            <span>•</span>
                                            <span>
                                                @if ($post->isInternship())
                                                    {{ $post->duration_weeks ?? 12 }} wks
                                                @else
                                                    {{ $post->experience_level_label ?? 'All Levels' }}
                                                @endif
                                                ({{ $post->slots }} {{ Str::plural('slot', $post->slots) }})
                                            </span>
                                        </div>
                                    </td>

                                    <!-- 2. Compensation -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="font-bold text-emerald-700 text-xs">
                                            {{ $post->compensation_display }}
                                        </span>
                                    </td>

                                    <!-- 3. Applicants & Slots Filled Progress -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @php
                                            $hiredCount = (int) ($post->accepted_count ?? 0);
                                            $totalSlots = max(1, (int) ($post->slots ?? 1));
                                            $fillPct = min(100, round(($hiredCount / $totalSlots) * 100));
                                        @endphp
                                        <div class="flex flex-col items-start gap-1.5">
                                            <a href="{{ route('company.applicants.index', ['post_id' => $post->id]) }}" 
                                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold transition-all {{ $post->applications_count > 0 ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/80' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 border border-slate-200/80' }}">
                                                <i class="fa-solid fa-users text-[10px]"></i>
                                                <span>{{ $post->applications_count }} {{ Str::plural('Candidate', $post->applications_count) }}</span>
                                            </a>
                                            <div class="flex items-center gap-2 w-full max-w-[125px]">
                                                <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full {{ $hiredCount >= $totalSlots ? 'bg-emerald-600' : 'bg-emerald-500' }}" style="width: {{ $fillPct }}%"></div>
                                                </div>
                                                <span class="text-[10px] font-bold {{ $hiredCount > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                                                    {{ $hiredCount }}/{{ $totalSlots }} Filled
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 4. Status & Deadline -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex flex-col items-start gap-1.5">
                                            <x-status-badge :status="$post->status" />
                                            @if ($isExpired)
                                                <span class="text-[11px] font-bold text-rose-600 inline-flex items-center gap-1">
                                                    <i class="fa-solid fa-circle-exclamation text-[10px]"></i>
                                                    Expired {{ $deadline->format('M d, Y') }}
                                                </span>
                                            @elseif ($isExpiringSoon)
                                                <span class="text-[11px] font-bold text-amber-600 inline-flex items-center gap-1">
                                                    <i class="fa-regular fa-clock text-[10px]"></i>
                                                    Due {{ $deadline->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="text-[11px] text-slate-500 font-medium">
                                                    Due {{ $deadline->format('M d, Y') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- 5. Actions -->
                                    <td class="py-3.5 px-6 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            {{-- Quick Submit Draft for Approval Button --}}
                                            @if ($post->status === 'draft')
                                                <form action="{{ route('company.posts.submit', $post) }}" method="POST" class="inline-flex m-0">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 text-xs font-bold transition-all cursor-pointer shadow-2xs"
                                                            title="Submit this draft for university admin moderation">
                                                        <i class="fa-solid fa-paper-plane text-[10px] text-emerald-600"></i>
                                                        <span>Submit</span>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Preview Post Detail Button --}}
                                            <button type="button"
                                                    @click="openPreviewModal({
                                                        id: {{ $post->id }},
                                                        title: @js($post->title),
                                                        category: @js($post->category ?? 'General IT'),
                                                        is_internship: @js($post->isInternship()),
                                                        opportunity_type_label: @js($post->opportunity_type_label),
                                                        type_label: @js(ucfirst(str_replace('_', '-', $post->type ?? 'on_site'))),
                                                        experience_level_label: @js($post->experience_level_label ?? 'All Levels'),
                                                        location: @js($post->location),
                                                        duration_weeks: @js($post->duration_weeks ?? 12),
                                                        slots: {{ $post->slots }},
                                                        hired_count: {{ $hiredCount }},
                                                        applications_count: {{ $post->applications_count }},
                                                        compensation: @js($post->compensation_display),
                                                        deadline_formatted: @js($deadline->format('M d, Y')),
                                                        status: @js($post->status),
                                                        description: @js($post->description),
                                                        responsibilities: @js($post->responsibilities ?? ''),
                                                        requirements: @js($post->requirements ?? ''),
                                                        applicants_url: @js(route('company.applicants.index', ['post_id' => $post->id]))
                                                    })"
                                                    class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors cursor-pointer border border-slate-200/80"
                                                    title="Preview Listing Details">
                                                <i class="fa-regular fa-eye text-xs"></i>
                                            </button>

                                            {{-- Quick Close / Reopen Button --}}
                                            @if (in_array($post->status, ['approved', 'closed'], true))
                                                <form action="{{ route('company.posts.toggle-close', $post) }}" method="POST" class="inline-flex m-0">
                                                    @csrf
                                                    <button type="submit"
                                                            class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors cursor-pointer border {{ $post->status === 'closed' ? 'text-emerald-600 bg-emerald-50 border-emerald-200 hover:bg-emerald-100' : 'text-slate-500 hover:text-amber-600 hover:bg-amber-50 border-slate-200/80' }}"
                                                            title="{{ $post->status === 'closed' ? 'Reopen Listing' : 'Close Listing to New Applicants' }}">
                                                        <i class="fa-solid {{ $post->status === 'closed' ? 'fa-lock-open' : 'fa-lock' }} text-xs"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Duplicate / Clone Post Button --}}
                                            <form action="{{ route('company.posts.duplicate', $post) }}" method="POST" class="inline-flex m-0">
                                                @csrf
                                                <button type="submit" 
                                                        class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors cursor-pointer border border-slate-200/80"
                                                        title="Duplicate Listing as Draft">
                                                    <i class="fa-solid fa-copy text-xs"></i>
                                                </button>
                                            </form>

                                            {{-- Edit Post Button (All fields passed safely) --}}
                                            <button type="button" 
                                                    @click="openEditModal({
                                                        id: {{ $post->id }},
                                                        title: @js($post->title),
                                                        category: @js($post->category ?? 'General IT'),
                                                        opportunity_type: @js($post->opportunity_type ?? 'internship'),
                                                        type: @js($post->type ?? 'on_site'),
                                                        experience_level: @js($post->experience_level ?? 'entry_level'),
                                                        location: @js($post->location),
                                                        duration_weeks: @js($post->duration_weeks),
                                                        slots: {{ $post->slots }},
                                                        stipend: @js($post->stipend ? (string)(float)$post->stipend : ''),
                                                        salary_max: @js($post->salary_max ? (string)(float)$post->salary_max : ''),
                                                        deadline: @js($deadline->format('Y-m-d')),
                                                        status: @js($post->status),
                                                        description: @js($post->description),
                                                        responsibilities: @js($post->responsibilities ?? ''),
                                                        requirements: @js($post->requirements ?? ''),
                                                        updateUrl: @js(route('company.posts.update', $post))
                                                    })"
                                                    class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors cursor-pointer border border-slate-200/80"
                                                    title="Edit Listing">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>

                                            {{-- Delete / Archive Post Button --}}
                                            <form action="{{ route('company.posts.destroy', $post) }}" method="POST" class="inline-flex m-0" onsubmit="return confirm('Archive this opportunity post?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer border border-slate-200/80"
                                                        title="Archive Post">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($posts->hasPages())
                    <div class="p-4 border-t border-slate-100 bg-white">
                        {{ $posts->links() }}
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-16 px-4 text-center text-slate-600 space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center">
                        <i class="fa-solid fa-folder-open text-2xl"></i>
                    </div>
                    @if ($currentSearch || $currentStatus !== 'all' || $currentOppType !== 'all')
                        <h3 class="text-base font-bold text-slate-800">No matching opportunities found</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">Try adjusting your search keywords or clearing your active status/type filters.</p>
                        <a href="{{ route('company.posts.index') }}" 
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                            <i class="fa-solid fa-rotate-left text-[10px]"></i>
                            <span>Reset Filters</span>
                        </a>
                    @else
                        <h3 class="text-base font-bold text-slate-800">No opportunities created yet</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">Create an internship or job listing to begin receiving applications from university students.</p>
                        <button type="button" 
                                @click="createModalOpen = true" 
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 active:scale-95 transition-all shadow-sm shadow-emerald-600/20 cursor-pointer">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>Post Your First Opportunity</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Post Internship (Create) Modal --}}
        @include('company.posts.modal-create')

        {{-- Edit Internship Modal --}}
        @include('company.posts.modal-edit')

        {{-- Preview Opportunity Detail Modal --}}
        <div x-show="previewModalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
             @keydown.escape.window="previewModalOpen = false"
             style="display: none;">
            <div @click.away="previewModalOpen = false"
                 class="bg-white rounded-3xl max-w-2xl w-full max-h-[88vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-4 bg-slate-50/50">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-lg font-extrabold text-slate-900" x-text="previewPost.title"></h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                  :class="previewPost.is_internship ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200'"
                                  x-text="previewPost.opportunity_type_label"></span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span class="font-semibold text-slate-700" x-text="previewPost.category"></span>
                            <span>•</span>
                            <span x-text="previewPost.location"></span>
                            <span>•</span>
                            <span x-text="previewPost.type_label"></span>
                            <span>•</span>
                            <span class="font-bold text-[#059669]" x-text="previewPost.compensation"></span>
                        </div>
                    </div>
                    <button type="button" @click="previewModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-5 text-xs sm:text-sm text-slate-700">
                    <!-- Quick Metrics Row -->
                    <div class="grid grid-cols-3 gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-100 text-center">
                        <div>
                            <span class="text-[11px] text-slate-400 block font-semibold">Applicants</span>
                            <span class="text-sm font-extrabold text-slate-900" x-text="previewPost.applications_count + ' Candidates'"></span>
                        </div>
                        <div class="border-x border-slate-200/70">
                            <span class="text-[11px] text-slate-400 block font-semibold">Slots Filled</span>
                            <span class="text-sm font-extrabold text-[#059669]" x-text="previewPost.hired_count + ' / ' + previewPost.slots"></span>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-400 block font-semibold">Deadline</span>
                            <span class="text-sm font-extrabold text-slate-900" x-text="previewPost.deadline_formatted"></span>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 mb-1.5">Description</h4>
                        <p class="whitespace-pre-line leading-relaxed text-slate-600" x-text="previewPost.description"></p>
                    </div>

                    <template x-if="previewPost.responsibilities">
                        <div>
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 mb-1.5">Responsibilities</h4>
                            <p class="whitespace-pre-line leading-relaxed text-slate-600" x-text="previewPost.responsibilities"></p>
                        </div>
                    </template>

                    <template x-if="previewPost.requirements">
                        <div>
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 mb-1.5">Qualifications & Requirements</h4>
                            <p class="whitespace-pre-line leading-relaxed text-slate-600" x-text="previewPost.requirements"></p>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between gap-3">
                    <a :href="previewPost.applicants_url"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#059669] hover:bg-[#047857] text-white text-xs font-bold transition-colors">
                        <i class="fa-solid fa-users text-xs"></i>
                        <span>View Applicants</span>
                    </a>
                    <button type="button" @click="previewModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold transition-colors cursor-pointer">
                        Close Preview
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        (function() {
            const categories = [
                { icon: '🖥️', label: 'IT Support & Helpdesk' },
                { icon: '🌐', label: 'Web Development' },
                { icon: '📱', label: 'Mobile Development' },
                { icon: '☕', label: 'Java / Spring Boot' },
                { icon: '⚙️', label: 'Backend Engineering' },
                { icon: '🎨', label: 'Frontend Engineering' },
                { icon: '🔗', label: 'Full-Stack Development' },
                { icon: '📊', label: 'Data Science & Analytics' },
                { icon: '🔒', label: 'Cybersecurity' },
                { icon: '☁️', label: 'DevOps & Cloud' },
                { icon: '✏️', label: 'UI/UX Design' },
                { icon: '🔌', label: 'Network Engineering' },
                { icon: '🗄️', label: 'Database Administration' },
                { icon: '🧪', label: 'Quality Assurance' },
                { icon: '📋', label: 'Business Analysis' },
                { icon: '💡', label: 'General IT' },
            ];

            function registerListingsManager() {
                if (window.Alpine && !Alpine.data('listingsManager')) {
                    Alpine.data('listingsManager', (initialCreateOpen, initialEditOpen, initialEditPost) => ({
                        createModalOpen: initialCreateOpen,
                        editModalOpen: initialEditOpen,
                        editPost: initialEditPost || {},
                        previewModalOpen: false,
                        previewPost: {},
                        openPreviewModal(post) {
                            this.previewPost = { ...post };
                            this.previewModalOpen = true;
                        },
                        categoryDropdownOpen: false,
                        categorySearch: '',
                        editCategory: (initialEditPost && initialEditPost.category) ? initialEditPost.category : 'General IT',
                        editCategoryIcon: '💡',
                        statusDropdownOpen: false,
                        editStatus: (initialEditPost && initialEditPost.status) ? initialEditPost.status : 'pending_approval',
                        editStatusIcon: '⏳',
                        editStatusLabel: 'Pending Approval',
                        categories: categories,
                        workTypes: [
                            { value: 'on_site', icon: '🏢', label: 'On-Site (Office)', desc: 'Work at physical office' },
                            { value: 'remote', icon: '🏠', label: 'Remote (Home)', desc: '100% work from home' },
                            { value: 'hybrid', icon: '🔄', label: 'Hybrid (Flexible)', desc: 'Split office & remote' },
                        ],
                        expLevels: [
                            { value: 'no_experience', icon: '🌱', label: 'No Experience', desc: 'Any student / beginner' },
                            { value: 'entry_level', icon: '🎓', label: 'Entry Level', desc: 'Fresh graduate (0-1 yr)' },
                            { value: 'junior', icon: '⚡', label: 'Junior', desc: '1 - 2 years experience' },
                            { value: 'mid_level', icon: '🚀', label: 'Mid-Level', desc: '3+ years experience' },
                        ],
                        workTypeDropdownOpen: false,
                        expLevelDropdownOpen: false,

                        get selectedEditWorkType() {
                            const val = (this.editPost && this.editPost.type) ? this.editPost.type : 'on_site';
                            return this.workTypes.find(w => w.value === val) || this.workTypes[0];
                        },

                        selectEditWorkType(item) {
                            this.editPost.type = item.value;
                            this.workTypeDropdownOpen = false;
                        },

                        get selectedEditExpLevel() {
                            const val = (this.editPost && this.editPost.experience_level) ? this.editPost.experience_level : 'entry_level';
                            return this.expLevels.find(e => e.value === val) || this.expLevels[1];
                        },

                        selectEditExpLevel(item) {
                            this.editPost.experience_level = item.value;
                            this.expLevelDropdownOpen = false;
                        },

                        get availableStatuses() {
                            // If the listing was ALREADY approved by admin, employer can keep it approved, or close/draft it
                            if (this.editPost && this.editPost.status === 'approved') {
                                return [
                                    { value: 'approved',         icon: '✅', label: 'Approved & Published (Keep Active)' },
                                    { value: 'closed',           icon: '🔒', label: 'Closed (Stop Applications)' },
                                    { value: 'draft',            icon: '📝', label: 'Draft (Unpublish)' },
                                    { value: 'pending_approval', icon: '⏳', label: 'Submit for Admin Re-approval' },
                                ];
                            }

                            // For unapproved postings (pending_approval, draft, rejected, closed):
                            // "Approved" and "Rejected" can NEVER be selected by company (reserved for Admin only)
                            return [
                                { value: 'pending_approval', icon: '⏳', label: 'Submit for Admin Approval' },
                                { value: 'draft',            icon: '📝', label: 'Save as Draft' },
                                { value: 'closed',           icon: '🔒', label: 'Closed' },
                            ];
                        },

                        syncStatusVisuals() {
                            const matchedStat = this.availableStatuses.find(s => s.value === this.editStatus);
                            if (matchedStat) {
                                this.editStatusIcon = matchedStat.icon;
                                this.editStatusLabel = matchedStat.label;
                            } else if (this.editStatus === 'rejected') {
                                this.editStatusIcon = '❌';
                                this.editStatusLabel = 'Rejected (Select Pending Approval to resubmit)';
                            } else if (this.editStatus === 'approved') {
                                this.editStatusIcon = '✅';
                                this.editStatusLabel = 'Approved & Published';
                            } else {
                                this.editStatusIcon = '⏳';
                                this.editStatusLabel = 'Pending Approval';
                            }
                        },

                        init() {
                            const matchedCat = this.categories.find(c => c.label === this.editCategory);
                            if (matchedCat) this.editCategoryIcon = matchedCat.icon;

                            this.syncStatusVisuals();
                        },

                        get filteredEditCategories() {
                            if (!this.categorySearch) return this.categories;
                            return this.categories.filter(c => c.label.toLowerCase().includes(this.categorySearch.toLowerCase()));
                        },

                        selectEditCategory(cat) {
                            this.editCategory = cat.label;
                            this.editCategoryIcon = cat.icon;
                            this.editPost.category = cat.label;
                            this.categoryDropdownOpen = false;
                            this.categorySearch = '';
                        },

                        selectEditStatus(s) {
                            this.editStatus = s.value;
                            this.editStatusIcon = s.icon;
                            this.editStatusLabel = s.label;
                            this.editPost.status = s.value;
                            this.statusDropdownOpen = false;
                        },

                        openEditModal(post) {
                            this.editPost = { ...post };
                            this.editCategory = post.category || 'General IT';
                            const matchedCat = this.categories.find(c => c.label === this.editCategory);
                            this.editCategoryIcon = matchedCat ? matchedCat.icon : '💡';
                            this.categorySearch = '';
                            this.categoryDropdownOpen = false;

                            this.editStatus = post.status || 'pending_approval';
                            this.syncStatusVisuals();
                            this.statusDropdownOpen = false;
                            this.workTypeDropdownOpen = false;
                            this.expLevelDropdownOpen = false;

                            this.editModalOpen = true;
                        }
                    }));
                }
            }

            if (window.Alpine) {
                registerListingsManager();
            } else {
                document.addEventListener('alpine:init', registerListingsManager);
            }
        })();
    </script>
</x-layout>
