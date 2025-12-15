@extends('layouts.app')

@section('content')
    @auth
        <div class="mb-4 text-left">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-red-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Dashboard
            </a>
        </div>
    @endauth

    <div class="flex justify-center items-center py-12 px-4">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
                <div class="p-8 space-y-6">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold text-blue-600">
                            @auth
                                Register New Worker
                            @else
                                Create Account
                            @endauth
                        </h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Enter details to register.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('register') }}" class="space-y-5">
                        @csrf

                        @if (session('success'))
                            <div
                                class="p-3 text-sm text-center text-green-700 bg-green-50 rounded-lg border border-green-200">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="p-3 text-sm text-center text-red-700 bg-red-50 rounded-lg border border-red-200">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-gray-400">
                                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                                <input id="name" name="name" type="text" value="{{ old('name') }}"
                                    placeholder="@auth Enter worker's name @else Enter your name @endauth" required
                                    autofocus
                                    class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition" />
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-gray-400">
                                        <path
                                            d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                        </path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                </div>
                                <input id="email" name="email" type="email" value="{{ old('email') }}"
                                    placeholder="Enter email address" required
                                    class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition" />
                            </div>
                        </div>

                        @auth
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <select id="role" name="role"
                                    class="w-full px-3 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition">
                                    <option value="1" {{ old('role') == '1' ? 'selected' : '' }}>Supervisor</option>
                                    <option value="2" {{ old('role', '2') == '2' ? 'selected' : '' }}>Karyawan
                                    </option>
                                    <option value="3" {{ old('role') == '3' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="role" value="2">
                        @endauth

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-gray-400">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2">
                                        </rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </div>
                                <input id="password" name="password" type="password" placeholder="Enter password"
                                    required
                                    class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition" />
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation"
                                class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-gray-400">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2">
                                        </rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </div>
                                <input id="password_confirmation" name="password_confirmation" type="password"
                                    placeholder="Confirm password" required
                                    class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition" />
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full flex justify-center items-center gap-2 py-3 px-4 rounded-lg text-white font-semibold transition bg-blue-600 hover:bg-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="h-5 w-5">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <polyline points="17 11 19 13 23 9"></polyline>
                            </svg>
                            @auth
                                Register Worker
                            @else
                                Create Account
                            @endauth
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
