<x-layout>
    <div class="space-y-6">
        
        <!-- Back Link -->
        <div class="flex items-center justify-between">
            <a href="{{ route('student.posts.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-emerald-600 transition-colors">
                <i class="fa-solid fa-arrow-left w-4 h-4"></i>
                <span>Back to all opportunities</span>
            </a>

            {{-- ❤️ Bookmark Toggle (show page) --}}
            <div x-data="{
                     bookmarked: {{ $isBookmarked ? 'true' : 'false' }},
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
                <button @click="toggle()"
                        :disabled="loading"
                        :title="bookmarked ? 'Remove from saved items' : 'Save opportunity'"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-rose-400/40"
                        :class="bookmarked
                            ? 'bg-rose-50 text-rose-600 border-rose-200 hover:bg-rose-100'
                            : 'bg-white text-slate-500 border-slate-200 hover:bg-rose-50 hover:text-rose-500 hover:border-rose-200'">
                    <i class="text-sm transition-all duration-150"
                       :class="[
                           bookmarked ? 'fa-solid fa-heart' : 'fa-regular fa-heart',
                           loading ? 'animate-pulse' : ''
                       ]"></i>
                    <span x-text="bookmarked ? 'Saved' : 'Save Opportunity'"></span>
                </button>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">

            <!-- Left Main Column (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Header Card -->
                <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            {{-- Opportunity Type Badge --}}
                            @if($post->isInternship())
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold tracking-wide bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1.5">
                                    <span>🎓</span>
                                    <span>Internship</span>
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold tracking-wide bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1.5">
                                    <span>💼</span>
                                    <span>{{ $post->opportunity_type_label }}</span>
                                </span>
                            @endif

                            {{-- Work Mode Badge --}}
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 flex items-center gap-1.5">
                                <i class="fa-solid {{ $post->type === 'remote' ? 'fa-house-laptop' : ($post->type === 'hybrid' ? 'fa-building-user' : 'fa-building') }} text-[11px] text-slate-500"></i>
                                <span>{{ ucfirst(str_replace('_', ' ', $post->type ?? 'on_site')) }}</span>
                            </span>

                            @if($post->experience_level)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                    {{ $post->experience_level_label }}
                                </span>
                            @endif

                            @if($post->category)
                                <span class="px-3 py-1 rounded-full text-xs font-bold tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $post->category }}
                                </span>
                            @endif
                        </div>

                        <span class="text-base sm:text-lg font-black text-emerald-600">
                            {{ $post->compensation_display }}
                        </span>
                    </div>

                    <div class="flex items-start gap-4">
                        @php
                            $compLogo = $post->companyProfile->logo_url;
                            $compHasLogo = $post->companyProfile->has_logo;
                            $compInitials = $post->companyProfile->initials;
                        @endphp
                        @if($compHasLogo && $compLogo)
                            <a href="{{ route('student.companies.show', $post->companyProfile) }}" title="View {{ $post->companyProfile->company_name }} profile" class="shrink-0">
                                <img src="{{ $compLogo }}" alt="{{ $post->companyProfile->company_name }}" class="w-14 h-14 rounded-2xl object-cover border border-slate-200 shadow-xs hover:opacity-90 transition-opacity">
                            </a>
                        @else
                            <a href="{{ route('student.companies.show', $post->companyProfile) }}" title="View {{ $post->companyProfile->company_name }} profile" class="w-14 h-14 rounded-2xl bg-teal-700 text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-xs hover:bg-teal-800 transition-colors">
                                {{ $compInitials }}
                            </a>
                        @endif
                        <div class="min-w-0 flex-1">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight">
                                {{ $post->title }}
                            </h2>
                            <p class="text-sm font-semibold text-slate-600 mt-1 flex items-center gap-1.5 flex-wrap">
                                <i class="fa-solid fa-city w-4 h-4 text-slate-400"></i>
                                <a href="{{ route('student.companies.show', $post->companyProfile) }}" class="text-slate-800 hover:text-emerald-600 hover:underline transition-colors font-bold">
                                    {{ $post->companyProfile->company_name }}
                                </a>
                                @if($post->companyProfile->is_verified ?? false)
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        <i class="fa-solid fa-circle-check text-sky-500"></i> Verified
                                    </span>
                                @endif
                                <span class="text-slate-300">•</span>
                                <span>{{ $post->location }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Meta Tags Row -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4 border-t border-slate-100 text-xs">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="block text-slate-500 text-[10px] uppercase font-bold tracking-wider">{{ $post->isInternship() ? 'Duration' : 'Type' }}</span>
                            <span class="font-bold text-slate-900">
                                @if($post->isInternship())
                                    {{ $post->duration_weeks ? $post->duration_weeks . ' Weeks' : 'Flexible' }}
                                @else
                                    {{ $post->opportunity_type_label }}
                                @endif
                            </span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="block text-slate-500 text-[10px] uppercase font-bold tracking-wider">Experience</span>
                            <span class="font-bold text-slate-900">{{ $post->experience_level_label ?? 'Any Level' }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="block text-slate-500 text-[10px] uppercase font-bold tracking-wider">Open Slots</span>
                            <span class="font-bold text-slate-900">{{ $post->slots }} Candidate{{ $post->slots > 1 ? 's' : '' }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="block text-slate-500 text-[10px] uppercase font-bold tracking-wider">Deadline</span>
                            <span class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($post->deadline)->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Detailed Description -->
                <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-6 text-slate-700 text-sm leading-relaxed">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Role Overview</h3>
                        <p class="whitespace-pre-line text-slate-600">{{ $post->description }}</p>
                    </div>

                    @if ($post->responsibilities)
                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-base font-bold text-slate-900 mb-2">Key Responsibilities</h3>
                            <div class="whitespace-pre-line text-slate-600 bg-slate-50 p-4.5 rounded-2xl border border-slate-100 text-xs sm:text-sm leading-relaxed">
                                {{ $post->responsibilities }}
                            </div>
                        </div>
                    @endif

                    @if ($post->requirements)
                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-base font-bold text-slate-900 mb-2">Technical & Educational Requirements</h3>
                            <div class="whitespace-pre-line text-slate-600 bg-slate-50 p-4.5 rounded-2xl border border-slate-100 text-xs sm:text-sm leading-relaxed">
                                {{ $post->requirements }}
                            </div>
                        </div>
                    @endif
                </div>

            </div>

            <!-- Right Sidebar Column (1 col) -->
            <div class="space-y-6">

                <!-- Application Submission Panel -->
                <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-4">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane w-4.5 h-4.5 text-emerald-600"></i>
                        Application Submission
                    </h3>

                    @if ($existingApplication && $existingApplication->status !== 'withdrawn')
                        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-900">Application Status</span>
                                <x-status-badge :status="$existingApplication->status" />
                            </div>
                            <p class="text-xs text-emerald-700">
                                You submitted your candidacy on {{ \Carbon\Carbon::parse($existingApplication->applied_at)->format('M d, Y') }}.
                            </p>
                            @if ($existingApplication->company_notes)
                                <div class="p-3 bg-white rounded-xl text-xs text-slate-700 border border-emerald-100">
                                    <strong class="block text-[11px] uppercase font-bold text-slate-600 mb-1">Company Note:</strong>
                                    {{ $existingApplication->company_notes }}
                                </div>
                            @endif

                            @if ($existingApplication->canBeWithdrawn())
                                <div class="pt-2 border-t border-emerald-200/60 flex items-center justify-between text-xs">
                                    <span class="text-slate-500">Changed your mind?</span>
                                    <a href="{{ route('student.applications.index') }}" class="text-rose-600 hover:text-rose-700 font-bold inline-flex items-center gap-1 hover:underline">
                                        <i class="fa-solid fa-arrow-rotate-left text-[10px]"></i>
                                        <span>Withdraw in My Applications &rarr;</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        @if ($existingApplication && $existingApplication->status === 'withdrawn')
                            <div class="p-3 rounded-2xl bg-slate-100 border border-slate-200 text-xs text-slate-600 flex items-start gap-2">
                                <i class="fa-solid fa-circle-info text-slate-400 mt-0.5 shrink-0"></i>
                                <span>You previously withdrew your application for this position. You may submit a new application below if you would like to re-apply.</span>
                            </div>
                        @endif

                        <!-- Application Form -->
                        <form action="{{ route('student.posts.apply', $post) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <div>
                                <label for="cover_letter" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Statement of Intent / Cover Letter <span class="text-rose-500">*</span>
                                </label>
                                <textarea id="cover_letter" 
                                          name="cover_letter" 
                                          rows="4" 
                                          required 
                                          placeholder="Describe your technical background, relevant projects, and motivation for joining {{ $post->companyProfile->company_name }}..."
                                          class="w-full p-3 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-900">{{ old('cover_letter') }}</textarea>
                                <p class="text-[10px] text-slate-600 mt-1">Minimum 30 characters explaining your background and motivation.</p>
                            </div>

                            <!-- Resume Attachment Section (Feature 1.4: Custom Resume per Application) -->
                            @php
                                $hasProfileResume = $student?->resume_path && (Storage::disk('local')->exists($student->resume_path) || Storage::disk('public')->exists($student->resume_path));
                                $profileResumeName = $hasProfileResume ? basename($student->resume_path) : null;
                            @endphp

                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3"
                                 x-data="{ resumeSource: '{{ $hasProfileResume ? 'profile' : 'custom' }}' }">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Resume / Curriculum Vitae <span class="text-rose-500">*</span>
                                </label>

                                {{-- Hidden field sent with form --}}
                                <input type="hidden" name="resume_source" :value="resumeSource">

                                @if ($hasProfileResume)
                                    {{-- Radio Toggle: Profile vs Custom --}}
                                    <div class="flex gap-2">
                                        {{-- Option 1: Use Profile Resume --}}
                                        <button type="button"
                                                @click="resumeSource = 'profile'"
                                                :class="resumeSource === 'profile'
                                                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                                    : 'bg-white text-slate-600 border-slate-200 hover:border-emerald-300 hover:text-emerald-600'"
                                                class="flex-1 flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl border text-[11px] font-bold transition-all duration-150 focus:outline-none">
                                            <i class="fa-solid fa-circle-check text-[10px]"
                                               :class="resumeSource === 'profile' ? 'opacity-100' : 'opacity-40'"></i>
                                            <span>Use Profile Resume</span>
                                        </button>

                                        {{-- Option 2: Upload Custom Resume --}}
                                        <button type="button"
                                                @click="resumeSource = 'custom'"
                                                :class="resumeSource === 'custom'
                                                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                                    : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300 hover:text-indigo-600'"
                                                class="flex-1 flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl border text-[11px] font-bold transition-all duration-150 focus:outline-none">
                                            <i class="fa-solid fa-cloud-arrow-up text-[10px]"
                                               :class="resumeSource === 'custom' ? 'opacity-100' : 'opacity-40'"></i>
                                            <span>Upload Tailored CV</span>
                                        </button>
                                    </div>

                                    {{-- Profile Resume Card (shown when source = profile) --}}
                                    <div x-show="resumeSource === 'profile'"
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-emerald-200 text-xs">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-8 h-8 rounded-lg bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-file-circle-check text-xs"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="font-bold text-slate-900 truncate block text-xs" title="{{ $profileResumeName }}">{{ $profileResumeName }}</span>
                                                <span class="text-[10px] text-emerald-700 font-medium">Profile resume — ready to send</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <a href="{{ route('student.resume.preview') }}" target="_blank"
                                               class="px-2.5 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50 rounded-lg border border-emerald-200/70 inline-flex items-center gap-1">
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                                <span>View</span>
                                            </a>
                                        </div>
                                    </div>

                                    {{-- Custom Resume Upload (shown when source = custom) --}}
                                    <div x-show="resumeSource === 'custom'"
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         class="space-y-1.5">
                                        <input type="file"
                                               name="resume"
                                               accept=".pdf,.doc,.docx"
                                               :required="resumeSource === 'custom'"
                                               class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-100 file:text-indigo-800 hover:file:bg-indigo-200 cursor-pointer">
                                        <p class="text-[10px] text-slate-500">PDF, DOC, or DOCX — Max 5 MB. This resume will be used <strong>only for this application</strong>.</p>
                                    </div>

                                @else
                                    {{-- No profile resume → must upload --}}
                                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/80 text-xs space-y-2">
                                        <div class="flex items-start gap-2 text-amber-800 font-medium">
                                            <i class="fa-solid fa-circle-exclamation text-amber-600 w-4 h-4 mt-0.5 shrink-0"></i>
                                            <span>No resume in your profile yet. Please upload a resume to continue.</span>
                                        </div>
                                        <input type="file"
                                               name="resume"
                                               required
                                               accept=".pdf,.doc,.docx"
                                               class="block w-full text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#059669] file:text-white hover:file:bg-[#047857] cursor-pointer">
                                        <span class="text-[10px] text-amber-700/90 block">This resume will be saved to your profile and sent to the host company.</span>
                                    </div>
                                @endif
                            </div>

                            <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-paper-plane w-4 h-4"></i>
                                <span>Submit Official Application</span>
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Host Organization Profile Card -->
                <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600">About the Host Organization</h4>
                    <div class="flex items-center gap-3">
                        @if($compHasLogo && $compLogo)
                            <a href="{{ route('student.companies.show', $post->companyProfile) }}" title="View {{ $post->companyProfile->company_name }} profile">
                                <img src="{{ $compLogo }}" alt="{{ $post->companyProfile->company_name }}" class="w-12 h-12 rounded-xl object-cover shrink-0 border border-slate-200 shadow-xs hover:opacity-90 transition-opacity">
                            </a>
                        @else
                            <a href="{{ route('student.companies.show', $post->companyProfile) }}" title="View {{ $post->companyProfile->company_name }} profile" class="w-12 h-12 rounded-xl bg-teal-700 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-xs hover:bg-teal-800 transition-colors">
                                {{ $compInitials }}
                            </a>
                        @endif
                        <div class="min-w-0 flex-1">
                            <h5 class="text-sm font-extrabold text-slate-900 truncate">
                                <a href="{{ route('student.companies.show', $post->companyProfile) }}" class="hover:text-emerald-600 transition-colors">
                                    {{ $post->companyProfile->company_name }}
                                </a>
                            </h5>
                            <span class="text-xs text-slate-500 font-medium truncate block">{{ $post->companyProfile->industry }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">{{ $post->companyProfile->description }}</p>
                    <div class="pt-3 border-t border-slate-100 text-xs text-slate-600 space-y-1">
                        <div>Industry: <strong class="text-slate-800">{{ $post->companyProfile->industry }}</strong></div>
                        @if ($post->companyProfile->website)
                            <div>Website: <a href="{{ $post->companyProfile->website }}" target="_blank" class="text-emerald-600 hover:underline">{{ $post->companyProfile->website }}</a></div>
                        @endif
                    </div>
                    <a href="{{ route('student.companies.show', $post->companyProfile) }}"
                       class="mt-2 block w-full text-center py-2 px-3 rounded-xl bg-slate-50 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-bold text-xs border border-slate-200 hover:border-emerald-200 transition-colors">
                        View Company Profile & All Openings &rarr;
                    </a>
                </div>

            </div>

        </div>

    </div>
</x-layout>
