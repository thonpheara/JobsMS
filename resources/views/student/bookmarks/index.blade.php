<x-layout>
    <div class="space-y-6">

        {{-- ─── Page Header ─── --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900 flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-500 text-sm shrink-0">
                        <i class="fa-solid fa-heart"></i>
                    </span>
                    Saved Jobs
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 mt-1 ml-11">
                    Internship posts you've bookmarked for later review.
                </p>
            </div>
            <a href="{{ route('student.posts.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white
                      font-bold text-xs hover:bg-emerald-700 transition-colors shadow-xs shrink-0 self-start sm:self-auto">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                <span>Explore More Roles</span>
            </a>
        </div>

        {{-- ─── Bookmark Count Badge ─── --}}
        @if ($bookmarkedPosts->isNotEmpty())
            <div class="flex items-center gap-2 text-xs text-slate-500 font-medium -mt-2">
                <i class="fa-solid fa-bookmark text-rose-400 text-[11px]"></i>
                <span>{{ $bookmarkedPosts->total() }} {{ Str::plural('position', $bookmarkedPosts->total()) }} saved</span>
            </div>
        @endif

        {{-- ─── Cards Grid ─── --}}
        @if ($bookmarkedPosts->isNotEmpty())

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($bookmarkedPosts as $post)

                    <div class="relative p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs
                                hover:shadow-md hover:border-emerald-300/60 transition-all flex flex-col justify-between group"
                         x-data="{
                             bookmarked: true,
                             loading: false,
                             removed: false,
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
                                     // Animate card out when unbookmarked
                                     if (!data.bookmarked) {
                                         await new Promise(r => setTimeout(r, 200));
                                         this.removed = true;
                                     }
                                 } catch(e) { console.error(e); }
                                 finally { this.loading = false; }
                             }
                         }"
                         x-show="!removed"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95">

                        {{-- ❤️ Remove Bookmark --}}
                        <button @click="toggle()"
                                :disabled="loading"
                                title="Remove from saved jobs"
                                class="absolute top-4 right-4 w-8 h-8 rounded-xl flex items-center justify-center
                                       transition-all duration-200 bg-rose-50 text-rose-500 border border-rose-200
                                       hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-400/40">
                            <i class="text-sm"
                               :class="loading ? 'fa-solid fa-heart animate-pulse' : 'fa-solid fa-heart'"></i>
                        </button>

                        {{-- Content --}}
                        <div class="space-y-3 pr-8">
                            {{-- Category + Stipend --}}
                            <div class="flex items-center gap-2 flex-wrap">
                                @if ($post->category)
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wider
                                                 bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $post->category }}
                                    </span>
                                @endif
                                @if ($post->stipend)
                                    <span class="text-[11px] font-bold text-emerald-600">
                                        ${{ number_format($post->stipend, 0) }}/mo
                                    </span>
                                @endif
                            </div>

                            {{-- Company Logo + Title --}}
                            <div class="flex items-center gap-3">
                                @php
                                    $cLogo     = $post->companyProfile->logo_url;
                                    $cHasLogo  = $post->companyProfile->has_logo;
                                    $cInitials = $post->companyProfile->initials;
                                @endphp
                                @if($cHasLogo && $cLogo)
                                    <a href="{{ route('student.companies.show', $post->companyProfile) }}" title="View {{ $post->companyProfile->company_name }} profile">
                                        <img src="{{ $cLogo }}" alt="{{ $post->companyProfile->company_name }}"
                                             class="w-10 h-10 rounded-xl object-cover shrink-0 border border-slate-200 shadow-xs hover:opacity-90 transition-opacity">
                                    </a>
                                @else
                                    <a href="{{ route('student.companies.show', $post->companyProfile) }}" title="View {{ $post->companyProfile->company_name }} profile"
                                       class="w-10 h-10 rounded-xl bg-teal-700 text-white flex items-center
                                              justify-center font-bold text-xs shrink-0 shadow-xs hover:bg-teal-800 transition-colors">
                                        {{ $cInitials }}
                                    </a>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-extrabold text-slate-900 group-hover:text-emerald-600
                                               transition-colors line-clamp-1">
                                        <a href="{{ route('student.posts.show', $post) }}">{{ $post->title }}</a>
                                    </h3>
                                    <p class="text-xs font-semibold text-slate-500 mt-0.5 truncate">
                                        <a href="{{ route('student.companies.show', $post->companyProfile) }}"
                                           class="hover:text-emerald-600 hover:underline transition-colors">
                                            {{ $post->companyProfile->company_name }}
                                        </a>
                                    </p>
                                </div>
                            </div>

                            {{-- Description preview --}}
                            <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                {{ $post->description }}
                            </p>
                        </div>

                        {{-- Footer --}}
                        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
                            <div class="text-[11px] text-slate-500 space-y-0.5">
                                <div class="flex items-center gap-1">
                                    <i class="fa-solid fa-location-dot text-slate-300 text-[10px]"></i>
                                    <span>{{ $post->location }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <i class="fa-regular fa-calendar text-slate-300 text-[10px]"></i>
                                    <span>Deadline: <strong class="text-slate-700">{{ \Carbon\Carbon::parse($post->deadline)->format('M d, Y') }}</strong></span>
                                    @if (\Carbon\Carbon::parse($post->deadline)->isPast())
                                        <span class="ml-1 text-[10px] font-bold text-rose-500 bg-rose-50 px-1.5 py-0.5 rounded-md border border-rose-200">
                                            Expired
                                        </span>
                                    @elseif (\Carbon\Carbon::parse($post->deadline)->diffInDays(now()) <= 7)
                                        <span class="ml-1 text-[10px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md border border-amber-200">
                                            Closing soon
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <a href="{{ route('student.posts.show', $post) }}"
                               class="shrink-0 px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-bold
                                      hover:bg-emerald-700 transition-colors flex items-center gap-1 shadow-xs">
                                <span>Apply</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>

                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if ($bookmarkedPosts->hasPages())
                <div class="flex justify-center pt-2">
                    {{ $bookmarkedPosts->links() }}
                </div>
            @endif

        @else
            {{-- Empty State --}}
            <div class="flex flex-col items-center justify-center py-20 text-center space-y-4
                        rounded-3xl bg-white border border-dashed border-slate-200">
                <div class="w-16 h-16 rounded-2xl bg-rose-50 border border-rose-100
                            flex items-center justify-center mx-auto">
                    <i class="fa-regular fa-heart text-3xl text-rose-300"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">No saved jobs yet</h3>
                    <p class="text-xs text-slate-500 max-w-xs mx-auto mt-1">
                        Browse internship listings and click the
                        <i class="fa-regular fa-heart text-rose-400"></i>
                        heart icon to save positions you're interested in.
                    </p>
                </div>
                <a href="{{ route('student.posts.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 text-white
                          text-xs font-bold hover:bg-emerald-700 transition-colors shadow-xs">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    Browse Internships
                </a>
            </div>
        @endif

    </div>
</x-layout>

