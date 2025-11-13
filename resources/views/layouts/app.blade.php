<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>ABADI Comm</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">
  <header class="bg-white shadow-sm border-b-2 border-blue-600">
    <div class="mx-auto px-6 py-4 flex justify-between items-center">
      <div class="flex items-center gap-3">
        <img src="{{ asset('images/logo_small.png') }}" alt="ABADI Comm Logo" class="w-10 h-10 object-contain" />
        <h1 class="text-2xl font-bold text-blue-600">ABADI Comm</h1>
      </div>

      @auth
      <div class="flex items-center gap-4">
        @if (auth()->user()->role_id == 3)
          <a href="{{ route('register') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium rounded-lg hover:bg-gray-100 transition">
            Register Worker
          </a>
        @endif
        <a href="{{ route('logout') }}" class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition">
          Logout
        </a>
      </div>
      @endauth
    </div>
  </header>

  @auth
  <nav class="flex items-center justify-between bg-gray-50 border-b border-gray-200 px-8 py-4 shadow-sm mx-auto">
    <div class="flex items-center gap-6">
      <a href="/dashboard" class="text-sm font-medium {{ request()->is('dashboard') ? 'text-blue-600' : 'text-gray-700 hover:text-blue-600' }}">Dashboard</a>
      @if (auth()->user()->role_id == 3)
        <a href="/assign" class="text-sm font-medium {{ request()->is('assign') ? 'text-blue-600' : 'text-gray-700 hover:text-blue-600' }}">Assign Worker</a>
      @endif
      <a href="/schedule" class="text-sm font-medium {{ request()->is('schedule*') ? 'text-blue-600' : 'text-gray-700 hover:text-blue-600' }}">Schedule</a>
    </div>
  </nav>
  @endauth

  <main class="mx-auto py-6 sm:px-6 lg:px-8">
    @yield('content')
  </main>

  <footer class="border-t bg-white text-center text-gray-500 text-sm py-4">
    © {{ date('Y') }} ABADI Comm. All rights reserved.
  </footer>
</body>
</html>
