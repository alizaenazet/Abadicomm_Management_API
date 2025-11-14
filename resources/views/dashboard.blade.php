@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 text-gray-900 font-sans">
  <main class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold mb-2 text-gray-900">Dashboard</h2>
        <p class="text-gray-600">
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      {{-- WORKER LIST --}}
      <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-md overflow-y-auto max-h-[80vh]">
        <h2 class="text-xl font-bold mb-3">Daftar Worker</h2>
        <ul class="space-y-1 text-sm">
          @foreach ($workers as $w)
            <li class="flex justify-between border p-2 rounded hover:bg-gray-50 transition">
              <span>{{ $w->name }}</span>
              <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-xs font-medium">
                @if ($w->role_id == 1) Supervisor
                @elseif ($w->role_id == 2) Karyawan
                @elseif ($w->role_id == 3) Admin
                @else Unknown
                @endif
              </span>
            </li>
          @endforeach
        </ul>
      </div>

      {{-- SCHEDULE GRID --}}
      <div class="lg:col-span-3">
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-md hover:shadow-lg transition">
          <div class="overflow-x-auto overflow-y-auto max-h-[80vh]">
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
                $dayWidths[] = "minmax(" . ($totalCols * 150) . "px, {$totalCols}fr)";
              }

              $TIME_SLOT_HEIGHT = 60;
            @endphp

            <div class="grid" style="grid-template-columns: 120px {{ implode(' ', $dayWidths) }}; min-width: max-content;">

              {{-- HEADER (2 Rows, Sticky) --}}

              {{-- Row 1: Time Label (Spans 2 rows) --}}
              <div class="sticky top-0 left-0 px-6 py-4 text-left text-sm font-semibold text-gray-700 bg-gray-50 border-b border-r border-gray-200 z-30 flex items-center" style="grid-row: span 2 / span 2;">
                Jam
              </div>

              {{-- Row 1: Dates --}}
              @foreach ($displayedDates as $d)
                <div class="sticky top-0 px-6 py-4 text-center text-sm font-semibold text-gray-700 bg-gray-100 border-b border-gray-200 z-20">
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

                <div class="sticky top-[57px] bg-gray-50 border-b border-gray-200 z-20" style="grid-column-start: {{ $dayIndex + 2 }}; grid-row-start: 2;">
                  <div class="grid h-full" style="grid-template-columns: repeat({{ $totalSubCols }}, minmax(150px, 1fr));">
                    @if (count($supervisors) > 0)
                      @foreach ($supervisors as $name)
                        @php $cols = $supervisorSubCols[$name]; @endphp
                        <div class="px-3 py-2 text-xs font-medium text-gray-600 text-center border-r border-gray-200" style="grid-column: span {{ $cols }} / span {{ $cols }};">
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

              {{-- BODY GRID --}}
                @php
                $rowCount = count($timeSlots);
                @endphp

                {{-- Grid utama (jam + semua hari) --}}
                <div class="col-span-full"
                    style="grid-column: 1 / -1; display: grid;
                            grid-template-columns: 120px repeat({{ count($displayedDates) }}, 1fr);
                            grid-template-rows: repeat({{ $rowCount }}, minmax({{ $TIME_SLOT_HEIGHT }}px, auto));">

                {{-- Kolom JAM --}}
                @foreach ($timeSlots as $rowIndex => $slot)
                    <div class="sticky left-0 z-10 bg-gray-50 border-b border-r border-gray-200 flex items-center justify-center text-sm font-semibold text-gray-700"
                        style="grid-row: {{ $rowIndex + 1 }};">
                    {{ $slot }}
                    </div>
                @endforeach

                {{-- Kolom JADWAL PER HARI --}}
                @foreach ($displayedDates as $dayIndex => $d)
                    @php
                    $events = $scheduleMap[$d['formatted']] ?? [];
                    $supervisors = $supervisorMap[$d['formatted']] ?? [];
                    @endphp

                    <div class="relative border-l border-gray-300 {{ $dayIndex % 2 === 0 ? 'bg-gray-50' : 'bg-white' }}"
                        style="grid-column: {{ $dayIndex + 2 }};
                                display: grid;
                                grid-template-rows: subgrid;
                                grid-row: 1 / span {{ $rowCount }};">

                    {{-- Garis background --}}
                    @foreach ($timeSlots as $r)
                        <div class="border-b border-gray-100"></div>
                    @endforeach

                    {{-- Event --}}
                    @foreach ($events as $ev)
                        @php
                        $rowStart = $ev['gridRowStart'];
                        $rowSpan = $ev['gridRowSpan'];
                        @endphp

                        <div style="grid-row-start: {{ $rowStart }}; grid-row-end: span {{ $rowSpan }}; padding: 0.25rem;">
                        <div class="h-full p-2 border border-blue-200 rounded-lg bg-blue-50 shadow-sm overflow-hidden">
                            <div class="text-blue-700 font-semibold mb-1 text-xs truncate">
                            {{ $ev['supervisor_name'] }}
                            </div>
                            @foreach ($ev['workers'] as $w)
                            <div class="text-xs text-gray-700 leading-tight truncate">
                                • {{ $w['worker_name'] }} – <span class="font-medium">{{ $w['jobdesc_name'] }}</span>
                            </div>
                            @endforeach
                        </div>
                        </div>
                    @endforeach
                    </div>
                @endforeach
                </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
@endsection
