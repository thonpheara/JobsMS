<x-layout>
    @php
        $nameParts = array_filter(explode(' ', trim($user->name ?? 'Student')));
        $initials = count($nameParts) >= 2
            ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
            : strtoupper(substr($user->name ?? 'ST', 0, 2));

        $hasResume = $student->resume_path && (Storage::disk('local')->exists($student->resume_path) || Storage::disk('public')->exists($student->resume_path));

        $initialSkillsRaw = old('skills_input', is_array($student->skills) ? implode(', ', $student->skills) : '');
        $initialSkills = array_values(array_filter(array_map('trim', explode(',', $initialSkillsRaw))));

        $completenessItems = [
            ['label' => 'Avatar', 'filled' => !empty($user->avatar_path), 'tab' => 'personal'],
            ['label' => 'Student ID', 'filled' => !empty($student->student_id_number), 'tab' => 'academic'],
            ['label' => 'Cohort Year', 'filled' => !empty($student->cohort_year), 'tab' => 'academic'],
            ['label' => 'Major', 'filled' => !empty($student->major), 'tab' => 'academic'],
            ['label' => 'GPA', 'filled' => $student->gpa !== null, 'tab' => 'academic'],
            ['label' => 'Skills', 'filled' => is_array($student->skills) && count($student->skills) > 0, 'tab' => 'personal'],
            ['label' => 'Bio', 'filled' => !empty($student->bio), 'tab' => 'personal'],
            ['label' => 'Resume PDF', 'filled' => $hasResume, 'tab' => 'resume'],
        ];
        $filledCount = collect($completenessItems)->where('filled', true)->count();
        $completenessPercent = (int) round(($filledCount / count($completenessItems)) * 100);
        $missingItems = collect($completenessItems)->where('filled', false)->values();
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto"
         x-data="{
            tab: '{{ $errors->has('current_password') || $errors->has('password') || request('tab') === 'security' ? 'security' : request('tab', 'all') }}',
            avatarPreview: null,
            avatarFileName: '',
            isDirty: false,
            previewModalOpen: false,
            bioLength: {{ mb_strlen(old('bio', $student->bio ?? '')) }},
            skills: @js($initialSkills),
            skillDraft: '',
            suggestedSkills: ['PHP', 'Laravel', 'JavaScript', 'React', 'Tailwind CSS', 'Python', 'MySQL', 'Figma', 'UI/UX', 'Git'],
            addSkill(raw = null) {
                const val = (raw ?? this.skillDraft).trim().replace(/,$/, '');
                if (!val) return;
                if (!this.skills.some(s => s.toLowerCase() === val.toLowerCase())) {
                    this.skills.push(val);
                    this.isDirty = true;
                }
                if (raw === null) this.skillDraft = '';
            },
            removeSkill(index) {
                this.skills.splice(index, 1);
                this.isDirty = true;
            },
            handleAvatarChange(event) {
                const file = event.target.files[0];
                if (!file) return;
                this.avatarFileName = file.name;
                this.isDirty = true;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.avatarPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            },
            clearAvatarSelection() {
                this.avatarPreview = null;
                this.avatarFileName = '';
                if (this.$refs.avatarInput) {
                    this.$refs.avatarInput.value = '';
                }
            }
         }">

        <!-- Top Header -->
        <div class="pb-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 sm:hidden mb-2">
                    <button @click="sidebarOpen = true" class="p-1.5 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100">
                        <i class="fa-solid fa-bars w-5 h-5"></i>
                    </button>
                    <span class="text-xs font-semibold text-slate-500">Job Management System</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    Student Profile
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Manage your academic credentials, contact information, and resume for host company review.
                </p>
            </div>

            <div class="flex items-center gap-2.5 shrink-0">
                <button type="button"
                        @click="previewModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 hover:border-[#059669]/40 hover:bg-emerald-50/50 text-slate-700 hover:text-[#059669] text-xs font-semibold shadow-xs transition-all cursor-pointer">
                    <i class="fa-regular fa-eye w-3.5 h-3.5"></i>
                    <span>Preview as Recruiter</span>
                </button>
            </div>
        </div>

        <!-- Sub-navigation Tabs Bar -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-200/70 overflow-x-auto text-xs gap-4">
            <div class="flex items-center gap-2 shrink-0">
                <!-- All Settings Tab -->
                <button @click="tab = 'all'"
                        :class="tab === 'all' ? 'bg-[#D1FAE5] text-[#065F46] border-[#A7F3D0] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium border-transparent'"
                        class="px-3.5 py-1.5 rounded-lg border flex items-center gap-2 transition-colors cursor-pointer">
                    <i class="fa-solid fa-grip w-3.5 h-3.5"></i>
                    <span>All Details</span>
                </button>

                <!-- Academic Tab -->
                <button @click="tab = 'academic'"
                        :class="tab === 'academic' ? 'bg-[#D1FAE5] text-[#065F46] border-[#A7F3D0] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium border-transparent'"
                        class="px-3.5 py-1.5 rounded-lg border flex items-center gap-2 transition-colors cursor-pointer">
                    <i class="fa-solid fa-graduation-cap w-3.5 h-3.5"></i>
                    <span>Academic Credentials</span>
                </button>

                <!-- Personal Info Tab -->
                <button @click="tab = 'personal'"
                        :class="tab === 'personal' ? 'bg-[#D1FAE5] text-[#065F46] border-[#A7F3D0] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium border-transparent'"
                        class="px-3.5 py-1.5 rounded-lg border flex items-center gap-2 transition-colors cursor-pointer">
                    <i class="fa-solid fa-user w-3.5 h-3.5"></i>
                    <span>Personal &amp; Skills</span>
                </button>

                <!-- Resume Tab -->
                <button @click="tab = 'resume'"
                        :class="tab === 'resume' ? 'bg-[#D1FAE5] text-[#065F46] border-[#A7F3D0] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium border-transparent'"
                        class="px-3.5 py-1.5 rounded-lg border flex items-center gap-2 transition-colors cursor-pointer">
                    <i class="fa-solid fa-file-lines w-3.5 h-3.5"></i>
                    <span>Official Resume</span>
                </button>

                <!-- Security & Password Tab -->
                <button @click="tab = 'security'"
                        :class="tab === 'security' ? 'bg-[#D1FAE5] text-[#065F46] border-[#A7F3D0] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium border-transparent'"
                        class="px-3.5 py-1.5 rounded-lg border flex items-center gap-2 transition-colors cursor-pointer">
                    <i class="fa-solid fa-shield-halved w-3.5 h-3.5"></i>
                    <span>Security &amp; Password</span>
                </button>
            </div>

            <!-- Last Updated / Unsaved Changes Indicator -->
            <div class="hidden sm:flex items-center gap-1.5 text-xs font-medium shrink-0">
                <template x-if="isDirty">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200/80">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Unsaved changes</span>
                    </span>
                </template>
                <template x-if="!isDirty">
                    <span class="inline-flex items-center gap-1.5 text-slate-400 font-normal">
                        <i class="fa-regular fa-clock w-3.5 h-3.5"></i>
                        <span>Last updated {{ $student->updated_at ? $student->updated_at->diffForHumans() : 'recently' }}</span>
                    </span>
                </template>
            </div>
        </div>

        <!-- Error Alerts -->
        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <i class="fa-solid fa-circle-exclamation w-4 h-4 text-rose-600 shrink-0"></i>
                    <span>Please correct the errors below</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Left Column: Profile Summary & Completeness (4 Cols - Sticky on scroll) -->
            <div class="lg:col-span-4 space-y-5 lg:sticky lg:top-6 lg:self-start">

                <!-- Profile Summary Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col items-center text-center space-y-4">
                    <!-- Squircle Avatar with Live Preview & Online Dot -->
                    <div class="relative group">
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview"
                                 alt="Avatar Preview"
                                 class="w-20 h-20 rounded-2xl object-cover shadow-md shadow-[#059669]/20 border-2 border-[#059669]">
                        </template>
                        <template x-if="!avatarPreview">
                            <div>
                                @if ($user->avatar_path)
                                    <img src="{{ asset('storage/' . ltrim($user->avatar_path, '/')) }}"
                                         alt="{{ $user->name }}"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                         class="w-20 h-20 rounded-2xl object-cover shadow-md shadow-[#059669]/20 border-2 border-white">
                                    <div style="display: none;" class="w-20 h-20 rounded-2xl bg-[#059669] text-white text-2xl font-black items-center justify-center shadow-md shadow-[#059669]/20">
                                        {{ $initials }}
                                    </div>
                                @else
                                    <div class="w-20 h-20 rounded-2xl bg-[#059669] text-white text-2xl font-black flex items-center justify-center shadow-md shadow-[#059669]/20">
                                        {{ $initials }}
                                    </div>
                                @endif
                            </div>
                        </template>
                        <span class="w-4 h-4 rounded-full bg-emerald-500 border-2 border-white absolute -bottom-1 -right-1"></span>
                    </div>

                    <!-- User Name & Email -->
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 leading-tight">
                            {{ $user->name }}
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ $user->email }}
                        </p>
                    </div>

                    <!-- Role Badge -->
                    <div class="flex items-center justify-center gap-2 pt-1">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-[#D1FAE5] text-[#059669] border border-[#A7F3D0] flex items-center gap-1">
                            <i class="fa-solid fa-graduation-cap w-3 h-3"></i>
                            <span>Student Portal</span>
                        </span>
                    </div>

                    <!-- Profile Completeness Progress Bar -->
                    <div class="w-full border-t border-slate-100 pt-3.5 text-left">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-bold text-slate-700">Profile Completeness</span>
                            <span class="font-extrabold {{ $completenessPercent === 100 ? 'text-emerald-600' : 'text-slate-800' }}">
                                {{ $completenessPercent }}%
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 {{ $completenessPercent === 100 ? 'bg-emerald-500' : ($completenessPercent >= 60 ? 'bg-[#059669]' : 'bg-amber-500') }}"
                                 style="width: {{ $completenessPercent }}%"></div>
                        </div>

                        @if ($missingItems->isNotEmpty())
                            <div class="mt-2.5 flex flex-wrap gap-1.5">
                                @foreach ($missingItems as $item)
                                    <button type="button"
                                            @click="tab = '{{ $item['tab'] }}'"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-[#059669] text-[10px] font-semibold transition-colors cursor-pointer">
                                        <i class="fa-solid fa-plus text-[9px]"></i>
                                        <span>{{ $item['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Your profile is 100% ready for recruiters!</span>
                            </p>
                        @endif
                    </div>

                    <!-- Academic Overview Section -->
                    <div class="w-full border-t border-slate-100 pt-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 text-left w-full block mb-3">
                            Academic Overview
                        </span>

                        <div class="space-y-3 text-xs w-full">
                            <!-- Student ID -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="fa-solid fa-circle-check w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Student ID</span>
                                </div>
                                <span class="font-medium text-slate-700 truncate max-w-[140px]">{{ $student->student_id_number ?: 'Not specified' }}</span>
                            </div>

                            <!-- GPA -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="fa-solid fa-award w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Cumulative GPA</span>
                                </div>
                                <span class="font-semibold text-[#059669]">{{ $student->gpa !== null ? number_format($student->gpa, 2) : 'N/A' }} / 4.00</span>
                            </div>

                            <!-- Cohort Year -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="fa-solid fa-calendar-days w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Cohort Year</span>
                                </div>
                                <span class="font-medium text-slate-700">{{ $student->cohort_year ?: 'N/A' }}</span>
                            </div>

                            <!-- Degree Major -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="fa-solid fa-book-open w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Degree Major</span>
                                </div>
                                <span class="font-medium text-slate-700 truncate max-w-[130px]" title="{{ $student->major }}">{{ $student->major ?: 'Not set' }}</span>
                            </div>

                            <!-- Resume Status -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="fa-solid fa-file-lines w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Resume File</span>
                                </div>
                                @if ($hasResume)
                                    <a href="{{ route('student.resume.preview') }}" target="_blank" class="font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 hover:underline cursor-pointer" title="View uploaded resume">
                                        <i class="fa-solid fa-check w-3 h-3"></i> Uploaded
                                    </a>
                                @else
                                    <button type="button" @click="tab = 'resume'" class="font-semibold text-amber-600 hover:text-amber-700 hover:underline flex items-center gap-1 cursor-pointer" title="Click to upload resume">
                                        <span>Missing</span>
                                        <i class="fa-solid fa-arrow-up-from-bracket text-[10px]"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Tip Box -->
                <div class="bg-[#D1FAE5]/60 border border-[#A7F3D0] rounded-2xl p-4.5 flex items-start gap-3">
                    <div class="w-6 h-6 rounded-lg bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-lightbulb w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-emerald-900">Academic Tip</h4>
                        <p class="text-xs text-emerald-800/80 leading-relaxed mt-1">
                            Keep your cumulative GPA and technical skills updated so Smart Match can recommend the best jobs and internships for you.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Right Column: Forms Stack (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">

                <form action="{{ route('student.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6" @input="isDirty = true">
                    @csrf
                    <input type="hidden" name="active_tab" :value="tab">

                    <!-- Card 1: Academic Credentials -->
                    <div x-show="tab === 'all' || tab === 'academic'" x-transition class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                        <!-- Card Header -->
                        <div class="flex items-start gap-3.5 pb-2">
                            <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-graduation-cap w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Academic Credentials</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Update your institutional ID, graduation cohort year, and cumulative GPA details.
                                </p>
                            </div>
                        </div>

                        <!-- Form Inputs -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Student ID Number -->
                            <div>
                                <label for="student_id_number" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Student ID Number
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-circle-check w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="student_id_number"
                                           name="student_id_number"
                                           value="{{ old('student_id_number', $student->student_id_number) }}"
                                           placeholder="e.g. STU-2024-088"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- Cohort Year -->
                            <div>
                                <label for="cohort_year" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Cohort Graduation Year
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-calendar-days w-4 h-4"></i>
                                    </div>
                                    <input type="number"
                                           id="cohort_year"
                                           name="cohort_year"
                                           value="{{ old('cohort_year', $student->cohort_year ?? 2024) }}"
                                           min="2020"
                                           max="2035"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- Faculty / Department -->
                            <div>
                                <label for="department" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Faculty / Department
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-building w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="department"
                                           name="department"
                                           value="{{ old('department', $student->department) }}"
                                           placeholder="e.g. Computer Science & IT"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- Degree Major -->
                            <div>
                                <label for="major" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Degree Major
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-book-open w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="major"
                                           name="major"
                                           value="{{ old('major', $student->major) }}"
                                           placeholder="e.g. Software Engineering"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- Cumulative GPA -->
                            <div class="sm:col-span-2">
                                <label for="gpa" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Cumulative GPA (Scale: 0.00 - 4.00)
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-award w-4 h-4"></i>
                                    </div>
                                    <input type="number"
                                           step="0.01"
                                           min="0.00"
                                           max="4.00"
                                           id="gpa"
                                           name="gpa"
                                           value="{{ old('gpa', $student->gpa) }}"
                                           placeholder="e.g. 3.50"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                                <span class="text-[11px] text-slate-400 mt-1 block">Enter your current cumulative GPA as shown on your university transcript.</span>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white font-semibold text-xs shadow-xs transition-all hover:translate-y-[-1px] cursor-pointer">
                                <i class="fa-solid fa-bookmark w-4 h-4 stroke-[2.2]"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </div>

                    <!-- Card 2: Personal Information & Technical Skills -->
                    <div x-show="tab === 'all' || tab === 'personal'" x-transition class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                        <!-- Card Header -->
                        <div class="flex items-start gap-3.5 pb-2">
                            <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Personal Information &amp; Skills</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Update your portrait avatar, contact phone, technical competencies, and professional bio.
                                </p>
                            </div>
                        </div>

                        <!-- Profile Picture / Avatar Upload Row with Live Preview -->
                        <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                            <div class="relative shrink-0">
                                <template x-if="avatarPreview">
                                    <img :src="avatarPreview"
                                         alt="New Avatar Preview"
                                         class="w-16 h-16 rounded-2xl object-cover border-2 border-[#059669] shadow-xs">
                                </template>
                                <template x-if="!avatarPreview">
                                    <div>
                                        @if ($user->avatar_path)
                                            <img src="{{ asset('storage/' . ltrim($user->avatar_path, '/')) }}"
                                                 alt="{{ $user->name }}"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                 class="w-16 h-16 rounded-2xl object-cover border-2 border-white shadow-xs">
                                            <div style="display: none;" class="w-16 h-16 rounded-2xl bg-[#059669] text-white font-black text-xl items-center justify-center shadow-xs">
                                                {{ $initials }}
                                            </div>
                                        @else
                                            <div class="w-16 h-16 rounded-2xl bg-[#059669] text-white font-black text-xl flex items-center justify-center shadow-xs">
                                                {{ $initials }}
                                            </div>
                                        @endif
                                    </div>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="block text-xs font-bold text-slate-900">Profile Picture / Avatar</span>
                                    <button type="button"
                                            x-show="avatarPreview"
                                            @click="clearAvatarSelection()"
                                            class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 cursor-pointer">
                                        Remove selected
                                    </button>
                                </div>
                                <span class="block text-[11px] text-slate-500 mt-0.5">Upload a square or portrait photo (PNG, JPG, WEBP, max 2MB).</span>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <input type="file"
                                           x-ref="avatarInput"
                                           name="avatar"
                                           accept="image/*"
                                           @change="handleAvatarChange($event)"
                                           class="block w-full sm:w-auto text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-semibold file:bg-[#059669] file:text-white hover:file:bg-[#047857] cursor-pointer">
                                    @if($user->avatar_path)
                                        <button type="submit"
                                                name="delete_avatar"
                                                value="1"
                                                onclick="return confirm('Remove your profile picture?');"
                                                class="px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-xl border border-rose-200 transition-colors cursor-pointer">
                                            <i class="fa-solid fa-trash-can mr-1"></i> Remove
                                        </button>
                                    @endif
                                </div>
                                <p x-show="avatarPreview" class="text-[11px] text-[#059669] font-medium mt-1.5 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>Previewing <span class="font-semibold" x-text="avatarFileName"></span> — click Save Changes to apply.</span>
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Full Name (Read-only) -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-user w-4 h-4"></i>
                                    </div>
                                    <input type="text" value="{{ $user->name }}" disabled class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200/80 bg-slate-50 text-xs text-slate-500 font-medium cursor-not-allowed">
                                </div>
                            </div>

                            <!-- Email Address (Read-only) -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-envelope w-4 h-4"></i>
                                    </div>
                                    <input type="email" value="{{ $user->email }}" disabled class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200/80 bg-slate-50 text-xs text-slate-500 font-medium cursor-not-allowed">
                                </div>
                            </div>

                            <!-- Contact Phone Number -->
                            <div class="sm:col-span-2">
                                <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1.5">Contact Phone Number</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-phone w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="phone"
                                           name="phone"
                                           value="{{ old('phone', $student->phone) }}"
                                           placeholder="+855 12 345 678"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- Interactive Skills Tag Chips -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Technical Skills &amp; Competencies
                                </label>
                                <input type="hidden" name="skills_input" :value="skills.join(', ')">

                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                            <i class="fa-solid fa-code w-4 h-4"></i>
                                        </div>
                                        <input type="text"
                                               x-model="skillDraft"
                                               @keydown.enter.prevent="addSkill()"
                                               @keydown.,.prevent="addSkill()"
                                               placeholder="Type a skill (e.g. Laravel) and press Enter..."
                                               class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                    </div>
                                    <button type="button"
                                            @click="addSkill()"
                                            class="px-3.5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors shrink-0 cursor-pointer">
                                        <i class="fa-solid fa-plus mr-1"></i> Add
                                    </button>
                                </div>

                                <!-- Active Skill Chips -->
                                <div class="flex flex-wrap gap-1.5 mt-2.5" x-show="skills.length > 0">
                                    <template x-for="(skill, idx) in skills" :key="idx">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#D1FAE5] text-[#065F46] text-[11px] font-semibold border border-[#A7F3D0]">
                                            <span x-text="skill"></span>
                                            <button type="button"
                                                    @click="removeSkill(idx)"
                                                    class="text-[#065F46]/70 hover:text-rose-600 transition-colors cursor-pointer"
                                                    title="Remove skill">
                                                <i class="fa-solid fa-xmark text-[10px]"></i>
                                            </button>
                                        </span>
                                    </template>
                                </div>

                                <!-- Quick Skill Suggestions -->
                                <div class="mt-2.5 flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mr-1">Quick add:</span>
                                    <template x-for="sug in suggestedSkills" :key="sug">
                                        <button type="button"
                                                x-show="!skills.some(s => s.toLowerCase() === sug.toLowerCase())"
                                                @click="addSkill(sug)"
                                                class="px-2 py-0.5 rounded-md bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-[#059669] text-[10px] font-semibold transition-colors cursor-pointer">
                                            + <span x-text="sug"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Professional Bio with Character Counter -->
                            <div class="sm:col-span-2">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="bio" class="block text-xs font-semibold text-slate-700">Professional Summary / Bio</label>
                                    <span class="text-[11px] font-medium"
                                          :class="bioLength > 900 ? 'text-amber-600' : 'text-slate-400'"
                                          x-text="bioLength + ' / 1000'"></span>
                                </div>
                                <textarea id="bio"
                                          name="bio"
                                          rows="3"
                                          maxlength="1000"
                                          @input="bioLength = $event.target.value.length"
                                          placeholder="Brief summary of your academic projects, career interests, and passion..."
                                          class="w-full p-3 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">{{ old('bio', $student->bio) }}</textarea>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white font-semibold text-xs shadow-xs transition-all hover:translate-y-[-1px] cursor-pointer">
                                <i class="fa-solid fa-bookmark w-4 h-4 stroke-[2.2]"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </div>

                    <!-- Card 3: Official Resume -->
                    <div x-show="tab === 'all' || tab === 'resume'" x-transition class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                        <!-- Card Header -->
                        <div class="flex items-start gap-3.5 pb-2">
                            <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-file-lines w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Official Resume</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Upload and maintain your latest curriculum vitae in PDF or Word document format (max 5MB).
                                </p>
                            </div>
                        </div>

                        @if ($hasResume)
                            <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/80 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-file-circle-check w-5 h-5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <span class="block text-xs font-bold text-slate-900 truncate" title="{{ basename($student->resume_path) }}">{{ basename($student->resume_path) }}</span>
                                        <span class="block text-[11px] text-emerald-700 font-medium">Ready for employer review</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0 flex-wrap">
                                    @php
                                        $resumeFileName = basename($student->resume_path);
                                        $resumeExt = strtolower(pathinfo($resumeFileName, PATHINFO_EXTENSION));
                                    @endphp
                                    @if(in_array($resumeExt, ['pdf', 'png', 'jpg', 'jpeg']))
                                        <a href="{{ route('student.resume.preview') }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-[#059669] text-white hover:bg-[#047857] text-xs font-semibold transition-colors inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-arrow-up-right-from-square w-3 h-3"></i>
                                            <span>Preview</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('student.resume.preview', ['download' => 1]) }}" download="{{ $resumeFileName }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition-colors inline-flex items-center gap-1.5" title="Download {{ $resumeFileName }}">
                                        <i class="fa-solid fa-download w-3.5 h-3.5"></i>
                                        <span>Download</span>
                                    </a>
                                    <button type="button"
                                            onclick="if(confirm('Are you sure you want to delete your resume?')) document.getElementById('delete-resume-form').submit();"
                                            class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-semibold transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                                        <i class="fa-solid fa-trash-can w-3.5 h-3.5"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-circle-exclamation w-5 h-5"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-amber-900">No Resume Uploaded Yet</h4>
                                    <p class="text-[11px] text-amber-800/80 mt-0.5">Please upload your resume in PDF or Word format so host companies can review your profile when you apply.</p>
                                </div>
                            </div>
                        @endif

                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2">
                            <label class="block text-xs font-bold text-slate-800">
                                {{ $hasResume ? 'Replace Current Resume (PDF, DOCX, max 5MB)' : 'Upload New Resume (PDF, DOCX, max 5MB)' }}
                            </label>
                            <input type="file"
                                   name="resume"
                                   accept=".pdf,.doc,.docx"
                                   class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#059669] file:text-white hover:file:bg-[#047857] cursor-pointer">
                            <span class="block text-[11px] text-slate-400">Select file and click "Save Changes" below to upload.</span>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white font-semibold text-xs shadow-xs transition-all hover:translate-y-[-1px] cursor-pointer">
                                <i class="fa-solid fa-arrow-up-from-bracket w-4 h-4 stroke-[2.2]"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </div>

                </form>

                <!-- Card 4: Security & Password -->
                <div x-show="tab === 'all' || tab === 'security'"
                     x-transition
                     class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5"
                     x-data="{
                        showCurrent: false,
                        showNew: false,
                        showConfirm: false,
                        newPass: '',
                        get passStrength() {
                            if (!this.newPass) return 0;
                            let score = 0;
                            if (this.newPass.length >= 8) score++;
                            if (/[A-Z]/.test(this.newPass) && /[a-z]/.test(this.newPass)) score++;
                            if (/[0-9]/.test(this.newPass)) score++;
                            if (/[^A-Za-z0-9]/.test(this.newPass)) score++;
                            return score;
                        }
                     }">
                    <!-- Card Header -->
                    <div class="flex items-start gap-3.5 pb-2">
                        <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-shield-halved w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Change Account Password</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Ensure your student account remains protected by updating your password regularly.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('student.password.update') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-4">
                            <!-- Current Password -->
                            <div>
                                <label for="student_current_password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Current Password <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-lock w-4 h-4"></i>
                                    </div>
                                    <input :type="showCurrent ? 'text' : 'password'"
                                           id="student_current_password"
                                           name="current_password"
                                           required
                                           placeholder="Enter your current password"
                                           class="w-full pl-10 pr-10 py-2.5 rounded-xl border {{ $errors->has('current_password') ? 'border-rose-300 ring-rose-200' : 'border-slate-200' }} bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                    <button type="button"
                                            @click="showCurrent = !showCurrent"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
                                        <i class="fa-solid text-xs" :class="showCurrent ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                @error('current_password')
                                    <p class="text-[11px] text-rose-600 font-medium mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- New Password + Strength Meter -->
                            <div>
                                <label for="student_password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    New Password <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-key w-4 h-4"></i>
                                    </div>
                                    <input :type="showNew ? 'text' : 'password'"
                                           id="student_password"
                                           name="password"
                                           x-model="newPass"
                                           required
                                           minlength="8"
                                           placeholder="Minimum 8 characters"
                                           class="w-full pl-10 pr-10 py-2.5 rounded-xl border {{ $errors->has('password') ? 'border-rose-300 ring-rose-200' : 'border-slate-200' }} bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                    <button type="button"
                                            @click="showNew = !showNew"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
                                        <i class="fa-solid text-xs" :class="showNew ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                <!-- Strength Bars -->
                                <div x-show="newPass.length > 0" x-transition class="mt-2 space-y-1">
                                    <div class="grid grid-cols-4 gap-1.5">
                                        <div class="h-1 rounded-full transition-colors" :class="passStrength >= 1 ? (passStrength <= 1 ? 'bg-rose-500' : (passStrength <= 2 ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-slate-200'"></div>
                                        <div class="h-1 rounded-full transition-colors" :class="passStrength >= 2 ? (passStrength <= 2 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-slate-200'"></div>
                                        <div class="h-1 rounded-full transition-colors" :class="passStrength >= 3 ? 'bg-emerald-500' : 'bg-slate-200'"></div>
                                        <div class="h-1 rounded-full transition-colors" :class="passStrength >= 4 ? 'bg-emerald-600' : 'bg-slate-200'"></div>
                                    </div>
                                    <p class="text-[11px] font-medium"
                                       :class="passStrength <= 1 ? 'text-rose-600' : (passStrength <= 2 ? 'text-amber-600' : 'text-emerald-600')"
                                       x-text="passStrength <= 1 ? 'Weak — use 8+ chars with numbers & symbols' : (passStrength <= 2 ? 'Fair — add uppercase, numbers, or symbols' : (passStrength === 3 ? 'Good password' : 'Strong password'))"></p>
                                </div>
                                @error('password')
                                    <p class="text-[11px] text-rose-600 font-medium mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="student_password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Confirm New Password <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-circle-check w-4 h-4"></i>
                                    </div>
                                    <input :type="showConfirm ? 'text' : 'password'"
                                           id="student_password_confirmation"
                                           name="password_confirmation"
                                           required
                                           placeholder="Re-type your new password"
                                           class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                    <button type="button"
                                            @click="showConfirm = !showConfirm"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
                                        <i class="fa-solid text-xs" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white font-semibold text-xs shadow-xs transition-all hover:translate-y-[-1px] cursor-pointer">
                                <i class="fa-solid fa-lock w-3.5 h-3.5"></i>
                                <span>Update Password</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <!-- Recruiter View Preview Modal -->
        <div x-show="previewModalOpen"
             x-transition.opacity.duration.200ms
             style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
             @keydown.escape.window="previewModalOpen = false">
            <div @click.outside="previewModalOpen = false"
                 class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full overflow-hidden">
                <!-- Banner -->
                <div class="h-24 bg-gradient-to-r from-[#059669] via-emerald-600 to-teal-600 relative px-6 pt-4 flex items-start justify-between">
                    <span class="px-2.5 py-1 rounded-full bg-white/20 backdrop-blur-xs text-white text-[11px] font-semibold">
                        <i class="fa-regular fa-eye mr-1"></i> Recruiter Profile View Preview
                    </span>
                    <button type="button"
                            @click="previewModalOpen = false"
                            class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors cursor-pointer">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-6 pb-6 relative z-10">
                    <div class="-mt-10 flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-5">
                        <div class="flex items-end gap-4">
                            <template x-if="avatarPreview">
                                <img :src="avatarPreview" class="w-20 h-20 rounded-2xl object-cover border-4 border-white shadow-md bg-white">
                            </template>
                            <template x-if="!avatarPreview">
                                <div>
                                    @if ($user->avatar_path)
                                        <img src="{{ asset('storage/' . ltrim($user->avatar_path, '/')) }}"
                                             alt="{{ $user->name }}"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                             class="w-20 h-20 rounded-2xl object-cover border-4 border-white shadow-md bg-white">
                                        <div style="display: none;" class="w-20 h-20 rounded-2xl bg-[#059669] text-white text-2xl font-black items-center justify-center border-4 border-white shadow-md">
                                            {{ $initials }}
                                        </div>
                                    @else
                                        <div class="w-20 h-20 rounded-2xl bg-[#059669] text-white text-2xl font-black flex items-center justify-center border-4 border-white shadow-md">
                                            {{ $initials }}
                                        </div>
                                    @endif
                                </div>
                            </template>
                            <div class="pb-1">
                                <h3 class="text-lg font-bold text-slate-900">{{ $user->name }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $student->major ?: 'Major not specified' }}
                                    @if ($student->department)
                                        · {{ $student->department }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-[#059669] border border-emerald-200/80 text-xs font-bold shrink-0">
                            <i class="fa-solid fa-award"></i>
                            <span>GPA: {{ $student->gpa !== null ? number_format($student->gpa, 2) : 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs mb-4">
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="fa-solid fa-envelope w-4 text-slate-400"></i>
                            <span class="truncate">{{ $user->email }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="fa-solid fa-phone w-4 text-slate-400"></i>
                            <span>{{ $student->phone ?: 'Phone not specified' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="fa-solid fa-id-card w-4 text-slate-400"></i>
                            <span>ID: <strong class="text-slate-800">{{ $student->student_id_number ?: 'N/A' }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="fa-solid fa-calendar-days w-4 text-slate-400"></i>
                            <span>Cohort: <strong class="text-slate-800">{{ $student->cohort_year ?: 'N/A' }}</strong></span>
                        </div>
                    </div>

                    <!-- Skills -->
                    <div class="mb-4" x-show="skills.length > 0">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Technical Skills</h4>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="(skill, idx) in skills" :key="idx">
                                <span class="px-2.5 py-1 rounded-lg bg-[#D1FAE5] text-[#065F46] text-[11px] font-semibold border border-[#A7F3D0]" x-text="skill"></span>
                            </template>
                        </div>
                    </div>

                    <!-- Bio -->
                    <div class="mb-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">About Candidate</h4>
                        <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">{{ $student->bio ?: 'No professional summary added yet.' }}</p>
                    </div>

                    <!-- Resume Status -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-medium">Official Resume</span>
                        @if ($hasResume)
                            <span class="inline-flex items-center gap-1.5 text-emerald-700 font-bold">
                                <i class="fa-solid fa-file-circle-check"></i> Attached &amp; Ready
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-amber-600 font-semibold">
                                <i class="fa-solid fa-circle-exclamation"></i> Not Uploaded
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden Delete Resume Form -->
        <form id="delete-resume-form" action="{{ route('student.resume.delete') }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

    </div>
</x-layout>
