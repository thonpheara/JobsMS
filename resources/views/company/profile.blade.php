<x-layout>
    @php
        $companyName = $company->company_name ?: ($user->name ?: 'Company');
        $nameParts = array_filter(explode(' ', trim($companyName)));
        $initials = count($nameParts) >= 2
            ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
            : strtoupper(substr($companyName, 0, 2));

        $verificationStatus = $company->verification_status ?? 'pending';

        $industryOptions = [
            'Software & Cloud Engineering',
            'Fintech & Banking',
            'E-Commerce & Retail',
            'Telecommunications',
            'Education & EdTech',
            'Healthcare & Biotech',
            'Media & Creative Agency',
            'Manufacturing & Logistics',
            'Consulting & Professional Services',
            'Non-Profit / NGO',
        ];
        $currentIndustry = old('industry', $company->industry ?? '');
        $isCustomIndustry = $currentIndustry !== '' && !in_array($currentIndustry, $industryOptions, true);

        $completenessItems = [
            ['label' => 'Logo', 'filled' => !empty($company->logo_path), 'tab' => 'company'],
            ['label' => 'Cover', 'filled' => !empty($company->cover_path), 'tab' => 'company'],
            ['label' => 'Industry', 'filled' => !empty($company->industry), 'tab' => 'company'],
            ['label' => 'Location', 'filled' => !empty($company->location), 'tab' => 'company'],
            ['label' => 'Hiring Lead', 'filled' => !empty($company->contact_person), 'tab' => 'contact'],
            ['label' => 'Phone', 'filled' => !empty($company->contact_phone), 'tab' => 'contact'],
            ['label' => 'Website', 'filled' => !empty($company->website), 'tab' => 'online'],
            ['label' => 'Address', 'filled' => !empty($company->address), 'tab' => 'online'],
            ['label' => 'About Us', 'filled' => !empty($company->description), 'tab' => 'online'],
        ];
        $filledCount = collect($completenessItems)->where('filled', true)->count();
        $completenessPercent = (int) round(($filledCount / count($completenessItems)) * 100);
        $missingItems = collect($completenessItems)->where('filled', false)->values();
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto"
         x-data="{
            tab: '{{ $errors->has('current_password') || $errors->has('password') || request('tab') === 'security' ? 'security' : request('tab', 'all') }}',
            logoPreview: null,
            logoFileName: '',
            logoError: '',
            coverPreview: null,
            coverFileName: '',
            coverError: '',
            isDirty: false,
            previewModalOpen: false,
            descLength: {{ mb_strlen(old('description', $company->description ?? '')) }},
            industryOpen: false,
            industrySelected: @js($isCustomIndustry ? '__other__' : $currentIndustry),
            customIndustry: @js($isCustomIndustry ? $currentIndustry : ''),
            industryOptions: @js($industryOptions),
            get resolvedIndustry() {
                return this.industrySelected === '__other__' ? this.customIndustry : this.industrySelected;
            },
            handleLogoChange(event) {
                const file = event.target.files[0];
                this.logoError = '';
                if (!file) return;
                if (file.size > 2 * 1024 * 1024) {
                    this.logoError = 'Logo image must be under 2MB (selected: ' + (file.size / (1024 * 1024)).toFixed(1) + 'MB).';
                    this.clearLogoSelection();
                    return;
                }
                this.logoFileName = file.name;
                this.isDirty = true;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.logoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            },
            clearLogoSelection() {
                this.logoPreview = null;
                this.logoFileName = '';
                if (this.$refs.logoInput) {
                    this.$refs.logoInput.value = '';
                }
            },
            handleCoverChange(event) {
                const file = event.target.files[0];
                this.coverError = '';
                if (!file) return;
                if (file.size > 4 * 1024 * 1024) {
                    this.coverError = 'Cover image must be under 4MB (selected: ' + (file.size / (1024 * 1024)).toFixed(1) + 'MB).';
                    this.clearCoverSelection();
                    return;
                }
                this.coverFileName = file.name;
                this.isDirty = true;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.coverPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            },
            clearCoverSelection() {
                this.coverPreview = null;
                this.coverFileName = '';
                if (this.$refs.coverInput) {
                    this.$refs.coverInput.value = '';
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
                    Host Company Profile
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Manage your organization credentials, hiring contacts, and branding displayed to student applicants.
                </p>
            </div>

            <div class="flex items-center gap-2.5 shrink-0">
                <button type="button"
                        @click="previewModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 hover:border-[#059669]/40 hover:bg-emerald-50/50 text-slate-700 hover:text-[#059669] text-xs font-semibold shadow-xs transition-all cursor-pointer">
                    <i class="fa-regular fa-eye w-3.5 h-3.5"></i>
                    <span>Preview Public Profile</span>
                </button>
            </div>
        </div>

        <!-- Verification Status Alert Banner -->
        @if ($verificationStatus === 'pending')
            <div class="p-4 rounded-2xl bg-amber-50/90 border border-amber-200/80 flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-clock w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs font-bold text-amber-900">Account Pending University Verification</h4>
                    <p class="text-xs text-amber-800/90 mt-0.5 leading-relaxed">
                        Your organization profile is currently under review by the University Admin. Complete all company details below to speed up verification.
                    </p>
                </div>
            </div>
        @elseif ($verificationStatus === 'rejected')
            <div class="p-4 rounded-2xl bg-rose-50/90 border border-rose-200/80 flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs font-bold text-rose-900">Verification Needs Attention (Rejected)</h4>
                    <p class="text-xs text-rose-800/90 mt-0.5 leading-relaxed">
                        @if ($company->rejection_reason)
                            <span class="font-semibold">Admin Feedback:</span> {{ $company->rejection_reason }}
                        @else
                            Your company verification was not approved. Please review and update your organization details below.
                        @endif
                    </p>
                </div>
            </div>
        @endif

        <!-- Sub-navigation Tabs Bar -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-200/70 overflow-x-auto text-xs gap-4">
            <div class="flex items-center gap-2 shrink-0">
                <!-- All Details Tab -->
                <button @click="tab = 'all'"
                        :class="tab === 'all' ? 'bg-[#D1FAE5] text-[#065F46] border-[#A7F3D0] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium border-transparent'"
                        class="px-3.5 py-1.5 rounded-lg border flex items-center gap-2 transition-colors cursor-pointer">
                    <i class="fa-solid fa-grip w-3.5 h-3.5"></i>
                    <span>All Details</span>
                </button>

                <!-- Company Info Tab -->
                <button @click="tab = 'company'"
                        :class="tab === 'company' ? 'bg-[#D1FAE5] text-[#065F46] border-[#A7F3D0] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium border-transparent'"
                        class="px-3.5 py-1.5 rounded-lg border flex items-center gap-2 transition-colors cursor-pointer">
                    <i class="fa-solid fa-building w-3.5 h-3.5"></i>
                    <span>Company Info</span>
                </button>

                <!-- Hiring Contact Tab -->
                <button @click="tab = 'contact'"
                        :class="tab === 'contact' ? 'bg-[#D1FAE5] text-[#065F46] border-[#A7F3D0] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium border-transparent'"
                        class="px-3.5 py-1.5 rounded-lg border flex items-center gap-2 transition-colors cursor-pointer">
                    <i class="fa-solid fa-user-check w-3.5 h-3.5"></i>
                    <span>Hiring Contact</span>
                </button>

                <!-- Online Presence Tab -->
                <button @click="tab = 'online'"
                        :class="tab === 'online' ? 'bg-[#D1FAE5] text-[#065F46] border-[#A7F3D0] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium border-transparent'"
                        class="px-3.5 py-1.5 rounded-lg border flex items-center gap-2 transition-colors cursor-pointer">
                    <i class="fa-solid fa-globe w-3.5 h-3.5"></i>
                    <span>Corporate Presence</span>
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
                        <span>Last updated {{ $company->updated_at ? $company->updated_at->diffForHumans() : 'recently' }}</span>
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

            <!-- Left Column: Company Overview & Quick Info (4 Cols - Sticky on scroll) -->
            <div class="lg:col-span-4 space-y-5 lg:sticky lg:top-6 lg:self-start">

                <!-- Company Overview Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col items-center text-center space-y-4">
                    <!-- Squircle Logo/Avatar with Live Preview & Online Dot -->
                    <div class="relative group">
                        <template x-if="logoPreview">
                            <img :src="logoPreview"
                                 alt="Logo Preview"
                                 class="w-20 h-20 rounded-2xl object-cover shadow-md shadow-[#059669]/20 border-2 border-[#059669]">
                        </template>
                        <template x-if="!logoPreview">
                            <div>
                                @if ($company->logo_path)
                                    <img src="{{ asset('storage/' . ltrim($company->logo_path, '/')) }}"
                                         alt="{{ $company->company_name }}"
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

                    <!-- Company Name & Industry -->
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 leading-tight">
                            {{ $company->company_name ?: 'Your Company' }}
                        </h2>
                        <p class="text-xs text-slate-400 mt-1" x-text="resolvedIndustry || 'Sector not specified'">
                            {{ $company->industry ?: 'Sector not specified' }}
                        </p>
                    </div>

                    <!-- Role & Status Badges -->
                    <div class="flex flex-wrap items-center justify-center gap-2 pt-1">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-[#D1FAE5] text-[#059669] border border-[#A7F3D0] flex items-center gap-1">
                            <i class="fa-solid fa-building w-3 h-3"></i>
                            <span>Company Portal</span>
                        </span>
                        <x-status-badge :status="$verificationStatus" />
                    </div>

                    <!-- Profile Completeness Bar -->
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
                                <span>All organization details are complete!</span>
                            </p>
                        @endif
                    </div>

                    <!-- Account Overview Section -->
                    <div class="w-full border-t border-slate-100 pt-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 text-left w-full block mb-3">
                            Company Overview
                        </span>

                        <div class="space-y-3 text-xs w-full">
                            <!-- Location -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="fa-solid fa-location-dot w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Location</span>
                                </div>
                                <span class="font-medium text-slate-700 truncate max-w-[140px]">{{ $company->location ?: 'Not specified' }}</span>
                            </div>

                            <!-- Contact Person -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="fa-solid fa-user w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Hiring Lead</span>
                                </div>
                                <span class="font-medium text-slate-700 truncate max-w-[130px]">{{ $company->contact_person ?: 'Not specified' }}</span>
                            </div>

                            <!-- Phone -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="fa-solid fa-phone w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Phone</span>
                                </div>
                                <span class="font-medium text-slate-700">{{ $company->contact_phone ?: 'Not specified' }}</span>
                            </div>

                            <!-- Website -->
                            @if ($company->website)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-slate-600">
                                        <i class="fa-solid fa-globe w-3.5 h-3.5 text-slate-400"></i>
                                        <span>Website</span>
                                    </div>
                                    <a href="{{ $company->website }}" target="_blank" class="font-semibold text-[#059669] hover:underline flex items-center gap-1 text-[11px]">
                                        <span>Visit</span>
                                        <i class="fa-solid fa-up-right-from-square w-3 h-3"></i>
                                    </a>
                                </div>
                            @endif

                            <!-- Verification (Dynamic) -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="fa-solid fa-shield-halved w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Status</span>
                                </div>
                                @if ($verificationStatus === 'verified')
                                    <span class="font-semibold text-emerald-600 flex items-center gap-1">
                                        <i class="fa-solid fa-check w-3 h-3"></i> Verified
                                    </span>
                                @elseif ($verificationStatus === 'rejected')
                                    <span class="font-semibold text-rose-600 flex items-center gap-1">
                                        <i class="fa-solid fa-xmark w-3 h-3"></i> Rejected
                                    </span>
                                @else
                                    <span class="font-semibold text-amber-600 flex items-center gap-1">
                                        <i class="fa-solid fa-clock w-3 h-3"></i> Pending Review
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recruiter Tip Box -->
                <div class="bg-[#D1FAE5]/60 border border-[#A7F3D0] rounded-2xl p-4.5 flex items-start gap-3">
                    <div class="w-6 h-6 rounded-lg bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-lightbulb w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-emerald-900">Recruiter Tip</h4>
                        <p class="text-xs text-emerald-800/80 leading-relaxed mt-1">
                            Complete organization details and verified contact info boost candidate application rates by over 40%.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Right Column: Forms Stack (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">

                <form action="{{ route('company.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6" @input="isDirty = true">
                    @csrf

                    <!-- Card 1: Company Profile Information -->
                    <div x-show="tab === 'all' || tab === 'company'" x-transition class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                        <!-- Card Header -->
                        <div class="flex items-start gap-3.5 pb-2">
                            <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-building w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Company Information</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Primary organization name, industry sector, city location, and corporate logo.
                                </p>
                            </div>
                        </div>

                        <!-- Logo Upload Row with Live Preview -->
                        <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                            <div class="relative shrink-0">
                                <template x-if="logoPreview">
                                    <img :src="logoPreview"
                                         alt="New Logo Preview"
                                         class="w-16 h-16 rounded-2xl object-cover border-2 border-[#059669] shadow-xs">
                                </template>
                                <template x-if="!logoPreview">
                                    <div>
                                        @if ($company->logo_path)
                                            <img src="{{ asset('storage/' . ltrim($company->logo_path, '/')) }}"
                                                 alt="{{ $company->company_name }}"
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
                                    <span class="block text-xs font-bold text-slate-900">Organization Logo</span>
                                    <button type="button"
                                            x-show="logoPreview"
                                            @click="clearLogoSelection()"
                                            class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 cursor-pointer">
                                        Remove selected
                                    </button>
                                </div>
                                <span class="block text-[11px] text-slate-500 mt-0.5">Upload a square image (PNG, JPG, WEBP, SVG, max 2MB).</span>
                                <div class="mt-2">
                                    <input type="file"
                                           x-ref="logoInput"
                                           name="logo"
                                           accept="image/*"
                                           @change="handleLogoChange($event)"
                                           class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-semibold file:bg-[#059669] file:text-white hover:file:bg-[#047857] cursor-pointer">
                                </div>
                                <p x-show="logoPreview" class="text-[11px] text-[#059669] font-medium mt-1.5 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>Previewing <span class="font-semibold" x-text="logoFileName"></span> — click Save Changes to apply.</span>
                                </p>
                                <p x-show="logoError" x-text="logoError" class="text-[11px] text-rose-600 font-semibold mt-1.5"></p>
                            </div>
                        </div>

                        <!-- Cover Banner Upload Row with Live Preview -->
                        <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <span class="block text-xs font-bold text-slate-900">Profile Cover Banner</span>
                                    <span class="block text-[11px] text-slate-500 mt-0.5">Recommended wide image (PNG, JPG, WEBP, max 4MB, e.g. 1200×300px).</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button"
                                            x-show="coverPreview"
                                            @click="clearCoverSelection()"
                                            class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 cursor-pointer">
                                        Remove selected
                                    </button>
                                    @if ($company->cover_path)
                                        <button type="submit"
                                                name="delete_cover"
                                                value="1"
                                                onclick="return confirm('Remove your company cover banner?');"
                                                class="px-2.5 py-1 text-[11px] font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg border border-rose-200 transition-colors cursor-pointer">
                                            <i class="fa-solid fa-trash-can mr-1"></i> Remove
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Cover Preview Box -->
                            <div class="relative h-28 rounded-xl overflow-hidden border border-slate-200 bg-gradient-to-r from-[#059669] via-emerald-600 to-teal-600">
                                <template x-if="coverPreview">
                                    <img :src="coverPreview" alt="New Cover Preview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!coverPreview">
                                    <div class="w-full h-full">
                                        @if ($company->cover_path)
                                            <img src="{{ asset('storage/' . ltrim($company->cover_path, '/')) }}"
                                                 alt="{{ $company->company_name }} Cover"
                                                 onerror="this.style.display='none';"
                                                 class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-white/80 text-xs font-medium gap-2">
                                                <i class="fa-regular fa-image text-base"></i>
                                                <span>Default Emerald Gradient Banner</span>
                                            </div>
                                        @endif
                                    </div>
                                </template>
                            </div>

                            <div>
                                <input type="file"
                                       x-ref="coverInput"
                                       name="cover"
                                       accept="image/*"
                                       @change="handleCoverChange($event)"
                                       class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-semibold file:bg-[#059669] file:text-white hover:file:bg-[#047857] cursor-pointer">
                                <p x-show="coverPreview" class="text-[11px] text-[#059669] font-medium mt-1.5 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>Previewing <span class="font-semibold" x-text="coverFileName"></span> — click Save Changes to apply.</span>
                                </p>
                                <p x-show="coverError" x-text="coverError" class="text-[11px] text-rose-600 font-semibold mt-1.5"></p>
                            </div>
                        </div>

                        <!-- Form Inputs -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Company Name -->
                            <div>
                                <label for="company_name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Company / Organization Name <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-building w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="company_name"
                                           name="company_name"
                                           value="{{ old('company_name', $company->company_name) }}"
                                           required
                                           placeholder="e.g. Acme Innovations Inc."
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- Industry (Custom Styled Alpine Dropdown + Custom Option) -->
                            <div class="relative" @click.outside="industryOpen = false">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Industry Sector <span class="text-rose-500">*</span>
                                </label>
                                <input type="hidden" name="industry" :value="resolvedIndustry">

                                <button type="button"
                                        @click="industryOpen = !industryOpen"
                                        class="relative w-full pl-10 pr-9 py-2.5 rounded-xl border bg-white text-xs text-left font-medium transition-all flex items-center justify-between cursor-pointer"
                                        :class="industryOpen ? 'border-[#059669] ring-2 ring-[#059669]/20' : 'border-slate-200 hover:border-slate-300'">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-briefcase w-4 h-4"></i>
                                    </span>
                                    <span class="truncate" :class="industrySelected ? 'text-slate-900' : 'text-slate-400'"
                                          x-text="industrySelected === '__other__' ? (customIndustry || 'Other (Specify below)') : (industrySelected || 'Select industry sector...')"></span>
                                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-150" :class="industryOpen ? 'rotate-180 text-[#059669]' : ''"></i>
                                    </span>
                                </button>

                                <!-- Dropdown Menu -->
                                <div x-show="industryOpen"
                                     x-transition.opacity.duration.150ms
                                     style="display: none;"
                                     class="absolute z-30 mt-1.5 w-full bg-white rounded-xl border border-slate-200 shadow-lg py-1.5 max-h-60 overflow-y-auto">
                                    <template x-for="opt in industryOptions" :key="opt">
                                        <button type="button"
                                                @click="industrySelected = opt; industryOpen = false; isDirty = true;"
                                                class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between transition-colors cursor-pointer"
                                                :class="industrySelected === opt ? 'bg-[#D1FAE5]/60 text-[#065F46] font-semibold' : 'text-slate-700 hover:bg-slate-50'">
                                            <span x-text="opt"></span>
                                            <i x-show="industrySelected === opt" class="fa-solid fa-check text-[#059669] text-[10px]"></i>
                                        </button>
                                    </template>
                                    <div class="border-t border-slate-100 my-1"></div>
                                    <button type="button"
                                            @click="industrySelected = '__other__'; industryOpen = false; isDirty = true;"
                                            class="w-full px-3.5 py-2 text-left text-xs flex items-center justify-between transition-colors cursor-pointer"
                                            :class="industrySelected === '__other__' ? 'bg-[#D1FAE5]/60 text-[#065F46] font-semibold' : 'text-slate-700 hover:bg-slate-50'">
                                        <span>Other (Specify custom industry)</span>
                                        <i x-show="industrySelected === '__other__'" class="fa-solid fa-check text-[#059669] text-[10px]"></i>
                                    </button>
                                </div>

                                <!-- Custom Industry Input when Other is selected -->
                                <div x-show="industrySelected === '__other__'" x-transition class="mt-2">
                                    <input type="text"
                                           x-model="customIndustry"
                                           placeholder="Type your industry sector..."
                                           class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- City / Location -->
                            <div class="sm:col-span-2">
                                <label for="location" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    City / Location <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-location-dot w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="location"
                                           name="location"
                                           value="{{ old('location', $company->location) }}"
                                           placeholder="e.g. Phnom Penh, Cambodia"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white font-semibold text-xs shadow-xs transition-all hover:translate-y-[-1px] cursor-pointer">
                                <i class="fa-solid fa-bookmark w-4 h-4 stroke-[2.2]"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </div>

                    <!-- Card 2: Hiring Contact & Recruitment Details -->
                    <div x-show="tab === 'all' || tab === 'contact'" x-transition class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                        <!-- Card Header -->
                        <div class="flex items-start gap-3.5 pb-2">
                            <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user-check w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Hiring Contact Person</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Contact point for student applicants and university administrator correspondence.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Contact Person -->
                            <div>
                                <label for="contact_person" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Hiring Contact Person <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-user w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="contact_person"
                                           name="contact_person"
                                           value="{{ old('contact_person', $company->contact_person) }}"
                                           placeholder="e.g. Sarah Jenkins (Talent Lead)"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- Contact Phone -->
                            <div>
                                <label for="contact_phone" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Contact Phone Number <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-phone w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="contact_phone"
                                           name="contact_phone"
                                           value="{{ old('contact_phone', $company->contact_phone) }}"
                                           placeholder="+855 12 345 678"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- Account Email (Read-only) -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Account Login Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-envelope w-4 h-4"></i>
                                    </div>
                                    <input type="email" value="{{ $user->email }}" disabled class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200/80 bg-slate-50 text-xs text-slate-500 font-medium cursor-not-allowed">
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white font-semibold text-xs shadow-xs transition-all hover:translate-y-[-1px] cursor-pointer">
                                <i class="fa-solid fa-bookmark w-4 h-4 stroke-[2.2]"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </div>

                    <!-- Card 3: Corporate Presence & Description -->
                    <div x-show="tab === 'all' || tab === 'online'" x-transition class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                        <!-- Card Header -->
                        <div class="flex items-start gap-3.5 pb-2">
                            <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-globe w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Corporate Presence &amp; Description</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Official company website, headquarters street address, and company culture overview.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Website URL -->
                            <div>
                                <label for="website" class="block text-xs font-semibold text-slate-700 mb-1.5">Official Website URL</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-globe w-4 h-4"></i>
                                    </div>
                                    <input type="url"
                                           id="website"
                                           name="website"
                                           value="{{ old('website', $company->website) }}"
                                           placeholder="https://company.com"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- Address -->
                            <div>
                                <label for="address" class="block text-xs font-semibold text-slate-700 mb-1.5">Headquarters / Street Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-location-dot w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="address"
                                           name="address"
                                           value="{{ old('address', $company->address) }}"
                                           placeholder="e.g. Building 12, Preah Monivong Blvd, Phnom Penh"
                                           class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">
                                </div>
                            </div>

                            <!-- About & Work Culture with Character Counter -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="description" class="block text-xs font-semibold text-slate-700">About Company &amp; Work Culture</label>
                                    <span class="text-[11px] font-medium"
                                          :class="descLength > 1800 ? 'text-amber-600' : 'text-slate-400'"
                                          x-text="descLength + ' / 2000'"></span>
                                </div>
                                <textarea id="description"
                                          name="description"
                                          rows="4"
                                          maxlength="2000"
                                          @input="descLength = $event.target.value.length"
                                          placeholder="Briefly describe what your organization does, your team culture, and what interns will learn..."
                                          class="w-full p-3 rounded-xl border border-slate-200 bg-white text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#059669]/20 focus:border-[#059669] transition-all font-medium">{{ old('description', $company->description) }}</textarea>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#059669] hover:bg-[#047857] text-white font-semibold text-xs shadow-xs transition-all hover:translate-y-[-1px] cursor-pointer">
                                <i class="fa-solid fa-bookmark w-4 h-4 stroke-[2.2]"></i>
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
                                Ensure your organization recruiter account remains protected by updating your credentials.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('company.password.update') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-4">
                            <!-- Current Password -->
                            <div>
                                <label for="company_current_password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Current Password <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-lock w-4 h-4"></i>
                                    </div>
                                    <input :type="showCurrent ? 'text' : 'password'"
                                           id="company_current_password"
                                           name="current_password"
                                           required
                                           placeholder="Enter current password"
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
                                <label for="company_password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    New Password <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-key w-4 h-4"></i>
                                    </div>
                                    <input :type="showNew ? 'text' : 'password'"
                                           id="company_password"
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
                                <label for="company_password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Confirm New Password <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-circle-check w-4 h-4"></i>
                                    </div>
                                    <input :type="showConfirm ? 'text' : 'password'"
                                           id="company_password_confirmation"
                                           name="password_confirmation"
                                           required
                                           placeholder="Re-type new password"
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

        <!-- Public Profile Preview Modal (Student View) -->
        <div x-show="previewModalOpen"
             x-transition.opacity.duration.200ms
             style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
             @keydown.escape.window="previewModalOpen = false">
            <div @click.outside="previewModalOpen = false"
                 class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full overflow-hidden">
                <!-- Banner -->
                <div class="h-32 sm:h-36 bg-gradient-to-r from-[#059669] via-emerald-600 to-teal-600 relative px-6 pt-4 flex items-start justify-between overflow-hidden">
                    <template x-if="coverPreview">
                        <img :src="coverPreview" alt="Cover Preview" class="absolute inset-0 w-full h-full object-cover">
                    </template>
                    @if ($company->cover_path)
                        <template x-if="!coverPreview">
                            <img src="{{ asset('storage/' . ltrim($company->cover_path, '/')) }}"
                                 alt="{{ $company->company_name }} Cover"
                                 onerror="this.style.display='none';"
                                 class="absolute inset-0 w-full h-full object-cover">
                        </template>
                    @endif
                    <div class="absolute inset-0 bg-slate-900/20"></div>
                    <span class="relative z-10 px-3 py-1 rounded-full bg-slate-900/55 backdrop-blur-sm text-white text-[11px] font-semibold shadow-xs">
                        <i class="fa-regular fa-eye mr-1"></i> Student Public View Preview
                    </span>
                    <button type="button"
                            @click="previewModalOpen = false"
                            class="relative z-10 w-8 h-8 rounded-full bg-slate-900/55 hover:bg-slate-900/75 backdrop-blur-sm text-white flex items-center justify-center transition-colors cursor-pointer shadow-xs">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-6 pb-6 relative z-10">
                    <div class="-mt-10 flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-5">
                        <div class="flex items-start gap-4">
                            <template x-if="logoPreview">
                                <img :src="logoPreview" class="w-20 h-20 rounded-2xl object-cover border-4 border-white shadow-md bg-white">
                            </template>
                            <template x-if="!logoPreview">
                                <div>
                                    @if ($company->logo_path)
                                        <img src="{{ asset('storage/' . ltrim($company->logo_path, '/')) }}"
                                             alt="{{ $company->company_name }}"
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
                            <div class="pt-12">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-lg font-bold text-slate-900">{{ $company->company_name ?: 'Your Company' }}</h3>
                                    @if ($verificationStatus === 'verified')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                                            <i class="fa-solid fa-circle-check"></i> Verified Host
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5" x-text="resolvedIndustry || 'Industry not specified'"></p>
                            </div>
                        </div>

                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold shrink-0">
                            <i class="fa-solid fa-briefcase text-[#059669]"></i>
                            <span>{{ $activePostsCount ?? 0 }} Active {{ ($activePostsCount ?? 0) === 1 ? 'Listing' : 'Listings' }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs mb-4">
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="fa-solid fa-location-dot w-4 text-slate-400"></i>
                            <span>{{ $company->location ?: 'Location not specified' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="fa-solid fa-user-tie w-4 text-slate-400"></i>
                            <span>Hiring Lead: <strong class="text-slate-800">{{ $company->contact_person ?: 'Not specified' }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="fa-solid fa-map-pin w-4 text-slate-400"></i>
                            <span class="truncate">{{ $company->address ?: 'Street address not specified' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="fa-solid fa-phone w-4 text-slate-400"></i>
                            <span>{{ $company->contact_phone ?: 'Phone not specified' }}</span>
                        </div>
                        @if ($company->website)
                            <div class="flex items-center gap-2 text-slate-600">
                                <i class="fa-solid fa-globe w-4 text-slate-400"></i>
                                <a href="{{ $company->website }}" target="_blank" class="text-[#059669] font-semibold hover:underline truncate">
                                    {{ $company->website }}
                                </a>
                            </div>
                        @endif
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="fa-regular fa-calendar-check w-4 text-slate-400"></i>
                            <span>Partner Since: <strong class="text-slate-800">{{ $company->created_at ? $company->created_at->format('M Y') : 'N/A' }}</strong></span>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">About Organization &amp; Culture</h4>
                        <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">{{ $company->description ?: 'No company description added yet. Add an overview in Corporate Presence to help students learn about your team.' }}</p>
                    </div>

                    <!-- Modal Footer -->
                    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                        <span class="text-[11px] text-slate-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>This is how students view your organization profile.</span>
                        </span>
                        <button type="button"
                                @click="previewModalOpen = false"
                                class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors cursor-pointer shrink-0">
                            Close Preview
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layout>
