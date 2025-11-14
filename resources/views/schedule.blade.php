@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gray-100 text-gray-900 font-sans">
        {{-- Loading Overlay --}}
        <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white p-6 rounded-xl shadow-2xl">
                <svg class="animate-spin h-10 w-10 text-blue-600 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <p class="text-gray-700 font-medium">Memuat jadwal...</p>
            </div>
        </div>

        {{-- Alert Modal --}}
        <div id="alertModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden transition-opacity duration-300">
            <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full m-4 transform transition-all duration-300">
                <h3 class="text-xl font-bold mb-4 text-gray-800">Pemberitahuan</h3>
                <p id="alertMessage" class="text-gray-700 mb-6 text-base"></p>
                <button onclick="closeAlert()"
                    class="w-full px-4 py-2.5 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                    Tutup
                </button>
            </div>
        </div>

        <main class="p-8 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold mb-2 text-gray-900">Weekly Schedule</h2>
                    <p class="text-gray-600">View and manage worker schedules</p>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-md hover:shadow-lg transition">

                <div id="scrollContainer" class="overflow-x-auto overflow-y-auto max-h-[80vh]">

                    {{-- LOGIKA GRID DIAMBIL DARI dashboard.blade.php --}}
                    @php
                        $dayWidths = [];
                        foreach ($displayedDates as $d) {
                            $events = $scheduleMap[$d['formatted']] ?? [];
                            $supervisors = $supervisorMap[$d['formatted']] ?? [];

                            $supervisorSubCols = [];
                            foreach ($supervisors as $sup) {
                                $supEvents = array_filter($events, fn($e) => $e['supervisor_name'] === $sup);
                                $maxCols = 1;
                                foreach ($supEvents as $e) {
                                    $maxCols = max($maxCols, $e['totalSubColumns'] ?? 1);
                                }
                                $supervisorSubCols[$sup] = $maxCols;
                            }

                            $totalCols = array_sum($supervisorSubCols) ?: 1;
                            // Menggunakan kalkulasi minmax dari dashboard.blade.php
                            $dayWidths[] = 'minmax(' . $totalCols * 150 . "px, {$totalCols}fr)";
                        }

                        $TIME_SLOT_HEIGHT = 60;
                    @endphp

                    <div id="scheduleGrid" class="grid"
                        style="grid-template-columns: 120px {{ implode(' ', $dayWidths) }}; min-width: max-content;">

                        {{-- HEADER (2 Rows, Sticky) --}}

                        {{-- Row 1: Time Label (Spans 2 rows) --}}
                        <div data-export-sticky="true"
                            class="sticky top-0 left-0 px-6 py-4 text-left text-sm font-semibold text-gray-700 bg-gray-50 border-b border-r border-gray-200 z-30 flex items-center"
                            style="grid-row: span 2 / span 2;">
                            Jam
                        </div>

                        {{-- Row 1: Dates --}}
                        @foreach ($displayedDates as $d)
                            <div data-export-sticky="true"
                                class="sticky top-0 px-6 py-4 text-center text-sm font-semibold text-gray-700 bg-gray-100 border-b border-gray-200 z-20">
                                {{ $d['day'] }}, {{ $d['date'] }}
                            </div>
                        @endforeach

                        {{-- Row 2: Supervisors (Nested Grid) --}}
                        @foreach ($displayedDates as $dayIndex => $d)
                            @php
                                $events = $scheduleMap[$d['formatted']] ?? [];
                                $supervisors = $supervisorMap[$d['formatted']] ?? [];

                                $supervisorSubCols = [];
                                foreach ($supervisors as $sup) {
                                    $supEvents = array_filter($events, fn($e) => $e['supervisor_name'] === $sup);
                                    $maxCols = 1;
                                    foreach ($supEvents as $e) {
                                        $maxCols = max($maxCols, $e['totalSubColumns'] ?? 1);
                                    }
                                    $supervisorSubCols[$sup] = $maxCols;
                                }

                                $totalSubCols = array_sum($supervisorSubCols) ?: 1;
                            @endphp

                            <div data-export-sticky="true"
                                class="sticky top-[57px] bg-gray-50 border-b border-gray-200 z-20"
                                style="grid-column-start: {{ $dayIndex + 2 }}; grid-row-start: 2;">
                                <div class="grid h-full"
                                    style="grid-template-columns: repeat({{ $totalSubCols }}, minmax(150px, 1fr));">
                                    @if (count($supervisors) > 0)
                                        @foreach ($supervisors as $name)
                                            @php $cols = $supervisorSubCols[$name]; @endphp
                                            <div class="px-3 py-2 text-xs font-medium text-gray-600 text-center border-r border-gray-200"
                                                style="grid-column: span {{ $cols }} / span {{ $cols }};">
                                                {{ $name }}
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="px-3 py-2 text-xs italic text-gray-400 text-center">
                                            -
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- BODY GRID (dari dashboard.blade.php) --}}
                        @php
                            $rowCount = count($timeSlots);
                        @endphp

                        {{-- Grid utama (jam + semua hari) --}}

                        {{-- PERUBAHAN 1: Menggunakan $dayWidths agar kolom body sinkron dengan header --}}
                        <div class="col-span-full"
                            style="grid-column: 1 / -1; display: grid;
                                   grid-template-columns: 120px {{ implode(' ', $dayWidths) }};
                                   grid-template-rows: repeat({{ $rowCount }}, minmax({{ $TIME_SLOT_HEIGHT }}px, auto));">

                            {{-- Kolom JAM --}}
                            @foreach ($timeSlots as $rowIndex => $slot)
                                <div data-export-sticky="true"
                                    class="sticky left-0 z-10 bg-gray-50 border-b border-r border-gray-200 flex items-center justify-center text-sm font-semibold text-gray-700"
                                    style="grid-row: {{ $rowIndex + 1 }};">
                                    {{ $slot }}
                                </div>
                            @endforeach

                            {{-- Kolom JADWAL PER HARI --}}
                            @foreach ($displayedDates as $dayIndex => $d)
                                @php
                                    $events = $scheduleMap[$d['formatted']] ?? [];
                                    $supervisors = $supervisorMap[$d['formatted']] ?? [];

                                    // PERUBAHAN 2: Menambahkan kalkulasi sub-kolom (copy dari header)
                                    // Variabel $supervisorSubCols ini akan dipakai di bawah untuk kalkulasi $colStart
                                    $supervisorSubCols = [];
                                    foreach ($supervisors as $sup) {
                                        $supEvents = array_filter($events, fn($e) => $e['supervisor_name'] === $sup);
                                        $maxCols = 1;
                                        foreach ($supEvents as $e) {
                                            $maxCols = max($maxCols, $e['totalSubColumns'] ?? 1);
                                        }
                                        $supervisorSubCols[$sup] = $maxCols;
                                    }
                                    $totalSubCols = array_sum($supervisorSubCols) ?: 1;
                                @endphp

                                <div class="relative border-l border-gray-300 {{ $dayIndex % 2 === 0 ? 'bg-gray-50' : 'bg-white' }}"
                                    style="grid-column: {{ $dayIndex + 2 }};
                                           display: grid;
                                           grid-template-rows: subgrid;
                                           grid-row: 1 / span {{ $rowCount }};
                                           grid-template-columns: repeat({{ $totalSubCols }}, 1fr);"> {{-- PERUBAHAN 3: Terapkan sub-kolom --}}

                                    {{-- Garis background --}}
                                    @foreach ($timeSlots as $r)
                                        {{-- PERUBAHAN 4: Loop untuk garis-garis sub-kolom --}}
                                        @for ($i = 0; $i < $totalSubCols; $i++)
                                            <div class="border-b border-gray-100 {{ $i < $totalSubCols - 1 ? 'border-r' : '' }}"></div>
                                        @endfor
                                    @endforeach

                                    {{-- Event --}}
                                    @foreach ($events as $ev)
                                        @php
                                            $rowStart = $ev['gridRowStart'];
                                            $rowSpan = $ev['gridRowSpan'];

                                            // --- PERUBAHAN 5 (KRUSIAL): Menghitung posisi kolom (dari logika React) ---
                                            $supIndex = array_search($ev['supervisor_name'], $supervisors);

                                            // Safety check jika supervisor tidak ditemukan
                                            if ($supIndex === false) continue;

                                            // $supervisorSubCols telah dihitung di (PERUBAHAN 2) di atas

                                            $colOffset = 0;
                                            for ($i = 0; $i < $supIndex; $i++) {
                                                // Akumulasi total kolom dari supervisor SEBELUMNYA
                                                $colOffset += $supervisorSubCols[$supervisors[$i]] ?? 1;
                                            }

                                            // ev['subColumn'] adalah index 0-based DARI controller (hasil logika overlap)
                                            $evSubCol = $ev['subColumn'] ?? 0;

                                            // Kolom grid 1-based = offset + index internal + 1
                                            $colStart = $colOffset + $evSubCol + 1;

                                            // Berdasarkan kode React, span kolom selalu 1
                                            $colSpan = 1;
                                        @endphp

                                        @auth
                                            {{-- PERUBAHAN 6: Terapkan $colStart dan $colSpan --}}
                                            <div style="grid-row-start: {{ $rowStart }}; grid-row-end: span {{ $rowSpan }};
                                                        grid-column-start: {{ $colStart }}; grid-column-end: span {{ $colSpan }};
                                                        padding: 0.25rem; z-index: 5; position: relative;"
                                                class="group cursor-pointer">

                                                @if (auth()->user()->role_id == 3)
                                                    <div
                                                        onclick="window.location.href='{{ route('schedule.edit', ['dateKey' => urlencode($d['formatted']), 'supervisor' => urlencode($ev['supervisor_name']), 'start' => urlencode($ev['start'])]) }}'">
                                                    @else
                                                        <div>
                                                @endif

                                                <div data-export-card="true"
                                                    class="h-full p-2 border border-blue-200 rounded-lg bg-blue-50 shadow-sm overflow-hidden relative transition hover:shadow-lg hover:bg-blue-100">

                                                    <div data-export-truncate="true"
                                                        class="text-blue-700 font-semibold mb-1 text-xs truncate">
                                                        {{ $ev['supervisor_name'] }}
                                                    </div>

                                                    @foreach ($ev['workers'] as $w)
                                                        <div data-export-truncate="true"
                                                            class="text-xs text-gray-700 leading-tight truncate">
                                                            • {{ $w['worker_name'] }} – <span
                                                                class="font-medium">{{ $w['jobdesc_name'] }}</span>
                                                        </div>
                                                    @endforeach

                                                    @if (auth()->user()->role_id == 3)
                                                        <div
                                                            class="absolute inset-0 flex items-center justify-center bg-blue-600 bg-opacity-0 group-hover:bg-opacity-10 transition-all pointer-events-none">
                                                            <span
                                                                class="opacity-0 group-hover:opacity-100 text-blue-700 font-bold text-sm flex items-center gap-1 transition-opacity bg-white px-3 py-1 rounded-full shadow-lg">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                                    fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.586-9.414a2 2 0 112.828 2.828L11 15l-4 1 1-4 8.414-8.414z" />
                                                                </svg>
                                                                Click to Edit
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                </div>
                            @endauth
                        @endforeach
                </div>
            @endforeach
        </div>

        </div>
        {{-- AKHIR DARI LOGIKA GRID dashboard.blade.php --}}

        {{-- START: Worker Summary Card --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 mb-6">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Ringkasan Pekerja</h3>
            <p class="text-sm text-gray-600 mb-4">
                Total jumlah jadwal per pekerja untuk periode <span class="font-semibold">{{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }}</span> s/d <span class="font-semibold">{{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }}</span>.
            </p>

            @if (isset($workerCounts) && count($workerCounts) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                    @foreach ($workerCounts as $worker)
                        <div class="py-2 flex justify-between items-center border-b border-gray-100">
                            <span class="text-gray-700">{{ $worker['name'] }}</span>
                            <span class="font-semibold text-blue-600 bg-blue-100 px-3 py-0.5 rounded-full text-sm">
                                {{ $worker['count'] }} kali
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 italic">Tidak ada data pekerja untuk rentang tanggal ini.</p>
            @endif
        </div>
        {{-- END: Worker Summary Card --}}

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <form method="GET" action="{{ route('schedule.page') }}" class="flex items-center gap-3">
                <div class="flex flex-col">
                    <label class="text-sm text-gray-700 mb-1">Tanggal Mulai (Rentang 7 Hari)</label>
                    <input type="date" name="startDate" value="{{ $startDate }}"
                        class="px-3 py-2 rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        onchange="this.form.submit()" />
                    <small class="text-xs text-gray-500 mt-1">Minggu: {{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }} s/d
                        {{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }}
                    </small>
                </div>
            </form>

            <button id="exportBtn" onclick="handleExport()"
                class="px-5 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed">
                Export PDF
            </button>
        </div>
        </div>
        </main>
    </div>

    {{-- SCRIPT (Tidak ada perubahan) --}}
    <script>
        // Initial data from server
        let scheduleMap = @json($scheduleMap);
        let supervisorMap = @json($supervisorMap);
        let startDate = '{{ $startDate }}';
        let endDate = '{{ $endDate }}';
        let isExporting = false;

        const TIME_SLOT_HEIGHT = 60;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Helper Functions
        function showAlert(message) {
            document.getElementById('alertMessage').textContent = message;
            document.getElementById('alertModal').classList.remove('hidden');
        }

        function closeAlert() {
            document.getElementById('alertModal').classList.add('hidden');
        }

        // Export to PDF (using html2canvas and jsPDF)
        async function handleExport() {
            const scrollContainer = document.getElementById('scrollContainer');
            const gridElement = document.getElementById('scheduleGrid');
            const exportBtn = document.getElementById('exportBtn');

            if (!scrollContainer || !gridElement) {
                showAlert('Gagal mengekspor: Elemen tidak ditemukan.');
                return;
            }

            showAlert('Mempersiapkan PDF... Ini mungkin perlu beberapa saat.');
            exportBtn.disabled = true;
            exportBtn.textContent = 'Mengekspor...';
            isExporting = true;

            // Save original styles
            const originalScrollStyle = scrollContainer.style.cssText;
            const originalStyles = [];

            // Disable sticky elements
            const stickyElements = gridElement.querySelectorAll('[data-export-sticky="true"]');
            stickyElements.forEach(el => {
                originalStyles.push({
                    el,
                    cssText: el.style.cssText
                });
                el.style.position = 'relative';
                el.style.top = 'auto';
                el.style.left = 'auto';
                el.style.zIndex = '1';
            });

            // Disable overflow on cards
            const cardElements = gridElement.querySelectorAll('[data-export-card="true"]');
            cardElements.forEach(el => {
                originalStyles.push({
                    el,
                    cssText: el.style.cssText
                });
                el.style.overflow = 'visible';
            });

            // Disable truncate
            const truncateElements = gridElement.querySelectorAll('[data-export-truncate="true"]');
            truncateElements.forEach(el => {
                originalStyles.push({
                    el,
                    cssText: el.style.cssText
                });
                el.style.overflow = 'visible';
                el.style.whiteSpace = 'normal';
                el.style.textOverflow = 'unset';
            });

            // Disable scroll
            scrollContainer.style.maxHeight = 'none';
            scrollContainer.style.overflow = 'visible';
            scrollContainer.scrollTop = 0;
            scrollContainer.scrollLeft = 0;

            // Cleanup function
            const cleanupDOM = () => {
                scrollContainer.style.cssText = originalScrollStyle;
                originalStyles.forEach(style => {
                    style.el.style.cssText = style.cssText;
                });
                exportBtn.disabled = false;
                exportBtn.textContent = 'Export PDF';
                isExporting = false;
                setTimeout(() => closeAlert(), 3000);
            };

            // Wait for DOM to update
            setTimeout(async () => {
                try {
                    const canvas = await html2canvas(gridElement, {
                        scale: 2,
                        useCORS: true,
                        logging: false,
                        allowTaint: true,
                        backgroundColor: '#ffffff',
                        width: gridElement.scrollWidth,
                        height: gridElement.scrollHeight,
                        windowWidth: gridElement.scrollWidth,
                        windowHeight: gridElement.scrollHeight
                    });

                    const imgData = canvas.toDataURL('image/png');
                    const imgWidth = canvas.width;
                    const imgHeight = canvas.height;

                    const {
                        jsPDF
                    } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'p',
                        unit: 'mm',
                        format: 'a4'
                    });

                    const pdfWidth = 210;
                    const margin = 10;
                    const usableWidth = pdfWidth - margin * 2;
                    const ratio = usableWidth / imgWidth;
                    const finalImgWidth = usableWidth;
                    const finalImgHeight = imgHeight * ratio;

                    pdf.addImage(imgData, 'PNG', margin, margin, finalImgWidth, finalImgHeight);

                    const dateStr = new Date().toISOString().split('T')[0];
                    pdf.save(`Jadwal_${startDate}_${endDate}_${dateStr}.pdf`);

                    showAlert('PDF berhasil dibuat!');
                } catch (err) {
                    console.error('Error creating PDF:', err);
                    showAlert('Terjadi kesalahan saat membuat PDF: ' + err.message);
                } finally {
                    cleanupDOM();
                }
            }, 100);
        }
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
@endsection
