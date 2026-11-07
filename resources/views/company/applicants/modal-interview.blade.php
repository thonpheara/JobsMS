<!-- Schedule Interview Modal Component -->
<div x-show="interviewModalOpen"
     x-cloak
     x-effect="document.body.classList.toggle('overflow-hidden', interviewModalOpen)"
     @keydown.escape.window="interviewModalOpen = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;"
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="modal-interview-title">

    <!-- Modal Backdrop with Blur -->
    <div x-show="interviewModalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="interviewModalOpen = false"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

    <!-- Centered Modal Container -->
    <div class="flex min-h-full items-center justify-center p-3 sm:p-6 lg:p-8">
        <div x-show="interviewModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="relative w-full max-w-xl bg-white rounded-3xl border border-slate-200/90 shadow-2xl overflow-hidden my-6">

            <!-- Modal Header -->
            <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-slate-100 bg-gradient-to-r from-indigo-50/70 via-slate-50/40 to-white flex items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/25 shrink-0">
                        <i class="fa-solid fa-calendar-check text-lg"></i>
                    </div>
                    <div>
                        <h3 id="modal-interview-title" class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                            Schedule Interview Session
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <span x-text="interviewApp.candidateName" class="font-bold text-slate-800"></span> • <span x-text="interviewApp.roleTitle" class="text-indigo-700 font-medium"></span>
                        </p>
                    </div>
                </div>
                <button type="button" 
                        @click="interviewModalOpen = false"
                        class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors cursor-pointer"
                        title="Close (Esc)">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form :action="interviewApp.storeUrl" method="POST" id="schedule-interview-modal-form" class="px-6 py-6 sm:px-8 space-y-5">
                @csrf

                <!-- Candidate Reschedule Request Banner (Feature 2.4) -->
                <div x-show="interviewApp.rescheduleReason" 
                     class="p-4 bg-amber-50/90 border border-amber-200/90 rounded-2xl flex items-start gap-3.5 text-xs text-amber-950">
                    <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                        <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="font-black text-amber-900 uppercase tracking-wider text-[11px]">
                                Candidate Requested Reschedule
                            </span>
                            <a :href="interviewApp.messageUrl" 
                               target="_blank"
                               class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 hover:text-indigo-900 underline">
                                <i class="fa-solid fa-comments text-[10px]"></i>
                                <span>Reply in Chat</span>
                            </a>
                        </div>
                        <div class="mt-1.5 p-2.5 bg-white/90 rounded-xl border border-amber-200/60 text-slate-800 italic leading-relaxed text-xs">
                            “<span x-text="interviewApp.rescheduleReason"></span>”
                        </div>
                        <p class="text-[11px] text-amber-800 font-semibold mt-1.5 flex items-center gap-1">
                            <i class="fa-solid fa-circle-info text-amber-600"></i>
                            <span>Select a new date & time below to accommodate the candidate's availability.</span>
                        </p>
                    </div>
                </div>

                <!-- Meeting Type Radio Selection -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Meeting Format <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" 
                                @click="interviewApp.meetingType = 'online'"
                                :class="interviewApp.meetingType === 'online' ? 'border-indigo-600 bg-indigo-50/80 text-indigo-950 ring-2 ring-indigo-500/20' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                class="p-3.5 rounded-2xl border-2 text-left transition-all cursor-pointer flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold"
                                 :class="interviewApp.meetingType === 'online' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'">
                                <i class="fa-solid fa-video"></i>
                            </div>
                            <div>
                                <span class="text-xs font-bold block">Online Video</span>
                                <span class="text-[10px] text-slate-500">Google Meet / Zoom</span>
                            </div>
                        </button>

                        <button type="button" 
                                @click="interviewApp.meetingType = 'in_person'"
                                :class="interviewApp.meetingType === 'in_person' ? 'border-indigo-600 bg-indigo-50/80 text-indigo-950 ring-2 ring-indigo-500/20' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                class="p-3.5 rounded-2xl border-2 text-left transition-all cursor-pointer flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold"
                                 :class="interviewApp.meetingType === 'in_person' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div>
                                <span class="text-xs font-bold block">In-Person</span>
                                <span class="text-[10px] text-slate-500">Company Office</span>
                            </div>
                        </button>
                    </div>
                    <input type="hidden" name="meeting_type" :value="interviewApp.meetingType">
                </div>

                <!-- Date & Time Input -->
                <div>
                    <label for="interview-scheduled-at" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Date & Time <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-calendar-days absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                        <input type="datetime-local" 
                               id="interview-scheduled-at" 
                               name="scheduled_at" 
                               x-model="interviewApp.scheduledAt"
                               required 
                               class="w-full pl-10 pr-4 py-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 transition-all font-medium">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Select the proposed interview date and local Cambodian time.</p>
                </div>

                <!-- Location or Link Input -->
                <div>
                    <label for="interview-location" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        <span x-text="interviewApp.meetingType === 'online' ? 'Meeting Video Link (URL)' : 'Physical Address / Office Location'"></span> <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i :class="interviewApp.meetingType === 'online' ? 'fa-solid fa-link' : 'fa-solid fa-location-dot'" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                        <input type="text" 
                               id="interview-location" 
                               name="location_or_link" 
                               x-model="interviewApp.locationOrLink"
                               required 
                               :placeholder="interviewApp.meetingType === 'online' ? 'https://meet.google.com/abc-defg-hij' : 'e.g. Canadia Tower, 14th Floor, Room 1402, Phnom Penh'"
                               class="w-full pl-10 pr-4 py-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 transition-all placeholder-slate-400">
                    </div>
                </div>

                <!-- Recruiter Notes / Instructions -->
                <div>
                    <label for="interview-notes" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Preparation Notes / Agenda <span class="text-slate-400 font-normal normal-case">(optional)</span>
                    </label>
                    <textarea id="interview-notes" 
                              name="notes" 
                              x-model="interviewApp.notes"
                              rows="3" 
                              placeholder="e.g. Please bring your laptop with a demo of your past projects. Interview will last approximately 30 minutes with our Engineering Lead." 
                              class="w-full px-4 py-3 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 transition-all placeholder-slate-400 resize-none"></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">This will be emailed to the student and displayed in their application portal.</p>
                </div>

                <!-- Modal Actions -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                    <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-bell text-indigo-500"></i>
                        <span>Student receives an in-app alert & email.</span>
                    </p>
                    <div class="flex items-center gap-2.5">
                        <button type="button" 
                                @click="interviewModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs shadow-md shadow-indigo-600/25 transition-all flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>Send Invitation</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

