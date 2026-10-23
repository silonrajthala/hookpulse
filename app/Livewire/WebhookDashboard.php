<?php

namespace App\Livewire;

use App\Jobs\DeliverWebhookJob;
use App\Models\Endpoint;
use App\Models\Event;
use App\Models\Tenant;
use App\Models\WebhookDelivery;
use App\Services\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookDashboard extends Component
{
    use WithPagination;

    public int $selectedTenantId;
    public ?WebhookDelivery $selectedDelivery = null;

    public string $newEventPayload = '{"invoice_id": "inv_9011", "amount": 250.00, "currency": "EUR"}';
    public string $newEventName = 'invoice.paid';

    public function mount()
    {
        $this->selectedTenantId = app(TenantContext::class)->getTenantId() ?? Tenant::first()?->id ?? 1;
        session(['selected_tenant_id' => $this->selectedTenantId]);
    }

    public function updatedSelectedTenantId($value)
    {
        session(['selected_tenant_id' => $value]);
        app(TenantContext::class)->setTenantId($value);
        $this->resetPage();
    }

    public function inspectDelivery(int $id)
    {
        $this->selectedDelivery = WebhookDelivery::with(['endpoint', 'event'])->find($id);
    }

    public function replayWebhook(int $deliveryId)
    {
        $original = WebhookDelivery::findOrFail($deliveryId);

        $newDelivery = WebhookDelivery::create([
            'tenant_id' => $original->tenant_id,
            'endpoint_id' => $original->endpoint_id,
            'event_id' => $original->event_id,
            'status' => 'pending',
            'attempts' => 0,
        ]);

        DeliverWebhookJob::dispatch($newDelivery);

        session()->flash('message', "Webhook replay dispatched successfully! New Delivery ID: #{$newDelivery->id}");
    }

    public function triggerTestEvent()
    {
        $payloadData = json_decode($this->newEventPayload, true);
        if (!$payloadData) {
            session()->flash('error', 'Invalid JSON payload structure.');
            return;
        }

        // 1. Generate a unique hash for this event name + payload combination
        $payloadString = json_encode($payloadData);
        $deduplicationHash = hash('sha256', $this->newEventName . '.' . $payloadString);

        // 2. Check if an identical event was dispatched within the last 30 seconds
        $duplicateEvent = Event::where('event_type', $this->newEventName)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->get()
            ->first(function ($event) use ($payloadData) {
                // Compare payloads strictly
                return $event->payload == $payloadData;
            });

        if ($duplicateEvent) {
            session()->flash('error', "Duplicate event blocked! An identical '{$this->newEventName}' event was already dispatched recently.");
            return;
        }

        // 3. Create the event safely if it's unique
        $event = Event::create([
            'event_type' => $this->newEventName,
            'payload' => $payloadData,
        ]);

        // 4. Fetch active endpoints matching subscriptions
        $endpoints = Endpoint::where('is_active', true)->get()->filter(function ($endpoint) {
            $subscriptions = $endpoint->subscribed_events ?? [];
            return in_array('*', $subscriptions) || in_array($this->newEventName, $subscriptions);
        });

        if ($endpoints->isEmpty()) {
            session()->flash('error', "Event '{$this->newEventName}' recorded, but no active endpoints are subscribed to it.");
            return;
        }

        foreach ($endpoints as $endpoint) {
            $delivery = WebhookDelivery::create([
                'endpoint_id' => $endpoint->id,
                'event_id' => $event->id,
                'status' => 'pending',
            ]);

            DeliverWebhookJob::dispatch($delivery);
        }

        session()->flash('message', "Unique event '{$this->newEventName}' broadcasted to " . $endpoints->count() . " subscribed endpoint(s).");
    }

    public function render()
    {
        $tenants = Tenant::all();
        $deliveries = WebhookDelivery::with(['endpoint', 'event'])
            ->latest()
            ->paginate(10);

        $stats = [
            'total' => WebhookDelivery::count(),
            'successful' => WebhookDelivery::where('status', 'success')->count(),
            'failed' => WebhookDelivery::where('status', 'failed')->count(),
            'success_rate' => WebhookDelivery::count() > 0
                ? round((WebhookDelivery::where('status', 'success')->count() / WebhookDelivery::count()) * 100, 1)
                : 100,
        ];

        return view('livewire.webhook-dashboard', compact('tenants', 'deliveries', 'stats'));
    }
}
