{{--
    Application Timeline Component
    Usage: <x-application-timeline :status="$app->status" />

    Supported statuses (in order):
      applied → under_review → shortlisted → interviewed → accepted
      rejected (terminal state, shown in red)
--}}

@props(['status'])

@php
    $rawStatus   = strtolower(trim((string) $status));
    $isWithdrawn = $rawStatus === 'withdrawn';
    $isRejected  = $rawStatus === 'rejected';

    // Map 'pending' status to 'applied' step
    $normalized = ($rawStatus === 'pending') ? 'applied' : $rawStatus;

    // Define ordered steps
    $steps = [
        'applied'      => ['label' => 'Applied',      'icon' => 'fa-solid fa-paper-plane'],
        'under_review' => ['label' => 'Under Review',  'icon' => 'fa-solid fa-hourglass-half'],
        'shortlisted'  => ['label' => 'Shortlisted',   'icon' => 'fa-solid fa-list-check'],
        'interviewed'  => ['label' => 'Interviewed',   'icon' => 'fa-solid fa-calendar-check'],
        'accepted'     => ['label' => 'Accepted',      'icon' => 'fa-solid fa-trophy'],
    ];

    $stepKeys   = array_keys($steps);

    // For rejected / withdrawn: fallback index
    $currentIndex = array_search($normalized, $stepKeys);

    // If status not in main steps, fallback to -1
    if ($currentIndex === false) {
        $currentIndex = -1;
    }
@endphp

<div class="w-full" x-data="{ mounted: false }" x-init="$nextTick(() => { mounted = true })">

    {{-- Withdrawn Banner --}}
    @if ($isWithdrawn)
        <div class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl bg-slate-100 border border-slate-200/80 text-slate-600 text-xs font-semibold">
            <div class="w-6 h-6 rounded-lg bg-slate-200 text-slate-500 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-ban text-[11px]"></i>
            </div>
            <span>You withdrew this application.</span>
        </div>

    {{-- Rejected Banner --}}
    @elseif ($isRejected)
        <div class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-700 text-xs font-semibold">
            <div class="w-6 h-6 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-xmark text-[11px]"></i>
            </div>
            <span>Application was not successful at this time.</span>
        </div>

    {{-- Normal Progress Timeline --}}
    @else
        <div class="flex items-center w-full min-w-0">
            @foreach ($steps as $key => $step)
                @php
                    $index     = array_search($key, $stepKeys);
                    $isDone    = $index < $currentIndex;
                    $isActive  = $index === $currentIndex;
                    $isPending = $index > $currentIndex;
                    $isLast    = $index === count($stepKeys) - 1;
                @endphp

                {{-- Step node + label --}}
                <div class="flex flex-col items-center relative shrink-0"
                     style="min-width: 60px; max-width: 80px; flex: 1;">

                    {{-- Circle Icon --}}
                    <div class="relative z-10 transition-all duration-500"
                         :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-75'"
                         style="transition-delay: {{ $index * 80 }}ms">

                        @if ($isDone)
                            {{-- Completed step --}}
                            <div class="w-8 h-8 rounded-full bg-emerald-600 border-2 border-emerald-600 flex items-center justify-center shadow-sm shadow-emerald-500/30">
                                <i class="fa-solid fa-check text-white text-[11px]"></i>
                            </div>

                        @elseif ($isActive)
                            {{-- Current active step — pulsing ring --}}
                            <div class="relative">
                                <div class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-25"></div>
                                <div class="w-8 h-8 rounded-full bg-emerald-600 border-2 border-emerald-500 flex items-center justify-center shadow-md shadow-emerald-500/40 relative z-10">
                                    <i class="{{ $step['icon'] }} text-white text-[10px]"></i>
                                </div>
                            </div>

                        @else
                            {{-- Pending step --}}
                            <div class="w-8 h-8 rounded-full bg-white border-2 border-slate-200 flex items-center justify-center">
                                <i class="{{ $step['icon'] }} text-slate-300 text-[10px]"></i>
                            </div>
                        @endif
                    </div>

                    {{-- Step Label --}}
                    <span class="mt-1.5 text-center leading-tight transition-all duration-500 whitespace-nowrap"
                          :class="mounted ? 'opacity-100' : 'opacity-0'"
                          style="transition-delay: {{ $index * 80 + 100 }}ms; font-size: 9.5px;"
                          @class([
                              'font-bold text-emerald-700'  => $isActive,
                              'font-semibold text-slate-500' => $isDone,
                              'font-medium text-slate-300'  => $isPending,
                          ])>
                        {{ $step['label'] }}
                    </span>
                </div>

                {{-- Connector Line (not after last step) --}}
                @if (! $isLast)
                    <div class="flex-1 h-[2px] mx-1 rounded-full relative overflow-hidden -mt-4 shrink"
                         style="min-width: 8px;">
                        {{-- Background track --}}
                        <div class="absolute inset-0 bg-slate-200 rounded-full"></div>
                        {{-- Fill bar --}}
                        @if ($isDone)
                            <div class="absolute inset-0 bg-emerald-500 rounded-full transition-all duration-700"
                                 :class="mounted ? 'w-full' : 'w-0'"
                                 style="transition-delay: {{ $index * 80 + 200 }}ms"></div>
                        @elseif ($isActive)
                            <div class="absolute inset-0 rounded-full transition-all duration-700"
                                 :class="mounted ? 'w-1/2 bg-emerald-400' : 'w-0 bg-emerald-400'"
                                 style="transition-delay: {{ $index * 80 + 200 }}ms"></div>
                        @endif
                    </div>
                @endif

            @endforeach
        </div>
    @endif

</div>

