@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-white">
  <div class="max-w-4xl mx-auto p-8">
    <div class="mb-8">
      <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('schedule.page') }}" class="flex items-center gap-2 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition font-medium">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Back to Schedule
        </a>
      </div>

      <h1 class="text-3xl font-bold text-gray-900 mb-2">Edit Schedule</h1>
      <p class="text-gray-600">Modify the event details and worker assignments</p>
    </div>

    @if (session('success'))
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3 text-green-700">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
      <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    @if (session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3 text-red-700">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="15" y1="9" x2="9" y2="15"></line>
        <line x1="9" y1="9" x2="15" y2="15"></line>
      </svg>
      <span class="font-medium">{{ session('error') }}</span>
    </div>
    @endif

    @if ($errors->any())
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('schedule.update') }}" id="editForm" class="space-y-6">
      @csrf

      {{-- Hidden: Schedule IDs to delete --}}
      @foreach ($schedules as $schedule)
      <input type="hidden" name="scheduleIds[]" value="{{ $schedule->id }}">
      @endforeach

      <div class="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Event Details</h3>
        <div class="grid grid-cols-2 gap-4">
          <label class="block">
            <span class="text-sm font-semibold text-gray-700 mb-2 block">Hari Tanggal</span>
            <input
              type="date"
              name="date"
              value="{{ old('date', $dateKey) }}"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none transition bg-blue-50"
              required
            />
          </label>

          <label class="block">
            <span class="text-sm font-semibold text-gray-700 mb-2 block">Tempat</span>
            <input
              type="text"
              name="location"
              value="{{ old('location', $assignments[0]['tempat'] ?? '') }}"
              placeholder="Enter location"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none transition bg-blue-50"
              required
            />
          </label>
        </div>
      </div>

      <div class="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Event Time</h3>
        <div class="grid grid-cols-2 gap-4">
          <label class="block">
            <span class="text-sm font-semibold text-gray-700 mb-2 block">Waktu Mulai</span>
            <input
              type="time"
              name="startTime"
              value="{{ old('startTime', $startTime->format('H:i')) }}"
              min="07:00"
              max="21:00"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none transition bg-blue-50"
              required
            />
          </label>

          <label class="block">
            <span class="text-sm font-semibold text-gray-700 mb-2 block">Waktu Berakhir</span>
            <input
              type="time"
              name="endTime"
              value="{{ old('endTime', $endTime->format('H:i')) }}"
              min="07:00"
              max="21:00"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none transition bg-blue-50"
              required
            />
          </label>
        </div>
      </div>

      <div class="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Worker Assignments</h3>
          <button
            type="button"
            onclick="addAssignment()"
            class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition text-sm font-medium"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Worker
          </button>
        </div>

        <div id="assignmentsContainer">
          @foreach ($assignments as $index => $assignment)
          <div class="assignment-item bg-gray-50 border-2 border-gray-200 rounded-lg p-4 space-y-4 mb-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-semibold text-gray-700">Worker #{{ $index + 1 }}</span>
              @if ($index > 0)
              <button
                type="button"
                onclick="removeAssignment(this)"
                class="p-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
              @endif
            </div>

            <div class="space-y-3">
              <label class="block">
                <span class="text-sm font-medium text-gray-700 mb-1.5 block">Worker</span>
                <select
                  name="assignments[{{ $index }}][workerId]"
                  class="worker-select w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none transition bg-blue-50 font-medium"
                  required
                >
                  <option value="">Select Worker</option>
                  @foreach ($workers as $worker)
                  <option value="{{ $worker->id }}" {{ $assignment['workerId'] == $worker->id ? 'selected' : '' }}>
                    {{ $worker->name }}
                  </option>
                  @endforeach
                </select>
              </label>

              <label class="block">
                <span class="text-sm font-medium text-gray-700 mb-1.5 block">Jobdesc</span>
                <select
                  name="assignments[{{ $index }}][jobdescId]"
                  class="jobdesc-select w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none transition bg-blue-50 font-medium"
                  required
                >
                  <option value="">Select Job Description</option>
                  @foreach ($jobdescs as $jobdesc)
                  <option value="{{ $jobdesc->id }}" {{ $assignment['jobdescId'] == $jobdesc->id ? 'selected' : '' }}>
                    {{ $jobdesc->name }}
                  </option>
                  @endforeach
                </select>
              </label>

              <label class="block">
                <span class="text-sm font-medium text-gray-700 mb-1.5 block">Supervisor</span>
                <select
                  name="assignments[{{ $index }}][supervisorId]"
                  class="supervisor-select w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none transition bg-blue-50 font-medium"
                  required
                >
                  <option value="">Select Supervisor</option>
                  @foreach ($supervisors as $supervisor)
                  <option value="{{ $supervisor->id }}" {{ $assignment['supervisorId'] == $supervisor->id ? 'selected' : '' }}>
                    {{ $supervisor->name }}
                  </option>
                  @endforeach
                </select>
              </label>
            </div>
          </div>
          @endforeach
        </div>
      </div>

      <div class="flex justify-center gap-4 pt-4">
        <a href="{{ route('schedule.page') }}" class="px-8 py-3 font-bold rounded-lg transition shadow-md hover:shadow-lg text-lg bg-gray-400 hover:bg-gray-500 text-white">
          Cancel
        </a>
        <button
          type="submit"
          class="px-8 py-3 font-bold rounded-lg transition shadow-md hover:shadow-lg text-lg bg-blue-500 hover:bg-blue-600 text-white focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
          Save Changes
        </button>
      </div>
    </form>
  </div>

  <script>
    let assignmentCount = {{ count($assignments) }};

    function addAssignment() {
      const container = document.getElementById('assignmentsContainer');
      const newAssignment = document.createElement('div');
      newAssignment.className = 'assignment-item bg-gray-50 border-2 border-gray-200 rounded-lg p-4 space-y-4 mb-4';
      newAssignment.innerHTML = `
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-semibold text-gray-700">Worker #${assignmentCount + 1}</span>
          <button
            type="button"
            onclick="removeAssignment(this)"
            class="p-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="space-y-3">
          <label class="block">
            <span class="text-sm font-medium text-gray-700 mb-1.5 block">Worker</span>
            <select
              name="assignments[${assignmentCount}][workerId]"
              class="worker-select w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none transition bg-blue-50 font-medium"
              required
            >
              <option value="">Select Worker</option>
              @foreach ($workers as $worker)
              <option value="{{ $worker->id }}">{{ $worker->name }}</option>
              @endforeach
            </select>
          </label>

          <label class="block">
            <span class="text-sm font-medium text-gray-700 mb-1.5 block">Jobdesc</span>
            <select
              name="assignments[${assignmentCount}][jobdescId]"
              class="jobdesc-select w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none transition bg-blue-50 font-medium"
              required
            >
              <option value="">Select Job Description</option>
              @foreach ($jobdescs as $jobdesc)
              <option value="{{ $jobdesc->id }}">{{ $jobdesc->name }}</option>
              @endforeach
            </select>
          </label>

          <label class="block">
            <span class="text-sm font-medium text-gray-700 mb-1.5 block">Supervisor</span>
            <select
              name="assignments[${assignmentCount}][supervisorId]"
              class="supervisor-select w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none transition bg-blue-50 font-medium"
              required
            >
              <option value="">Select Supervisor</option>
              @foreach ($supervisors as $supervisor)
              <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
              @endforeach
            </select>
          </label>
        </div>
      `;
      container.appendChild(newAssignment);
      assignmentCount++;
    }

    function removeAssignment(button) {
      const item = button.closest('.assignment-item');
      item.remove();
      updateAssignmentNumbers();
    }

    function updateAssignmentNumbers() {
      const items = document.querySelectorAll('.assignment-item');
      items.forEach((item, index) => {
        const label = item.querySelector('.text-sm.font-semibold');
        if (label) {
          label.textContent = `Worker #${index + 1}`;
        }
      });
    }
  </script>
</div>
@endsection
