<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'HookPulse') }} - B2B Webhook Management Platform</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        slate: {
                            850: '#151f32',
                            950: '#090d16',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen selection:bg-indigo-500 selection:text-white">

    <!-- Top Navigation Bar -->
    <header class="border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="p-2 bg-indigo-600 rounded-lg text-white font-bold flex items-center justify-center shadow-lg shadow-indigo-600/30">
                    ⚡
                </span>
                <span class="font-bold text-lg tracking-tight text-white">Hook<span class="text-indigo-400">Pulse</span></span>
                <span class="text-xs bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-2 py-0.5 rounded-full ml-2">Enterprise B2B</span>
            </div>

            <div class="flex items-center gap-4 text-sm text-slate-400">
                <a href="/horizon" target="_blank" class="hover:text-white transition flex items-center gap-1.5 bg-slate-800/80 hover:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700/60">
                    <span>📊</span> Horizon Queues
                </a>
            </div>
        </div>
    </header>

    <!-- Main Page Content -->
    <main class="max-w-7xl mx-auto py-8">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="max-w-7xl mx-auto px-6 py-8 border-t border-slate-800/60 text-center text-xs text-slate-500">
        HookPulse &bull; Multi-Tenant Webhook Management & Exponential Backoff Engine &copy; {{ date('Y') }}
    </footer>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>
