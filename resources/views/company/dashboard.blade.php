<x-layout>
    <!-- Script Component Definition for Company Dashboard Charts -->
    <script>
        function topPostsBarChart(serverData) {
            return {
                chartData: serverData || { labels: [], fullTitles: [], data: [], total: 0, hasData: false },
                chart: null,
                init() {
                    this.$nextTick(() => {
                        if (this.chartData.hasData) {
                            this.renderChart();
                        }
                    });
                },
                renderChart() {
                    const canvas = document.getElementById('topPostsBarCanvas');
                    if (!canvas || typeof Chart === 'undefined') return;
                    const ctx = canvas.getContext('2d');

                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.chartData.labels,
                            datasets: [{
                                label: 'Applications',
                                data: this.chartData.data,
                                backgroundColor: '#059669',
                                hoverBackgroundColor: '#047857',
                                borderRadius: 8,
                                borderSkipped: false,
                                maxBarThickness: 42,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: '#0F172A',
                                    titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: 'bold' },
                                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                                    padding: 10,
                                    cornerRadius: 10,
                                    callbacks: {
                                        title: (items) => {
                                            const idx = items[0].dataIndex;
                                            return this.chartData.fullTitles[idx] || items[0].label;
                                        },
                                        label: (item) => ` ${item.parsed.y} Applications received`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    border: { display: false },
                                    ticks: {
                                        color: '#64748B',
                                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '600' }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: '#F1F5F9', drawTicks: false },
                                    ticks: {
                                        precision: 0,
                                        color: '#64748B',
                                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }
                                    }
                                }
                            }
                        }
                    });
                }
            };
        }

        function applicantStatusDonutChart(serverData) {
            return {
                donutData: serverData || {
                    labels: ['Applied', 'Shortlisted', 'Accepted', 'Rejected'],
                    data: [0, 0, 0, 0],
                    colors: ['#0EA5E9', '#6366F1', '#10B981', '#F43F5E'],
                    total: 0,
                    placementRate: 0,
                    counts: { applied: 0, shortlisted: 0, accepted: 0, rejected: 0 },
                    percentages: { applied: 0, shortlisted: 0, accepted: 0, rejected: 0 }
                },
                chart: null,
                init() {
                    this.$nextTick(() => {
                        this.renderChart();
                    });
                },
                renderChart() {
                    const canvas = document.getElementById('companyStatusDonutCanvas');
                    if (!canvas || typeof Chart === 'undefined') return;
                    const ctx = canvas.getContext('2d');

                    const hasData = this.donutData.data && this.donutData.data.some(v => v > 0);
                    const chartData = hasData ? this.donutData.data : [1];
                    const chartColors = hasData ? this.donutData.colors : ['#E2E8F0'];
                    const chartLabels = hasData ? this.donutData.labels : ['No Applications'];

                    this.chart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: chartLabels,
                            datasets: [{
                                data: chartData,
                                backgroundColor: chartColors,
                                borderWidth: 3,
                                borderColor: '#FFFFFF',
                                hoverOffset: hasData ? 6 : 0,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '72%',
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    enabled: hasData,
                                    backgroundColor: '#0F172A',
                                    titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: 'bold' },
                                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                                    padding: 10,
                                    cornerRadius: 10,
                                    callbacks: {
                                        label: (item) => {
                                            const count = item.parsed;
                                            const total = this.donutData.total;
                                            const pct = total > 0 ? Math.round((count / total) * 100) : 0;
                                            return ` ${item.label}: ${count} (${pct}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            };
        }

        if (typeof Alpine !== 'undefined') {
            Alpine.data('topPostsBarChart', topPostsBarChart);
            Alpine.data('applicantStatusDonutChart', applicantStatusDonutChart);
        } else {
            document.addEventListener('alpine:init', () => {
                Alpine.data('topPostsBarChart', topPostsBarChart);
                Alpine.data('applicantStatusDonutChart', applicantStatusDonutChart);
            });
        }
    </script>

    <div class="space-y-6">

        <!-- Top Header & Quick Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-slate-200/60">
            <div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900">
                    Welcome, {{ $company->company_name ?? Auth::user()->name }}!
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Review candidate submissions, monitor recruitment pipeline progress, and manage active internship vacancies.
                </p>
            </div>
            <div class="flex items-center gap-2.5 self-start sm:self-auto shrink-0">
                <a href="{{ route('company.applicants.index') }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition-colors">
                    <i class="fa-solid fa-table-columns text-slate-400"></i>
                    <span>Pipeline Board</span>
                </a>
                <a href="{{ route('company.posts.create') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-[#059669] hover:bg-[#047857] text-white shadow-xs transition-colors">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Post Internship</span>
                </a>
            </div>
        </div>

        <!-- Organization Verification Status Alerts -->
        @if($company && $company->verification_status === 'pending')
            <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-800 flex items-start gap-3 text-xs shadow-2xs">
                <div class="w-7 h-7 rounded-xl bg-amber-100/80 text-amber-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                </div>
                <div class="flex-1">
                    <h5 class="font-bold text-amber-900">Company Account Pending Institutional Verification</h5>
                    <p class="mt-0.5 text-amber-700 leading-relaxed text-[11px]">
                        Your organization profile is currently being reviewed by the university internship administration. Once verified, your vacancy listings will be published to enrolled students.
                    </p>
                </div>
            </div>
        @elseif($company && $company->verification_status === 'rejected')
            <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-800 flex items-start gap-3 text-xs shadow-2xs">
                <div class="w-7 h-7 rounded-xl bg-rose-100/80 text-rose-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                </div>
                <div class="flex-1">
                    <h5 class="font-bold text-rose-900">Verification Rejected</h5>
                    <p class="mt-0.5 text-rose-700 leading-relaxed text-[11px]">
                        Reason: {{ $company->rejection_reason ?: 'Profile does not meet institutional verification criteria.' }} Please update your <a href="{{ route('company.profile') }}" class="underline font-bold hover:text-rose-950">company profile</a> or contact the university coordinator.
                    </p>
                </div>
            </div>
        @endif

        <!-- 4 Key Performance Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            
            <!-- 1. Active Listings -->
            <a href="{{ route('company.posts.index') }}" 
               class="group p-4 sm:p-5 rounded-2xl bg-white border border-[#E5E7EB] shadow-xs hover:border-[#059669]/50 transition-all block">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-[#059669] transition-colors">
                        Active Listings
                    </span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#D1FAE5] text-[#059669] border border-[#A7F3D0]/60 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-list-check text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-1.5">
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900">{{ $stats['active_posts'] }}</h3>
                        <span class="text-[11px] text-slate-400">/ {{ $stats['total_posts'] }} total</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Vetted & Published</span>
                    </p>
                </div>
            </a>

            <!-- 2. Total Applicants -->
            <a href="{{ route('company.applicants.index') }}" 
               class="group p-4 sm:p-5 rounded-2xl bg-white border border-[#E5E7EB] shadow-xs hover:border-[#059669]/50 transition-all block">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-[#059669] transition-colors">
                        Total Applicants
                    </span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#D1FAE5] text-[#059669] border border-[#A7F3D0]/60 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-user-group text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900">{{ $stats['total_applicants'] }}</h3>
                    <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5">Received candidacies</p>
                </div>
            </a>

            <!-- 3. Pending Reviews -->
            <a href="{{ route('company.applicants.index', ['status' => 'pending']) }}" 
               class="group p-4 sm:p-5 rounded-2xl bg-white border border-[#E5E7EB] shadow-xs hover:border-amber-400/60 transition-all block">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-amber-600 transition-colors">
                        Pending Reviews
                    </span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-200/70 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-hourglass-half text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <h3 class="text-xl sm:text-2xl font-black text-amber-600">{{ $stats['pending_review'] }}</h3>
                    @if($stats['pending_review'] > 0)
                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                            Action req.
                        </span>
                    @else
                        <span class="text-[10px] font-medium text-slate-400">Up to date</span>
                    @endif
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5">Awaiting review</p>
            </a>

            <!-- 4. Accepted / Placements -->
            <a href="{{ route('company.applicants.index', ['status' => 'accepted']) }}" 
               class="group p-4 sm:p-5 rounded-2xl bg-white border border-[#E5E7EB] shadow-xs hover:border-emerald-400/60 transition-all block">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-emerald-700 transition-colors">
                        Offers Accepted
                    </span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/70 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-award text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <h3 class="text-xl sm:text-2xl font-black text-emerald-700">{{ $stats['accepted'] }}</h3>
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                        {{ $stats['interviewed'] }} active
                    </span>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5">Confirmed placements</p>
            </a>
        </div>

        <!-- ========================================================================= -->
        <!-- FEATURE 2.5: RECRUITMENT INTELLIGENCE & ANALYTICS CHARTS (lg:grid-cols-12)-->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">

            <!-- Chart 1: Bar Chart — Applications received per post (Top 5 posts) -->
            <div class="lg:col-span-7 p-6 sm:p-7 rounded-3xl bg-white border border-[#E5E7EB] shadow-xs flex flex-col justify-between"
                 x-data="topPostsBarChart(@js($topPostsChart))">
                
                <!-- Card Header -->
                <div class="flex items-center justify-between gap-3 pb-3.5 border-b border-gray-100 shrink-0">
                    <div>
                        <h4 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center">
                                <i class="fa-solid fa-chart-column text-xs"></i>
                            </span>
                            <span>Applications per Listing</span>
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">Top 5 vacancies ranked by student applications</p>
                    </div>
                    <a href="{{ route('company.posts.index') }}" class="text-xs font-semibold text-[#059669] hover:underline whitespace-nowrap shrink-0 flex items-center gap-1 group">
                        <span>Manage Listings</span>
                        <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                </div>

                <!-- Canvas Area / Empty State -->
                <div class="relative w-full flex-1 min-h-[220px] sm:min-h-[240px] my-3 flex items-center justify-center">
                    <template x-if="chartData.hasData">
                        <canvas id="topPostsBarCanvas" class="w-full h-full max-h-[260px]"></canvas>
                    </template>
                    <template x-if="!chartData.hasData">
                        <div class="w-full h-52 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/60 flex flex-col items-center justify-center text-center p-6">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-2.5">
                                <i class="fa-solid fa-chart-column text-lg"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-800">No applications received yet</p>
                            <p class="text-[11px] text-slate-400 mt-0.5 max-w-xs">Publish vacancies and receive student candidacies to view application volume metrics.</p>
                        </div>
                    </template>
                </div>

                <!-- Bottom Legend & Summary -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100 shrink-0 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3 rounded-xs bg-[#059669] inline-block"></span>
                        <span class="font-semibold text-slate-700">Candidacy Submissions</span>
                    </div>
                    <span class="text-slate-500 font-medium">
                        Top posts volume: <strong class="text-slate-900 font-bold" x-text="chartData.total"></strong> applicants
                    </span>
                </div>
            </div>

            <!-- Chart 2: Donut Chart — Applicant Status Breakdown -->
            <div class="lg:col-span-5 p-6 sm:p-7 rounded-3xl bg-white border border-[#E5E7EB] shadow-xs flex flex-col justify-between"
                 x-data="applicantStatusDonutChart(@js($donutData))">
                
                <!-- Card Header -->
                <div class="flex items-center justify-between gap-3 pb-3.5 border-b border-gray-100 shrink-0">
                    <div>
                        <h4 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center">
                                <i class="fa-solid fa-chart-pie text-xs"></i>
                            </span>
                            <span>Candidacy Outcomes</span>
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">Status distribution across hiring pipeline</p>
                    </div>
                    <a href="{{ route('company.applicants.index') }}" class="text-xs font-semibold text-[#059669] hover:underline whitespace-nowrap shrink-0 flex items-center gap-1 group">
                        <span>Pipeline</span>
                        <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                </div>

                <!-- Donut Canvas with Center KPI Metric -->
                <div class="relative w-full flex-1 min-h-[190px] my-3 flex items-center justify-center">
                    <canvas id="companyStatusDonutCanvas" class="w-full h-full max-h-[210px]"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
                        <span class="text-2xl font-black text-slate-900 tracking-tight leading-none" x-text="donutData.total"></span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Applicants</span>
                        <template x-if="donutData.total > 0">
                            <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full mt-1 border border-emerald-200/70"
                                  x-text="donutData.placementRate + '% Placed'"></span>
                        </template>
                    </div>
                </div>

                <!-- 2x2 Custom Categorical Legend with Counts & Percentages -->
                <div class="grid grid-cols-2 gap-2 pt-3 border-t border-gray-100 shrink-0">
                    <!-- Applied -->
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-sky-50/60 border border-sky-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500 shrink-0"></span>
                            <span class="text-xs font-bold text-slate-700 truncate">Applied</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-black text-slate-900 block" x-text="donutData.counts.applied"></span>
                            <span class="text-[10px] text-slate-500" x-text="donutData.percentages.applied + '%'"></span>
                        </div>
                    </div>

                    <!-- Shortlisted -->
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-indigo-50/60 border border-indigo-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 shrink-0"></span>
                            <span class="text-xs font-bold text-slate-700 truncate">Shortlisted</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-black text-slate-900 block" x-text="donutData.counts.shortlisted"></span>
                            <span class="text-[10px] text-slate-500" x-text="donutData.percentages.shortlisted + '%'"></span>
                        </div>
                    </div>

                    <!-- Accepted -->
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                            <span class="text-xs font-bold text-slate-700 truncate">Accepted</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-black text-emerald-800 block" x-text="donutData.counts.accepted"></span>
                            <span class="text-[10px] text-slate-500" x-text="donutData.percentages.accepted + '%'"></span>
                        </div>
                    </div>

                    <!-- Rejected -->
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-rose-50/60 border border-rose-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                            <span class="text-xs font-bold text-slate-700 truncate">Rejected</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-black text-slate-900 block" x-text="donutData.counts.rejected"></span>
                            <span class="text-[10px] text-slate-500" x-text="donutData.percentages.rejected + '%'"></span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- BOTTOM OPERATIONS GRID: RECENT CANDIDATES & PIPELINE SUMMARY              -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">

            <!-- Left Column: Recent Candidate Submissions (7 cols) -->
            <div class="lg:col-span-7 p-5 sm:p-6 rounded-3xl bg-white border border-[#E5E7EB] shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center">
                            <i class="fa-solid fa-users text-xs"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 tracking-tight">
                                Recent Candidate Submissions
                            </h4>
                            <p class="text-[11px] text-slate-500">
                                Latest applications received across all vacancy posts
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('company.applicants.index') }}" 
                       class="text-xs font-bold text-[#059669] hover:text-[#047857] flex items-center gap-1 group">
                        <span>Pipeline Board</span>
                        <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                </div>

                @if ($recentApplicants->isNotEmpty())
                    <div class="divide-y divide-slate-100 mt-1 max-h-[420px] overflow-y-auto pr-1">
                        @foreach ($recentApplicants as $app)
                            @php
                                $studentUser = $app->studentProfile?->user;
                                $hasAvatar = $studentUser?->has_avatar ?? false;
                                $avatarUrl = $studentUser?->avatar_url ?? null;
                                $initials = $studentUser?->initials ?? 'ST';
                                $hasResume = !empty($app->getEffectiveResumePath());
                            @endphp
                            <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 group hover:bg-slate-50/70 -mx-2 px-2 rounded-2xl transition-colors">
                                <!-- Candidate Avatar & Details -->
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    @if($hasAvatar && $avatarUrl)
                                        <img src="{{ $avatarUrl }}" 
                                             alt="{{ $studentUser->name ?? 'Candidate' }}" 
                                             class="w-10 h-10 rounded-full object-cover border border-slate-200 shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs border border-emerald-200/80 shrink-0">
                                            {{ $initials }}
                                        </div>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <h5 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-[#059669] transition-colors truncate">
                                                {{ $studentUser->name ?? 'Candidate' }}
                                            </h5>
                                            @if($app->studentProfile?->gpa !== null)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                                                    GPA {{ number_format($app->studentProfile->gpa, 2) }}
                                                </span>
                                            @endif
                                        </div>

                                        <p class="text-xs text-slate-600 font-medium mt-0.5 truncate">
                                            {{ $app->internshipPost->title }}
                                        </p>

                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-400 mt-1">
                                            @if(!empty($app->studentProfile?->major))
                                                <span class="text-slate-500 font-medium truncate max-w-[150px]">
                                                    {{ $app->studentProfile->major }}
                                                </span>
                                                <span>•</span>
                                            @elseif(!empty($app->studentProfile?->department))
                                                <span class="text-slate-500 font-medium truncate max-w-[150px]">
                                                    {{ $app->studentProfile->department }}
                                                </span>
                                                <span>•</span>
                                            @endif
                                            <span>Applied {{ \Carbon\Carbon::parse($app->applied_at)->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Badge & Quick Actions -->
                                <div class="flex items-center gap-2 sm:self-center shrink-0 pt-0.5 sm:pt-0 pl-13 sm:pl-0">
                                    <x-status-badge :status="$app->status" />

                                    <div class="flex items-center gap-1 border-l border-slate-200 pl-2 ml-1">
                                        @if ($hasResume)
                                            <a href="{{ route('company.applicants.resume', $app->id) }}" 
                                               target="_blank" 
                                               title="View Resume / CV" 
                                               class="w-7 h-7 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 flex items-center justify-center transition-colors">
                                                <i class="fa-solid fa-file-pdf text-xs"></i>
                                            </a>
                                        @endif

                                        <a href="{{ route('company.messages.start', $app->id) }}" 
                                           title="Message Candidate" 
                                           class="w-7 h-7 rounded-lg text-slate-500 hover:text-blue-700 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 flex items-center justify-center transition-colors">
                                            <i class="fa-solid fa-comment-dots text-xs"></i>
                                        </a>

                                        <a href="{{ route('company.applicants.index', ['post_id' => $app->internship_post_id]) }}" 
                                           title="Review on Pipeline Board" 
                                           class="w-7 h-7 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 flex items-center justify-center transition-colors">
                                            <i class="fa-solid fa-arrow-right text-xs"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                            <i class="fa-regular fa-folder-open text-xl"></i>
                        </div>
                        <h5 class="text-xs font-bold text-slate-800">No applications received yet</h5>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                            When enrolled students apply for your published internship posts, their profiles and resumes will appear here.
                        </p>
                        <a href="{{ route('company.posts.create') }}" 
                           class="inline-flex items-center gap-2 mt-4 px-3.5 py-1.5 text-xs font-bold rounded-xl bg-[#059669] text-white hover:bg-[#047857] transition-colors">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>Create Vacancy</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Right Column: Hiring Pipeline Summary (5 cols) -->
            <div class="lg:col-span-5 p-5 sm:p-6 rounded-3xl bg-white border border-[#E5E7EB] shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-[#D1FAE5] text-[#059669] flex items-center justify-center">
                            <i class="fa-solid fa-list-check text-xs"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 tracking-tight">
                                Hiring Pipeline
                            </h4>
                            <p class="text-[11px] text-slate-500">
                                Candidate progress by review stage
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg">
                        {{ $stats['total_applicants'] }} Total
                    </span>
                </div>

                <!-- Visual Segmented Progress Bar -->
                <div class="mt-4">
                    <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden flex">
                        @if($stats['total_applicants'] > 0)
                            @if($pipeline['pending']['percentage'] > 0)
                                <div style="width: {{ $pipeline['pending']['percentage'] }}%" 
                                     class="h-full bg-amber-400 transition-all duration-500" 
                                     title="Pending: {{ $pipeline['pending']['count'] }} ({{ $pipeline['pending']['percentage'] }}%)"></div>
                            @endif
                            @if($pipeline['shortlisted']['percentage'] > 0)
                                <div style="width: {{ $pipeline['shortlisted']['percentage'] }}%" 
                                     class="h-full bg-teal-500 transition-all duration-500" 
                                     title="Shortlisted: {{ $pipeline['shortlisted']['count'] }} ({{ $pipeline['shortlisted']['percentage'] }}%)"></div>
                            @endif
                            @if($pipeline['interviewed']['percentage'] > 0)
                                <div style="width: {{ $pipeline['interviewed']['percentage'] }}%" 
                                     class="h-full bg-blue-500 transition-all duration-500" 
                                     title="Interviewing: {{ $pipeline['interviewed']['count'] }} ({{ $pipeline['interviewed']['percentage'] }}%)"></div>
                            @endif
                            @if($pipeline['accepted']['percentage'] > 0)
                                <div style="width: {{ $pipeline['accepted']['percentage'] }}%" 
                                     class="h-full bg-emerald-500 transition-all duration-500" 
                                     title="Accepted: {{ $pipeline['accepted']['count'] }} ({{ $pipeline['accepted']['percentage'] }}%)"></div>
                            @endif
                            @if($pipeline['rejected']['percentage'] > 0)
                                <div style="width: {{ $pipeline['rejected']['percentage'] }}%" 
                                     class="h-full bg-rose-400 transition-all duration-500" 
                                     title="Not Selected: {{ $pipeline['rejected']['count'] }} ({{ $pipeline['rejected']['percentage'] }}%)"></div>
                            @endif
                        @else
                            <div class="w-full h-full bg-slate-200"></div>
                        @endif
                    </div>
                </div>

                <!-- Pipeline Stage Breakdown Rows -->
                <div class="mt-4 space-y-2 text-xs">
                    @foreach($pipeline as $stageKey => $stage)
                        <a href="{{ route('company.applicants.index', ['status' => $stageKey === 'shortlisted' ? 'shortlisted' : $stageKey]) }}" 
                           class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition-colors group border border-transparent hover:border-slate-200/60">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full {{ $stage['color'] }} shrink-0"></span>
                                <span class="text-slate-700 font-medium group-hover:text-slate-900">{{ $stage['label'] }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">{{ $stage['count'] }}</span>
                                <span class="text-[11px] text-slate-400 w-10 text-right font-medium">({{ $stage['percentage'] }}%)</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- Bottom Summary & Action -->
                <div class="mt-4 pt-3.5 border-t border-slate-100 space-y-3">
                    <div class="flex items-center justify-between text-xs text-slate-500 px-1">
                        <span class="font-medium">Placement Rate</span>
                        <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                            {{ $stats['total_applicants'] > 0 ? round(($stats['accepted'] / $stats['total_applicants']) * 100) : 0 }}% Placed
                        </span>
                    </div>
                    <a href="{{ route('company.applicants.index') }}" 
                       class="w-full py-2.5 px-3 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold text-xs flex items-center justify-center gap-2 transition-colors border border-slate-200/60">
                        <span>Open Pipeline Board</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

        </div>

    </div>
</x-layout>
