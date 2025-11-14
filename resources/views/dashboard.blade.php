@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 text-gray-900 font-sans">
  <main class="space-y-6">

    <div class="flex items-center justify-between">
      <div>

        <h2 class="text-3xl font-bold mb-2 text-gray-900">Dashboard</h2>
        <form method="GET" class=" flex space-x-2">
            <select name="month" class="border rounded px-2 py-1">
                @foreach (range(1, 12) as $m)
                <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                    {{ Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}
                </option>
                @endforeach
            </select>

            <select name="year" class="border rounded px-2 py-1">
                @foreach (range(now()->year - 3, now()->year + 1) as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                    {{ $y }}
                </option>
                @endforeach
            </select>

            <button class="px-3 py-1 bg-blue-600 text-white rounded">
                Filter
            </button>
        </form>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

      {{-- WORKER LIST --}}
      <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-md overflow-y-auto max-h-[80vh]">
        <h2 class="text-xl font-bold mb-3">Daftar Worker Bulan {{ $monthName }} {{ $year }}</h2>

        <ul class="space-y-1 text-sm">
          @foreach ($workers as $w)
            <li class="flex justify-between items-center border p-2 rounded hover:bg-gray-50 transition">
              <div>
                <div class="font-semibold">{{ $w->name }}</div>
                <div class="text-xs text-gray-500">
                  @if ($w->role_id == 1) Supervisor
                  @elseif ($w->role_id == 2) Karyawan
                  @elseif ($w->role_id == 3) Admin
                  @else Unknown
                  @endif
                </div>
              </div>

              <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs font-medium">
                {{ $w->schedule_count }} jadwal
              </span>
            </li>
          @endforeach
        </ul>
      </div>

      {{-- RIGHT SIDE — JADWAL BY LOCATION & MONTH --}}
        <div class="lg:col-span-3 bg-white border border-gray-200 rounded-xl p-6 shadow-md">

        <h2 class="text-xl font-bold mb-4">Jadwal Berdasarkan Lokasi & Bulan</h2>

        {{-- FILTER --}}
        <form method="GET" class="flex items-end gap-4 mb-6">

            {{-- Lokasi --}}
            <div class="w-full md:w-1/3">
                <label class="block text-sm font-medium mb-1">Lokasi</label>
                <select name="location_id" class="border rounded px-2 py-1 w-full">
                    <option value="">Pilih Lokasi</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Button --}}
            <div>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">
                    Filter
                </button>
            </div>

        </form>



        {{-- HASIL --}}
        @if ($schedulesByLocation->isEmpty())
            <p class="text-gray-500 italic">Tidak ada jadwal pada filter ini.</p>
        @else

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($schedulesByLocation as $supervisorId => $items)
                    @foreach ($items as $date => $lists)

                        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm">

                            {{-- Supervisor --}}
                            <div class="text-lg font-bold text-blue-700 mb-1">
                                Dewan: {{ $lists->first()->supervisor->name ?? '-' }}
                            </div>

                            {{-- Hari & tanggal --}}
                            <div class="text-sm font-semibold text-gray-600 mb-3">
                                {{ $lists->first()->hari }} — {{ $lists->first()->tanggal }}
                            </div>

                            {{-- List pekerjaan (KESAMPING) --}}
                            <div class="flex flex-wrap gap-2">
                                @foreach ($lists as $sch)
                                    <div class="px-3 py-2 bg-blue-100 text-blue-800 rounded-lg text-sm border border-blue-200">
                                        <span class="font-semibold">{{ $sch->worker->name }}</span>
                                        — {{ $sch->jobdesc->name }}
                                    </div>
                                @endforeach
                            </div>

                        </div>

                    @endforeach
                @endforeach
            </div>

        @endif

        </div>


    </div>
  </main>
</div>
@endsection
