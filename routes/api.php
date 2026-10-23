<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/mock-receiver', function (Request $request) {
    // Optional: Validate HMAC Signature header sent by HookPulse
    $signature = $request->header('X-Webhook-Signature');
    $timestamp = $request->header('X-Webhook-Timestamp');
    $webhookId = $request->header('X-Webhook-Id');

    // Return a successful response verifying receipt
    return response()->json([
        'status' => 'received',
        'message' => 'Webhook successfully processed by client receiver.',
        'received_at' => now()->toIso8601String(),
        'debug_headers' => [
            'signature_present' => !empty($signature),
            'timestamp' => $timestamp,
            'webhook_id' => $webhookId,
        ],
        'payload_received' => $request->all(),
    ], 200);
});
