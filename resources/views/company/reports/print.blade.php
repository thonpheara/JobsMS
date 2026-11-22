<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hiring Report — {{ $company?->company_name ?? 'Company' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body, button, input, select, textarea, table {
            font-family: "Plus Jakarta Sans", "Kantumruy Pro", sans-serif;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased p-4 sm:p-8">

    <div class="max-w-5xl mx-auto mb-4 flex items-center justify-between gap-3 no-print">
        <a href="{{ route('company.reports.index', request()->query()) }}"
           class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50">
            &larr; Back to Hiring Reports
        </a>
        <button onclick="window.print()"
                class="px-4 py-2 rounded-xl bg-[#059669] hover:bg-[#047857] text-white text-xs font-bold cursor-pointer">
            Print / Save PDF
        </button>
    </div>

    <div class="max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 p-6 sm:p-10 shadow-xs space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-[#059669]">Job Management System · Official Hiring Audit</p>
                <h1 class="text-2xl font-black text-slate-900 mt-1">{{ $company?->company_name ?? 'Host Company' }} — Hiring Report</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Scope: <strong>{{ $selectedPostTitle }}</strong>
                    @if ($status !== 'all') · Status: <strong>{{ ucfirst(str_replace('_', ' ', $status)) }}</strong> @endif
                    @if ($dateFrom || $dateTo) · Date: <strong>{{ $dateFrom ?: 'Start' }} to {{ $dateTo ?: 'Present' }}</strong> @endif
                </p>
            </div>
            <div class="text-left sm:text-right text-xs text-slate-500">
                <p>Generated: <strong class="text-slate-800">{{ now()->format('M d, Y h:i A') }}</strong></p>
                <p class="mt-0.5">Location: <strong class="text-slate-800">{{ $company?->location ?: 'N/A' }}</strong></p>
            </div>
        </div>

        <!-- Summary Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase text-slate-500 block">Filtered Applicants</span>
                <span class="text-xl font-black text-slate-900">{{ $totalFiltered }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase text-slate-500 block">Shortlisted / Interview</span>
                <span class="text-xl font-black text-indigo-700">{{ $shortlistedFiltered }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase text-slate-500 block">Hired / Accepted</span>
                <span class="text-xl font-black text-[#059669]">{{ $acceptedFiltered }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase text-slate-500 block">Hire Rate</span>
                <span class="text-xl font-black text-slate-900">{{ $hireRate }}%</span>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="border-b-2 border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    <th class="py-2.5 pr-3">#</th>
                    <th class="py-2.5 pr-3">Candidate</th>
                    <th class="py-2.5 pr-3">Major / GPA</th>
                    <th class="py-2.5 pr-3">Position</th>
                    <th class="py-2.5 pr-3">Status</th>
                    <th class="py-2.5">Applied Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($applications as $idx => $app)
                    <tr>
                        <td class="py-2.5 pr-3 text-slate-400">{{ $idx + 1 }}</td>
                        <td class="py-2.5 pr-3">
                            <span class="font-bold text-slate-900 block">{{ $app->studentProfile?->user?->name ?? 'N/A' }}</span>
                            <span class="text-[10px] text-slate-500">{{ $app->studentProfile?->user?->email }}</span>
                        </td>
                        <td class="py-2.5 pr-3">
                            {{ $app->studentProfile?->major ?: 'N/A' }}
                            ({{ $app->studentProfile?->gpa !== null ? number_format((float) $app->studentProfile->gpa, 2) : 'N/A' }})
                        </td>
                        <td class="py-2.5 pr-3 font-medium">{{ $app->internshipPost?->title ?? 'N/A' }}</td>
                        <td class="py-2.5 pr-3 font-semibold">{{ ucfirst(str_replace('_', ' ', $app->status)) }}</td>
                        <td class="py-2.5">{{ $app->applied_at ? \Carbon\Carbon::parse($app->applied_at)->format('M d, Y') : 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No records found for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
