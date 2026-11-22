<x-layout>
    <!-- Script Component Definition for Applicant Review Board -->
    <script>
        function applicantPipeline() {
            let defaultMode = 'kanban';
            try {
                defaultMode = localStorage.getItem('applicant_view_mode') || 'kanban';
            } catch (e) {
                defaultMode = 'kanban';
            }

            return {
                viewMode: defaultMode,
                setViewMode(mode) {
                    this.viewMode = mode;
                    try {
                        localStorage.setItem('applicant_view_mode', mode);
                    } catch (e) {}
                    if (mode === 'table') {
                        const serverSort = @js(request('sort', 'latest'));
                        const serverOpp = @js(request('opportunity_type', 'all')) || 'all';
                        const serverSearch = @js(request('search', ''));
                        const serverReschedule = @js(request()->boolean('reschedule_only'));
                        if (this.sortBy !== serverSort || this.oppTypeFilter !== serverOpp || this.searchQuery !== serverSearch || this.rescheduleOnly !== serverReschedule) {
                            this.$nextTick(() => {
                                const form = document.getElementById('pipelineFilterForm');
                                if (form) form.submit();
                            });
                        }
                    }
                },
                syncTableIfNeeded() {
                    if (this.viewMode === 'table') {
                        this.$nextTick(() => {
                            const form = document.getElementById('pipelineFilterForm');
                            if (form) form.submit();
                        });
                    }
                },
                apps: @js($kanbanApplications),
                searchQuery: @js(request('search', '')),
                oppTypeFilter: @js(request('opportunity_type', 'all')),
                sortBy: @js(request('sort', 'latest')),
                rescheduleOnly: @js(request()->boolean('reschedule_only')),
                hideRejected: false,
                selectedIds: [],
                bulkStatus: 'shortlisted',
                bulkUpdating: false,
                selectableTableIds: @js($applications->filter(fn($a) => $a->status !== 'withdrawn')->pluck('id')->values()),
                get allTableSelected() {
                    return this.selectableTableIds.length > 0 && this.selectableTableIds.every(id => this.selectedIds.includes(id));
                },
                toggleSelectAll() {
                    this.selectedIds = this.allTableSelected ? [] : [...this.selectableTableIds];
                },
                toggleSelectOne(id) {
                    if (this.selectedIds.includes(id)) {
                        this.selectedIds = this.selectedIds.filter(i => i !== id);
                    } else {
                        this.selectedIds = [...this.selectedIds, id];
                    }
                },
                async applyBulkStatus() {
                    if (this.selectedIds.length === 0 || this.bulkUpdating) return;
                    this.bulkUpdating = true;
                    try {
                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
                        const response = await fetch(@js(route('company.applicants.bulk-status')), {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                application_ids: this.selectedIds,
                                status: this.bulkStatus
                            })
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Bulk update failed.');
                        }
                        this.showToast(result.message || 'Candidates updated successfully.', 'success');
                        setTimeout(() => window.location.reload(), 450);
                    } catch (err) {
                        this.showToast(err.message || 'Failed to update selected candidates.', 'error');
                        this.bulkUpdating = false;
                    }
                },
                draggedAppId: null,
                dragOverColumn: null,
                updatingAppId: null,
                showWithdrawn: false,
                toast: { show: false, message: '', type: 'success' },
                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => { this.toast.show = false; }, 3500);
                },
                columns: [
                    { id: 'pending', label: 'Applied', color: 'sky', dot: 'bg-sky-500', headerBg: 'bg-sky-50/80 text-sky-900 border-sky-200', countBadge: 'bg-sky-100 text-sky-800', icon: 'fa-solid fa-inbox' },
                    { id: 'under_review', label: 'Under Review', color: 'purple', dot: 'bg-purple-500', headerBg: 'bg-purple-50/80 text-purple-900 border-purple-200', countBadge: 'bg-purple-100 text-purple-800', icon: 'fa-solid fa-magnifying-glass' },
                    { id: 'shortlisted', label: 'Shortlisted', color: 'indigo', dot: 'bg-indigo-500', headerBg: 'bg-indigo-50/80 text-indigo-900 border-indigo-200', countBadge: 'bg-indigo-100 text-indigo-800', icon: 'fa-solid fa-star' },
                    { id: 'interviewed', label: 'Interviewed', color: 'amber', dot: 'bg-amber-500', headerBg: 'bg-amber-50/80 text-amber-900 border-amber-200', countBadge: 'bg-amber-100 text-amber-800', icon: 'fa-solid fa-comments' },
                    { id: 'accepted', label: 'Accepted', color: 'emerald', dot: 'bg-emerald-500', headerBg: 'bg-emerald-50/80 text-emerald-900 border-emerald-200', countBadge: 'bg-emerald-100 text-emerald-800', icon: 'fa-solid fa-circle-check' },
                    { id: 'rejected', label: 'Rejected', color: 'rose', dot: 'bg-rose-500', headerBg: 'bg-rose-50/80 text-rose-900 border-rose-200', countBadge: 'bg-rose-100 text-rose-800', icon: 'fa-solid fa-circle-xmark' },
                ],
                get visibleColumns() {
                    return this.hideRejected ? this.columns.filter(c => c.id !== 'rejected') : this.columns;
                },
                get filteredApps() {
                    let list = this.apps;

                    if (this.oppTypeFilter === 'internship') {
                        list = list.filter(a => a.is_internship);
                    } else if (this.oppTypeFilter === 'job') {
                        list = list.filter(a => !a.is_internship);
                    }

                    if (this.rescheduleOnly) {
                        list = list.filter(a => a.interview && a.interview.status === 'reschedule_requested');
                    }

                    if (this.searchQuery && this.searchQuery.trim() !== '') {
                        const q = this.searchQuery.toLowerCase().trim();
                        list = list.filter(a => {
                            const name = (a.candidate_name || '').toLowerCase();
                            const major = (a.major || '').toLowerCase();
                            const role = (a.role_title || '').toLowerCase();
                            const skills = (a.skills || []).join(' ').toLowerCase();
                            return name.includes(q) || major.includes(q) || role.includes(q) || skills.includes(q);
                        });
                    }

                    if (this.sortBy === 'gpa_desc') {
                        list = [...list].sort((a, b) => (b.gpa_numeric || 0) - (a.gpa_numeric || 0));
                    } else {
                        list = [...list].sort((a, b) => (b.applied_timestamp || 0) - (a.applied_timestamp || 0));
                    }

                    return list;
                },
                getAppsByStatus(status) {
                    return this.filteredApps.filter(a => a.status === status);
                },
                getWithdrawnApps() {
                    return this.filteredApps.filter(a => a.status === 'withdrawn');
                },
                onDragStart(event, app) {
                    if (app.is_withdrawn) {
                        event.preventDefault();
                        return;
                    }
                    this.draggedAppId = app.id;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', String(app.id));
                },
                onDragEnd() {
                    this.draggedAppId = null;
                    this.dragOverColumn = null;
                },
                onDragOver(event, colId) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                    this.dragOverColumn = colId;
                },
                onDragLeave(event, colId) {
                    if (event.currentTarget && event.relatedTarget && event.currentTarget.contains(event.relatedTarget)) {
                        return;
                    }
                    if (this.dragOverColumn === colId) {
                        this.dragOverColumn = null;
                    }
                },
                async onDrop(event, targetStatus) {
                    event.preventDefault();
                    this.dragOverColumn = null;
                    const appId = this.draggedAppId || parseInt(event.dataTransfer.getData('text/plain'), 10);
                    this.draggedAppId = null;

                    if (!appId) return;
                    const app = this.apps.find(a => a.id === appId);
                    if (!app || app.status === targetStatus || app.is_withdrawn) return;

                    const previousStatus = app.status;
                    app.status = targetStatus;
                    this.updatingAppId = app.id;

                    try {
                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
                        const response = await fetch(app.update_url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                status: targetStatus,
                                company_notes: app.company_notes
                            })
                        });

                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to update candidate status.');
                        }

                        this.showToast(result.message || `Moved to ${targetStatus.replace('_', ' ')}`, 'success');
                    } catch (err) {
                        app.status = previousStatus;
                        this.showToast(err.message || 'Failed to update status. Reverting changes.', 'error');
                    } finally {
                        this.updatingAppId = null;
                    }
                },
                statusModalOpen: false,
                statusApp: {
                    id: '',
                    candidateName: '',
                    roleTitle: '',
                    status: 'pending',
                    companyNotes: '',
                    updateUrl: '',
                    hasResume: false,
                    resumeFileName: '',
                    resumePreviewUrl: '',
                    resumeDownloadUrl: ''
                },
                openStatusModal(data) {
                    this.statusApp = { ...data };
                    this.statusModalOpen = true;
                },

                // Feature 2.4: Interview Scheduling Modal State
                interviewModalOpen: false,
                interviewApp: {
                    id: '',
                    candidateName: '',
                    roleTitle: '',
                    storeUrl: '',
                    scheduledAt: '',
                    meetingType: 'online',
                    locationOrLink: '',
                    notes: '',
                    status: '',
                    rescheduleReason: '',
                    messageUrl: ''
                },
                openInterviewModal(app) {
                    this.interviewApp = {
                        id: app.id,
                        candidateName: app.candidate_name,
                        roleTitle: app.role_title,
                        storeUrl: app.interview_store_url,
                        scheduledAt: app.interview ? app.interview.scheduled_at : '',
                        meetingType: app.interview ? app.interview.meeting_type : 'online',
                        locationOrLink: app.interview ? app.interview.location_or_link : '',
                        notes: app.interview ? app.interview.notes : '',
                        status: app.interview ? app.interview.status : '',
                        rescheduleReason: app.interview ? (app.interview.reschedule_reason || '') : '',
                        messageUrl: app.message_url || ''
                    };
                    this.interviewModalOpen = true;
                }
            };
        }

        if (typeof Alpine !== 'undefined') {
            Alpine.data('applicantPipeline', applicantPipeline);
        } else {
            document.addEventListener('alpine:init', () => {
                Alpine.data('applicantPipeline', applicantPipeline);
            });
        }
    </script>

    <div class="flex flex-col min-h-[640px]"
         :class="viewMode === 'kanban' ? 'h-[calc(100vh-6rem)] sm:h-[calc(100vh-7rem)] lg:h-[calc(100vh-8rem)]' : ''"
         x-data="applicantPipeline()">

        <!-- Page Header & View Switcher -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 shrink-0">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900">Applicant Pipeline</h2>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/80">
                        <i class="fa-solid fa-users text-[10px] text-emerald-600"></i>
                        <span x-text="filteredApps.length"></span> Candidates
                    </span>
                </div>
                <p class="text-xs text-slate-600 mt-0.5">Drag candidate cards across hiring stages or switch to table view for structured management.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto shrink-0">
                <!-- Hide Rejected Column Toggle (Kanban Only) -->
                <button type="button"
                        x-show="viewMode === 'kanban'"
                        @click="hideRejected = !hideRejected"
                        :class="hideRejected ? 'bg-rose-50 text-rose-700 border-rose-200 font-bold' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200 font-medium'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs transition-all cursor-pointer shadow-2xs"
                        title="Toggle Rejected column visibility to fit active stages on screen">
                    <i class="fa-solid" :class="hideRejected ? 'fa-eye-slash text-rose-500' : 'fa-eye text-slate-400'"></i>
                    <span x-text="hideRejected ? 'Rejected Hidden' : 'Hide Rejected'"></span>
                </button>

                <!-- View Switcher (Kanban vs Table) -->
                <div class="inline-flex p-1 bg-slate-100 rounded-2xl border border-slate-200/90 shadow-2xs">
                    <button type="button" 
                            @click="setViewMode('kanban')"
                            :class="viewMode === 'kanban' ? 'bg-white text-emerald-800 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                        <i class="fa-solid fa-table-columns text-xs text-emerald-600"></i>
                        <span>Kanban Board</span>
                    </button>
                    <button type="button" 
                            @click="setViewMode('table')"
                            :class="viewMode === 'table' ? 'bg-white text-emerald-800 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                        <i class="fa-solid fa-list text-xs text-slate-500"></i>
                        <span>Table List</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Unified Search, Type Filter, Sort & Dropdown Toolbar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-2.5 mb-3 shrink-0 flex flex-col xl:flex-row xl:items-center justify-between gap-2.5">
            
            <!-- Left: Instant Search + Opportunity Type Pills -->
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <!-- Instant Candidate Search -->
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           @input.debounce.500ms="syncTableIfNeeded()"
                           @keydown.enter.prevent="syncTableIfNeeded()"
                           placeholder="Search name, major, skill, or role..." 
                           class="w-full pl-8 pr-8 py-1.5 rounded-xl border border-slate-200 bg-slate-50/60 focus:bg-white text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    <button type="button" 
                            x-show="searchQuery" 
                            @click="searchQuery = ''; syncTableIfNeeded()" 
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-600 cursor-pointer text-xs"
                            title="Clear search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Opportunity Type Pills -->
                <div class="inline-flex items-center gap-1 bg-slate-100/90 p-1 rounded-xl border border-slate-200/60">
                    <button type="button" 
                            @click="oppTypeFilter = 'all'; syncTableIfNeeded()" 
                            :class="oppTypeFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer">
                        All
                    </button>
                    <button type="button" 
                            @click="oppTypeFilter = 'internship'; syncTableIfNeeded()" 
                            :class="oppTypeFilter === 'internship' ? 'bg-white text-emerald-800 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer inline-flex items-center gap-1">
                        <span>🎓</span><span>Interns</span>
                    </button>
                    <button type="button" 
                            @click="oppTypeFilter = 'job'; syncTableIfNeeded()" 
                            :class="oppTypeFilter === 'job' ? 'bg-white text-indigo-800 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer inline-flex items-center gap-1">
                        <span>💼</span><span>Jobs</span>
                    </button>
                </div>

                <!-- Sort Selector (Latest vs Highest GPA) -->
                <div class="inline-flex items-center gap-1 bg-slate-100/90 p-1 rounded-xl border border-slate-200/60">
                    <button type="button" 
                            @click="sortBy = 'latest'; syncTableIfNeeded()" 
                            :class="sortBy === 'latest' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer inline-flex items-center gap-1">
                        <i class="fa-regular fa-clock text-[10px]"></i><span>Latest</span>
                    </button>
                    <button type="button" 
                            @click="sortBy = 'gpa_desc'; syncTableIfNeeded()" 
                            :class="sortBy === 'gpa_desc' ? 'bg-white text-emerald-800 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-2.5 py-1 rounded-lg text-[11px] transition-all cursor-pointer inline-flex items-center gap-1">
                        <i class="fa-solid fa-arrow-up-wide-short text-[10px]"></i><span>Top GPA</span>
                    </button>
                </div>

                <!-- Reschedule Requests Alert Filter Pill -->
                @if (($rescheduleCount ?? 0) > 0 || request()->boolean('reschedule_only'))
                    <button type="button"
                            @click="rescheduleOnly = !rescheduleOnly; syncTableIfNeeded()"
                            :class="rescheduleOnly ? 'bg-amber-500 text-white border-amber-500 shadow-xs font-bold' : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100 font-semibold'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-[11px] transition-all cursor-pointer shrink-0"
                            title="Filter candidates requesting interview reschedule">
                        <i class="fa-solid fa-clock-rotate-left text-[10px]"></i>
                        <span>Reschedules</span>
                        <span :class="rescheduleOnly ? 'bg-white/25 text-white' : 'bg-amber-200/80 text-amber-900'"
                              class="px-1.5 py-0.2 rounded-full text-[10px] font-black leading-tight">{{ $rescheduleCount ?? 0 }}</span>
                    </button>
                @endif
            </div>

            <!-- Right: Server Filter Form (Job Post + Stage) -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <!-- Filter Form -->
                <form id="pipelineFilterForm" action="{{ route('company.applicants.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="search" :value="searchQuery">
                    <input type="hidden" name="opportunity_type" :value="oppTypeFilter === 'all' ? '' : oppTypeFilter">
                    <input type="hidden" name="sort" :value="sortBy">
                    <input type="hidden" name="reschedule_only" :value="rescheduleOnly ? '1' : ''">
                    <!-- Job Post Filter Dropdown -->
                    <div class="relative"
                         x-data="{
                             open: false,
                             postId: '{{ request('post_id', '') }}',
                             postTitle: '{{ $companyPosts->firstWhere('id', request('post_id'))?->title ?? 'All Job Listings' }}',
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
                                class="inline-flex items-center justify-between gap-2.5 py-2 pl-3 pr-2.5 text-xs rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] font-medium text-slate-700 transition-all cursor-pointer shadow-2xs max-w-[220px]">
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
                            @foreach ($companyPosts as $p)
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

                    <!-- Candidacy Stage Filter Dropdown -->
                    <div class="relative"
                         x-data="{
                             open: false,
                             statusVal: '{{ request('status', '') }}',
                             options: [
                                 { value: '', label: 'All Candidacy Stages', dot: 'bg-slate-400' },
                                 { value: 'pending', label: 'Pending Review', dot: 'bg-sky-500 ring-2 ring-sky-100' },
                                 { value: 'under_review', label: 'Under Review', dot: 'bg-purple-500 ring-2 ring-purple-100' },
                                 { value: 'shortlisted', label: 'Shortlisted', dot: 'bg-indigo-500 ring-2 ring-indigo-100' },
                                 { value: 'interviewed', label: 'Interviewed', dot: 'bg-amber-500 ring-2 ring-amber-100' },
                                 { value: 'accepted', label: 'Accepted (Hired)', dot: 'bg-emerald-500 ring-2 ring-emerald-100' },
                                 { value: 'rejected', label: 'Rejected', dot: 'bg-rose-500 ring-2 ring-rose-100' },
                                 { value: 'withdrawn', label: 'Withdrawn', dot: 'bg-slate-400 ring-2 ring-slate-100' }
                             ],
                             get currentOption() {
                                 return this.options.find(o => o.value === this.statusVal) || this.options[0];
                             },
                             selectStatus(val) {
                                 this.statusVal = val;
                                 this.open = false;
                                 $nextTick(() => {
                                     $el.closest('form').submit();
                                 });
                             }
                         }">
                        <input type="hidden" name="status" :value="statusVal">
                        <button type="button" 
                                @click="open = !open" 
                                class="inline-flex items-center justify-between gap-2.5 py-2 pl-3 pr-2.5 text-xs rounded-xl bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#059669] focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] font-medium text-slate-700 transition-all cursor-pointer shadow-2xs">
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
                             class="absolute right-0 sm:left-0 top-full z-40 mt-1.5 w-52 rounded-2xl bg-white border border-slate-200 shadow-xl p-1.5 space-y-0.5"
                             style="display: none;">
                            <template x-for="item in options" :key="item.value">
                                <button type="button" 
                                        @click="selectStatus(item.value)" 
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                        :class="statusVal === item.value ? 'bg-emerald-50 text-[#065F46] font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="item.dot"></span>
                                        <span x-text="item.label"></span>
                                    </div>
                                    <i x-show="statusVal === item.value" class="fa-solid fa-check text-[10px] text-[#059669]"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- VIEW 1: KANBAN PIPELINE BOARD                                             -->
        <!-- ========================================================================= -->
        <div x-show="viewMode === 'kanban'" 
             class="flex-1 min-h-0 flex flex-col overflow-hidden">

            <!-- Horizontal Columns Container -->
            <div class="flex-1 min-h-0 overflow-x-auto overflow-y-hidden pb-2 select-none">
                <div class="inline-flex min-w-full h-full gap-3.5 items-stretch p-0.5">

                    <!-- Kanban Columns Loop -->
                    <template x-for="col in visibleColumns" :key="col.id">
                        <div class="w-72 min-w-[276px] max-w-[276px] flex flex-col h-full rounded-3xl border transition-all duration-200 overflow-hidden"
                             :class="dragOverColumn === col.id 
                                 ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/25 shadow-md' 
                                 : 'border-slate-200/80 bg-slate-100/70 shadow-2xs'"
                             @dragover.prevent="onDragOver($event, col.id)"
                             @dragleave="onDragLeave($event, col.id)"
                             @drop="onDrop($event, col.id)">

                            <!-- Column Header -->
                            <div class="px-3.5 py-2.5 border-b flex items-center justify-between shrink-0"
                                 :class="col.headerBg">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="col.dot"></span>
                                    <i :class="col.icon" class="text-xs shrink-0 opacity-80"></i>
                                    <h3 class="font-extrabold text-xs tracking-tight truncate" x-text="col.label"></h3>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-black shrink-0 shadow-2xs"
                                      :class="col.countBadge"
                                      x-text="getAppsByStatus(col.id).length"></span>
                            </div>

                            <!-- Column Body / Droppable Cards Container -->
                            <div class="flex-1 min-h-0 overflow-y-auto p-2.5 space-y-2">
                                
                                <!-- Empty Column Placeholder -->
                                <div x-show="getAppsByStatus(col.id).length === 0" 
                                     class="h-32 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center text-center p-3 transition-colors"
                                     :class="dragOverColumn === col.id ? 'border-emerald-400 bg-emerald-100/40 text-emerald-700' : 'text-slate-400'">
                                    <i class="fa-solid fa-arrow-down-to-line text-base mb-1 opacity-60"></i>
                                    <span class="text-[11px] font-semibold" x-text="dragOverColumn === col.id ? 'Drop candidate here' : 'No candidates'"></span>
                                    <span class="text-[10px] opacity-75 mt-0.5" x-text="dragOverColumn === col.id ? 'Release to update' : 'Drag card here'"></span>
                                </div>

                                <!-- Candidate Cards (Compact Density) -->
                                <template x-for="app in getAppsByStatus(col.id)" :key="app.id">
                                    <div :draggable="!app.is_withdrawn ? 'true' : 'false'"
                                         @dragstart="onDragStart($event, app)"
                                         @dragend="onDragEnd()"
                                         class="bg-white rounded-2xl p-3 border border-slate-200/90 shadow-2xs transition-all duration-150 relative group"
                                         :class="{
                                             'opacity-40 scale-95 border-emerald-500 border-dashed ring-2 ring-emerald-500/20': draggedAppId === app.id,
                                             'hover:shadow-md hover:border-emerald-300 hover:-translate-y-0.5 cursor-grab active:cursor-grabbing': !app.is_withdrawn,
                                             'opacity-60 bg-slate-50 border-dashed cursor-not-allowed': app.is_withdrawn
                                         }">

                                        <!-- Card Top: Candidate Info -->
                                        <div class="flex items-start gap-2.5">
                                            <!-- Candidate Avatar / Initials -->
                                            <div class="w-8 h-8 rounded-xl text-white flex items-center justify-center font-bold text-[11px] shrink-0 shadow-2xs overflow-hidden bg-[#059669]">
                                                <template x-if="app.avatar_url">
                                                    <img :src="app.avatar_url" :alt="app.candidate_name" draggable="false" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!app.avatar_url">
                                                    <span x-text="app.candidate_initials"></span>
                                                </template>
                                            </div>

                                            <!-- Name, Major & GPA -->
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between gap-1">
                                                    <a :href="app.profile_url"
                                                       @click.stop
                                                       class="font-extrabold text-slate-900 text-xs truncate hover:text-emerald-700 hover:underline transition-colors"
                                                       x-text="app.candidate_name"></a>
                                                    <span class="px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-[10px] font-black shrink-0" 
                                                          x-text="'GPA ' + app.gpa"></span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 truncate" x-text="app.major"></p>
                                            </div>
                                        </div>

                                        <!-- Card Middle: Opportunity Badge, Role & Applied Date -->
                                        <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-1.5 text-[11px]">
                                            <div class="flex items-center gap-1.5 min-w-0" :title="app.role_title">
                                                <span x-show="app.is_internship" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">🎓</span>
                                                <span x-show="!app.is_internship" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">💼</span>
                                                <span class="truncate text-slate-700 font-bold" x-text="app.role_title"></span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 shrink-0" x-text="app.applied_human"></span>
                                        </div>

                                        <!-- Skills Chips -->
                                        <div x-show="app.skills && app.skills.length > 0" class="flex flex-wrap gap-1 mt-1.5">
                                            <template x-for="skill in app.skills" :key="skill">
                                                <span class="px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-semibold truncate max-w-[110px]" 
                                                      x-text="skill"></span>
                                            </template>
                                        </div>

                                        <!-- Recruiter Note Pill (if present) -->
                                        <div x-show="app.company_notes" class="mt-1.5 text-[10px] text-emerald-800 font-medium bg-emerald-50/80 px-2 py-1 rounded-xl border border-emerald-200/60 truncate" :title="app.company_notes">
                                            <i class="fa-solid fa-note-sticky text-emerald-600 mr-1"></i>
                                            <span x-text="app.company_notes"></span>
                                        </div>

                                        <!-- Scheduled Interview Info Chip (Feature 2.4) -->
                                        <div x-show="app.has_interview" 
                                             class="mt-2 text-[10px] font-semibold px-2 py-1.5 rounded-xl border flex items-center justify-between gap-1.5"
                                             :class="{
                                                 'bg-indigo-50/90 text-indigo-900 border-indigo-200/80': app.interview && app.interview.status === 'scheduled',
                                                 'bg-emerald-50/90 text-emerald-900 border-emerald-200/80': app.interview && app.interview.status === 'confirmed',
                                                 'bg-amber-50/90 text-amber-900 border-amber-200/80': app.interview && app.interview.status === 'reschedule_requested',
                                                 'bg-slate-50 text-slate-700 border-slate-200': app.interview && (app.interview.status === 'completed' || app.interview.status === 'cancelled')
                                             }">
                                            <span class="truncate flex items-center gap-1.5">
                                                <i class="fa-solid fa-calendar-days text-[10px] text-indigo-600"></i>
                                                <span x-text="app.interview ? app.interview.scheduled_human : ''"></span>
                                            </span>
                                            <span class="capitalize text-[9px] font-bold px-1.5 py-0.5 rounded-md"
                                                  :class="{
                                                      'bg-indigo-100 text-indigo-800': app.interview && app.interview.status === 'scheduled',
                                                      'bg-emerald-100 text-emerald-800': app.interview && app.interview.status === 'confirmed',
                                                      'bg-amber-100 text-amber-800': app.interview && app.interview.status === 'reschedule_requested',
                                                      'bg-slate-200 text-slate-700': app.interview && (app.interview.status === 'completed' || app.interview.status === 'cancelled')
                                                  }"
                                                  x-text="app.interview ? app.interview.status.replace('_', ' ') : ''"></span>
                                        </div>

                                        <!-- Candidate Reschedule Request Reason Snippet (Feature 2.4) -->
                                        <div x-show="app.interview && app.interview.status === 'reschedule_requested' && app.interview.reschedule_reason" 
                                             class="mt-2 p-2.5 rounded-xl bg-amber-50/95 border border-amber-200/90 text-[11px] text-amber-950">
                                            <div class="flex items-center justify-between gap-1 text-[10px] font-black uppercase text-amber-800">
                                                <span class="flex items-center gap-1">
                                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                                    <span>Reschedule Note:</span>
                                                </span>
                                                <button type="button" 
                                                        @click="openInterviewModal(app)" 
                                                        class="text-amber-800 hover:text-amber-950 underline font-bold cursor-pointer">
                                                    Propose Time
                                                </button>
                                            </div>
                                            <p class="mt-1 text-slate-800 italic leading-snug line-clamp-3 bg-white/80 p-2 rounded-lg border border-amber-200/50" :title="app.interview ? app.interview.reschedule_reason : ''">
                                                “<span x-text="app.interview ? app.interview.reschedule_reason : ''"></span>”
                                            </p>
                                            <div class="mt-1.5 flex items-center justify-between text-[10px]">
                                                <a :href="app.message_url" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1">
                                                    <i class="fa-solid fa-comment-dots"></i>
                                                    <span>Reply in Chat</span>
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Card Bottom Actions Toolbar -->
                                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                                            <!-- Resume Action Buttons -->
                                            <div class="flex items-center gap-1">
                                                <template x-if="app.has_resume">
                                                    <div class="inline-flex items-center gap-1">
                                                        <template x-if="app.resume_preview_url">
                                                            <a :href="app.resume_preview_url" 
                                                               target="_blank"
                                                               class="w-7 h-7 flex items-center justify-center text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors border border-slate-200/70"
                                                               :title="'Preview ' + app.resume_name">
                                                                <i class="fa-solid fa-file-lines text-[11px] text-emerald-600"></i>
                                                            </a>
                                                        </template>
                                                        <a :href="app.resume_download_url" 
                                                           :download="app.resume_name"
                                                           class="w-7 h-7 flex items-center justify-center text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors border border-slate-200/70"
                                                           :title="'Download ' + app.resume_name">
                                                            <i class="fa-solid fa-download text-[10px]"></i>
                                                        </a>
                                                    </div>
                                                </template>
                                                <template x-if="!app.has_resume">
                                                    <span class="w-7 h-7 flex items-center justify-center text-slate-300 rounded-lg border border-dashed border-slate-200 text-[10px]" title="No resume document attached">
                                                        <i class="fa-solid fa-file-excel"></i>
                                                    </span>
                                                </template>
                                            </div>

                                            <!-- Candidate Actions & Drag Handle -->
                                            <div class="flex items-center gap-1">
                                                <!-- Direct Message Chat Link -->
                                                <a :href="app.message_url" 
                                                   class="w-7 h-7 flex items-center justify-center text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors border border-slate-200/70"
                                                   title="Chat with candidate">
                                                    <i class="fa-solid fa-comment-dots text-[11px]"></i>
                                                </a>

                                                <!-- Schedule / Manage Interview Button (Feature 2.4) -->
                                                <button type="button"
                                                        @click="openInterviewModal(app)"
                                                        :class="app.has_interview ? 'text-indigo-600 bg-indigo-50 border-indigo-200 hover:bg-indigo-100' : 'text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 border-slate-200/70'"
                                                        class="w-7 h-7 flex items-center justify-center rounded-lg transition-colors border cursor-pointer"
                                                        :title="app.has_interview ? 'Manage Interview (' + (app.interview ? app.interview.status : '') + ')' : 'Schedule Interview'">
                                                    <i class="fa-solid fa-calendar-check text-[11px]"></i>
                                                </button>

                                                <!-- Status Update Modal Trigger -->
                                                <button type="button"
                                                        @click="openStatusModal({
                                                            id: app.id,
                                                            candidateName: app.candidate_name,
                                                            roleTitle: app.role_title,
                                                            status: app.status,
                                                            companyNotes: app.company_notes,
                                                            updateUrl: app.update_url,
                                                            hasResume: app.has_resume,
                                                            resumeFileName: app.resume_name,
                                                            resumePreviewUrl: app.resume_preview_url,
                                                            resumeDownloadUrl: app.resume_download_url
                                                        })"
                                                        class="w-7 h-7 flex items-center justify-center text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors border border-slate-200/70 cursor-pointer"
                                                        title="Update candidacy stage">
                                                    <i class="fa-solid fa-sliders text-[11px]"></i>
                                                </button>

                                                <!-- Drag Handle Visual Indicator (Drag or Click to Move) -->
                                                <div class="w-6 h-7 flex items-center justify-center text-slate-400 hover:text-emerald-600 transition-colors ml-0.5 cursor-grab active:cursor-grabbing rounded-lg hover:bg-slate-100"
                                                     title="Drag card to move between stages (or click to change stage)"
                                                     @click="if (!app.is_withdrawn) openStatusModal({
                                                         id: app.id,
                                                         candidateName: app.candidate_name,
                                                         roleTitle: app.role_title,
                                                         status: app.status,
                                                         companyNotes: app.company_notes,
                                                         updateUrl: app.update_url,
                                                         hasResume: app.has_resume,
                                                         resumeFileName: app.resume_name,
                                                         resumePreviewUrl: app.resume_preview_url,
                                                         resumeDownloadUrl: app.resume_download_url
                                                     })">
                                                    <i class="fa-solid fa-grip-vertical text-xs"></i>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </template>
                            </div>

                        </div>
                    </template>

                </div>
            </div>

            <!-- Optional Withdrawn Candidates Footer Notice -->
            <div x-show="getWithdrawnApps().length > 0" 
                 class="mt-2 shrink-0 bg-slate-100/90 border border-slate-200/80 rounded-2xl px-4 py-2.5 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2 text-slate-600">
                    <i class="fa-solid fa-ban text-slate-400"></i>
                    <span>Notice: </span>
                    <strong class="text-slate-800" x-text="getWithdrawnApps().length + ' candidates have withdrawn their applications.'"></strong>
                </div>
                <button type="button" 
                        @click="setViewMode('table')" 
                        class="text-emerald-700 hover:text-emerald-800 font-bold hover:underline cursor-pointer flex items-center gap-1">
                    <span>View in Table List</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- VIEW 2: TABLE LIST VIEW (Original paginated table)                        -->
        <!-- ========================================================================= -->
        <div x-show="viewMode === 'table'" 
             class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col justify-between flex-1 min-h-0">
            @if ($applications->isNotEmpty())
                <!-- Bulk Action Bar -->
                <div x-show="selectedIds.length > 0"
                     x-cloak
                     x-transition
                     class="px-5 py-2.5 bg-emerald-50/95 border-b border-emerald-200/80 flex flex-wrap items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-2 text-xs font-bold text-emerald-900">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-emerald-600 text-white text-[11px] font-black" x-text="selectedIds.length"></span>
                        <span>candidates selected</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Styled Bulk Status Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button type="button"
                                    @click="open = !open"
                                    class="inline-flex items-center justify-between gap-2.5 py-1.5 pl-3 pr-2.5 text-xs rounded-xl bg-white hover:bg-slate-50 border border-emerald-300 hover:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 font-semibold text-slate-800 transition-all cursor-pointer shadow-2xs min-w-[185px]">
                                <span class="flex items-center gap-2 truncate">
                                    <span class="w-2 h-2 rounded-full shrink-0"
                                          :class="(columns.find(c => c.id === bulkStatus) || {}).dot || 'bg-indigo-500'"></span>
                                    <span class="truncate" x-text="'Move to ' + ((columns.find(c => c.id === bulkStatus) || {}).label || 'Shortlisted')"></span>
                                </span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200 shrink-0"
                                   :class="open ? 'rotate-180 text-emerald-600' : ''"></i>
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
                                 class="absolute right-0 sm:left-0 top-full z-40 mt-1.5 w-52 rounded-2xl bg-white border border-slate-200 shadow-xl p-1.5 space-y-0.5"
                                 style="display: none;">
                                <template x-for="col in columns" :key="col.id">
                                    <button type="button"
                                            @click="bulkStatus = col.id; open = false"
                                            class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-xl transition-all cursor-pointer text-left"
                                            :class="bulkStatus === col.id ? 'bg-emerald-50 text-emerald-800 font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium'">
                                        <span class="flex items-center gap-2 truncate">
                                            <span class="w-2 h-2 rounded-full shrink-0" :class="col.dot"></span>
                                            <span x-text="'Move to ' + col.label"></span>
                                        </span>
                                        <i x-show="bulkStatus === col.id" class="fa-solid fa-check text-[10px] text-emerald-600"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <button type="button"
                                @click="applyBulkStatus()"
                                :disabled="bulkUpdating"
                                class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-xs font-bold shadow-2xs transition-colors cursor-pointer inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-check-double text-[10px]" x-show="!bulkUpdating"></i>
                            <i class="fa-solid fa-spinner fa-spin text-[10px]" x-show="bulkUpdating"></i>
                            <span x-text="bulkUpdating ? 'Updating...' : 'Apply Status'"></span>
                        </button>
                        <button type="button"
                                @click="selectedIds = []"
                                class="px-2.5 py-1.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 text-xs font-semibold transition-colors cursor-pointer">
                            Clear
                        </button>
                    </div>
                </div>

                <div class="w-full overflow-auto flex-1 min-h-0">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur-xs">
                            <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 pl-5 pr-2 w-10">
                                    <input type="checkbox"
                                           :checked="allTableSelected"
                                           @change="toggleSelectAll()"
                                           class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                           title="Select all candidates on this page">
                                </th>
                                <th class="py-3.5 px-4 min-w-[230px]">Student Candidate</th>
                                <th class="py-3.5 px-4 min-w-[210px]">Opportunity Applied</th>
                                <th class="py-3.5 px-4 min-w-[165px] whitespace-nowrap">Status & Interview</th>
                                <th class="py-3.5 px-4 min-w-[220px]">Statement / Notes</th>
                                <th class="py-3.5 px-5 text-right whitespace-nowrap">Review Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($applications as $app)
                                @php
                                    $candUser = $app->studentProfile->user;
                                    $candAvatar = $candUser->avatar_path;
                                    $candInitials = strtoupper(substr($candUser->name, 0, 2));
                                    $tableInterview = $app->interview;
                                    $appResumePath = $app->getEffectiveResumePath();
                                    $resumeExists = $appResumePath && (Storage::disk('local')->exists($appResumePath) || Storage::disk('public')->exists($appResumePath));
                                    $resumeFileName = $resumeExists ? basename($appResumePath) : '';
                                    $resumeExt = $resumeExists ? strtolower(pathinfo($resumeFileName, PATHINFO_EXTENSION)) : '';
                                    $canPreviewResume = $resumeExists && in_array($resumeExt, ['pdf', 'png', 'jpg', 'jpeg']);
                                @endphp
                                <tr x-show="!(hideRejected && '{{ $app->status }}' === 'rejected')"
                                    :class="selectedIds.includes({{ $app->id }}) ? 'bg-emerald-50/40' : 'hover:bg-slate-50/70'"
                                    class="transition-colors group">
                                    <!-- 0. Bulk Select Checkbox -->
                                    <td class="py-3.5 pl-5 pr-2 align-top">
                                        <input type="checkbox"
                                               @if ($app->status === 'withdrawn') disabled @endif
                                               :checked="selectedIds.includes({{ $app->id }})"
                                               @change="toggleSelectOne({{ $app->id }})"
                                               class="mt-1 w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                               title="{{ $app->status === 'withdrawn' ? 'Withdrawn applications cannot be bulk updated' : 'Select candidate' }}">
                                    </td>

                                    <!-- 1. Student Candidate -->
                                    <td class="py-3.5 px-4 align-top">
                                        <div class="flex items-start gap-3">
                                            <div class="w-9 h-9 rounded-xl text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs overflow-hidden bg-[#059669]">
                                                @if($candAvatar)
                                                    <img src="{{ asset('storage/' . ltrim($candAvatar, '/')) }}" alt="{{ $candUser->name }}" class="w-full h-full object-cover">
                                                @else
                                                    {{ $candInitials }}
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <a href="{{ route('company.students.show', $app->student_profile_id) }}"
                                                       class="font-extrabold text-slate-900 text-sm hover:text-emerald-700 hover:underline transition-colors">
                                                        {{ $candUser->name }}
                                                    </a>
                                                    @if ($app->studentProfile->gpa !== null)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/70 text-[10px] font-black shrink-0">
                                                            GPA {{ number_format((float)$app->studentProfile->gpa, 2) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-slate-500 font-medium text-[11px] mt-0.5">
                                                    {{ $app->studentProfile->major ?? 'N/A' }}
                                                </div>
                                                @if (is_array($app->studentProfile->skills) && count($app->studentProfile->skills) > 0)
                                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                                        @foreach (array_slice($app->studentProfile->skills, 0, 3) as $skill)
                                                            <span class="px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-semibold truncate max-w-[110px]">
                                                                {{ $skill }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 2. Opportunity Applied -->
                                    <td class="py-3.5 px-4 align-top">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            @if ($app->internshipPost->isInternship())
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                                    <span>🎓</span> Internship
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                                                    <span>💼</span> {{ $app->internshipPost->opportunity_type_label }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="font-bold text-slate-900 text-xs leading-snug" title="{{ $app->internshipPost->title }}">
                                            {{ $app->internshipPost->title }}
                                        </div>
                                        <span class="text-[11px] text-slate-400 block mt-1">
                                            Applied {{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}
                                        </span>
                                    </td>

                                    <!-- 3. Status & Interview Chip -->
                                    <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                        <div class="flex flex-col items-start gap-1.5">
                                            <x-status-badge :status="$app->status" />
                                            @if ($tableInterview)
                                                <button type="button"
                                                        @click="openInterviewModal({
                                                            id: {{ $app->id }},
                                                            candidate_name: @js($candUser->name),
                                                            role_title: @js($app->internshipPost->title),
                                                            interview_store_url: @js(route('company.applicants.interview.store', $app)),
                                                            message_url: @js(route('company.messages.start', $app)),
                                                            interview: @js([
                                                                'id'                   => $tableInterview->id,
                                                                'scheduled_at'         => $tableInterview->scheduled_at->format('Y-m-d\TH:i'),
                                                                'scheduled_human'      => $tableInterview->scheduled_at->format('M d, Y \a\t g:i A'),
                                                                'meeting_type'         => $tableInterview->meeting_type,
                                                                'location_or_link'     => $tableInterview->location_or_link,
                                                                'notes'                => $tableInterview->notes ?? '',
                                                                'status'               => $tableInterview->status,
                                                                'confirmed_by_student' => (bool)$tableInterview->confirmed_by_student,
                                                                'reschedule_reason'    => $tableInterview->reschedule_reason ?? '',
                                                                'is_online'            => $tableInterview->isOnline(),
                                                            ])
                                                        })"
                                                        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[10px] font-bold border transition-colors cursor-pointer {{ $tableInterview->status === 'confirmed' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' : ($tableInterview->status === 'reschedule_requested' ? 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100' : 'bg-indigo-50 text-indigo-800 border-indigo-200 hover:bg-indigo-100') }}"
                                                        title="Click to view or update interview details">
                                                    <i class="fa-solid fa-calendar-days text-[9px]"></i>
                                                    <span>{{ $tableInterview->scheduled_at->format('M d, g:i A') }}</span>
                                                    <span class="opacity-75 capitalize">• {{ str_replace('_', ' ', $tableInterview->status) }}</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- 4. Statement / Cover Letter & Notes -->
                                    <td class="py-3.5 px-4 text-slate-600 align-top max-w-xs">
                                        @if ($app->cover_letter)
                                            <p class="line-clamp-2 text-xs break-words leading-relaxed text-slate-600">
                                                {{ $app->cover_letter }}
                                            </p>
                                        @else
                                            <span class="text-[11px] text-slate-400 italic">No cover statement provided</span>
                                        @endif

                                        @if ($app->company_notes)
                                            <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200/70 text-[10px] font-semibold max-w-full">
                                                <i class="fa-solid fa-note-sticky text-emerald-600 shrink-0"></i>
                                                <span class="truncate">{{ $app->company_notes }}</span>
                                            </div>
                                        @endif

                                        @if ($tableInterview && $tableInterview->status === 'reschedule_requested' && $tableInterview->reschedule_reason)
                                            <div class="mt-2 p-2 bg-amber-50/95 border border-amber-200 rounded-xl text-xs text-amber-950">
                                                <div class="flex items-center justify-between gap-2 text-[10px] font-black uppercase text-amber-800">
                                                    <span class="flex items-center gap-1">
                                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                                        <span>Reschedule:</span>
                                                    </span>
                                                    <a href="{{ route('company.messages.start', $app) }}" class="text-indigo-600 hover:underline font-bold text-[10px]">
                                                        Reply in Chat
                                                    </a>
                                                </div>
                                                <p class="mt-1 text-slate-800 italic text-[11px] leading-relaxed line-clamp-2 break-words" title="{{ $tableInterview->reschedule_reason }}">
                                                    “{{ $tableInterview->reschedule_reason }}”
                                                </p>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- 5. Review Actions -->
                                    <td class="py-3.5 px-5 text-right align-top whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            @if($resumeExists)
                                                @if($canPreviewResume)
                                                    <a href="{{ route('company.applicants.resume', $app) }}" 
                                                       target="_blank"
                                                       class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-xl transition-colors border border-slate-200/80 cursor-pointer"
                                                       title="Preview {{ $resumeFileName }}">
                                                        <i class="fa-solid fa-file-lines text-xs text-emerald-600"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('company.applicants.resume', [$app, 'download' => 1]) }}" 
                                                   download="{{ $resumeFileName }}"
                                                   class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-xl transition-colors border border-slate-200/80 cursor-pointer"
                                                   title="Download {{ $resumeFileName }}">
                                                    <i class="fa-solid fa-download text-xs"></i>
                                                </a>
                                            @else
                                                <span class="w-8 h-8 flex items-center justify-center text-slate-300 rounded-xl border border-dashed border-slate-200" title="No resume attached">
                                                    <i class="fa-solid fa-file-circle-xmark text-xs"></i>
                                                </span>
                                            @endif

                                            <a href="{{ route('company.messages.start', $app) }}" 
                                                class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-blue-700 hover:bg-blue-50 rounded-xl transition-colors border border-slate-200/80 cursor-pointer"
                                                title="Chat with Candidate">
                                                <i class="fa-solid fa-comment-dots text-xs"></i>
                                            </a>

                                            @if ($app->status === 'withdrawn')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-100 text-slate-400 border border-slate-200 text-[11px] font-bold"
                                                      title="Candidate has withdrawn this application">
                                                    <i class="fa-solid fa-ban text-[10px]"></i>
                                                    <span>Withdrawn</span>
                                                </span>
                                            @else
                                                <button type="button"
                                                        @click="openInterviewModal({
                                                            id: {{ $app->id }},
                                                            candidate_name: @js($candUser->name),
                                                            role_title: @js($app->internshipPost->title),
                                                            interview_store_url: @js(route('company.applicants.interview.store', $app)),
                                                            message_url: @js(route('company.messages.start', $app)),
                                                            interview: @js($tableInterview ? [
                                                                'id'                   => $tableInterview->id,
                                                                'scheduled_at'         => $tableInterview->scheduled_at->format('Y-m-d\TH:i'),
                                                                'scheduled_human'      => $tableInterview->scheduled_at->format('M d, Y \a\t g:i A'),
                                                                'meeting_type'         => $tableInterview->meeting_type,
                                                                'location_or_link'     => $tableInterview->location_or_link,
                                                                'notes'                => $tableInterview->notes ?? '',
                                                                'status'               => $tableInterview->status,
                                                                'confirmed_by_student' => (bool)$tableInterview->confirmed_by_student,
                                                                'reschedule_reason'    => $tableInterview->reschedule_reason ?? '',
                                                                'is_online'            => $tableInterview->isOnline(),
                                                            ] : null)
                                                        })"
                                                        class="w-8 h-8 flex items-center justify-center rounded-xl transition-colors border cursor-pointer {{ $tableInterview ? 'text-indigo-600 bg-indigo-50 border-indigo-200 hover:bg-indigo-100' : 'text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 border-slate-200/80' }}"
                                                        title="{{ $tableInterview ? 'Manage Interview (' . $tableInterview->status . ')' : 'Schedule Interview' }}">
                                                    <i class="fa-solid fa-calendar-check text-xs"></i>
                                                </button>

                                                <button type="button"
                                                        @click="openStatusModal({
                                                            id: {{ $app->id }},
                                                            candidateName: @js($candUser->name),
                                                            roleTitle: @js($app->internshipPost->title),
                                                            status: @js($app->status),
                                                            companyNotes: @js($app->company_notes ?? ''),
                                                            updateUrl: @js(route('company.applicants.update', $app)),
                                                            hasResume: @js($resumeExists),
                                                            resumeFileName: @js($resumeFileName),
                                                            resumePreviewUrl: @js($canPreviewResume ? route('company.applicants.resume', $app) : ''),
                                                            resumeDownloadUrl: @js($resumeExists ? route('company.applicants.resume', [$app, 'download' => 1]) : '')
                                                        })"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-2xs transition-colors cursor-pointer"
                                                        title="Update Candidacy Stage & Notes">
                                                    <i class="fa-solid fa-sliders text-[10px]"></i>
                                                    <span>Review</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($applications->hasPages())
                    <div class="p-4 border-t border-slate-100 bg-white shrink-0">
                        {{ $applications->links() }}
                    </div>
                @endif
            @else
                <div class="flex-1 flex flex-col items-center justify-center py-16 text-center text-slate-600 space-y-3">
                    <i class="fa-solid fa-users w-12 h-12 mx-auto text-slate-300"></i>
                    <h3 class="text-base font-bold text-slate-800">No applicants match this filter</h3>
                    <p class="text-xs text-slate-600 max-w-sm mx-auto">Select a different role or reset filters to review incoming candidate profiles.</p>
                </div>
            @endif
        </div>

        <!-- ========================================================================= -->
        <!-- FLOATING TOAST NOTIFICATION (Drag & Drop Feedback)                        -->
        <!-- ========================================================================= -->
        <div x-show="toast.show"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 scale-95"
             class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl text-xs font-bold border backdrop-blur-md"
             :class="toast.type === 'error' 
                 ? 'bg-rose-950/90 text-rose-100 border-rose-800 shadow-rose-950/20' 
                 : 'bg-slate-900/95 text-white border-slate-700 shadow-slate-950/30'">
            <i :class="toast.type === 'error' ? 'fa-solid fa-triangle-exclamation text-rose-400' : 'fa-solid fa-circle-check text-emerald-400'" class="text-sm"></i>
            <span x-text="toast.message"></span>
        </div>

        {{-- Update Candidacy Status Modal Component --}}
        @include('company.applicants.modal-status')

        {{-- Feature 2.4: Schedule Interview Modal Component --}}
        @include('company.applicants.modal-interview')

    </div>
</x-layout>