@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100">
  <div class="max-w-6xl mx-auto p-8">
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Job Description Management</h1>
          <p class="text-gray-600">Manage all job descriptions in the system</p>
        </div>
        <button
          onclick="showAddModal()"
          class="flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-semibold shadow-md"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Add New Job Description
        </button>
      </div>
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

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
              Job Description Name
            </th>
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
              Used in Schedules
            </th>
            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @forelse ($jobdescs as $jobdesc)
          <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ $jobdesc->name }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $jobdesc->schedules_count > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                {{ $jobdesc->schedules_count }} schedule(s)
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                onclick="showEditModal({{ $jobdesc->id }}, '{{ addslashes($jobdesc->name) }}')"
                class="text-blue-600 hover:text-blue-900 mr-4 inline-flex items-center gap-1"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit
              </button>
              <button
                onclick="showDeleteModal({{ $jobdesc->id }}, '{{ addslashes($jobdesc->name) }}', {{ $jobdesc->schedules_count }})"
                class="text-red-600 hover:text-red-900 inline-flex items-center gap-1"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Delete
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="3" class="px-6 py-12 text-center text-gray-500">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-lg font-medium">No job descriptions found</p>
              <p class="text-sm mt-1">Click "Add New Job Description" to create one</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Modal -->
  <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-96 shadow-2xl">
      <h3 class="text-xl font-bold text-gray-900 mb-4">Add New Job Description</h3>
      <form action="{{ route('jobdesc.store') }}" method="POST">
        @csrf
        <input
          type="text"
          name="name"
          placeholder="Enter job description name"
          class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none mb-4"
          required
        />
        <div class="flex gap-3 justify-end">
          <button
            type="button"
            onclick="hideAddModal()"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition"
          >
            Add Job Description
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-96 shadow-2xl">
      <h3 class="text-xl font-bold text-gray-900 mb-4">Edit Job Description</h3>
      <form id="editForm" method="POST">
        @csrf
        @method('PUT')
        <input
          type="text"
          id="editName"
          name="name"
          placeholder="Enter job description name"
          class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:outline-none mb-4"
          required
        />
        <div class="flex gap-3 justify-end">
          <button
            type="button"
            onclick="hideEditModal()"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition"
          >
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Modal -->
  <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-96 shadow-2xl">
      <h3 class="text-xl font-bold text-gray-900 mb-4">Delete Job Description</h3>
      <p id="deleteMessage" class="text-gray-700 mb-6"></p>
      <form id="deleteForm" method="POST">
        @csrf
        @method('DELETE')
        <div class="flex gap-3 justify-end">
          <button
            type="button"
            onclick="hideDeleteModal()"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition"
          >
            Delete
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function showAddModal() {
      document.getElementById('addModal').classList.remove('hidden');
    }

    function hideAddModal() {
      document.getElementById('addModal').classList.add('hidden');
    }

    function showEditModal(id, name) {
      document.getElementById('editForm').action = `/jobdesc/${id}`;
      document.getElementById('editName').value = name;
      document.getElementById('editModal').classList.remove('hidden');
    }

    function hideEditModal() {
      document.getElementById('editModal').classList.add('hidden');
    }

    function showDeleteModal(id, name, scheduleCount) {
      if (scheduleCount > 0) {
        document.getElementById('deleteMessage').innerHTML =
          `<strong class="text-red-600">Warning!</strong> This job description "<strong>${name}</strong>" is being used in ${scheduleCount} schedule(s). You cannot delete it until all related schedules are removed.`;
        document.getElementById('deleteForm').querySelector('button[type="submit"]').disabled = true;
        document.getElementById('deleteForm').querySelector('button[type="submit"]').classList.add('opacity-50', 'cursor-not-allowed');
      } else {
        document.getElementById('deleteMessage').textContent =
          `Are you sure you want to delete "${name}"? This action cannot be undone.`;
        document.getElementById('deleteForm').querySelector('button[type="submit"]').disabled = false;
        document.getElementById('deleteForm').querySelector('button[type="submit"]').classList.remove('opacity-50', 'cursor-not-allowed');
      }
      document.getElementById('deleteForm').action = `/jobdesc/${id}`;
      document.getElementById('deleteModal').classList.remove('hidden');
    }

    function hideDeleteModal() {
      document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        hideAddModal();
        hideEditModal();
        hideDeleteModal();
      }
    });

    // Close modals when clicking outside
    ['addModal', 'editModal', 'deleteModal'].forEach(modalId => {
      document.getElementById(modalId)?.addEventListener('click', function(e) {
        if (e.target === this) {
          if (modalId === 'addModal') hideAddModal();
          if (modalId === 'editModal') hideEditModal();
          if (modalId === 'deleteModal') hideDeleteModal();
        }
      });
    });
  </script>
</div>
@endsection
