<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 10;

    public function __construct(public WebhookDelivery $delivery) {}

    /**
     * Exponential Backoff Schedule in seconds
     * Attempt 1: Immediate
     * Attempt 2: 10s | Attempt 3: 60s | Attempt 4: 300s (5m) | Attempt 5: 1800s (30m)
     */
    public function backoff(): array
    {
        return [10, 60, 300, 1800];
    }

    public function handle(): void
    {
        $endpoint =$this->delivery->endpoint;
        $event =$this->delivery->event;

        $payload = [
            'id' => $event->id,
            'event' => $event->event_type,
            'data' => $event->payload,
            'created_at' => $event->created_at->toIso8601String(),
        ];

        $jsonPayload = json_encode($payload);$timestamp = time();

        // Generate Security HMAC SHA-256 Signature
        $signature = $this->generateHmacSignature($jsonPayload, $timestamp,$endpoint->secret);

        $startTime = microtime(true);

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'HookPulse-Webhook-Worker/1.0',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Timestamp' => $timestamp,
                    'X-Webhook-Id' => (string) $this->delivery->id,
                ])
                ->withBody($jsonPayload, 'application/json')
                ->post($endpoint->url);

            $durationMs = (int) round((microtime(true) -$startTime) * 1000);

            $this->delivery->update([
                'attempts' => $this->attempts(),
                'status_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 1500), 'execution_time_ms' =>$durationMs,
                'status' => $response->successful() ? 'success' : 'failed',
                'delivered_at' => $response->successful() ? now() : null,
            ]);

            if ($response->failed()) {$this->fail(new \Exception("Receiver endpoint responded with status HTTP {$response->status()}"));
            }

        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) -$startTime) * 1000);

            $this->delivery->update([
                'attempts' => $this->attempts(),
                'status_code' => 500,
                'response_body' => 'Connection Exception: ' . $e->getMessage(),
                'execution_time_ms' => $durationMs,
                'status' => 'failed',
            ]);

            throw $e; // Triggers queue worker retry via backoff schedule
        }
    }

    private function generateHmacSignature(string $payload, int $timestamp, string$secret): string
    {
        $signedPayload = "{$timestamp}.{$payload}";
        return 'sha256=' . hash_hmac('sha256', $signedPayload,$secret);
    }
}
