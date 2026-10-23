<div class="p-6 bg-slate-900 text-slate-100 min-h-screen font-sans">
    <!-- Header Controls -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-6 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                <span class="p-2 bg-indigo-600 rounded-lg text-white text-base">⚡</span>
                HookPulse Live Monitoring
            </h1>
            <p class="text-slate-400 text-sm mt-1">Multi-tenant webhook management & exponential backoff engine</p>
        </div>

        <!-- Tenant Switcher -->
        <div class="flex items-center gap-3 bg-slate-800 p-2 rounded-xl border border-slate-700">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider pl-2">Active Tenant:</span>
            <select wire:model.live="selectedTenantId" class="bg-slate-900 border border-slate-700 text-white text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:border-indigo-500">
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}">{{ $tenant->name }} (ID: {{ $tenant->id }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
            <span>✅</span> {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-500/10 border border-rose-500/30 text-rose-400 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
            <span>⚠️</span> {{ session('error') }}
        </div>
    @endif

    <!-- Metrics Bar -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-slate-800/60 border border-slate-700/50 p-4 rounded-xl">
            <span class="text-xs text-slate-400">Total Requests</span>
            <div class="text-2xl font-bold mt-1 text-white">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-slate-800/60 border border-slate-700/50 p-4 rounded-xl">
            <span class="text-xs text-emerald-400">Successful Deliveries</span>
            <div class="text-2xl font-bold mt-1 text-emerald-400">{{ $stats['successful'] }}</div>
        </div>
        <div class="bg-slate-800/60 border border-slate-700/50 p-4 rounded-xl">
            <span class="text-xs text-rose-400">Failed Attempts</span>
            <div class="text-2xl font-bold mt-1 text-rose-400">{{ $stats['failed'] }}</div>
        </div>
        <div class="bg-slate-800/60 border border-slate-700/50 p-4 rounded-xl">
            <span class="text-xs text-indigo-400">Success Rate</span>
            <div class="text-2xl font-bold mt-1 text-indigo-400">{{ $stats['success_rate'] }}%</div>
        </div>
    </div>

    <!-- Dispatch Tester Card -->
    <div class="bg-slate-800/40 border border-slate-700/60 rounded-xl p-5 mb-8">
        <h3 class="text-sm font-semibold text-slate-200 mb-3">Broadcast Test Event</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" wire:model="newEventName" class="bg-slate-900 border border-slate-700 text-sm text-white px-3 py-2 rounded-lg focus:outline-none focus:border-indigo-500" placeholder="Event Name (e.g., invoice.paid)">
            <input type="text" wire:model="newEventPayload" class="bg-slate-900 border border-slate-700 text-sm text-white px-3 py-2 rounded-lg font-mono focus:outline-none focus:border-indigo-500" placeholder="JSON Payload">
            <button wire:click="triggerTestEvent" class="bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm rounded-lg px-4 py-2 transition shadow-lg shadow-indigo-600/20">
                Dispatch Event
            </button>
        </div>
    </div>

    <!-- Real-time Webhook Deliveries Log Table -->
    <div class="bg-slate-800/40 border border-slate-700/60 rounded-xl overflow-hidden shadow-xl">
        <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-slate-800/30">
            <h3 class="font-semibold text-sm text-slate-200">Webhook Delivery Logs</h3>
            <span class="text-xs text-slate-400">Auto-scoped to Tenant ID: {{ $selectedTenantId }}</span>
        </div>
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-800/80 text-xs text-slate-400 uppercase tracking-wider">
                <tr>
                    <th class="p-4">Status</th>
                    <th class="p-4">Event</th>
                    <th class="p-4">Endpoint</th>
                    <th class="p-4">Attempts</th>
                    <th class="p-4">Execution Time</th>
                    <th class="p-4">Timestamp</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 font-mono text-xs">
                @forelse($deliveries as $delivery)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="p-4">
                            @if($delivery->status === 'success')
                                <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-1 rounded-full font-sans font-medium">
                                    {{ $delivery->status_code }} OK
                                </span>
                            @elseif($delivery->status === 'failed')
                                <span class="bg-rose-500/10 text-rose-400 border border-rose-500/20 px-2.5 py-1 rounded-full font-sans font-medium">
                                    {{ $delivery->status_code ?? 'ERR' }} FAIL
                                </span>
                            @else
                                <span class="bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2.5 py-1 rounded-full font-sans font-medium">
                                    PENDING
                                </span>
                            @endif
                        </td>
                        <td class="p-4 font-sans font-medium text-slate-200">
                            {{ $delivery->event->event_type ?? 'N/A' }}
                        </td>
                        <td class="p-4 text-slate-400 truncate max-w-xs">
                            {{ $delivery->endpoint->url ?? 'N/A' }}
                        </td>
                        <td class="p-4">
                            <span class="text-slate-300 font-bold">{{ $delivery->attempts }}</span>/5
                        </td>
                        <td class="p-4 text-slate-400">
                            {{ $delivery->execution_time_ms ? $delivery->execution_time_ms . 'ms' : '-' }}
                        </td>
                        <td class="p-4 text-slate-400 font-sans">
                            {{ $delivery->created_at->diffForHumans() }}
                        </td>
                        <td class="p-4 text-right font-sans">
                            <button wire:click="inspectDelivery({{ $delivery->id }})" class="text-indigo-400 hover:text-indigo-300 font-medium mr-3">
                                Inspect
                            </button>
                            <button wire:click="replayWebhook({{ $delivery->id }})" class="text-slate-400 hover:text-white font-medium">
                                Replay
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-500 font-sans">
                            No webhook deliveries recorded for this tenant yet. Try dispatching a test event above!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-800 bg-slate-800/20">
            {{ $deliveries->links() }}
        </div>
    </div>

    <!-- Payload Modal Drawer -->
    @if($selectedDelivery)
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex justify-end transition-all">
            <div class="w-full max-w-2xl bg-slate-900 border-l border-slate-800 h-full p-6 overflow-y-auto shadow-2xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <h3 class="font-bold text-lg text-white">Delivery Inspection #{{ $selectedDelivery->id }}</h3>
                        <button wire:click="$set('selectedDelivery', null)" class="text-slate-400 hover:text-white text-2xl font-bold">&times;</button>
                    </div>

                    <div class="mt-6 space-y-5 font-mono text-xs">
                        <div>
                            <label class="text-slate-400 font-sans text-xs uppercase tracking-wider block mb-1">Target Endpoint URL:</label>
                            <p class="p-3 bg-slate-800 rounded-lg text-slate-200 break-all border border-slate-700/50">{{ $selectedDelivery->endpoint->url }}</p>
                        </div>

                        <div>
                            <label class="text-slate-400 font-sans text-xs uppercase tracking-wider block mb-1">Event Payload (JSON):</label>
                            <pre class="p-3 bg-slate-950 rounded-lg text-indigo-300 overflow-x-auto border border-slate-800">{{ json_encode($selectedDelivery->event->payload, JSON_PRETTY_PRINT) }}</pre>
                        </div>

                        <div>
                            <label class="text-slate-400 font-sans text-xs uppercase tracking-wider block mb-1">Server Response Body / Error Log:</label>
                            <pre class="p-3 bg-slate-950 rounded-lg text-rose-300 overflow-x-auto border border-slate-800">{{ $selectedDelivery->response_body ?? 'No response content recorded.' }}</pre>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800 flex justify-end gap-3 font-sans">
                    <button wire:click="replayWebhook({{ $selectedDelivery->id }})" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        Replay Webhook Now
                    </button>
                    <button wire:click="$set('selectedDelivery', null)" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-lg text-sm font-medium transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
