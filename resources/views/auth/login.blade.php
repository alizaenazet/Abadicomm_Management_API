@extends('layouts.app')

@section('content')
<div class="flex justify-center items-center py-12 px-4">
  <div class="max-w-md w-full bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
    <div class="p-8 space-y-6">
      <div class="text-center">
        {{-- <img src="{{ asset('images/logo_small.png') }}" alt="AbadiComm Logo" class="w-16 h-16 mx-auto mb-4" /> --}}
        <h2 class="text-3xl font-bold text-blue-600">
          Welcome Back!
        </h2>
        <p class="text-gray-600 mt-2">
          Please login to continue
        </p>
      </div>

      <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        @if ($errors->any())
        <div class="text-sm text-center text-red-600 bg-red-50 p-3 rounded-lg border border-red-200">
          {{ $errors->first() }}
        </div>
        @endif

        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
            Name
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-gray-400">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <input
              id="name"
              name="name"
              type="text"
              value="{{ old('name') }}"
              placeholder="Enter your name"
              required
              autofocus
              class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
            />
          </div>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            Password
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-gray-400">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </div>
            <input
              id="password"
              name="password"
              type="password"
              placeholder="Enter your password"
              required
              class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
            />
          </div>
        </div>

        <button
          type="submit"
          class="w-full flex justify-center items-center gap-3 py-3 px-4 rounded-lg text-white font-semibold transition bg-blue-600 hover:bg-blue-700"
        >
          Login
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
