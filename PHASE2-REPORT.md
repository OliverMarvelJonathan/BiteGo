# BiteGo Phase 2 — Completion Report

> Branch: `feature/ordering-system`
> Report generated from the actual working tree and live `bitego` database state.
> Phase 3 was not started. No application code was modified while producing this report.

---

## 1. Initial Qoder Progress Discovered

At takeover, the repository was on branch `feature/ordering-system`, up to date with `origin/feature/ordering-system`. The last commit was `9bea947 Change CardView for HomePage`, and Phase 2 existed entirely as uncommitted work:

- **10 modified tracked files**
- **5 untracked paths / 6 untracked files**
  - `app/Services/OrderService.php`
  - `database/migrations/2026_11_08_000001_add_pickup_date_and_unique_code_to_orders_table.php`
  - `database/migrations/2026_11_08_000002_add_stock_deducted_to_orders_table.php`
  - `tests/Feature/OrderIntegrityTest.php`
  - `tests/Feature/SecurityTest.php`

A baseline test run before any completion work produced:

- **56 tests passed**
- **157 assertions**
- **0 failures**
- **0 skipped**

This established that Qoder had implemented most of Phase 2 functionally, not merely scaffolded it. The audit classification at takeover was:

| Area | Status at takeover |
|---|---|
| P0-A Stock consistency / transactions | Complete and verified |
| P0-B Status enforcement | Complete and verified |
| P0-C Cancellation / stock restoration | Partially implemented — no record of whether stock was actually deducted |
| P0-D Security | Complete and verified |
| P0-E Pickup-code uniqueness | Complete and verified |
| Automated tests | Present and passing; concurrency coverage was indirect |

---

## 2. Completed Tasks

### P0-A — Stock Consistency and Order Transactions

- Order creation is wrapped in `DB::transaction`.
- Menus are read with `lockForUpdate()` ordered by `id` to obtain InnoDB row-level locks and reduce deadlocks.
- Quantities are aggregated per menu and sorted before locking.
- Stock availability and `is_available` are validated while the row is locked.
- Stock is decremented with a conditional `WHERE stock >= quantity` guard.
- Failed order/item writes roll back the entire transaction, leaving no partial orders.
- Concurrent stale-model ordering cannot oversell because stock is re-read under lock.
- The `menus.stock` column is unsigned, providing an additional database-level guard against negative stock.

### P0-B — Order Status Enforcement

- Lifecycle enforced: `menunggu → dimasak → siap_ambil → selesai`.
- `STATUS_NEXT` is actively used by `Order::canTransitionTo()`.
- Skipped transitions, backward transitions, unknown statuses, and reopening completed orders are rejected.
- Vendor ownership is checked against the order's stall.
- Students cannot update vendor order statuses (`role:vendor` middleware).
- Cancellation is limited to `menunggu` via `Order::CANCELLABLE_STATUSES`.

### P0-C — Cancellation and Stock Restoration

- Student cancellation (`OrderController@cancel`) and vendor cancellation (`VendorController@updateStatus`) both use the same locked `OrderService::transition()`.
- Cancellation and stock restoration happen atomically.
- Order row locking prevents duplicate restoration.
- Cancellation after processing (`dimasak` or later) is rejected.
- Added `orders.stock_deducted` tracking so stock is restored **only if it was actually deducted**, and the flag is cleared after restoration.

### P0-D — Security

- Fixed the injection issue in `vendor/menu.blade.php`.
- Open-redirect protection added to login.
- Session regeneration on login and registration.
- Login rate limiting and registration rate limiting.
- Authorization checks preserved on orders, success pages, cancellation, and vendor menus/statuses.
- Validation applied to order quantities, pickup slots, notes, menu fields, and statuses.
- No unsafe Blade output remains (`{!! !!}`, `@json`, or raw user data in JavaScript were not found).

### P0-E — Pickup-Code Uniqueness

- `pickup_date` added to orders and backfilled from `created_at`.
- Unique index added on `(stall_id, pickup_date, pickup_code)`.
- Code generation checks for collisions and retries on unique-constraint failure.
- Existing data checked before/after: **0 duplicate pickup-code groups**.

---

## 3. Modified and Created Files

### Modified tracked files

- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/OrderController.php`
- `app/Http/Controllers/VendorController.php`
- `app/Models/Order.php`
- `app/Providers/AppServiceProvider.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/views/orders/create.blade.php`
- `resources/views/vendor/dashboard.blade.php`
- `resources/views/vendor/menu.blade.php`
- `routes/web.php`

### Created / untracked files

- `app/Services/OrderService.php`
- `database/migrations/2026_11_08_000001_add_pickup_date_and_unique_code_to_orders_table.php`
- `database/migrations/2026_11_08_000002_add_stock_deducted_to_orders_table.php`
- `tests/Feature/OrderIntegrityTest.php`
- `tests/Feature/SecurityTest.php`

No commits were created. All Phase 2 work remains in the working tree.

---

## 4. Database Changes

### Migration status

All 11 migrations are applied:

| Migration | Batch | Status |
|---|---:|---|
| `0001_01_01_000000_create_users_table` | 1 | Ran |
| `0001_01_01_000001_create_cache_table` | 1 | Ran |
| `0001_01_01_000002_create_jobs_table` | 1 | Ran |
| `2026_09_10_000001_add_profile_fields_to_users_table` | 1 | Ran |
| `2026_09_10_000002_create_stalls_table` | 1 | Ran |
| `2026_09_10_000003_create_categories_table` | 1 | Ran |
| `2026_09_10_000004_create_menus_table` | 1 | Ran |
| `2026_09_10_000005_create_orders_table` | 1 | Ran |
| `2026_09_10_000006_create_order_items_table` | 1 | Ran |
| `2026_11_08_000001_add_pickup_date_and_unique_code_to_orders_table` | 2 | Ran |
| `2026_11_08_000002_add_stock_deducted_to_orders_table` | 3 | Ran |

### Schema changes

- `orders.pickup_date` — `DATE NOT NULL`, backfilled from `created_at`.
- Unique index — `orders_stall_id_pickup_date_pickup_code_unique` on `(stall_id, pickup_date, pickup_code)`.
- `orders.stock_deducted` — `TINYINT(1) NOT NULL DEFAULT 0`; existing rows backfilled to `1`.

### Current live database safety verification (`bitego`)

- Orders: **9**
- Status distribution: `menunggu` 1, `dimasak` 2, `siap_ambil` 1, `selesai` 2, `dibatalkan` 3
- Duplicate pickup-code groups: **0**
- Null `pickup_date` rows: **0**
- No `migrate:fresh`, no drops, no truncation, no database reset, and no destructive seeders were used.
- Tests use the isolated `bitego_test` database configured in `phpunit.xml`.

---

## 5. Tests Executed and Results

Executed with:

```sh
php artisan test --colors=never
```

Final result:

- **Tests:** 58
- **Passed:** 58
- **Assertions:** 165
- **Duration:** ~15.4 seconds
- **Result:** `passed`

Baseline result before completion work:

- **Tests:** 56
- **Passed:** 56
- **Assertions:** 157

Additional verification executed:

```sh
php vendor/bin/pint --test
git diff --check
```

- Pint: **passed**
- Whitespace check: **clean**

Test distribution:

| Test file | Tests |
|---|---:|
| `OrderIntegrityTest.php` | 27 |
| `SecurityTest.php` | 13 |
| `VendorMenuManagementTest.php` | 9 |
| `OrderingTest.php` | 7 |
| `ExampleTest.php` (Feature) | 1 |
| `tests/Unit/ExampleTest.php` | 1 |
| **Total** | **58** |

Coverage includes successful ordering, insufficient stock, stale/concurrent stock safety, stock deduction, stock restoration, duplicate cancellation prevention, invalid and valid transitions, vendor ownership, role restrictions, pickup-code uniqueness, Blade output safety, and login rate limiting.

### Failed or skipped tests

- **Failed:** 0
- **Skipped:** 0
- **Incomplete:** 0

All 58 tests passed with no warnings reported by Pest.

---

## 6. Security Fixes Performed

### `vendor/menu.blade.php`

The menu name was previously interpolated directly into a single-quoted JavaScript string inside `onclick`. A quote in the menu name could break out of the JavaScript string. It now uses escaped `data-menu-name` / `data-delete-form` attributes and reads them via `dataset`.

### Open redirect

`AuthController` now only allows same-origin absolute paths. It rejects:

- Protocol-relative URLs such as `//evil.example`
- Backslash variants such as `/\evil.example`
- Whitespace and control characters

### Session security

- Session regeneration after both login and registration.

### Rate limiting

- Login: 5 attempts per minute per email + IP, plus 30 per minute per IP.
- Registration: 30 per hour per IP.

### Authorization

- Order ownership enforced on show, success, and cancellation.
- Vendor ownership enforced on status updates and menu management.
- Students and vendors are separated by role middleware.

### Input validation / mass assignment

- Explicit validation for quantity, pickup slot, notes, status, menu price, stock, category, and image URL.
- Explicit `$fillable` on models and explicit attribute arrays in controllers.
- No request-wide mass assignment found.

### Output safety

- No unescaped Blade output (`{!! !!}`) found.
- No unsafe `@json` or raw user data embedded in inline scripts.
- Menu name rendering is escaped in HTML and JavaScript contexts.

### Pickup-code collisions

- Database-level unique index plus application-level collision detection and retry.

---

## 7. Remaining Bugs and Risks

1. **Seeder stock inconsistency:** Demo seed orders do not decrement menu stock. They are display-only and are never seeded with cancellable statuses. New seeded orders default to `stock_deducted = false`, so they cannot over-restore stock.
2. **Concurrency test depth:** The test suite verifies stale-model safety and InnoDB locking behavior, but does not run a multi-process load test. The real guarantee is row locking at the database level.
3. **Pickup-code scope:** Uniqueness is per **stall per day**, not globally per day. This is appropriate for the current 4-digit stall-scoped pickup flow.
4. **Backfill assumption:** Existing orders were marked `stock_deducted = true`. In the current `bitego` database, the only cancellable order (`id 9`, `menunggu`) is a real order, so this is safe. A different deployment containing legacy demo `menunggu` orders that never deducted stock could over-restore stock if cancelled.
5. **Single-item UI:** The service supports multi-item orders, but the current order form still creates one menu per order. The existing `order_items` schema is preserved.
6. **Uncommitted work:** All Phase 2 changes remain uncommitted. This is not a functional defect, but the work should be committed deliberately when the user is ready.
7. **Rate-limit storage dependency:** Throttling depends on the configured cache store. It is correctly configured for the current environment.

**Phase 2 status: COMPLETE AND VERIFIED.** No Phase 3 work was started.
