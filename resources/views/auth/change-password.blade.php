{{-- filepath: /Users/alizaenazet/programming/personal/project/Abadicomm_Management_API/resources/views/auth/change-password.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white shadow-md rounded-lg p-8">
  <h2 class="text-2xl font-bold text-gray-800 mb-6">Ubah Password</h2>

  @if (session('success'))
    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
      {{ session('success') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('change-password') }}">
    @csrf

    <div class="mb-4">
      <label for="current_password" class="block text-gray-700 font-medium mb-2">Password Saat Ini</label>
      <input 
        type="password" 
        id="current_password" 
        name="current_password" 
        required
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
    </div>

    <div class="mb-4">
      <label for="new_password" class="block text-gray-700 font-medium mb-2">Password Baru</label>
      <input 
        type="password" 
        id="new_password" 
        name="new_password" 
        required
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
    </div>

    <div class="mb-6">
      <label for="new_password_confirmation" class="block text-gray-700 font-medium mb-2">Konfirmasi Password Baru</label>
      <input 
        type="password" 
        id="new_password_confirmation" 
        name="new_password_confirmation" 
        required
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
    </div>

    <button 
      type="submit" 
      class="w-full bg-blue-600 text-white font-medium py-2 rounded-lg hover:bg-blue-700 transition"
    >
      Ubah Password
    </button>
  </form>

  <div class="mt-6 pt-6 border-t border-gray-200">
    <p class="text-sm text-gray-600 mb-3">Lupa password saat ini?</p>
    <button 
      disabled
      class="w-full bg-gray-300 text-gray-500 font-medium py-2 rounded-lg cursor-not-allowed"
      title="Fitur ini belum tersedia"
    >
      Reset Password (Coming Soon)
    </button>
  </div>
</div>
@endsection