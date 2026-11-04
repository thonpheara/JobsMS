<x-layout>
    <div class="flex flex-col h-[calc(100vh-6rem)] sm:h-[calc(100vh-7rem)] lg:h-[calc(100vh-8rem)]"
         x-data="{
             // Withdraw Modal State
             withdrawModalOpen: false,
             withdrawUrl: '',
             jobTitle: '',
             companyName: '',
             submitting: false,
             openWithdrawModal(url, jobTitle, companyName) {
                 this.withdrawUrl = url;
                 this.jobTitle = jobTitle;
                 this.companyName = companyName;
                 this.submitting = false;
                 this.withdrawModalOpen = true;
             },

             // Feature 1.8: Detail Modal State (Cover Letter & Resume Preview)
             detailModalOpen: false,
             detailTab: 'letter',
             copied: false,
             detailApp: {
                 id: null,
                 jobTitle: '',
                 companyName: '',
                 companyLogo: '',
                 companyInitials: '',
                 hasLogo: false,
                 location: '',
                 status: '',
                 appliedAt: '',
                 appliedHuman: '',
                 coverLetter: '',
                 companyNotes: '',
                 hasResume: false,
                 resumeName: '',
                 resumePreviewUrl: '',
                 resumeDownloadUrl: '',
                 isPdfOrImage: false,
                 jobUrl: ''
             },
             openDetailModal(data) {
                 this.detailApp = { ...data };
                 this.detailTab = 'letter';
                 this.copied = false;
                 this.detailModalOpen = true;
             },
             copyCoverLetter() {
                 if (!this.detailApp.coverLetter) return;
                 navigator.clipboard.writeText(this.detailApp.coverLetter);
                 this.copied = true;
                 setTimeout(() => { this.copied = false; }, 2000);
             }
         }">

        {{-- ─── Page Header ─── --}}
        <div class="flex items-center justify-between pb-4 shrink-0">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900">My Internship Applications</h2>
                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">
                    Real-time status updates from host company recruiters.
                </p>
            </div>
            <a href="{{ route('student.posts.index') }}"
               class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700
                      transition-colors flex items-center gap-1.5 shadow-xs shrink-0">
                <i class="fa-solid fa-plus w-4 h-4"></i>
                <span>Explore Open Roles</span>
            </a>
        </div>

        {{-- ─── Applications Container ─── --}}
        <div class="flex flex-col flex-1 min-h-0 overflow-hidden rounded-3xl bg-white border border-slate-200/80 shadow-xs">

            @if ($applications->isNotEmpty())

                {{-- Scrollable card list --}}
                <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-3.5">
                    @foreach ($applications as $app)
                        @php
                            $appComp    = $app->internshipPost->companyProfile;
                            $appLogo    = $appComp->logo_url;
                            $appHasLogo = $appComp->has_logo;
                            $appInitials= $appComp->initials;

                            $appResumePath = $app->getEffectiveResumePath();
                            $resumeExists  = $appResumePath &&
                                            (Storage::disk('local')->exists($appResumePath) ||
                                             Storage::disk('public')->exists($appResumePath));

                            $isRejected  = strtolower($app->status) === 'rejected';
                            $isAccepted  = strtolower($app->status) === 'accepted';
                            $isWithdrawn = strtolower($app->status) === 'withdrawn';
                            $canWithdraw = $app->canBeWithdrawn();
                        @endphp

                        {{-- ─── Application Card ─── --}}
                        <div @class([
                                'group relative rounded-2xl border bg-white p-4 sm:p-5 transition-all duration-200 hover:shadow-md',
                                'border-emerald-200 bg-emerald-50/30 hover:border-emerald-400/60' => $isAccepted,
                                'border-rose-200 bg-rose-50/20 hover:border-rose-300'             => $isRejected,
                                'border-slate-200/60 bg-slate-50/40 opacity-75'                   => $isWithdrawn,
                                'border-slate-200/80 hover:border-emerald-300/60'                 => !$isAccepted && !$isRejected && !$isWithdrawn,
                            ])>

                            {{-- Accepted ribbon --}}
                            @if ($isAccepted)
                                <div class="absolute top-3 right-3 inline-flex items-center gap-1 px-2.5 py-1
                                            rounded-full bg-emerald-600 text-white text-[10px] font-bold shadow-xs">
                                    <i class="fa-solid fa-trophy text-[9px]"></i>
                                    <span>Offer Accepted</span>
                                </div>
                            @elseif ($isWithdrawn)
                                <div class="absolute top-3 right-3 inline-flex items-center gap-1 px-2.5 py-1
                                            rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold">
                                    <i class="fa-solid fa-ban text-[9px]"></i>
                                    <span>Withdrawn</span>
                                </div>
                            @endif

                            {{-- ── Top Row: Company + Role info ── --}}
                            <div class="flex items-start gap-3 mb-4">

                                {{-- Company Logo / Initials --}}
                                @if ($appHasLogo && $appLogo)
                                    <a href="{{ route('student.companies.show', $appComp) }}" title="View {{ $appComp->company_name }} profile" class="shrink-0 mt-0.5">
                                        <img src="{{ $appLogo }}" alt="{{ $appComp->company_name }}"
                                             class="w-11 h-11 rounded-xl object-cover border border-slate-200 shadow-xs hover:opacity-90 transition-opacity">
                                    </a>
                                @else
                                    <a href="{{ route('student.companies.show', $appComp) }}" title="View {{ $appComp->company_name }} profile"
                                       class="w-11 h-11 rounded-xl bg-teal-700 text-white flex items-center
                                              justify-center font-bold text-sm shrink-0 shadow-xs mt-0.5 hover:bg-teal-800 transition-colors">
                                        {{ $appInitials }}
                                    </a>
                                @endif

                                {{-- Role & Company name --}}
                                <div class="min-w-0 flex-1 pr-2">
                                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base leading-tight truncate"
                                        title="{{ $app->internshipPost->title }}">
                                        {{ $app->internshipPost->title }}
                                    </h3>
                                    <p class="text-xs text-slate-500 font-semibold mt-0.5 flex items-center gap-1.5 flex-wrap">
                                        <i class="fa-solid fa-city text-slate-300 text-[10px]"></i>
                                        <a href="{{ route('student.companies.show', $appComp) }}"
                                           class="hover:text-emerald-600 hover:underline transition-colors font-medium text-slate-700">
                                            {{ $appComp->company_name }}
                                        </a>
                                        <span class="text-slate-300">•</span>
                                        <span>{{ $app->internshipPost->location }}</span>
                                    </p>

                                    {{-- Applied date + Resume badge --}}
                                    <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                                        <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 font-medium">
                                            <i class="fa-regular fa-calendar text-[10px]"></i>
                                            Applied {{ \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') }}
                                            <span class="text-slate-300">·</span>
                                            {{ \Carbon\Carbon::parse($app->applied_at)->diffForHumans() }}
                                        </span>
                                        @if ($resumeExists)
                                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-700 font-semibold
                                                         bg-emerald-50 border border-emerald-200/70 px-1.5 py-0.5 rounded-lg">
                                                <i class="fa-solid fa-file-circle-check text-[10px]"></i>
                                                <span class="truncate max-w-[120px]">{{ basename($appResumePath) }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- ── Progress Timeline ── --}}
                            <div class="mb-4 px-1">
                                <x-application-timeline :status="$app->status" />
                            </div>

                            {{-- ── Bottom Row: Recruiter Feedback + Actions ── --}}
                            <div class="flex items-end justify-between gap-3 pt-3.5 border-t border-slate-100 flex-wrap sm:flex-nowrap">

                                {{-- Recruiter Feedback --}}
                                <div class="flex items-start gap-2 min-w-0 flex-1">
                                    <div class="w-6 h-6 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="fa-solid fa-comment-dots text-[10px]"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">
                                            Recruiter Feedback
                                        </p>
                                        <p class="text-xs text-slate-600 leading-relaxed line-clamp-2"
                                           title="{{ $app->company_notes ?: 'Application under evaluation by recruitment team.' }}">
                                            {{ $app->company_notes ?: 'Application under evaluation by recruitment team.' }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="inline-flex items-center gap-1.5 shrink-0">
                                    {{-- Feature 1.8: View Application Details (Cover Letter & Resume Preview Modal) --}}
                                    <button type="button"
                                            @click="openDetailModal({
                                                id: {{ $app->id }},
                                                jobTitle: @js($app->internshipPost->title),
                                                companyName: @js($appComp->company_name),
                                                companyLogo: @js($appLogo ?? ''),
                                                companyInitials: @js($appInitials),
                                                hasLogo: @js($appHasLogo && $appLogo),
                                                location: @js($app->internshipPost->location),
                                                status: @js($app->status),
                                                appliedAt: @js(\Carbon\Carbon::parse($app->applied_at)->format('M d, Y')),
                                                appliedHuman: @js(\Carbon\Carbon::parse($app->applied_at)->diffForHumans()),
                                                coverLetter: @js($app->cover_letter ?? ''),
                                                companyNotes: @js($app->company_notes ?? ''),
                                                hasResume: @js($resumeExists),
                                                resumeName: @js($resumeExists ? basename($appResumePath) : ''),
                                                resumePreviewUrl: @js($resumeExists ? route('student.resume.preview', ['application_id' => $app->id]) : ''),
                                                resumeDownloadUrl: @js($resumeExists ? route('student.resume.preview', ['application_id' => $app->id, 'download' => 1]) : ''),
                                                isPdfOrImage: @js($resumeExists && in_array(strtolower(pathinfo(basename($appResumePath), PATHINFO_EXTENSION)), ['pdf', 'png', 'jpg', 'jpeg'])),
                                                jobUrl: @js(route('student.posts.show', $app->internshipPost))
                                            })"
                                            class="px-2.5 py-1.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300 text-xs font-bold inline-flex items-center gap-1.5 transition-all shadow-2xs cursor-pointer"
                                            title="View Application Details (Cover Letter & Resume)">
                                        <i class="fa-solid fa-eye text-[11px]"></i>
                                        <span>View</span>
                                    </button>

                                    {{-- Feature 1.5: Withdraw Application Button (pending / applied only) --}}
                                    @if ($canWithdraw)
                                        <button type="button"
                                                @click="openWithdrawModal('{{ route('student.applications.withdraw', $app) }}', '{{ addslashes($app->internshipPost->title) }}', '{{ addslashes($appComp->company_name) }}')"
                                                class="px-2.5 py-1.5 rounded-xl border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:border-rose-300 text-xs font-bold inline-flex items-center gap-1.5 transition-all shadow-2xs"
                                                title="Withdraw Application">
                                            <i class="fa-solid fa-arrow-rotate-left text-[11px]"></i>
                                            <span>Withdraw</span>
                                        </button>
                                    @endif

                                    @if ($resumeExists)
                                        <a href="{{ route('student.resume.preview', ['application_id' => $app->id]) }}"
                                           target="_blank"
                                           class="w-8 h-8 inline-flex items-center justify-center text-slate-500
                                                  hover:text-emerald-700 hover:bg-emerald-50 rounded-xl
                                                  border border-slate-200/70 transition-colors"
                                           title="View Submitted Resume">
                                            <i class="fa-solid fa-file-lines text-xs text-emerald-600"></i>
                                        </a>
                                    @endif

                                    <a href="{{ route('student.messages.start', $app) }}"
                                       class="w-8 h-8 inline-flex items-center justify-center text-slate-500
                                              hover:text-emerald-700 hover:bg-emerald-50 rounded-xl
                                              border border-slate-200/70 transition-colors"
                                       title="Message Host Company">
                                        <i class="fa-solid fa-comment-dots text-xs"></i>
                                    </a>

                                    <a href="{{ route('student.posts.show', $app->internshipPost) }}"
                                       class="w-8 h-8 inline-flex items-center justify-center text-emerald-600
                                              hover:text-emerald-800 hover:bg-emerald-50 rounded-xl
                                              border border-slate-200/70 transition-colors"
                                       title="View Job Details">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                        {{-- ─── End Application Card ─── --}}

                    @endforeach
                </div>

                {{-- Pagination footer --}}
                <div class="p-4 border-t border-slate-100 bg-white shrink-0">
                    {{ $applications->links() }}
                </div>

            @else
                {{-- Empty State --}}
                <div class="flex-1 flex flex-col items-center justify-center py-16 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto">
                        <i class="fa-solid fa-file-circle-xmark text-2xl text-slate-300"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">No applications on record</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                            Browse through our accredited internship vacancies and submit your first application.
                        </p>
                    </div>
                    <a href="{{ route('student.posts.index') }}"
                       class="inline-block px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold
                              hover:bg-emerald-700 transition-colors shadow-xs">
                        Browse Positions
                    </a>
                </div>
            @endif

        </div>

        {{-- ─── Feature 1.5: Withdraw Confirmation Modal (Alpine.js) ─── --}}
        <div x-show="withdrawModalOpen"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title" role="dialog" aria-modal="true"
             style="display: none;">
            
            {{-- Backdrop --}}
            <div x-show="withdrawModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="withdrawModalOpen = false"></div>

            <div class="flex min-h-full items-end sm:items-center justify-center p-4 text-center">
                <div x-show="withdrawModalOpen"
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     @click.stop
                     class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md p-6 border border-slate-100">

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-black text-slate-900 tracking-tight" id="modal-title">
                                Withdraw Application?
                            </h3>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Are you sure you want to withdraw your application for
                                <strong class="text-slate-900" x-text="jobTitle"></strong> at
                                <strong class="text-slate-900" x-text="companyName"></strong>?
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs flex items-start gap-2.5">
                        <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 shrink-0 text-xs"></i>
                        <span class="leading-relaxed">The host company recruitment team will be formally notified that you have withdrawn from candidacy.</span>
                    </div>

                    <form :action="withdrawUrl" method="POST" @submit="submitting = true" class="mt-6 flex items-center justify-end gap-2.5">
                        @csrf
                        <button type="button"
                                @click="withdrawModalOpen = false"
                                :disabled="submitting"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                            Keep Application
                        </button>
                        <button type="submit"
                                :disabled="submitting"
                                class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all shadow-xs inline-flex items-center gap-1.5 disabled:opacity-50">
                            <i class="fa-solid fa-spinner animate-spin text-xs" x-show="submitting" style="display: none;"></i>
                            <i class="fa-solid fa-arrow-rotate-left text-xs" x-show="!submitting"></i>
                            <span x-text="submitting ? 'Withdrawing...' : 'Yes, Withdraw Application'"></span>
                        </button>
                    </form>

                </div>
            </div>
        </div>

        {{-- ─── Feature 1.8: Application Detail Modal (Cover Letter & Resume Preview) ─── --}}
        @include('student.applications.modal-detail')

    </div>
</x-layout>
