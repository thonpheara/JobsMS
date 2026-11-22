<x-layout>
    <div class="flex flex-col h-[calc(100vh-6rem)] sm:h-[calc(100vh-7rem)] lg:h-[calc(100vh-8rem)]">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 shrink-0">
            <div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Direct Candidate Messaging</h2>
                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">Communicate directly with applicants, coordinate interviews, and update hiring stages in real time.</p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                <a href="{{ route('company.interviews.index') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition-colors">
                    <i class="fa-solid fa-calendar-check text-slate-400"></i>
                    <span>Interviews</span>
                </a>
                <a href="{{ route('company.applicants.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-[#059669] hover:bg-[#047857] text-white shadow-xs transition-colors">
                    <i class="fa-solid fa-users text-xs"></i>
                    <span>View Applicants</span>
                </a>
            </div>
        </div>

        @if($conversations->isEmpty())
            <!-- Empty State -->
            <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-12 text-center max-w-lg mx-auto my-auto">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-comment text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900">No active conversations</h3>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    You can start a conversation directly with candidates by clicking the "Message Candidate" button on the Applicants or Interviews page.
                </p>
                <div class="mt-6">
                    <a href="{{ route('company.applicants.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#059669] text-white text-xs font-bold hover:bg-[#047857] shadow-xs transition-all">
                        <i class="fa-solid fa-users text-xs"></i>
                        <span>View Applicants</span>
                    </a>
                </div>
            </div>
        @else
            <!-- 2-Column Chat Workspace -->
            <div x-data="{
                    searchQuery: '',
                    filterMode: 'all',
                    mobileShowChat: {{ $activeConversation ? 'true' : 'false' }},
                    insertQuickReply(text) {
                        const input = document.getElementById('chat-input');
                        if (!input) return;
                        input.value = text;
                        input.dispatchEvent(new Event('input'));
                        input.focus();
                    },
                    matchesConvo(el) {
                        const unread = parseInt(el.dataset.unread || '0', 10);
                        const status = el.dataset.status || '';
                        const search = el.dataset.search || '';

                        if (this.filterMode === 'unread' && unread <= 0) return false;
                        if (this.filterMode === 'interviewing' && !['shortlisted', 'interviewed'].includes(status)) return false;
                        if (this.filterMode === 'accepted' && status !== 'accepted') return false;

                        if (this.searchQuery.trim() && !search.includes(this.searchQuery.toLowerCase().trim())) {
                            return false;
                        }
                        return true;
                    }
                 }"
                 class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden grid grid-cols-1 lg:grid-cols-12 flex-1 min-h-0">
                
                <!-- Left Column: Conversations List -->
                <div :class="mobileShowChat ? 'hidden lg:flex' : 'flex'"
                     class="lg:col-span-4 border-r border-slate-200/80 flex-col h-full min-h-0 bg-slate-50/50 overflow-hidden">
                    <div class="p-3.5 border-b border-slate-200/80 bg-white shrink-0 space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Conversations</span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-extrabold text-[11px] border border-emerald-100">
                                    {{ $conversations->count() }}
                                </span>
                            </div>
                        </div>

                        <!-- Filter Pills (All / Unread / Interviewing / Hired) -->
                        <div class="flex items-center gap-1 overflow-x-auto pb-0.5">
                            <button type="button"
                                    @click="filterMode = 'all'"
                                    :class="filterMode === 'all' ? 'bg-[#D1FAE5] text-[#065F46] font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70 font-medium'"
                                    class="px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer whitespace-nowrap">
                                All
                            </button>
                            <button type="button"
                                    @click="filterMode = 'unread'"
                                    :class="filterMode === 'unread' ? 'bg-[#D1FAE5] text-[#065F46] font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70 font-medium'"
                                    class="px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer whitespace-nowrap">
                                Unread
                            </button>
                            <button type="button"
                                    @click="filterMode = 'interviewing'"
                                    :class="filterMode === 'interviewing' ? 'bg-[#D1FAE5] text-[#065F46] font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70 font-medium'"
                                    class="px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer whitespace-nowrap">
                                Interviewing
                            </button>
                            <button type="button"
                                    @click="filterMode = 'accepted'"
                                    :class="filterMode === 'accepted' ? 'bg-[#D1FAE5] text-[#065F46] font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/70 font-medium'"
                                    class="px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer whitespace-nowrap">
                                Hired
                            </button>
                        </div>

                        <!-- Search Candidates / Role -->
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text"
                                   x-model="searchQuery"
                                   placeholder="Search candidate or role..."
                                   class="w-full pl-8 pr-8 py-1.5 rounded-xl border border-slate-200 bg-slate-50/70 focus:bg-white text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            <button type="button"
                                    x-show="searchQuery"
                                    @click="searchQuery = ''"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-600 cursor-pointer text-xs"
                                    title="Clear search">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div id="conversations-list" class="flex-1 min-h-0 overflow-y-auto divide-y divide-slate-100">
                        @foreach($conversations as $convo)
                            @php
                                $isActive = $activeConversation && $activeConversation->id === $convo->id;
                                $unreadCount = $convo->unreadCountFor(Auth::user());
                                $studentUser = $convo->studentProfile->user;
                                $studentName = $studentUser?->name ?? 'Candidate';
                                $studentAvatar = $studentUser?->avatar_url;
                                $studentHasAvatar = $studentUser?->has_avatar;
                                $studentInitials = $studentUser?->initials ?? 'ST';
                                $roleTitle = $convo->application?->internshipPost?->title ?? '';
                                $appStatus = $convo->application?->status ?? 'pending';
                                $searchIndex = strtolower($studentName . ' ' . $roleTitle);
                            @endphp
                            <a href="{{ route('company.messages.index', ['conversation_id' => $convo->id]) }}" 
                               data-convo-id="{{ $convo->id }}"
                               data-search="{{ $searchIndex }}"
                               data-unread="{{ $unreadCount }}"
                               data-status="{{ $appStatus }}"
                               x-show="matchesConvo($el)"
                               class="convo-item block p-3.5 transition-colors {{ $isActive ? 'bg-emerald-50/80 border-l-4 border-emerald-600' : 'hover:bg-slate-100/70' }}">
                                <div class="flex items-start gap-3">
                                    @if($studentHasAvatar && $studentAvatar)
                                        <img src="{{ $studentAvatar }}" alt="{{ $studentName }}" class="w-10 h-10 rounded-xl object-cover shrink-0 border border-slate-200 shadow-xs">
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-[#059669] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                                            {{ $studentInitials }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <h4 class="text-xs font-bold text-slate-900 truncate">{{ $studentName }}</h4>
                                            <span class="convo-time text-[10px] text-slate-500 shrink-0">
                                                {{ $convo->last_message_at ? $convo->last_message_at->diffForHumans(null, true, true) : '' }}
                                            </span>
                                        </div>
                                        @if($convo->application && $convo->application->internshipPost)
                                            <div class="flex items-center gap-1.5 mb-1">
                                                <span class="inline-block text-[10px] font-semibold text-emerald-700 bg-emerald-100/60 px-1.5 py-0.5 rounded truncate max-w-[160px]">
                                                    {{ $convo->application->internshipPost->title }}
                                                </span>
                                                @if($appStatus === 'accepted')
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-600 text-white shrink-0">Hired</span>
                                                @elseif($appStatus === 'interviewed')
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">Interview</span>
                                                @endif
                                            </div>
                                        @endif
                                        <p class="convo-preview text-xs text-slate-600 truncate">
                                            {{ $convo->latestMessage?->body ?? 'Start this conversation...' }}
                                        </p>
                                    </div>
                                    <span class="convo-unread w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] font-bold flex items-center justify-center shrink-0 {{ $unreadCount > 0 ? '' : 'hidden' }}">
                                        {{ $unreadCount }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Right Column: Active Chat Stream -->
                <div :class="mobileShowChat ? 'flex' : 'hidden lg:flex'"
                     class="lg:col-span-8 flex-col h-full min-h-0 bg-white overflow-hidden">
                    @if($activeConversation)
                        @php
                            $candidate = $activeConversation->studentProfile->user;
                            $candidateAvatar = $candidate?->avatar_url;
                            $candidateHasAvatar = $candidate?->has_avatar;
                            $candidateInitials = $candidate?->initials ?? 'ST';
                            $activeApp = $activeConversation->application;
                            $activeInterview = $activeApp?->interview;
                            $appResumePath = $activeApp?->getEffectiveResumePath();
                            $hasResume = $appResumePath && (\Illuminate\Support\Facades\Storage::disk('local')->exists($appResumePath) || \Illuminate\Support\Facades\Storage::disk('public')->exists($appResumePath));
                            $candFirstName = explode(' ', trim($candidate->name ?? 'there'))[0] ?: 'there';
                        @endphp

                        <!-- Chat Header -->
                        <div class="p-4 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 bg-white shrink-0">
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Mobile Back Button -->
                                <button type="button"
                                        @click="mobileShowChat = false"
                                        class="lg:hidden w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center shrink-0 cursor-pointer transition-colors"
                                        title="Back to Candidates">
                                    <i class="fa-solid fa-arrow-left text-xs"></i>
                                </button>

                                @if($candidateHasAvatar && $candidateAvatar)
                                    <img src="{{ $candidateAvatar }}" alt="{{ $candidate->name ?? 'Candidate' }}" class="w-10 h-10 rounded-xl object-cover shrink-0 border border-slate-200 shadow-xs">
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-[#059669] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ $candidateInitials }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-extrabold text-slate-900 truncate">{{ $candidate->name ?? 'Candidate' }}</h3>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>Real-time</span>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap text-[11px] text-slate-600 mt-0.5">
                                        <span>{{ $activeConversation->studentProfile->major ?? 'Student' }}</span>
                                        @if($activeConversation->studentProfile->gpa)
                                            <span>•</span>
                                            <span class="font-semibold text-slate-700">GPA {{ number_format((float) $activeConversation->studentProfile->gpa, 2) }}</span>
                                        @endif
                                        @if($activeApp && $activeApp->internshipPost)
                                            <span>•</span>
                                            <span class="font-semibold text-emerald-700 truncate">{{ $activeApp->internshipPost->title }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions & Custom Stage Changer Dropdown -->
                            <div class="flex items-center gap-2 flex-wrap shrink-0">
                                @if($activeConversation->student_profile_id)
                                    <a href="{{ route('company.students.show', $activeConversation->student_profile_id) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 border border-slate-200/80 text-xs font-bold transition-colors">
                                        <i class="fa-regular fa-user text-emerald-600 text-[11px]"></i>
                                        <span>Profile</span>
                                    </a>
                                @endif
                            @if($activeApp)
                                    @if($hasResume)
                                        <a href="{{ route('company.applicants.resume', $activeApp) }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 border border-slate-200/80 text-xs font-bold transition-colors">
                                            <i class="fa-solid fa-file-lines text-emerald-600 text-[11px]"></i>
                                            <span>CV</span>
                                        </a>
                                    @endif

                                    @if($activeApp->status !== 'withdrawn')
                                        <!-- Quick Candidacy Stage Changer Custom Dropdown -->
                                        <form method="POST" action="{{ route('company.applicants.update', $activeApp) }}" class="m-0">
                                            @csrf
                                            @method('PATCH')
                                            <div class="relative"
                                                 x-data="{
                                                     open: false,
                                                     currentStage: '{{ $activeApp->status }}',
                                                     stages: [
                                                         { value: 'pending', label: 'Pending Review', dot: 'bg-sky-500' },
                                                         { value: 'under_review', label: 'Under Review', dot: 'bg-purple-500' },
                                                         { value: 'shortlisted', label: 'Shortlisted', dot: 'bg-indigo-500' },
                                                         { value: 'interviewed', label: 'Interviewing', dot: 'bg-amber-500' },
                                                         { value: 'accepted', label: 'Accepted (Hire)', dot: 'bg-emerald-500' },
                                                         { value: 'rejected', label: 'Rejected', dot: 'bg-rose-500' }
                                                     ],
                                                     get activeStage() {
                                                         return this.stages.find(s => s.value === this.currentStage) || this.stages[0];
                                                     },
                                                     changeStage(val) {
                                                         this.currentStage = val;
                                                         this.open = false;
                                                         $nextTick(() => { $el.closest('form').submit(); });
                                                     }
                                                 }">
                                                <input type="hidden" name="status" :value="currentStage">
                                                <button type="button"
                                                        @click="open = !open"
                                                        class="inline-flex items-center justify-between gap-2 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] text-xs font-bold text-slate-800 transition-all cursor-pointer shadow-2xs">
                                                    <span class="flex items-center gap-1.5">
                                                        <span class="w-2 h-2 rounded-full" :class="activeStage.dot"></span>
                                                        <span x-text="activeStage.label"></span>
                                                    </span>
                                                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 transition-transform"
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
                                                     class="absolute right-0 top-full z-50 mt-1.5 w-48 rounded-2xl bg-white border border-slate-200 shadow-xl p-1.5 space-y-0.5"
                                                     style="display: none;">
                                                    <div class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                                        Update Stage
                                                    </div>
                                                    <template x-for="st in stages" :key="st.value">
                                                        <button type="button"
                                                                @click="changeStage(st.value)"
                                                                class="w-full flex items-center justify-between px-2.5 py-1.5 text-xs rounded-xl transition-all cursor-pointer text-left"
                                                                :class="currentStage === st.value ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                                            <span class="flex items-center gap-2">
                                                                <span class="w-2 h-2 rounded-full" :class="st.dot"></span>
                                                                <span x-text="st.label"></span>
                                                            </span>
                                                            <i x-show="currentStage === st.value" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </form>
                                    @endif
                            @endif
                            </div>
                        </div>

                        <!-- Pinned Interview Context Banner (When Candidate Has an Interview) -->
                        @if($activeInterview && $activeInterview->status !== 'cancelled')
                            @php
                                $isOnlineInterview = $activeInterview->isOnline();
                                $isMeetingUrl = filter_var($activeInterview->location_or_link, FILTER_VALIDATE_URL) !== false;
                                $isRescheduleReq = $activeInterview->isRescheduleRequested();
                                $isConfirmedInterview = $activeInterview->isConfirmed();
                            @endphp
                            <div class="px-4 py-2.5 border-b flex flex-wrap items-center justify-between gap-2.5 text-xs shrink-0
                                        {{ $isRescheduleReq ? 'bg-amber-50/90 border-amber-200 text-amber-900' : 'bg-indigo-50/60 border-indigo-100 text-slate-800' }}">
                                <div class="flex items-center gap-2.5 flex-wrap min-w-0">
                                    <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ $isRescheduleReq ? 'bg-amber-500 text-white' : 'bg-indigo-600 text-white' }}">
                                        <i class="fa-solid {{ $isRescheduleReq ? 'fa-clock-rotate-left' : 'fa-calendar-check' }} text-xs"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-slate-900">
                                                Interview: {{ $activeInterview->scheduled_at->format('D, M d, Y \a\t g:i A') }}
                                            </span>
                                            @if($isRescheduleReq)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200/80 text-amber-900">
                                                    Reschedule Requested
                                                </span>
                                            @elseif($isConfirmedInterview)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                    Confirmed by Student
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white text-slate-600 border border-slate-200">
                                                    Awaiting Confirmation
                                                </span>
                                            @endif
                                        </div>
                                        @if($isRescheduleReq && $activeInterview->reschedule_reason)
                                            <p class="text-[11px] text-amber-800 truncate mt-0.5">
                                                Reason: “{{ $activeInterview->reschedule_reason }}”
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    @if($isOnlineInterview && $isMeetingUrl)
                                        <a href="{{ $activeInterview->location_or_link }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#059669] hover:bg-[#047857] text-white text-[11px] font-bold transition-colors">
                                            <i class="fa-solid fa-video text-[10px]"></i>
                                            <span>Join Call</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('company.interviews.index', ['search' => $candidate->name ?? '']) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-[11px] font-bold transition-colors">
                                        <span>{{ $isRescheduleReq ? 'Respond' : 'Manage' }}</span>
                                        <i class="fa-solid fa-arrow-right text-[9px]"></i>
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Chat Messages History -->
                        <div id="message-container" data-active-convo-id="{{ $activeConversation->id }}" class="flex-1 min-h-0 p-6 overflow-y-auto space-y-4 bg-slate-50/40">
                            @php
                                $lastDateLabel = null;
                            @endphp
                            @forelse($messages as $msg)
                                @php
                                    $isMe = $msg->sender_id === Auth::id();
                                    $msgDate = $msg->created_at;
                                    $dateLabel = $msgDate->isToday()
                                        ? 'Today'
                                        : ($msgDate->isYesterday() ? 'Yesterday' : $msgDate->format('M d, Y'));

                                    // Safe URL auto-linking inside escaped message body
                                    $escapedBody = e($msg->body);
                                    $linkedBody = preg_replace(
                                        '~(https?://[^\s<]+)~i',
                                        '<a href="$1" target="_blank" rel="noopener noreferrer" class="underline font-semibold break-all">$1</a>',
                                        $escapedBody
                                    );
                                @endphp

                                @if($dateLabel !== $lastDateLabel)
                                    <div class="flex items-center justify-center my-2">
                                        <span class="px-3 py-0.5 rounded-full bg-slate-200/70 text-slate-600 text-[10px] font-bold">
                                            {{ $dateLabel }}
                                        </span>
                                    </div>
                                    @php $lastDateLabel = $dateLabel; @endphp
                                @endif

                                <div class="message-bubble flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-[75%] space-y-1">
                                        <div class="flex items-center gap-2 {{ $isMe ? 'justify-end' : 'justify-start' }} text-[10px] text-slate-600">
                                            <span class="font-semibold">{{ $isMe ? 'You (Host Recruiter)' : $msg->sender->name }}</span>
                                            <span>{{ $msg->created_at->format('h:i A') }}</span>
                                            @if($isMe)
                                                <span>•</span>
                                                <span class="{{ $msg->is_read ? 'text-emerald-600 font-bold' : 'text-slate-600' }}">
                                                    {{ $msg->is_read ? 'Read' : 'Sent' }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="p-3.5 rounded-2xl text-xs leading-relaxed whitespace-pre-line break-words {{ $isMe ? 'bg-emerald-600 text-white rounded-br-xs shadow-sm shadow-emerald-600/10' : 'bg-white border border-slate-200/90 text-slate-800 rounded-bl-xs shadow-xs' }}">{!! $linkedBody !!}</div>
                                    </div>
                                </div>
                            @empty
                                <div id="no-messages-placeholder" class="text-center py-16 text-slate-600 text-xs">
                                    <i class="fa-solid fa-comment-dots text-2xl mx-auto text-slate-300 mb-2 block"></i>
                                    <p class="font-semibold text-slate-700">No messages yet in this candidate conversation.</p>
                                    <p class="text-[11px] text-slate-500 mt-1">Use one of the quick replies below to start the conversation.</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Chat Input Box + Quick Replies -->
                        <div class="p-3.5 border-t border-slate-200/80 bg-white shrink-0 space-y-2.5">
                            <!-- Quick Reply Chips -->
                            <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 shrink-0 mr-1">Quick Replies:</span>
                                <button type="button"
                                        @click="insertQuickReply(@js('Hi ' . $candFirstName . ', we would love to invite you for an interview. Could you share your available times this week?'))"
                                        class="px-2.5 py-1 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200/70 text-[11px] font-semibold transition-colors cursor-pointer shrink-0">
                                    📅 Schedule Interview
                                </button>
                                @if($activeInterview && $activeInterview->location_or_link)
                                    <button type="button"
                                            @click="insertQuickReply(@js('Hi ' . $candFirstName . ', here is the meeting link/location for our scheduled interview on ' . $activeInterview->scheduled_at->format('M d \a\t g:i A') . ': ' . $activeInterview->location_or_link))"
                                            class="px-2.5 py-1 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200/70 text-[11px] font-semibold transition-colors cursor-pointer shrink-0">
                                        🔗 Send Interview Details
                                    </button>
                                @endif
                                <button type="button"
                                        @click="insertQuickReply(@js('Hi ' . $candFirstName . ', thank you for applying! Could you share your portfolio or GitHub link for our team to review?'))"
                                        class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200/70 text-slate-700 border border-slate-200/80 text-[11px] font-semibold transition-colors cursor-pointer shrink-0">
                                    📄 Request Portfolio
                                </button>
                                <button type="button"
                                        @click="insertQuickReply(@js('Hi ' . $candFirstName . ', congratulations! Following our review, we are pleased to offer you the position. Please let us know your confirmation.'))"
                                        class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200/70 text-slate-700 border border-slate-200/80 text-[11px] font-semibold transition-colors cursor-pointer shrink-0">
                                    🎉 Send Offer
                                </button>
                                <button type="button"
                                        @click="insertQuickReply(@js('Hi ' . $candFirstName . ', we are currently reviewing your application and will update you shortly.'))"
                                        class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200/70 text-slate-700 border border-slate-200/80 text-[11px] font-semibold transition-colors cursor-pointer shrink-0">
                                    ✅ Status Update
                                </button>
                            </div>

                            <form id="chat-form" action="{{ route('company.messages.store', $activeConversation) }}" method="POST" class="flex items-end gap-2.5">
                                @csrf
                                <div class="flex-1">
                                    <textarea id="chat-input"
                                              name="body"
                                              rows="1"
                                              required
                                              autocomplete="off"
                                              placeholder="Type your message to {{ $candidate->name ?? 'the candidate' }}... (Enter to send, Shift+Enter for new line)"
                                              class="w-full min-h-[44px] max-h-28 px-4 py-2.5 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 transition-all text-slate-900 placeholder:text-slate-400 resize-none leading-snug"></textarea>
                                </div>
                                <button type="submit" id="chat-submit-btn" class="h-[44px] px-5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white font-bold text-xs shadow-xs transition-all flex items-center justify-center gap-2 shrink-0 cursor-pointer">
                                    <span>Send</span>
                                    <i class="fa-solid fa-paper-plane text-xs"></i>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="flex-1 flex items-center justify-center p-8 text-center text-slate-600 text-xs">
                            Select a candidate conversation on the left to start messaging.
                        </div>
                    @endif
                </div>

            </div>
        @endif

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('message-container');
            const chatForm = document.getElementById('chat-form');
            const chatInput = document.getElementById('chat-input');
            const activeConvoId = container?.dataset.activeConvoId;
            const userIdMeta = document.querySelector('meta[name="user-id"]');
            const currentUserId = userIdMeta ? parseInt(userIdMeta.content, 10) : null;

            // Auto scroll chat to bottom
            const scrollToBottom = () => {
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            };
            scrollToBottom();

            function escapeAndLinkify(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.innerText = text;
                return div.innerHTML.replace(
                    /(https?:\/\/[^\s<]+)/gi,
                    '<a href="$1" target="_blank" rel="noopener noreferrer" class="underline font-semibold break-all">$1</a>'
                );
            }

            function appendBubble({ isMe, senderName, time, body, isRead }) {
                if (!container) return;
                const placeholder = document.getElementById('no-messages-placeholder');
                if (placeholder) placeholder.remove();

                const div = document.createElement('div');
                div.innerText = senderName || '';
                const safeSender = div.innerHTML;

                const bubble = document.createElement('div');
                bubble.className = `message-bubble flex ${isMe ? 'justify-end' : 'justify-start'} animate-fade-in`;
                bubble.innerHTML = `
                    <div class="max-w-[75%] space-y-1">
                        <div class="flex items-center gap-2 ${isMe ? 'justify-end' : 'justify-start'} text-[10px] text-slate-600">
                            <span class="font-semibold">${safeSender}</span>
                            <span>${time}</span>
                            ${isMe ? `
                                <span>•</span>
                                <span class="${isRead ? 'text-emerald-600 font-bold' : 'text-slate-600'}">
                                    ${isRead ? 'Read' : 'Sent'}
                                </span>
                            ` : ''}
                        </div>
                        <div class="p-3.5 rounded-2xl text-xs leading-relaxed whitespace-pre-line break-words ${isMe ? 'bg-emerald-600 text-white rounded-br-xs shadow-sm shadow-emerald-600/10' : 'bg-white border border-slate-200/90 text-slate-800 rounded-bl-xs shadow-xs'}">${escapeAndLinkify(body)}</div>
                    </div>
                `;
                container.appendChild(bubble);
                scrollToBottom();
            }

            function updateConvoSidebar(convoId, messageBody, timeText, incrementUnread = false) {
                const convoItem = document.querySelector(`.convo-item[data-convo-id="${convoId}"]`);
                if (convoItem) {
                    const preview = convoItem.querySelector('.convo-preview');
                    const time = convoItem.querySelector('.convo-time');
                    const unreadBadge = convoItem.querySelector('.convo-unread');

                    if (preview) preview.textContent = messageBody;
                    if (time) time.textContent = timeText;

                    if (incrementUnread && unreadBadge) {
                        let count = parseInt(unreadBadge.textContent.trim() || '0', 10);
                        count++;
                        unreadBadge.textContent = count;
                        unreadBadge.classList.remove('hidden');
                        convoItem.dataset.unread = String(count);
                    }

                    // Move to top of conversations list
                    const list = document.getElementById('conversations-list');
                    if (list && list.firstElementChild !== convoItem) {
                        list.prepend(convoItem);
                    }
                }
            }

            // Auto-resize textarea & Enter-to-send
            if (chatForm && chatInput) {
                const autoResizeInput = () => {
                    chatInput.style.height = 'auto';
                    chatInput.style.height = Math.min(chatInput.scrollHeight, 112) + 'px';
                };

                chatInput.addEventListener('input', autoResizeInput);
                chatInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        if (chatInput.value.trim()) {
                            chatForm.requestSubmit();
                        }
                    }
                });

                chatForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const text = chatInput.value.trim();
                    if (!text) return;

                    chatInput.value = '';
                    chatInput.style.height = 'auto';
                    chatInput.focus();

                    // Pre-render local bubble
                    const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    appendBubble({
                        isMe: true,
                        senderName: 'You (Host Recruiter)',
                        time: nowTime,
                        body: text,
                        isRead: false
                    });

                    // Update sidebar immediately
                    if (activeConvoId) {
                        updateConvoSidebar(activeConvoId, text, 'Just now', false);
                    }

                    try {
                        const socketId = window.Echo?.socketId();
                        const headers = {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        };
                        if (socketId) headers['X-Socket-ID'] = socketId;

                        await window.axios.post(chatForm.action, { body: text }, { headers });
                    } catch (err) {
                        console.error('Failed to transmit message:', err);
                    }
                });
            }

            // Real-Time WebSockets with Laravel Echo
            if (window.Echo) {
                // 1. Listen for messages on the active conversation channel
                if (activeConvoId) {
                    window.Echo.private(`conversation.${activeConvoId}`)
                        .listen('.message.sent', (e) => {
                            if (e.sender_id !== currentUserId) {
                                appendBubble({
                                    isMe: false,
                                    senderName: e.sender_name,
                                    time: e.created_at_time,
                                    body: e.body,
                                    isRead: false
                                });
                                updateConvoSidebar(e.conversation_id, e.body, 'Just now', false);
                            }
                        });
                }

                // 2. Listen for incoming messages across all candidate conversations
                if (currentUserId) {
                    window.Echo.private(`App.Models.User.${currentUserId}`)
                        .listen('.message.sent', (e) => {
                            if (String(e.conversation_id) !== String(activeConvoId)) {
                                updateConvoSidebar(e.conversation_id, e.body, 'Just now', true);
                            }
                        });
                }
            }
        });
    </script>
</x-layout>
