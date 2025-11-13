@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 text-gray-900 font-sans">
  @if (session('success'))
  <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3 text-green-700 max-w-7xl mx-auto">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
      <polyline points="22 4 12 14.01 9 11.01"></polyline>
    </svg>
    <span class="font-medium">{{ session('success') }}</span>
  </div>
  @endif

  @if (session('error'))
  <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3 text-red-700 max-w-7xl mx-auto">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
      <circle cx="12" cy="12" r="10"></circle>
      <line x1="15" y1="9" x2="9" y2="15"></line>
      <line x1="9" y1="9" x2="15" y2="15"></line>
    </svg>
    <span class="font-medium">{{ session('error') }}</span>
  </div>
  @endif

  <main class="p-8 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold mb-2 text-gray-900">Weekly Schedule</h2>
        <p class="text-gray-600">View and manage worker schedules</p>
      </div>
      @if (auth()->user()->role_id == 3)
      <a href="{{ route('assign') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[#E63946] hover:bg-[#d62828] transition text-white font-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Add Schedule
      </a>
      @endif
    </div>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-md hover:shadow-lg transition">
      <div class="overflow-auto max-h-[80vh]">
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
            $dayWidths[] = "minmax({$totalCols}50px, {$totalCols}fr)";
          }

          $TIME_SLOT_HEIGHT = 60;
        @endphp

        <div class="grid" style="grid-template-columns: 120px {{ implode(' ', $dayWidths) }}; min-width: max-content; background-color: #ffffff;">

          {{-- HEADER (2 Rows, Sticky) --}}

          {{-- Row 1: Time Label --}}
          <div class="sticky top-0 left-0 px-6 py-4 text-left text-sm font-semibold text-gray-700 bg-gray-50 border-b border-r border-gray-200 z-30 flex items-center" style="grid-row: span 2 / span 2;">
            Jam
          </div>

          {{-- Row 1: Dates --}}
          @foreach ($displayedDates as $d)
          <div class="sticky top-0 px-6 py-4 text-center text-sm font-semibold text-gray-700 bg-gray-100 border-b border-gray-200 z-20">
            {{ $d['day'] }}, {{ $d['date'] }}
          </div>
          @endforeach

          {{-- Row 2: Supervisors --}}
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
                  <div class="px-3 py-2 text-xs italic text-gray-400 text-center">-</div>
                @endif
              </div>
            </div>
          @endforeach

          {{-- BODY GRID --}}

          {{-- Column 1: Time Axis --}}
          <div class="col-start-1 sticky left-0 z-10" style="display: grid; grid-template-rows: repeat({{ count($timeSlots) }}, {{ $TIME_SLOT_HEIGHT }}px); grid-row-start: 3;">
            @foreach ($timeSlots as $slot)
            <div class="px-4 py-2 text-sm font-medium text-gray-900 border-r border-b border-gray-100 bg-gray-50 box-border" style="height: {{ $TIME_SLOT_HEIGHT }}px;">
              {{ $slot }}
            </div>
            @endforeach
          </div>

          {{-- Columns 2+: Day Columns --}}
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

            <div class="relative grid {{ $dayIndex % 2 === 0 ? 'bg-gray-50' : 'bg-white' }}"
                 style="grid-template-rows: repeat({{ count($timeSlots) }}, {{ $TIME_SLOT_HEIGHT }}px);
                        grid-template-columns: repeat({{ $totalSubCols }}, minmax(150px, 1fr));
                        grid-column-start: {{ $dayIndex + 2 }};
                        grid-row-start: 3;
                        border-left: 1px solid #e5e7eb;">

              {{-- Background Grid --}}
              @for ($i = 0; $i < count($timeSlots) * $totalSubCols; $i++)
                @php
                  $row = floor($i / $totalSubCols) + 1;
                  $col = ($i % $totalSubCols) + 1;
                @endphp
                <div style="grid-row: {{ $row }}; grid-column: {{ $col }}; height: {{ $TIME_SLOT_HEIGHT }}px;"
                     class="border-b border-gray-100 box-border {{ $col < $totalSubCols ? 'border-r border-gray-100' : '' }}">
                </div>
              @endfor

              {{-- Schedule Events --}}
              @foreach ($events as $ev)
                @php
                  $supIndex = array_search($ev['supervisor_name'], $supervisors);
                  if ($supIndex === false) continue;

                  $colOffset = 0;
                  for ($i = 0; $i < $supIndex; $i++) {
                    $colOffset += $supervisorSubCols[$supervisors[$i]];
                  }

                  $colStart = $colOffset + $ev['subColumn'] + 1;
                @endphp

                <div style="grid-row-start: {{ $ev['gridRowStart'] }};
                            grid-row-end: span {{ $ev['gridRowSpan'] }};
                            grid-column-start: {{ $colStart }};
                            padding: 0.25rem;
                            z-index: 5;
                            position: relative;"
                     class="group cursor-pointer"
                     onclick="window.location.href='{{ route('jadwal.edit', ['dateKey' => urlencode($d['formatted']), 'supervisor' => urlencode($ev['supervisor_name']), 'start' => urlencode($ev['start'])]) }}'">

                  <div class="flex-1 min-w-0 p-2 border border-blue-200 rounded-lg bg-blue-50 shadow-sm overflow-hidden h-full relative transition hover:shadow-lg hover:bg-blue-100">
                    <div class="text-blue-700 font-semibold mb-1 text-xs truncate">
                      {{ $ev['supervisor_name'] }}
                    </div>

                    @foreach ($ev['workers'] as $w)
                    <div class="text-xs text-gray-700 leading-tight truncate">
                      • {{ $w['worker_name'] }} — <span class="font-medium">{{ $w['jobdesc_name'] }}</span>
                    </div>
                    @endforeach

                    {{-- Hover Overlay --}}
                    <div class="absolute inset-0 flex items-center justify-center bg-blue-600 bg-opacity-0 group-hover:bg-opacity-10 transition-all pointer-events-none">
                      <span class="opacity-0 group-hover:opacity-100 text-blue-700 font-bold text-sm flex items-center gap-1 transition-opacity bg-white px-3 py-1 rounded-full shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.586-9.414a2 2 0 112.828 2.828L11 15l-4 1 1-4 8.414-8.414z" />
                        </svg>
                        Click to Edit
                      </span>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endforeach

        </div>
      </div>

      {{-- Filter Section --}}
      <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
        <form method="GET" action="{{ route('jadwal') }}" class="flex items-center gap-3">
          <div class="flex flex-col">
            <label class="text-sm text-gray-700 mb-1">Tanggal Mulai (Rentang 7 Hari)</label>
            <input
              type="date"
              name="startDate"
              value="{{ $startDate }}"
              class="px-3 py-2 rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400"
              onchange="this.form.submit()"
            />
          </div>
        </form>
      </div>
    </div>
  </main>
</div>
@endsection
