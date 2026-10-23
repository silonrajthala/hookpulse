# ⚡ HookPulse

> **Production-Grade, Multi-Tenant B2B Webhook Management & Telemetry Engine**

HookPulse is an enterprise-grade, high-reliability B2B webhook dispatch and management platform built on **Laravel 13**, **Livewire 4**, and **Tailwind CSS**. It is designed to handle asynchronous event delivery, subscription filtering, exponential backoff retries, cryptographic security signatures, and real-time telemetry across multi-tenant workloads with absolute data isolation.

---

## 🌟 Key Features

- **🔒 Multi-Tenant Data Isolation:** Single-database architecture powered by Eloquent Global Scopes (`BelongsToTenant`) and a singleton `TenantContext` service.
- **⚡ Asynchronous Delivery Engine:** Queue-backed asynchronous execution supporting PostgreSQL database queues and Redis/Horizon.
- **🔄 Exponential Backoff Retries:** Built-in fault tolerance using a staggered retry schedule (`10s`, `60s`, `300s`, `1800s`) across failed delivery attempts.
- **🔑 HMAC SHA-256 Signature Verification:** Standardized payload security using `X-Webhook-Signature` and timestamp headers to guarantee authenticity and prevent replay attacks.
- **🎯 Subscription-Based Event Filtering:** Flexible event routing allowing target endpoints to subscribe to specific event types (e.g., `invoice.paid`, `user.registered`) or wildcard (`*`) patterns.
- **🛡️ Idempotency & Deduplication Guard:** Time-window hash verification protecting downstream consumers against accidental duplicate triggers and payload hammering.
- **📊 Live Telemetry Dashboard:** Reactive Livewire 4 panel providing tenant context switching, metric snapshots, request inspection drawers, and manual delivery replay triggers.

---

## 🏗️ Architecture & Tech Stack

- **Framework:** [Laravel 1](https://laravel.com/)
- **Frontend / UI:** [Livewire 4](https://livewire.laravel.com/) + [Tailwind CSS](https://tailwindcss.com/)
- **Database:** [PostgreSQL](https://www.postgresql.org/)
- **Queue Engine:** Database-backed driver (`QUEUE_CONNECTION=database`) or Redis/Horizon
- **Language:** PHP 8.2+

### Multi-Tenancy Architecture

Data isolation is strictly enforced at the Eloquent ORM level:

1. `TenantContext` singleton retains the active tenant scope per request/job lifecycle.
2. `IdentifyTenant` middleware extracts tenant headers/session contexts.
3. `BelongsToTenant` model trait injects `TenantScope`, applying `WHERE tenant_id = ?` to all database interactions and auto-assigning tenant ownership on resource creation.

---

## ⚙️ Installation & Setup

### 1. Repository Setup & Dependencies

```bash
# Clone the repository
git clone https://github.com/your-username/hookpulse.git
cd hookpulse

# Install PHP dependencies
composer install

# Copy environment template
cp .env.example .env
```

### 2. Environment Configuration

Update your `.env` file with your PostgreSQL database credentials and queue configuration:

```env
APP_NAME=HookPulse
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hookpulse_db
DB_USERNAME=postgres
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
CACHE_STORE=database
```

Generate your application key:

```bash
php artisan key:generate
```

### 3. Database Migrations & Seeding

Prepare database tables and populate mock tenants, secrets, and test endpoints:

```bash
# Run migrations
php artisan migrate

# Create database queue tables
php artisan queue:table
php artisan queue:failed-table
php artisan migrate

# Seed sample tenants and webhook endpoints
php artisan db:seed --class=WebhookPlatformSeeder
```

---

## 🚀 Running the Platform

To run HookPulse locally, open two terminal windows:

### Terminal 1: Web Application Server

```bash
php artisan serve
```

Access the application dashboard at `http://localhost:8000`.

### Terminal 2: Queue Worker

Process background delivery jobs, retries, and exponential backoffs:

```bash
php artisan queue:work database --tries=5 --backoff=10,60,300
```

*(Note: If utilizing Redis/Horizon in production environments, run `php artisan horizon` instead).*

---

## 🧪 Testing Webhook Delivery Flow

### 1. Local Endpoint Testing with Mock Receiver

HookPulse includes a built-in mock endpoint for verification:

1. Ensure an endpoint is registered with the URL:  
   `http://localhost:8000/api/mock-receiver`
2. Select **Acme Logistics GmbH** from the **Active Tenant** dropdown on the top right.
3. In the **Broadcast Test Event** card, supply an event name and JSON payload:
   - **Event Name:** `invoice.paid`
   - **JSON Payload:** `{"invoice_id": "inv_9011", "amount": 250.00, "currency": "EUR"}`
4. Click **Dispatch Event**.
5. Watch the queue worker process delivery and review the execution status (`200 OK`), payload contents, and headers in the telemetry log table.

### 2. External Verification via Webhook.site

To test external payload receipt without port binding conflicts:

1. Obtain a test URL from [Webhook.site](https://webhook.site).
2. Update an endpoint's URL in the database or via Artisan Tinker:
   ```bash
   php artisan tinker
   ```
   ```php
   App\Models\Endpoint::first()->update(['url' => 'https://webhook.site/your-unique-token']);
   ```
3. Trigger a test event from the dashboard and inspect the incoming payload and `X-Webhook-Signature` header in real time.

---

## 🔒 HMAC SHA-256 Security Verification

Every outgoing payload includes a signature generated using the endpoint's secret key:

```php
$timestamp = time();
$signedPayload = "{$timestamp}.{$jsonPayload}";
$signature = 'sha256=' . hash_hmac('sha256', $signedPayload, $endpointSecret);
```

### Request Headers

Receiving endpoints can verify authenticity by inspecting these headers:

- `X-Webhook-Signature`: HMAC SHA-256 hash.
- `X-Webhook-Timestamp`: Unix timestamp of dispatch.
- `X-Webhook-Id`: Unique `WebhookDelivery` log ID.

---

## 📄 License

This project is open-source software licensed under the [MIT License](LICENSE).
