<x-layout>
    @php
        $queryParams = array_filter([
            'post_id'   => $postId !== 'all' ? $postId : null,
            'status'    => $status !== 'all' ? $status : null,
            'date_from' => $dateFrom ?: null,
            'date_to'   => $dateTo ?: null,
            'search'    => $search ?: null,
        ]);

        $statusOptions = [
            'all'          => 'All Statuses',
            'pending'      => 'Pending',
            'under_review' => 'Under Review',
            'shortlisted'  => 'Shortlisted',
            'interviewed'  => 'Interviewed',
            'accepted'     => 'Accepted / Hired',
            'rejected'     => 'Rejected',
            'withdrawn'    => 'Withdrawn',
        ];

        $selectedPostLabel = 'All Job Listings';
        if ($postId !== 'all') {
            $foundPost = $companyPosts->firstWhere('id', (int) $postId);
            if ($foundPost) {
                $selectedPostLabel = $foundPost->title;
            }
        }
    @endphp

    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Hiring Reports</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Analyze applicant pipelines, filter by job or date range, and export official hiring records.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                <a href="{{ route('company.reports.exportCsv', $queryParams) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 hover:border-[#059669]/40 hover:bg-emerald-50/50 text-slate-700 hover:text-[#059669] text-xs font-semibold shadow-xs transition-all">
                    <i class="fa-solid fa-file-csv text-emerald-600 text-sm"></i>
                    <span>Export CSV</span>
                </a>
                <a href="{{ route('company.reports.print', $queryParams) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white text-xs font-semibold shadow-xs transition-all">
                    <i class="fa-solid fa-print text-xs"></i>
                    <span>Print Report</span>
                </a>
            </div>
        </div>

        <!-- 4 KPI Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Applicants</span>
                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 border border-sky-200/70 flex items-center justify-center">
                        <i class="fa-solid fa-users text-xs"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-slate-900 mt-3">{{ $stats['total_applications'] }}</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">All-time submissions</p>
            </div>

            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Shortlisted &amp; Interview</span>
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200/70 flex items-center justify-center">
                        <i class="fa-solid fa-calendar-check text-xs"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-indigo-700 mt-3">{{ $stats['total_shortlisted'] }}</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Qualified candidates</p>
            </div>

            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Hired / Accepted</span>
                    <div class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#059669] border border-[#A7F3D0]/70 flex items-center justify-center">
                        <i class="fa-solid fa-award text-xs"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-[#059669] mt-3">{{ $stats['total_accepted'] }}</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Confirmed hires</p>
            </div>

            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Hire Conversion</span>
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/70 flex items-center justify-center">
                        <i class="fa-solid fa-chart-line text-xs"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-slate-900 mt-3">{{ $stats['hire_rate'] }}%</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Accepted / Total</p>
            </div>
        </div>

        <!-- Filters Card (Custom Styled Dropdowns) -->
        <form action="{{ route('company.reports.index') }}" method="GET"
              class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4"
              x-data="{
                  postOpen: false,
                  postId: @js((string) $postId),
                  postLabel: @js($selectedPostLabel),
                  statusOpen: false,
                  statusVal: @js((string) $status),
                  statusOptions: @js($statusOptions),
              }">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">

                <!-- 1. Job Listing Custom Dropdown -->
                <div class="relative" @click.outside="postOpen = false">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Job Listing</label>
                    <input type="hidden" name="post_id" :value="postId">
                    <button type="button"
                            @click="postOpen = !postOpen"
                            class="relative w-full pl-3.5 pr-8 py-2.5 rounded-xl border bg-white text-xs text-left font-medium transition-all flex items-center justify-between cursor-pointer"
                            :class="postOpen ? 'border-[#059669] ring-2 ring-[#059669]/20' : 'border-slate-200 hover:border-slate-300'">
                        <span class="truncate text-slate-800" x-text="postLabel"></span>
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="postOpen ? 'rotate-180 text-[#059669]' : ''"></i>
                        </span>
                    </button>

                    <div x-show="postOpen" x-transition.opacity.duration.150ms style="display: none;"
                         class="absolute z-30 mt-1.5 w-full bg-white rounded-xl border border-slate-200 shadow-lg py-1.5 max-h-60 overflow-y-auto">
                        <button type="button"
                                @click="postId = 'all'; postLabel = 'All Job Listings'; postOpen = false;"
                                class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between cursor-pointer"
                                :class="postId === 'all' ? 'bg-[#D1FAE5]/60 text-[#065F46] font-semibold' : 'text-slate-700 hover:bg-slate-50'">
                            <span>All Job Listings</span>
                            <i x-show="postId === 'all'" class="fa-solid fa-check text-[#059669] text-[10px]"></i>
                        </button>
                        @foreach ($companyPosts as $cp)
                            <button type="button"
                                    @click="postId = '{{ $cp->id }}'; postLabel = @js($cp->title); postOpen = false;"
                                    class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between cursor-pointer"
                                    :class="postId === '{{ $cp->id }}' ? 'bg-[#D1FAE5]/60 text-[#065F46] font-semibold' : 'text-slate-700 hover:bg-slate-50'">
                                <span class="truncate pr-2">{{ $cp->title }}</span>
                                <i x-show="postId === '{{ $cp->id }}'" class="fa-solid fa-check text-[#059669] text-[10px] shrink-0"></i>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- 2. Status Custom Dropdown -->
                <div class="relative" @click.outside="statusOpen = false">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                    <input type="hidden" name="status" :value="statusVal">
                    <button type="button"
                            @click="statusOpen = !statusOpen"
                            class="relative w-full pl-3.5 pr-8 py-2.5 rounded-xl border bg-white text-xs text-left font-medium transition-all flex items-center justify-between cursor-pointer"
                            :class="statusOpen ? 'border-[#059669] ring-2 ring-[#059669]/20' : 'border-slate-200 hover:border-slate-300'">
                        <span class="truncate text-slate-800" x-text="statusOptions[statusVal] || 'All Statuses'"></span>
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="statusOpen ? 'rotate-180 text-[#059669]' : ''"></i>
                        </span>
                    </button>

                    <div x-show="statusOpen" x-transition.opacity.duration.150ms style="display: none;"
                         class="absolute z-30 mt-1.5 w-full bg-white rounded-xl border border-slate-200 shadow-lg py-1.5 max-h-60 overflow-y-auto">
                        <template x-for="(label, key) in statusOptions" :key="key">
                            <button type="button"
                                    @click="statusVal = key; statusOpen = false;"
                                    class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between cursor-pointer"
                                    :class="statusVal === key ? 'bg-[#D1FAE5]/60 text-[#065F46] font-semibold' : 'text-slate-700 hover:bg-slate-50'">
                                <span x-text="label"></span>
                                <i x-show="statusVal === key" class="fa-solid fa-check text-[#059669] text-[10px]"></i>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 3. Applied From (Custom Styled Calendar Dropdown) -->
                <div class="relative" x-data="reportDatePicker(@js($dateFrom), 'Select from date')" @click.outside="open = false">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">From Date</label>
                    <input type="hidden" name="date_from" :value="value">
                    <button type="button"
                            @click="open = !open"
                            class="relative w-full pl-9 pr-8 py-2.5 rounded-xl border bg-white text-xs text-left font-medium transition-all flex items-center justify-between cursor-pointer"
                            :class="open ? 'border-[#059669] ring-2 ring-[#059669]/20' : 'border-slate-200 hover:border-slate-300'">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#059669]">
                            <i class="fa-solid fa-calendar-days text-xs"></i>
                        </span>
                        <span class="truncate" :class="value ? 'text-slate-900 font-semibold' : 'text-slate-400'" x-text="displayLabel"></span>
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="open ? 'rotate-180 text-[#059669]' : ''"></i>
                        </span>
                    </button>

                    <!-- Calendar Dropdown -->
                    <div x-show="open" x-cloak x-transition.opacity.duration.150ms style="display: none;"
                         class="absolute left-0 top-full z-40 mt-1.5 w-68 rounded-2xl bg-white border border-slate-200 shadow-2xl p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <button type="button" @click="prevMonth()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                                <i class="fa-solid fa-chevron-left text-[10px]"></i>
                            </button>
                            <span class="text-xs font-bold text-slate-900" x-text="monthNames[viewMonth] + ' ' + viewYear"></span>
                            <button type="button" @click="nextMonth()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-7 text-center text-[10px] font-bold text-slate-400">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>

                        <div class="grid grid-cols-7 gap-1 text-center">
                            <template x-for="(cell, idx) in calendarCells" :key="idx">
                                <button type="button"
                                        :disabled="!cell.day"
                                        @click="cell.day && selectDay(cell.day)"
                                        class="h-7 w-7 mx-auto rounded-lg text-[11px] font-medium flex items-center justify-center transition-colors"
                                        :class="!cell.day ? 'invisible' : (isSelected(cell.day) ? 'bg-[#059669] text-white font-bold shadow-xs cursor-pointer' : (isToday(cell.day) ? 'bg-emerald-50 text-[#059669] font-bold border border-emerald-200 cursor-pointer' : 'text-slate-700 hover:bg-slate-100 cursor-pointer'))"
                                        x-text="cell.day || ''"></button>
                            </template>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-[11px]">
                            <button type="button" @click="clearDate()" class="font-semibold text-rose-600 hover:underline cursor-pointer">Clear</button>
                            <button type="button" @click="pickToday()" class="font-semibold text-[#059669] hover:underline cursor-pointer">Today</button>
                        </div>
                    </div>
                </div>

                <!-- 4. Applied To (Custom Styled Calendar Dropdown) -->
                <div class="relative" x-data="reportDatePicker(@js($dateTo), 'Select to date')" @click.outside="open = false">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">To Date</label>
                    <input type="hidden" name="date_to" :value="value">
                    <button type="button"
                            @click="open = !open"
                            class="relative w-full pl-9 pr-8 py-2.5 rounded-xl border bg-white text-xs text-left font-medium transition-all flex items-center justify-between cursor-pointer"
                            :class="open ? 'border-[#059669] ring-2 ring-[#059669]/20' : 'border-slate-200 hover:border-slate-300'">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#059669]">
                            <i class="fa-solid fa-calendar-days text-xs"></i>
                        </span>
                        <span class="truncate" :class="value ? 'text-slate-900 font-semibold' : 'text-slate-400'" x-text="displayLabel"></span>
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="open ? 'rotate-180 text-[#059669]' : ''"></i>
                        </span>
                    </button>

                    <!-- Calendar Dropdown -->
                    <div x-show="open" x-cloak x-transition.opacity.duration.150ms style="display: none;"
                         class="absolute left-0 top-full z-40 mt-1.5 w-68 rounded-2xl bg-white border border-slate-200 shadow-2xl p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <button type="button" @click="prevMonth()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                                <i class="fa-solid fa-chevron-left text-[10px]"></i>
                            </button>
                            <span class="text-xs font-bold text-slate-900" x-text="monthNames[viewMonth] + ' ' + viewYear"></span>
                            <button type="button" @click="nextMonth()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-7 text-center text-[10px] font-bold text-slate-400">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>

                        <div class="grid grid-cols-7 gap-1 text-center">
                            <template x-for="(cell, idx) in calendarCells" :key="idx">
                                <button type="button"
                                        :disabled="!cell.day"
                                        @click="cell.day && selectDay(cell.day)"
                                        class="h-7 w-7 mx-auto rounded-lg text-[11px] font-medium flex items-center justify-center transition-colors"
                                        :class="!cell.day ? 'invisible' : (isSelected(cell.day) ? 'bg-[#059669] text-white font-bold shadow-xs cursor-pointer' : (isToday(cell.day) ? 'bg-emerald-50 text-[#059669] font-bold border border-emerald-200 cursor-pointer' : 'text-slate-700 hover:bg-slate-100 cursor-pointer'))"
                                        x-text="cell.day || ''"></button>
                            </template>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-[11px]">
                            <button type="button" @click="clearDate()" class="font-semibold text-rose-600 hover:underline cursor-pointer">Clear</button>
                            <button type="button" @click="pickToday()" class="font-semibold text-[#059669] hover:underline cursor-pointer">Today</button>
                        </div>
                    </div>
                </div>

                <!-- 5. Search & Apply -->
                <div>
                    <label for="report_search" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search</label>
                    <div class="flex items-center gap-2">
                        <input type="text"
                               id="report_search"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Candidate, major, job..."
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669]">
                        <button type="submit"
                                class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors shrink-0 cursor-pointer">
                            Filter
                        </button>
                    </div>
                </div>
            </div>

            @if (!empty($queryParams))
                <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                    <span class="text-slate-500">
                        Showing <strong class="text-slate-800">{{ $stats['filtered_count'] }}</strong> matching record(s)
                    </span>
                    <a href="{{ route('company.reports.index') }}" class="text-rose-600 hover:text-rose-700 font-semibold">
                        <i class="fa-solid fa-rotate-left mr-1"></i> Reset Filters
                    </a>
                </div>
            @endif
        </form>

        <!-- Report Table -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            @if ($applications->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-5">#</th>
                                <th class="py-3.5 px-4">Candidate</th>
                                <th class="py-3.5 px-4">Academic / GPA</th>
                                <th class="py-3.5 px-4">Applied Position</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Applied Date</th>
                                <th class="py-3.5 px-4">Interview</th>
                                <th class="py-3.5 px-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach ($applications as $idx => $app)
                                @php
                                    $student = $app->studentProfile;
                                    $candUser = $student?->user;
                                    $post = $app->internshipPost;
                                    $interview = $app->interview;
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3.5 px-5 font-semibold text-slate-400">{{ $idx + 1 }}</td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2.5">
                                            @if ($candUser?->has_avatar && $candUser?->avatar_url)
                                                <img src="{{ $candUser->avatar_url }}" alt="{{ $candUser->name }}" class="w-8 h-8 rounded-lg object-cover border border-slate-200 shrink-0">
                                            @else
                                                <div class="w-8 h-8 rounded-lg bg-[#059669] text-white font-bold text-[11px] flex items-center justify-center shrink-0">
                                                    {{ $candUser?->initials ?? 'ST' }}
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                @if ($student)
                                                    <a href="{{ route('company.students.show', $student) }}" class="font-bold text-slate-900 hover:text-[#059669] hover:underline truncate block">
                                                        {{ $candUser?->name ?? 'Candidate' }}
                                                    </a>
                                                @else
                                                    <span class="font-bold text-slate-900">{{ $candUser?->name ?? 'Candidate' }}</span>
                                                @endif
                                                <span class="text-[11px] text-slate-400 truncate block">{{ $candUser?->email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-semibold text-slate-700 block">{{ $student?->major ?: 'N/A' }}</span>
                                        <span class="text-[11px] text-[#059669] font-bold">
                                            GPA: {{ $student?->gpa !== null ? number_format((float) $student->gpa, 2) : 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-bold text-slate-800 block">{{ $post?->title ?? 'N/A' }}</span>
                                        <span class="text-[11px] text-slate-400">{{ $post?->opportunity_type_label ?? 'Internship' }}</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <x-status-badge :status="$app->status" />
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600 font-medium whitespace-nowrap">
                                        {{ $app->applied_at ? \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if ($interview)
                                            <span class="text-indigo-700 font-semibold block">
                                                {{ \Carbon\Carbon::parse($interview->scheduled_at)->format('M d, h:i A') }}
                                            </span>
                                            <span class="text-[10px] text-slate-500 uppercase font-bold">
                                                {{ str_replace('_', ' ', $interview->status) }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            @if ($app->getEffectiveResumePath())
                                                <a href="{{ route('company.applicants.resume', $app) }}" target="_blank"
                                                   class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition-colors">
                                                    CV
                                                </a>
                                            @endif
                                            @if ($student)
                                                <a href="{{ route('company.students.show', $student) }}"
                                                   class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-[#059669] text-[11px] font-semibold transition-colors">
                                                    Profile
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-16 text-center space-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-300 flex items-center justify-center mx-auto">
                        <i class="fa-solid fa-file-circle-xmark text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700">No hiring records match your filters</h3>
                    <p class="text-xs text-slate-400">Try adjusting the job listing, status, or date range.</p>
                </div>
            @endif
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('reportDatePicker', (initial = '', placeholder = 'Select date') => ({
                open: false,
                value: initial || '',
                placeholder,
                viewYear: new Date().getFullYear(),
                viewMonth: new Date().getMonth(),
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                init() {
                    if (this.value) {
                        const d = new Date(this.value + 'T00:00:00');
                        if (!isNaN(d.getTime())) {
                            this.viewYear = d.getFullYear();
                            this.viewMonth = d.getMonth();
                        }
                    }
                },
                get displayLabel() {
                    if (!this.value) return this.placeholder;
                    const d = new Date(this.value + 'T00:00:00');
                    if (isNaN(d.getTime())) return this.placeholder;
                    return `${this.shortMonths[d.getMonth()]} ${String(d.getDate()).padStart(2, '0')}, ${d.getFullYear()}`;
                },
                get calendarCells() {
                    const firstDay = new Date(this.viewYear, this.viewMonth, 1).getDay();
                    const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                    const cells = [];
                    for (let i = 0; i < firstDay; i++) cells.push({ day: null });
                    for (let d = 1; d <= daysInMonth; d++) cells.push({ day: d });
                    return cells;
                },
                prevMonth() {
                    if (this.viewMonth === 0) { this.viewMonth = 11; this.viewYear--; }
                    else { this.viewMonth--; }
                },
                nextMonth() {
                    if (this.viewMonth === 11) { this.viewMonth = 0; this.viewYear++; }
                    else { this.viewMonth++; }
                },
                selectDay(day) {
                    const mm = String(this.viewMonth + 1).padStart(2, '0');
                    const dd = String(day).padStart(2, '0');
                    this.value = `${this.viewYear}-${mm}-${dd}`;
                    this.open = false;
                },
                clearDate() {
                    this.value = '';
                    this.open = false;
                },
                pickToday() {
                    const now = new Date();
                    this.viewYear = now.getFullYear();
                    this.viewMonth = now.getMonth();
                    this.selectDay(now.getDate());
                },
                isSelected(day) {
                    if (!this.value) return false;
                    const mm = String(this.viewMonth + 1).padStart(2, '0');
                    const dd = String(day).padStart(2, '0');
                    return this.value === `${this.viewYear}-${mm}-${dd}`;
                },
                isToday(day) {
                    const now = new Date();
                    return now.getFullYear() === this.viewYear && now.getMonth() === this.viewMonth && now.getDate() === day;
                }
            }));
        });
    </script>
</x-layout>
