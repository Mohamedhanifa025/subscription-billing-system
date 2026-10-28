# Subscription Billing & Usage-Metering System

Laravel REST backend + Vue 3 merchant dashboard for multi-tenant subscription usage, metering, and billing.

## Requirements
- PHP 8.2+, Composer
- MySQL 8+
- Node 20+, npm
- Redis (recommended for queues and cache)

## Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
# Set DB_* and REDIS_* in .env
php artisan migrate
npm install
npm run build
php artisan serve
php artisan queue:work --queue=default
```
For local quick testing, set `QUEUE_CONNECTION=database` and `CACHE_STORE=database` if Redis is not installed. Ensure the Laravel skeleton has the standard `bootstrap/app.php`, config, and public files.

## Main endpoints
- `POST /api/usage` — record a usage event; send `Idempotency-Key` in body as `idempotency_key`.
- `GET /api/merchants/{id}/dashboard`
- `GET|POST /api/merchants/{merchant}/plans`
- `GET|POST /api/merchants/{merchant}/subscriptions`
- `POST /api/subscriptions/{subscription}/change-plan`
- `POST /api/subscriptions/{subscription}/invoice` — body `period_start`, `period_end`.
- `GET /api/subscriptions/{subscription}/invoices`

Usage example:
```json
{
  "merchant_id": 1,
  "customer_id": 1,
  "subscription_id": 1,
  "usage_date": "2026-10-26",
  "quantity": 20,
  "idempotency_key": "event-unique-uuid"
}
```

## Schema and scale (5M+ events)
- `usage_events` is append-oriented, with unique `(merchant_id,idempotency_key)` for idempotency and indexes for subscription/date and merchant/date/customer access paths.
- `daily_usage_summaries` is a compact read model with unique `(subscription_id,usage_date)`.
- At higher volumes, partition usage events by month on `usage_date` (MySQL partitioning requires all unique keys to include the partition key; migrate idempotency to a separate nonpartitioned key registry or include date in key design). Archive closed periods to object storage/warehouse.
- Keep payload metadata small; avoid indexing JSON. Batch inserts and aggregate with `chunkById` / grouped SQL rather than loading events into PHP memory.
- Consider a separate write-optimized ingestion store or Kafka if throughput grows beyond a single MySQL primary.

## Idempotency and queues
A unique database constraint is the source of truth. Retries use the same key; only first insert dispatches aggregation. Aggregation recomputes the daily sum (idempotent under at-least-once queue delivery). For very large daily partitions, use chunked scans or SQL SUM against indexed ranges and a scheduled reconciliation job.

## Billing assumptions
- Prices are integer minor units (cents/paise); never use floating point for stored currency.
- Included usage and overage are applied per segment. A plan change closes the old effective segment and opens a new segment; pricing is snapshotted in the segment so later plan edits do not rewrite historical billing.
- Base charge is prorated by active seconds divided by nominal cycle duration (30 days monthly, 365 days yearly); document and adjust if the product uses calendar-month proration.
- Overage is `max(usage - included_units, 0) * overage_rate_cents`.
- Invoice creation is unique per subscription/period and transaction-protected.
- Clarify product policy whether included units reset per segment or are pooled across a billing cycle. This exercise applies allowance per segment.

## Cache
Cache plan snapshots by `plan:{id}:v{version}` for 10 minutes. On plan create/update/delete, increment version and evict affected key (or use a tagged cache in Redis). Do not cache usage writes or invoice totals. This starter invalidates the `plans` tag on plan mutations; use Redis tag support or explicit keys in production.

## Dashboard
Returns top customers by current-month summary usage, projected overage revenue, month-over-month drop >50%, daily trend, and informational status. A production projection should calculate remaining-cycle usage forecast using daily pace and each active subscription's segment allowance; the supplied implementation reports current overage amount as a simple transparent baseline.

## Tests
```bash
php artisan test
```
Tests cover idempotent usage creation and arithmetic boundaries (zero overage, excess usage, and partial-period proration). Add integration tests for segment allocation, invoice uniqueness, tenant isolation, and month/year edge dates before production.

## Trade-offs / next steps
- This is an exercise-ready starter and keeps domain logic in services/jobs rather than controllers.
- Add API authentication/API keys, policy authorization, tenant-scoped route binding, webhook/outbox delivery, invoice tax/discount handling, and payment-provider integration.
- Add chunked nightly backfill/reconciliation, observability, dead-letter handling, and dashboard projection forecasts.
- Add factories/seeders and a more complete test matrix.
- Prompt log: record actual AI prompts/screenshots used by the developer in `/prompts`; do not fabricate screenshots.
