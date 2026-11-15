<x-layout>
    <!-- Script Component Definition for Company Dashboard Charts -->
    <script>
        function topPostsBarChart(serverData) {
            return {
                chartData: serverData || { labels: [], fullTitles: [], data: [], total: 0, hasData: false, isInternships: [], typeLabels: [], categories: [] },
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

                    // Gradient Generator for each bar:
                    // If internship: gradient from #10B981 to #047857
                    // If job: gradient from #818CF8 to #4338CA
                    const bgColors = this.chartData.data.map((val, idx) => {
                        const isIntern = this.chartData.isInternships ? this.chartData.isInternships[idx] : true;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 240);
                        if (isIntern) {
                            gradient.addColorStop(0, '#10B981'); // Emerald 500
                            gradient.addColorStop(1, '#047857'); // Emerald 700
                        } else {
                            gradient.addColorStop(0, '#818CF8'); // Indigo 400
                            gradient.addColorStop(1, '#4338CA'); // Indigo 700
                        }
                        return gradient;
                    });

                    const hoverColors = this.chartData.data.map((val, idx) => {
                        const isIntern = this.chartData.isInternships ? this.chartData.isInternships[idx] : true;
                        return isIntern ? '#059669' : '#4F46E5';
                    });

                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.chartData.labels,
                            datasets: [{
                                label: 'Applications',
                                data: this.chartData.data,
                                backgroundColor: bgColors,
                                hoverBackgroundColor: hoverColors,
                                borderRadius: 10,
                                borderSkipped: false,
                                maxBarThickness: 44,
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
                                    padding: 12,
                                    cornerRadius: 12,
                                    callbacks: {
                                        title: (items) => {
                                            const idx = items[0].dataIndex;
                                            return this.chartData.fullTitles[idx] || items[0].label;
                                        },
                                        beforeLabel: (item) => {
                                            const idx = item.dataIndex;
                                            const type = this.chartData.typeLabels ? this.chartData.typeLabels[idx] : 'Internship';
                                            const isIntern = this.chartData.isInternships ? this.chartData.isInternships[idx] : true;
                                            const cat = this.chartData.categories ? this.chartData.categories[idx] : '';
                                            return `Type: ${isIntern ? '🎓 ' : '💼 '}${type}${cat ? ' (' + cat + ')' : ''}`;
                                        },
                                        label: (item) => `📥 ${item.parsed.y} Applications received`
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
                    labels: ['Applied', 'Shortlisted', 'Interviewing', 'Accepted', 'Rejected'],
                    data: [0, 0, 0, 0, 0],
                    colors: ['#0EA5E9', '#14B8A6', '#6366F1', '#10B981', '#F43F5E'],
                    total: 0,
                    placementRate: 0,
                    counts: { applied: 0, shortlisted: 0, interviewing: 0, accepted: 0, rejected: 0 },
                    percentages: { applied: 0, shortlisted: 0, interviewing: 0, accepted: 0, rejected: 0 }
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
                                borderRadius: hasData ? 6 : 0,
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
                <a href="{{ route('company.posts.index') }}?create=1" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-[#059669] hover:bg-[#047857] text-white shadow-xs transition-colors">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Post Opportunity</span>
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
            
            <!-- 1. Active Listings (Indigo Theme) -->
            <a href="{{ route('company.posts.index') }}" 
               class="group p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-white border border-[#E5E7EB] shadow-xs hover:border-indigo-400/80 hover:shadow-md hover:-translate-y-1 transition-all duration-200 block">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-indigo-600 transition-colors">
                        Active Listings
                    </span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100/80 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                        <i class="fa-solid fa-briefcase text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-1.5">
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 group-hover:text-indigo-950 transition-colors">{{ $stats['active_posts'] }}</h3>
                        <span class="text-[11px] text-slate-400 font-medium">/ {{ $stats['total_posts'] }} total</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>
                        @if(($stats['active_internships'] ?? 0) > 0 || ($stats['active_jobs'] ?? 0) > 0)
                            <span>{{ $stats['active_internships'] }} Interns • {{ $stats['active_jobs'] }} Jobs</span>
                        @else
                            <span>Vetted & Published</span>
                        @endif
                    </p>
                </div>
            </a>

            <!-- 2. Total Applicants (Sky Blue Theme) -->
            <a href="{{ route('company.applicants.index') }}" 
               class="group p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-white border border-[#E5E7EB] shadow-xs hover:border-sky-400/80 hover:shadow-md hover:-translate-y-1 transition-all duration-200 block">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-sky-600 transition-colors">
                        Total Applicants
                    </span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-sky-50 text-sky-600 border border-sky-100/80 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                        <i class="fa-solid fa-users text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 group-hover:text-sky-950 transition-colors">{{ $stats['total_applicants'] }}</h3>
                        <span class="text-[10px] font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md border border-sky-200/80">
                            Pipeline
                        </span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 shrink-0"></span>
                        <span>Received candidacies</span>
                    </p>
                </div>
            </a>

            <!-- 3. Pending Reviews (Amber Alert Theme with Pulse) -->
            <a href="{{ route('company.applicants.index', ['status' => 'pending']) }}" 
               class="group p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-white border border-[#E5E7EB] shadow-xs hover:border-amber-400/80 hover:shadow-md hover:-translate-y-1 transition-all duration-200 block">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-amber-600 transition-colors">
                        Pending Reviews
                    </span>
                    <div class="relative w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200/80 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                        @if($stats['pending_review'] > 0)
                            <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                            </span>
                        @endif
                        <i class="fa-solid fa-hourglass-half text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <h3 class="text-xl sm:text-2xl font-black text-amber-600">{{ $stats['pending_review'] }}</h3>
                    @if($stats['pending_review'] > 0)
                        <span class="text-[10px] font-bold text-amber-800 bg-amber-100/90 px-2 py-0.5 rounded-md border border-amber-300/80 animate-pulse">
                            Action req.
                        </span>
                    @else
                        <span class="text-[10px] font-medium text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">
                            Up to date
                        </span>
                    @endif
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                    <span>Awaiting response</span>
                </p>
            </a>

            <!-- 4. Accepted / Placements (Emerald Success Theme) -->
            <a href="{{ route('company.applicants.index', ['status' => 'accepted']) }}" 
               class="group p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-white border border-[#E5E7EB] shadow-xs hover:border-emerald-400/80 hover:shadow-md hover:-translate-y-1 transition-all duration-200 block">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-emerald-700 transition-colors">
                        Offers Accepted
                    </span>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                        <i class="fa-solid fa-award text-xs sm:text-sm"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <h3 class="text-xl sm:text-2xl font-black text-emerald-700">{{ $stats['accepted'] }}</h3>
                    <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100/80 px-2 py-0.5 rounded-md border border-emerald-300/70">
                        {{ $stats['placement_rate'] ?? 0 }}% Placed
                    </span>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 mt-1 flex items-center gap-1.5 truncate">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                    <span>Confirmed placements</span>
                </p>
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
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full mt-1.5 border border-emerald-200/80 shadow-2xs">
                                <i class="fa-solid fa-trophy text-[9px] text-amber-500"></i>
                                <span x-text="Math.round(donutData.placementRate) + '% Placed'"></span>
                            </span>
                        </template>
                    </div>
                </div>

                <!-- 5-Stage Custom Categorical Legend with Counts & Percentages (Clickable Quick Filters) -->
                <div class="grid grid-cols-2 gap-2 pt-3 border-t border-gray-100 shrink-0">
                    <!-- 1. Applied -->
                    <a href="{{ route('company.applicants.index', ['status' => 'pending']) }}" 
                       title="View Pending Applications" 
                       class="group/item flex items-center justify-between p-2.5 rounded-xl bg-sky-50/60 hover:bg-sky-100/80 border border-sky-100 hover:border-sky-300 transition-all hover:shadow-2xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500 group-hover/item:scale-110 transition-transform shrink-0"></span>
                            <span class="text-xs font-bold text-slate-700 group-hover/item:text-sky-900 transition-colors truncate">Applied</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-black text-slate-900 group-hover/item:text-sky-950 transition-colors block" x-text="donutData.counts.applied"></span>
                            <span class="text-[10px] text-slate-500" x-text="donutData.percentages.applied + '%'"></span>
                        </div>
                    </a>

                    <!-- 2. Shortlisted -->
                    <a href="{{ route('company.applicants.index', ['status' => 'shortlisted']) }}" 
                       title="View Shortlisted Candidates" 
                       class="group/item flex items-center justify-between p-2.5 rounded-xl bg-teal-50/60 hover:bg-teal-100/80 border border-teal-100 hover:border-teal-300 transition-all hover:shadow-2xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-500 group-hover/item:scale-110 transition-transform shrink-0"></span>
                            <span class="text-xs font-bold text-slate-700 group-hover/item:text-teal-900 transition-colors truncate">Shortlisted</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-black text-slate-900 group-hover/item:text-teal-950 transition-colors block" x-text="donutData.counts.shortlisted"></span>
                            <span class="text-[10px] text-slate-500" x-text="donutData.percentages.shortlisted + '%'"></span>
                        </div>
                    </a>

                    <!-- 3. Interviewing -->
                    <a href="{{ route('company.applicants.index', ['status' => 'interviewed']) }}" 
                       title="View Candidates in Interviewing Stage" 
                       class="group/item flex items-center justify-between p-2.5 rounded-xl bg-indigo-50/60 hover:bg-indigo-100/80 border border-indigo-100 hover:border-indigo-300 transition-all hover:shadow-2xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 group-hover/item:scale-110 transition-transform shrink-0"></span>
                            <span class="text-xs font-bold text-slate-700 group-hover/item:text-indigo-900 transition-colors truncate">Interviewing</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-black text-slate-900 group-hover/item:text-indigo-950 transition-colors block" x-text="donutData.counts.interviewing"></span>
                            <span class="text-[10px] text-slate-500" x-text="donutData.percentages.interviewing + '%'"></span>
                        </div>
                    </a>

                    <!-- 4. Accepted -->
                    <a href="{{ route('company.applicants.index', ['status' => 'accepted']) }}" 
                       title="View Accepted Candidates" 
                       class="group/item flex items-center justify-between p-2.5 rounded-xl bg-emerald-50/60 hover:bg-emerald-100/80 border border-emerald-100 hover:border-emerald-300 transition-all hover:shadow-2xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 group-hover/item:scale-110 transition-transform shrink-0"></span>
                            <span class="text-xs font-bold text-slate-700 group-hover/item:text-emerald-900 transition-colors truncate">Accepted</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-black text-emerald-800 group-hover/item:text-emerald-950 transition-colors block" x-text="donutData.counts.accepted"></span>
                            <span class="text-[10px] text-slate-500" x-text="donutData.percentages.accepted + '%'"></span>
                        </div>
                    </a>

                    <!-- 5. Rejected (Spans 2 columns) -->
                    <a href="{{ route('company.applicants.index', ['status' => 'rejected']) }}" 
                       title="View Not Selected Candidates" 
                       class="col-span-2 group/item flex items-center justify-between p-2.5 rounded-xl bg-rose-50/50 hover:bg-rose-100/80 border border-rose-100 hover:border-rose-300 transition-all hover:shadow-2xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 group-hover/item:scale-110 transition-transform shrink-0"></span>
                            <span class="text-xs font-bold text-slate-700 group-hover/item:text-rose-900 transition-colors truncate">Not Selected / Rejected</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-black text-rose-900 group-hover/item:text-rose-950 transition-colors block" x-text="donutData.counts.rejected"></span>
                            <span class="text-[10px] text-slate-500" x-text="donutData.percentages.rejected + '%'"></span>
                        </div>
                    </a>
                </div>

            </div>

        </div>

        {{-- Reschedule Request High-Priority Alert Banner --}}
        @if($rescheduleCount > 0)
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                    </div>
                    <div>
                        <h5 class="font-bold text-xs sm:text-sm text-amber-950">
                            {{ $rescheduleCount }} Candidate Interview Reschedule Request{{ $rescheduleCount > 1 ? 's' : '' }} Requiring Attention
                        </h5>
                        <p class="text-[11px] sm:text-xs text-amber-800 mt-0.5">
                            Candidate(s) requested an alternative date/time for their scheduled interview. Review their explanations below or in the pipeline board.
                        </p>
                    </div>
                </div>
                <a href="{{ route('company.interviews.index', ['tab' => 'reschedule_requested']) }}"
                   class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shrink-0 transition-colors flex items-center gap-1.5 self-start sm:self-auto shadow-2xs">
                    <span>Manage Requests</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- UPCOMING CANDIDATE INTERVIEWS AGENDA (FEATURE 2.4 INTEGRATION)            -->
        <!-- ========================================================================= -->
        <div class="p-6 sm:p-7 rounded-3xl bg-white border border-[#E5E7EB] shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3.5 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-calendar-check text-base"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                                Upcoming Scheduled Interviews
                            </h3>
                            @if($upcomingInterviews->isNotEmpty())
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ $upcomingInterviews->count() }} Session{{ $upcomingInterviews->count() > 1 ? 's' : '' }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">
                            Real-time candidate interview schedule, video call links, and student attendance confirmations.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                    <a href="{{ route('company.interviews.index') }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl text-indigo-600 bg-indigo-50/70 hover:bg-indigo-100/70 border border-indigo-200 transition-colors">
                        <i class="fa-solid fa-calendar-check text-[11px]"></i>
                        <span>Manage Interviews</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            @if($upcomingInterviews->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4 pt-1">
                    @foreach($upcomingInterviews as $interview)
                        @php
                            $candidate = $interview->application?->studentProfile?->user;
                            $candidateInitials = $candidate?->initials ?? 'ST';
                            $candidateAvatar = $candidate?->avatar_url ?? null;
                            $candidateHasAvatar = $candidate?->has_avatar ?? false;
                            $post = $interview->application?->internshipPost;
                            $isOnline = $interview->isOnline();
                            $isUrl = filter_var($interview->location_or_link, FILTER_VALIDATE_URL) !== false;
                            $isConfirmed = $interview->isConfirmed();
                            $isReschedule = $interview->isRescheduleRequested();
                            $scheduledAt = $interview->scheduled_at;
                            $isToday = $scheduledAt ? $scheduledAt->isToday() : false;
                            $isTomorrow = $scheduledAt ? $scheduledAt->isTomorrow() : false;
                        @endphp
                        <div class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-3.5 group
                                    {{ $isReschedule ? 'bg-amber-50/30 border-amber-300 ring-1 ring-amber-300/40' : ($isConfirmed ? 'bg-slate-50/40 hover:bg-white border-slate-200 hover:border-indigo-300 hover:shadow-xs' : 'bg-white border-slate-200 hover:border-indigo-300 hover:shadow-xs') }}">
                            
                            {{-- Card Top: Candidate Info & Status Pill --}}
                            <div class="flex items-start justify-between gap-2.5">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    @if($candidateHasAvatar && $candidateAvatar)
                                        <img src="{{ $candidateAvatar }}" alt="{{ $candidate->name ?? 'Candidate' }}" class="w-9 h-9 rounded-full object-cover border border-slate-200 shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs shrink-0 border border-indigo-200/80">
                                            {{ $candidateInitials }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <h5 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors truncate">
                                            {{ $candidate->name ?? 'Candidate' }}
                                        </h5>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 truncate mt-0.5">
                                            <span class="truncate max-w-[130px]">{{ $post->title ?? 'Position' }}</span>
                                            @if($post && $post->isInternship())
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">🎓 Intern</span>
                                            @elseif($post)
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">💼 Job</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Confirmation Status Pill --}}
                                <div class="shrink-0">
                                    @if($isConfirmed)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-circle-check text-emerald-500"></i> Confirmed
                                        </span>
                                    @elseif($isReschedule)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                                            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Reschedule
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            <i class="fa-regular fa-clock text-slate-400"></i> Awaiting Student
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Card Middle: Time, Meeting Type & Location --}}
                            <div class="space-y-2 py-2 px-3 rounded-xl bg-white border border-slate-100 text-xs">
                                {{-- Scheduled DateTime --}}
                                <div class="flex items-center justify-between text-slate-700">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa-regular fa-calendar text-indigo-500 text-xs"></i>
                                        <span class="font-bold text-slate-900">
                                            {{ $scheduledAt ? $scheduledAt->format('D, M d, Y') : 'Date TBD' }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] font-extrabold px-1.5 py-0.5 rounded {{ $isToday ? 'bg-emerald-50 text-emerald-700 font-black' : ($isTomorrow ? 'bg-sky-50 text-sky-700' : 'text-slate-500') }}">
                                        {{ $scheduledAt ? $scheduledAt->format('h:i A') : '' }}
                                        @if($isToday) • Today @elseif($isTomorrow) • Tomorrow @endif
                                    </span>
                                </div>

                                {{-- Meeting Format & Link / Location --}}
                                <div class="flex items-center justify-between pt-1.5 border-t border-slate-100/80 text-[11px]">
                                    <div class="flex items-center gap-1.5 text-slate-600 truncate mr-2">
                                        @if($isOnline)
                                            <i class="fa-solid fa-video text-teal-600 text-[11px] shrink-0"></i>
                                            <span class="font-medium truncate">Online Video</span>
                                        @else
                                            <i class="fa-solid fa-location-dot text-rose-500 text-[11px] shrink-0"></i>
                                            <span class="font-medium truncate">{{ $interview->location_or_link ?: 'In-Person Office' }}</span>
                                        @endif
                                    </div>

                                    @if($isOnline && $isUrl)
                                        <a href="{{ $interview->location_or_link }}" 
                                           target="_blank" 
                                           class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold text-[10px] shadow-2xs transition-colors shrink-0">
                                            <span>Join Call</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[8px]"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- If student requested reschedule, show their explanation --}}
                            @if($isReschedule && $interview->reschedule_reason)
                                <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-900 leading-snug">
                                    <div class="font-bold flex items-center gap-1 text-amber-800 text-[10px] uppercase tracking-wider mb-0.5">
                                        <i class="fa-solid fa-comment-dots"></i> Student Reason:
                                    </div>
                                    <p class="italic text-amber-950">"{{ $interview->reschedule_reason }}"</p>
                                </div>
                            @endif

                            {{-- Card Bottom: Quick Actions --}}
                            <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-xs">
                                <a href="{{ route('company.messages.start', $interview->application_id) }}" 
                                   class="text-[11px] font-semibold text-slate-600 hover:text-indigo-600 flex items-center gap-1.5 transition-colors">
                                    <i class="fa-solid fa-comment-dots text-slate-400"></i>
                                    <span>Chat with Student</span>
                                </a>

                                <a href="{{ route('company.applicants.index', ['status' => 'interviewed', 'post_id' => $post?->id]) }}" 
                                   class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-colors">
                                    <span>Pipeline &rarr;</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-500 border border-indigo-100 flex items-center justify-center mb-3">
                        <i class="fa-regular fa-calendar-days text-xl"></i>
                    </div>
                    <h5 class="text-xs sm:text-sm font-bold text-slate-800">No upcoming interviews scheduled</h5>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">
                        When you advance candidates to the Interview stage and schedule video or in-person sessions, your upcoming timetable will appear here.
                    </p>
                    <a href="{{ route('company.applicants.index') }}" 
                       class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition-colors">
                        <i class="fa-solid fa-table-columns text-xs"></i>
                        <span>Open Hiring Pipeline</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- ========================================================================= -->
        <!-- BOTTOM OPERATIONS: RECENT CANDIDATE SUBMISSIONS (FULL WIDTH)             -->
        <!-- ========================================================================= -->
        <div class="p-5 sm:p-6 lg:p-7 rounded-3xl bg-white border border-[#E5E7EB] shadow-xs">
            
            <!-- Card Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#059669] flex items-center justify-center shrink-0 border border-emerald-100">
                        <i class="fa-solid fa-users text-sm"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-base font-bold text-slate-900 tracking-tight">
                                Recent Candidate Submissions
                            </h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $stats['total_applicants'] }} Total
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Latest student applications received across your published vacancies
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                    <a href="{{ route('company.applicants.index') }}" 
                       class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 transition-colors">
                        <i class="fa-solid fa-table-columns text-slate-400"></i>
                        <span>Open Pipeline Board</span>
                        <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
                    </a>
                </div>
            </div>

            @if ($recentApplicants->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach ($recentApplicants as $app)
                        @php
                            $studentUser = $app->studentProfile?->user;
                            $hasAvatar = $studentUser?->has_avatar ?? false;
                            $avatarUrl = $studentUser?->avatar_url ?? null;
                            $initials = $studentUser?->initials ?? 'ST';
                            $hasResume = !empty($app->getEffectiveResumePath());
                        @endphp
                        <div class="py-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4 group hover:bg-slate-50/70 -mx-3 px-3 rounded-2xl transition-colors">
                            
                            <!-- Left: Candidate Profile Info -->
                            <div class="flex items-start sm:items-center gap-3.5 min-w-0 flex-1">
                                @if($hasAvatar && $avatarUrl)
                                    <img src="{{ $avatarUrl }}" 
                                         alt="{{ $studentUser->name ?? 'Candidate' }}" 
                                         class="w-11 h-11 rounded-2xl object-cover border border-slate-200 shrink-0 shadow-2xs">
                                @else
                                    <div class="w-11 h-11 rounded-2xl bg-emerald-100/90 text-emerald-800 font-bold flex items-center justify-center text-sm border border-emerald-200/80 shrink-0 shadow-2xs">
                                        {{ $initials }}
                                    </div>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h5 class="text-sm font-bold text-slate-900 group-hover:text-[#059669] transition-colors truncate">
                                            {{ $studentUser->name ?? 'Candidate' }}
                                        </h5>
                                        @if($app->studentProfile?->gpa !== null)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                                                GPA {{ number_format($app->studentProfile->gpa, 2) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 text-xs text-slate-500 mt-1 flex-wrap">
                                        @if(!empty($app->studentProfile?->major))
                                            <span class="font-medium text-slate-600 truncate max-w-[200px]">
                                                <i class="fa-solid fa-graduation-cap text-slate-400 text-[11px] mr-1"></i>{{ $app->studentProfile->major }}
                                            </span>
                                            <span class="text-slate-300">•</span>
                                        @elseif(!empty($app->studentProfile?->department))
                                            <span class="font-medium text-slate-600 truncate max-w-[200px]">
                                                <i class="fa-solid fa-building-columns text-slate-400 text-[11px] mr-1"></i>{{ $app->studentProfile->department }}
                                            </span>
                                            <span class="text-slate-300">•</span>
                                        @endif
                                        <span class="text-slate-400 text-[11px]">
                                            Applied {{ \Carbon\Carbon::parse($app->applied_at)->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Middle: Opportunity Applied For -->
                            <div class="flex items-center gap-2.5 min-w-0 lg:w-1/3 shrink-0">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        @if($app->internshipPost->isInternship())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                                🎓 Internship
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                                                💼 {{ $app->internshipPost->opportunity_type_label }}
                                            </span>
                                        @endif
                                        @if($app->internshipPost->category)
                                            <span class="text-[11px] text-slate-400 truncate">
                                                • {{ $app->internshipPost->category }}
                                            </span>
                                        @endif
                                    </div>
                                    <h6 class="text-xs font-semibold text-slate-800 truncate" title="{{ $app->internshipPost->title }}">
                                        {{ $app->internshipPost->title }}
                                    </h6>
                                </div>
                            </div>

                            <!-- Right: Status Badge & Quick Actions -->
                            <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-2 lg:pt-0 border-t border-slate-100 lg:border-t-0">
                                <x-status-badge :status="$app->status" />

                                <div class="flex items-center gap-1.5 border-l border-slate-200 pl-3">
                                    @if ($hasResume)
                                        <a href="{{ route('company.applicants.resume', $app->id) }}" 
                                           target="_blank" 
                                           title="View Resume / CV" 
                                           class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-emerald-700 bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition-colors">
                                            <i class="fa-solid fa-file-pdf text-rose-500 text-xs"></i>
                                            <span class="hidden sm:inline">CV</span>
                                        </a>
                                    @endif

                                    <a href="{{ route('company.messages.start', $app->id) }}" 
                                       title="Chat with Candidate" 
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-blue-700 bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 transition-colors">
                                        <i class="fa-solid fa-comment-dots text-blue-500 text-xs"></i>
                                        <span class="hidden sm:inline">Chat</span>
                                    </a>

                                    <a href="{{ route('company.applicants.index', ['post_id' => $app->internship_post_id]) }}" 
                                       title="Review Application on Pipeline Board" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-[#059669] hover:bg-[#047857] shadow-2xs transition-colors">
                                        <span>Review</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>

                <!-- Footer Summary Bar -->
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500">
                    <div>
                        Showing latest <strong class="text-slate-900 font-bold">{{ $recentApplicants->count() }}</strong> candidate applications
                    </div>
                    <a href="{{ route('company.applicants.index') }}" 
                       class="font-bold text-[#059669] hover:text-[#047857] inline-flex items-center gap-1.5 group">
                        <span>View All {{ $stats['total_applicants'] }} Applicants in Pipeline Board</span>
                        <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                        <i class="fa-regular fa-folder-open text-2xl"></i>
                    </div>
                    <h5 class="text-sm font-bold text-slate-800">No applications received yet</h5>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        When enrolled students apply for your published internship and job posts, their profiles and resumes will appear here.
                    </p>
                    <a href="{{ route('company.posts.index') }}?create=1" 
                       class="inline-flex items-center gap-2 mt-4 px-4 py-2 text-xs font-bold rounded-xl bg-[#059669] text-white hover:bg-[#047857] shadow-xs transition-colors">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Create Opportunity Vacancy</span>
                    </a>
                </div>
            @endif

        </div>

    </div>
</x-layout>
