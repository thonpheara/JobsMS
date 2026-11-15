<!-- Schedule Interview Modal Component -->
<div x-show="interviewModalOpen"
     x-cloak
     x-effect="document.body.classList.toggle('overflow-hidden', interviewModalOpen)"
     @keydown.escape.window="interviewModalOpen = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;"
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="modal-interview-title">

    <!-- Modal Backdrop with Blur -->
    <div x-show="interviewModalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="interviewModalOpen = false"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

    <!-- Centered Modal Container -->
    <div class="flex min-h-full items-center justify-center p-3 sm:p-6 lg:p-8">
        <div x-show="interviewModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="relative w-full max-w-xl bg-white rounded-3xl border border-slate-200/90 shadow-2xl my-6">

            <!-- Modal Header -->
            <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-slate-100 rounded-t-3xl bg-gradient-to-r from-indigo-50/70 via-slate-50/40 to-white flex items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/25 shrink-0">
                        <i class="fa-solid fa-calendar-check text-lg"></i>
                    </div>
                    <div>
                        <h3 id="modal-interview-title" class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                            Schedule Interview Session
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <span x-text="interviewApp.candidateName" class="font-bold text-slate-800"></span> • <span x-text="interviewApp.roleTitle" class="text-indigo-700 font-medium"></span>
                        </p>
                    </div>
                </div>
                <button type="button" 
                        @click="interviewModalOpen = false"
                        class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors cursor-pointer"
                        title="Close (Esc)">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form :action="interviewApp.storeUrl" method="POST" id="schedule-interview-modal-form" class="px-6 py-6 sm:px-8 space-y-5">
                @csrf

                <!-- Candidate Reschedule Request Banner (Feature 2.4) -->
                <div x-show="interviewApp.rescheduleReason" 
                     class="p-4 bg-amber-50/90 border border-amber-200/90 rounded-2xl flex items-start gap-3.5 text-xs text-amber-950">
                    <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                        <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="font-black text-amber-900 uppercase tracking-wider text-[11px]">
                                Candidate Requested Reschedule
                            </span>
                            <a :href="interviewApp.messageUrl" 
                               target="_blank"
                               class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 hover:text-indigo-900 underline">
                                <i class="fa-solid fa-comments text-[10px]"></i>
                                <span>Reply in Chat</span>
                            </a>
                        </div>
                        <div class="mt-1.5 p-2.5 bg-white/90 rounded-xl border border-amber-200/60 text-slate-800 italic leading-relaxed text-xs">
                            “<span x-text="interviewApp.rescheduleReason"></span>”
                        </div>
                        <p class="text-[11px] text-amber-800 font-semibold mt-1.5 flex items-center gap-1">
                            <i class="fa-solid fa-circle-info text-amber-600"></i>
                            <span>Select a new date & time below to accommodate the candidate's availability.</span>
                        </p>
                    </div>
                </div>

                <!-- Meeting Type Radio Selection -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Meeting Format <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" 
                                @click="interviewApp.meetingType = 'online'"
                                :class="interviewApp.meetingType === 'online' ? 'border-indigo-600 bg-indigo-50/80 text-indigo-950 ring-2 ring-indigo-500/20' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                class="p-3.5 rounded-2xl border-2 text-left transition-all cursor-pointer flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold"
                                 :class="interviewApp.meetingType === 'online' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'">
                                <i class="fa-solid fa-video"></i>
                            </div>
                            <div>
                                <span class="text-xs font-bold block">Online Video</span>
                                <span class="text-[10px] text-slate-500">Google Meet / Zoom</span>
                            </div>
                        </button>

                        <button type="button" 
                                @click="interviewApp.meetingType = 'in_person'"
                                :class="interviewApp.meetingType === 'in_person' ? 'border-indigo-600 bg-indigo-50/80 text-indigo-950 ring-2 ring-indigo-500/20' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                class="p-3.5 rounded-2xl border-2 text-left transition-all cursor-pointer flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold"
                                 :class="interviewApp.meetingType === 'in_person' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <div>
                                <span class="text-xs font-bold block">In-Person</span>
                                <span class="text-[10px] text-slate-500">Company Office</span>
                            </div>
                        </button>
                    </div>
                    <input type="hidden" name="meeting_type" :value="interviewApp.meetingType">
                </div>

                <!-- Date & Time Custom Dropdown Picker -->
                <div class="relative"
                     x-data="{
                         open: false,
                         viewYear: 2026,
                         viewMonth: 0,
                         selectedYear: 2026,
                         selectedMonth: 0,
                         selectedDay: 1,
                         hour12: 9,
                         minute: '00',
                         ampm: 'AM',
                         monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                         shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                         hoursList: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                         minutesList: ['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'],
                         init() {
                             this.parseValue(interviewApp.scheduledAt);
                         },
                         parseValue(val) {
                             let d = val ? new Date(val) : new Date(Date.now() + 86400000);
                             if (isNaN(d.getTime())) {
                                 d = new Date(Date.now() + 86400000);
                             }
                             this.selectedYear = d.getFullYear();
                             this.selectedMonth = d.getMonth();
                             this.selectedDay = d.getDate();
                             this.viewYear = this.selectedYear;
                             this.viewMonth = this.selectedMonth;
                             let h = d.getHours();
                             this.ampm = h >= 12 ? 'PM' : 'AM';
                             this.hour12 = h % 12 || 12;
                             let m = Math.round(d.getMinutes() / 5) * 5;
                             if (m === 60) m = 55;
                             this.minute = String(m).padStart(2, '0');
                             this.syncValue();
                         },
                         syncValue() {
                             let h24 = parseInt(this.hour12, 10);
                             if (this.ampm === 'PM' && h24 < 12) h24 += 12;
                             if (this.ampm === 'AM' && h24 === 12) h24 = 0;
                             const yyyy = this.selectedYear;
                             const mm = String(this.selectedMonth + 1).padStart(2, '0');
                             const dd = String(this.selectedDay).padStart(2, '0');
                             const hh = String(h24).padStart(2, '0');
                             interviewApp.scheduledAt = `${yyyy}-${mm}-${dd}T${hh}:${this.minute}`;
                         },
                         get displayLabel() {
                             if (!interviewApp.scheduledAt) return 'Select date & time';
                             const mName = this.shortMonths[this.selectedMonth] || 'Jan';
                             const dd = String(this.selectedDay).padStart(2, '0');
                             const hh = String(this.hour12).padStart(2, '0');
                             return `${mName} ${dd}, ${this.selectedYear} • ${hh}:${this.minute} ${this.ampm}`;
                         },
                         get calendarDays() {
                             const firstDay = new Date(this.viewYear, this.viewMonth, 1).getDay();
                             const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                             const prevMonthDays = new Date(this.viewYear, this.viewMonth, 0).getDate();
                             const cells = [];
                             for (let i = firstDay - 1; i >= 0; i--) {
                                 cells.push({ day: prevMonthDays - i, currentMonth: false, dateObj: new Date(this.viewYear, this.viewMonth - 1, prevMonthDays - i) });
                             }
                             for (let i = 1; i <= daysInMonth; i++) {
                                 cells.push({ day: i, currentMonth: true, dateObj: new Date(this.viewYear, this.viewMonth, i) });
                             }
                             const remaining = 42 - cells.length;
                             for (let i = 1; i <= remaining; i++) {
                                 cells.push({ day: i, currentMonth: false, dateObj: new Date(this.viewYear, this.viewMonth + 1, i) });
                             }
                             return cells;
                         },
                         prevMonth() {
                             if (this.viewMonth === 0) { this.viewMonth = 11; this.viewYear--; } else { this.viewMonth--; }
                         },
                         nextMonth() {
                             if (this.viewMonth === 11) { this.viewMonth = 0; this.viewYear++; } else { this.viewMonth++; }
                         },
                         selectDate(cell) {
                             const d = cell.dateObj;
                             this.selectedYear = d.getFullYear();
                             this.selectedMonth = d.getMonth();
                             this.selectedDay = d.getDate();
                             this.viewYear = this.selectedYear;
                             this.viewMonth = this.selectedMonth;
                             this.syncValue();
                         },
                         isSelected(cell) {
                             const d = cell.dateObj;
                             return d.getFullYear() === this.selectedYear && d.getMonth() === this.selectedMonth && d.getDate() === this.selectedDay;
                         },
                         isToday(cell) {
                             const now = new Date();
                             const d = cell.dateObj;
                             return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate();
                         },
                         setToday() {
                             const now = new Date();
                             this.selectedYear = now.getFullYear();
                             this.selectedMonth = now.getMonth();
                             this.selectedDay = now.getDate();
                             this.viewYear = this.selectedYear;
                             this.viewMonth = this.selectedMonth;
                             this.syncValue();
                         }
                     }"
                     x-effect="if (interviewModalOpen) { parseValue(interviewApp.scheduledAt); }"
                     @click.outside="open = false">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Date & Time <span class="text-rose-500">*</span>
                    </label>
                    <input type="hidden" id="interview-scheduled-at" name="scheduled_at" :value="interviewApp.scheduledAt" required>

                    <button type="button"
                            @click="open = !open"
                            :class="open ? 'ring-2 ring-indigo-500 border-indigo-500 bg-white' : 'bg-slate-50 border-slate-200 hover:border-slate-300 hover:bg-white'"
                            class="w-full flex items-center justify-between px-4 py-3 text-sm border rounded-xl text-slate-900 transition-all font-semibold cursor-pointer">
                        <span class="flex items-center gap-2.5 truncate">
                            <i class="fa-solid fa-calendar-days text-indigo-600 text-sm"></i>
                            <span x-text="displayLabel"></span>
                        </span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform duration-200"
                           :class="open ? 'rotate-180 text-indigo-600' : ''"></i>
                    </button>

                    <!-- Custom Calendar & Time Dropdown Panel -->
                    <div x-show="open"
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute left-0 top-full z-50 mt-1.5 w-76 sm:w-84 rounded-2xl bg-white border border-slate-200 shadow-2xl p-4 space-y-3"
                         style="display: none;">

                        <div class="flex items-center justify-between">
                            <button type="button" @click="prevMonth()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                                <i class="fa-solid fa-chevron-left text-[10px]"></i>
                            </button>
                            <span class="text-xs font-extrabold text-slate-900" x-text="monthNames[viewMonth] + ' ' + viewYear"></span>
                            <button type="button" @click="nextMonth()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-7 text-center text-[10px] font-bold text-slate-400">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>

                        <div class="grid grid-cols-7 gap-1 text-center">
                            <template x-for="(cell, idx) in calendarDays" :key="idx">
                                <button type="button"
                                        @click="selectDate(cell)"
                                        class="h-8 rounded-lg text-xs transition-all flex items-center justify-center cursor-pointer"
                                        :class="isSelected(cell)
                                            ? 'bg-indigo-600 text-white font-extrabold shadow-2xs'
                                            : (isToday(cell)
                                                ? 'bg-indigo-50 text-indigo-700 font-bold border border-indigo-200'
                                                : (cell.currentMonth ? 'text-slate-700 hover:bg-slate-100 font-medium' : 'text-slate-300 hover:bg-slate-50'))">
                                    <span x-text="cell.day"></span>
                                </button>
                            </template>
                        </div>

                        <!-- Time Selector Row -->
                        <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <span class="text-[11px] font-bold text-slate-500 flex items-center gap-1">
                                <i class="fa-regular fa-clock text-indigo-600"></i> Time
                            </span>
                            <div class="flex items-center gap-1.5">
                                <!-- Hour Custom Dropdown -->
                                <div class="relative" x-data="{ hOpen: false }">
                                    <button type="button" @click="hOpen = !hOpen"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:border-indigo-500 text-xs font-bold text-slate-800 cursor-pointer shadow-2xs">
                                        <span x-text="String(hour12).padStart(2, '0')"></span>
                                        <i class="fa-solid fa-chevron-down text-[8px] text-slate-400"></i>
                                    </button>
                                    <div x-show="hOpen" x-cloak @click.outside="hOpen = false"
                                         class="absolute bottom-full left-0 mb-1 w-16 max-h-40 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-xl p-1 z-50">
                                        <template x-for="h in hoursList" :key="h">
                                            <button type="button"
                                                    @click="hour12 = h; syncValue(); hOpen = false"
                                                    class="w-full px-2 py-1 text-xs rounded-lg text-center cursor-pointer transition-colors"
                                                    :class="hour12 == h ? 'bg-indigo-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'"
                                                    x-text="String(h).padStart(2, '0')"></button>
                                        </template>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-slate-400">:</span>
                                <!-- Minute Custom Dropdown -->
                                <div class="relative" x-data="{ mOpen: false }">
                                    <button type="button" @click="mOpen = !mOpen"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:border-indigo-500 text-xs font-bold text-slate-800 cursor-pointer shadow-2xs">
                                        <span x-text="minute"></span>
                                        <i class="fa-solid fa-chevron-down text-[8px] text-slate-400"></i>
                                    </button>
                                    <div x-show="mOpen" x-cloak @click.outside="mOpen = false"
                                         class="absolute bottom-full left-0 mb-1 w-16 max-h-40 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-xl p-1 z-50">
                                        <template x-for="m in minutesList" :key="m">
                                            <button type="button"
                                                    @click="minute = m; syncValue(); mOpen = false"
                                                    class="w-full px-2 py-1 text-xs rounded-lg text-center cursor-pointer transition-colors"
                                                    :class="minute === m ? 'bg-indigo-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100 font-medium'"
                                                    x-text="m"></button>
                                        </template>
                                    </div>
                                </div>
                                <!-- AM/PM Toggle -->
                                <div class="inline-flex rounded-lg bg-slate-100 p-0.5 border border-slate-200">
                                    <button type="button"
                                            @click="ampm = 'AM'; syncValue()"
                                            :class="ampm === 'AM' ? 'bg-indigo-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                                            class="px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer">AM</button>
                                    <button type="button"
                                            @click="ampm = 'PM'; syncValue()"
                                            :class="ampm === 'PM' ? 'bg-indigo-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                                            class="px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer">PM</button>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <button type="button" @click="setToday()" class="text-[11px] font-bold text-indigo-600 hover:underline cursor-pointer">
                                Today
                            </button>
                            <button type="button" @click="open = false" class="px-3 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold transition-colors cursor-pointer">
                                Done
                            </button>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-400 mt-1">Select the proposed interview date and local Cambodian time.</p>
                </div>

                <!-- Location or Link Input -->
                <div>
                    <label for="interview-location" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        <span x-text="interviewApp.meetingType === 'online' ? 'Meeting Video Link (URL)' : 'Physical Address / Office Location'"></span> <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i :class="interviewApp.meetingType === 'online' ? 'fa-solid fa-link' : 'fa-solid fa-location-dot'" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                        <input type="text" 
                               id="interview-location" 
                               name="location_or_link" 
                               x-model="interviewApp.locationOrLink"
                               required 
                               :placeholder="interviewApp.meetingType === 'online' ? 'https://meet.google.com/abc-defg-hij' : 'e.g. Canadia Tower, 14th Floor, Room 1402, Phnom Penh'"
                               class="w-full pl-10 pr-4 py-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 transition-all placeholder-slate-400">
                    </div>
                </div>

                <!-- Recruiter Notes / Instructions -->
                <div>
                    <label for="interview-notes" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Preparation Notes / Agenda <span class="text-slate-400 font-normal normal-case">(optional)</span>
                    </label>
                    <textarea id="interview-notes" 
                              name="notes" 
                              x-model="interviewApp.notes"
                              rows="3" 
                              placeholder="e.g. Please bring your laptop with a demo of your past projects. Interview will last approximately 30 minutes with our Engineering Lead." 
                              class="w-full px-4 py-3 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 transition-all placeholder-slate-400 resize-none"></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">This will be emailed to the student and displayed in their application portal.</p>
                </div>

                <!-- Modal Actions -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                    <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-bell text-indigo-500"></i>
                        <span>Student receives an in-app alert & email.</span>
                    </p>
                    <div class="flex items-center gap-2.5">
                        <button type="button" 
                                @click="interviewModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs shadow-md shadow-indigo-600/25 transition-all flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>Send Invitation</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

