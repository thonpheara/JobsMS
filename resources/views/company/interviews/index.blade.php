<x-layout>
    <script>
        function customDateTimePicker(initialVal = '') {
            return {
                open: false,
                value: initialVal,
                viewYear: 2026,
                viewMonth: 0,
                selectedYear: 2026,
                selectedMonth: 0,
                selectedDay: 1,
                hour12: 9,
                minute: '00',
                ampm: 'AM',
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                hoursList: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                minutesList: ['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'],
                init() {
                    this.parseValue(this.value);
                },
                parseValue(val) {
                    let d = val ? new Date(val) : new Date(Date.now() + 86400000);
                    if (isNaN(d.getTime())) {
                        d = new Date(Date.now() + 86400000);
                    }
                    this.selectedYear = d.getFullYear();
                    this.selectedMonth = d.getMonth();
                    this.selectedDay = d.getDate();
                    this.viewYear = this.selectedYear;
                    this.viewMonth = this.selectedMonth;

                    let h = d.getHours();
                    this.ampm = h >= 12 ? 'PM' : 'AM';
                    this.hour12 = h % 12 || 12;
                    let m = Math.round(d.getMinutes() / 5) * 5;
                    if (m === 60) m = 55;
                    this.minute = String(m).padStart(2, '0');
                    this.syncValue();
                },
                syncValue() {
                    let h24 = parseInt(this.hour12, 10);
                    if (this.ampm === 'PM' && h24 < 12) h24 += 12;
                    if (this.ampm === 'AM' && h24 === 12) h24 = 0;

                    const yyyy = this.selectedYear;
                    const mm = String(this.selectedMonth + 1).padStart(2, '0');
                    const dd = String(this.selectedDay).padStart(2, '0');
                    const hh = String(h24).padStart(2, '0');
                    this.value = `${yyyy}-${mm}-${dd}T${hh}:${this.minute}`;
                },
                get displayLabel() {
                    if (!this.value) return 'Select date & time';
                    const mName = this.shortMonths[this.selectedMonth] || 'Jan';
                    const dd = String(this.selectedDay).padStart(2, '0');
                    const hh = String(this.hour12).padStart(2, '0');
                    return `${mName} ${dd}, ${this.selectedYear} • ${hh}:${this.minute} ${this.ampm}`;
                },
                get calendarDays() {
                    const firstDay = new Date(this.viewYear, this.viewMonth, 1).getDay();
                    const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                    const prevMonthDays = new Date(this.viewYear, this.viewMonth, 0).getDate();
                    const cells = [];

                    for (let i = firstDay - 1; i >= 0; i--) {
                        cells.push({ day: prevMonthDays - i, currentMonth: false, dateObj: new Date(this.viewYear, this.viewMonth - 1, prevMonthDays - i) });
                    }
                    for (let i = 1; i <= daysInMonth; i++) {
                        cells.push({ day: i, currentMonth: true, dateObj: new Date(this.viewYear, this.viewMonth, i) });
                    }
                    const remaining = 42 - cells.length;
                    for (let i = 1; i <= remaining; i++) {
                        cells.push({ day: i, currentMonth: false, dateObj: new Date(this.viewYear, this.viewMonth + 1, i) });
                    }
                    return cells;
                },
                prevMonth() {
                    if (this.viewMonth === 0) {
                        this.viewMonth = 11;
                        this.viewYear--;
                    } else {
                        this.viewMonth--;
                    }
                },
                nextMonth() {
                    if (this.viewMonth === 11) {
                        this.viewMonth = 0;
                        this.viewYear++;
                    } else {
                        this.viewMonth++;
                    }
                },
                selectDate(cell) {
                    const d = cell.dateObj;
                    this.selectedYear = d.getFullYear();
                    this.selectedMonth = d.getMonth();
                    this.selectedDay = d.getDate();
                    this.viewYear = this.selectedYear;
                    this.viewMonth = this.selectedMonth;
                    this.syncValue();
                },
                isSelected(cell) {
                    const d = cell.dateObj;
                    return d.getFullYear() === this.selectedYear &&
                           d.getMonth() === this.selectedMonth &&
                           d.getDate() === this.selectedDay;
                },
                isToday(cell) {
                    const now = new Date();
                    const d = cell.dateObj;
                    return d.getFullYear() === now.getFullYear() &&
                           d.getMonth() === now.getMonth() &&
                           d.getDate() === now.getDate();
                },
                setToday() {
                    const now = new Date();
                    this.selectedYear = now.getFullYear();
                    this.selectedMonth = now.getMonth();
                    this.selectedDay = now.getDate();
                    this.viewYear = this.selectedYear;
                    this.viewMonth = this.selectedMonth;
                    this.syncValue();
                }
            };
        }
    </script>

    <div x-data="{
            scheduleModalOpen: false,
            scheduleType: 'online',
            editModalOpen: false,
            editInterview: {
                id: null,
                update_url: '',
                candidate_name: '',
                role_title: '',
                scheduled_at: '',
                meeting_type: 'online',
                location_or_link: '',
                notes: '',
                reschedule_reason: ''
            },
            evalModalOpen: false,
            evalInterview: {
                id: null,
                status_url: '',
                candidate_name: '',
                role_title: '',
                notes: '',
                company_notes: '',
                hiring_decision: 'keep_interviewed'
            },
            openEditModal(data) {
                this.editInterview = { ...data };
                this.editModalOpen = true;
            },
            openEvalModal(data) {
                this.evalInterview = {
                    ...data,
                    hiring_decision: 'keep_interviewed'
                };
                this.evalModalOpen = true;
            }
        }"
        class="max-w-7xl mx-auto space-y-6 pb-12">

        <!-- ========================================================================= -->
        <!-- PAGE HEADER & ACTIONS                                                     -->
        <!-- ========================================================================= -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900">Interview Management</h2>
                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">
                    Manage candidate interview schedules, handle reschedule requests, and record post-interview hiring decisions.
                </p>
            </div>

            <div class="flex items-center gap-2.5 self-start sm:self-auto shrink-0">
                <a href="{{ route('company.applicants.index') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition-colors">
                    <i class="fa-solid fa-users text-slate-400"></i>
                    <span>View Applicants</span>
                </a>
                <button type="button"
                        @click="scheduleModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-[#059669] hover:bg-[#047857] text-white shadow-xs transition-colors cursor-pointer">
                    <i class="fa-solid fa-calendar-plus text-xs"></i>
                    <span>Schedule Interview</span>
                </button>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- RESCHEDULE REQUEST ALERT BANNER                                           -->
        <!-- ========================================================================= -->
        @if($stats['reschedule'] > 0 && $tab !== 'reschedule_requested')
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                    </div>
                    <div>
                        <h5 class="font-bold text-xs sm:text-sm text-amber-950">
                            {{ $stats['reschedule'] }} Candidate Reschedule Request{{ $stats['reschedule'] > 1 ? 's' : '' }} Pending Your Response
                        </h5>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Candidates have requested an alternative interview date or time. Review their reason and update the schedule.
                        </p>
                    </div>
                </div>
                <a href="{{ route('company.interviews.index', ['tab' => 'reschedule_requested']) }}"
                   class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shrink-0 transition-colors flex items-center gap-1.5 self-start sm:self-auto shadow-2xs">
                    <span>View Requests</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- 4 KPI SUMMARY CARDS                                                       -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            <!-- Today's Interviews -->
            <a href="{{ route('company.interviews.index', ['tab' => 'today']) }}"
               class="p-4 sm:p-5 rounded-2xl bg-white border {{ $tab === 'today' ? 'border-indigo-500 ring-2 ring-indigo-500/15' : 'border-slate-200 hover:border-indigo-300' }} shadow-2xs transition-all flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500">Today's Interviews</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">{{ $stats['today'] }}</p>
                    <span class="text-[11px] font-medium text-indigo-600 mt-1 inline-block">Active today</span>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-calendar-day text-lg"></i>
                </div>
            </a>

            <!-- Upcoming Sessions -->
            <a href="{{ route('company.interviews.index', ['tab' => 'upcoming']) }}"
               class="p-4 sm:p-5 rounded-2xl bg-white border {{ $tab === 'upcoming' ? 'border-emerald-500 ring-2 ring-emerald-500/15' : 'border-slate-200 hover:border-emerald-300' }} shadow-2xs transition-all flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500">Upcoming</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">{{ $stats['upcoming'] }}</p>
                    <span class="text-[11px] font-medium text-emerald-600 mt-1 inline-block">Scheduled & Confirmed</span>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-clock text-lg"></i>
                </div>
            </a>

            <!-- Reschedule Requests -->
            <a href="{{ route('company.interviews.index', ['tab' => 'reschedule_requested']) }}"
               class="p-4 sm:p-5 rounded-2xl bg-white border {{ $tab === 'reschedule_requested' ? 'border-amber-500 ring-2 ring-amber-500/15' : 'border-slate-200 hover:border-amber-300' }} shadow-2xs transition-all flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500">Reschedule Requests</p>
                    <p class="text-2xl sm:text-3xl font-extrabold {{ $stats['reschedule'] > 0 ? 'text-amber-600' : 'text-slate-900' }} mt-1">{{ $stats['reschedule'] }}</p>
                    <span class="text-[11px] font-medium text-amber-600 mt-1 inline-block">Needs new time</span>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-calendar-week text-lg"></i>
                </div>
            </a>

            <!-- Completed Interviews -->
            <a href="{{ route('company.interviews.index', ['tab' => 'completed']) }}"
               class="p-4 sm:p-5 rounded-2xl bg-white border {{ $tab === 'completed' ? 'border-teal-500 ring-2 ring-teal-500/15' : 'border-slate-200 hover:border-teal-300' }} shadow-2xs transition-all flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500">Completed</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">{{ $stats['completed'] }}</p>
                    <span class="text-[11px] font-medium text-teal-600 mt-1 inline-block">Evaluated sessions</span>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                </div>
            </a>
        </div>

        <!-- ========================================================================= -->
        <!-- FILTER TABS & SEARCH BAR                                                  -->
        <!-- ========================================================================= -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-4">
            <!-- Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-2 border-b border-slate-100">
                @php
                    $tabs = [
                        'all'                  => ['label' => 'All Sessions', 'count' => $stats['total']],
                        'today'                => ['label' => 'Today', 'count' => $stats['today']],
                        'upcoming'             => ['label' => 'Upcoming', 'count' => $stats['upcoming']],
                        'reschedule_requested' => ['label' => 'Reschedule Requests', 'count' => $stats['reschedule'], 'badgeColor' => 'bg-amber-100 text-amber-800'],
                        'completed'            => ['label' => 'Completed', 'count' => $stats['completed']],
                        'cancelled'            => ['label' => 'Cancelled', 'count' => $stats['cancelled']],
                    ];
                @endphp

                @foreach($tabs as $key => $info)
                    <a href="{{ route('company.interviews.index', array_merge(request()->except('page'), ['tab' => $key])) }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors
                              {{ $tab === $key ? 'bg-[#D1FAE5] text-[#065F46]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span>{{ $info['label'] }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $tab === $key ? 'bg-white text-[#065F46]' : ($info['badgeColor'] ?? 'bg-slate-100 text-slate-600') }}">
                            {{ $info['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>

            <!-- Search & Dropdown Filters -->
            <form method="GET" action="{{ route('company.interviews.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <!-- Search Input -->
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search candidate name, major, or job title..."
                           class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/60 focus:bg-white text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all">
                </div>

                <!-- Styled Custom Dropdowns & Filter Buttons -->
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <!-- Job Post Filter Dropdown -->
                    <div class="relative"
                         x-data="{
                             open: false,
                             postId: '{{ request('post_id', '') }}',
                             postTitle: '{{ addslashes($companyPosts->firstWhere('id', request('post_id'))?->title ?? 'All Job Listings') }}',
                             selectPost(id, title) {
                                 this.postId = id;
                                 this.postTitle = title;
                                 this.open = false;
                                 $nextTick(() => {
                                     $el.closest('form').submit();
                                 });
                             }
                         }">
                        <input type="hidden" name="post_id" :value="postId">
                        <button type="button"
                                @click="open = !open"
                                class="inline-flex items-center justify-between gap-2.5 py-2 pl-3 pr-2.5 text-xs rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] font-medium text-slate-700 transition-all cursor-pointer shadow-2xs min-w-[180px] max-w-[240px]">
                            <span class="flex items-center gap-2 truncate">
                                <i class="fa-solid fa-briefcase text-[11px] text-gray-400"></i>
                                <span class="truncate font-semibold text-slate-800" x-text="postTitle"></span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200 shrink-0"
                               :class="open ? 'rotate-180 text-[#059669]' : ''"></i>
                        </button>

                        <div x-show="open"
                             x-cloak
                             @click.outside="open = false"
                             @keydown.escape.window="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                             class="absolute left-0 top-full z-40 mt-1.5 w-64 max-h-60 overflow-y-auto rounded-2xl bg-white border border-slate-200 shadow-xl p-1.5 space-y-0.5"
                             style="display: none;">
                            <button type="button"
                                    @click="selectPost('', 'All Job Listings')"
                                    class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                    :class="postId === '' ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                <span class="truncate">All Job Listings</span>
                                <i x-show="postId === ''" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                            </button>
                            @foreach($companyPosts as $p)
                                <button type="button"
                                        @click="selectPost('{{ $p->id }}', '{{ addslashes($p->title) }}')"
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                        :class="postId == '{{ $p->id }}' ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                    <span class="truncate">{{ $p->title }}</span>
                                    <i x-show="postId == '{{ $p->id }}'" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Format Filter Dropdown -->
                    <div class="relative"
                         x-data="{
                             open: false,
                             formatVal: '{{ request('meeting_type', '') }}',
                             options: [
                                 { value: '', label: 'All Formats', dot: 'bg-slate-400', icon: 'fa-solid fa-layer-group text-slate-400' },
                                 { value: 'online', label: 'Online Video', dot: 'bg-indigo-500 ring-2 ring-indigo-100', icon: 'fa-solid fa-video text-indigo-500' },
                                 { value: 'in_person', label: 'In-Person', dot: 'bg-emerald-500 ring-2 ring-emerald-100', icon: 'fa-solid fa-location-dot text-emerald-600' }
                             ],
                             get currentOption() {
                                 return this.options.find(o => o.value === this.formatVal) || this.options[0];
                             },
                             selectFormat(val) {
                                 this.formatVal = val;
                                 this.open = false;
                                 $nextTick(() => {
                                     $el.closest('form').submit();
                                 });
                             }
                         }">
                        <input type="hidden" name="meeting_type" :value="formatVal">
                        <button type="button"
                                @click="open = !open"
                                class="inline-flex items-center justify-between gap-2.5 py-2 pl-3 pr-2.5 text-xs rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] font-medium text-slate-700 transition-all cursor-pointer shadow-2xs min-w-[145px]">
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
                             @keydown.escape.window="open = false"
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
                                        @click="selectFormat(item.value)"
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                        :class="formatVal === item.value ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="item.dot"></span>
                                        <span x-text="item.label"></span>
                                    </div>
                                    <i x-show="formatVal === item.value" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    <button type="submit"
                            class="py-2 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors cursor-pointer shadow-2xs">
                        Filter
                    </button>

                    @if(request()->filled('search') || request()->filled('post_id') || request()->filled('meeting_type'))
                        <a href="{{ route('company.interviews.index', ['tab' => $tab]) }}"
                           class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-colors"
                           title="Clear Filters">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- ========================================================================= -->
        <!-- INTERVIEWS LIST                                                           -->
        <!-- ========================================================================= -->
        @if($interviews->isNotEmpty())
            <div class="space-y-3.5">
                @foreach($interviews as $interview)
                    @php
                        $app = $interview->application;
                        $student = $app?->studentProfile;
                        $candidate = $student?->user;
                        $post = $app?->internshipPost;
                        $isOnline = $interview->isOnline();
                        $isUrl = filter_var($interview->location_or_link, FILTER_VALIDATE_URL) !== false;
                        $isConfirmed = $interview->isConfirmed();
                        $isReschedule = $interview->isRescheduleRequested();
                        $scheduledAt = $interview->scheduled_at;
                        $isToday = $scheduledAt?->isToday() ?? false;
                        $isTomorrow = $scheduledAt?->isTomorrow() ?? false;
                        $isPast = $scheduledAt?->isPast() ?? false;
                    @endphp

                    <div class="p-5 rounded-2xl bg-white border transition-all shadow-2xs
                                {{ $isReschedule ? 'border-amber-300 ring-1 ring-amber-200 bg-amber-50/15' : ($isToday && in_array($interview->status, ['scheduled', 'confirmed']) ? 'border-indigo-300 ring-1 ring-indigo-100' : 'border-slate-200 hover:border-slate-300') }}">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

                            <!-- Left: Candidate & Position Info -->
                            <div class="flex items-start gap-3.5 min-w-0">
                                @if($candidate?->has_avatar && $candidate?->avatar_url)
                                    <img src="{{ $candidate->avatar_url }}" alt="{{ $candidate->name }}" class="w-11 h-11 rounded-full object-cover border border-slate-200 shrink-0">
                                @else
                                    <div class="w-11 h-11 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm shrink-0 border border-indigo-200">
                                        {{ $candidate?->initials ?? 'ST' }}
                                    </div>
                                @endif

                                <div class="min-w-0 space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                            {{ $candidate?->name ?? 'Unknown Candidate' }}
                                        </h3>

                                        <!-- Interview Status Pill -->
                                        @if($interview->status === 'completed')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                                <i class="fa-solid fa-check-double text-[10px]"></i> Completed
                                            </span>
                                        @elseif($interview->status === 'cancelled')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                <i class="fa-solid fa-ban text-[10px]"></i> Cancelled
                                            </span>
                                        @elseif($isReschedule)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-[10px]"></i> Reschedule Requested
                                            </span>
                                        @elseif($isConfirmed)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i class="fa-solid fa-circle-check text-emerald-500 text-[10px]"></i> Confirmed by Student
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                <i class="fa-regular fa-clock text-slate-400 text-[10px]"></i> Awaiting Confirmation
                                            </span>
                                        @endif

                                        <!-- Application Outcome Pill -->
                                        @if($app?->status === 'accepted')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-600 text-white">
                                                <i class="fa-solid fa-award"></i> Hired / Accepted
                                            </span>
                                        @elseif($app?->status === 'rejected')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                                Not Selected
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 flex-wrap text-xs text-slate-500">
                                        <span class="font-semibold text-slate-700">{{ $post?->title ?? 'Position' }}</span>
                                        <span>•</span>
                                        <span>{{ $student?->major ?? 'Major N/A' }}</span>
                                        @if($student?->gpa)
                                            <span>•</span>
                                            <span class="font-semibold text-slate-700">GPA {{ number_format((float) $student->gpa, 2) }}</span>
                                        @endif
                                    </div>

                                    @if($isReschedule && !empty($interview->reschedule_reason))
                                        <div class="mt-2 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
                                            <span class="font-bold text-amber-950"><i class="fa-solid fa-comment-dots mr-1"></i> Student's Reschedule Reason:</span>
                                            {{ $interview->reschedule_reason }}
                                        </div>
                                    @endif

                                    @if(!empty($interview->notes))
                                        <p class="text-xs text-slate-500 italic pt-0.5">
                                            <i class="fa-regular fa-note-sticky mr-1 text-slate-400"></i> {{ $interview->notes }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Middle: Schedule & Location Details -->
                            <div class="flex flex-col sm:flex-row lg:flex-col gap-2 sm:items-center lg:items-end text-xs shrink-0 bg-slate-50/80 p-3 rounded-xl border border-slate-100">
                                <div class="flex items-center gap-2 font-bold text-slate-900">
                                    <i class="fa-regular fa-calendar text-indigo-600"></i>
                                    <span>{{ $scheduledAt ? $scheduledAt->format('D, M d, Y \a\t g:i A') : 'TBD' }}</span>
                                    @if($isToday && in_array($interview->status, ['scheduled', 'confirmed']))
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-indigo-600 text-white">TODAY</span>
                                    @elseif($isTomorrow && in_array($interview->status, ['scheduled', 'confirmed']))
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">Tomorrow</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 text-slate-600">
                                    @if($isOnline)
                                        <span class="inline-flex items-center gap-1 font-semibold text-indigo-600">
                                            <i class="fa-solid fa-video text-[11px]"></i> Online Video
                                        </span>
                                        <span>•</span>
                                        @if($isUrl)
                                            <a href="{{ $interview->location_or_link }}" target="_blank" rel="noopener noreferrer"
                                               class="font-bold text-[#059669] hover:underline inline-flex items-center gap-1">
                                                <span>Join Meeting</span>
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                            </a>
                                        @else
                                            <span class="truncate max-w-[200px]">{{ $interview->location_or_link }}</span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                                            <i class="fa-solid fa-location-dot text-[11px]"></i> In-Person
                                        </span>
                                        <span>•</span>
                                        <span class="truncate max-w-[220px]" title="{{ $interview->location_or_link }}">{{ $interview->location_or_link }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Right: Quick Actions -->
                            <div class="flex items-center gap-2 flex-wrap shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100">
                                @if($interview->status !== 'cancelled')
                                    <!-- Reschedule / Edit Button -->
                                    <button type="button"
                                            @click="openEditModal({
                                                id: {{ $interview->id }},
                                                update_url: @js(route('company.interviews.update', $interview)),
                                                candidate_name: @js($candidate?->name ?? 'Candidate'),
                                                role_title: @js($post?->title ?? 'Position'),
                                                scheduled_at: @js($scheduledAt ? $scheduledAt->format('Y-m-d\TH:i') : ''),
                                                meeting_type: @js($interview->meeting_type),
                                                location_or_link: @js($interview->location_or_link),
                                                notes: @js($interview->notes ?? ''),
                                                reschedule_reason: @js($interview->reschedule_reason ?? '')
                                            })"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-colors cursor-pointer
                                                   {{ $isReschedule ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                                        <i class="fa-solid fa-calendar-pen text-[11px]"></i>
                                        <span>{{ $isReschedule ? 'Respond & Reschedule' : 'Reschedule' }}</span>
                                    </button>

                                    <!-- Complete & Evaluate Button -->
                                    <button type="button"
                                            @click="openEvalModal({
                                                id: {{ $interview->id }},
                                                status_url: @js(route('company.interviews.status', $interview)),
                                                candidate_name: @js($candidate?->name ?? 'Candidate'),
                                                role_title: @js($post?->title ?? 'Position'),
                                                notes: @js($interview->notes ?? ''),
                                                company_notes: @js($app?->company_notes ?? '')
                                            })"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-[#065F46] border border-emerald-200 text-xs font-bold transition-colors cursor-pointer">
                                        <i class="fa-solid fa-clipboard-check text-[11px]"></i>
                                        <span>{{ $interview->status === 'completed' ? 'Update Evaluation' : 'Complete & Evaluate' }}</span>
                                    </button>
                                @endif

                                @if($app)
                                    <!-- Direct Message Candidate -->
                                    <a href="{{ route('company.messages.start', $app) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors"
                                       title="Message Candidate">
                                        <i class="fa-solid fa-comment text-xs"></i>
                                    </a>
                                @endif

                                @if(in_array($interview->status, ['scheduled', 'confirmed', 'reschedule_requested']))
                                    <!-- Cancel Interview -->
                                    <form method="POST" action="{{ route('company.interviews.status', $interview) }}"
                                          onsubmit="return confirm('Are you sure you want to cancel this interview? The candidate will be notified.');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 transition-colors cursor-pointer"
                                                title="Cancel Interview">
                                            <i class="fa-solid fa-xmark text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-2">
                {{ $interviews->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="p-10 sm:p-14 rounded-3xl bg-white border border-slate-200 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto">
                    <i class="fa-solid fa-calendar-check text-2xl"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">No interview sessions found</h3>
                <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                    There are no interviews matching your current filter. You can schedule a new interview with any shortlisted or active applicant.
                </p>
                <div class="pt-2">
                    <button type="button"
                            @click="scheduleModalOpen = true"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white text-xs font-bold transition-colors cursor-pointer">
                        <i class="fa-solid fa-calendar-plus"></i>
                        <span>Schedule First Interview</span>
                    </button>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- MODAL 1: SCHEDULE NEW INTERVIEW                                           -->
        <!-- ========================================================================= -->
        <div x-show="scheduleModalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
             @keydown.escape.window="scheduleModalOpen = false">
            <div @click.away="scheduleModalOpen = false"
                 class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-xl border border-slate-200 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Schedule Candidate Interview</h3>
                            <p class="text-xs text-slate-500">Send an interview invitation & notify the student</p>
                        </div>
                    </div>
                    <button type="button" @click="scheduleModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('company.interviews.store') }}" class="space-y-4">
                    @csrf
                    <!-- Candidate Custom Dropdown -->
                    <div class="relative"
                         x-data="{
                             open: false,
                             appId: '',
                             appLabel: '-- Choose an applicant --',
                             selectApp(id, label) {
                                 this.appId = id;
                                 this.appLabel = label;
                                 this.open = false;
                             }
                         }">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Select Candidate & Job Listing *</label>
                        <input type="hidden" name="application_id" :value="appId" required>
                        <button type="button"
                                @click="open = !open"
                                class="w-full inline-flex items-center justify-between gap-2.5 px-3.5 py-2.5 text-xs sm:text-sm rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all cursor-pointer shadow-2xs">
                            <span class="flex items-center gap-2 truncate">
                                <i class="fa-solid fa-user-graduate text-xs text-slate-400"></i>
                                <span class="truncate" :class="appId ? 'font-semibold text-slate-900' : 'text-slate-400'" x-text="appLabel"></span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200 shrink-0"
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
                             class="absolute left-0 top-full z-50 mt-1.5 w-full max-h-56 overflow-y-auto rounded-2xl bg-white border border-slate-200 shadow-xl p-1.5 space-y-0.5"
                             style="display: none;">
                            @forelse($eligibleApplications as $eligApp)
                                @php
                                    $eligLabel = ($eligApp->studentProfile?->user?->name ?? 'Student') . ' — ' . ($eligApp->internshipPost?->title ?? 'Listing') . ' (' . ucfirst(str_replace('_', ' ', $eligApp->status)) . ')';
                                @endphp
                                <button type="button"
                                        @click="selectApp('{{ $eligApp->id }}', '{{ addslashes($eligLabel) }}')"
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                        :class="appId == '{{ $eligApp->id }}' ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                    <span class="truncate">{{ $eligLabel }}</span>
                                    <i x-show="appId == '{{ $eligApp->id }}'" class="fa-solid fa-check text-[10px] text-[#059669] shrink-0 ml-2"></i>
                                </button>
                            @empty
                                <div class="px-3 py-2.5 text-xs text-slate-400 text-center">No eligible applicants available</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="relative" x-data="customDateTimePicker('{{ now()->addDay()->setHour(9)->setMinute(0)->format('Y-m-d\TH:i') }}')">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Date & Time *</label>
                            <input type="hidden" name="scheduled_at" :value="value" required>
                            <button type="button"
                                    @click="open = !open"
                                    class="w-full inline-flex items-center justify-between gap-2 px-3.5 py-2 text-xs sm:text-sm rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all cursor-pointer shadow-2xs">
                                <span class="flex items-center gap-2 truncate">
                                    <i class="fa-regular fa-calendar text-xs text-[#059669]"></i>
                                    <span class="font-semibold text-slate-800 truncate" x-text="displayLabel"></span>
                                </span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200 shrink-0"
                                   :class="open ? 'rotate-180 text-[#059669]' : ''"></i>
                            </button>

                            <!-- Custom Calendar & Time Dropdown Panel -->
                            <div x-show="open"
                                 x-cloak
                                 @click.outside="open = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 class="absolute left-0 top-full z-50 mt-1.5 w-72 sm:w-80 rounded-2xl bg-white border border-slate-200 shadow-2xl p-3.5 space-y-3"
                                 style="display: none;">

                                <!-- Month Navigation -->
                                <div class="flex items-center justify-between">
                                    <button type="button" @click="prevMonth()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                                    </button>
                                    <span class="text-xs font-extrabold text-slate-900" x-text="monthNames[viewMonth] + ' ' + viewYear"></span>
                                    <button type="button" @click="nextMonth()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </button>
                                </div>

                                <!-- Day Headers -->
                                <div class="grid grid-cols-7 text-center text-[10px] font-bold text-slate-400">
                                    <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                                </div>

                                <!-- Calendar Days Grid -->
                                <div class="grid grid-cols-7 gap-1 text-center">
                                    <template x-for="(cell, idx) in calendarDays" :key="idx">
                                        <button type="button"
                                                @click="selectDate(cell)"
                                                class="h-8 rounded-lg text-xs transition-all flex items-center justify-center cursor-pointer"
                                                :class="isSelected(cell)
                                                    ? 'bg-[#059669] text-white font-extrabold shadow-2xs'
                                                    : (isToday(cell)
                                                        ? 'bg-emerald-50 text-[#059669] font-bold border border-emerald-200'
                                                        : (cell.currentMonth ? 'text-slate-700 hover:bg-slate-100 font-medium' : 'text-slate-300 hover:bg-slate-50'))">
                                            <span x-text="cell.day"></span>
                                        </button>
                                    </template>
                                </div>

                                <!-- Time Picker Row -->
                                <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-bold text-slate-500 flex items-center gap-1">
                                        <i class="fa-regular fa-clock text-[#059669]"></i> Time
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <!-- Hour Custom Dropdown -->
                                        <div class="relative" x-data="{ hOpen: false }">
                                            <button type="button" @click="hOpen = !hOpen"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:border-[#059669] text-xs font-bold text-slate-800 cursor-pointer shadow-2xs">
                                                <span x-text="String(hour12).padStart(2, '0')"></span>
                                                <i class="fa-solid fa-chevron-down text-[8px] text-slate-400"></i>
                                            </button>
                                            <div x-show="hOpen" x-cloak @click.outside="hOpen = false"
                                                 class="absolute bottom-full left-0 mb-1 w-16 max-h-40 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-xl p-1 z-50">
                                                <template x-for="h in hoursList" :key="h">
                                                    <button type="button"
                                                            @click="hour12 = h; syncValue(); hOpen = false"
                                                            class="w-full px-2 py-1 text-xs rounded-lg text-center cursor-pointer transition-colors"
                                                            :class="hour12 == h ? 'bg-[#059669] text-white font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'"
                                                            x-text="String(h).padStart(2, '0')"></button>
                                                </template>
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-400">:</span>
                                        <!-- Minute Custom Dropdown -->
                                        <div class="relative" x-data="{ mOpen: false }">
                                            <button type="button" @click="mOpen = !mOpen"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:border-[#059669] text-xs font-bold text-slate-800 cursor-pointer shadow-2xs">
                                                <span x-text="minute"></span>
                                                <i class="fa-solid fa-chevron-down text-[8px] text-slate-400"></i>
                                            </button>
                                            <div x-show="mOpen" x-cloak @click.outside="mOpen = false"
                                                 class="absolute bottom-full left-0 mb-1 w-16 max-h-40 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-xl p-1 z-50">
                                                <template x-for="m in minutesList" :key="m">
                                                    <button type="button"
                                                            @click="minute = m; syncValue(); mOpen = false"
                                                            class="w-full px-2 py-1 text-xs rounded-lg text-center cursor-pointer transition-colors"
                                                            :class="minute === m ? 'bg-[#059669] text-white font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'"
                                                            x-text="m"></button>
                                                </template>
                                            </div>
                                        </div>
                                        <!-- AM/PM Toggle -->
                                        <div class="inline-flex rounded-lg bg-slate-100 p-0.5 border border-slate-200">
                                            <button type="button"
                                                    @click="ampm = 'AM'; syncValue()"
                                                    :class="ampm === 'AM' ? 'bg-[#059669] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                                                    class="px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer">AM</button>
                                            <button type="button"
                                                    @click="ampm = 'PM'; syncValue()"
                                                    :class="ampm === 'PM' ? 'bg-[#059669] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                                                    class="px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer">PM</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Actions -->
                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                    <button type="button" @click="setToday()" class="text-[11px] font-bold text-[#059669] hover:underline cursor-pointer">
                                        Today
                                    </button>
                                    <button type="button" @click="open = false" class="px-3 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold transition-colors cursor-pointer">
                                        Done
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="relative" x-data="{ open: false }">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Interview Format *</label>
                            <input type="hidden" name="meeting_type" :value="scheduleType">
                            <button type="button"
                                    @click="open = !open"
                                    class="w-full inline-flex items-center justify-between gap-2 px-3.5 py-2 text-xs sm:text-sm rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all cursor-pointer shadow-2xs">
                                <span class="flex items-center gap-2 truncate">
                                    <i class="text-xs" :class="scheduleType === 'online' ? 'fa-solid fa-video text-indigo-600' : 'fa-solid fa-location-dot text-emerald-600'"></i>
                                    <span class="font-semibold text-slate-800 truncate" x-text="scheduleType === 'online' ? 'Online Video Call' : 'In-Person at Office'"></span>
                                </span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200 shrink-0"
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
                                 class="absolute left-0 top-full z-50 mt-1.5 w-full rounded-2xl bg-white border border-slate-200 shadow-xl p-1.5 space-y-0.5"
                                 style="display: none;">
                                <button type="button"
                                        @click="scheduleType = 'online'; open = false"
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                        :class="scheduleType === 'online' ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid fa-video text-indigo-600 w-3.5 text-center"></i>
                                        <span>Online Video Call</span>
                                    </span>
                                    <i x-show="scheduleType === 'online'" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                                </button>
                                <button type="button"
                                        @click="scheduleType = 'in_person'; open = false"
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                        :class="scheduleType === 'in_person' ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid fa-location-dot text-emerald-600 w-3.5 text-center"></i>
                                        <span>In-Person at Office</span>
                                    </span>
                                    <i x-show="scheduleType === 'in_person'" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5"
                               x-text="scheduleType === 'online' ? 'Meeting Link (Google Meet, Zoom, Teams) *' : 'Office Address / Room Location *'"></label>
                        <input type="text" name="location_or_link" required
                               :placeholder="scheduleType === 'online' ? 'https://meet.google.com/...' : 'Floor 3, Meeting Room B, Phnom Penh'"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:border-[#059669]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Preparation Notes for Candidate (Optional)</label>
                        <textarea name="notes" rows="3"
                                  placeholder="Bring portfolio or prepare a 5-minute walkthrough of recent projects..."
                                  class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:border-[#059669]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button type="button" @click="scheduleModalOpen = false"
                                class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2 rounded-xl bg-[#059669] hover:bg-[#047857] text-white text-xs font-bold transition-colors cursor-pointer">
                            Send Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL 2: RESCHEDULE / EDIT INTERVIEW                                      -->
        <!-- ========================================================================= -->
        <div x-show="editModalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
             @keydown.escape.window="editModalOpen = false">
            <div @click.away="editModalOpen = false"
                 class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-xl border border-slate-200 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Reschedule / Update Interview</h3>
                        <p class="text-xs text-slate-500">
                            <span class="font-semibold text-slate-700" x-text="editInterview.candidate_name"></span> •
                            <span x-text="editInterview.role_title"></span>
                        </p>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <template x-if="editInterview.reschedule_reason">
                    <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
                        <span class="font-bold block text-amber-950 mb-0.5">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i> Candidate's Reschedule Request Reason:
                        </span>
                        <span x-text="editInterview.reschedule_reason"></span>
                    </div>
                </template>

                <form method="POST" :action="editInterview.update_url" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="relative"
                             x-data="customDateTimePicker(editInterview.scheduled_at)"
                             x-effect="if (editInterview.scheduled_at && editInterview.scheduled_at !== value) { parseValue(editInterview.scheduled_at); }">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">New Date & Time *</label>
                            <input type="hidden" name="scheduled_at" :value="value" required>
                            <button type="button"
                                    @click="open = !open"
                                    class="w-full inline-flex items-center justify-between gap-2 px-3.5 py-2 text-xs sm:text-sm rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all cursor-pointer shadow-2xs">
                                <span class="flex items-center gap-2 truncate">
                                    <i class="fa-regular fa-calendar text-xs text-[#059669]"></i>
                                    <span class="font-semibold text-slate-800 truncate" x-text="displayLabel"></span>
                                </span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200 shrink-0"
                                   :class="open ? 'rotate-180 text-[#059669]' : ''"></i>
                            </button>

                            <!-- Custom Calendar & Time Dropdown Panel -->
                            <div x-show="open"
                                 x-cloak
                                 @click.outside="open = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 class="absolute left-0 top-full z-50 mt-1.5 w-72 sm:w-80 rounded-2xl bg-white border border-slate-200 shadow-2xl p-3.5 space-y-3"
                                 style="display: none;">

                                <!-- Month Navigation -->
                                <div class="flex items-center justify-between">
                                    <button type="button" @click="prevMonth()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                                    </button>
                                    <span class="text-xs font-extrabold text-slate-900" x-text="monthNames[viewMonth] + ' ' + viewYear"></span>
                                    <button type="button" @click="nextMonth()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </button>
                                </div>

                                <!-- Day Headers -->
                                <div class="grid grid-cols-7 text-center text-[10px] font-bold text-slate-400">
                                    <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                                </div>

                                <!-- Calendar Days Grid -->
                                <div class="grid grid-cols-7 gap-1 text-center">
                                    <template x-for="(cell, idx) in calendarDays" :key="idx">
                                        <button type="button"
                                                @click="selectDate(cell); editInterview.scheduled_at = value;"
                                                class="h-8 rounded-lg text-xs transition-all flex items-center justify-center cursor-pointer"
                                                :class="isSelected(cell)
                                                    ? 'bg-[#059669] text-white font-extrabold shadow-2xs'
                                                    : (isToday(cell)
                                                        ? 'bg-emerald-50 text-[#059669] font-bold border border-emerald-200'
                                                        : (cell.currentMonth ? 'text-slate-700 hover:bg-slate-100 font-medium' : 'text-slate-300 hover:bg-slate-50'))">
                                            <span x-text="cell.day"></span>
                                        </button>
                                    </template>
                                </div>

                                <!-- Time Picker Row -->
                                <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-bold text-slate-500 flex items-center gap-1">
                                        <i class="fa-regular fa-clock text-[#059669]"></i> Time
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <!-- Hour Custom Dropdown -->
                                        <div class="relative" x-data="{ hOpen: false }">
                                            <button type="button" @click="hOpen = !hOpen"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:border-[#059669] text-xs font-bold text-slate-800 cursor-pointer shadow-2xs">
                                                <span x-text="String(hour12).padStart(2, '0')"></span>
                                                <i class="fa-solid fa-chevron-down text-[8px] text-slate-400"></i>
                                            </button>
                                            <div x-show="hOpen" x-cloak @click.outside="hOpen = false"
                                                 class="absolute bottom-full left-0 mb-1 w-16 max-h-40 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-xl p-1 z-50">
                                                <template x-for="h in hoursList" :key="h">
                                                    <button type="button"
                                                            @click="hour12 = h; syncValue(); editInterview.scheduled_at = value; hOpen = false"
                                                            class="w-full px-2 py-1 text-xs rounded-lg text-center cursor-pointer transition-colors"
                                                            :class="hour12 == h ? 'bg-[#059669] text-white font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'"
                                                            x-text="String(h).padStart(2, '0')"></button>
                                                </template>
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-400">:</span>
                                        <!-- Minute Custom Dropdown -->
                                        <div class="relative" x-data="{ mOpen: false }">
                                            <button type="button" @click="mOpen = !mOpen"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:border-[#059669] text-xs font-bold text-slate-800 cursor-pointer shadow-2xs">
                                                <span x-text="minute"></span>
                                                <i class="fa-solid fa-chevron-down text-[8px] text-slate-400"></i>
                                            </button>
                                            <div x-show="mOpen" x-cloak @click.outside="mOpen = false"
                                                 class="absolute bottom-full left-0 mb-1 w-16 max-h-40 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-xl p-1 z-50">
                                                <template x-for="m in minutesList" :key="m">
                                                    <button type="button"
                                                            @click="minute = m; syncValue(); editInterview.scheduled_at = value; mOpen = false"
                                                            class="w-full px-2 py-1 text-xs rounded-lg text-center cursor-pointer transition-colors"
                                                            :class="minute === m ? 'bg-[#059669] text-white font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'"
                                                            x-text="m"></button>
                                                </template>
                                            </div>
                                        </div>
                                        <!-- AM/PM Toggle -->
                                        <div class="inline-flex rounded-lg bg-slate-100 p-0.5 border border-slate-200">
                                            <button type="button"
                                                    @click="ampm = 'AM'; syncValue(); editInterview.scheduled_at = value;"
                                                    :class="ampm === 'AM' ? 'bg-[#059669] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                                                    class="px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer">AM</button>
                                            <button type="button"
                                                    @click="ampm = 'PM'; syncValue(); editInterview.scheduled_at = value;"
                                                    :class="ampm === 'PM' ? 'bg-[#059669] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                                                    class="px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer">PM</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Actions -->
                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                    <button type="button" @click="setToday(); editInterview.scheduled_at = value;" class="text-[11px] font-bold text-[#059669] hover:underline cursor-pointer">
                                        Today
                                    </button>
                                    <button type="button" @click="open = false" class="px-3 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold transition-colors cursor-pointer">
                                        Done
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="relative" x-data="{ open: false }">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Interview Format *</label>
                            <input type="hidden" name="meeting_type" :value="editInterview.meeting_type">
                            <button type="button"
                                    @click="open = !open"
                                    class="w-full inline-flex items-center justify-between gap-2 px-3.5 py-2 text-xs sm:text-sm rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all cursor-pointer shadow-2xs">
                                <span class="flex items-center gap-2 truncate">
                                    <i class="text-xs" :class="editInterview.meeting_type === 'online' ? 'fa-solid fa-video text-indigo-600' : 'fa-solid fa-location-dot text-emerald-600'"></i>
                                    <span class="font-semibold text-slate-800 truncate" x-text="editInterview.meeting_type === 'online' ? 'Online Video Call' : 'In-Person at Office'"></span>
                                </span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200 shrink-0"
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
                                 class="absolute left-0 top-full z-50 mt-1.5 w-full rounded-2xl bg-white border border-slate-200 shadow-xl p-1.5 space-y-0.5"
                                 style="display: none;">
                                <button type="button"
                                        @click="editInterview.meeting_type = 'online'; open = false"
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                        :class="editInterview.meeting_type === 'online' ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid fa-video text-indigo-600 w-3.5 text-center"></i>
                                        <span>Online Video Call</span>
                                    </span>
                                    <i x-show="editInterview.meeting_type === 'online'" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                                </button>
                                <button type="button"
                                        @click="editInterview.meeting_type = 'in_person'; open = false"
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                        :class="editInterview.meeting_type === 'in_person' ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid fa-location-dot text-emerald-600 w-3.5 text-center"></i>
                                        <span>In-Person at Office</span>
                                    </span>
                                    <i x-show="editInterview.meeting_type === 'in_person'" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5"
                               x-text="editInterview.meeting_type === 'online' ? 'Meeting Link *' : 'Office Location *'"></label>
                        <input type="text" name="location_or_link" required
                               x-model="editInterview.location_or_link"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:border-[#059669]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Notes for Candidate</label>
                        <textarea name="notes" rows="3" x-model="editInterview.notes"
                                  class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:border-[#059669]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button type="button" @click="editModalOpen = false"
                                class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors cursor-pointer">
                            Save & Notify Candidate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL 3: COMPLETE & EVALUATE CANDIDATE                                    -->
        <!-- ========================================================================= -->
        <div x-show="evalModalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
             @keydown.escape.window="evalModalOpen = false">
            <div @click.away="evalModalOpen = false"
                 class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-xl border border-slate-200 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Post-Interview Evaluation</h3>
                            <p class="text-xs text-slate-500">
                                <span class="font-semibold text-slate-700" x-text="evalInterview.candidate_name"></span> •
                                <span x-text="evalInterview.role_title"></span>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="evalModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form method="POST" :action="evalInterview.status_url" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="completed">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Hiring Decision After Interview</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <label class="flex items-center gap-2 p-3 rounded-xl border text-xs font-bold cursor-pointer transition-all"
                                   :class="evalInterview.hiring_decision === 'keep_interviewed' ? 'border-indigo-500 bg-indigo-50/50 text-indigo-900' : 'border-slate-200 text-slate-600'">
                                <input type="radio" name="hiring_decision" value="keep_interviewed" x-model="evalInterview.hiring_decision" class="text-indigo-600">
                                <span>Undecided</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-xl border text-xs font-bold cursor-pointer transition-all"
                                   :class="evalInterview.hiring_decision === 'accepted' ? 'border-emerald-500 bg-emerald-50/60 text-emerald-900' : 'border-slate-200 text-slate-600'">
                                <input type="radio" name="hiring_decision" value="accepted" x-model="evalInterview.hiring_decision" class="text-emerald-600">
                                <span>Accept (Hire)</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-xl border text-xs font-bold cursor-pointer transition-all"
                                   :class="evalInterview.hiring_decision === 'rejected' ? 'border-rose-500 bg-rose-50/60 text-rose-900' : 'border-slate-200 text-slate-600'">
                                <input type="radio" name="hiring_decision" value="rejected" x-model="evalInterview.hiring_decision" class="text-rose-600">
                                <span>Reject</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Internal Evaluation / Company Notes</label>
                        <textarea name="company_notes" rows="3" x-model="evalInterview.company_notes"
                                  placeholder="Record technical assessment, communication skills, and team fit notes..."
                                  class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:border-[#059669]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button type="button" @click="evalModalOpen = false"
                                class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2 rounded-xl bg-[#059669] hover:bg-[#047857] text-white text-xs font-bold transition-colors cursor-pointer">
                            Save Evaluation
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layout>
