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
                },
                apps: @js($kanbanApplications),
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
                getAppsByStatus(status) {
                    return this.apps.filter(a => a.status === status);
                },
                getWithdrawnApps() {
                    return this.apps.filter(a => a.status === 'withdrawn');
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

    <div class="flex flex-col min-h-[640px] h-[calc(100vh-6rem)] sm:h-[calc(100vh-7rem)] lg:h-[calc(100vh-8rem)]"
         x-data="applicantPipeline()">

        <!-- Page Header & Toolbar -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3.5 pb-3.5 shrink-0">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-black tracking-tight text-slate-900">Applicant Review Board</h2>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/80">
                        <i class="fa-solid fa-users text-[10px] text-emerald-600"></i>
                        <span x-text="apps.length"></span> Candidates
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">Drag candidate cards across hiring stages or switch to table view for bulk management.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
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

                <!-- Filter Form -->
                <form action="{{ route('company.applicants.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
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
                    <template x-for="col in columns" :key="col.id">
                        <div class="w-80 min-w-[310px] max-w-[310px] flex flex-col h-full rounded-3xl border transition-all duration-200 overflow-hidden"
                             :class="dragOverColumn === col.id 
                                 ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/25 shadow-md' 
                                 : 'border-slate-200/80 bg-slate-100/70 shadow-2xs'"
                             @dragover.prevent="onDragOver($event, col.id)"
                             @dragleave="onDragLeave($event, col.id)"
                             @drop="onDrop($event, col.id)">

                            <!-- Column Header -->
                            <div class="px-4 py-3 border-b flex items-center justify-between shrink-0"
                                 :class="col.headerBg">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="col.dot"></span>
                                    <i :class="col.icon" class="text-xs shrink-0 opacity-80"></i>
                                    <h3 class="font-extrabold text-xs tracking-tight truncate" x-text="col.label"></h3>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-black shrink-0 shadow-2xs"
                                      :class="col.countBadge"
                                      x-text="getAppsByStatus(col.id).length"></span>
                            </div>

                            <!-- Column Body / Droppable Cards Container -->
                            <div class="flex-1 min-h-0 overflow-y-auto p-3 space-y-2.5">
                                
                                <!-- Empty Column Placeholder -->
                                <div x-show="getAppsByStatus(col.id).length === 0" 
                                     class="h-36 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center text-center p-4 transition-colors"
                                     :class="dragOverColumn === col.id ? 'border-emerald-400 bg-emerald-100/40 text-emerald-700' : 'text-slate-400'">
                                    <i class="fa-solid fa-arrow-down-to-line text-lg mb-1.5 opacity-60"></i>
                                    <span class="text-xs font-semibold" x-text="dragOverColumn === col.id ? 'Drop candidate here' : 'No candidates in this stage'"></span>
                                    <span class="text-[10px] opacity-75 mt-0.5" x-text="dragOverColumn === col.id ? 'Release mouse to update' : 'Drag candidate card here to update'"></span>
                                </div>

                                <!-- Candidate Cards -->
                                <template x-for="app in getAppsByStatus(col.id)" :key="app.id">
                                    <div draggable="!app.is_withdrawn"
                                         @dragstart="onDragStart($event, app)"
                                         @dragend="onDragEnd()"
                                         class="bg-white rounded-2xl p-3.5 border border-slate-200/90 shadow-2xs transition-all duration-150 relative group"
                                         :class="{
                                             'opacity-40 scale-95 border-emerald-500 border-dashed ring-2 ring-emerald-500/20': draggedAppId === app.id,
                                             'hover:shadow-md hover:border-emerald-300 hover:-translate-y-0.5 cursor-grab active:cursor-grabbing': !app.is_withdrawn,
                                             'opacity-60 bg-slate-50 border-dashed cursor-not-allowed': app.is_withdrawn
                                         }">

                                        <!-- Card Top: Candidate Info -->
                                        <div class="flex items-start gap-2.5">
                                            <!-- Candidate Avatar / Initials -->
                                            <div class="w-9 h-9 rounded-xl text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs overflow-hidden bg-[#059669]">
                                                <template x-if="app.avatar_url">
                                                    <img :src="app.avatar_url" :alt="app.candidate_name" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!app.avatar_url">
                                                    <span x-text="app.candidate_initials"></span>
                                                </template>
                                            </div>

                                            <!-- Name, Major & GPA -->
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between gap-1">
                                                    <h4 class="font-extrabold text-slate-900 text-xs truncate group-hover:text-emerald-700 transition-colors" 
                                                        x-text="app.candidate_name"></h4>
                                                    <span class="px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-[10px] font-black shrink-0" 
                                                          x-text="'GPA: ' + app.gpa"></span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 truncate" x-text="app.major"></p>
                                            </div>
                                        </div>

                                        <!-- Card Middle: Role & Applied Date -->
                                        <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between gap-1 text-[11px]">
                                            <div class="flex items-center gap-1.5 text-slate-700 font-bold truncate min-w-0" :title="app.role_title">
                                                <i class="fa-solid fa-briefcase text-[10px] text-emerald-600 shrink-0"></i>
                                                <span class="truncate" x-text="app.role_title"></span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 shrink-0" x-text="app.applied_human"></span>
                                        </div>

                                        <!-- Skills Chips -->
                                        <div x-show="app.skills && app.skills.length > 0" class="flex flex-wrap gap-1 mt-2">
                                            <template x-for="skill in app.skills" :key="skill">
                                                <span class="px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-semibold truncate max-w-[120px]" 
                                                      x-text="skill"></span>
                                            </template>
                                        </div>

                                        <!-- Cover Letter Snippet -->
                                        <div x-show="app.cover_letter" class="mt-2 text-[11px] text-slate-500 line-clamp-2 leading-relaxed bg-slate-50/70 p-2 rounded-xl border border-slate-100">
                                            <span class="italic" x-text="'“' + app.cover_letter + '”'"></span>
                                        </div>

                                        <!-- Recruiter Note Pill (if present) -->
                                        <div x-show="app.company_notes" class="mt-2 text-[10px] text-emerald-800 font-medium bg-emerald-50/80 px-2 py-1 rounded-xl border border-emerald-200/60 truncate" :title="app.company_notes">
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

                                                <!-- Drag Handle Visual Indicator -->
                                                <div class="w-6 h-7 flex items-center justify-center text-slate-300 group-hover:text-slate-500 transition-colors ml-0.5"
                                                     title="Drag card to move between stages">
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
                <div class="w-full overflow-auto flex-1 min-h-0">
                    <table class="w-full text-left border-collapse text-xs table-fixed">
                        <thead class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur-xs">
                            <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                                <th class="py-3 px-6 w-[24%]">Student Candidate</th>
                                <th class="py-3 px-4 w-[22%]">Role Applied</th>
                                <th class="py-3 px-4 w-[14%] whitespace-nowrap">Status</th>
                                <th class="py-3 px-4 w-[26%]">Statement / Cover Letter</th>
                                <th class="py-3 px-6 text-right w-[14%] whitespace-nowrap">Review Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($applications as $app)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-6 min-w-0">
                                        <div class="flex items-center gap-3">
                                            @php
                                                $candUser = $app->studentProfile->user;
                                                $candAvatar = $candUser->avatar_path;
                                                $candInitials = strtoupper(substr($candUser->name, 0, 2));
                                            @endphp
                                            <div class="w-8 h-8 rounded-xl text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs overflow-hidden bg-[#059669]">
                                                @if($candAvatar)
                                                    <img src="{{ asset('storage/' . ltrim($candAvatar, '/')) }}" alt="{{ $candUser->name }}" class="w-full h-full object-cover">
                                                @else
                                                    {{ $candInitials }}
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-extrabold text-slate-900 text-sm truncate">{{ $candUser->name }}</div>
                                                <div class="text-slate-600 font-semibold truncate">{{ $app->studentProfile->major }} (GPA: {{ $app->studentProfile->gpa ?? 'N/A' }})</div>
                                            </div>
                                        </div>
                                        @if (is_array($app->studentProfile->skills))
                                            <div class="flex flex-wrap gap-1 mt-1.5 ml-11">
                                                @foreach (array_slice($app->studentProfile->skills, 0, 3) as $skill)
                                                    <span class="px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-semibold truncate max-w-[100px]">
                                                        {{ $skill }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 min-w-0">
                                        <div class="font-bold text-slate-900 truncate" title="{{ $app->internshipPost->title }}">{{ $app->internshipPost->title }}</div>
                                        <span class="text-[11px] text-slate-600 block truncate">Applied {{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}</span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <x-status-badge :status="$app->status" />
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 min-w-0">
                                        <p class="line-clamp-2 text-xs break-all break-words leading-relaxed">{{ $app->cover_letter }}</p>
                                        @if ($app->company_notes)
                                            <div class="mt-1 text-[11px] text-emerald-700 font-medium truncate break-all break-words">Note: {{ $app->company_notes }}</div>
                                        @endif
                                        @if ($app->interview && $app->interview->status === 'reschedule_requested' && $app->interview->reschedule_reason)
                                            <div class="mt-2 p-2 bg-amber-50/95 border border-amber-200 rounded-xl text-xs text-amber-950">
                                                <div class="flex items-center justify-between gap-1 text-[10px] font-black uppercase text-amber-800">
                                                    <span class="flex items-center gap-1">
                                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                                        <span>Reschedule Request:</span>
                                                    </span>
                                                    <a href="{{ route('company.messages.start', $app) }}" class="text-indigo-600 hover:underline font-bold text-[10px]">
                                                        Reply in Chat
                                                    </a>
                                                </div>
                                                <p class="mt-1 text-slate-800 italic text-[11px] leading-relaxed line-clamp-2" title="{{ $app->interview->reschedule_reason }}">
                                                    “{{ $app->interview->reschedule_reason }}”
                                                </p>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-6 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            @php
                                                $appResumePath = $app->getEffectiveResumePath();
                                                $resumeExists = $appResumePath && (Storage::disk('local')->exists($appResumePath) || Storage::disk('public')->exists($appResumePath));
                                            @endphp
                                            @if($resumeExists)
                                                @php
                                                    $resumeFileName = basename($appResumePath);
                                                    $resumeExt = strtolower(pathinfo($resumeFileName, PATHINFO_EXTENSION));
                                                @endphp
                                                @if(in_array($resumeExt, ['pdf', 'png', 'jpg', 'jpeg']))
                                                    <a href="{{ route('company.applicants.resume', $app) }}" 
                                                       target="_blank"
                                                       class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors border border-slate-200/60 cursor-pointer"
                                                       title="Preview {{ $resumeFileName }}">
                                                        <i class="fa-solid fa-file-lines text-xs text-emerald-600"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('company.applicants.resume', [$app, 'download' => 1]) }}" 
                                                   download="{{ $resumeFileName }}"
                                                   class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors border border-slate-200/60 cursor-pointer"
                                                   title="Download {{ $resumeFileName }}">
                                                    <i class="fa-solid fa-download text-xs text-slate-600"></i>
                                                </a>
                                            @else
                                                <span class="w-8 h-8 flex items-center justify-center text-slate-300 rounded-lg border border-dashed border-slate-200" title="No resume document attached">
                                                    <i class="fa-solid fa-file-circle-xmark text-xs text-slate-300"></i>
                                                </span>
                                            @endif
                                            <a href="{{ route('company.messages.start', $app) }}" 
                                                class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors border border-slate-200/60 cursor-pointer"
                                                title="Chat with Candidate">
                                                <i class="fa-solid fa-comment-dots text-xs"></i>
                                            </a>
                                            @if ($app->status === 'withdrawn')
                                                <span class="w-8 h-8 flex items-center justify-center text-slate-300 rounded-lg border border-slate-200"
                                                      title="Candidate has withdrawn this application">
                                                    <i class="fa-solid fa-ban text-xs text-slate-400"></i>
                                                </span>
                                            @else
                                                @php
                                                    $tableInterview = $app->interview;
                                                @endphp
                                                <button type="button"
                                                        @click="openInterviewModal({
                                                            id: {{ $app->id }},
                                                            candidate_name: @js($app->studentProfile->user->name),
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
                                                        class="w-8 h-8 flex items-center justify-center rounded-lg transition-colors border border-slate-200/60 cursor-pointer {{ $tableInterview ? 'text-indigo-600 bg-indigo-50 hover:bg-indigo-100' : 'text-slate-600 hover:text-indigo-600 hover:bg-indigo-50' }}"
                                                        title="{{ $tableInterview ? 'Manage Interview (' . $tableInterview->status . ')' : 'Schedule Interview' }}">
                                                    <i class="fa-solid fa-calendar-check text-xs"></i>
                                                </button>

                                                <button type="button"
                                                        @click="openStatusModal({
                                                            id: {{ $app->id }},
                                                            candidateName: @js($app->studentProfile->user->name),
                                                            roleTitle: @js($app->internshipPost->title),
                                                            status: @js($app->status),
                                                            companyNotes: @js($app->company_notes ?? ''),
                                                            updateUrl: @js(route('company.applicants.update', $app)),
                                                            hasResume: @js($resumeExists),
                                                            resumeFileName: @js($resumeExists ? basename($appResumePath) : ''),
                                                            resumePreviewUrl: @js($resumeExists && in_array(strtolower(pathinfo(basename($appResumePath), PATHINFO_EXTENSION)), ['pdf', 'png', 'jpg', 'jpeg']) ? route('company.applicants.resume', $app) : ''),
                                                            resumeDownloadUrl: @js($resumeExists ? route('company.applicants.resume', [$app, 'download' => 1]) : '')
                                                        })"
                                                        class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors border border-slate-200/60 cursor-pointer"
                                                        title="Update Candidacy Status">
                                                    <i class="fa-solid fa-sliders text-xs"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 bg-white shrink-0">
                    {{ $applications->links() }}
                </div>
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