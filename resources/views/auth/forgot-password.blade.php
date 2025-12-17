@extends('layouts.app')

@section('content')
<div class="flex justify-center items-center py-12 px-4">
  <div class="max-w-md w-full bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
    <div class="p-8 space-y-6">
      <div class="text-center">
        <h2 class="text-3xl font-bold text-blue-600">Forgot Password</h2>
        <p class="text-gray-600 mt-2">
          Masukkan email Anda untuk menerima link reset password
        </p>
      </div>

      @if (session('success'))
        <div class="p-4 bg-green-50 text-green-700 rounded-lg border border-green-200">
          {{ session('success') }}
        </div>
      @endif

      @if ($errors->any())
        <div class="p-4 bg-red-50 text-red-700 rounded-lg border border-red-200">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
            Email Address
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-gray-400">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
            </div>
            <input
              id="email"
              name="email"
              type="email"
              value="{{ old('email') }}"
              placeholder="Enter your email"
              required
              autofocus
              class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
            />
          </div>
        </div>

        <button
          type="submit"
          class="w-full flex justify-center items-center gap-3 py-3 px-4 rounded-lg text-white font-semibold transition bg-blue-600 hover:bg-blue-700"
        >
          Send Reset Link
        </button>

        <div class="text-center">
          <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-700">
            Back to Login
          </a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection