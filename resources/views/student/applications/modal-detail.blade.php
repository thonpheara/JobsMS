<!-- Application Cover Letter & Resume Preview Modal Component (Feature 1.8) -->
<div x-show="detailModalOpen"
     x-cloak
     x-effect="document.body.classList.toggle('overflow-hidden', detailModalOpen)"
     @keydown.escape.window="detailModalOpen = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modal-detail-title">

    <!-- Modal Backdrop with Blur -->
    <div x-show="detailModalOpen"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="detailModalOpen = false"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

    <!-- Centered Modal Container -->
    <div class="flex min-h-full items-center justify-center p-3 sm:p-6 lg:p-8">
        <div x-show="detailModalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="relative w-full max-w-2xl sm:max-w-3xl lg:max-w-4xl bg-white rounded-3xl border border-slate-200/90 shadow-2xl overflow-hidden my-6 flex flex-col max-h-[90vh]">

            <!-- Modal Header -->
            <div class="px-6 py-5 sm:px-8 sm:py-5 border-b border-slate-100 bg-gradient-to-r from-emerald-50/50 via-slate-50/40 to-white flex items-center justify-between gap-4 shrink-0">
                <div class="flex items-center gap-3.5 min-w-0 flex-1">
                    <!-- Company Logo / Initials -->
                    <template x-if="detailApp.hasLogo && detailApp.companyLogo">
                        <img :src="detailApp.companyLogo" :alt="detailApp.companyName"
                             class="w-12 h-12 rounded-2xl object-cover shrink-0 border border-slate-200 shadow-xs">
                    </template>
                    <template x-if="!detailApp.hasLogo || !detailApp.companyLogo">
                        <div class="w-12 h-12 rounded-2xl bg-teal-700 text-white flex items-center justify-center font-bold text-base shrink-0 shadow-xs"
                             x-text="detailApp.companyInitials"></div>
                    </template>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 id="modal-detail-title" class="text-base sm:text-lg font-black text-slate-900 tracking-tight truncate"
                                x-text="detailApp.jobTitle"></h3>
                            
                            <!-- Dynamic Status Pill -->
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border capitalize"
                                  :class="{
                                      'bg-emerald-50 text-emerald-800 border-emerald-200 ring-1 ring-emerald-500/10': ['accepted', 'approved'].includes(detailApp.status),
                                      'bg-amber-50 text-amber-800 border-amber-200 ring-1 ring-amber-500/10': ['pending', 'under_review'].includes(detailApp.status),
                                      'bg-teal-50 text-teal-800 border-teal-200 ring-1 ring-teal-500/10': ['shortlisted', 'interviewed'].includes(detailApp.status),
                                      'bg-rose-50 text-rose-800 border-rose-200 ring-1 ring-rose-500/10': ['rejected'].includes(detailApp.status),
                                      'bg-slate-100 text-slate-700 border-slate-200 ring-1 ring-slate-500/10': ['withdrawn'].includes(detailApp.status),
                                  }">
                                <span class="w-1.5 h-1.5 rounded-full"
                                      :class="{
                                          'bg-emerald-500': ['accepted', 'approved'].includes(detailApp.status),
                                          'bg-amber-500': ['pending', 'under_review'].includes(detailApp.status),
                                          'bg-teal-500': ['shortlisted', 'interviewed'].includes(detailApp.status),
                                          'bg-rose-500': ['rejected'].includes(detailApp.status),
                                          'bg-slate-400': ['withdrawn'].includes(detailApp.status),
                                      }"></span>
                                <span x-text="detailApp.status ? detailApp.status.replace('_', ' ') : 'Pending'"></span>
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 font-medium mt-0.5 flex items-center gap-1.5 flex-wrap">
                            <span class="font-semibold text-slate-700" x-text="detailApp.companyName"></span>
                            <span class="text-slate-300">•</span>
                            <span x-text="detailApp.location"></span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-400">Applied <span x-text="detailApp.appliedAt"></span> (<span x-text="detailApp.appliedHuman"></span>)</span>
                        </p>
                    </div>
                </div>

                <!-- Close Button -->
                <button type="button" 
                        @click="detailModalOpen = false"
                        class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors cursor-pointer shrink-0"
                        title="Close (Esc)">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Segmented Navigation Tabs -->
            <div class="px-6 sm:px-8 pt-3 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <!-- Tab 1: Cover Letter -->
                    <button type="button"
                            @click="detailTab = 'letter'"
                            :class="detailTab === 'letter'
                                ? 'bg-white text-emerald-700 border-b-2 border-emerald-600 font-bold shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-semibold'"
                            class="px-3.5 py-2.5 rounded-t-xl text-xs inline-flex items-center gap-2 transition-all cursor-pointer">
                        <i class="fa-solid fa-file-lines text-xs" :class="detailTab === 'letter' ? 'text-emerald-600' : 'text-slate-400'"></i>
                        <span>Statement of Intent</span>
                    </button>

                    <!-- Tab 2: Resume / CV -->
                    <button type="button"
                            @click="detailTab = 'resume'"
                            :class="detailTab === 'resume'
                                ? 'bg-white text-emerald-700 border-b-2 border-emerald-600 font-bold shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-semibold'"
                            class="px-3.5 py-2.5 rounded-t-xl text-xs inline-flex items-center gap-2 transition-all cursor-pointer">
                        <i class="fa-solid fa-file-pdf text-xs" :class="detailTab === 'resume' ? 'text-emerald-600' : 'text-slate-400'"></i>
                        <span>Submitted Resume</span>
                        <template x-if="detailApp.hasResume">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        </template>
                    </button>

                    <!-- Tab 3: Recruiter Feedback (if exists) -->
                    <template x-if="detailApp.companyNotes">
                        <button type="button"
                                @click="detailTab = 'feedback'"
                                :class="detailTab === 'feedback'
                                    ? 'bg-white text-emerald-700 border-b-2 border-emerald-600 font-bold shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-semibold'"
                                class="px-3.5 py-2.5 rounded-t-xl text-xs inline-flex items-center gap-2 transition-all cursor-pointer">
                            <i class="fa-solid fa-comment-dots text-xs" :class="detailTab === 'feedback' ? 'text-emerald-600' : 'text-slate-400'"></i>
                            <span>Recruiter Notes</span>
                            <span class="px-1.5 py-0.2 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">1</span>
                        </button>
                    </template>
                </div>

                <!-- Quick Job Post Link -->
                <a :href="detailApp.jobUrl"
                   class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-800 hover:underline hidden sm:inline-flex items-center gap-1">
                    <span>View Role Listing</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                </a>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-6 sm:p-8 flex-1 overflow-y-auto">

                <!-- ══════════════ TAB 1: COVER LETTER ══════════════ -->
                <div x-show="detailTab === 'letter'" class="space-y-4">
                    <div class="flex items-center justify-between gap-3 pb-2 border-b border-slate-100 text-xs">
                        <div class="flex items-center gap-2 text-slate-500">
                            <i class="fa-solid fa-feather-pointed text-emerald-600"></i>
                            <span class="font-bold text-slate-700">Statement of Intent</span>
                            <span class="text-slate-300">•</span>
                            <span x-text="detailApp.coverLetter ? (detailApp.coverLetter.trim().split(/\s+/).length + ' words') : '0 words'"></span>
                            <span class="text-slate-300">•</span>
                            <span x-text="detailApp.coverLetter ? (detailApp.coverLetter.length + ' characters') : '0 characters'"></span>
                        </div>

                        <!-- Copy Button -->
                        <button type="button"
                                @click="copyCoverLetter()"
                                class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-[11px] font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer">
                            <i :class="copied ? 'fa-solid fa-check text-emerald-600' : 'fa-regular fa-copy text-slate-400'"></i>
                            <span x-text="copied ? 'Copied!' : 'Copy Text'"></span>
                        </button>
                    </div>

                    <!-- Cover Letter Text Box -->
                    <template x-if="detailApp.coverLetter">
                        <div class="p-5 sm:p-6 rounded-2xl bg-slate-50 border border-slate-200/80 text-slate-800 text-xs sm:text-sm leading-relaxed whitespace-pre-line selection:bg-emerald-100 selection:text-emerald-900 font-sans"
                             x-text="detailApp.coverLetter"></div>
                    </template>
                    <template x-if="!detailApp.coverLetter">
                        <div class="p-8 text-center text-slate-400 rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-xs">
                            <i class="fa-regular fa-file-lines text-2xl text-slate-300 mb-2 block"></i>
                            <span>No cover letter text was submitted for this application.</span>
                        </div>
                    </template>
                </div>

                <!-- ══════════════ TAB 2: RESUME PREVIEW ══════════════ -->
                <div x-show="detailTab === 'resume'" class="space-y-4">
                    <!-- Resume Meta Bar -->
                    <template x-if="detailApp.hasResume">
                        <div class="p-3.5 bg-emerald-50/70 border border-emerald-200/80 rounded-2xl flex items-center justify-between gap-3 text-xs flex-wrap">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0 shadow-2xs">
                                    <i class="fa-solid fa-file-circle-check text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <span class="font-bold text-slate-900 block truncate text-xs sm:text-sm" x-text="detailApp.resumeName"></span>
                                    <span class="text-[10px] text-emerald-700 font-medium">Candidate Attached Resume</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="detailApp.resumePreviewUrl" target="_blank"
                                   class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold inline-flex items-center gap-1.5 transition-colors shadow-2xs">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                    <span>Open in New Tab</span>
                                </a>
                                <a :href="detailApp.resumeDownloadUrl" :download="detailApp.resumeName"
                                   class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold inline-flex items-center gap-1.5 transition-colors shadow-2xs">
                                    <i class="fa-solid fa-download text-[10px]"></i>
                                    <span>Download</span>
                                </a>
                            </div>
                        </div>
                    </template>

                    <!-- Interactive PDF / Document Preview Container -->
                    <template x-if="detailApp.hasResume && detailApp.isPdfOrImage">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 overflow-hidden shadow-xs">
                            <iframe :src="detailApp.resumePreviewUrl"
                                    class="w-full h-[520px] bg-white"
                                    frameborder="0"></iframe>
                        </div>
                    </template>

                    <!-- Non-PDF (Word document: doc/docx) fallback notice -->
                    <template x-if="detailApp.hasResume && !detailApp.isPdfOrImage">
                        <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200 text-center space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center mx-auto">
                                <i class="fa-solid fa-file-word text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800" x-text="detailApp.resumeName"></h4>
                                <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 leading-relaxed">
                                    Word documents (.doc/.docx) cannot be previewed directly inside the web browser. You can download the file to view it in Microsoft Word or your preferred document reader.
                                </p>
                            </div>
                            <a :href="detailApp.resumeDownloadUrl" :download="detailApp.resumeName"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors shadow-xs">
                                <i class="fa-solid fa-download text-xs"></i>
                                <span>Download Document (<span x-text="detailApp.resumeName"></span>)</span>
                            </a>
                        </div>
                    </template>

                    <!-- No resume attached -->
                    <template x-if="!detailApp.hasResume">
                        <div class="p-8 text-center text-slate-400 rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-xs">
                            <i class="fa-solid fa-file-circle-xmark text-2xl text-slate-300 mb-2 block"></i>
                            <span>No resume document is attached to this application record.</span>
                        </div>
                    </template>
                </div>

                <!-- ══════════════ TAB 3: RECRUITER FEEDBACK ══════════════ -->
                <template x-if="detailApp.companyNotes">
                    <div x-show="detailTab === 'feedback'" class="space-y-4">
                        <div class="p-5 sm:p-6 rounded-2xl bg-amber-50/70 border border-amber-200/80 space-y-3">
                            <div class="flex items-center gap-2 text-amber-900 font-bold text-xs uppercase tracking-wider">
                                <i class="fa-solid fa-comment-dots text-amber-600 text-sm"></i>
                                <span>Official Recruiter Feedback</span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-800 whitespace-pre-line leading-relaxed font-sans"
                               x-text="detailApp.companyNotes"></p>
                        </div>
                    </div>
                </template>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 sm:px-8 border-t border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3 shrink-0">
                <span class="text-xs text-slate-500 font-medium">
                    Application ID: <strong class="text-slate-800 font-bold">#<span x-text="detailApp.id"></span></strong>
                </span>

                <div class="flex items-center gap-2.5">
                    <button type="button"
                            @click="detailModalOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-white transition-colors cursor-pointer shadow-2xs">
                        Close
                    </button>
                    <a :href="detailApp.jobUrl"
                       class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs inline-flex items-center gap-1.5">
                        <span>View Job Listing</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

