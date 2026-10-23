<?php

namespace Database\Seeders;

use App\Models\Endpoint;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WebhookPlatformSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Tenant A (Acme Logistics)
        $tenantA = Tenant::create([
            'name' => 'Acme Logistics GmbH',
            'slug' => 'acme-logistics',
            'api_key' => 'hp_live_' . Str::random(32),
        ]);

        // Create an endpoint for Tenant A
        Endpoint::create([
            'tenant_id' => $tenantA->id,
            'url' => 'http://localhost:8000/api/mock-receiver', // points to our mock receiver route below
            'secret' => 'whsec_' . Str::random(32),
            'is_active' => true,
            'subscribed_events' => ['invoice.paid', 'user.registered'],
        ]);

        // 2. Create Tenant B (FlowCorp EU)
        $tenantB = Tenant::create([
            'name' => 'FlowCorp EU',
            'slug' => 'flow-corp',
            'api_key' => 'hp_live_' . Str::random(32),
        ]);

        Endpoint::create([
            'tenant_id' => $tenantB->id,
            'url' => 'http://localhost:8000/api/mock-receiver',
            'secret' => 'whsec_' . Str::random(32),
            'is_active' => true,
            'subscribed_events' => ['order.shipped'],
        ]);
    }
}
