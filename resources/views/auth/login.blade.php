@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center p-4 font-sans">
  <div class="max-w-md w-full bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
    <div class="p-8 space-y-6">
      <div class="text-center">
        <h2 class="text-3xl font-bold text-[#0066FF]">
          Welcome Back!
        </h2>
        <p class="text-gray-600 mt-2">
          Please login to continue
        </p>
      </div>

      @if (session('error'))
        <div class="text-red-600 text-center text-sm">{{ session('error') }}</div>
      @endif

      @if ($errors->any())
        <div class="text-red-600 text-center text-sm">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Name input --}}
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
            Name
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              {{-- Icon User --}}
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.612 0 5.034.755 6.879 2.046M15 11a3 3 0 10-6 0 3 3 0 006 0z" />
              </svg>
            </div>
            <input
              id="name"
              name="name"
              type="text"
              value="{{ old('name') }}"
              required
              placeholder="Enter your name"
              class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0066FF] focus:border-transparent transition"
            />
          </div>
        </div>

        {{-- Password input --}}
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            Password
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              {{-- Icon Lock --}}
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c.828 0 1.5.672 1.5 1.5V16a1.5 1.5 0 01-3 0v-3.5c0-.828.672-1.5 1.5-1.5zM6 10V8a6 6 0 1112 0v2m-9 4h6" />
              </svg>
            </div>
            <input
              id="password"
              name="password"
              type="password"
              required
              placeholder="Enter your password"
              class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0066FF] focus:border-transparent transition"
            />
          </div>
        </div>

        {{-- Error message --}}
        @if ($errors->has('login'))
          <p class="text-sm text-red-600 text-center">{{ $errors->first('login') }}</p>
        @endif

        {{-- Submit button --}}
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
